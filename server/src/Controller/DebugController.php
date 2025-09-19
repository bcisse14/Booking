<?php
// src/Controller/DebugController.php
namespace App\Controller;

use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    public function __construct(private AppointmentRepository $repo) {}

    #[Route('/_debug/appointment-token/{id}', name: 'debug_appointment_token', methods: ['GET'])]
    public function token(int $id): Response
    {
        if (getenv('APP_DEBUG') !== '1') {
            return new JsonResponse(['error' => 'Not allowed'], 403);
        }

        $appointment = $this->repo->find($id);
        if (!$appointment) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        return new JsonResponse(['id' => $appointment->getId(), 'cancelToken' => $appointment->getCancelToken()]);
    }
}
