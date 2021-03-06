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

namespace Zikula\GroupsModule\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\GroupsModule\Constant;
use Zikula\GroupsModule\Entity\GroupEntity;
use Zikula\GroupsModule\Entity\Repository\GroupApplicationRepository;
use Zikula\GroupsModule\Helper\CommonHelper;
use Zikula\PermissionsModule\Api\ApiInterface\PermissionApiInterface;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\Entity\RepositoryInterface\UserRepositoryInterface;

class MenuBuilder
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var PermissionApiInterface
     */
    private $permissionApi;

    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CurrentUserApiInterface
     */
    private $currentUserApi;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var GroupApplicationRepository
     */
    private $groupApplicationRepository;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        FactoryInterface $factory,
        PermissionApiInterface $permissionApi,
        VariableApiInterface $variableApi,
        RequestStack $requestStack,
        CurrentUserApiInterface $currentUserApi,
        UserRepositoryInterface $userRepository,
        GroupApplicationRepository $groupApplicationRepository,
        RouterInterface $router
    ) {
        $this->factory = $factory;
        $this->permissionApi = $permissionApi;
        $this->variableApi = $variableApi;
        $this->requestStack = $requestStack;
        $this->currentUserApi = $currentUserApi;
        $this->userRepository = $userRepository;
        $this->groupApplicationRepository = $groupApplicationRepository;
        $this->router = $router;
    }

    public function createAdminMenu(array $options): ItemInterface
    {
        $defaultGroup = $this->variableApi->get('ZikulaGroupsModule', 'defaultgroup');
        /** @var GroupEntity $group */
        $group = $options['group'];
        $gid = $group->getGid();
        $routeParams = ['gid' => $gid];
        $menu = $this->factory->createItem('adminActions');
        $menu->setChildrenAttribute('class', 'list-inline');
        $menu->addChild('Edit group', [
            'route' => 'zikulagroupsmodule_group_edit',
            'routeParameters' => $routeParams,
        ])->setAttribute('icon', 'fas fa-pencil-alt');
        if (Constant::GROUP_ID_ADMIN !== $gid
            && $defaultGroup !== $gid
            && $this->permissionApi->hasPermission('ZikulaGroupsModule::', $gid . '::', ACCESS_DELETE)) {
            $menu->addChild('Delete group', [
                'route' => 'zikulagroupsmodule_group_remove',
                'routeParameters' => $routeParams,
            ])->setAttribute('icon', 'fas fa-trash-alt');
        }
        $menu->addChild('Group membership', [
            'route' => 'zikulagroupsmodule_membership_adminlist',
            'routeParameters' => $routeParams,
        ])->setAttribute('icon', 'fas fa-users');

        return $menu;
    }

    public function createUserMenu(array $options): ItemInterface
    {
        /** @var GroupEntity $group */
        $group = $options['group'];
        $gid = $group->getGid();
        $menu = $this->factory->createItem('userActions');
        $menu->setChildrenAttribute('class', 'list-inline');
        $requestAttributes = $this->requestStack->getCurrentRequest()->attributes->all();
        $currentUserId = $this->currentUserApi->get('uid');
        if (null !== $currentUserId) {
            $currentUser = $this->userRepository->find($currentUserId);
        }

        if ('zikulagroupsmodule_membership_list' !== $requestAttributes['_route']
            && $this->permissionApi->hasPermission('ZikulaGroupsModule::', $gid . '::', ACCESS_READ)
            && (CommonHelper::GTYPE_PUBLIC === $group->getGtype()
                || (CommonHelper::GTYPE_PRIVATE === $group->getGtype() && isset($currentUser) && $group->getUsers()->contains($currentUser)))
        ) {
            $menu->addChild('View membership of group', [
                'route' => 'zikulagroupsmodule_membership_list',
                'routeParameters' => ['gid' => $gid],
            ])->setAttribute('icon', 'fas fa-users');
        }
        if (isset($currentUser)) {
            if ($group->getUsers()->contains($currentUser)) {
                $menu->addChild('Leave group', [
                    'route' => 'zikulagroupsmodule_membership_leave',
                    'routeParameters' => ['gid' => $gid],
                ])->setAttribute('icon', 'fas fa-user-times text-danger');
            } elseif (CommonHelper::GTYPE_PRIVATE === $group->getGtype()) {
                $existingApplication = $this->groupApplicationRepository->findOneBy(['group' => $group, 'user' => $currentUser]);
                if ($existingApplication) {
                    $menu->addChild('Applied!');
                } else {
                    $menu->addChild('Apply to membership of group', [
                        'route' => 'zikulagroupsmodule_application_create',
                        'routeParameters' => ['gid' => $gid],
                    ])->setAttribute('icon', 'fas fa-paper-plane');
                }
            } elseif (CommonHelper::STATE_CLOSED !== $group->getState()) {
                $menu->addChild('Join group', [
                    'route' => 'zikulagroupsmodule_membership_join',
                    'routeParameters' => ['gid' => $gid],
                ])->setAttribute('icon', 'fas fa-user-plus text-success');
            }
        } else {
            $returnUrl = $this->router->generate('zikulagroupsmodule_membership_list', ['gid' => $gid], UrlGeneratorInterface::ABSOLUTE_URL);
            $menu->addChild('Log in or register', [
                'route' => 'zikulausersmodule_access_login',
                'routeParameters' => ['returnUrl' => $returnUrl]
            ])->setAttribute('icon', 'fas fa-key');
        }

        return $menu;
    }
}
