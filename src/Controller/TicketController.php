<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Enum\PaymentStatus;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TicketController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QrCodeService $qrCodeService
    ) {}

    #[Route('/api/tickets/{id}/qr.png', methods: ['GET'])]
    public function downloadQr(Ticket $ticket): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isGranted('ROLE_ADMIN') && $ticket->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez télécharger que vos propres tickets.');
        }

        if ($ticket->getPaymentStatus() !== PaymentStatus::PAID) {
            return new Response('Ticket non payé', Response::HTTP_PAYMENT_REQUIRED);
        }

        if (!$ticket->getQrCode()) {
            return new Response('QR code indisponible', Response::HTTP_BAD_REQUEST);
        }

        $image = $this->qrCodeService->generateQrCodeImage($ticket->getQrCode());

        // Retourner l'image PNG
        $imageData = base64_decode(explode(',', $image)[1]);

        return new Response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="ticket-qr.png"'
        ]);
    }
}
