<?php

namespace App\Form;

use App\Entity\ScheduledEmail;
use App\Entity\User;
use App\Enum\AccountIncompleteReason;
use App\Enum\CreditApplicationIncompleteReason;
use App\Enum\EmailTemplateType;
use App\Enum\KycRejectionReason;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduledEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('templateType', EnumType::class, [
                'class' => EmailTemplateType::class,
                'choice_label' => fn($choice) => $choice->getLabel(),
                'label' => 'Type de template',
                'required' => true,
            ])
            ->add('recipient', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return sprintf('%s %s (%s)', 
                        $user->getFirstname() ?? '', 
                        $user->getLastname() ?? '', 
                        $user->getEmail()
                    );
                },
                'label' => 'Destinataire',
                'required' => true,
            ])
            ->add('locale', ChoiceType::class, [
                'choices' => [
                    'Français' => 'fr',
                    'English' => 'en',
                    'Nederlands' => 'nl',
                    'Deutsch' => 'de',
                    'Español' => 'es',
                ],
                'label' => 'Langue',
                'required' => true,
            ])
            ->add('customMessage', TextareaType::class, [
                'label' => 'Message personnalisé',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                ],
            ]);

        // Add event listener to dynamically change reasons field based on templateType
        $formModifier = function (FormInterface $form, ?EmailTemplateType $templateType) {
            if ($templateType === null) {
                return;
            }

            $reasonsChoices = $this->getReasonsChoices($templateType);
            
            if (!empty($reasonsChoices)) {
                $form->add('reasons', ChoiceType::class, [
                    'choices' => $reasonsChoices,
                    'multiple' => true,
                    'expanded' => true,
                    'label' => 'Raisons',
                    'required' => false,
                    'help' => 'Sélectionnez les raisons (affichage automatique selon le type)',
                ]);
            } else {
                // Remove reasons field if template doesn't require it
                if ($form->has('reasons')) {
                    $form->remove('reasons');
                }
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $templateType = null;
                
                // Check if data exists and has templateType initialized
                if ($data instanceof ScheduledEmail) {
                    try {
                        $templateType = $data->getTemplateType();
                    } catch (\Error $e) {
                        // Property not initialized yet, that's ok for new entities
                        $templateType = null;
                    }
                }
                
                $formModifier($event->getForm(), $templateType);
            }
        );

        $builder->get('templateType')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $templateType = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $templateType);
            }
        );
    }

    private function getReasonsChoices(EmailTemplateType $templateType): array
    {
        return match($templateType) {
            EmailTemplateType::KYC_REJECTED => array_combine(
                array_map(fn($reason) => $reason->getLabel(), KycRejectionReason::cases()),
                array_map(fn($reason) => $reason->value, KycRejectionReason::cases())
            ),
            EmailTemplateType::INCOMPLETE_ACCOUNT => array_combine(
                array_map(fn($reason) => $reason->getLabel(), AccountIncompleteReason::cases()),
                array_map(fn($reason) => $reason->value, AccountIncompleteReason::cases())
            ),
            EmailTemplateType::CREDIT_APPLICATION_INCOMPLETE => array_combine(
                array_map(fn($reason) => $reason->getLabel(), CreditApplicationIncompleteReason::cases()),
                array_map(fn($reason) => $reason->value, CreditApplicationIncompleteReason::cases())
            ),
            default => [],
        };
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ScheduledEmail::class,
        ]);
    }
}
