<?php

namespace App\Form;

use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class Step1TestType extends AbstractType
{
    private ProfessionalTranslationService $translationService;

    public function __construct(ProfessionalTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => $this->translationService->tp('firstname', [], 'forms_common'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => $this->translationService->tp('firstname_required', [], 'forms_common')]),
                    new Assert\Length([
                        'min' => 2,
                        'minMessage' => $this->translationService->tp('firstname_min_2_chars', [], 'forms_common')
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translationService->tp('lastname', [], 'forms_common'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => $this->translationService->tp('lastname_required', [], 'forms_common')]),
                    new Assert\Length([
                        'min' => 2,
                        'minMessage' => $this->translationService->tp('lastname_min_2_chars', [], 'forms_common')
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
        ]);
    }
}
