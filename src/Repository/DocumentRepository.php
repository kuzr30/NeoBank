<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\CreditApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Find documents by owner
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.type = :type')
            ->setParameter('type', $type)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by verification status
     */
    public function findByVerificationStatus(string $status): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.verificationStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending verification documents
     */
    public function findPendingVerification(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.verificationStatus = :status')
            ->setParameter('status', 'pending')
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find verified documents
     */
    public function findVerified(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.verificationStatus = :status')
            ->setParameter('status', 'verified')
            ->orderBy('d.verifiedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rejected documents
     */
    public function findRejected(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.verificationStatus = :status')
            ->setParameter('status', 'rejected')
            ->orderBy('d.verifiedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by credit application
     */
    public function findByCreditApplication(CreditApplication $creditApplication): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.creditApplication = :application')
            ->setParameter('application', $creditApplication)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents expiring soon
     */
    public function findExpiringSoon(\DateTimeInterface $threshold = null): array
    {
        if (!$threshold) {
            $threshold = new \DateTime('+30 days');
        }

        return $this->createQueryBuilder('d')
            ->andWhere('d.expiryDate IS NOT NULL')
            ->andWhere('d.expiryDate <= :threshold')
            ->andWhere('d.expiryDate > :now')
            ->setParameter('threshold', $threshold)
            ->setParameter('now', new \DateTime())
            ->orderBy('d.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired documents
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.expiryDate IS NOT NULL')
            ->andWhere('d.expiryDate <= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('d.expiryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by security classification
     */
    public function findBySecurityClassification(string $classification): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.securityClassification = :classification')
            ->setParameter('classification', $classification)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find documents by size range
     */
    public function findBySizeRange(int $minSize, int $maxSize): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.fileSize BETWEEN :minSize AND :maxSize')
            ->setParameter('minSize', $minSize)
            ->setParameter('maxSize', $maxSize)
            ->orderBy('d.fileSize', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find large documents (above threshold)
     */
    public function findLargeDocuments(int $sizeThreshold = 10485760): array // 10MB default
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.fileSize >= :threshold')
            ->setParameter('threshold', $sizeThreshold)
            ->orderBy('d.fileSize', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total storage used by owner
     */
    public function getTotalStorageUsedByOwner(User $owner): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.fileSize)')
            ->andWhere('d.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Find documents by verifier
     */
    public function findByVerifier(User $verifier): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.verifiedBy = :verifier')
            ->setParameter('verifier', $verifier)
            ->orderBy('d.verifiedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get verification statistics
     */
    public function getVerificationStats(): array
    {
        return $this->createQueryBuilder('d')
            ->select('
                COUNT(d.id) as total,
                SUM(CASE WHEN d.verificationStatus = :pendingStatus THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN d.verificationStatus = :verifiedStatus THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN d.verificationStatus = :rejectedStatus THEN 1 ELSE 0 END) as rejected
            ')
            ->setParameter('pendingStatus', 'pending')
            ->setParameter('verifiedStatus', 'verified')
            ->setParameter('rejectedStatus', 'rejected')
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Find documents by file extension
     */
    public function findByFileExtension(string $extension): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.fileName LIKE :extension')
            ->setParameter('extension', '%.' . $extension)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
