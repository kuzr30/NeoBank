<?php

namespace App\Form;

use App\Entity\BankAccount;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BankAccountType extends AbstractType
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accountName', TextType::class, [
                'label' => $this->translationService->tp('labels.account_title', [], 'forms_common'),
                'help' => $this->translationService->tp('help_texts.account_examples', [], 'forms_common'),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Mon compte courant'
                ]
            ])
            ->add('iban', TextType::class, [
                'label' => $this->translationService->tp('labels.iban', [], 'forms_common'),
                'help' => $this->translationService->tp('help_texts.iban_example', [], 'forms_common'),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'FR14 2004 1010 0505 0001 3M02 606',
                    'maxlength' => 34,
                    'style' => 'text-transform: uppercase;',
                    'data-iban-validation' => 'true'
                ]
            ])
            ->add('bankName', TextType::class, [
                'label' => $this->translationService->tp('labels.bank_name', [], 'forms_common'),
                'help' => $this->translationService->tp('help_texts.bank_examples', [], 'forms_common'),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'BNP Paribas'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
        ]);
    }
}
