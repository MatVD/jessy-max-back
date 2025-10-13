<?php

namespace App\Repository;

use App\Entity\RefundRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefundRequest>
 */
class RefundRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundRequest::class);
    }

    /**
     * Vérifie si un ticket a déjà une demande de remboursement en attente ou approuvée
     */
    public function hasPendingOrApprovedRefund(string $ticketId): bool
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.ticket = :ticketId')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('ticketId', $ticketId)
            ->setParameter('statuses', ['pending', 'approved'])
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Trouve les demandes en attente
     */
    public function findPendingRequests(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
