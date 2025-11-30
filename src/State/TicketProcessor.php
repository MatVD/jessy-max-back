<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;

class TicketProcessor implements ProcessorInterface
{
    public function __construct(private ProcessorInterface $persistProcessor)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Ticket) {
            // Validation après désérialisation
            if (($data->getEvent() === null && $data->getFormation() === null) ||
                ($data->getEvent() !== null && $data->getFormation() !== null)
            ) {
                throw new \InvalidArgumentException('Un ticket doit être lié soit à un événement, soit à une formation.');
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}