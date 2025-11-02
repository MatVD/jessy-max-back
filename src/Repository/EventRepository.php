<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Trouve les événements avec des places disponibles
     */
    public function findAvailableEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.availableTickets > 0')
            ->andWhere('e.date >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements à venir
     */
    public function findUpcomingEvents(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.date >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un événement par ses tickets payés
     */
    public function findPaidByEvent(string $eventId): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.tickets', 't')
            ->addSelect('t')
            ->where('e.id = :eventId')
            ->andWhere('t.paymentStatus = :status')
            ->setParameter('eventId', $eventId)
            ->setParameter('status', 'PAID')
            ->getQuery()
            ->getResult();
    }
}
