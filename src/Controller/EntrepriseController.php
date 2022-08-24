<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\Media;
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

class EntrepriseController extends AbstractController
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
        $users = $this->getDoctrine()->getRepository(Entreprise::class)->findBy(array('isDeleted' => null));

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
    //Ajouter un compte Entreprise 

    /**
     * @Route("/entreprise/add", name="ajoutEntreprise", methods={"POST"})
     */
    public function createEntreprise(Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): Response
    {
        // On instancie un nouvel article
        $user = new Entreprise();

        // On décode les données envoyées
        $donnees = json_decode($request->getContent());

        // On hydrate l'objet
        $user->setEmail($donnees->email);
        $user->setRoles(['ROLE_ENTREPRISE']);
        $user->setPassword($donnees->password);
        $user->setNumTel($donnees->numTel);

        $user->setPhoto("avatar_entreprise.jpg");
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(null);
        $user->setIsDeleted(null);
        $user->setRestToken("");
        $user->setType(0);
        $user->setGouvernerat($donnees->gouvernerat);
        $user->setDelegation($donnees->delegation);
        $user->setNom($donnees->nom);

        $user->setNote(0);
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
            $media1 = new Media();
            $media2 = new Media();
            $media3 = new Media();

            //media1 
            $media1->setNom(1);
            $media1->setEntreprise($user);
            $media1->setImage('default1.png');

            $entityManager->persist($media1);
            //media2 
            $media2->setNom(2);
            $media2->setTitre('Titre');
            $media2->setDescription('Text');
            $media2->setEntreprise($user);
            $media2->setImage('default2.png');

            $entityManager->persist($media2);

            //media3
            $media3->setNom(3);
            $media3->setTitre('Titre');
            $media3->setDescription('Text');
            $media3->setEntreprise($user);
            $media3->setImage('default3.png');
            $entityManager->persist($media3);
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

    public function editEntreprise(?Entreprise $user, Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): Response
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
            $user->setEmail($request->get('email'));
            $user->setNumTel($request->get('numTel'));
            //$user->setPhoto($donnees->photo);
            $user->setUpdatedAt(new \DateTime());
            $user->setIsDeleted(null);
            $user->setRestToken("");
            $user->setGouvernerat($request->get('gouvernerat'));
            $user->setDelegation($request->get('delegation'));
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

    public function editAboutUs(?Entreprise $user, Request $request, ValidatorInterface $validator): Response
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
            $user->setTextAbout($request->get('text'));

            $errors = $validator->validate($user);
            if (count($errors) > 0 || strlen($user->getTextAbout()) < 10) {
                return new Response("failed", 400);
            } else {
                if ($request->files->get('assets')[0] != null) {
                    $image = $request->files->get('assets')[0];
                    $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                    $image->move(
                        $this->getParameter('images_directory'),
                        $fichier
                    );
                    $user->setPhotoAbout($fichier);
                } else {
                    $user->setPhotoAbout($user->getPhoto());
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

    public function showByID(?Entreprise $entreprise): Response
    {
        if (!$entreprise) {
            // On interdit l accés
            $code = 404;
            return new Response('error', $code);
        } else {
            $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->findBy(['id' => $entreprise->getId()]);
            if ($entreprise == null) {
                $code = 404;
                return new Response('error', $code);
            }
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($entreprise, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }

    // modifier note entreprise
    public function updateNote(?Entreprise $entreprise, Request $request, ValidatorInterface $validator): Response
    {
        $donnees = json_decode($request->getContent());

        // On initialise le code de réponse
        $code = 200;

        // Si le bon n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$entreprise || $donnees->note > 5 || $donnees->note < 0) {
            // On interdit l accés
            $code = 404;
            return new Response('error', $code);
        } else {
            $entreprise->setNote(($entreprise->getNote() + $donnees->note));
            $entreprise->setType($entreprise->getType() + 1);
            $errors = $validator->validate($entreprise);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($entreprise);
                $entityManager->flush();
                return new Response('ok', $code);
            }
        }
    }
}
