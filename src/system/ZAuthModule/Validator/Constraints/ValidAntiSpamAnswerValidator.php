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

namespace Zikula\ZAuthModule\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\ZAuthModule\ZAuthConstant;

class ValidAntiSpamAnswerValidator extends ConstraintValidator
{
    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(VariableApiInterface $variableApi, TranslatorInterface $translator, ValidatorInterface $validator)
    {
        $this->variableApi = $variableApi;
        $this->translator = $translator;
        $this->validator = $validator;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidAntiSpamAnswer) {
            throw new UnexpectedTypeException($constraint, ValidAntiSpamAnswer::class);
        }
        $correctAnswer = $this->variableApi->get('ZikulaZAuthModule', ZAuthConstant::MODVAR_REGISTRATION_ANTISPAM_ANSWER, '');
        /** @var ConstraintViolationListInterface $errors */
        $errors = $this->validator->validate($value, [
            new EqualTo([
                'value' => $correctAnswer,
                'message' => $this->translator->trans('You did not provide the correct answer for the security question. Try %answer%!', ['%answer%' => $correctAnswer], 'validators')
            ])
        ]);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                // this method forces the error to appear at the form input location instead of at the top of the form
                $this->context->buildViolation($error->getMessage())->addViolation();
            }
        }
    }
}
