<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;
use App\Entity\Event;
use App\Entity\Formation;
use App\Service\QRCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Processor pour gérer la création de tickets avec toute la logique métier
 */
class TicketProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QRCodeGenerator $qrCodeGenerator,
    ) {}

    /**
     * @param Ticket $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Ticket
    {
        // Validation: Un ticket doit avoir soit un event soit une formation
        if (!$data->getEvent() && !$data->getFormation()) {
            throw new BadRequestHttpException('A ticket must be associated with either an event or a formation.');
        }

        if ($data->getEvent() && $data->getFormation()) {
            throw new BadRequestHttpException('A ticket cannot be associated with both an event and a formation.');
        }

        // Démarrer une transaction pour garantir la cohérence
        $this->entityManager->beginTransaction();

        try {
            // Traitement pour un Event
            if ($data->getEvent()) {
                $this->processEventTicket($data);
            }

            // Traitement pour une Formation
            if ($data->getFormation()) {
                $this->processFormationTicket($data);
            }

            // Générer un QR code unique
            $qrCode = $this->qrCodeGenerator->generateUniqueTicketCode();
            $data->setQrCode($qrCode);

            // Définir le statut du paiement (pour le moment, on considère "completed" directement)
            $data->setPaymentStatus('completed');

            // Persister le ticket
            $this->entityManager->persist($data);
            $this->entityManager->flush();

            // Commit de la transaction
            $this->entityManager->commit();

            return $data;
        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Traite la création d'un ticket pour un événement
     */
    private function processEventTicket(Ticket $ticket): void
    {
        $event = $ticket->getEvent();
        $quantity = $ticket->getQuantity();

        // Verrouillage pessimiste pour éviter les race conditions
        $this->entityManager->lock($event, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

        // Vérifier la disponibilité
        if ($event->getAvailableTickets() < $quantity) {
            throw new UnprocessableEntityHttpException(
                sprintf('Insufficient tickets available. Only %d tickets remaining.', $event->getAvailableTickets())
            );
        }

        // Calculer le prix total
        $unitPrice = (float) $event->getPrice();
        $totalPrice = $unitPrice * $quantity;
        $ticket->setTotalPrice((string) number_format($totalPrice, 2, '.', ''));

        // Décrémenter les billets disponibles
        $event->setAvailableTickets($event->getAvailableTickets() - $quantity);
        $this->entityManager->persist($event);
    }

    /**
     * Traite la création d'un ticket pour une formation
     */
    private function processFormationTicket(Ticket $ticket): void
    {
        $formation = $ticket->getFormation();
        $quantity = $ticket->getQuantity();

        // Verrouillage pessimiste pour éviter les race conditions
        $this->entityManager->lock($formation, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

        // Vérifier la disponibilité
        $availablePlaces = $formation->getMaxParticipants() - $formation->getCurrentParticipants();
        if ($availablePlaces < $quantity) {
            throw new UnprocessableEntityHttpException(
                sprintf('Insufficient places available. Only %d places remaining.', $availablePlaces)
            );
        }

        // Calculer le prix total
        $unitPrice = (float) $formation->getPrice();
        $totalPrice = $unitPrice * $quantity;
        $ticket->setTotalPrice((string) number_format($totalPrice, 2, '.', ''));

        // Incrémenter les participants actuels
        $formation->setCurrentParticipants($formation->getCurrentParticipants() + $quantity);
        $this->entityManager->persist($formation);
    }
}
