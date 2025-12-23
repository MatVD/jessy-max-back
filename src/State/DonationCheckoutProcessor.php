<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Donation;
use App\Enum\PaymentStatus;
use Stripe\Checkout\Session;
use Stripe\Stripe;

readonly class DonationCheckoutProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $decorated,
        private string $stripeSecretKey,
        private string $frontendUrl
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Donation) {
            $session = Session::create([
                'mode' => 'payment',
                'payment_method_types' => ['card', 'paypal'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => ['name' => 'Don - Jessy Max'],
                        'unit_amount' => (int)($data->getAmount() * 100),
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $this->frontendUrl . '/dons/success',
                'customer_email' => $data->getDonorEmail(),
                'metadata' => ['donation_id' => $data->getId()->toRfc4122()],
            ]);

            $data->setStripeSessionId($session->id);
            $data->setStripeCheckoutUrl($session->url);
            $data->setStatus(PaymentStatus::PENDING->value);
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}