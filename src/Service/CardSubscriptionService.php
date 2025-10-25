<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\Card;
use App\Entity\CardSubscription;
use App\Entity\ContractSubscription;
use App\Entity\User;
use App\Repository\CardSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des souscriptions de cartes bancaires
 * 
 * Responsabilités:
 * - Création de nouvelles souscriptions
 * - Gestion des contrats de souscription
 * - Validation et traitement administratif
 * - Génération de cartes après approbation
 * - Notifications des utilisateurs
 * 
 * Suit les principes SOLID:
 * - Single Responsibility: Gestion exclusive des souscriptions
 * - Open/Closed: Extensible via événements
 * - Dependency Inversion: Injection des dépendances
 */
class CardSubscriptionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CardSubscriptionRepository $subscriptionRepository,
        private CardNumberGeneratorService $cardNumberGenerator,
        private CardNotificationService $notificationService,
        private ContractSubscriptionService $contractService,
        private LoggerInterface $logger
    ) {}

    /**
     * Crée une nouvelle demande de souscription
     */
    public function createSubscription(
        User $user,
        Account $account,
        string $cardType,
        string $cardBrand,
        ?string $reason = null
    ): CardSubscription {
        // Validation des paramètres
        $this->validateSubscriptionRequest($user, $account, $cardType, $cardBrand);
        
        // Vérifier que l'utilisateur n'a pas déjà une carte active ou une souscription en attente
        if ($this->hasActiveCardOrPendingSubscription($user)) {
            throw new \InvalidArgumentException(
                'Vous avez déjà une carte active ou une demande de souscription en cours. Une seule carte par utilisateur est autorisée.'
            );
        }

        $subscription = new CardSubscription();
        $subscription->setUser($user);
        $subscription->setAccount($account);
        $subscription->setCardType($cardType);
        $subscription->setCardBrand($cardBrand);
        $subscription->setReason($reason);
        $subscription->setStatus('pending');

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Log de la création
        $this->logger->info('Nouvelle souscription de carte créée', [
            'subscription_id' => $subscription->getId(),
            'user_id' => $user->getId(),
            'account_id' => $account->getId(),
            'card_type' => $cardType,
            'brand' => $cardBrand
        ]);

        // Notification à l'utilisateur : demande en cours de traitement
        $this->notificationService->notifySubscriptionCreated($subscription);

        return $subscription;
    }

    /**
     * Approuve une souscription et envoie le contrat pour signature
     */
    public function approveSubscription(
        CardSubscription $subscription,
        User $approver,
        ?string $adminComment = null
    ): void {
        if ($subscription->getStatus() !== 'fees_set') {
            throw new \InvalidArgumentException(
                'Seules les souscriptions avec frais définis peuvent être approuvées'
            );
        }

        // Marquer comme approuvé
        $subscription->approve($approver);
        $subscription->setReason($adminComment);
        
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Créer et envoyer le contrat maintenant que c'est approuvé
        try {
            $contract = $this->contractService->createContract($subscription);
            $this->contractService->sendContractByEmail($contract);
            
            $this->logger->info('Contrat de souscription créé et envoyé après approbation', [
                'subscription_id' => $subscription->getId(),
                'contract_reference' => $contract->getReference(),
                'user_email' => $subscription->getUser()->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du contrat', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Erreur lors de l\'envoi du contrat: ' . $e->getMessage());
        }

        // Log de l'approbation
        $this->logger->info('Souscription de carte approuvée', [
            'subscription_id' => $subscription->getId(),
            'approved_by' => $approver->getId()
        ]);

        // Notification à l'utilisateur
        $this->notificationService->notifySubscriptionApproved($subscription);
    }

    /**
     * Traite la signature du contrat et passe en attente de paiement
     */
    public function processContractSigned(CardSubscription $subscription): void {
        if ($subscription->getStatus() !== 'approved') {
            throw new \InvalidArgumentException(
                'Seules les souscriptions approuvées peuvent être signées'
            );
        }

        $subscription->setStatus('signed');
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Notification à l'admin et à l'utilisateur
        $this->notificationService->notifyContractSigned($subscription);
    }

    /**
     * Valide le paiement et génère la carte
     */
    public function validatePaymentAndCreateCard(
        CardSubscription $subscription,
        User $approver,
        ?string $adminComment = null
    ): Card {
        if ($subscription->getStatus() !== 'signed') {
            throw new \InvalidArgumentException(
                'Le contrat doit être signé avant validation du paiement'
            );
        }

        // Génération du numéro de carte
        $cardNumber = $this->cardNumberGenerator->generateValidCardNumber(
            $subscription->getCardBrand()
        );
        
        // Vérification d'unicité du numéro
        while ($this->cardNumberExists($cardNumber)) {
            $cardNumber = $this->cardNumberGenerator->generateValidCardNumber(
                $subscription->getCardBrand()
            );
        }

        // Création de la carte
        $card = new Card();
        $card->setAccount($subscription->getAccount());
        $card->setCardNumber($cardNumber);
        $card->setCardholderName($subscription->getUser()->getFirstName() . ' ' . $subscription->getUser()->getLastName());
        $card->setType('debit');
        $card->setCategory($subscription->getCardType());
        $card->setCvv($this->cardNumberGenerator->generateCVV());
        $card->setExpiryDate($this->cardNumberGenerator->generateExpiryDate());
        $card->setStatus('active');
        $card->setDailySpent('0.00');
        $card->setMonthlySpent('0.00');
        $card->setContactless(true);
        $card->setOnlinePayments(true);
        $card->setInternationalPayments(false);
        $card->setCreatedAt(new \DateTimeImmutable());

        // Mise à jour de la souscription
        $subscription->setStatus('active');
        $subscription->setCard($card);
        $subscription->setProcessedBy($approver);
        $subscription->setProcessedAt(new \DateTimeImmutable());
        
        if ($adminComment) {
            $subscription->setReason($adminComment);
        }

        $this->entityManager->persist($card);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Log de la validation
        $this->logger->info('Paiement validé et carte créée', [
            'subscription_id' => $subscription->getId(),
            'card_id' => $card->getId(),
            'validated_by' => $approver->getId()
        ]);

        // Notification à l'utilisateur
        $this->notificationService->notifyCardReady($subscription, $card);

        return $card;
    }

    /**
     * Rejette une souscription
     */
    public function rejectSubscription(
        CardSubscription $subscription,
        User $rejector,
        string $rejectionReason
    ): void {
        if (in_array($subscription->getStatus(), ['active', 'rejected', 'cancelled'])) {
            throw new \InvalidArgumentException(
                'Cette souscription ne peut plus être rejetée'
            );
        }

        $subscription->reject($rejector, $rejectionReason);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Log du rejet
        $this->logger->info('Souscription de carte rejetée', [
            'subscription_id' => $subscription->getId(),
            'rejected_by' => $rejector->getId(),
            'reason' => $rejectionReason
        ]);

        // Notification à l'utilisateur
        $this->notificationService->notifySubscriptionRejected($subscription);
    }

    /**
     * Récupère les souscriptions en attente pour l'administration
     */
    public function getPendingSubscriptions(): array
    {
        return $this->subscriptionRepository->findBy([
            'status' => 'pending'
        ], ['createdAt' => 'ASC']);
    }

    /**
     * Récupère l'historique des souscriptions d'un utilisateur
     */
    public function getUserSubscriptionHistory(User $user): array
    {
        return $this->subscriptionRepository->findBy([
            'user' => $user
        ], ['createdAt' => 'DESC']);
    }

    /**
     * Vérifie si l'utilisateur a une souscription en cours
     */
    public function hasActivependingSubscription(User $user, Account $account): bool
    {
        $pendingSubscription = $this->subscriptionRepository->findOneBy([
            'user' => $user,
            'account' => $account,
            'status' => 'pending'
        ]);

        return $pendingSubscription !== null;
    }

    /**
     * Validation des paramètres de souscription
     */
    private function validateSubscriptionRequest(
        User $user,
        Account $account,
        string $cardType,
        string $cardBrand
    ): void {
        // Vérifier que l'utilisateur est propriétaire du compte
        if (!$account->getOwner() || $account->getOwner()->getId() !== $user->getId()) {
            throw new \InvalidArgumentException(
                'L\'utilisateur n\'est pas propriétaire de ce compte'
            );
        }

        // Vérifier que le compte est actif
        if ($account->getStatus() !== 'active') {
            throw new \InvalidArgumentException(
                'Le compte doit être actif pour souscrire une carte'
            );
        }

        // Validation du type de carte
        $validTypes = ['classic', 'gold', 'platinum'];
        if (!in_array($cardType, $validTypes)) {
            throw new \InvalidArgumentException(
                'Type de carte invalide: ' . $cardType
            );
        }

        // Validation de la marque
        $validBrands = ['visa', 'mastercard'];
        if (!in_array($cardBrand, $validBrands)) {
            throw new \InvalidArgumentException(
                'Marque de carte invalide: ' . $cardBrand
            );
        }
    }

    /**
     * Vérifie si l'utilisateur a déjà une carte active ou une souscription en attente
     */
    public function hasActiveCardOrPendingSubscription(User $user): bool
    {
        // Vérifier s'il a une carte active
        $activeCard = $this->entityManager->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->join('c.account', 'a')
            ->where('a.owner = :user')
            ->andWhere('c.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['active', 'blocked']) // blocked car peut être débloquée
            ->getQuery()
            ->getOneOrNullResult();

        if ($activeCard) {
            return true;
        }

        // Vérifier s'il a une souscription en attente
        $pendingSubscription = $this->subscriptionRepository->findOneBy([
            'user' => $user,
            'status' => 'pending'
        ]);

        return $pendingSubscription !== null;
    }

    /**
     * Récupère la carte active ou la souscription en attente de l'utilisateur
     */
    public function getUserCardOrSubscription(User $user): array
    {
        // Chercher une carte active
        $activeCard = $this->entityManager->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->join('c.account', 'a')
            ->where('a.owner = :user')
            ->andWhere('c.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['active', 'blocked'])
            ->getQuery()
            ->getOneOrNullResult();

        if ($activeCard) {
            return ['type' => 'card', 'entity' => $activeCard];
        }

        // Chercher une souscription en attente (tous les statuts sauf 'cancelled' et 'rejected')
        $pendingSubscription = $this->subscriptionRepository
            ->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['pending', 'approved', 'fees_set'])
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($pendingSubscription) {
            return ['type' => 'subscription', 'entity' => $pendingSubscription];
        }

        return ['type' => 'none', 'entity' => null];
    }

    /**
     * Vérifie si un numéro de carte existe déjà
     */
    private function cardNumberExists(string $cardNumber): bool
    {
        $existingCard = $this->entityManager->getRepository(Card::class)
            ->findOneBy(['cardNumber' => $cardNumber]);
        
        return $existingCard !== null;
    }

    /**
     * Crée une carte physique après signature du contrat
     */
    public function createCard(CardSubscription $subscription): Card
    {
        // Vérifier que la souscription est approuvée
        if ($subscription->getStatus() !== 'approved') {
            throw new \InvalidArgumentException('La souscription doit être approuvée pour créer une carte');
        }

        // Vérifier qu'il n'y a pas déjà une carte
        if ($subscription->getCard()) {
            throw new \InvalidArgumentException('Une carte existe déjà pour cette souscription');
        }

        // Vérifier que le contrat est signé
        $contract = $this->entityManager->getRepository(ContractSubscription::class)
            ->findOneBy(['cardSubscription' => $subscription, 'status' => 'signed']);

        if (!$contract) {
            throw new \InvalidArgumentException('Le contrat doit être signé avant de créer la carte');
        }

        // Créer la carte
        $card = new Card();
        $card->setAccount($subscription->getAccount());
        $card->setType($subscription->getCardType());
        $card->setCategory($subscription->getCardBrand());
        $card->setCardNumber($this->cardNumberGenerator->generateValidCardNumber($subscription->getCardBrand()));
        $card->setCardholderName($subscription->getUser()->getFirstName() . ' ' . $subscription->getUser()->getLastName());
        $card->setCvv($this->cardNumberGenerator->generateCVV());
        $card->setExpiryDate(new \DateTimeImmutable('+3 years'));
        $card->setStatus('active');
        $card->setCreatedAt(new \DateTimeImmutable());
        $card->setDailyLimit((string) $subscription->getDailyLimit());
        $card->setMonthlyLimit((string) $subscription->getMonthlyLimit());
        $card->setCreditLimit((string) $subscription->getCreditLimit());

        // Associer la carte à la souscription
        $subscription->setCard($card);
        $subscription->setStatus('completed');

        $this->entityManager->persist($card);
        $this->entityManager->flush();

        $this->logger->info('Carte créée pour la souscription', [
            'subscription_id' => $subscription->getId(),
            'card_number' => substr($card->getCardNumber(), -4),
            'user_id' => $subscription->getUser()->getId()
        ]);

        return $card;
    }

    /**
     * Persiste une souscription (pour la mise à jour des frais)
     */
    public function persistSubscription(CardSubscription $subscription): void
    {
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    /**
     * Approuve une souscription avec frais définis et génère le contrat
     */
    public function approveWithContract(CardSubscription $subscription, User $approver): void
    {
        if ($subscription->getStatus() !== 'fees_set') {
            throw new \InvalidArgumentException(
                'Les frais doivent être définis avant d\'approuver la souscription'
            );
        }

        // Marquer comme approuvée
        $subscription->setStatus('approved');
        $subscription->setProcessedBy($approver);
        $subscription->setProcessedAt(new \DateTimeImmutable());

        // Créer le contrat avec les frais définis
        $contract = $this->contractService->createContract($subscription);
        
        // Envoyer le contrat par email au client
        $this->contractService->sendContractByEmail($contract);
        
        $this->entityManager->flush();

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        // Log de l'approbation
        $this->logger->info('Souscription de carte approuvée avec contrat', [
            'subscription_id' => $subscription->getId(),
            'approved_by' => $approver->getId(),
            'activation_fee' => $subscription->getActivationFee(),
            'monthly_fee' => $subscription->getMonthlyFee()
        ]);

        // Envoyer email au client avec le contrat
        $this->sendContractEmail($subscription);
    }

    /**
     * Envoie l'email avec le contrat au client
     */
    private function sendContractEmail(CardSubscription $subscription): void
    {
        try {
            $user = $subscription->getUser();
            $contract = $subscription->getContract();

            if (!$contract) {
                throw new \Exception('Aucun contrat associé à la souscription');
            }

            $subject = sprintf('Contrat de souscription carte %s %s - Frais: %s€',
                $subscription->getCardBrand(),
                $subscription->getCardType(),
                $subscription->getActivationFee()
            );

            $templateData = [
                'user' => $user,
                'subscription' => $subscription,
                'contract' => $contract,
                'activation_fee' => $subscription->getActivationFee(),
                'monthly_fee' => $subscription->getMonthlyFee(),
                'daily_limit' => $subscription->getDailyLimit(),
                'monthly_limit' => $subscription->getMonthlyLimit(),
            ];

            $this->emailService->sendTemplatedEmail(
                $user->getEmail(),
                $subject,
                'email/card_subscription_contract.html.twig',
                $templateData
            );

            $this->logger->info('Email de contrat envoyé', [
                'subscription_id' => $subscription->getId(),
                'user_email' => $user->getEmail(),
                'contract_reference' => $contract->getReference()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de contrat', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Récupère une souscription par son ID
     */
    public function getSubscriptionById(int $id): ?CardSubscription
    {
        return $this->subscriptionRepository->find($id);
    }
}
