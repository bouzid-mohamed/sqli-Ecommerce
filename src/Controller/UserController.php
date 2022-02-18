<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;




class UserController extends AbstractController
{

    public function index(): Response
    {
    $users = $this->getDoctrine()->getRepository(User::class)->findAll();

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

    /**
    * @Route("/api/users/add", name="ajout", methods={"POST"})
    */
    public function create(Request $request,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder) : Response
    {
 
   
        // On instancie un nouvel article
        $user = new User();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles($donnees->roles);
        $user->setPassword($donnees->password);
        $user->setPhoneNumber($donnees->phoneNumber);
        $user->setFirstName($donnees->firstName);
        $user->setLastName($donnees->lastName);
        $user->setAddress($donnees->address);
        $user->setState($donnees->state);
        $user->setCity($donnees->city);
        $user->setCountry($donnees->country);
        $user->setPhoto($donnees->photo);
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else{
        $plainPassword = $user->getPassword();
        $encoded = $encoder->encodePassword($user, $plainPassword);
        $user->setPassword($encoded);

        // On sauvegarde en base
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        // On retourne la confirmation
        return new Response($user->getId(), 201);
        }
    }


    public function getSingle(User $user)
    {
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

    public function editUser (?User $user, Request $request,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder) : Response
    {
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On initialise le code de réponse
        $code = 200;

        // Si l'article n'est pas trouvé
        if(!$user){
            // On instancie un nouvel article
            $user = new User();
            // On change le code de réponse
            $code = 201 ;
        }

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles($donnees->roles);
        //$user->setPassword($donnees->password);
        $user->setPhoneNumber($donnees->phoneNumber);
        $user->setFirstName($donnees->firstName);
        $user->setLastName($donnees->lastName);
        $user->setAddress($donnees->address);
        $user->setState($donnees->state);
        $user->setCity($donnees->city);
        $user->setCountry($donnees->country);
        $user->setPhoto($donnees->photo);

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return new Response('Failed', 401);

        }else {

        // On sauvegarde en base
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return new Response('ok', $code);


        }  
    }

    public function removeUser(User $user)
    {
    $entityManager = $this->getDoctrine()->getManager();
    $entityManager->remove($user);
    $entityManager->flush();
    return new Response('ok');
    }






}
