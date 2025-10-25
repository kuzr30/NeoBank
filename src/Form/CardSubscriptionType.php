<?php

namespace App\Form;

use App\Entity\Account;
use App\Entity\CardSubscription;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de souscription de carte bancaire
 * Interface utilisateur pour sélectionner le type et la marque de carte
 */
class CardSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('account', EntityType::class, [
                'class' => Account::class,
                'choices' => $options['user_accounts'],
                'choice_label' => function (Account $account) {
                    return sprintf('%s - %s (%s)', 
                        $account->getAccountNumber(), 
                        $account->getType(),
                        $account->getBalance() . ' ' . $account->getCurrency()
                    );
                },
                'label' => 'Compte à associer',
                'help' => 'Sélectionnez le compte bancaire auquel associer votre nouvelle carte',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez sélectionner un compte')
                ]
            ])
            
            ->add('cardBrand', ChoiceType::class, [
                'choices' => [
                    'Visa' => 'visa',
                    'Mastercard' => 'mastercard',
                ],
                'label' => 'Marque de carte',
                'help' => 'Choisissez la marque de votre carte bancaire',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez sélectionner une marque de carte')
                ]
            ])
            
            ->add('cardType', ChoiceType::class, [
                'choices' => [
                    'Classic - Carte standard avec services essentiels' => 'classic',
                    'Gold - Carte premium avec assurances étendues' => 'gold',
                    'Platinum - Carte prestige avec services exclusifs' => 'platinum',
                ],
                'label' => 'Type de carte',
                'help' => 'Sélectionnez le niveau de services souhaité',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez sélectionner un type de carte')
                ]
            ])
            
            ->add('reason', TextareaType::class, [
                'label' => 'Motif de la demande (optionnel)',
                'help' => 'Précisez le motif de votre demande si nécessaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Ex: Première carte, remplacement, carte supplémentaire...'
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 500,
                        maxMessage: 'Le motif ne peut pas dépasser {{ limit }} caractères'
                    )
                ]
            ])
            
            ->add('submit', SubmitType::class, [
                'label' => 'Demander la carte',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg w-100 mt-3'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CardSubscription::class,
            'user_accounts' => [],
        ]);
        
        $resolver->setAllowedTypes('user_accounts', 'array');
    }
}
