<?php
namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private string $ownerEmail = 'cbafode14@gmail.com';
    private string $appUrl;

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
        $this->appUrl = getenv('APP_URL') ?: 'http://localhost:8000';
    }

    public function sendAppointmentNotification(Appointment $appointment): void
    {
        try {
            $slot = $appointment->getSlot();
            $datetime = 'Non renseigné';
            if ($slot !== null) {
                try {
                    $slotDatetime = $slot->getDatetime();
                    if ($slotDatetime instanceof \DateTimeInterface) {
                        $datetime = $slotDatetime->format('d/m/Y à H:i');
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning('Impossible de lire la date du slot', ['exception' => $e]);
                }
            }

            $clientName = $appointment->getName() ?: '—';
            $clientEmail = $appointment->getEmail() ?: '—';
            $clientNameEsc = htmlspecialchars($clientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $clientEmailEsc = htmlspecialchars($clientEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $datetimeEsc = htmlspecialchars($datetime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $ownerHtml = "
                <!DOCTYPE html>
                <html lang='fr'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Nouveau rendez-vous réservé</title>
                    <!--[if mso]>
                    <noscript>
                        <xml>
                            <o:OfficeDocumentSettings>
                                <o:PixelsPerInch>96</o:PixelsPerInch>
                            </o:OfficeDocumentSettings>
                        </xml>
                    </noscript>
                    <![endif]-->
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body {
                            margin: 0 !important;
                            padding: 0 !important;
                            background-color: #f4f6f9 !important;
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
                            line-height: 1.6 !important;
                            -webkit-text-size-adjust: 100% !important;
                            -ms-text-size-adjust: 100% !important;
                        }
                        table { border-collapse: collapse !important; mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important; }
                        .container {
                            max-width: 600px !important;
                            margin: 0 auto !important;
                            background-color: #ffffff !important;
                        }
                        .header {
                            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
                            padding: 40px 30px !important;
                            text-align: center !important;
                        }
                        .header h1 {
                            color: #ffffff !important;
                            font-size: 24px !important;
                            font-weight: 600 !important;
                            margin: 0 0 8px 0 !important;
                        }
                        .header p {
                            color: #ffffff !important;
                            font-size: 16px !important;
                            margin: 0 !important;
                            opacity: 0.9 !important;
                        }
                        .notification-icon {
                            width: 70px !important;
                            height: 70px !important;
                            background-color: #ffffff !important;
                            border-radius: 50% !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            text-align: center !important;
                            margin: 0 auto 20px auto !important;
                            font-size: 35px !important;
                            color: #2563eb !important;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
                        }
                        .content {
                            padding: 40px 30px !important;
                        }
                        .status-badge {
                            display: inline-block !important;
                            background-color: #2563eb !important;
                            color: #ffffff !important;
                            padding: 8px 16px !important;
                            border-radius: 20px !important;
                            font-size: 12px !important;
                            font-weight: 600 !important;
                            text-transform: uppercase !important;
                            letter-spacing: 0.5px !important;
                            margin-bottom: 20px !important;
                        }
                        .appointment-card {
                            background-color: #eff6ff !important;
                            border: 1px solid #bfdbfe !important;
                            border-left: 4px solid #2563eb !important;
                            border-radius: 12px !important;
                            padding: 24px !important;
                            margin: 24px 0 !important;
                        }
                        .appointment-card h3 {
                            color: #1e3a8a !important;
                            font-size: 18px !important;
                            margin: 0 0 20px 0 !important;
                            font-weight: 600 !important;
                        }
                        .info-grid {
                            margin: 15px 0 !important;
                        }
                        .info-item {
                            display: table !important;
                            width: 100% !important;
                            padding: 12px 0 !important;
                            border-bottom: 1px solid #e2e8f0 !important;
                        }
                        .info-item:last-child {
                            border-bottom: none !important;
                        }
                        .info-item .label {
                            display: table-cell !important;
                            font-weight: 600 !important;
                            color: #4a5568 !important;
                            width: 35% !important;
                        }
                        .info-item .value {
                            display: table-cell !important;
                            color: #2d3748 !important;
                            font-weight: 500 !important;
                        }
                        .success-message {
                            background-color: #ecfdf5 !important;
                            border: 1px solid #86efac !important;
                            border-radius: 8px !important;
                            padding: 20px !important;
                            margin: 24px 0 !important;
                            color: #14532d !important;
                        }
                        .success-message strong {
                            color: #166534 !important;
                            font-weight: 700 !important;
                        }
                        .message-text {
                            color: #4a5568 !important;
                            font-size: 16px !important;
                            margin: 20px 0 !important;
                        }
                        .footer {
                            background-color: #f7fafc !important;
                            padding: 24px 30px !important;
                            text-align: center !important;
                            font-size: 12px !important;
                            color: #718096 !important;
                            border-top: 1px solid #e2e8f0 !important;
                        }
                        @media only screen and (max-width: 600px) {
                            .container { margin: 10px !important; }
                            .header, .content { padding: 25px 20px !important; }
                            .header h1 { font-size: 20px !important; }
                            .notification-icon { width: 60px !important; height: 60px !important; font-size: 28px !important; }
                        }
                    </style>
                </head>
                <body>
                    <div style='background-color: #f4f6f9; padding: 20px 0;'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0'>
                            <tr>
                                <td align='center'>
                                    <table class='container' role='presentation' width='600' cellspacing='0' cellpadding='0' border='0'>
                                        <!-- Header -->
                                        <tr>
                                            <td class='header'>
                                                <table style='margin: 0 auto;'>
                                                    <tr>
                                                        <td class='notification-icon'>🔔</td>
                                                    </tr>
                                                </table>
                                                <h1>Nouveau rendez-vous !</h1>
                                                <p>Un client vient de réserver un créneau</p>
                                            </td>
                                        </tr>
                                        
                                        <!-- Content -->
                                        <tr>
                                            <td class='content'>
                                                <div class='status-badge'>✨ Nouveau</div>
                                                
                                                <p class='message-text'>
                                                    Excellente nouvelle ! Un nouveau rendez-vous vient d'être <strong>réservé</strong> sur votre plateforme.
                                                </p>
                                                
                                                <div class='appointment-card'>
                                                    <h3>👤 Informations du client</h3>
                                                    <div class='info-grid'>
                                                        <div class='info-item'>
                                                            <span class='label'>Nom du client :</span>
                                                            <span class='value'>{$clientNameEsc}</span>
                                                        </div>
                                                        <div class='info-item'>
                                                            <span class='label'>Email :</span>
                                                            <span class='value'>{$clientEmailEsc}</span>
                                                        </div>
                                                        <div class='info-item'>
                                                            <span class='label'>Date & heure :</span>
                                                            <span class='value'>{$datetimeEsc}</span>
                                                        </div>
                                                        <div class='info-item'>
                                                            <span class='label'>Statut :</span>
                                                            <span class='value' style='color: #10b981; font-weight: 600;'>Confirmé ✓</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class='success-message'>
                                                    <strong>🎯 Actions automatiques effectuées</strong><br><br>
                                                    • Le créneau a été marqué comme réservé<br>
                                                    • Un email de confirmation a été envoyé au client<br>
                                                    • Le client a reçu un lien d'annulation sécurisé
                                                </div>
                                                
                                                <p class='message-text' style='font-size: 14px; color: #718096;'>
                                                    <strong>💡 Rappel :</strong> Vous pouvez consulter tous vos rendez-vous via votre interface d'administration.
                                                </p>
                                            </td>
                                        </tr>
                                        
                                        <!-- Footer -->
                                        <tr>
                                            <td class='footer'>
                                                <p><strong>Système de réservation automatisé</strong></p>
                                                <p>Notification générée automatiquement par votre plateforme</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </body>
                </html>
            ";
            $ownerEmail = (new Email())
                ->from('noreply@votre-domaine.com')
                ->to($this->ownerEmail)
                ->subject('Nouveau rendez-vous réservé')
                ->html($ownerHtml);

            $this->mailer->send($ownerEmail);
            $this->logger->info('Notification envoyée au propriétaire', ['appointment_id' => $appointment->getId()]);

            if (!empty($appointment->getEmail())) {
                $cancelUrl = $this->buildCancelUrl($appointment);
                $clientHtml = "
                <!DOCTYPE html>
                <html lang='fr'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Confirmation de votre rendez-vous</title>
                    <!--[if mso]>
                    <noscript>
                        <xml>
                            <o:OfficeDocumentSettings>
                                <o:PixelsPerInch>96</o:PixelsPerInch>
                            </o:OfficeDocumentSettings>
                        </xml>
                    </noscript>
                    <![endif]-->
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body {
                            margin: 0 !important;
                            padding: 0 !important;
                            background-color: #f4f6f9 !important;
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
                            line-height: 1.6 !important;
                            -webkit-text-size-adjust: 100% !important;
                            -ms-text-size-adjust: 100% !important;
                        }
                        table { border-collapse: collapse !important; mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important; }
                        .container {
                            max-width: 600px !important;
                            margin: 0 auto !important;
                            background-color: #ffffff !important;
                        }
                        .header {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                            padding: 40px 30px !important;
                            text-align: center !important;
                        }
                        .header h1 {
                            color: #ffffff !important;
                            font-size: 28px !important;
                            font-weight: 600 !important;
                            margin: 0 0 8px 0 !important;
                        }
                        .header p {
                            color: #ffffff !important;
                            font-size: 16px !important;
                            margin: 0 !important;
                            opacity: 0.9 !important;
                        }
                        .calendar-icon {
                            width: 80px !important;
                            height: 80px !important;
                            background-color: #ffffff !important;
                            border-radius: 50% !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            margin: 0 auto 20px auto !important;
                            font-size: 40px !important;
                            color: #667eea !important;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
                        }
                        .content {
                            padding: 40px 30px !important;
                        }
                        .status-badge {
                            display: inline-block !important;
                            background-color: #667eea !important;
                            color: #ffffff !important;
                            padding: 8px 16px !important;
                            border-radius: 20px !important;
                            font-size: 12px !important;
                            font-weight: 600 !important;
                            text-transform: uppercase !important;
                            letter-spacing: 0.5px !important;
                            margin-bottom: 20px !important;
                        }
                        .appointment-card {
                            background-color: #f7fafc !important;
                            border-left: 4px solid #667eea !important;
                            border-radius: 12px !important;
                            padding: 24px !important;
                            margin: 24px 0 !important;
                        }
                        .appointment-card h3 {
                            color: #2d3748 !important;
                            font-size: 18px !important;
                            margin: 0 0 16px 0 !important;
                            font-weight: 600 !important;
                        }
                        .detail-row {
                            display: table !important;
                            width: 100% !important;
                            padding: 12px 0 !important;
                            border-bottom: 1px solid #e2e8f0 !important;
                        }
                        .detail-row:last-child {
                            border-bottom: none !important;
                        }
                        .detail-row .label {
                            display: table-cell !important;
                            font-weight: 600 !important;
                            color: #4a5568 !important;
                            width: 40% !important;
                        }
                        .detail-row .value {
                            display: table-cell !important;
                            color: #2d3748 !important;
                            font-weight: 500 !important;
                        }
                        .message-box {
                            background-color: #ebf8ff !important;
                            border: 1px solid #90cdf4 !important;
                            border-radius: 8px !important;
                            padding: 20px !important;
                            margin: 24px 0 !important;
                            color: #1e3a8a !important;
                        }
                        .message-box strong {
                            color: #1e40af !important;
                        }
                        .message-text {
                            color: #495057 !important;
                            font-size: 16px !important;
                            margin: 20px 0 !important;
                        }
                        .cancel-button {
                            display: inline-block !important;
                            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                            color: #ffffff !important;
                            padding: 12px 24px !important;
                            border-radius: 8px !important;
                            text-decoration: none !important;
                            font-weight: 600 !important;
                            text-align: center !important;
                            margin: 20px 0 !important;
                        }
                        .divider {
                            height: 1px !important;
                            background-color: #dee2e6 !important;
                            margin: 30px 0 !important;
                        }
                        .footer {
                            background-color: #f8f9fa !important;
                            padding: 30px !important;
                            text-align: center !important;
                            border-top: 1px solid #dee2e6 !important;
                        }
                        .footer p {
                            margin: 8px 0 !important;
                            color: #6c757d !important;
                            font-size: 14px !important;
                        }
                        .company-info {
                            margin-top: 20px !important;
                            font-weight: 600 !important;
                            color: #495057 !important;
                        }
                        @media only screen and (max-width: 600px) {
                            .container { margin: 10px !important; }
                            .header, .content, .footer { padding: 25px 20px !important; }
                            .header h1 { font-size: 24px !important; }
                            .calendar-icon { width: 60px !important; height: 60px !important; font-size: 30px !important; }
                        }
                    </style>
                </head>
                <body>
                    <div style='background-color: #f4f6f9; padding: 20px 0;'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0'>
                            <tr>
                                <td align='center'>
                                    <table class='container' role='presentation' width='600' cellspacing='0' cellpadding='0' border='0'>
                                        <!-- Header -->
                                        <tr>
                                            <td class='header'>
                                                <table style='margin: 0 auto;'>
                                                    <tr>
                                                        <td class='calendar-icon'>📅</td>
                                                    </tr>
                                                </table>
                                                <h1>Rendez-vous confirmé</h1>
                                                <p>Votre réservation a été enregistrée avec succès</p>
                                            </td>
                                        </tr>
                                        
                                        <!-- Content -->
                                        <tr>
                                            <td class='content'>
                                                <div class='status-badge'>✓ Confirmé</div>
                                                
                                                <p class='message-text'>
                                                    Bonjour <strong>{$clientNameEsc}</strong>,
                                                </p>
                                                
                                                <p class='message-text'>
                                                    Merci d'avoir pris rendez-vous avec moi ! Je vous confirme que votre 
                                                    réservation a été <strong>enregistrée avec succès</strong>.
                                                </p>
                                                
                                                <div class='appointment-card'>
                                                    <h3>📋 Détails de votre rendez-vous</h3>
                                                    <div class='detail-row'>
                                                        <span class='label'>Date et heure :</span>
                                                        <span class='value'>{$datetimeEsc}</span>
                                                    </div>
                                                    <div class='detail-row'>
                                                        <span class='label'>Statut :</span>
                                                        <span class='value' style='color: #667eea; font-weight: 600;'>Confirmé ✓</span>
                                                    </div>
                                                    <div class='detail-row'>
                                                        <span class='label'>Email de contact :</span>
                                                        <span class='value'>{$clientEmailEsc}</span>
                                                    </div>
                                                </div>
                                                
                                                <div class='message-box'>
                                                    <strong>📍 Informations importantes</strong><br><br>
                                                    Veuillez vous présenter à l'heure convenue. En cas d'empêchement, je vous remercie 
                                                    de me prévenir le plus tôt possible en utilisant le lien d'annulation ci-dessous.
                                                </div>
                                                
                                                <div style='text-align: center; margin: 30px 0;'>
                                                    <p style='margin-bottom: 15px; color: #4a5568;'>Besoin d'annuler votre rendez-vous ?</p>
                                                    <a href='{$cancelUrl}' class='cancel-button'>
                                                        Annuler ce rendez-vous
                                                    </a>
                                                    <p style='margin-top: 15px; font-size: 12px; color: #718096;'>
                                                        Après annulation, le créneau sera de nouveau disponible
                                                    </p>
                                                </div>
                                                
                                                <div class='message-box'>
                                                    <strong>🤝 J'ai hâte de vous rencontrer</strong><br><br>
                                                    Je me prépare à vous recevoir dans les meilleures conditions. 
                                                    Merci pour votre confiance !
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Footer -->
                                        <tr>
                                            <td class='footer'>
                                                <div class='company-info'>
                                                    <p><strong>Bafodé Cissé</strong></p>
                                                    <p>Service professionnel de prise de rendez-vous</p>
                                                </div>
                                                <div class='divider'></div>
                                                <p>📧 Cet email a été envoyé automatiquement suite à votre réservation.</p>
                                                <p>💬 Pour toute question, contactez-moi via mes canaux habituels.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </body>
                </html>
                ";

                $confirmation = (new Email())
                    ->from('noreply@votre-domaine.com')
                    ->to($appointment->getEmail())
                    ->subject('Confirmation de votre rendez-vous')
                    ->html($clientHtml);

                $this->mailer->send($confirmation);
                $this->logger->info('Confirmation envoyée au client', ['appointment_id' => $appointment->getId()]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur envoi email: ' . $e->getMessage(), [
                'exception' => $e,
                'appointment_id' => $appointment->getId(),
            ]);
        }
    }

    public function sendCancellationNotification(Appointment $appointment): void
    {
        try {
            $slot = $appointment->getSlot();
            $datetime = 'Non renseigné';
            if ($slot !== null && $slot->getDatetime() instanceof \DateTimeInterface) {
                $datetime = $slot->getDatetime()->format('d/m/Y à H:i');
            }

            $clientName = $appointment->getName() ?: '—';
            $clientEmail = $appointment->getEmail() ?: '—';
            $clientNameEsc = htmlspecialchars($clientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $clientEmailEsc = htmlspecialchars($clientEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $datetimeEsc = htmlspecialchars($datetime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $ownerHtml = "
                <!DOCTYPE html>
                <html lang='fr'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Notification d'annulation de rendez-vous</title>
                    <!--[if mso]>
                    <noscript>
                        <xml>
                            <o:OfficeDocumentSettings>
                                <o:PixelsPerInch>96</o:PixelsPerInch>
                            </o:OfficeDocumentSettings>
                        </xml>
                    </noscript>
                    <![endif]-->
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body {
                            margin: 0 !important;
                            padding: 0 !important;
                            background-color: #f4f6f9 !important;
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
                            line-height: 1.6 !important;
                            -webkit-text-size-adjust: 100% !important;
                            -ms-text-size-adjust: 100% !important;
                        }
                        table { border-collapse: collapse !important; mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important; }
                        .container {
                            max-width: 600px !important;
                            margin: 0 auto !important;
                            background-color: #ffffff !important;
                        }
                        .header {
                            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
                            padding: 40px 30px !important;
                            text-align: center !important;
                        }
                        .header h1 {
                            color: #ffffff !important;
                            font-size: 24px !important;
                            font-weight: 600 !important;
                            margin: 0 0 8px 0 !important;
                        }
                        .header p {
                            color: #ffffff !important;
                            font-size: 16px !important;
                            margin: 0 !important;
                            opacity: 0.9 !important;
                        }
                        .calendar-icon {
                            width: 70px !important;
                            height: 70px !important;
                            background-color: #ffffff !important;
                            border-radius: 50% !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            text-align: center !important;
                            margin: 0 auto 20px auto !important;
                            font-size: 35px !important;
                            color: #2563eb !important;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
                        }
                        .content {
                            padding: 40px 30px !important;
                        }
                        .status-badge {
                            display: inline-block !important;
                            background-color: #2563eb !important;
                            color: #ffffff !important;
                            padding: 8px 16px !important;
                            border-radius: 20px !important;
                            font-size: 12px !important;
                            font-weight: 600 !important;
                            text-transform: uppercase !important;
                            letter-spacing: 0.5px !important;
                            margin-bottom: 20px !important;
                        }
                        .appointment-card {
                            background-color: #eff6ff !important;
                            border: 1px solid #bfdbfe !important;
                            border-left: 4px solid #2563eb !important;
                            border-radius: 12px !important;
                            padding: 24px !important;
                            margin: 24px 0 !important;
                        }
                        .appointment-card h3 {
                            color: #1e3a8a !important;
                            font-size: 18px !important;
                            margin: 0 0 20px 0 !important;
                            font-weight: 600 !important;
                        }
                        .info-grid {
                            margin: 15px 0 !important;
                        }
                        .info-item {
                            display: table !important;
                            width: 100% !important;
                            padding: 12px 0 !important;
                            border-bottom: 1px solid #e2e8f0 !important;
                        }
                        .info-item:last-child {
                            border-bottom: none !important;
                        }
                        .info-item .label {
                            display: table-cell !important;
                            font-weight: 600 !important;
                            color: #4a5568 !important;
                            width: 35% !important;
                        }
                        .info-item .value {
                            display: table-cell !important;
                            color: #2d3748 !important;
                            font-weight: 500 !important;
                        }
                        .success-message {
                            background-color: #f0fff4 !important;
                            border: 1px solid #9ae6b4 !important;
                            border-radius: 8px !important;
                            padding: 20px !important;
                            margin: 24px 0 !important;
                            color: #22543d !important;
                        }
                        .success-message strong {
                            color: #22543d !important;
                            font-weight: 700 !important;
                        }
                        .message-text {
                            color: #4a5568 !important;
                            font-size: 16px !important;
                            margin: 20px 0 !important;
                        }
                        .footer {
                            background-color: #f7fafc !important;
                            padding: 24px 30px !important;
                            text-align: center !important;
                            font-size: 12px !important;
                            color: #718096 !important;
                            border-top: 1px solid #e2e8f0 !important;
                        }
                        @media only screen and (max-width: 600px) {
                            .container { margin: 10px !important; }
                            .header, .content { padding: 25px 20px !important; }
                            .header h1 { font-size: 20px !important; }
                            .calendar-icon { width: 60px !important; height: 60px !important; font-size: 28px !important; }
                        }
                    </style>
                </head>
                <body>
                    <div style='background-color: #f4f6f9; padding: 20px 0;'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0'>
                            <tr>
                                <td align='center'>
                                    <table class='container' role='presentation' width='600' cellspacing='0' cellpadding='0' border='0'>
                                        <!-- Header -->
                                        <tr>
                                            <td class='header'>
                                                <table style='margin: 0 auto;'>
                                                    <tr>
                                                        <td class='calendar-icon'>📅</td>
                                                    </tr>
                                                </table>
                                                <h1>Rendez-vous annulé</h1>
                                                <p>Notification système de votre plateforme</p>
                                            </td>
                                        </tr>
                                        
                                        <!-- Content -->
                                        <tr>
                                            <td class='content'>
                                                <div class='status-badge'>⚠️ Annulé</div>
                                                
                                                <p class='message-text'>
                                                    Un rendez-vous vient d'être <strong>annulé</strong> sur votre plateforme de réservation.
                                                </p>
                                                
                                                <div class='appointment-card'>
                                                    <h3>📋 Détails du rendez-vous annulé</h3>
                                                    <div class='info-grid'>
                                                        <div class='info-item'>
                                                            <span class='label'>Client :</span>
                                                            <span class='value'>{$clientNameEsc}</span>
                                                        </div>
                                                        <div class='info-item'>
                                                            <span class='label'>Email :</span>
                                                            <span class='value'>{$clientEmailEsc}</span>
                                                        </div>
                                                        <div class='info-item'>
                                                            <span class='label'>Date & heure :</span>
                                                            <span class='value'>{$datetimeEsc}</span>
                                                        </div>
                                                        <div class='info-item'>
                                                            <span class='label'>Statut :</span>
                                                            <span class='value' style='color: #e53e3e; font-weight: 600;'>Annulé ❌</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class='success-message'>
                                                    <strong>✅ Action automatique effectuée</strong><br><br>
                                                    Le créneau a été automatiquement libéré et est maintenant disponible pour de nouvelles réservations. 
                                                    Le client a reçu un email de confirmation d'annulation.
                                                </div>
                                                
                                                <p class='message-text' style='font-size: 14px; color: #718096;'>
                                                    <strong>💡 Rappel :</strong> Vous pouvez consulter l'état de tous vos créneaux via votre interface d'administration.
                                                </p>
                                            </td>
                                        </tr>
                                        
                                        <!-- Footer -->
                                        <tr>
                                            <td class='footer'>
                                                <p><strong>Système de réservation automatisé</strong></p>
                                                <p>Cette notification a été générée automatiquement par votre plateforme</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </body>
                </html>
            ";
            $ownerEmail = (new Email())
                ->from('noreply@votre-domaine.com')
                ->to($this->ownerEmail)
                ->subject('Annulation de rendez-vous')
                ->html($ownerHtml);

            $this->mailer->send($ownerEmail);
            $this->logger->info('Notification d\'annulation envoyée au propriétaire', ['appointment_id' => $appointment->getId()]);

            if (!empty($appointment->getEmail())) {
                $clientHtml = "
                <!DOCTYPE html>
                <html lang='fr'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Annulation confirmée - Votre rendez-vous</title>
                    <!--[if mso]>
                    <noscript>
                        <xml>
                            <o:OfficeDocumentSettings>
                                <o:PixelsPerInch>96</o:PixelsPerInch>
                            </o:OfficeDocumentSettings>
                        </xml>
                    </noscript>
                    <![endif]-->
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body {
                            margin: 0 !important;
                            padding: 0 !important;
                            background-color: #f4f6f9 !important;
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
                            line-height: 1.6 !important;
                            -webkit-text-size-adjust: 100% !important;
                            -ms-text-size-adjust: 100% !important;
                        }
                        table { border-collapse: collapse !important; mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important; }
                        .container {
                            max-width: 600px !important;
                            margin: 0 auto !important;
                            background-color: #ffffff !important;
                        }
                        .header {
                            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%) !important;
                            padding: 40px 30px !important;
                            text-align: center !important;
                        }
                        .header h1 {
                            color: #ffffff !important;
                            font-size: 28px !important;
                            font-weight: 600 !important;
                            margin: 0 0 8px 0 !important;
                        }
                        .header p {
                            color: #ffffff !important;
                            font-size: 16px !important;
                            margin: 0 !important;
                            opacity: 0.9 !important;
                        }
                        .check-icon {
                            width: 80px !important;
                            height: 80px !important;
                            background-color: #ffffff !important;
                            border-radius: 50% !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            margin: 0 auto 20px auto !important;
                            font-size: 40px !important;
                            color: #48bb78 !important;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
                        }
                        .content {
                            padding: 40px 30px !important;
                        }
                        .status-badge {
                            display: inline-block !important;
                            background-color: #48bb78 !important;
                            color: #ffffff !important;
                            padding: 8px 16px !important;
                            border-radius: 20px !important;
                            font-size: 12px !important;
                            font-weight: 600 !important;
                            text-transform: uppercase !important;
                            letter-spacing: 0.5px !important;
                            margin-bottom: 20px !important;
                        }
                        .appointment-card {
                            background-color: #f7fafc !important;
                            border-left: 4px solid #48bb78 !important;
                            border-radius: 12px !important;
                            padding: 24px !important;
                            margin: 24px 0 !important;
                        }
                        .appointment-card h3 {
                            color: #2d3748 !important;
                            font-size: 18px !important;
                            margin: 0 0 16px 0 !important;
                            font-weight: 600 !important;
                        }
                        .detail-row {
                            display: table !important;
                            width: 100% !important;
                            padding: 12px 0 !important;
                            border-bottom: 1px solid #e2e8f0 !important;
                        }
                        .detail-row:last-child {
                            border-bottom: none !important;
                        }
                        .detail-row .label {
                            display: table-cell !important;
                            font-weight: 600 !important;
                            color: #4a5568 !important;
                            width: 40% !important;
                        }
                        .detail-row .value {
                            display: table-cell !important;
                            color: #2d3748 !important;
                            font-weight: 500 !important;
                        }
                        .message-box {
                            background-color: #e6fffa !important;
                            border: 1px solid #81e6d9 !important;
                            border-radius: 8px !important;
                            padding: 20px !important;
                            margin: 24px 0 !important;
                            color: #234e52 !important;
                        }
                        .message-box strong {
                            color: #065f46 !important;
                        }
                        .message-text {
                            color: #495057 !important;
                            font-size: 16px !important;
                            margin: 20px 0 !important;
                        }
                        .divider {
                            height: 1px !important;
                            background-color: #dee2e6 !important;
                            margin: 30px 0 !important;
                        }
                        .footer {
                            background-color: #f8f9fa !important;
                            padding: 30px !important;
                            text-align: center !important;
                            border-top: 1px solid #dee2e6 !important;
                        }
                        .footer p {
                            margin: 8px 0 !important;
                            color: #6c757d !important;
                            font-size: 14px !important;
                        }
                        .company-info {
                            margin-top: 20px !important;
                            font-weight: 600 !important;
                            color: #495057 !important;
                        }
                        @media only screen and (max-width: 600px) {
                            .container { margin: 10px !important; }
                            .header, .content, .footer { padding: 25px 20px !important; }
                            .header h1 { font-size: 24px !important; }
                            .check-icon { width: 60px !important; height: 60px !important; font-size: 30px !important; }
                        }
                    </style>
                </head>
                <body>
                    <div style='background-color: #f4f6f9; padding: 20px 0;'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0'>
                            <tr>
                                <td align='center'>
                                    <table class='container' role='presentation' width='600' cellspacing='0' cellpadding='0' border='0'>
                                        <!-- Header -->
                                        <tr>
                                            <td class='header'>
                                                <div class='check-icon'>✓</div>
                                                <h1>Annulation confirmée</h1>
                                                <p>Votre rendez-vous a été annulé avec succès</p>
                                            </td>
                                        </tr>
                                        
                                        <!-- Content -->
                                        <tr>
                                            <td class='content'>
                                                <div class='status-badge'>✓ Annulé</div>
                                                
                                                <p class='message-text'>
                                                    Bonjour <strong>{$clientNameEsc}</strong>,
                                                </p>
                                                
                                                <p class='message-text'>
                                                    Je vous confirme que votre rendez-vous a été <strong>annulé avec succès</strong>. 
                                                    Le créneau est désormais libéré et sera de nouveau disponible à la réservation.
                                                </p>
                                                
                                                <div class='appointment-card'>
                                                    <h3>📅 Détails du rendez-vous annulé</h3>
                                                    <div class='detail-row'>
                                                        <span class='label'>Date et heure :</span>
                                                        <span class='value'>{$datetimeEsc}</span>
                                                    </div>
                                                    <div class='detail-row'>
                                                        <span class='label'>Statut :</span>
                                                        <span class='value' style='color: #48bb78; font-weight: 600;'>Annulé ✓</span>
                                                    </div>
                                                    <div class='detail-row'>
                                                        <span class='label'>Email de contact :</span>
                                                        <span class='value'>{$clientEmailEsc}</span>
                                                    </div>
                                                </div>
                                                
                                                <div class='message-box'>
                                                    <strong>💡 Besoin de reprendre un rendez-vous ?</strong><br><br>
                                                    N'hésitez pas à consulter mes créneaux disponibles et à effectuer une nouvelle réservation 
                                                    selon vos préférences. Je reste à votre entière disposition.
                                                </div>
                                                
                                                <div class='message-box'>
                                                    <strong>🤝 Je comprends</strong><br><br>
                                                    Si cette annulation fait suite à un empêchement de dernière minute, je comprends 
                                                    parfaitement. Votre satisfaction est ma priorité et je vous remercie de m'avoir prévenu.
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Footer -->
                                        <tr>
                                            <td class='footer'>
                                                <div class='company-info'>
                                                    <p><strong>Bafodé Cissé</strong></p>
                                                    <p>Service professionnel de prise de rendez-vous</p>
                                                </div>
                                                <div class='divider'></div>
                                                <p>📧 Cet email a été envoyé automatiquement suite à votre demande d'annulation.</p>
                                                <p>💬 Pour toute question, contactez-moi via mes canaux habituels.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </body>
                </html>
                ";
                $confirmation = (new Email())
                    ->from('noreply@votre-domaine.com')
                    ->to($appointment->getEmail())
                    ->subject('Annulation de votre rendez-vous')
                    ->html($clientHtml);

                $this->mailer->send($confirmation);
                $this->logger->info('Confirmation d\'annulation envoyée au client', ['appointment_id' => $appointment->getId()]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur envoi email annulation: ' . $e->getMessage(), [
                'exception' => $e,
                'appointment_id' => $appointment->getId(),
            ]);
        }
    }

    private function buildCancelUrl(Appointment $appointment): string
    {
        $token = $appointment->getCancelToken() ?: '';
        $base = rtrim($this->appUrl, '/');
        return $base . '/appointments/cancel/' . rawurlencode($token);
    }
}
