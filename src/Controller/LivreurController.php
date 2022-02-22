<?php

namespace App\Controller;

use App\Entity\Livreur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class LivreurController extends AbstractController
{
    /**
     * @param Security
     */
    private $_security;

    public function __construct(Security $security)
    {
        $this->_security = $security;
    }
    //get liste livreur
    public function index(): Response
    {
        $users = $this->getDoctrine()->getRepository(Livreur::class)->findBy(array('isDeleted' => null));

        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize($users, 'json', [
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

    // ajouter compte livreur 

    /**
     * @Route("/api/poste/addlivreur", name="ajoutLivreur", methods={"POST"})
     */
    public function createLivreur(Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): Response
    {


        // On instancie un nouvel article
        $user = new Livreur();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles(["ROLE_LIVREUR"]);
        $user->setPassword($donnees->password);
        $user->setNumTel($donnees->numTel);

        $user->setPhoto($donnees->photo);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(null);
        $user->setIsDeleted(null);
        $user->setRestToken("");
        $user->setType(1);
        $user->setNom($donnees->nom);
        $user->setPrenom($donnees->prenom);
        $user->setTypePermis($donnees->typePermis);
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else {
            $plainPassword = $user->getPassword();
            $encoded = $encoder->encodePassword($user, $plainPassword);
            $user->setPassword($encoded);

            // On sauvegarde en base
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // On retourne la confirmation
            return new Response('ok', 201);
        }
    }

    // edit client

    public function editLivreur(?Livreur $user, Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): Response
    {
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On initialise le code de réponse
        $code = 200;

        // Si le user n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$user ||  $this->_security->getUser()->getId() != $user->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            //$user->setEmail($donnees->email);

            $user->setNumTel($donnees->numTel);
            $user->setPhoto($donnees->photo);
            $user->setUpdatedAt(new \DateTime());
            $user->setIsDeleted(null);
            $user->setRestToken("");
            $user->setNom($donnees->nom);
            $user->setPrenom($donnees->prenom);
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                $user->setTypePermis($donnees->typePermis);
                return new Response('ok', $code);
            }
        }
    }
}
