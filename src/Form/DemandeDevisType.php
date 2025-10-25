<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\DemandeDevis;
use App\Enum\AssuranceType;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Type pour les demandes de devis d'assurance
 * Implémentation professionnelle avec validation et bonnes pratiques
 */
class DemandeDevisType extends AbstractType
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeAssurance', EnumType::class, [
                'class' => AssuranceType::class,
                'choice_label' => fn(AssuranceType $choice) => $this->translationService->tp($choice->getLabel(), [], 'enums'),
                'label' => $this->translationService->tp('form_common.labels.insurance_type', [], 'forms_common'),
                'required' => true,
                'attr' => [
                    'class' => 'form-input',
                    'data-testid' => 'type-assurance'
                ],
                'placeholder' => $this->translationService->tp('form_common.placeholders.select_insurance_type', [], 'forms_common')
            ])
            ->add('nom', TextType::class, [
                'label' => $this->translationService->tp('form_common.labels.lastname', [], 'forms_common'),
                'required' => true,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => $this->translationService->tp('form_common.placeholders.lastname', [], 'forms_common'),
                    'maxlength' => 100,
                    'data-testid' => 'nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => $this->translationService->tp('form_common.labels.firstname', [], 'forms_common'),
                'required' => true,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => $this->translationService->tp('form_common.placeholders.firstname', [], 'forms_common'),
                    'maxlength' => 100,
                    'data-testid' => 'prenom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translationService->tp('form_common.labels.email_address', [], 'forms_common'),
                'required' => true,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => $this->translationService->tp('form_common.placeholders.email_placeholder', [], 'forms_common'),
                    'maxlength' => 180,
                    'data-testid' => 'email'
                ]
            ])
            ->add('telephone', TelType::class, [
                'label' => $this->translationService->tp('form_common.labels.phone_number', [], 'forms_common'),
                'required' => true,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => $this->translationService->tp('form_common.placeholders.phone_example', [], 'forms_common'),
                    'maxlength' => 20,
                    'data-testid' => 'telephone'
                ]
            ])
            ->add('preferenceContact', ChoiceType::class, [
                'label' => $this->translationService->tp('form_common.labels.contact_preference', [], 'forms_common'),
                'required' => true,
                'choices' => [
                    $this->translationService->tp('form_common.contact_preference.email', [], 'forms_common') => 'email',
                    $this->translationService->tp('form_common.contact_preference.phone', [], 'forms_common') => 'telephone',
                    $this->translationService->tp('form_common.contact_preference.indifferent', [], 'forms_common') => 'indifferent'
                ],
                'attr' => [
                    'class' => 'form-input',
                    'data-testid' => 'preference-contact'
                ],
                'placeholder' => $this->translationService->tp('form_common.placeholders.how_to_contact', [], 'forms_common')
            ])
            ->add('commentaires', TextareaType::class, [
                'label' => $this->translationService->tp('form_common.labels.additional_comments', [], 'forms_common'),
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'rows' => 4,
                    'placeholder' => $this->translationService->tp('form_common.additional_comments_placeholder', [], 'forms_common'),
                    'maxlength' => 2000,
                    'data-testid' => 'commentaires'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translationService->tp('form_common.labels.request_quote', [], 'forms_common'),
                'attr' => [
                    'class' => 'btn btn-primary',
                    'data-testid' => 'submit-devis'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeDevis::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'demande_devis',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'demande_devis';
    }
}
