<?php

namespace App\Controller;

use App\Entity\Bon;
use App\Entity\Entreprise;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Knp\Component\Pager\PaginatorInterface; // Nous appelons le bundle KNP Paginator


class BonController extends AbstractController
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
        $bons = $this->getDoctrine()->getRepository(Bon::class)->findBy(array('entreprise' => $this->_security->getUser()->getId()), ['id' => 'DESC']);

        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize($bons, 'json', [
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

    public function getAllpagination(PaginatorInterface $paginator, Request $request): Response
    {

        if ($request->get('search')) {
            $bonsData = $this->getDoctrine()->getRepository(Bon::class)->getAllSearch($this->_security->getUser(), $request->get('search'));
        } else {
            $bonsData = $this->getDoctrine()->getRepository(Bon::class)->findBy(array('entreprise' => $this->_security->getUser()->getId()), ['id' => 'DESC']);
        }
        $bons = $paginator->paginate(
            $bonsData, // Requête contenant les données à paginer (ici nos articles)
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
        $jsonContent = $serializer->serialize([$bons, 'pagination' =>   ceil($bons->getTotalItemCount() / 10)], 'json', [
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
    // ajouter un bon nb : apres l auth en tant que entreprise
    public function addBon(Request $request, ValidatorInterface $validator): Response
    {
        $bon = new Bon();
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $bon->setCode($donnees->code);
        $bon->setReduction($donnees->reduction);
        $bon->setEntreprise($this->_security->getUser());
        $errors = $validator->validate($bon);
        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else {
            // On sauvegarde en base
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($bon);
            $entityManager->flush();
            // On retourne la confirmation
            return new Response($bon->getId(), 201);
        }
    }


    // modifier un bon
    public function updateBon(?Bon $bon, Request $request, ValidatorInterface $validator): Response
    {
        $donnees = json_decode($request->getContent());

        // On initialise le code de réponse
        $code = 200;

        // Si le bon n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$bon ||  $this->_security->getUser() != $bon->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            $bon->setCode($donnees->code);
            $bon->setReduction($donnees->reduction);

            $errors = $validator->validate($bon);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($bon);
                $entityManager->flush();
                return new Response('ok', $code);
            }
        }
    }

    public function deleteBon(Bon $bon)
    {
        if (!$bon ||  $this->_security->getUser() != $bon->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($bon);
            $entityManager->flush();
            return new Response('ok', 200);
        }
    }

    public function show(?Bon $bon): Response
    {
        if (!$bon ||  $this->_security->getUser()->getId() != $bon->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $b = $this->getDoctrine()->getRepository(Bon::class)->findBy(['id' => $bon->getId()]);
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
    public function verifBon($entreprise,  $bon): Response
    {
        //  $e = new Entreprise();
        $e = $this->getDoctrine()->getRepository(Entreprise::class)->findBy(['id' => $entreprise]);
        $bon = $this->getDoctrine()->getRepository(Bon::class)->findBy(['code' => $bon,  'entreprise' => $e]);


        //      $p = $this->getDoctrine()->getRepository(Produit::class)->findBy(['id' => $produit->getId(), 'deletedAt' => null]);
        if ($bon == null || $e == null) {
            $code = 404;
            return new Response('error', $code);
        }
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($bon, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        $response = new Response($jsonContent);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
