<?php
namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendAppointmentNotification(Appointment $appointment): void
    {
        $email = (new Email())
            ->from('noreply@votre-domaine.com')
            ->to('cbafode14@gmail.com')
            ->subject('Nouveau rendez-vous pris')
            ->html($this->createAppointmentEmailContent($appointment));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la réservation
            error_log('Erreur envoi email: ' . $e->getMessage());
        }
    }

    private function createAppointmentEmailContent(Appointment $appointment): string
    {
        $slot = $appointment->getSlot();
        $datetime = $slot->getDatetime()->format('d/m/Y à H:i');

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
            <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h1 style='color: #333; text-align: center; margin-bottom: 30px; font-size: 24px;'>
                    🗓️ Nouveau Rendez-vous Réservé
                </h1>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='color: #495057; margin-top: 0; font-size: 18px;'>Détails du rendez-vous :</h2>
                    <p style='margin: 10px 0;'><strong>📅 Date et heure :</strong> {$datetime}</p>
                    <p style='margin: 10px 0;'><strong>👤 Nom :</strong> " . htmlspecialchars($appointment->getName()) . "</p>
                    <p style='margin: 10px 0;'><strong>📧 Email :</strong> " . htmlspecialchars($appointment->getEmail()) . "</p>
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
