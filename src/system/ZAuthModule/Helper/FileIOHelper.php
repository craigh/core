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

namespace Zikula\ZAuthModule\Helper;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\Bundle\CoreBundle\Event\GenericEvent;
use Zikula\Bundle\CoreBundle\Translation\TranslatorTrait;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\GroupsModule\Entity\GroupEntity;
use Zikula\GroupsModule\Entity\RepositoryInterface\GroupRepositoryInterface;
use Zikula\PermissionsModule\Api\ApiInterface\PermissionApiInterface;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\Constant as UsersConstant;
use Zikula\UsersModule\Entity\UserEntity;
use Zikula\UsersModule\RegistrationEvents;
use Zikula\UsersModule\UserEvents;
use Zikula\UsersModule\Validator\Constraints\ValidEmail;
use Zikula\UsersModule\Validator\Constraints\ValidUname;
use Zikula\ZAuthModule\Entity\AuthenticationMappingEntity;
use Zikula\ZAuthModule\Validator\Constraints\ValidPassword;

class FileIOHelper
{
    use TranslatorTrait;

    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var PermissionApiInterface
     */
    private $permissionApi;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MailHelper
     */
    private $mailHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CurrentUserApiInterface
     */
    private $currentUser;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    public function __construct(
        VariableApiInterface $variableApi,
        PermissionApiInterface $permissionApi,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        MailHelper $mailHelper,
        EventDispatcherInterface $eventDispatcher,
        CurrentUserApiInterface $currentUserApi,
        EncoderFactoryInterface $encoderFactory,
        GroupRepositoryInterface $groupRepository
    ) {
        $this->variableApi = $variableApi;
        $this->permissionApi = $permissionApi;
        $this->setTranslator($translator);
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->mailHelper = $mailHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->currentUser = $currentUserApi;
        $this->encoderFactory = $encoderFactory;
        $this->groupRepository = $groupRepository;
    }

    public function importUsersFromFile(UploadedFile $file, string $delimiter = ','): string
    {
        $defaultGroup = $this->variableApi->get('ZikulaGroupsModule', 'defaultgroup');
        // get available groups
        $allGroups = $this->groupRepository->findAllAndIndexBy('gid');
        // create an array with the groups identities where the user can add other users
        $allGroupsArray = [];
        foreach ($allGroups as $gid => $group) {
            if ($this->permissionApi->hasPermission('ZikulaGroupsModule::', $group['gid'] . '::', ACCESS_EDIT)) {
                $allGroupsArray[] = $gid;
            }
        }

        // read the choosen file
        ini_set('auto_detect_line_endings', true); // allows for macintosh line endings ("/r")
        if (!$lines = file($file->getPathname())) {
            return $this->trans('Error! It has not been possible to read the import file.');
        }
        $expectedFields = ['uname', 'pass', 'email', 'activated', 'sendmail', 'groups'];
        $firstLineArray = explode($delimiter, str_replace('"', '', trim($lines[0])));
        foreach ($firstLineArray as $field) {
            if (!in_array(mb_strtolower(trim($field)), $expectedFields, true)) {
                return $this->trans('Error! The import file does not have the expected field %s in the first row. Please check your import file.', ['%s' => $field]);
            }
        }
        unset($lines[0]);

        $counter = 1;
        $importValues = [];

        // read the lines and create an array with the values. Check if the values passed are correct and set the default values if it is necessary
        foreach ($lines as $line) {
            $line = str_replace('"', '', trim($line));
            $lineArray = explode($delimiter, $line);

            // check if the line has all the needed values
            if (count($lineArray) !== count($firstLineArray)) {
                return $this->trans('Error! The number of parameters in line %s is not correct. Please check your import file.', ['%s' => $counter]);
            }
            $importValues[] = array_combine($firstLineArray, $lineArray);

            // validate user name
            $uname = trim($importValues[$counter - 1]['uname']);
            $errors = $this->validator->validate($uname, new ValidUname());
            if ($errors->count() > 0) {
                return $this->locateErrors($errors, 'username', $counter);
            }

            // validate password
            $pass = (string)trim($importValues[$counter - 1]['pass']);
            $errors = $this->validator->validate($pass, [new NotNull(), new ValidPassword()]);
            if ($errors->count() > 0) {
                return $this->locateErrors($errors, 'password', $counter);
            }

            // validate email
            $email = trim($importValues[$counter - 1]['email']);
            $errors = $this->validator->validate($email, new ValidEmail());
            if ($errors->count() > 0) {
                return $this->locateErrors($errors, 'email', $counter);
            }

            // validate activation value
            $importValues[$counter - 1]['activated'] = isset($importValues[$counter - 1]['activated']) ? (int)$importValues[$counter - 1]['activated'] : UsersConstant::ACTIVATED_ACTIVE;
            $activated = $importValues[$counter - 1]['activated'];
            if ((UsersConstant::ACTIVATED_INACTIVE !== $activated) && (UsersConstant::ACTIVATED_ACTIVE !== $activated)) {
                return $this->locateErrors($this->trans('Error! The CSV is not valid: the "activated" column must contain 0 or 1 only.'), 'activated', $counter);
            }

            // validate sendmail
            $importValues[$counter - 1]['sendmail'] = isset($importValues[$counter - 1]['sendmail']) ? (int)$importValues[$counter - 1]['sendmail'] : 0;
            if ($importValues[$counter - 1]['sendmail'] < 0 || $importValues[$counter - 1]['sendmail'] > 1) {
                return $this->locateErrors($this->trans('Error! The CSV is not valid: the "sendmail" column must contain 0 or 1 only.'), 'sendmail', $counter);
            }

            // check groups and set defaultGroup as default if there are not groups defined
            $importValues[$counter - 1]['groups'] = !empty($importValues[$counter - 1]['groups']) ? $importValues[$counter - 1]['groups'] : $defaultGroup;
            $groupsArray = explode('|', $importValues[$counter - 1]['groups']);
            foreach ($groupsArray as $group) {
                if (!in_array($group, $allGroupsArray, true)) {
                    return $this->locateErrors($this->trans('Sorry! The identity of the group %gid% is not not valid. Perhaps it does not exist. Please check your import file.', ['%gid%' => $group]), 'groups', $counter);
                }
            }

            $counter++;
        }

        if (empty($importValues)) {
            return $this->trans('Error! The import file does not have values.');
        }

        // The values in import file are ready. Proceed creating users
        if (!$this->createUsers($importValues)) {
            return $this->trans('Error! The creation of users has failed.');
        }
        // send email if indicated
        foreach ($importValues as $importValue) {
            if ($importValue['activated'] && $importValue['sendmail']) {
                $templateArgs = [
                    'user' => $importValue
                ];
                $this->mailHelper->sendNotification($importValue['email'], 'importnotify', $templateArgs);
            }
        }

        return '';
    }

    private function createUsers(array &$importValues): bool
    {
        if (empty($importValues)) {
            return false;
        }

        /** @var GroupEntity[] $groups */
        $groups = $this->groupRepository->findAllAndIndexBy('gid');
        $nowUTC = new DateTime(null, new DateTimeZone('UTC'));
        // create users
        foreach ($importValues as $k => $importValue) {
            $unHashedPass = $importValue['pass'];
            $importValue['pass'] = $this->encoderFactory->getEncoder(AuthenticationMappingEntity::class)->encodePassword($importValue['pass'], null);
            if (!$importValue['activated']) {
                $importValues[$k]['activated'] = UsersConstant::ACTIVATED_PENDING_REG;
            } else {
                $importValue['approvedDate'] = $nowUTC;
                $importValue['approvedBy'] = $this->currentUser->get('uid');
            }
            $user = new UserEntity();
            $groupsArray = explode('|', $importValue['groups']);
            unset($importValue['groups'], $importValue['sendmail']);
            $user->merge($importValue);
            $user->setRegistrationDate($nowUTC);
            foreach ($groupsArray as $group) {
                $user->addGroup($groups[$group]);
                $groups[$group]->addUser($user);
            }
            $this->entityManager->persist($user);
            $importValues[$k]['unHashedPass'] = $unHashedPass;
            $importValues[$k]['userReference'] = $user;
        }
        $this->entityManager->flush();

        // post processing
        foreach ($importValues as $k => $importValue) {
            $eventName = $importValue['activated'] ? UserEvents::CREATE_ACCOUNT : RegistrationEvents::CREATE_REGISTRATION;
            $user = $importValue['userReference'];
            $this->eventDispatcher->dispatch(new GenericEvent($user), $eventName);
        }

        return true;
    }

    /**
     * Convert errors to string and add current line.
     */
    private function locateErrors($errors, string $type, int $line): string
    {
        $errorString = '';
        if ($errors instanceof ConstraintViolationListInterface) {
            foreach ($errors as $error) {
                $errorString .= $error->getMessage() . '<br />';
            }
        } elseif (is_array($errors)) {
            $errorString .= implode('<br />', $errors);
        } else {
            $errorString .= $errors;
        }
        $errorString .= $this->translator->trans('%type% error in line %line%', ['%type%' => ucwords($type), '%line%' => $line]);

        return $errorString;
    }
}
