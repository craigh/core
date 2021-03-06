<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\UsersModule\Controller;

use DateTime;
use LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Zikula\Bundle\CoreBundle\Controller\AbstractController;
use Zikula\Bundle\CoreBundle\Event\GenericEvent;
use Zikula\Bundle\HookBundle\Dispatcher\HookDispatcherInterface;
use Zikula\Bundle\HookBundle\Hook\ProcessHook;
use Zikula\Bundle\HookBundle\Hook\ValidationHook;
use Zikula\UsersModule\AccessEvents;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\AuthenticationMethodInterface\AuthenticationMethodInterface;
use Zikula\UsersModule\AuthenticationMethodInterface\NonReEntrantAuthenticationMethodInterface;
use Zikula\UsersModule\AuthenticationMethodInterface\ReEntrantAuthenticationMethodInterface;
use Zikula\UsersModule\Collector\AuthenticationMethodCollector;
use Zikula\UsersModule\Constant;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;
use Zikula\UsersModule\Entity\UserEntity;
use Zikula\UsersModule\Event\UserFormAwareEvent;
use Zikula\UsersModule\Event\UserFormDataEvent;
use Zikula\UsersModule\Exception\InvalidAuthenticationMethodLoginFormException;
use Zikula\UsersModule\Form\Type\DefaultLoginType;
use Zikula\UsersModule\Helper\AccessHelper;
use Zikula\UsersModule\HookSubscriber\LoginUiHooksSubscriber;

class AccessController extends AbstractController
{
    /**
     * @Route("/login", options={"zkNoBundlePrefix"=1})
     *
     * @throws InvalidAuthenticationMethodLoginFormException
     */
    public function loginAction(
        Request $request,
        CurrentUserApiInterface $currentUserApi,
        UserRepositoryInterface $userRepository,
        AuthenticationMethodCollector $authenticationMethodCollector,
        AccessHelper $accessHelper,
        EventDispatcherInterface $eventDispatcher,
        HookDispatcherInterface $hookDispatcher
    ): Response {
        if ($currentUserApi->isLoggedIn()) {
            return $this->redirectToRoute('zikulausersmodule_account_menu');
        }
        $returnUrl = $request->query->get('returnUrl', '');

        $session = $request->hasSession() ? $request->getSession() : null;

        $selectedMethod = null !== $session ? $session->get('authenticationMethod') : '';
        $selectedMethod = $request->query->get('authenticationMethod', $selectedMethod);
        if (empty($selectedMethod) && count($authenticationMethodCollector->getActiveKeys()) > 1) {
            // there are multiple authentication methods available and none selected yet, so let the user choose one
            return $this->render('@ZikulaUsersModule/Access/authenticationMethodSelector.html.twig', [
                'collector' => $authenticationMethodCollector,
                'path' => 'zikulausersmodule_access_login'
            ]);
        }
        if (empty($selectedMethod) && 1 === count($authenticationMethodCollector->getActiveKeys())) {
            // there is only one authentication method available, so use this
            $selectedMethod = $authenticationMethodCollector->getActiveKeys()[0];
        }
        if (null !== $session) {
            // save method to session for reEntrant needs
            $session->set('authenticationMethod', $selectedMethod);
            if (!empty($returnUrl)) {
                // save returnUrl to session for reEntrant needs
                $session->set('returnUrl', $returnUrl);
            }
        }

        $authenticationMethod = $authenticationMethodCollector->get($selectedMethod);
        $rememberMe = false;

        $eventDispatcher->dispatch(new GenericEvent(), AccessEvents::LOGIN_STARTED);

        $loginHeader = $this->renderView('@ZikulaUsersModule/Access/loginHeader.html.twig');
        $loginFooter = $this->renderView('@ZikulaUsersModule/Access/loginFooter.html.twig');

        $form = null;
        if ($authenticationMethod instanceof NonReEntrantAuthenticationMethodInterface) {
            $form = $this->createForm($authenticationMethod->getLoginFormClassName());
            if (!$form->has('rememberme')) {
                throw new InvalidAuthenticationMethodLoginFormException();
            }
            $loginFormEvent = new UserFormAwareEvent($form);
            $eventDispatcher->dispatch($loginFormEvent, AccessEvents::AUTHENTICATION_FORM);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $rememberMe = $data['rememberme'];
                $uid = $authenticationMethod->authenticate($data);
            } else {
                return $this->render($authenticationMethod->getLoginTemplateName(), [
                    'loginHeader' => $loginHeader,
                    'loginFooter' => $loginFooter,
                    'form' => $form->createView(),
                    'additional_templates' => isset($loginFormEvent) ? $loginFormEvent->getTemplates() : []
                ]);
            }
        } elseif ($authenticationMethod instanceof ReEntrantAuthenticationMethodInterface) {
            // provide temp value for uid until form gives real value.
            $uid = 'POST' === $request->getMethod() ? Constant::USER_ID_ANONYMOUS : $authenticationMethod->authenticate();
            $hasListeners = $eventDispatcher->hasListeners(AccessEvents::AUTHENTICATION_FORM);
            $hookBindings = $hookDispatcher->getBindingsFor('subscriber.users.ui_hooks.login_screen');
            if ($hasListeners || count($hookBindings) > 0) {
                $form = $this->createForm(DefaultLoginType::class, ['uid' => $uid]);
                $loginFormEvent = new UserFormAwareEvent($form);
                $eventDispatcher->dispatch($loginFormEvent, AccessEvents::AUTHENTICATION_FORM);
                if ($form->count() > 3) { // count > 3 means that the AUTHENTICATION_FORM event added some form children
                    $form->handleRequest($request);
                    if ($form->isSubmitted() && $form->isValid()) {
                        $uid = $form->get('uid')->getData();
                        $rememberMe = $form->get('rememberme')->getData();
                    } else {
                        return $this->render('@ZikulaUsersModule/Access/defaultLogin.html.twig', [
                            'loginHeader' => $loginHeader,
                            'loginFooter' => $loginFooter,
                            'form' => $form->createView(),
                            'additional_templates' => isset($loginFormEvent) ? $loginFormEvent->getTemplates() : []
                        ]);
                    }
                }
            }
        } else {
            throw new LogicException($this->trans('Invalid authentication method.'));
        }
        $user = null;
        if (isset($uid)) {
            /** @var UserEntity $user */
            $user = $userRepository->find($uid);
            if (isset($user)) {
                $hook = new ValidationHook();
                $hookDispatcher->dispatch(LoginUiHooksSubscriber::LOGIN_VALIDATE, $hook);
                $validators = $hook->getValidators();
                if (!$validators->hasErrors() && $accessHelper->loginAllowed($user)) {
                    if (isset($form)) {
                        $formDataEvent = new UserFormDataEvent($user, $form);
                        $eventDispatcher->dispatch($formDataEvent, AccessEvents::AUTHENTICATION_FORM_HANDLE);
                    }
                    $hookDispatcher->dispatch(LoginUiHooksSubscriber::LOGIN_PROCESS, new ProcessHook($user));
                    $event = new GenericEvent($user, ['authenticationMethod' => $selectedMethod]);
                    $eventDispatcher->dispatch($event, AccessEvents::LOGIN_VETO);
                    if (!$event->isPropagationStopped()) {
                        $returnUrlFromSession = null !== $session ? $session->get('returnUrl', $returnUrl) : $returnUrl;
                        $returnUrlFromSession = urldecode($returnUrlFromSession);
                        $accessHelper->login($user, $rememberMe);
                        $returnUrl = $this->dispatchLoginSuccessEvent($eventDispatcher, $selectedMethod, $returnUrlFromSession, $user);
                    } else {
                        if ($event->hasArgument('flash')) {
                            $this->addFlash('danger', $event->getArgument('flash'));
                        }
                        $returnUrl = $event->getArgument('returnUrl');
                    }

                    return !empty($returnUrl) ? $this->redirect($returnUrl) : $this->redirectToRoute('home');
                }
            }
        }
        // login failed
        // implement auto-register setting here. If true, do so and proceed. #2915
        $this->addFlash('error', 'Login failed.');
        if (null !== $session) {
            $session->remove('authenticationMethod');
        }
        $returnUrl = $this->dispatchLoginFailedEvent($eventDispatcher, $authenticationMethod, $returnUrl, $user);

        return !empty($returnUrl) ? $this->redirect($returnUrl) : $this->redirectToRoute('home');
    }

    private function dispatchLoginSuccessEvent(
        EventDispatcherInterface $eventDispatcher,
        string $selectedMethod,
        string $returnUrl,
        UserEntity $user
    ): string {
        $eventArgs = [
            'authenticationMethod' => $selectedMethod,
            'returnUrl' => $returnUrl,
        ];
        $defaultLastLogin = new DateTime('1970-01-01 00:00:00');
        $actualLastLogin = $user->getLastlogin();
        if (null === $actualLastLogin || $actualLastLogin === $defaultLastLogin) {
            $eventArgs['isFirstLogin'] = true;
        }
        $event = new GenericEvent($user, $eventArgs);
        $event = $eventDispatcher->dispatch($event, AccessEvents::LOGIN_SUCCESS);

        return $event->hasArgument('returnUrl') ? $event->getArgument('returnUrl') : $returnUrl;
    }

    private function dispatchLoginFailedEvent(
        EventDispatcherInterface $eventDispatcher,
        AuthenticationMethodInterface $authenticationMethod,
        string $returnUrl,
        UserEntity $user = null
    ) {
        $eventArgs = [
            'authenticationMethod' => $authenticationMethod,
            'returnUrl' => $returnUrl,
        ];
        $event = new GenericEvent($user, $eventArgs);
        $event = $eventDispatcher->dispatch($event, AccessEvents::LOGIN_FAILED);

        return $event->hasArgument('returnUrl') ? $event->getArgument('returnUrl') : $returnUrl;
    }

    /**
     * @Route("/logout/{returnUrl}", options={"zkNoBundlePrefix"=1})
     */
    public function logoutAction(
        Request $request,
        CurrentUserApiInterface $currentUserApi,
        UserRepositoryInterface $userRepository,
        AccessHelper $accessHelper,
        EventDispatcherInterface $eventDispatcher,
        string $returnUrl = null
    ): RedirectResponse {
        if ($currentUserApi->isLoggedIn()) {
            $uid = $currentUserApi->get('uid');
            $user = $userRepository->find($uid);
            if ($accessHelper->logout()) {
                $authMethod = null;
                if ($request->hasSession() && ($session = $request->getSession())) {
                    $authMethod = $session->get('authenticationMethod');
                }
                $event = new GenericEvent($user, [
                    'authenticationMethod' => $authMethod,
                    'uid' => $uid,
                ]);
                $eventDispatcher->dispatch($event, AccessEvents::LOGOUT_SUCCESS);
            } else {
                $this->addFlash('error', 'Error! You have not been logged out.');
            }
        }

        return isset($returnUrl)
            ? $this->redirect($returnUrl)
            : $this->redirectToRoute('home', ['_locale' => $this->getParameter('locale')])
        ;
    }
}
