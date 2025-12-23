<?php

namespace App\Command;

use App\Entity\Donation;
use App\Enum\PaymentStatus;
use App\Service\DonationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:test-donation-email',
    description: 'Teste l\'envoi d\'email de confirmation de don',
)]
class TestDonationEmailCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DonationEmailService $donationEmailService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('donation-id', 'd', InputOption::VALUE_OPTIONAL, 'UUID du don à utiliser')
            ->addOption('email', 'm', InputOption::VALUE_OPTIONAL, 'Email de destination (override)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $donationId = $input->getOption('donation-id');

        if ($donationId) {
            // Nettoyer l'UUID (enlever 0x et formater avec tirets)
            $donationId = str_replace('0x', '', $donationId);
            if (strlen($donationId) === 32 && !str_contains($donationId, '-')) {
                // Formater l'UUID: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
                $donationId = substr($donationId, 0, 8) . '-' .
                    substr($donationId, 8, 4) . '-' .
                    substr($donationId, 12, 4) . '-' .
                    substr($donationId, 16, 4) . '-' .
                    substr($donationId, 20);
            }

            // Récupérer un don spécifique
            $donation = $this->entityManager->getRepository(Donation::class)
                ->find(Uuid::fromString($donationId));

            if (!$donation) {
                $io->error("Don #{$donationId} introuvable");
                return Command::FAILURE;
            }
        } else {
            // Récupérer le dernier don payé
            $donation = $this->entityManager->getRepository(Donation::class)
                ->createQueryBuilder('d')
                ->where('d.status = :status')
                ->setParameter('status', PaymentStatus::PAID)
                ->orderBy('d.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$donation) {
                $io->error('Aucun don payé trouvé dans la base de données');
                $io->note('Créez d\'abord un don ou spécifiez un UUID avec --donation-id');
                return Command::FAILURE;
            }
        }

        // Override de l'email si spécifié
        $emailOverride = $input->getOption('email');
        $originalEmail = $donation->getDonorEmail();

        if ($emailOverride) {
            $donation->setDonorEmail($emailOverride);
            $io->warning("Email overridé: {$originalEmail} → {$emailOverride}");
        }

        $io->section('📧 Envoi d\'email de test');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['ID', $donation->getId()->toRfc4122()],
                ['Donateur', $donation->getDonorName()],
                ['Email', $donation->getDonorEmail()],
                ['Montant', $donation->getAmount() . ' €'],
                ['Statut', $donation->getStatus()->value],
                ['Message', $donation->getMessage() ?? 'N/A'],
                ['Date', $donation->getCreatedAt()->format('d/m/Y à H:i')],
            ]
        );

        if (!$io->confirm('Envoyer l\'email ?', true)) {
            return Command::SUCCESS;
        }

        try {
            $this->donationEmailService->sendDonationConfirmation($donation);
            $io->success('✅ Email envoyé avec succès !');

            // Restaurer l'email original
            if ($emailOverride) {
                $donation->setDonorEmail($originalEmail);
                $this->entityManager->flush();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'envoi: ' . $e->getMessage());
            $io->note($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
