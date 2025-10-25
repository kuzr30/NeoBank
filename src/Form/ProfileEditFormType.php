<?php

namespace App\Form;

use App\Entity\User;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ProfileEditFormType extends AbstractType
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('profilePictureFile', VichFileType::class, [
                'label' => 'Photo de profil',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer la photo',
                'download_uri' => false,
                'attr' => [
                    'class' => 'profile-edit__file-input',
                    'accept' => 'image/*'
                ],
                'help' => $this->translationService->tp('help_texts.accepted_formats_image', [], 'forms_common')
            ])
            ->add('firstName', TextType::class, [
                'label' => $this->translationService->tp('labels.firstname', [], 'forms_common'),
                'attr' => [
                    'class' => 'profile-edit__input',
                    'placeholder' => $this->translationService->tp('placeholders.firstname', [], 'forms_common')
                ],
                'constraints' => [
                    new NotBlank(['message' => $this->translationService->tp('validation.firstname_required', [], 'forms_common')]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => $this->translationService->tp('validation.firstname_min', [], 'forms_common'),
                        'maxMessage' => $this->translationService->tp('validation.firstname_max', [], 'forms_common')
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translationService->tp('labels.lastname', [], 'forms_common'),
                'attr' => [
                    'class' => 'profile-edit__input',
                    'placeholder' => 'Votre nom'
                ],
                'constraints' => [
                    new NotBlank(['message' => $this->translationService->tp('validation.lastname_required', [], 'forms_common')]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => $this->translationService->tp('validation.lastname_min', [], 'forms_common'),
                        'maxMessage' => $this->translationService->tp('validation.lastname_max', [], 'forms_common')
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translationService->tp('labels.email', [], 'forms_common'),
                'attr' => [
                    'class' => 'profile-edit__input',
                    'placeholder' => 'votre@email.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => $this->translationService->tp('validation.email_required', [], 'forms_common')]),
                    new Email(['message' => $this->translationService->tp('validation.email_invalid', [], 'forms_common')])
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => $this->translationService->tp('labels.phone', [], 'forms_common'),
                'required' => false,
                'attr' => [
                    'class' => 'profile-edit__input',
                    'placeholder' => '+33 1 23 45 67 89'
                ]
            ])
            ->add('birthDate', DateType::class, [
                'label' => $this->translationService->tp('labels.birth_date', [], 'forms_common'),
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'profile-edit__input'
                ]
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'class' => 'profile-edit__input',
                    'placeholder' => '123 Rue de la Paix'
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => 'profile-edit__input',
                    'placeholder' => 'Paris'
                ]
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'class' => 'profile-edit__input',
                    'placeholder' => '75001'
                ]
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'required' => false,
                'preferred_choices' => ['FR', 'BE', 'CH', 'LU', 'CA'],
                'attr' => [
                    'class' => 'profile-edit__select'
                ]
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'Langue',
                'choices' => [
                    'Français' => 'fr',
                    'English' => 'en',
                    'Deutsch' => 'de',
                    'Español' => 'es',
                    'Nederlands' => 'nl'
                ],
                'data' => 'fr',
                'attr' => [
                    'class' => 'profile-edit__select'
                ]
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => [
                    'Euro (EUR)' => 'EUR',
                    'Dollar américain (USD)' => 'USD',
                    'Livre sterling (GBP)' => 'GBP',
                    'Franc suisse (CHF)' => 'CHF',
                    'Dollar canadien (CAD)' => 'CAD'
                ],
                'data' => 'EUR',
                'attr' => [
                    'class' => 'profile-edit__select'
                ]
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'Fuseau horaire',
                'choices' => [
                    'Europe/Paris (CET)' => 'Europe/Paris',
                    'Europe/London (GMT)' => 'Europe/London',
                    'America/New_York (EST)' => 'America/New_York',
                    'America/Toronto (EST)' => 'America/Toronto',
                    'Europe/Zurich (CET)' => 'Europe/Zurich'
                ],
                'data' => 'Europe/Paris',
                'attr' => [
                    'class' => 'profile-edit__select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
