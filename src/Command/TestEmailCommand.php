<?php

namespace App\Command;

use App\Entity\Ticket;
use App\Enum\PaymentStatus;
use App\Service\TicketEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:test-email',
    description: 'Teste l\'envoi d\'email de confirmation de ticket',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TicketEmailService $ticketEmailService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('ticket-id', 't', InputOption::VALUE_OPTIONAL, 'UUID du ticket à utiliser')
            ->addOption('email', 'm', InputOption::VALUE_OPTIONAL, 'Email de destination (override)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ticketId = $input->getOption('ticket-id');

        if ($ticketId) {
            // Récupérer un ticket spécifique
            $ticket = $this->entityManager->getRepository(Ticket::class)
                ->find(Uuid::fromString($ticketId));
            
            if (!$ticket) {
                $io->error("Ticket #{$ticketId} introuvable");
                return Command::FAILURE;
            }
        } else {
            // Récupérer le dernier ticket payé
            $ticket = $this->entityManager->getRepository(Ticket::class)
                ->createQueryBuilder('t')
                ->where('t.paymentStatus = :status')
                ->setParameter('status', PaymentStatus::PAID)
                ->orderBy('t.purchasedAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$ticket) {
                $io->error('Aucun ticket payé trouvé dans la base de données');
                $io->note('Créez d\'abord un ticket ou spécifiez un UUID avec --ticket-id');
                return Command::FAILURE;
            }
        }

        // Override de l'email si spécifié
        $emailOverride = $input->getOption('email');
        $originalEmail = $ticket->getCustomerEmail();
        
        if ($emailOverride) {
            $ticket->setCustomerEmail($emailOverride);
            $io->warning("Email overridé: {$originalEmail} → {$emailOverride}");
        }

        $io->section('📧 Envoi d\'email de test');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['ID', $ticket->getId()->toRfc4122()],
                ['Client', $ticket->getCustomerName()],
                ['Email', $ticket->getCustomerEmail()],
                ['Prix', $ticket->getPrice() . ' €'],
                ['Statut', $ticket->getPaymentStatus()->value],
                ['Événement', $ticket->getEvent()?->getTitle() ?? $ticket->getFormation()?->getTitle() ?? 'N/A'],
            ]
        );

        if (!$io->confirm('Envoyer l\'email ?', true)) {
            return Command::SUCCESS;
        }

        try {
            $this->ticketEmailService->sendTicketEmail($ticket);
            $io->success('✅ Email envoyé avec succès !');
            
            // Restaurer l'email original
            if ($emailOverride) {
                $ticket->setCustomerEmail($originalEmail);
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'envoi: ' . $e->getMessage());
            $io->note($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
