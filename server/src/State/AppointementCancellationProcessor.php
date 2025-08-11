<?php
// src/State/AppointmentCancellationProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Appointment;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Processor qui annule un Appointment et remet le Slot associé disponible.
 *
 * Usage recommandé (exemple d'operation à ajouter sur l'Entity Appointment) :
 *
 * new \ApiPlatform\Metadata\Post(
 *     uriTemplate: '/appointments/cancel/{token}',
 *     processor: \App\State\AppointmentCancellationProcessor::class,
 *     name: 'cancel_appointment'
 * )
 *
 * Le processor accepte soit :
 *  - $data instanceof Appointment (si ApiPlatform a déjà résolu l'entité), soit
 *  - la variable d'URI "token" (cancelToken) pour rechercher l'Appointment.
 */
class AppointmentCancellationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    /**
     * @param Appointment|mixed $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // Résolution de l'appointment : priorité à $data, sinon lookup par token, sinon par id si fourni.
        $appointment = null;

        if ($data instanceof Appointment) {
            $appointment = $data;
        } elseif (!empty($uriVariables['token'])) {
            $appointment = $this->entityManager
                ->getRepository(Appointment::class)
                ->findOneBy(['cancelToken' => $uriVariables['token']]);
        } elseif (!empty($uriVariables['id'])) {
            // fallback : si l'opération est un item operation (id)
            $appointment = $this->entityManager
                ->getRepository(Appointment::class)
                ->find($uriVariables['id']);
        }

        if (!$appointment) {
            throw new NotFoundHttpException('Rendez-vous introuvable.');
        }

        // Idempotence : si déjà annulé, on retourne l'entité (pas d'erreur)
        if ($appointment->isCancelled()) {
            $this->logger->info('Tentative d\'annulation d\'un rendez-vous déjà annulé', [
                'appointment_id' => $appointment->getId(),
            ]);
            return $appointment;
        }

        // Opération dans une transaction : on remet le slot libre et on marque l'appointment cancelled
        $this->entityManager->beginTransaction();
        try {
            $slot = $appointment->getSlot();
            if ($slot !== null) {
                // Remet le créneau disponible (quel que soit son état précédent)
                $slot->setReserved(false);
                $this->entityManager->persist($slot);
            }

            $appointment->setCancelled(true);
            $this->entityManager->persist($appointment);

            $this->entityManager->flush();
            $this->entityManager->commit();

            // Notifications : ne doivent pas faire échouer la transaction
            try {
                $this->notificationService->sendCancellationNotification($appointment);
            } catch (\Throwable $e) {
                $this->logger->error('Échec envoi notification d\'annulation', [
                    'exception' => $e,
                    'appointment_id' => $appointment->getId(),
                ]);
            }

            $this->logger->info('Rendez-vous annulé avec succès', [
                'appointment_id' => $appointment->getId(),
                'slot_id' => $slot?->getId(),
            ]);

            return $appointment;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error('Erreur lors de l\'annulation du rendez-vous', [
                'exception' => $e,
                'appointment_id' => $appointment->getId() ?? null,
            ]);
            throw $e;
        }
    }
}
