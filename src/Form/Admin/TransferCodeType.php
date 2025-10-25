<?php

namespace App\Form\Admin;

use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TransferCodeType extends AbstractType
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeName', TextType::class, [
                'label' => $this->translationService->tp('labels.code_name', [], 'forms_common'),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translationService->tp('placeholders.security_code_example', [], 'forms_common')
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translationService->tp('validation.code_name_required', [], 'forms_common')),
                    new Assert\Length(
                        min: 3,
                        max: 255,
                        minMessage: $this->translationService->tp('validation.lastname_min_3_chars', [], 'forms_common'),
                        maxMessage: $this->translationService->tp('validation.lastname_max_255_chars', [], 'forms_common')
                    )
                ]
            ])
            ->add('codeValue', TextType::class, [
                'label' => $this->translationService->tp('labels.code_9_chars', [], 'forms_common'),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ABC123XYZ',
                    'maxlength' => 9,
                    'style' => 'font-family: monospace; font-size: 16px; letter-spacing: 1px;',
                    'autocapitalize' => 'characters'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translationService->tp('validation.code_required', [], 'forms_common')),
                    new Assert\Length(
                        exactly: 9,
                        exactMessage: $this->translationService->tp('validation.code_exact_9_chars', [], 'forms_common')
                    ),
                    new Assert\Regex(
                        pattern: '/^[A-Z0-9]{9}$/',
                        message: $this->translationService->tp('validation.code_format_error', [], 'forms_common')
                    )
                ]
            ])
            ->add('generateCode', ButtonType::class, [
                'label' => $this->translationService->tp('labels.auto_generate', [], 'forms_common'),
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'onclick' => 'generateRandomCode()'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translationService->tp('labels.add_code', [], 'forms_common'),
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
