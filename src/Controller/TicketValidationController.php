<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/tickets', name: 'api_tickets_')]
class TicketValidationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly QrCodeService $qrCodeService
    ) {}

    /**
     * Valide un ticket via QR code
     * Seulement accessible aux validateurs de tickets
     */
    #[Route('/validate', name: 'validate', methods: ['POST'])]
    public function validateTicket(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_TICKET_VALIDATOR');
        
        $data = json_decode($request->getContent(), true);
        $qrCode = $data['qrCode'] ?? null;

        if (!$qrCode) {
            return $this->json([
                'error' => 'QR code manquant'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier la validité du JWT
        try {
            $qrData = $this->qrCodeService->validateQrCode($qrCode);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => 'QR code invalide ou expiré',
                'message' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer le ticket
        $ticket = $this->entityManager->getRepository(Ticket::class)->find(Uuid::fromString($qrData['ticket_id']));

        if (!$ticket) {
            return $this->json([
                'error' => 'Ticket introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le ticket n'a pas déjà été utilisé
        if ($ticket->isUsed()) {
            return $this->json([
                'error' => 'Ce ticket a déjà été utilisé',
                'usedAt' => $ticket->getUsedAt()?->format('Y-m-d H:i:s')
            ], Response::HTTP_CONFLICT);
        }

        // Vérifier le statut de paiement
        if ($ticket->getPaymentStatus() !== \App\Enum\PaymentStatus::PAID) {
            return $this->json([
                'error' => 'Ce ticket n\'est pas payé',
                'status' => $ticket->getPaymentStatus()->value
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        // Marquer le ticket comme utilisé
        $ticket->markAsUsed();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Ticket validé avec succès',
            'ticket' => [
                'id' => $ticket->getId()->toRfc4122(),
                'customerName' => $ticket->getCustomerName(),
                'customerEmail' => $ticket->getCustomerEmail(),
                'eventTitle' => $ticket->getEvent()?->getTitle() ?? $ticket->getFormation()?->getTitle(),
                'usedAt' => $ticket->getUsedAt()?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Vérifie un QR code sans le marquer comme utilisé
     * Seulement accessible aux admins
     */
    #[Route('/check', name: 'check', methods: ['POST'])]
    public function checkTicket(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        $qrCode = $data['qrCode'] ?? null;

        if (!$qrCode) {
            return $this->json([
                'error' => 'QR code manquant'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier la validité du JWT
        try {
            $qrData = $this->qrCodeService->validateQrCode($qrCode);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'valid' => false,
                'error' => 'QR code invalide ou expiré'
            ]);
        }

        // Récupérer le ticket
        $ticket = $this->entityManager->getRepository(Ticket::class)->find(Uuid::fromString($qrData['ticket_id']));

        if (!$ticket) {
            return $this->json([
                'valid' => false,
                'error' => 'Ticket introuvable'
            ]);
        }

        return $this->json([
            'valid' => true,
            'ticket' => [
                'id' => $ticket->getId()->toRfc4122(),
                'customerName' => $ticket->getCustomerName(),
                'eventTitle' => $ticket->getEvent()?->getTitle() ?? $ticket->getFormation()?->getTitle(),
                'isUsed' => $ticket->isUsed(),
                'usedAt' => $ticket->getUsedAt()?->format('Y-m-d H:i:s'),
                'paymentStatus' => $ticket->getPaymentStatus()->value
            ]
        ]);
    }

    /**
     * Liste tous les tickets d'un événement
     */
    #[Route('/event/{eventId}', name: 'list_by_event', methods: ['GET'])]
    public function listByEvent(string $eventId): JsonResponse
    {
        $tickets = $this->entityManager->getRepository(Ticket::class)->findPaidByEvent($eventId);

        return $this->json([
            'total' => count($tickets),
            'tickets' => array_map(fn(Ticket $t) => [
                'id' => $t->getId()->toRfc4122(),
                'customerName' => $t->getCustomerName(),
                'customerEmail' => $t->getCustomerEmail(),
                'totalPrice' => $t->getTotalPrice(),
                'purchasedAt' => $t->getPurchasedAt()?->format('Y-m-d H:i:s'),
                'isUsed' => $t->isUsed(),
                'usedAt' => $t->getUsedAt()?->format('Y-m-d H:i:s')
            ], $tickets)
        ]);
    }

    /**
     * Statistiques d'un événement
     */
    #[Route('/event/{eventId}/stats', name: 'event_stats', methods: ['GET'])]
    public function eventStats(string $eventId): JsonResponse
    {
        $tickets = $this->entityManager->getRepository(Ticket::class)->findPaidByEvent($eventId);
        $usedTickets = array_filter($tickets, fn(Ticket $t) => $t->isUsed());

        $totalRevenue = array_reduce(
            $tickets, 
            fn($sum, Ticket $t) => $sum + (float) $t->getTotalPrice(), 
            0
        );

        return $this->json([
            'totalSold' => count($tickets),
            'totalUsed' => count($usedTickets),
            'remainingToUse' => count($tickets) - count($usedTickets),
            'totalRevenue' => number_format($totalRevenue, 2),
            'averageTicketPrice' => count($tickets) > 0 
                ? number_format($totalRevenue / count($tickets), 2) 
                : 0
        ]);
    }
}