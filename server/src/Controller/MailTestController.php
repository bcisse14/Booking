<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailTestController extends AbstractController
{
    #[Route('/test-mail', name: 'app_test_mail')]
    public function index(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('noreply@booking.test')
            ->to('test@example.com')
            ->subject('Test Mailtrap depuis Symfony')
            ->text('Ceci est un test envoyé via Mailtrap')
            ->html('<p><strong>Ceci est un test HTML</strong> envoyé via Mailtrap ✅</p>');

        $mailer->send($email);

        return new Response('Email envoyé ! Vérifie ta boîte Mailtrap 😉');
    }
}
