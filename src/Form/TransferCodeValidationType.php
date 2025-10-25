<?php

namespace App\Form;

use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TransferCodeValidationType extends AbstractType
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => $this->translationService->tp('form_common.labels.validation_code', [], 'form_common'),
                'attr' => [
                    'class' => 'banking__form-input banking__form-input--code',
                    'placeholder' => $this->translationService->tp('form_common.placeholders.code_placeholder', [], 'form_common'),
                    'maxlength' => 9,
                    'autocomplete' => 'off',
                    'autocapitalize' => 'characters',
                    'style' => 'font-family: monospace; font-size: 18px; text-align: center; letter-spacing: 2px;'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translationService->tp('form_common.validation.code_required', [], 'form_common')),
                    new Assert\Length(
                        exactly: 9, 
                        exactMessage: $this->translationService->tp('form_common.validation.code_incorrect', [], 'form_common')
                    ),
                    new Assert\Regex(
                        pattern: '/^[A-Z0-9]{9}$/', 
                        message: $this->translationService->tp('form_common.validation.code_format_error', [], 'form_common')
                    )
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translationService->tp('form_common.labels.validate_code', [], 'form_common'),
                'attr' => [
                    'class' => 'banking__action-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
