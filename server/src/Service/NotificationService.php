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
                <div style='font-family: Arial, sans-serif;'>
                    <h2>Nouveau rendez-vous réservé</h2>
                    <p><strong>Nom :</strong> {$clientNameEsc}</p>
                    <p><strong>Email :</strong> {$clientEmailEsc}</p>
                    <p><strong>Date & heure :</strong> {$datetimeEsc}</p>
                </div>
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
                    <div style='font-family: Arial, sans-serif;'>
                      <h2>Confirmation de votre rendez-vous</h2>
                      <p>Bonjour {$clientNameEsc},</p>
                      <p>Merci d'avoir pris rendez-vous. Voici les détails :</p>
                      <ul>
                        <li><strong>Date & heure :</strong> {$datetimeEsc}</li>
                        <li><strong>Contact :</strong> {$clientEmailEsc}</li>
                      </ul>
                      <p>Si vous souhaitez annuler, cliquez sur le lien ci-dessous :</p>
                      <p><a href=\"{$cancelUrl}\">Appuyez ici pour annuler votre rendez-vous</a></p>
                      <p>Après annulation, le créneau sera de nouveau disponible et nous vous enverrons une confirmation.</p>
                      <p>Cordialement,<br/>Bafodé Cissé</p>
                    </div>
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
                <div style='font-family: Arial, sans-serif;'>
                    <h2>Rendez-vous annulé</h2>
                    <p>Le rendez-vous suivant a été annulé et le créneau est de nouveau disponible :</p>
                    <p><strong>Nom :</strong> {$clientNameEsc}</p>
                    <p><strong>Email :</strong> {$clientEmailEsc}</p>
                    <p><strong>Date & heure :</strong> {$datetimeEsc}</p>
                </div>
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
                    <div style='font-family: Arial, sans-serif;'>
                      <h2>Votre rendez-vous a été annulé</h2>
                      <p>Bonjour {$clientNameEsc},</p>
                      <p>Votre rendez-vous du <strong>{$datetimeEsc}</strong> a bien été annulé. Le créneau est maintenant disponible.</p>
                      <p>Cordialement,<br/>Bafodé Cissé</p>
                    </div>
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
