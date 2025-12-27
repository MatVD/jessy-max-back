<?php

namespace App\Service;

use App\Entity\Ticket;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service pour envoyer les tickets par email avec QR code
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

        $qrCodePng = $this->qrCodeService->generateQrCodePng($ticket->getQrCode());

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($ticket->getCustomerEmail())
            ->subject('Votre ticket pour ' . $eventOrFormation->getTitle())
            ->html($this->getEmailTemplate($ticket, $eventOrFormation))
            ->attach($qrCodePng, 'ticket-qr-code.png', 'image/png');

        $this->mailer->send($email);
    }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; background-color: #121212; font-family: 'Inter', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #121212; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #1a1a1a; border-radius: 12px; border: 1px solid rgba(154, 143, 136, 0.2);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px 40px; border-bottom: 1px solid rgba(154, 143, 136, 0.2);">
                            <h1 style="margin: 0; font-family: 'Playfair Display', Georgia, serif; font-size: 28px; color: #F8F8F8; font-weight: 700;">
                                JessyMax
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #F8F8F8; font-size: 16px; line-height: 1.6;">
                                Bonjour <strong>{$ticket->getCustomerName()}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 30px; color: rgba(248, 248, 248, 0.7); font-size: 15px; line-height: 1.6;">
                                Merci pour votre achat ! Voici votre ticket pour :
                            </p>
                            
                            <!-- Ticket Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #121212; border-radius: 8px; border: 1px solid rgba(154, 143, 136, 0.3); margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid rgba(154, 143, 136, 0.2);">
                                                    <span style="color: #C13E3E; font-weight: 600; font-size: 14px;">{$type} :</span>
                                                    <span style="color: #F8F8F8; float: right; font-size: 14px;">{$eventOrFormation->getTitle()}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid rgba(154, 143, 136, 0.2);">
                                                    <span style="color: #C13E3E; font-weight: 600; font-size: 14px;">Date :</span>
                                                    <span style="color: #F8F8F8; float: right; font-size: 14px;">{$date}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid rgba(154, 143, 136, 0.2);">
                                                    <span style="color: #C13E3E; font-weight: 600; font-size: 14px;">Prix :</span>
                                                    <span style="color: #F8F8F8; float: right; font-size: 14px;">{$ticket->getPrice()} €</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0;">
                                                    <span style="color: #C13E3E; font-weight: 600; font-size: 14px;">Référence :</span>
                                                    <span style="color: rgba(248, 248, 248, 0.6); float: right; font-size: 13px;">#{$ticket->getId()->toRfc4122()}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- QR Code -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <h3 style="margin: 0 0 15px; font-family: 'Playfair Display', Georgia, serif; font-size: 20px; color: #F8F8F8;">
                                            Votre QR Code
                                        </h3>
                                        <div style="background: #F8F8F8; padding: 15px; border-radius: 8px; display: inline-block; border: 3px solid #C13E3E;">
                                            <img src="{$qrCodeDataUri}" alt="QR Code" style="display: block; width: 180px; height: 180px;" />
                                        </div>
                                        <p style="margin: 15px 0 0; color: #9A8F88; font-size: 13px;">
                                            Présentez ce QR code à l'entrée de l'{$type}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Warning -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: rgba(193, 62, 62, 0.1); border-radius: 8px; border-left: 4px solid #C13E3E; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <p style="margin: 0; color: #F8F8F8; font-size: 14px; line-height: 1.5;">
                                            <strong>⚠️ Important :</strong> Conservez ce QR code précieusement. Vous en aurez besoin pour accéder à l'{$type}.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{$this->frontendUrl}/mon-ticket/{$ticket->getId()->toRfc4122()}" 
                                           style="display: inline-block; background-color: #C13E3E; color: #F8F8F8; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                                            Voir mon ticket en ligne
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 15px; color: rgba(248, 248, 248, 0.7); font-size: 14px; line-height: 1.6;">
                                Si vous avez des questions, n'hésitez pas à nous contacter.
                            </p>
                            
                            <p style="margin: 0; color: #F8F8F8; font-size: 15px;">
                                À très bientôt ! 👋
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 25px 40px; border-top: 1px solid rgba(154, 143, 136, 0.2); text-align: center;">
                            <p style="margin: 0 0 8px; color: #9A8F88; font-size: 12px;">
                                © JessyMax - Tous droits réservés
                            </p>
                            <p style="margin: 0; color: rgba(154, 143, 136, 0.6); font-size: 11px;">
                                Cet email a été envoyé à {$ticket->getCustomerEmail()}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
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
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; background-color: #121212; font-family: 'Inter', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #121212; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #1a1a1a; border-radius: 12px; border: 1px solid rgba(154, 143, 136, 0.2);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px 40px; border-bottom: 1px solid rgba(154, 143, 136, 0.2);">
                            <h1 style="margin: 0; font-family: 'Playfair Display', Georgia, serif; font-size: 28px; color: #F8F8F8; font-weight: 700;">
                                JessyMax
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 25px; font-family: 'Playfair Display', Georgia, serif; font-size: 24px; color: #F8F8F8;">
                                ⏰ Rappel : C'est bientôt !
                            </h2>
                            
                            <p style="margin: 0 0 20px; color: #F8F8F8; font-size: 16px; line-height: 1.6;">
                                Bonjour <strong>{$ticket->getCustomerName()}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 25px; color: rgba(248, 248, 248, 0.7); font-size: 15px; line-height: 1.6;">
                                Nous vous rappelons que <strong style="color: #C13E3E;">{$eventOrFormation->getTitle()}</strong> a lieu demain :
                            </p>
                            
                            <!-- Date Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #121212; border-radius: 8px; border: 1px solid rgba(154, 143, 136, 0.3); margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #F8F8F8; font-size: 15px;">
                                            <span style="color: #C13E3E; font-weight: 600;">📅 Date :</span> {$date}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 20px; color: rgba(248, 248, 248, 0.7); font-size: 14px; line-height: 1.6;">
                                N'oubliez pas d'apporter votre QR code !
                            </p>
                            
                            <p style="margin: 0; color: #F8F8F8; font-size: 16px;">
                                À demain ! 🎉
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 25px 40px; border-top: 1px solid rgba(154, 143, 136, 0.2); text-align: center;">
                            <p style="margin: 0; color: #9A8F88; font-size: 12px;">
                                © JessyMax - Tous droits réservés
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}