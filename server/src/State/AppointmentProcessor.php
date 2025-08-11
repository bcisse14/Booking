<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Appointment;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AppointmentProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Appointment) {
            throw new \InvalidArgumentException('Expected Appointment instance');
        }

        $this->entityManager->beginTransaction();
        try {
            $slot = $data->getSlot();

            if ($slot) {
                if ($slot->isReserved()) {
                    throw new ConflictHttpException('Ce créneau est déjà réservé.');
                }
                $slot->setReserved(true);
                $this->entityManager->persist($slot);
            }

            if (null === $data->getCancelToken()) {
                try {
                    $data->setCancelToken(bin2hex(random_bytes(16)));
                } catch (\Throwable $e) {
                    $data->setCancelToken(uniqid('', true));
                }
            }

            if (null === $data->getCreatedAt()) {
                $data->setCreatedAt(new \DateTimeImmutable());
            }

            $this->entityManager->persist($data);
            $this->entityManager->flush();
            $this->entityManager->commit();

            try {
                $this->notificationService->sendAppointmentNotification($data);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send appointment notification', [
                    'exception' => $e,
                    'appointment_id' => $data->getId(),
                ]);
            }

            return $data;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
