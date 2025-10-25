<?php

namespace App\Form\CreditApplication;

use App\DTO\CreditApplicationDTO;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreditApplicationStep1Type extends AbstractType
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Informations personnelles
            ->add('firstName', TextType::class, [
                'label' => $this->translationService->tp('step1.fields.firstname.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step1.fields.firstname.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-validation' => 'required minlength:2'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translationService->tp('step1.fields.lastname.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step1.fields.lastname.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-validation' => 'required minlength:2'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translationService->tp('step1.fields.email.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step1.fields.email.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-validation' => 'required email'
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => $this->translationService->tp('step1.fields.phone.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step1.fields.phone.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-validation' => 'required phone'
                ]
            ])
            ->add('birthDate', DateType::class, [
                'label' => $this->translationService->tp('step1.fields.birth_date.label', [], 'credit_step_forms'),
                'required' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'credit-form__input credit-form__input--date',
                    'data-validation' => 'required age:18'
                ]
            ])
            ->add('nationality', CountryType::class, [
                'label' => $this->translationService->tp('step1.fields.nationality.label', [], 'credit_step_forms'),
                'required' => true,
                'preferred_choices' => ['FR', 'BE', 'NL', 'ES'],
                'placeholder' => $this->translationService->tp('step1.fields.nationality.placeholder', [], 'credit_step_forms'),
                'attr' => [
                    'class' => 'credit-form__select',
                    'data-validation' => 'required'
                ]
            ])
            ->add('maritalStatus', ChoiceType::class, [
                'label' => $this->translationService->tp('step1.fields.marital_status.label', [], 'credit_step_forms'),
                'required' => true,
                'choices' => [
                    $this->translationService->tp('step1.fields.marital_status.choices.single', [], 'credit_step_forms') => 'single',
                    $this->translationService->tp('step1.fields.marital_status.choices.married', [], 'credit_step_forms') => 'married',
                    $this->translationService->tp('step1.fields.marital_status.choices.divorced', [], 'credit_step_forms') => 'divorced',
                    $this->translationService->tp('step1.fields.marital_status.choices.widowed', [], 'credit_step_forms') => 'widowed',
                    $this->translationService->tp('step1.fields.marital_status.choices.cohabiting', [], 'credit_step_forms') => 'cohabiting'
                ],
                'placeholder' => $this->translationService->tp('step1.fields.marital_status.placeholder', [], 'credit_step_forms'),
                'attr' => [
                    'class' => 'credit-form__select',
                    'data-validation' => 'required'
                ]
            ])
            ->add('dependents', ChoiceType::class, [
                'label' => $this->translationService->tp('step1.fields.dependents.label', [], 'credit_step_forms'),
                'required' => true,
                'choices' => [
                    $this->translationService->tp('step1.fields.dependents.choices.none', [], 'credit_step_forms') => 0,
                    $this->translationService->tp('step1.fields.dependents.choices.one', [], 'credit_step_forms') => 1,
                    $this->translationService->tp('step1.fields.dependents.choices.two', [], 'credit_step_forms') => 2,
                    $this->translationService->tp('step1.fields.dependents.choices.three', [], 'credit_step_forms') => 3,
                    $this->translationService->tp('step1.fields.dependents.choices.four', [], 'credit_step_forms') => 4,
                    $this->translationService->tp('step1.fields.dependents.choices.five_plus', [], 'credit_step_forms') => 5
                ],
                'placeholder' => $this->translationService->tp('step1.fields.dependents.placeholder', [], 'credit_step_forms'),
                'attr' => [
                    'class' => 'credit-form__select',
                    'data-validation' => 'required'
                ]
            ])
            
            // Adresse
            ->add('address', TextType::class, [
                'label' => $this->translationService->tp('step1.address_section.address.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step1.address_section.address.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-validation' => 'required minlength:5'
                ]
            ])
            ->add('city', TextType::class, [
                'label' => $this->translationService->tp('step1.address_section.city.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step1.address_section.city.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-validation' => 'required minlength:2'
                ]
            ])
            ->add('postalCode', TextType::class, [
                'label' => $this->translationService->tp('step1.address_section.postal_code.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step1.address_section.postal_code.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input credit-form__input--postal',
                    'data-validation' => 'required postal'
                ]
            ])
            ->add('country', CountryType::class, [
                'label' => $this->translationService->tp('step1.address_section.country.label', [], 'credit_step_forms'),
                'required' => true,
                'preferred_choices' => ['FR', 'BE', 'NL', 'ES'],
                'placeholder' => $this->translationService->tp('step1.address_section.country.placeholder', [], 'credit_step_forms'),
                'attr' => [
                    'class' => 'credit-form__select',
                    'data-validation' => 'required'
                ]
            ])
            ->add('housingType', ChoiceType::class, [
                'label' => $this->translationService->tp('step1.address_section.housing_type.label', [], 'credit_step_forms'),
                'required' => true,
                'choices' => [
                    $this->translationService->tp('step1.address_section.housing_type.choices.owner', [], 'credit_step_forms') => 'owner',
                    $this->translationService->tp('step1.address_section.housing_type.choices.tenant', [], 'credit_step_forms') => 'tenant',
                    $this->translationService->tp('step1.address_section.housing_type.choices.family', [], 'credit_step_forms') => 'family',
                    $this->translationService->tp('step1.address_section.housing_type.choices.other', [], 'credit_step_forms') => 'other'
                ],
                'placeholder' => $this->translationService->tp('step1.address_section.housing_type.placeholder', [], 'credit_step_forms'),
                'attr' => [
                    'class' => 'credit-form__select',
                    'data-validation' => 'required'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreditApplicationDTO::class,
            'validation_groups' => ['step2'],
            'attr' => [
                'class' => 'credit-form credit-form--step1',
                'novalidate' => true
            ]
        ]);
    }
}
