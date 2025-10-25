<?php

namespace App\Form;

use App\Service\ProfessionalTranslationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LoginFormType extends AbstractType
{
    private ProfessionalTranslationService $translationService;

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        ProfessionalTranslationService $translationService
    ) {
        $this->translationService = $translationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', EmailType::class, [
                'label' => $this->translationService->tp('email', [], 'forms_common'),
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => $this->translationService->tp('email_placeholder', [], 'forms_common'),
                    'autocomplete' => 'email',
                    'autofocus' => true
                ],
                'required' => true
            ])
            ->add('_password', PasswordType::class, [
                'label' => $this->translationService->tp('password', [], 'forms_common'),
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => $this->translationService->tp('password_placeholder', [], 'forms_common'),
                    'autocomplete' => 'current-password'
                ],
                'required' => true
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label' => $this->translationService->tp('remember_me', [], 'forms_common'),
                'required' => false,
                'attr' => [
                    'class' => 'checkbox-input'
                ]
            ])
            ->add('_csrf_token', HiddenType::class, [
                'data' => $this->csrfTokenManager->getToken('authenticate')->getValue(),
                'mapped' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translationService->tp('login', [], 'forms_common'),
                'attr' => [
                    'class' => 'auth-btn auth-btn--primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            'method' => 'POST'
        ]);
    }
}
