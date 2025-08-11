<?php
// src/Service/NotificationService.php
namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    /**
     * Envoie une notification par email pour un rendez-vous.
     * Nève JAMAIS l'exception au caller : on loggue et on continue.
     */
    public function sendAppointmentNotification(Appointment $appointment): void
    {
        try {
            $html = $this->createAppointmentEmailContent($appointment);

            $email = (new Email())
                ->from('noreply@votre-domaine.com')      // adapte ici si besoin
                ->to('cbafode14@gmail.com')             // adapte la cible
                ->subject('Nouveau rendez-vous pris')
                ->html($html);

            $this->mailer->send($email);
            $this->logger->info('Notification appointment envoyée', ['appointment_id' => $appointment->getId()]);
        } catch (\Throwable $e) {
            // Log et on continue : ne doit pas casser la création du rendez-vous
            $this->logger->error('Erreur envoi email: ' . $e->getMessage(), [
                'exception' => $e,
                'appointment_id' => $appointment->getId(),
            ]);
        }
    }

    private function createAppointmentEmailContent(Appointment $appointment): string
    {
        $slot = $appointment->getSlot();
        $datetime = 'Non renseigné';

        if ($slot !== null) {
            try {
                $slotDatetime = $slot->getDatetime();
                if ($slotDatetime instanceof \DateTimeInterface) {
                    $datetime = $slotDatetime->format('d/m/Y à H:i');
                } else {
                    $datetime = (string) $slotDatetime;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Impossible de récupérer la date du slot pour le mail', [
                    'exception' => $e,
                    'appointment_id' => $appointment->getId(),
                ]);
            }
        }

        $nameEsc = htmlspecialchars($appointment->getName() ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $emailEsc = htmlspecialchars($appointment->getEmail() ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $datetimeEsc = htmlspecialchars($datetime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
            <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h1 style='color: #333; text-align: center; margin-bottom: 30px; font-size: 24px;'>
                    🗓️ Nouveau Rendez-vous Réservé
                </h1>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='color: #495057; margin-top: 0; font-size: 18px;'>Détails du rendez-vous :</h2>
                    <p style='margin: 10px 0;'><strong>📅 Date et heure :</strong> {$datetimeEsc}</p>
                    <p style='margin: 10px 0;'><strong>👤 Nom :</strong> {$nameEsc}</p>
                    <p style='margin: 10px 0;'><strong>📧 Email :</strong> {$emailEsc}</p>
                </div>
                
                <div style='background-color: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #bee5eb;'>
                    <p style='margin: 0; color: #0c5460;'>
                        <strong>ℹ️ Information :</strong> Ce rendez-vous est en attente de confirmation.
                    </p>
                </div>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <p style='color: #6c757d; font-size: 14px;'>
                        Email envoyé automatiquement par le système de réservation
                    </p>
                </div>
            </div>
        </div>";
    }
}
