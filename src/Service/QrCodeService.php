<?php

namespace App\Service;

use App\Entity\Ticket;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Service pour générer et valider des QR codes sécurisés avec JWT
 * 
 * Installation requise : 
 * - composer require firebase/php-jwt
 * - composer require endroid/qr-code
 */
class QrCodeService
{
    /**
     * Génère un QR code sécurisé pour un ticket (JWT signé)
     */
    public function generateQrCode(Ticket $ticket): string
    {
        $eventOrFormation = $ticket->getEvent() ?? $ticket->getFormation();

        if (!$eventOrFormation) {
            throw new \LogicException('Le ticket doit être lié à un événement ou une formation');
        }

        // Déterminer la date d'expiration (date de l'événement + 1 jour)
        $eventDate = $ticket->getEvent()
            ? $ticket->getEvent()->getDate()
            : $ticket->getFormation()->getStartDate();

        $expirationDate = $eventDate->modify('+1 day');

        // Payload JWT
        $payload = [
            'ticket_id' => $ticket->getId()->toRfc4122(),
            'customer_email' => $ticket->getCustomerEmail(),
            'exp' => $expirationDate->getTimestamp(),
        ];

        // Créer le token JWT avec la clé secrète spécifique aux QR codes
        $secretKey = $_ENV['JWT_QRCODE_SECRET'] ?? throw new \RuntimeException('JWT_QRCODE_SECRET not configured');
        return JWT::encode($payload, $secretKey, 'HS256');
    }

    /**
     * Valide un QR code et retourne les données décodées
     */
    public function validateQrCode(string $qrCode): array
    {
        try {
            $secretKey = $_ENV['JWT_QRCODE_SECRET'] ?? throw new \RuntimeException('JWT_QRCODE_SECRET not configured');
            $decoded = JWT::decode($qrCode, new Key($secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('QR code invalide ou expiré: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si un QR code est valide et non expiré
     */
    public function isValid(string $qrCode): bool
    {
        try {
            $this->validateQrCode($qrCode);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Génère une image PNG du QR code en base64 data URI
     * Utile pour affichage direct dans HTML
     * 
     * Installation requise : composer require endroid/qr-code
     */
    public function generateQrCodeImage(string $qrCodeData): string
    {
        $qrCode = new QrCode(
            data: $qrCodeData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }

    /**
     * Génère les données PNG brutes du QR code
     * Utile pour pièce jointe email ou téléchargement
     */
    public function generateQrCodePng(string $qrCodeData): string
    {
        $qrCode = new QrCode(
            data: $qrCodeData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getString();
    }

    /**
     * Génère un QR code SVG (vectoriel, plus léger)
     */
    public function generateQrCodeSvg(string $qrCodeData): string
    {
        $qrCode = new QrCode(
            data: $qrCodeData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10
        );

        $writer = new \Endroid\QrCode\Writer\SvgWriter();
        $result = $writer->write($qrCode);

        return $result->getString();
    }

    /**
     * Sauvegarde le QR code dans un fichier
     */
    public function saveQrCodeToFile(string $qrCodeData, string $filePath): void
    {
        $qrCode = new QrCode(
            data: $qrCodeData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $result->saveToFile($filePath);
    }
}
