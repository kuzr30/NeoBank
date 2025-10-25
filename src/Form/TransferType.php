<?php

namespace App\Form;

use App\Entity\BankAccount;
use App\Validator\SufficientBalance;
use App\Service\ProfessionalTranslationService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TransferType extends AbstractType
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destinationAccount', EntityType::class, [
                'class' => BankAccount::class,
                'choices' => $options['bank_accounts'],
                'choice_label' => function (BankAccount $bankAccount) {
                    return sprintf('%s - %s (%s)', 
                        $bankAccount->getAccountName(),
                        $bankAccount->getMaskedIban(),
                        $bankAccount->getBankName()
                    );
                },
                'label' => $this->translationService->tp('form_common.labels.beneficiary_account', [], 'form_common'),
                'placeholder' => $this->translationService->tp('form_common.placeholders.select_account', [], 'form_common'),
                'attr' => [
                    'class' => 'banking__form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translationService->tp('form_common.validation.select_beneficiary_account', [], 'form_common'))
                ]
            ])
            ->add('amount', MoneyType::class, [
                'label' => $this->translationService->tp('form_common.labels.amount', [], 'form_common'),
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'banking__form-input',
                    'placeholder' => $this->translationService->tp('form_common.placeholders.amount_placeholder', [], 'form_common'),
                    'step' => '0.01',
                    'min' => '0.01'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translationService->tp('form_common.validation.amount_required', [], 'form_common')),
                    new Assert\GreaterThan(value: 0, message: $this->translationService->tp('form_common.validation.amount_greater_than_zero', [], 'form_common')),
                    // La limite sera vérifiée dynamiquement selon le solde disponible
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translationService->tp('form_common.labels.transfer_reason', [], 'form_common'),
                'required' => false,
                'attr' => [
                    'class' => 'banking__form-textarea',
                    'placeholder' => $this->translationService->tp('form_common.placeholders.describe_transfer_reason', [], 'form_common'),
                    'rows' => 3,
                    'maxlength' => 500
                ],
                'constraints' => [
                    new Assert\Length(max: 500, maxMessage: $this->translationService->tp('form_common.validation.description_max_500_chars', [], 'form_common'))
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translationService->tp('form_common.labels.submit_transfer', [], 'form_common'),
                'attr' => [
                    'class' => 'banking__action-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'bank_accounts' => [],
            'constraints' => [
                new SufficientBalance()
            ]
        ]);

        $resolver->setAllowedTypes('bank_accounts', 'array');
    }
}
