<?php

namespace App\Service;

use Mailjet\Client;
use Mailjet\Resources;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

class EmailService
{
    private Client $mailjetClient;
    private LoggerInterface $logger;
    private Environment $twig;
    private ProfessionalTranslationService $translationService;
    private CompanySettingsService $companySettingsService;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        #[Autowire('%env(MAILJET_API_KEY)%')] string $apiKey,
        #[Autowire('%env(MAILJET_SECRET_KEY)%')] string $secretKey,
        LoggerInterface $logger,
        Environment $twig,
        ProfessionalTranslationService $translationService,
        CompanySettingsService $companySettingsService,
        string $defaultFromEmail,
        string $defaultFromName
    ) {
        $this->mailjetClient = new Client($apiKey, $secretKey, true, ['version' => 'v3.1']);
        $this->logger = $logger;
        $this->twig = $twig;
        $this->translationService = $translationService;
        $this->companySettingsService = $companySettingsService;
        $this->fromEmail = $defaultFromEmail;
        $this->fromName = $defaultFromName;
    }

    /**
     * Génère un ID personnalisé basé sur le nom de l'entreprise
     */
    private function generateCustomId(string $suffix = ''): string
    {
        $companyName = $this->companySettingsService->getCompanyName() ?? 'sedefbank';
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $companyName));
        return $cleanName . ($suffix ? '-' . $suffix : '') . '-' . uniqid();
    }

    /**
     * Envoie un email avec le template spécifié
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
        ?string $locale = null
    ): bool {
        $this->logger->info('🚀 EmailService::sendEmail appelé', [
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
            'locale' => $locale
        ]);
        
        try {
            // Gestion temporaire de la locale si spécifiée
            $originalLocale = null;
            if ($locale) {
                $originalLocale = $this->translationService->getLocale();
                $this->translationService->setLocale($locale);
                $this->logger->info('📍 Locale changée temporairement', [
                    'original' => $originalLocale,
                    'new' => $locale
                ]);
            }
            
            // Ajouter automatiquement le translationService et les infos de l'entreprise au contexte pour les templates
            $context['translationService'] = $this->translationService;
            $context['current_locale'] = $locale ?? $this->translationService->getLocale();
            $context['company_settings'] = $this->companySettingsService->getCompanySettings();
            $context['company_name'] = $this->companySettingsService->getCompanyName();
            $context['company_email'] = $this->companySettingsService->getEmail();
            $context['company_phone'] = $this->companySettingsService->getPhone();
            $context['company_website'] = $this->companySettingsService->getWebsite();
            
            // Rendu du template
            $htmlContent = $this->twig->render($template, $context);
            
            // Configuration de l'email
            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => $fromEmail ?? $this->fromEmail,
                            'Name' => $fromName ?? $this->fromName
                        ],
                        'To' => [
                            [
                                'Email' => $to,
                                'Name' => $toName ?? $to
                            ]
                        ],
                        'Subject' => $subject,
                        'HTMLPart' => $htmlContent,
                        'CustomID' => $this->generateCustomId()
                    ]
                ]
            ];

            // Envoi via Mailjet
            $response = $this->mailjetClient->post(Resources::$Email, ['body' => $body]);
            
            if ($response->success()) {
                $this->logger->info($this->translationService->tp('log_messages.success', [], 'email_service'), [
                    'to' => $to,
                    'subject' => $subject,
                    'template' => $template
                ]);
                
                // Restaurer la locale originale
                if ($originalLocale !== null) {
                    $this->translationService->setLocale($originalLocale);
                }
                
                return true;
            } else {
                $this->logger->error($this->translationService->tp('log_messages.error', [], 'email_service'), [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $response->getReasonPhrase(),
                    'status' => $response->getStatus()
                ]);
                
                // Restaurer la locale originale
                if ($originalLocale !== null) {
                    $this->translationService->setLocale($originalLocale);
                }
                
                return false;
            }
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale) && $originalLocale !== null) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('log_messages.exception', [], 'email_service'), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendEmailWithAttachment(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
        ?string $locale = null,
        ?string $attachmentContent = null,
        ?string $attachmentFilename = null,
        ?string $attachmentMimeType = 'application/pdf'
    ): bool {
        $this->logger->info('🚀 EmailService::sendEmailWithAttachment appelé', [
            'to' => $to,
            'subject' => $subject,
            'template' => $template,
            'hasAttachment' => !is_null($attachmentContent),
            'locale' => $locale
        ]);
        
        try {
            // Gestion temporaire de la locale si spécifiée
            $originalLocale = null;
            if ($locale) {
                $originalLocale = $this->translationService->getLocale();
                $this->translationService->setLocale($locale);
                $this->logger->info('📍 Locale changée temporairement (avec pièce jointe)', [
                    'original' => $originalLocale,
                    'new' => $locale
                ]);
            }

            // Ajouter automatiquement translationService au contexte si pas déjà présent
            if (!isset($context['translationService'])) {
                $context['translationService'] = $this->translationService;
            }
            $context['current_locale'] = $locale ?? $this->translationService->getLocale();
            
            $htmlContent = $this->twig->render($template, $context);
            
            $messageData = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => $fromEmail ?? $this->fromEmail,
                            'Name' => $fromName ?? $this->fromName
                        ],
                        'To' => [
                            [
                                'Email' => $to,
                                'Name' => $toName ?? $to
                            ]
                        ],
                        'Subject' => $subject,
                        'HTMLPart' => $htmlContent,
                        'CustomID' => $this->generateCustomId('contract')
                    ]
                ]
            ];

            // Ajouter la pièce jointe si fournie
            if ($attachmentContent && $attachmentFilename) {
                $messageData['Messages'][0]['Attachments'] = [
                    [
                        'ContentType' => $attachmentMimeType,
                        'Filename' => $attachmentFilename,
                        'Base64Content' => base64_encode($attachmentContent)
                    ]
                ];
            }
            
            $response = $this->mailjetClient->post(Resources::$Email, ['body' => $messageData]);
            
            if ($response->success()) {
                $this->logger->info($this->translationService->tp('log_messages.success_with_attachment', [], 'email_service'), [
                    'to' => $to,
                    'subject' => $subject,
                    'template' => $template,
                    'attachment' => $attachmentFilename
                ]);
                
                // Restaurer la locale originale
                if ($originalLocale !== null) {
                    $this->translationService->setLocale($originalLocale);
                }
                
                return true;
            } else {
                $this->logger->error($this->translationService->tp('log_messages.error', [], 'email_service'), [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $response->getReasonPhrase(),
                    'status' => $response->getStatus()
                ]);
                
                // Restaurer la locale originale
                if ($originalLocale !== null) {
                    $this->translationService->setLocale($originalLocale);
                }
                
                return false;
            }
        } catch (\Exception $e) {
            // Restaurer la locale originale en cas d'erreur
            if (isset($originalLocale) && $originalLocale !== null) {
                $this->translationService->setLocale($originalLocale);
            }
            
            $this->logger->error($this->translationService->tp('log_messages.exception', [], 'email_service'), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email en mode asynchrone (avec Messenger)
     */
    public function sendEmailAsync(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $toName = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
        ?string $locale = null
    ): bool {
        // Cette méthode sera utilisée par les MessageHandlers
        return $this->sendEmail($to, $subject, $template, $context, $toName, $fromEmail, $fromName, $locale);
    }

    /**
     * Valide une adresse email
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Obtient les statistiques d'envoi (optionnel)
     */
    public function getEmailStats(): array
    {
        try {
            $response = $this->mailjetClient->get(Resources::$Statcounters);
            return $response->success() ? $response->getData() : [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des statistiques', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
}
