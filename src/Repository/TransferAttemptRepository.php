<?php

namespace App\Repository;

use App\Entity\TransferAttempt;
use App\Entity\Transfer;
use App\Entity\TransferCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferAttempt>
 */
class TransferAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferAttempt::class);
    }

    /**
     * Compte les tentatives échouées pour un code spécifique
     */
    public function countFailedAttemptsForCode(TransferCode $code): int
    {
        return $this->createQueryBuilder('ta')
            ->select('COUNT(ta.id)')
            ->andWhere('ta.transferCode = :code')
            ->andWhere('ta.isSuccess = :success')
            ->setParameter('code', $code)
            ->setParameter('success', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve toutes les tentatives pour un virement (pour audit)
     */
    public function findByTransfer(Transfer $transfer): array
    {
        return $this->createQueryBuilder('ta')
            ->leftJoin('ta.transferCode', 'tc')
            ->addSelect('tc')
            ->andWhere('ta.transfer = :transfer')
            ->setParameter('transfer', $transfer)
            ->orderBy('ta.attemptedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tentatives récentes par IP pour détecter les abus
     */
    public function findRecentAttemptsByIp(string $ipAddress, int $minutes = 15): array
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");
        
        return $this->createQueryBuilder('ta')
            ->andWhere('ta.ipAddress = :ip')
            ->andWhere('ta.attemptedAt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', $since)
            ->orderBy('ta.attemptedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
