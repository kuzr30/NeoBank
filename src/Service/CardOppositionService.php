<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\CardOpposition;
use App\Entity\User;
use App\Repository\CardOppositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des oppositions de cartes bancaires
 * 
 * Responsabilités:
 * - Déclaration d'oppositions (perte, vol, compromission)
 * - Blocage immédiat des cartes
 * - Traitement administratif des oppositions
 * - Émission de cartes de remplacement
 * 
 * Principe KISS: Interface simple et actions claires
 */
class CardOppositionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CardOppositionRepository $oppositionRepository,
        private CardSubscriptionService $subscriptionService,
        private CardNotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    /**
     * Crée une opposition et bloque immédiatement la carte
     */
    public function createOpposition(
        Card $card,
        User $user,
        string $reason,
        ?string $description = null
    ): CardOpposition {
        // Validation des droits
        $this->validateOppositionRequest($card, $user);

        // Vérifier qu'il n'y a pas déjà une opposition en cours
        if ($this->hasActiveOpposition($card)) {
            throw new \InvalidArgumentException(
                'Une opposition est déjà en cours de traitement pour cette carte'
            );
        }

        // Blocage immédiat de la carte
        $this->blockCard($card);

        // Création de l'opposition
        $opposition = new CardOpposition();
        $opposition->setCard($card);
        $opposition->setUser($user);
        $opposition->setReason($reason);
        $opposition->setDescription($description);
        $opposition->setStatus('pending');
        $opposition->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($opposition);
        $this->entityManager->flush();

        // Log de l'opposition
        $this->logger->warning('Opposition de carte créée', [
            'opposition_id' => $opposition->getId(),
            'card_id' => $card->getId(),
            'user_id' => $user->getId(),
            'reason' => $reason
        ]);

        // Notifications
        $this->notificationService->notifyOppositionCreated($opposition);

        return $opposition;
    }

    /**
     * Traite une opposition et propose une carte de remplacement
     */
    public function processOpposition(
        CardOpposition $opposition,
        User $processor,
        bool $issueReplacement = true,
        ?string $adminComment = null
    ): ?CardSubscription {
        if ($opposition->getStatus() !== 'pending') {
            throw new \InvalidArgumentException(
                'Seules les oppositions en attente peuvent être traitées'
            );
        }

        $opposition->setStatus('processed');
        $opposition->setProcessedBy($processor);
        $opposition->setProcessedAt(new \DateTimeImmutable());
        $opposition->setAdminComment($adminComment);

        $replacementSubscription = null;

        // Émission automatique d'une carte de remplacement si demandée
        if ($issueReplacement) {
            $card = $opposition->getCard();
            
            $replacementSubscription = $this->subscriptionService->createSubscription(
                $opposition->getUser(),
                $card->getAccount(),
                $card->getCardType(),
                $card->getBrand(),
                'Remplacement suite à opposition - Ref: ' . $opposition->getReference()
            );

            $opposition->setReplacementSubscription($replacementSubscription);
        }

        $this->entityManager->persist($opposition);
        $this->entityManager->flush();

        // Log du traitement
        $this->logger->info('Opposition traitée', [
            'opposition_id' => $opposition->getId(),
            'processed_by' => $processor->getId(),
            'replacement_issued' => $issueReplacement
        ]);

        // Notifications
        $this->notificationService->notifyOppositionProcessed($opposition);

        return $replacementSubscription;
    }

    /**
     * Récupère les oppositions en attente de traitement
     */
    public function getPendingOppositions(): array
    {
        return $this->oppositionRepository->findBy([
            'status' => 'pending'
        ], ['createdAt' => 'ASC']);
    }

    /**
     * Récupère l'historique des oppositions d'un utilisateur
     */
    public function getUserOppositionHistory(User $user): array
    {
        return $this->oppositionRepository->findBy([
            'user' => $user
        ], ['createdAt' => 'DESC']);
    }

    /**
     * Vérifie si une carte a une opposition active
     */
    public function hasActiveOpposition(Card $card): bool
    {
        $activeOpposition = $this->oppositionRepository->findOneBy([
            'card' => $card,
            'status' => 'pending'
        ]);

        return $activeOpposition !== null;
    }

    /**
     * Bloque immédiatement une carte
     */
    private function blockCard(Card $card): void
    {
        if ($card->getStatus() === 'blocked') {
            return; // Déjà bloquée
        }

        $previousStatus = $card->getStatus();
        $card->setStatus('blocked');
        $card->setBlockedAt(new \DateTimeImmutable());

        $this->entityManager->persist($card);

        $this->logger->warning('Carte bloquée suite à opposition', [
            'card_id' => $card->getId(),
            'previous_status' => $previousStatus,
            'blocked_at' => $card->getBlockedAt()?->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Validation des droits pour créer une opposition
     */
    private function validateOppositionRequest(Card $card, User $user): void
    {
        // Vérifier que l'utilisateur est propriétaire de la carte
        if ($card->getUser()->getId() !== $user->getId()) {
            throw new \InvalidArgumentException(
                'L\'utilisateur n\'est pas propriétaire de cette carte'
            );
        }

        // Vérifier que la carte n'est pas déjà expirée ou annulée
        if (in_array($card->getStatus(), ['expired', 'cancelled'])) {
            throw new \InvalidArgumentException(
                'Impossible de faire opposition sur une carte expirée ou annulée'
            );
        }
    }

    /**
     * Obtient les statistiques d'oppositions pour l'administration
     */
    public function getOppositionStats(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        // Oppositions par raison
        $reasonStats = $qb->select('o.reason, COUNT(o.id) as count')
            ->from(CardOpposition::class, 'o')
            ->groupBy('o.reason')
            ->getQuery()
            ->getResult();

        // Oppositions par mois (12 derniers mois)
        $monthlyStats = $qb->select('DATE_FORMAT(o.createdAt, \'%Y-%m\') as month, COUNT(o.id) as count')
            ->from(CardOpposition::class, 'o')
            ->where('o.createdAt >= :dateLimit')
            ->setParameter('dateLimit', new \DateTimeImmutable('-12 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'by_reason' => $reasonStats,
            'by_month' => $monthlyStats,
            'total_pending' => count($this->getPendingOppositions())
        ];
    }
}
