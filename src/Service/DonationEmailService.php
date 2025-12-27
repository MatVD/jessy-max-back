<?php

namespace App\Service;

use App\Entity\Donation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service pour envoyer les emails de confirmation de don
 */
class DonationEmailService
{
    private string $frontendUrl;
    private string $fromEmail;

    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
        $this->frontendUrl = $_ENV['FRONTEND_URL'] ?? 'https://jessyjoycemaxwellmyles.com';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'contact@jessyjoycemaxwellmyles.com';
    }

    /**
     * Envoie un email de confirmation de don
     */
    public function sendDonationConfirmation(Donation $donation): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($donation->getDonorEmail())
            ->subject('Merci pour votre don !')
            ->html($this->getEmailTemplate($donation));

        $this->mailer->send($email);
    }

    private function getEmailTemplate(Donation $donation): string
    {
        $amount = number_format((float)$donation->getAmount(), 2, ',', ' ');
        $date = $donation->getCreatedAt()->format('d/m/Y à H:i');
        $message = $donation->getMessage();

        $messageSection = $message ? <<<HTML
                            <!-- Message du donateur -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #121212; border-radius: 8px; border-left: 4px solid #C13E3E; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <p style="margin: 0 0 8px; color: #C13E3E; font-weight: 600; font-size: 14px;">Votre message :</p>
                                        <p style="margin: 0; color: rgba(248, 248, 248, 0.8); font-size: 14px; font-style: italic; line-height: 1.5;">
                                            "{$message}"
                                        </p>
                                    </td>
                                </tr>
                            </table>
HTML : '';

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
                                Bonjour <strong>{$donation->getDonorName()}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 25px; color: rgba(248, 248, 248, 0.7); font-size: 15px; line-height: 1.6;">
                                Nous avons bien reçu votre don et nous vous en remercions chaleureusement !
                            </p>
                            
                            <!-- Amount Highlight -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #C13E3E; border-radius: 8px; margin-bottom: 25px;">
                                <tr>
                                    <td align="center" style="padding: 25px;">
                                        <p style="margin: 0; color: #F8F8F8; font-size: 32px; font-weight: 700; font-family: 'Playfair Display', Georgia, serif;">
                                            {$amount} €
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 25px; color: rgba(248, 248, 248, 0.7); font-size: 15px; line-height: 1.6;">
                                Votre générosité nous aide à continuer notre mission et à faire une réelle différence. Grâce à des personnes comme vous, nous pouvons poursuivre nos actions et toucher encore plus de personnes.
                            </p>
                            
                            <!-- Donation Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #121212; border-radius: 8px; border: 1px solid rgba(154, 143, 136, 0.3); margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid rgba(154, 143, 136, 0.2);">
                                                    <span style="color: #C13E3E; font-weight: 600; font-size: 14px;">Date :</span>
                                                    <span style="color: #F8F8F8; float: right; font-size: 14px;">{$date}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0; border-bottom: 1px solid rgba(154, 143, 136, 0.2);">
                                                    <span style="color: #C13E3E; font-weight: 600; font-size: 14px;">Montant :</span>
                                                    <span style="color: #F8F8F8; float: right; font-size: 14px;">{$amount} €</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px 0;">
                                                    <span style="color: #C13E3E; font-weight: 600; font-size: 14px;">Référence :</span>
                                                    <span style="color: rgba(248, 248, 248, 0.6); float: right; font-size: 13px;">#{$donation->getId()->toRfc4122()}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
{$messageSection}
                            
                            <!-- Info Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: rgba(79, 221, 148, 0.1); border-radius: 8px; border-left: 4px solid #4fdd94; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <p style="margin: 0; color: #F8F8F8; font-size: 14px; line-height: 1.5;">
                                            <strong>ℹ️ Information :</strong> Un reçu fiscal vous sera envoyé dans les prochains jours si votre don est éligible à une réduction d'impôts.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 15px; color: rgba(248, 248, 248, 0.7); font-size: 15px; line-height: 1.6;">
                                Encore une fois, merci du fond du cœur pour votre soutien !
                            </p>
                            
                            <p style="margin: 0; color: #F8F8F8; font-size: 15px;">
                                Toute l'équipe JessyMax 🙏
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
                                Cet email a été envoyé à {$donation->getDonorEmail()}
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