<?php

namespace App\EventSubscriber;


use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Twig\Environment;



final class ForgotPasswordEventSubscriber extends AbstractController implements EventSubscriberInterface
{
    private $mailer;
    private $twig;
    private $encoded;

    public function __construct(MailerInterface $mailer, Environment $twig, UserPasswordEncoderInterface $encoded)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->encoded = $encoded;
    }

    public static function getSubscribedEvents()
    {
        return [
            // Symfony 4.3 and inferior, use 'coop_tilleuls_forgot_password.create_token' event name
            CreateTokenEvent::class => 'onCreateToken',
            UpdatePasswordEvent::class => 'onUpdatePassword'
        ];
    }

    public function onCreateToken(CreateTokenEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();

        $message = (new Email())
            ->from('mohamed.bouzid1@esprit.tn')
            ->to($user->getEmail())
            ->subject('Reset your password')
            ->html($this->twig->render(
                'ResetPassword/mail.html.twig',
                [
                    'reset_password_url' => sprintf('http://localhost:8000/forgot_password/%s', $passwordToken->getToken()),
                ]
            ));
        $this->mailer->send($message);
    }

    public function onUpdatePassword(UpdatePasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();
        $plainPassword = $user->getPassword();
        $encoded = $this->encoded->encodePassword($user, $plainPassword);
        $user->setPassword($encoded);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
    }
}
