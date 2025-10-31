<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * State Processor pour créer une Checkout Session Stripe lors de la création d'un ticket
 */
final readonly class TicketCheckoutProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $decorated,
        private EntityManagerInterface $entityManager,
        private string $stripeSecretKey,
        private string $frontendUrl
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Si c'est une création de ticket
        if ($data instanceof Ticket && $operation instanceof \ApiPlatform\Metadata\Post) {
            
            // Déterminer le produit (Event ou Formation)
            $product = $data->getEvent() ?? $data->getFormation();
            $productType = $data->getEvent() ? 'event' : 'formation';
            
            if (!$product) {
                throw new \LogicException('Le ticket doit être lié à un événement ou une formation');
            }

            // Créer la Checkout Session Stripe
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $product->getTitle(),
                            'description' => substr($product->getDescription(), 0, 200),
                        ],
                        'unit_amount' => (int)($data->getTotalPrice() * 100), // Stripe utilise les centimes
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->frontendUrl . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->frontendUrl . '/payment/cancel',
                'customer_email' => $data->getCustomerEmail(),
                'metadata' => [
                    'ticket_id' => $data->getId()->toRfc4122(),
                    'product_type' => $productType,
                    'product_id' => $product->getId()->toRfc4122(),
                ],
            ]);

            // Stocker l'ID de la session
            $data->setStripeCheckoutSessionId($checkoutSession->id);
            $data->setPaymentStatus(PaymentStatus::PENDING);
        }

        // Appeler le processor par défaut pour persister
        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}