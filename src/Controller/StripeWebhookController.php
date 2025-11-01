<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Enum\PaymentStatus;
use App\Repository\TicketRepository;
use App\Service\QrCodeService;
use App\Service\TicketEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly string $stripeWebhookSecret,
        private readonly EntityManagerInterface $entityManager,
        private readonly QrCodeService $qrCodeService,
        private readonly TicketEmailService $ticketEmailService,
        private readonly LoggerInterface $logger
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        // En environnement de test, on désactive la vérification de signature
        if ($_ENV['APP_ENV'] === 'test') {
            $event = json_decode($payload);
        } else {
            try {
                $event = Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $this->stripeWebhookSecret
                );
            } catch (\UnexpectedValueException $e) {
                $this->logger->error('Stripe webhook: Invalid payload', ['error' => $e->getMessage()]);
                return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
            } catch (SignatureVerificationException $e) {
                $this->logger->error('Stripe webhook: Invalid signature', ['error' => $e->getMessage()]);
                return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
            }
        }

        // Gérer les différents événements
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            'charge.refunded' => $this->handleChargeRefunded($event->data->object),
            default => $this->logger->info('Unhandled Stripe event', ['type' => $event->type])
        };

        return new Response(json_encode(['status' => 'success']), Response::HTTP_OK);
    }

    private function handleCheckoutCompleted($session): void
    {
        $ticketId = $session->metadata->ticket_id ?? null;

        if (!$ticketId) {
            $this->logger->error('Stripe webhook: Missing ticket_id in metadata');
            return;
        }

        $ticket = $this->entityManager->getRepository(Ticket::class)->find(Uuid::fromString($ticketId));

        if (!$ticket) {
            $this->logger->error('Stripe webhook: Ticket not found', ['ticket_id' => $ticketId]);
            return;
        }

        // Mettre à jour le ticket
        $ticket->setPaymentStatus(PaymentStatus::PAID);
        $ticket->setPurchasedAt(new \DateTimeImmutable());
        $ticket->setStripePaymentIntentId($session->payment_intent);

        // Générer le QR code sécurisé avec JWT
        $qrCode = $this->qrCodeService->generateQrCode($ticket);
        $ticket->setQrCode($qrCode);

        // Décrémenter les places disponibles
        if ($event = $ticket->getEvent()) {
            $event->setAvailableTickets($event->getAvailableTickets() - 1);
        }

        $this->entityManager->flush();

        // Envoyer l'email avec le ticket et QR code
        try {
            $this->ticketEmailService->sendTicketEmail($ticket);
            $this->logger->info('Stripe webhook: Email sent', ['ticket_id' => $ticketId]);
        } catch (\Exception $e) {
            $this->logger->error('Stripe webhook: Failed to send email', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }

        $this->logger->info('Stripe webhook: Payment completed', ['ticket_id' => $ticketId]);
    }

    private function handlePaymentFailed($paymentIntent): void
    {
        // Trouver le ticket via payment_intent
        $ticket = $this->entityManager->getRepository(Ticket::class)->findOneBy([
            'stripePaymentIntentId' => $paymentIntent->id
        ]);

        if ($ticket) {
            $ticket->setPaymentStatus(PaymentStatus::FAILED);
            $this->entityManager->flush();

            $this->logger->info('Stripe webhook: Payment failed', [
                'ticket_id' => $ticket->getId()->toRfc4122()
            ]);
        }
    }

    private function handleChargeRefunded($charge): void
    {
        // Logique de remboursement
        $this->logger->info('Stripe webhook: Charge refunded', ['charge_id' => $charge->id]);

        // TODO: Mettre à jour le ticket et la RefundRequest
    }
}
