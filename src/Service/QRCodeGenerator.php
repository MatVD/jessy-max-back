<?php

namespace App\Service;

use App\Repository\TicketRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeGenerator
{
    public function __construct(
        private readonly TicketRepository $ticketRepository
    ) {
    }

    /**
     * Génère un code QR unique pour un ticket
     * Format: TICKET-{timestamp}-{random}
     */
    public function generateUniqueTicketCode(): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $timestamp = time();
            $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $code = sprintf('TICKET-%d-%s', $timestamp, $random);
            
            $attempt++;
            
            // Vérifier l'unicité
            if (!$this->ticketRepository->qrCodeExists($code)) {
                return $code;
            }
            
            // Petit délai pour éviter les collisions sur le timestamp
            usleep(100000); // 100ms
            
        } while ($attempt < $maxAttempts);

        throw new \RuntimeException('Unable to generate a unique QR code after ' . $maxAttempts . ' attempts.');
    }

    /**
     * Génère l'image QR code au format Data URI
     */
    public function generateQRCodeImage(string $data): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getDataUri();
    }

    /**
     * Génère et sauvegarde l'image QR code dans un fichier
     */
    public function saveQRCodeToFile(string $data, string $filepath): void
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        $result->saveToFile($filepath);
    }
}
