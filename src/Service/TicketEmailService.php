<?php

namespace App\Service;

use App\Entity\Ticket;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service pour envoyer les tickets par email avec QR code
 * 
 * Installation requise : composer require symfony/mailer
 */
class TicketEmailService
{
    private string $frontendUrl;
    private string $fromEmail;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly QrCodeService $qrCodeService,
    ) {
        $this->frontendUrl = $_ENV['FRONTEND_URL'] ?? 'https://jessyjoycemaxwellmyles.com';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'contact@jessyjoycemaxwellmyles.com';
    }

    /**
     * Envoie le ticket par email avec le QR code en pièce jointe
     */
    public function sendTicketEmail(Ticket $ticket): void
    {
        $eventOrFormation = $ticket->getEvent() ?? $ticket->getFormation();

        if (!$eventOrFormation) {
            throw new \LogicException('Le ticket doit être lié à un événement ou une formation');
        }

        // Générer l'image PNG du QR code
        $qrCodePng = $this->qrCodeService->generateQrCodePng($ticket->getQrCode());

        // Créer l'email
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($ticket->getCustomerEmail())
            ->subject('Votre ticket pour ' . $eventOrFormation->getTitle())
            ->html($this->getEmailTemplate($ticket, $eventOrFormation))
            ->attach($qrCodePng, 'ticket-qr-code.png', 'image/png');

        $this->mailer->send($email);
    }

    /**
     * Template HTML de l'email
     */
    private function getEmailTemplate(Ticket $ticket, $eventOrFormation): string
    {
        $isEvent = $ticket->getEvent() !== null;
        $type = $isEvent ? 'événement' : 'formation';
        $date = $isEvent
            ? $ticket->getEvent()->getDate()->format('d/m/Y à H:i')
            : $ticket->getFormation()->getStartDate()->format('d/m/Y');

        $qrCodeDataUri = $this->qrCodeService->generateQrCodeImage($ticket->getQrCode());

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .ticket-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            color: #667eea;
        }
        .qr-code {
            text-align: center;
            margin: 30px 0;
        }
        .qr-code img {
            border: 4px solid #667eea;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="content">
        <p>Bonjour <strong>{$ticket->getCustomerName()}</strong>,</p>
        
        <p>Merci pour votre achat ! Voici votre ticket pour :</p>
        
        <div class="ticket-details">
            <div class="detail-row">
                <span class="detail-label">{$type} :</span>
                <span>{$eventOrFormation->getTitle()}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date :</span>
                <span>{$date}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Prix :</span>
                <span>{$ticket->getPrice()} €</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Référence :</span>
                <span>#{$ticket->getId()->toRfc4122()}</span>
            </div>
        </div>
        
        <div class="qr-code">
            <h3>Votre QR Code</h3>
            <img src="{$qrCodeDataUri}" alt="QR Code" />
            <p style="font-size: 14px; color: #666;">
                Présentez ce QR code à l'entrée de l'{$type}
            </p>
        </div>
        
        <p style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
            <strong>⚠️ Important :</strong> Conservez ce QR code précieusement. 
            Vous en aurez besoin pour accéder à l'{$type}.
        </p>
        
        <div style="text-align: center;">
            <a href="{$this->frontendUrl}/mon-ticket/{$ticket->getId()->toRfc4122()}" class="button">
                Voir mon ticket en ligne
            </a>
        </div>
        
        <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
        
        <p>À très bientôt ! 👋</p>
    </div>
    
    <div class="footer">
        <p>© JessyMax - Tous droits réservés</p>
        <p>Cet email a été envoyé à {$ticket->getCustomerEmail()}</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Envoie un email de rappel avant l'événement
     */
    public function sendReminderEmail(Ticket $ticket): void
    {
        $eventOrFormation = $ticket->getEvent() ?? $ticket->getFormation();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($ticket->getCustomerEmail())
            ->subject('Rappel : ' . $eventOrFormation->getTitle() . ' demain !')
            ->html($this->getReminderTemplate($ticket, $eventOrFormation));

        $this->mailer->send($email);
    }

    /**
     * Template HTML de l'email de rappel
     */
    private function getReminderTemplate(Ticket $ticket, $eventOrFormation): string
    {
        $isEvent = $ticket->getEvent() !== null;
        $type = $isEvent ? 'événement' : 'formation';
        $date = $isEvent
            ? $ticket->getEvent()->getDate()->format('d/m/Y à H:i')
            : $ticket->getFormation()->getStartDate()->format('d/m/Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto;">
    <h2>⏰ Rappel : C'est bientôt !</h2>
    
    <p>Bonjour {$ticket->getCustomerName()},</p>
    
    <p>Nous vous rappelons que <strong>{$eventOrFormation->getTitle()}</strong> a lieu demain :</p>
    
    <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p><strong>📅 Date :</strong> {$date}</p>
    </div>
    
    <p>N'oubliez pas d'apporter votre QR code !</p>
    
    <p>À demain ! 🎉</p>
</body>
</html>
HTML;
    }
}
