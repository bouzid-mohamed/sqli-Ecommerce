<?php

namespace App\Controller;

use App\Entity\Entreprise;
use Symfony\Component\Security\Core\Security;
use App\Entity\Promotion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface; // Nous appelons le bundle KNP Paginator


class PromotionController extends AbstractController
{

    /**
     * @param Security
     */
    private $_security;

    public function __construct(Security $security)
    {
        $this->_security = $security;
    }
    // afficher la liste des promotions d une entreprise 
    public function index(): Response
    {
        $categories = $this->getDoctrine()->getRepository(Promotion::class)->findBy(array('deletedAt' => null, 'entreprise' => $this->_security->getUser()));


        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize($categories, 'json', [
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

    public function getAll(PaginatorInterface $paginator, Request $request): Response
    {
        if ($request->get('search')) {
            $categories = $this->getDoctrine()->getRepository(Promotion::class)->getAllSearch($this->_security->getUser(), $request->get('search'));
        } else {
            $categories = $this->getDoctrine()->getRepository(Promotion::class)->findBy(array('deletedAt' => null, 'entreprise' => $this->_security->getUser()), ['id' => 'DESC']);
        }

        $promotions = $paginator->paginate(
            $categories, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            10 // Nombre de résultats par page
        );

        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize([$promotions, 'pagination' =>   ceil($promotions->getTotalItemCount() / 10)], 'json', [

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

    // ajouter un promotion nb : apres l auth en tant que entreprise
    public function addPromotion(Request $request, ValidatorInterface $validator): Response
    {
        $promotion = new Promotion();
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $promotion->setNom($request->get('nom'));
        $promotion->setDescription($request->get('description'));
        //$promotion->setBanniere($request->get('bannière'));
        $promotion->setDateDebut(new \DateTime($request->get('dateDebut')));
        $promotion->setDateFin(new \DateTime($request->get('dateFin')));
        $promotion->setDeletedAt(null);
        $promotion->setPourcentage($request->get('pourcentage'));
        $promotion->setEntreprise($this->_security->getUser());
        $errors = $validator->validate($promotion);
        if (count($errors) > 0 && $request->files->get('assets')[0] != null) {
            return new Response("failed", 400);
        } else {
            $image = $request->files->get('assets');
            $fichier = md5(uniqid()) . '.' . $image->guessExtension();
            $image->move(
                $this->getParameter('images_directory'),
                $fichier
            );
            $promotion->setBanniere($fichier);

            // On sauvegarde en base
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($promotion);
            $entityManager->flush();
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($promotion, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }

    // modifier une promotion
    public function updatePromotion(?Promotion $promotion, Request $request, ValidatorInterface $validator): Response
    {
        // On initialise le code de réponse
        $code = 200;

        // Si le bon n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$promotion ||  $this->_security->getUser() != $promotion->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            // On hydrate l'objet
            $promotion->setNom($request->get('nom'));
            $promotion->setDescription($request->get('description'));
            //$promotion->setBanniere($request->get('bannière'));
            $promotion->setDateDebut(new \DateTime($request->get('dateDebut')));
            $promotion->setDateFin(new \DateTime($request->get('dateFin')));
            $promotion->setDeletedAt(null);
            $promotion->setPourcentage($request->get('pourcentage'));
            $promotion->setEntreprise($this->_security->getUser());
            $errors = $validator->validate($promotion);
            if (count($errors) > 0) {
                return new Response("failed", 400);
            } else {

                if ($request->files->get('assets')[0] != null) {
                    $image = $request->files->get('assets')[0];
                    $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                    $image->move(
                        $this->getParameter('images_directory'),
                        $fichier
                    );
                    $promotion->setBanniere($fichier);
                } else {
                    $promotion->setBanniere($promotion->getBanniere());
                }
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($promotion);
                $entityManager->flush();
                $encoders = [new JsonEncoder()];
                $normalizers = [new ObjectNormalizer()];
                $serializer = new Serializer($normalizers, $encoders);
                $jsonContent = $serializer->serialize($promotion, 'json', [
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
    // remove promotion
    public function deletePromotion(?Promotion $promotion)
    {
        $code = 200;
        if (!$promotion ||  $this->_security->getUser()->getId() != $promotion->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $promotion->setDeletedAt(new \DateTime());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($promotion);
            $entityManager->flush();
            return new Response('ok', $code);
        }
    }

    //show stock
    public function show(?Promotion $promotion): Response
    {

        if (!$promotion ||  $this->_security->getUser()->getId() != $promotion->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error access', $code);
        } else {
            $p = $this->getDoctrine()->getRepository(Promotion::class)->findBy(['id' => $promotion->getId(), 'deletedAt' => null]);
            if ($p == null) {
                $code = 404;
                return new Response('error', $code);
            }
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($p, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }

    //get promotions page home 
    public function getPromotionsHome(?Entreprise $e): Response
    {

        if (!$e) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $p = $this->getDoctrine()->getRepository(Promotion::class)->findBy(['entreprise' => $e, 'deletedAt' => null], ['id' => 'DESC'], 5);

            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($p, 'json', [
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
