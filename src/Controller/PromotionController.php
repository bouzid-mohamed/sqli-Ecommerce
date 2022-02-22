<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Security;
use App\Entity\Promotion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    // ajouter un promotion nb : apres l auth en tant que entreprise
    public function addPromotion(Request $request, ValidatorInterface $validator): Response
    {
        $promotion = new Promotion();
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $promotion->setNom($donnees->nom);
        $promotion->setDescription($donnees->description);
        $promotion->setBanniere($donnees->banniere);
        $promotion->setDateDebut(new \DateTime($donnees->dateDebut));
        $promotion->setDateFin(new \DateTime($donnees->dateFin));
        $promotion->setDeletedAt(null);
        $promotion->setPourcentage($donnees->pourcentage);
        $promotion->setEntreprise($this->_security->getUser());
        $errors = $validator->validate($promotion);
        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else {
            // On sauvegarde en base
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($promotion);
            $entityManager->flush();
            // On retourne la confirmation
            return new Response($promotion->getId(), 201);
        }
    }
}
