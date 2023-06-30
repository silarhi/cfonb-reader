<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Form;

use App\Cfonb\CfonbManager;
use App\Validator\Constraints\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\Dropzone\Form\DropzoneType;

class CfonbFileType extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Automatique',
                'choices' => [
                    'CFONB 120' => CfonbManager::TYPE_120,
                    'CFONB 240' => CfonbManager::TYPE_240,
                ],
            ])
            ->add('file', DropzoneType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(),
                    new File(),
                ],
                'attr' => [
                    'placeholder' => 'Glissez ou déposez votre fichier CFONB (120 ou 240)',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new FileType(),
            ],
        ]);
    }
}
