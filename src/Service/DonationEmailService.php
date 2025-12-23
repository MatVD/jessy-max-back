<?php

namespace App\Service;

use App\Entity\Donation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service pour envoyer les emails de confirmation de don
 * 
 * Installation requise : composer require symfony/mailer
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
        // Créer l'email
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($donation->getDonorEmail())
            ->subject('Merci pour votre don !')
            ->html($this->getEmailTemplate($donation));

        $this->mailer->send($email);
    }

    /**
     * Template HTML de l'email de confirmation
     */
    private function getEmailTemplate(Donation $donation): string
    {
        $amount = number_format((float)$donation->getAmount(), 2, ',', ' ');
        $date = $donation->getCreatedAt()->format('d/m/Y à H:i');
        $message = $donation->getMessage();

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
        .donation-details {
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
        .amount-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
        }
        .message-box {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
            font-style: italic;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .heart {
            color: #e25555;
            font-size: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><span class="heart">❤️</span> Merci pour votre générosité !</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{$donation->getDonorName()}</strong>,</p>
        
        <p>Nous avons bien reçu votre don et nous vous en remercions chaleureusement !</p>
        
        <div class="amount-highlight">
            {$amount} €
        </div>
        
        <p>Votre générosité nous aide à continuer notre mission et à faire une réelle différence. 
        Grâce à des personnes comme vous, nous pouvons poursuivre nos actions et toucher encore plus de personnes.</p>
        
        <div class="donation-details">
            <div class="detail-row">
                <span class="detail-label">Date :</span>
                <span>{$date}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Montant :</span>
                <span>{$amount} €</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Référence :</span>
                <span>#{$donation->getId()->toRfc4122()}</span>
            </div>
        </div>
HTML;

        // Ajouter le message du donateur si présent
        if ($message) {
            $messageSection = <<<HTML
        
        <div class="message-box">
            <strong>Votre message :</strong><br>
            "{$message}"
        </div>
HTML;
            $template = $messageSection;
        } else {
            $template = '';
        }

        $template .= <<<HTML
        
        <p style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
            <strong>ℹ️ Information :</strong> Un reçu fiscal vous sera envoyé dans les prochains jours 
            si votre don est éligible à une réduction d'impôts.
        </p>
        
        <p>Encore une fois, merci du fond du cœur pour votre soutien !</p>
        
        <p>Toute l'équipe JessyMax 🙏</p>
    </div>
    
    <div class="footer">
        <p>© JessyMax - Tous droits réservés</p>
        <p>Cet email a été envoyé à {$donation->getDonorEmail()}</p>
    </div>
</body>
</html>
HTML;

        return $template;
    }
}
