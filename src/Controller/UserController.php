<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Client;
use App\Entity\Livreur;
use App\Entity\Entreprise;
use App\Entity\Poste;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;


class UserController extends AbstractController
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
    * @Route("/api/client/add", name="ajoutClient", methods={"POST"})
    */
    public function createClient(Request $request,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder) : Response
    {
        // On instancie un nouvel article
        $user = new Client();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles($donnees->roles);
        $user->setPassword($donnees->password);
        $user->setNumTel($donnees->numTel);
       
        $user->setPhoto($donnees->photo);
        $user->setCreatedAt(new \DateTime()) ;
        $user->setUpdatedAt(null) ;
        $user->setIsDeleted(null) ;
        $user->setRestToken("") ;
        $user->setType(1) ;
        $user->setNom($donnees->nom) ;
        $user->setPrenom($donnees->prenom) ;
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
        return new Response('ok', 201);
        }
    }

    // ajouter compte livreur 

    /**
    * @Route("/api/livreur/add", name="ajoutLivreur", methods={"POST"})
    */
    public function createLivreur(Request $request,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder) : Response
    {
 
   
        // On instancie un nouvel article
        $user = new Livreur();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles($donnees->roles);
        $user->setPassword($donnees->password);
        $user->setNumTel($donnees->numTel);
       
        $user->setPhoto($donnees->photo);
        $user->setCreatedAt(new \DateTime()) ;
        $user->setUpdatedAt(null) ;
        $user->setIsDeleted(null) ;
        $user->setRestToken("") ;
        $user->setType(1) ;
        $user->setNom($donnees->nom) ;
        $user->setPrenom($donnees->prenom) ;
        $user->setTypePermis($donnees->typePermis) ;
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
        return new Response('ok', 201);
        }
    }

    //Ajouter un compte Entreprise 

     /**
    * @Route("/api/entreprise/add", name="ajoutEntreprise", methods={"POST"})
    */
    public function createEntreprise(Request $request,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder) : Response
    {
        // On instancie un nouvel article
        $user = new Entreprise();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles($donnees->roles);
        $user->setPassword($donnees->password);
        $user->setNumTel($donnees->numTel);
       
        $user->setPhoto($donnees->photo);
        $user->setCreatedAt(new \DateTime()) ;
        $user->setUpdatedAt(null) ;
        $user->setIsDeleted(null) ;
        $user->setRestToken("") ;
        $user->setType(1) ;
        $user->setGouvernerat($donnees->gouvernerat) ;
        $user->setDelegation($donnees->delegation) ;
        $user->setNote($donnees->note) ;
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
        return new Response('ok', 201);
        }
    }


    //Ajouter un compte Poste 

     /**
    * @Route("/api/poste/add", name="ajoutPoste", methods={"POST"})
    */
    public function createPoste(Request $request,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder) : Response
    {
        // On instancie un nouvel article
        $user = new Poste();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles($donnees->roles);
        $user->setPassword($donnees->password);
        $user->setNumTel($donnees->numTel);
       
        $user->setPhoto($donnees->photo);
        $user->setCreatedAt(new \DateTime()) ;
        $user->setUpdatedAt(null) ;
        $user->setIsDeleted(null) ;
        $user->setRestToken("") ;
        $user->setType(1) ;
        $user->setGouvernerat($donnees->gouvernerat) ;
        $user->setDelegation($donnees->delegation) ;
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
        return new Response('ok', 201);
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
        if(!$user ||  $this->_security->getUser()->getId() != $user->getId() ){
            // On interdit l accés
            $code = 401;
            return new Response('error', $code);
        }else {



        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles($donnees->roles);
        $user->setNumTel($donnees->numTel);
        $user->setPhoto($donnees->photo);
        //$user->setCreatedAt() ;
        $user->setUpdatedAt(new \DateTime()) ;
        $user->setIsDeleted(null) ;
        $user->setRestToken("") ;
        // si il s agit d un client 
        if(get_class($user) == get_class($l = new Client())){
            $user->setNom($donnees->nom) ; 
            $user->setPrenom($donnees->prenom) ;

        } elseif (get_class($user) == get_class($l = new Livreur())){  // si il s agit d un Livreur
            $user->setNom($donnees->nom) ;
            $user->setPrenom($donnees->prenom) ;
            $user->setTypePermis($donnees->typePermis) ;
        } elseif (get_class($user) == get_class($l = new Entreprise())){ // si il s agit d une entreprise
            $user->setGouvernerat($donnees->gouvernerat) ;
            $user->setDelegation($donnees->delegation) ;
            $user->setNote($donnees->note) ;
        }  elseif (get_class($user) == get_class($l = new Poste())){   // si il s agit d une poste
            $user->setGouvernerat($donnees->gouvernerat) ;
            $user->setDelegation($donnees->delegation) ;
        }
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
    }

    public function removeUser(User $user)
    {
    $entityManager = $this->getDoctrine()->getManager();
    $entityManager->remove($user);
    $entityManager->flush();
    return new Response('ok');
    }



}
