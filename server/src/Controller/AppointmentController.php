<?php
// src/Controller/AppointmentController.php
namespace App\Controller;

use App\Entity\Appointment;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppointmentController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    #[Route('/appointments/cancel/{token}', name: 'appointment_cancel', methods: ['GET'])]
    public function cancel(string $token): Response
    {
        $repo = $this->em->getRepository(Appointment::class);
        /** @var Appointment|null $appointment */
        $appointment = $repo->findOneBy(['cancelToken' => $token]);

        if (!$appointment) {
            return new Response('<h1>Token invalide</h1><p>Le lien est invalide ou a déjà été utilisé.</p>', Response::HTTP_NOT_FOUND);
        }

        if ($appointment->isCancelled()) {
            return new Response('<h1>Rendez-vous déjà annulé</h1><p>Ce rendez-vous a déjà été annulé.</p>');
        }

        $slot = $appointment->getSlot();
        if ($slot) {
            $slot->setReserved(false);
            $this->em->persist($slot);
        }

        $appointment->setCancelled(true);
        $this->em->persist($appointment);

        $this->em->flush();

        try {
            $this->notificationService->sendCancellationNotification($appointment);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur envoi notifications annulation', [
                'exception' => $e,
                'appointment_id' => $appointment->getId(),
            ]);
        }

        // Retour simple : tu peux ici rediriger vers une page front si tu as une UI
        $html = '<h1>Rendez-vous annulé</h1><p>Le créneau a été libéré et une notification a été envoyée.</p>';
        return new Response($html);
    }
}
