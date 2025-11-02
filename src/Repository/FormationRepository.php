<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * Trouve les formations avec des places disponibles
     */
    public function findAvailableFormations(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.currentParticipants < f.maxParticipants')
            ->andWhere('f.startDate >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('f.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les formations à venir
     */
    public function findUpcomingFormations(int $limit = 10): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.startDate >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('f.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une formation par ses tickets payés
     */
    public function findPaidByFormation(string $formationId): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.tickets', 't')
            ->addSelect('t')
            ->where('f.id = :formationId')
            ->andWhere('t.paymentStatus = :status')
            ->setParameter('formationId', $formationId)
            ->setParameter('status', 'PAID')
            ->getQuery()
            ->getResult();
    }
}
