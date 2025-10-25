<?php

namespace App\Form;

use App\Entity\CardOpposition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de déclaration d'opposition de carte bancaire
 * Interface utilisateur pour signaler perte, vol ou compromission
 */
class CardOppositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'choices' => [
                    'Carte perdue' => 'lost',
                    'Carte volée' => 'stolen',
                    'Carte compromise (données utilisées frauduleusement)' => 'compromised',
                    'Activité frauduleuse détectée' => 'fraudulent_activity',
                    'Carte endommagée' => 'damaged',
                    'Autre motif' => 'other',
                ],
                'label' => 'Motif de l\'opposition',
                'help' => 'Sélectionnez la raison de votre opposition',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez indiquer le motif de l\'opposition')
                ]
            ])
            
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'help' => 'Décrivez les circonstances (lieu, heure, contexte...)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez les circonstances de la perte/vol/compromission de votre carte...'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez décrire les circonstances'),
                    new Assert\Length(
                        min: 10,
                        max: 1000,
                        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
                    )
                ]
            ])
            
            ->add('newCardRequested', ChoiceType::class, [
                'choices' => [
                    'Oui, je souhaite une carte de remplacement' => true,
                    'Non, je ne souhaite pas de remplacement pour le moment' => false,
                ],
                'label' => 'Carte de remplacement',
                'help' => 'Souhaitez-vous qu\'une nouvelle carte soit émise automatiquement ?',
                'expanded' => true,
                'multiple' => false,
                'data' => true, // Par défaut, on propose une carte de remplacement
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [
                    new Assert\NotNull(message: 'Veuillez indiquer si vous souhaitez une carte de remplacement')
                ]
            ])
            
            ->add('submit', SubmitType::class, [
                'label' => '🚨 DÉCLARER L\'OPPOSITION (Blocage immédiat)',
                'attr' => [
                    'class' => 'btn btn-danger btn-lg w-100 mt-4',
                    'onclick' => 'return confirm("Confirmez-vous vouloir bloquer immédiatement cette carte ? Cette action est irréversible.")'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CardOpposition::class,
        ]);
    }
}
