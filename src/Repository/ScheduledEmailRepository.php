<?php

namespace App\Repository;

use App\Entity\ScheduledEmail;
use App\Entity\User;
use App\Enum\EmailStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduledEmail>
 */
class ScheduledEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduledEmail::class);
    }

    /**
     * Find emails that are scheduled and ready to be sent
     *
     * @return ScheduledEmail[]
     */
    public function findPendingScheduled(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :scheduled')
            ->andWhere('e.scheduledFor <= :now')
            ->setParameter('scheduled', EmailStatus::SCHEDULED)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.scheduledFor', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find emails by recipient
     *
     * @return ScheduledEmail[]
     */
    public function findByRecipient(User $recipient): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find emails by status
     *
     * @return ScheduledEmail[]
     */
    public function findByStatus(EmailStatus $status): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find emails pending immediate send (not scheduled, status pending)
     *
     * @return ScheduledEmail[]
     */
    public function findPendingImmediate(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :pending')
            ->andWhere('e.scheduledFor IS NULL OR e.scheduledFor <= :now')
            ->setParameter('pending', EmailStatus::PENDING)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count failed emails
     */
    public function countFailed(): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :failed')
            ->setParameter('failed', EmailStatus::FAILED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count pending emails (scheduled or immediate)
     */
    public function countPending(): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status IN (:statuses)')
            ->setParameter('statuses', [EmailStatus::PENDING, EmailStatus::SCHEDULED])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
