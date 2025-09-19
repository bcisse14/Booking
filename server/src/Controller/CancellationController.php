<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CancellationController extends AbstractController
{
    #[Route('/appointments/cancel/{token}', name: 'appointment_cancel', methods: ['GET'])]
    public function showCancellation(string $token, AppointmentRepository $appointmentRepository): Response
    {
        $appointment = $appointmentRepository->findOneBy(['cancelToken' => $token]);
        
        if (!$appointment) {
            return $this->render('cancellation/not_found.html.twig');
        }

        $slot = $appointment->getSlot();
        $datetime = 'Non renseigné';
        if ($slot !== null && $slot->getDatetime() instanceof \DateTimeInterface) {
            $datetime = $slot->getDatetime()->format('d/m/Y à H:i');
        }

        $clientName = $appointment->getName() ?: 'Client';
        $status = $appointment->isCancelled() ? 'Déjà annulé' : 'Actif';

        return new Response($this->generateCancellationHtml($clientName, $datetime, $status, $token), 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }

    private function generateCancellationHtml(string $clientName, string $datetime, string $status, string $token): string
    {
        $clientNameEsc = htmlspecialchars($clientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $datetimeEsc = htmlspecialchars($datetime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $statusEsc = htmlspecialchars($status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $isAlreadyCancelled = $status === 'Déjà annulé';
        $headerClass = $isAlreadyCancelled ? 'header-warning' : 'header-active';
        $actionButton = $isAlreadyCancelled ? '' : "
            <div class='actions'>
                <button onclick='cancelAppointment(\"{$token}\")' class='btn btn-cancel'>
                    Confirmer l'annulation
                </button>
            </div>";

        return "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Annulation de rendez-vous</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .header-active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header-warning {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            color: #2d3436;
            padding: 40px 30px;
            text-align: center;
        }
        .header-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .icon-active { color: #f5576c; }
        .icon-warning { color: #e17055; }
        .icon-success { color: #48bb78; }
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .appointment-card {
            background: #f7fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
            border-left: 4px solid #f5576c;
        }
        .appointment-card h3 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }
        .appointment-card h3::before {
            content: '📅';
            margin-right: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #4a5568;
        }
        .value {
            color: #2d3748;
            font-weight: 500;
        }
        .status-active {
            background: #c6f6d5;
            color: #22543d;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-cancelled {
            background: #fed7d7;
            color: #c53030;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .message {
            background: #e6fffa;
            border: 1px solid #81e6d9;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
            color: #234e52;
        }
        .message strong {
            color: #065f46;
        }
        .actions {
            text-align: center;
            padding: 24px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-cancel {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
        }
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .footer {
            background: #f7fafc;
            padding: 24px 30px;
            text-align: center;
            font-size: 14px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }
        .footer strong {
            color: #4a5568;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f5576c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 12px;
            }
            .header-active, .header-warning, .header-success, .content {
                padding: 24px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <div class='container' id='mainContainer'>
        <div class='header {$headerClass}'>
            <div class='icon " . ($isAlreadyCancelled ? 'icon-warning' : 'icon-active') . "'>
                " . ($isAlreadyCancelled ? '⚠️' : '📅') . "
            </div>
            <h1>" . ($isAlreadyCancelled ? 'Déjà annulé' : 'Annulation de rendez-vous') . "</h1>
            <p>" . ($isAlreadyCancelled ? 'Ce rendez-vous a déjà été annulé' : 'Confirmez l\'annulation de votre rendez-vous') . "</p>
        </div>
        
        <div class='content'>
            <div class='appointment-card'>
                <h3>Détails du rendez-vous</h3>
                <div class='detail-row'>
                    <span class='label'>Client</span>
                    <span class='value'>{$clientNameEsc}</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Date & heure</span>
                    <span class='value'>{$datetimeEsc}</span>
                </div>
                <div class='detail-row'>
                    <span class='label'>Statut</span>
                    <span class='" . ($isAlreadyCancelled ? 'status-cancelled' : 'status-active') . "'>{$statusEsc}</span>
                </div>
            </div>
            
            " . ($isAlreadyCancelled ? "
                <div class='message'>
                    <strong>ℹ️ Information</strong><br>
                    Ce rendez-vous a déjà été annulé précédemment. Le créneau est libre et disponible 
                    pour de nouvelles réservations.
                </div>
                <div class='actions'>
                    <a href='" . (getenv('FRONTEND_URL') ?: (getenv('APP_URL') ?: '/')) . "' class='btn btn-home'>← Retour à l'accueil</a>
                </div>
            " : "
                <div class='message'>
                    <strong>⚠️ Attention</strong><br>
                    Vous êtes sur le point d'annuler votre rendez-vous. Cette action libérera le créneau 
                    qui deviendra disponible pour d'autres personnes.
                </div>
                {$actionButton}
            ") . "
            
            <div class='loading' id='loadingDiv'>
                <div class='spinner'></div>
                <p>Annulation en cours...</p>
            </div>
        </div>
        
        <div class='footer'>
            <strong>Service de réservation - Bafodé Cissé</strong><br>
            Gestion sécurisée de vos rendez-vous
        </div>
    </div>

    <script>
        async function cancelAppointment(token) {
            document.getElementById('loadingDiv').style.display = 'block';
            document.querySelector('.actions').style.display = 'none';
            
            try {
                const response = await fetch('/api/appointments/cancel/' + token, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                if (response.ok) {
                    showSuccessPage();
                } else {
                    throw new Error('Erreur lors de l\\'annulation');
                }
            } catch (error) {
                document.getElementById('loadingDiv').style.display = 'none';
                document.querySelector('.actions').style.display = 'block';
                alert('Erreur lors de l\\'annulation. Veuillez réessayer.');
            }
        }
        
        function showSuccessPage() {
            document.getElementById('mainContainer').innerHTML = `
                <div class='header header-success'>
                    <div class='icon icon-success'>✓</div>
                    <h1>Annulation confirmée</h1>
                    <p>Votre rendez-vous a été annulé avec succès</p>
                </div>
                <div class='content'>
                    <div class='message'>
                        <strong>✓ Mission accomplie !</strong><br>
                        Le créneau a été libéré et est maintenant disponible pour de nouvelles réservations. 
                        Une notification par email a été automatiquement envoyée pour confirmer cette annulation.
                    </div>
                    <div class='actions'>
                        <a href='" . (getenv('FRONTEND_URL') ?: (getenv('APP_URL') ?: '/')) . "' class='btn btn-home'>← Retour à l'accueil</a>
                    </div>
                </div>
                <div class='footer'>
                    <strong>Service de réservation - Bafodé Cissé</strong><br>
                    Annulation confirmée avec succès
                </div>
            `;
        }
    </script>
</body>
</html>";
    }
}