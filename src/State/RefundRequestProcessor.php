<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\RefundRequest;
use App\Repository\RefundRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Enum\PaymentStatus;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Processor pour gérer la création de demandes de remboursement
 */
class RefundRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RefundRequestRepository $refundRequestRepository,
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
    ) {}

    /**
     * @param RefundRequest $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RefundRequest
    {
        // Vérifier qu'il n'y a pas déjà une demande en attente ou approuvée pour ce ticket
        if ($data->getTicket()) {
            $ticketId = (string) $data->getTicket()->getId();

            if ($this->refundRequestRepository->hasPendingOrApprovedRefund($ticketId)) {
                throw new BadRequestHttpException(
                    'A refund request for this ticket is already pending or has been approved.'
                );
            }

            // Vérifier que le ticket n'est pas déjà remboursé
            if ($data->getTicket()->getPaymentStatus() === PaymentStatus::REFUNDED) {
                throw new BadRequestHttpException('This ticket has already been refunded.');
            }

            $currentUser = $this->security->getUser();
            if ($currentUser && !$this->security->isGranted('ROLE_ADMIN') && $data->getTicket()->getUser() !== $currentUser) {
                throw new AccessDeniedHttpException('You can only request a refund for your own ticket.');
            }

            if ($currentUser && $data->getUser() === null) {
                $data->setUser($currentUser);
            }
        }

        // Utiliser le processor par défaut pour persister
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
