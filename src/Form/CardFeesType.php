<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CardFeesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activationFee', MoneyType::class, [
                'label' => 'Frais d\'activation (€)',
                'currency' => 'EUR',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 25.00',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1])  // Minimum 1€, pas de maximum
                ],
                'help' => 'Frais unique lors de la création de la carte'
            ])
            ->add('monthlyFee', MoneyType::class, [
                'label' => 'Frais mensuels (€)',
                'currency' => 'EUR',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 8.50',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\PositiveOrZero(),
                    new Assert\Range(['min' => 0, 'max' => 100000])
                ],
                'help' => 'Frais prélevés chaque mois'
            ])
            ->add('dailyLimit', MoneyType::class, [
                'label' => 'Limite journalière (€)',
                'currency' => 'EUR',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 1500.00',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                    new Assert\Range(['min' => 100, 'max' => 10000])
                ],
                'help' => 'Montant maximum autorisé par jour'
            ])
            ->add('monthlyLimit', MoneyType::class, [
                'label' => 'Limite mensuelle (€)',
                'currency' => 'EUR',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 4500.00',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                    new Assert\Range(['min' => 1000, 'max' => 50000])
                ],
                'help' => 'Montant maximum autorisé par mois'
            ])
            ->add('creditLimit', MoneyType::class, [
                'label' => 'Crédit autorisé (€)',
                'currency' => 'EUR',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 500.00 (optionnel)',
                    'step' => '0.01'
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(),
                    new Assert\Range(['min' => 0, 'max' => 5000])
                ],
                'help' => 'Montant de découvert autorisé (0 = aucun crédit)'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Définir les frais et continuer',
                'attr' => [
                    'class' => 'btn btn-success btn-lg'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
