<?php

namespace App\Service;

use App\Entity\ContractSubscription;
use App\Entity\CardSubscription;
use App\Entity\CompanySettings;
use App\Repository\ContractSubscriptionRepository;
use App\Repository\CompanySettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Service de gestion des contrats de souscription de carte
 * 
 * Responsabilités :
 * - Création de contrats avec frais et conditions
 * - Génération de PDF de contrat
 * - Envoi d'emails avec contrat
 * - Gestion des signatures électroniques
 * - Validation des contrats signés
 */
class ContractSubscriptionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContractSubscriptionRepository $contractRepository,
        private CompanySettingsRepository $companySettingsRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private string $appUrl,
        private string $defaultFromEmail,
        private string $defaultFromName
    ) {}

    /**
     * Crée un contrat pour une souscription de carte
     */
    public function createContract(CardSubscription $cardSubscription): ContractSubscription
    {
        // Vérifier qu'il n'y a pas déjà un contrat pour cette souscription
        $existingContract = $this->contractRepository->findByCardSubscription($cardSubscription);
        if ($existingContract) {
            throw new \InvalidArgumentException(
                'Un contrat existe déjà pour cette souscription'
            );
        }

        $contract = new ContractSubscription();
        $contract->setCardSubscription($cardSubscription);
        
        // Utiliser les frais définis par l'admin (frais annuels = frais mensuels x 12)
        $monthlyFee = $cardSubscription->getMonthlyFee() ?? '0.00';
        $annualFees = bcmul($monthlyFee, '12', 2);
        $contract->setCardFees($annualFees);
        
        // Utiliser les limites définies par l'admin
        $contract->setDailyLimit($cardSubscription->getDailyLimit() ?? '500.00');
        $contract->setMonthlyLimit($cardSubscription->getMonthlyLimit() ?? '2000.00');
        
        // Ajouter la limite de crédit si définie
        $creditLimit = $cardSubscription->getCreditLimit();
        if ($creditLimit && (float)$creditLimit > 0) {
            $contract->setCreditLimit($creditLimit);
        }
        
        // Définir les conditions générales et particulières
        $contract->setGeneralConditions($this->getGeneralConditions());
        $contract->setSpecificConditions($this->getSpecificConditions($cardSubscription->getCardType()));

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $this->logger->info('Contrat de souscription créé', [
            'contract_id' => $contract->getId(),
            'card_subscription_id' => $cardSubscription->getId(),
            'reference' => $contract->getReference(),
            'card_type' => $cardSubscription->getCardType(),
            'annual_fees' => $annualFees,
            'monthly_fee' => $monthlyFee
        ]);

        return $contract;
    }

    /**
     * Envoie le contrat par email au client
     */
    public function sendContractByEmail(ContractSubscription $contract): void
    {
        if (!$contract->isPending()) {
            throw new \InvalidArgumentException(
                'Seuls les contrats en attente peuvent être envoyés'
            );
        }

        $cardSubscription = $contract->getCardSubscription();
        $user = $cardSubscription->getUser();

        // Générer le PDF du contrat
        $pdfPath = $this->generateContractPdf($contract);
        $contract->setContractPdfPath($pdfPath);

        // Créer l'URL de signature
        $signatureUrl = $this->urlGenerator->generate(
            'contract_signature', 
            ['reference' => $contract->getReference()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Définir la locale utilisateur pour les traductions
        $userLocale = $user->getLanguage() ?? 'fr';
        
        // Générer le sujet avec traduction
        $subject = $this->translator->trans('email_card_contract.title', [], 'email_card_contract', $userLocale);
        
        // Préparer l'email avec le template modernisé
        $email = (new Email())
            ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
            ->to($user->getEmail())
            ->subject($subject)
            ->html($this->twig->render('email/card_subscription_contract.html.twig', [
                'user' => $user,
                'contract' => $contract,
                'cardSubscription' => $cardSubscription,
                'signatureUrl' => $signatureUrl,
                'expiresAt' => $contract->getExpiresAt(),
                '_locale' => $userLocale
            ]));

        // Attacher le PDF du contrat
        $fullPdfPath = __DIR__ . '/../../public/' . $pdfPath;
        if (file_exists($fullPdfPath)) {
            $email->attachFromPath($fullPdfPath, 'Contrat_Carte_' . $contract->getReference() . '.pdf');
        } else {
            $this->logger->warning('PDF du contrat non trouvé pour l\'email', [
                'pdf_path' => $fullPdfPath,
                'contract_reference' => $contract->getReference()
            ]);
        }

        $this->mailer->send($email);

        // Mettre à jour le statut du contrat
        $contract->setStatus('sent');
        $this->entityManager->flush();

        $this->logger->info('Contrat envoyé par email', [
            'contract_id' => $contract->getId(),
            'user_email' => $user->getEmail(),
            'reference' => $contract->getReference()
        ]);
    }

    /**
     * Traite la signature électronique du contrat
     */
    public function signContract(
        ContractSubscription $contract,
        string $signatureData,
        string $signerIp,
        string $signerUserAgent
    ): void {
        if (!$contract->canBeSigned()) {
            throw new \InvalidArgumentException(
                'Ce contrat ne peut pas être signé (expiré ou statut incorrect)'
            );
        }

        // Enregistrer les données de signature
        $contract->setSignatureData($signatureData);
        $contract->setSignerIp($signerIp);
        $contract->setSignerUserAgent($signerUserAgent);
        $contract->setStatus('signed');

        // Générer le PDF du contrat signé
        $signedPdfPath = $this->generateSignedContractPdf($contract);
        $contract->setSignedContractPdfPath($signedPdfPath);

        $this->entityManager->flush();

        $this->logger->info('Contrat signé électroniquement', [
            'contract_id' => $contract->getId(),
            'reference' => $contract->getReference(),
            'signer_ip' => $signerIp
        ]);

        // Notification à l'équipe admin
        $this->notifyAdminContractSigned($contract);
    }

    /**
     * Calcule les frais de carte selon le type
     */
    private function calculateCardFees(string $cardType): string
    {
        return match($cardType) {
            'classic' => '0.00', // Gratuit la première année
            'gold' => '60.00', // 5€/mois x 12 mois
            'platinum' => '180.00', // 15€/mois x 12 mois
            default => '0.00'
        };
    }

    /**
     * Retourne les limites selon le type de carte
     */
    private function getCardLimits(string $cardType): array
    {
        return match($cardType) {
            'classic' => [
                'daily' => '500.00',
                'monthly' => '2000.00'
            ],
            'gold' => [
                'daily' => '1000.00',
                'monthly' => '5000.00',
                'credit' => '2000.00'
            ],
            'platinum' => [
                'daily' => '2500.00',
                'monthly' => '10000.00',
                'credit' => '5000.00'
            ],
            default => [
                'daily' => '300.00',
                'monthly' => '1000.00'
            ]
        };
    }

    /**
     * Conditions générales communes à toutes les cartes
     */
    private function getGeneralConditions(): string
    {
        return "CONDITIONS GÉNÉRALES D'UTILISATION DES CARTES SEDEF BANK

1. OBJET
Les présentes conditions générales régissent l'utilisation des cartes de paiement émises par SEDEF BANK.

2. DURÉE DE VALIDITÉ
La carte est valide pendant 3 ans à compter de sa date d'émission.

3. CODE CONFIDENTIEL
Le titulaire s'engage à :
- Mémoriser son code confidentiel sans le noter
- Ne jamais communiquer son code à un tiers
- Signaler immédiatement toute compromission

4. UTILISATION
La carte permet :
- Les paiements chez les commerçants
- Les retraits aux distributeurs automatiques
- Les paiements en ligne (si activés)

5. OPPOSITION
En cas de perte, vol ou utilisation frauduleuse, le titulaire doit faire opposition immédiatement.

6. RESPONSABILITÉ
Le titulaire est responsable des opérations effectuées avec sa carte jusqu'à la déclaration d'opposition.

7. RÉSILIATION
Le contrat peut être résilié à tout moment par l'une ou l'autre partie avec un préavis de 30 jours.";
    }

    /**
     * Récupère les données de l'entreprise depuis la BDD
     */
    private function getCompanyData(): array
    {
        $companySettings = $this->companySettingsRepository->findOneBy([]);
        
        if (!$companySettings) {
            // Valeurs par défaut si pas de configuration en BDD
            return [
                'name' => 'SEDEF BANK',
                'address' => '123 Avenue des Finances',
                'postal_code' => '75001',
                'city' => 'Paris',
                'siren' => '123 456 789',
                'ape' => '6419Z',
                'capital' => '1 000 000',
                'phone' => '01 23 45 67 89',
                'email' => 'contact@bankit.com',
                'logo_base64' => $this->getDefaultLogo()
            ];
        }
        
        return [
            'name' => $companySettings->getCompanyName(),
            'address' => $companySettings->getAddress(),
            'postal_code' => '', // À ajouter dans CompanySettings si nécessaire
            'city' => '', // À extraire de l'adresse ou ajouter un champ
            'siren' => $companySettings->getSiret() ? substr($companySettings->getSiret(), 0, 9) : '',
            'ape' => '', // À ajouter dans CompanySettings si nécessaire
            'capital' => '1 000 000', // À ajouter dans CompanySettings si nécessaire
            'phone' => $companySettings->getPhone(),
            'email' => $companySettings->getEmail(),
            'logo_base64' => $companySettings->getLogoBase64() ?: $this->getDefaultLogo()
        ];
    }

    /**
     * Récupère le logo par défaut
     */
    private function getDefaultLogo(): string
    {
        $logoPath = __DIR__ . '/../../public/logo.svg';
        if (file_exists($logoPath)) {
            return base64_encode(file_get_contents($logoPath));
        }
        return '';
    }

    /**
     * Conditions particulières selon le type de carte
     */
    private function getSpecificConditions(string $cardType): string
    {
        return match($cardType) {
            'classic' => "CONDITIONS PARTICULIÈRES - CARTE CLASSIC

TARIFICATION :
- Cotisation annuelle : Gratuite la première année, puis 24€/an
- Retrait en zone euro : Gratuit (3 par mois), puis 1€
- Paiement en devises : 2% de commission

PLAFONDS :
- Retrait quotidien : 500€
- Paiement mensuel : 2 000€

SERVICES INCLUS :
- Assurance achats : 500€
- Assistance téléphonique 24h/7j",

            'gold' => "CONDITIONS PARTICULIÈRES - CARTE GOLD

TARIFICATION :
- Cotisation annuelle : 60€
- Retraits gratuits dans le monde entier
- Paiements sans commission en zone euro

PLAFONDS :
- Retrait quotidien : 1 000€
- Paiement mensuel : 5 000€
- Crédit revolving : 2 000€

SERVICES INCLUS :
- Assurance voyage internationale
- Cashback 1% sur tous les achats
- Conciergerie téléphonique
- Assurance achats : 2 000€",

            'platinum' => "CONDITIONS PARTICULIÈRES - CARTE PLATINUM

TARIFICATION :
- Cotisation annuelle : 180€
- Tous services gratuits dans le monde entier
- Taux de change préférentiels

PLAFONDS :
- Retrait quotidien : 2 500€
- Paiement mensuel : 10 000€
- Crédit revolving : 5 000€

SERVICES PREMIUM :
- Assurance voyage premium + famille
- Cashback 2% sur tous les achats
- Accès salons VIP aéroports
- Conciergerie premium 24h/24
- Assurance achats : 5 000€
- Service de rachat de billets d'avion",

            default => "Conditions particulières non définies pour ce type de carte."
        };
    }

    /**
     * Génère le PDF du contrat de carte bancaire (non signé)
     */
    private function generateContractPdf(ContractSubscription $contract): string
    {
        $cardSubscription = $contract->getCardSubscription();
        $user = $cardSubscription->getUser();
        
        $filename = 'contract_' . $contract->getReference() . '.pdf';
        $uploadDir = __DIR__ . '/../../public/uploads/contracts/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filepath = $uploadDir . $filename;
        
        // Données pour le template avec informations dynamiques de l'entreprise
        $templateData = [
            'contract' => $contract,
            'subscription' => $cardSubscription,
            'user' => $user,
            'company' => $this->getCompanyData(),
            '_locale' => $user->getLanguage() ?? 'fr'
        ];
        
        // Utiliser le nouveau template professionnel pour contrat NON SIGNÉ
        $html = $this->twig->render('pdf/card_contract.html.twig', $templateData);
        
        // Configuration DomPDF
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Sauvegarde du PDF
        file_put_contents($filepath, $dompdf->output());
        
        return 'uploads/contracts/' . $filename;
    }

    /**
     * Génère le PDF du contrat signé (avec informations de signature)
     */
    private function generateSignedContractPdf(ContractSubscription $contract): string
    {
        $cardSubscription = $contract->getCardSubscription();
        $user = $cardSubscription->getUser();
        
        $filename = 'contract_signed_' . $contract->getReference() . '.pdf';
        $uploadDir = __DIR__ . '/../../public/uploads/contracts/';
        $filepath = $uploadDir . $filename;
        
        // Données pour le template avec informations de signature
        $templateData = [
            'contract' => $contract,
            'subscription' => $cardSubscription,
            'user' => $user,
            'company' => $this->getCompanyData(),
            '_locale' => $user->getLanguage() ?? 'fr'
        ];
        
        // Utiliser le nouveau template professionnel pour contrat SIGNÉ
        $html = $this->twig->render('pdf/card_contract.html.twig', $templateData);
        
        // Configuration DomPDF
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Sauvegarde du PDF
        file_put_contents($filepath, $dompdf->output());
        
        return 'uploads/contracts/' . $filename;
    }

    /**
     * Notifie l'admin qu'un contrat a été signé
     */
    private function notifyAdminContractSigned(ContractSubscription $contract): void
    {
        $email = (new Email())
            ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
            ->to($this->defaultFromEmail)
            ->subject('SEDEF BANK - Contrat signé - Validation requise')
            ->html($this->twig->render('email/admin_contract_signed.html.twig', [
                'contract' => $contract
            ]));

        $this->mailer->send($email);
    }

    /**
     * Marque les contrats expirés
     */
    public function markExpiredContracts(): int
    {
        $expiredContracts = $this->contractRepository->findExpiredContracts();
        $count = 0;

        foreach ($expiredContracts as $contract) {
            $contract->setStatus('expired');
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info("Marqué {$count} contrats comme expirés");
        }

        return $count;
    }
}
