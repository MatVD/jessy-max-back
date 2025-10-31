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
    private const ALGORITHM = 'HS256';
    
    public function __construct(
        private readonly string $jwtSecret
    ) {}

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
            'event_id' => $ticket->getEvent()?->getId()->toRfc4122(),
            'formation_id' => $ticket->getFormation()?->getId()->toRfc4122(),
            'customer_email' => $ticket->getCustomerEmail(),
            'customer_name' => $ticket->getCustomerName(),
            'total_price' => $ticket->getTotalPrice(),
            'iat' => time(),
            'exp' => $expirationDate->getTimestamp(),
        ];

        // Encoder en JWT
        return JWT::encode($payload, $this->jwtSecret, self::ALGORITHM);
    }

    /**
     * Valide un QR code et retourne les données décodées
     */
    public function validateQrCode(string $qrCode): array
    {
        try {
            $decoded = JWT::decode($qrCode, new Key($this->jwtSecret, self::ALGORITHM));
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
     * 
     * Installation requise : composer require endroid/qr-code
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
     * 
     * Installation requise : composer require endroid/qr-code
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