<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Appointment;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;

class AppointmentProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Appointment) {
            throw new \InvalidArgumentException('Expected Appointment instance');
        }

        // Sauvegarder le rendez-vous
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        // Envoyer la notification email
        $this->notificationService->sendAppointmentNotification($data);

        return $data;
    }
}