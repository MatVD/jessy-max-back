<?php

namespace App\Controller;

use App\Entity\Ticket;
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
        $image = $this->qrCodeService->generateQrCodeImage($ticket->getQrCode());
        
        // Retourner l'image PNG
        $imageData = base64_decode(explode(',', $image)[1]);
        
        return new Response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="ticket-qr.png"'
        ]);
    }
}