<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Appointment;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AppointmentProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    /**
     * @param Appointment $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Appointment) {
            throw new \InvalidArgumentException('Expected Appointment instance');
        }

        // Persist the appointment (this must not be interrupted by email issues)
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        // Try to send notification but never throw if email fails
        try {
            $this->notificationService->sendAppointmentNotification($data);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send appointment notification', [
                'exception' => $e,
                'appointment_id' => $data->getId(),
            ]);
            // Do not rethrow — the appointment is already persisted
        }

        return $data;
    }
}
