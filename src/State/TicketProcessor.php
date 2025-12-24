<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TicketProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Ticket) {
            // Validation après désérialisation
            if (($data->getEvent() === null && $data->getFormation() === null) ||
                ($data->getEvent() !== null && $data->getFormation() !== null)
            ) {
                throw new \InvalidArgumentException('Un ticket doit être lié soit à un événement, soit à une formation.');
            }

            $currentUser = $this->security->getUser();

            if ($data->getUser() !== null && $currentUser !== null && $data->getUser() !== $currentUser && !$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedException('Impossible de créer ou modifier un ticket pour un autre utilisateur.');
            }

            if ($data->getUser() === null && $currentUser !== null) {
                $data->setUser($currentUser);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
