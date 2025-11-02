<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Enum\PaymentStatus;
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
     * Trouve tous les tickets payés pour un événement
     */
    public function findPaidByEvent(string $eventId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.event = :eventId')
            ->andWhere('t.paymentStatus = :status')
            ->setParameter('eventId', $eventId)
            ->setParameter('status', PaymentStatus::PAID)
            ->orderBy('t.purchasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les tickets payés pour une formation
     */
    public function findPaidByFormation(string $formationId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.formation = :formationId')
            ->andWhere('t.paymentStatus = :status')
            ->setParameter('formationId', $formationId)
            ->setParameter('status', PaymentStatus::PAID)
            ->orderBy('t.purchasedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un ticket par QR code
     */
    public function findByQrCode(string $qrCode): ?Ticket
    {
        return $this->createQueryBuilder('t')
            ->where('t.qrCode = :qrCode')
            ->setParameter('qrCode', $qrCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les tickets vendus pour un événement
     */
    public function countSoldByEvent(string $eventId): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.event = :eventId')
            ->andWhere('t.paymentStatus = :status')
            ->setParameter('eventId', $eventId)
            ->setParameter('status', PaymentStatus::PAID)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les tickets en attente de paiement depuis plus de X heures
     */
    public function findExpiredPendingTickets(int $hoursAgo = 24): array
    {
        $date = new \DateTimeImmutable("-{$hoursAgo} hours");

        return $this->createQueryBuilder('t')
            ->where('t.paymentStatus = :status')
            ->andWhere('t.createdAt < :date')
            ->setParameter('status', PaymentStatus::PENDING)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les tickets d'un utilisateur
     */
    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tickets d'un guest par email
     */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.customerEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur a déjà acheté un ticket pour cet événement
     */
    public function hasUserPurchasedEvent(string $userId, string $eventId): bool
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :userId')
            ->andWhere('t.event = :eventId')
            ->andWhere('t.paymentStatus = :status')
            ->setParameter('userId', $userId)
            ->setParameter('eventId', $eventId)
            ->setParameter('status', PaymentStatus::PAID)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Statistiques des ventes par mois
     */
    public function getSalesStatsByMonth(int $year): array
    {
        return $this->createQueryBuilder('t')
            ->select('MONTH(t.purchasedAt) as month, COUNT(t.id) as total, SUM(t.totalPrice) as revenue')
            ->where('YEAR(t.purchasedAt) = :year')
            ->andWhere('t.paymentStatus = :status')
            ->setParameter('year', $year)
            ->setParameter('status', PaymentStatus::PAID)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }
}