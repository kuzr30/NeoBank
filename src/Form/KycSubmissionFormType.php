<?php

namespace App\Form;

use App\Entity\KycSubmission;
use App\Entity\KycDocument;
use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class KycSubmissionFormType extends AbstractType
{
    public function __construct(
        private ProfessionalTranslationService $translationService
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identityDocument', FileType::class, [
                'label' => $this->translationService->tp('labels.identity_document', [], 'forms_common'),
                'help' => $this->translationService->tp('help_texts.accepted_formats_kyc', [], 'forms_common'),
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png', 
                            'application/pdf'
                        ],
                        'mimeTypesMessage' => $this->translationService->tp('validation.file_mime_type_error', [], 'forms_common'),
                        'maxSizeMessage' => $this->translationService->tp('validation.file_max_size_error', [], 'forms_common')
                    ])
                ],
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png'
                ]
            ])
            ->add('incomeDocument', FileType::class, [
                'label' => $this->translationService->tp('labels.income_proof', [], 'forms_common'),
                'help' => $this->translationService->tp('help_texts.accepted_formats_kyc', [], 'forms_common'),
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'application/pdf'
                        ],
                        'mimeTypesMessage' => $this->translationService->tp('validation.file_mime_type_error', [], 'forms_common'),
                        'maxSizeMessage' => $this->translationService->tp('validation.file_max_size_error', [], 'forms_common')
                    ])
                ],
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png'
                ]
            ])
            ->add('addressDocument', FileType::class, [
                'label' => $this->translationService->tp('labels.address_proof', [], 'forms_common'),
                'help' => $this->translationService->tp('help_texts.accepted_formats_kyc', [], 'forms_common'),
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'application/pdf'
                        ],
                        'mimeTypesMessage' => $this->translationService->tp('validation.file_mime_type_error', [], 'forms_common'),
                        'maxSizeMessage' => $this->translationService->tp('validation.file_max_size_error', [], 'forms_common')
                    ])
                ],
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png'
                ]
            ])
            ->add('selfieDocument', FileType::class, [
                'label' => $this->translationService->tp('labels.selfie_with_id', [], 'forms_common'),
                'help' => $this->translationService->tp('help_texts.accepted_formats_selfie', [], 'forms_common'),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                        'mimeTypesMessage' => $this->translationService->tp('validation.image_mime_type_error', [], 'forms_common'),
                        'maxSizeMessage' => $this->translationService->tp('validation.file_max_size_error', [], 'forms_common')
                    ])
                ],
                'attr' => [
                    'accept' => '.jpg,.jpeg,.png'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KycSubmission::class,
        ]);
    }
}
