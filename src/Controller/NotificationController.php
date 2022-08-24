<?php

namespace App\Controller;

use App\Entity\Bon;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class NotificationController extends AbstractController
{
    /**
     * @param Security
     */
    private $_security;

    public function __construct(Security $security)
    {
        $this->_security = $security;
    }

    public function index(): Response
    {
        $notifications = $this->getDoctrine()->getRepository(Notification::class)->findBy(array('user' => $this->_security->getUser()), ['id' => 'DESC']);
        $notificationsNotVu = $this->getDoctrine()->getRepository(Notification::class)->findBy(array('user' => $this->_security->getUser(), 'vu' => false), ['id' => 'DESC']);
        $countNotifications =  count($notificationsNotVu);
        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize([$notifications, $countNotifications], 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);

        // On instancie la réponse
        $response = new Response($jsonContent);

        // On ajoute l'entête HTTP
        $response->headers->set('Content-Type', 'application/json');

        // On envoie la réponse
        return $response;
    }

    // modifier un bon
    public function updateNotifcations(): Response
    {
        $code = 200;
        $notifications = $this->getDoctrine()->getRepository(Notification::class)->findBy(array('user' => $this->_security->getUser()), ['id' => 'DESC']);
        foreach ($notifications as $n) {
            $n->setVu(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($n);
            $entityManager->flush();
        }
        return new Response('ok', $code);
    }
    //show entreprise 
    public function showEntreprise(?Commande $commande): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $lc = $entityManager->getRepository(LigneCommande::class)->findOneBy(array('commande' => $commande->getId()));
        if (!$commande ||  $this->_security->getUser() != $lc->getStock()->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $b = $this->getDoctrine()->getRepository(Commande::class)->findBy(['id' => $commande->getId()]);
            if ($b == null) {
                $code = 404;
                return new Response('error', $code);
            }
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($b, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
    //show poste 
    public function showPoste(?Commande $commande): Response
    {

        if (!$commande || ($commande->getStatus() == 'nouvelle' || $commande->getStatus() == 'confirmationClient' || $commande->getStatus() == 'annulee')) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $b = $this->getDoctrine()->getRepository(Commande::class)->findBy(['id' => $commande->getId()]);
            if ($b == null) {
                $code = 404;
                return new Response('error', $code);
            }
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($b, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
    //show livreur 
    public function showLivreur(?Commande $commande): Response
    {

        if (!$commande || $commande->getLivreur() != $this->_security->getUser()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $b = $this->getDoctrine()->getRepository(Commande::class)->findBy(['id' => $commande->getId()]);
            if ($b == null) {
                $code = 404;
                return new Response('error', $code);
            }
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($b, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
}
