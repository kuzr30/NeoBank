<?php

namespace App\Form\CreditApplication;

use App\DTO\CreditApplicationDTO;
use App\Enum\CreditTypeEnum;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreditApplicationStep3Type extends AbstractType
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Données du crédit
            ->add('loanAmount', IntegerType::class, [
                'label' => $this->translationService->tp('step3.credit_configuration.loan_amount.label', [], 'credit_step_forms'),
                'required' => true,
                'attr' => [
                    'placeholder' => $this->translationService->tp('step3.credit_configuration.loan_amount.placeholder', [], 'credit_step_forms'),
                    'class' => 'credit-form__input credit-form__input--money credit-form__input--large',
                    'data-credit-application-target' => 'loanAmount',
                    'min' => 3000,
                    'max' => 30000000,
                    'step' => '1'
                ]
            ])
            ->add('creditType', EnumType::class, [
                'label' => $this->translationService->tp('step3.credit_configuration.credit_type.label', [], 'credit_step_forms'),
                'required' => true,
                'class' => CreditTypeEnum::class,
                'choice_value' => 'value', // Utilise les valeurs backing de l'enum
                'choice_label' => function(CreditTypeEnum $choice): string {
                    return $this->translationService->tp($choice->getLabel(), [], 'credit_step_forms');
                },
                'choice_attr' => function(CreditTypeEnum $choice): array {
                    return [
                        'data-rate' => $choice->getRate(),
                        'data-description' => $this->translationService->tp($choice->getDescription(), [], 'credit_step_forms')
                    ];
                },
                'placeholder' => $this->translationService->tp('step3.credit_configuration.credit_type.placeholder', [], 'credit_step_forms'),
                'attr' => [
                    'class' => 'credit-form__select credit-form__select--large',
                    'data-credit-application-target' => 'creditType'
                ]
            ])
            ->add('duration', IntegerType::class, [
                'label' => $this->translationService->tp('duration', [], 'forms_common'),
                'required' => true,
                'attr' => [
                    'min' => 2,
                    'placeholder' => $this->translationService->tp('minimum_2_months', [], 'forms_common'),
                    'class' => 'credit-form__input credit-form__input--duration',
                    'data-credit-application-target' => 'duration',
                    'step' => 1
                ]
            ])
            
            // Résultats calculés (en lecture seule)
            ->add('monthlyPayment', IntegerType::class, [
                'label' => $this->translationService->tp('monthly_payment', [], 'forms_common'),
                'required' => false,
                'attr' => [
                    'readonly' => true,
                    'class' => 'credit-form__input credit-form__input--readonly credit-form__result',
                    'data-credit-application-target' => 'monthlyPayment',
                    'placeholder' => $this->translationService->tp('estimated_amount', [], 'forms_common')
                ]
            ])
            ->add('totalCost', IntegerType::class, [
                'label' => $this->translationService->tp('total_cost', [], 'forms_common'),
                'required' => false,
                'attr' => [
                    'readonly' => true,
                    'class' => 'credit-form__input credit-form__input--readonly credit-form__result',
                    'data-credit-application-target' => 'totalCost',
                    'placeholder' => $this->translationService->tp('estimated_amount', [], 'forms_common')
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreditApplicationDTO::class,
            'validation_groups' => ['step1'],
            'attr' => [
                'class' => 'credit-form credit-form--step3',
                'novalidate' => true
            ]
        ]);
    }
}
