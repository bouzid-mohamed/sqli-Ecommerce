<?php

namespace App\Controller;

use App\Entity\Commande;
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
use Knp\Component\Pager\PaginatorInterface; // Nous appelons le bundle KNP Paginator


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
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        if ($request->get('search')) {
            $donnees  = $this->getDoctrine()->getRepository(Livreur::class)->getAllSearch($this->_security->getUser()->getId(), $request->get('search'));
        } else {
            $donnees = $this->getDoctrine()->getRepository(Livreur::class)->findBy(array('isDeleted' => null, 'poste' => $this->_security->getUser()->getId()));
        }
        $users = $paginator->paginate(
            $donnees, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            16 // Nombre de résultats par page
        );

        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize([$users, 'pagination' =>   ceil($users->getTotalItemCount() / 16)], 'json', [

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

    public function getAll(): Response
    {
        $users = $this->getDoctrine()->getRepository(Livreur::class)->findBy(array('isDeleted' => null, 'poste' => $this->_security->getUser()->getId()));


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

        $user->setPhoto('default.jpg');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(null);
        $user->setIsDeleted(null);
        $user->setRestToken("");
        $user->setType(1);
        $user->setNom($donnees->nom);
        $user->setPrenom($donnees->prenom);
        $user->setTypePermis($donnees->typePermis);
        $user->setPoste($this->_security->getUser());
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
            return new Response($user->getId(), 201);
        }
    }

    // edit entreprise 

    public function editLivreur(?Livreur $user, Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): Response
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
            $user->setNumTel($request->get('numTel'));
            $user->setTypePermis($request->get('typePermis'));

            $user->setUpdatedAt(new \DateTime());
            $user->setIsDeleted(null);
            $user->setRestToken("");

            $match = true;
            if ($request->get('newPassword') != '') {
                $plainPassword = $request->get('newPassword');
                $encoded = $encoder->encodePassword($user, $plainPassword);
                $currentPasswordGet = $request->get('password');
                $match = $encoder->isPasswordValid($user,  $currentPasswordGet);
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
    //get liste des commandes 
    public function getAllCommandes(PaginatorInterface $paginator, Request $request): Response
    {
        if ($request->get('search')) {
            $donnees = $this->getDoctrine()->getRepository(Commande::class)->getAllCommandeRoleLivreurSearch($this->_security->getUser()->getId(), $request->get('search'));
        } else {
            $donnees = $this->getDoctrine()->getRepository(Commande::class)->findBy(array('livreur' => $this->_security->getUser()->getId()), ['id' => 'DESC']);
        }
        $users = $paginator->paginate(
            $donnees, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            16 // Nombre de résultats par page
        );

        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize([$users, 'pagination' =>   ceil($users->getTotalItemCount() / 16)], 'json', [

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
    //remove livreur from poste 
    public function removeLivreurFromPost(?Livreur $user)
    {
        $code = 200;
        if (!$user ||  $this->_security->getUser()->getId() != $user->getPoste()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $user->setIsDeleted(new \DateTime());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            return new Response('ok', $code);
        }
    }
}
