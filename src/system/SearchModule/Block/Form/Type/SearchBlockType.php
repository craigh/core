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

namespace Zikula\SearchModule\Block\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Translation\Extractor\Annotation\Ignore;

/**
 * Class SearchBlockType
 */
class SearchBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('displaySearchBtn', CheckboxType::class, [
                'label' => 'Show \'Search now\' button',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false
            ])
            ->add('active', ChoiceType::class, [
                'label_attr' => ['class' => 'checkbox-custom'],
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => /** @Ignore */$options['activeModules']
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'zikulasearchmodule_searchblock';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'displaySearchBtn' => true,
            'activeModules' => []
        ]);
    }
}
