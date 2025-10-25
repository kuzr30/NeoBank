<?php

namespace App\Form\CreditApplication;

use App\DTO\CreditApplicationDTO;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreditApplicationStep2Type extends AbstractType
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Situation professionnelle
            ->add('monthlyIncome', IntegerType::class, [
                'label' => $this->translationService->tp('step2.fields.monthly_income.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step2.fields.monthly_income.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input credit-form__input--money',
                    'min' => 0
                ]
            ])
            ->add('employmentType', ChoiceType::class, [
                'label' => $this->translationService->tp('step2.fields.employment_type.label', [], 'credit_step_forms'),
                'required' => true,
                'choices' => [
                    $this->translationService->tp('step2.fields.employment_type.choices.employee', [], 'credit_step_forms') => 'employee',
                    $this->translationService->tp('step2.fields.employment_type.choices.civil_servant', [], 'credit_step_forms') => 'civil_servant',
                    $this->translationService->tp('step2.fields.employment_type.choices.freelancer', [], 'credit_step_forms') => 'freelancer',
                    $this->translationService->tp('step2.fields.employment_type.choices.entrepreneur', [], 'credit_step_forms') => 'entrepreneur',
                    $this->translationService->tp('step2.fields.employment_type.choices.retired', [], 'credit_step_forms') => 'retired',
                    $this->translationService->tp('step2.fields.employment_type.choices.unemployed', [], 'credit_step_forms') => 'unemployed'
                ],
                'placeholder' => $this->translationService->tp('step2.fields.employment_type.placeholder', [], 'credit_step_forms'),
                'attr' => [
                    'class' => 'credit-form__select',
                    'data-target' => 'employment-details'
                ]
            ])
            ->add('employer', TextType::class, [
                'label' => $this->translationService->tp('step2.fields.employer.label', [], 'credit_step_forms'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step2.fields.employer.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-conditional' => 'employment-details'
                ]
            ])
            ->add('jobTitle', TextType::class, [
                'label' => $this->translationService->tp('step2.fields.job_title.label', [], 'credit_step_forms'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step2.fields.job_title.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input',
                    'data-conditional' => 'employment-details'
                ]
            ])
            ->add('employmentStartDate', DateType::class, [
                'label' => $this->translationService->tp('step2.fields.employment_start_date.label', [], 'credit_step_forms'),
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'credit-form__input credit-form__input--date',
                    'data-conditional' => 'employment-details'
                ],
                'help' => $this->translationService->tp('step2.fields.employment_start_date.help', [], 'credit_step_forms')
            ])
            
            // Situation financière
            ->add('monthlyExpenses', IntegerType::class, [
                'label' => $this->translationService->tp('step2.fields.monthly_expenses.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step2.fields.monthly_expenses.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input credit-form__input--money',
                    'min' => 0
                ],
                'help' => $this->translationService->tp('step2.fields.monthly_expenses.help', [], 'credit_step_forms')
            ])
            ->add('existingLoans', IntegerType::class, [
                'label' => $this->translationService->tp('step2.fields.existing_loans.label', [], 'credit_step_forms'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step2.fields.existing_loans.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input credit-form__input--money',
                    'min' => 0
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreditApplicationDTO::class,
            'validation_groups' => ['step3'],
            'attr' => [
                'class' => 'credit-form credit-form--step2',
                'novalidate' => true
            ]
        ]);
    }
}
