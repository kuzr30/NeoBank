<?php

namespace App\Form;

use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class Step1Type extends AbstractType
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.first_name.label', [], 'application'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->trans('credit_application.forms.personal_info.first_name.placeholder', [], 'application'),
                    'class' => 'form-control'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.last_name.label', [], 'application'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->trans('credit_application.forms.personal_info.last_name.placeholder', [], 'application'),
                    'class' => 'form-control'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.email.label', [], 'application'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->trans('credit_application.forms.personal_info.email.placeholder', [], 'application'),
                    'class' => 'form-control'
                ]
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.phone_number.label', [], 'application'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->trans('credit_application.forms.personal_info.phone_number.placeholder', [], 'application'),
                    'class' => 'form-control'
                ]
            ])
            ->add('birthDate', DateType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.birth_date.label', [], 'application'),
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('nationality', ChoiceType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.nationality.label', [], 'application'),
                'required' => true,
                'placeholder' => $this->translationService->trans('credit_application.forms.personal_info.nationality.placeholder', [], 'application'),
                'attr' => [
                    'class' => 'form-control'
                ],
                'choices' => [
                    $this->translationService->trans('credit_application.forms.personal_info.nationality.choices.french', [], 'application') => 'french',
                    $this->translationService->trans('credit_application.forms.personal_info.nationality.choices.eu', [], 'application') => 'eu',
                    $this->translationService->trans('credit_application.forms.personal_info.nationality.choices.non_eu', [], 'application') => 'non_eu'
                ]
            ])
            ->add('birthCountry', TextType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.birth_country.label', [], 'application'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->trans('credit_application.forms.personal_info.birth_country.placeholder', [], 'application'),
                    'class' => 'form-control'
                ]
            ])
            ->add('birthCity', TextType::class, [
                'label' => $this->translationService->trans('credit_application.forms.personal_info.birth_city.label', [], 'application'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->trans('credit_application.forms.personal_info.birth_city.placeholder', [], 'application'),
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'step1_form',
        ]);
    }
}
