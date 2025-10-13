<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * Trouve tous les billets d'un client par email
     */
    public function findByCustomerEmail(string $email): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.customerEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('t.purchasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un QR code existe déjà
     */
    public function qrCodeExists(string $qrCode): bool
    {
        $result = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.qrCode = :qrCode')
            ->setParameter('qrCode', $qrCode)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
