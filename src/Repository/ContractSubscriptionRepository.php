<?php

namespace App\Repository;

use App\Entity\ContractSubscription;
use App\Entity\CardSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContractSubscription>
 */
class ContractSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractSubscription::class);
    }

    /**
     * Trouve un contrat par référence
     */
    public function findByReference(string $reference): ?ContractSubscription
    {
        return $this->findOneBy(['reference' => $reference]);
    }

    /**
     * Trouve un contrat par souscription de carte
     */
    public function findByCardSubscription(CardSubscription $cardSubscription): ?ContractSubscription
    {
        return $this->findOneBy(['cardSubscription' => $cardSubscription]);
    }

    /**
     * Trouve les contrats expirés qui n'ont pas été signés
     */
    public function findExpiredContracts(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :sentStatus')
            ->andWhere('c.expiresAt < :now')
            ->setParameter('sentStatus', 'sent')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats en attente de signature
     */
    public function findPendingSignature(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :sentStatus')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('sentStatus', 'sent')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les contrats signés récemment
     */
    public function findRecentlySigned(int $days = 7): array
    {
        $since = (new \DateTimeImmutable())->modify("-{$days} days");
        
        return $this->createQueryBuilder('c')
            ->where('c.status = :signedStatus')
            ->andWhere('c.signedAt >= :since')
            ->setParameter('signedStatus', 'signed')
            ->setParameter('since', $since)
            ->orderBy('c.signedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des contrats
     */
    public function getContractStats(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.status, COUNT(c.id) as count')
            ->groupBy('c.status');

        $results = $qb->getQuery()->getResult();
        
        $stats = [
            'pending' => 0,
            'sent' => 0,
            'signed' => 0,
            'expired' => 0,
            'total' => 0
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
            $stats['total'] += (int)$result['count'];
        }

        return $stats;
    }

    public function save(ContractSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContractSubscription $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
