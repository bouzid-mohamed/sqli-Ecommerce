<?php

namespace App\Controller;

use App\Entity\Client;
use phpDocumentor\Reflection\Types\Null_;
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

class ClientController extends AbstractController
{
    /**
     * @param Security
     */
    private $_security;

    public function __construct(Security $security)
    {
        $this->_security = $security;
    }
    //get liste entreprise
    public function index(): Response
    {
        $users = $this->getDoctrine()->getRepository(Client::class)->findBy(array('isDeleted' => null));

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

    //Ajouter un compte Client
    /**
     * @Route("/client/add", name="ajoutClient", methods={"POST"})
     */
    public function createClient(Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): Response
    {
        // On instancie un nouvel article
        $user = new Client();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles(['ROLE_CLIENT']);
        $user->setPassword($donnees->password);
        $user->setNumTel($donnees->numTel);

        $user->setPhoto('avatar.jpg');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(null);
        $user->setIsDeleted(null);
        $user->setRestToken("");
        $user->setType(1);
        $user->setNom($donnees->nom);
        $user->setPrenom($donnees->prenom);
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return new Response($errors[0], 400);
        } else {
            $plainPassword = $user->getPassword();
            $encoded = $encoder->encodePassword($user, $plainPassword);
            $user->setPassword($encoded);

            // On sauvegarde en base
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            //retourner un json
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($user, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }


    // edit entreprise 

    public function editClient(?Client $user, Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): Response
    {
        // On initialise le code de réponse
        $code = 200;

        // Si le bon n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$user ||  $this->_security->getUser()->getId() != $user->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            // On hydrate l'objet
            $user->setNom($request->get('nom'));
            $user->setPrenom($request->get('prenom'));
            $user->setNumTel($request->get('numTel'));
            $user->setEmail($request->get('email'));
            $user->setUpdatedAt(new \DateTime());
            $user->setIsDeleted(null);
            $user->setRestToken("");
            $match = true;
            if ($request->get('newPassword') != '') {
                $plainPassword = $request->get('newPassword');
                $encoded = $encoder->encodePassword($user, $plainPassword);
                $currentPasswordGet = $request->get('password');
                if ($user->getPassword() == null) {
                    $match = true;
                } else {
                    $match = $encoder->isPasswordValid($user,  $currentPasswordGet);
                }
                $user->setPassword($encoded);
            }
            $errors = $validator->validate($user);
            if (count($errors) > 0 || $match == false) {
                return new Response("failed", 400);
            } else {

                if ($request->files->get('assets')[0] != null) {
                    $image = $request->files->get('assets')[0];
                    $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                    $image->move(
                        $this->getParameter('images_directory'),
                        $fichier
                    );
                    $user->setPhoto($fichier);
                } else {
                    $user->setPhoto($user->getPhoto());
                }
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                $encoders = [new JsonEncoder()];
                $normalizers = [new ObjectNormalizer()];
                $serializer = new Serializer($normalizers, $encoders);
                $jsonContent = $serializer->serialize($user, 'json', [
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
}
