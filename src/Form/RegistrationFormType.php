<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'register.fields.first_name_label',
                'translation_domain' => 'register',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'register.fields.last_name_label',
                'translation_domain' => 'register',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'register.fields.email_label',
                'translation_domain' => 'register',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'register.fields.password_label',
                    'translation_domain' => 'register',
                    'attr' => [
                        'class' => 'form-control'
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'register.errors.password_required',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'register.errors.password_min',
                            'max' => 4096,
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'register.fields.password_confirm_label',
                    'translation_domain' => 'register',
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ],
                'invalid_message' => 'register.errors.password_mismatch',
                'mapped' => false,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'register.fields.agree_terms',
                'translation_domain' => 'register',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'register.errors.terms_required',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'register',
        ]);
    }
}
