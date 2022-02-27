<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Image;
use App\Entity\Produit;
use App\Entity\Promotion;
use App\Entity\Stock;
use App\Repository\StockRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function PHPUnit\Framework\isNull;

class ProduitController extends AbstractController
{

    /**
     * @param Security
     */
    private $_security;

    public function __construct(Security $security)
    {
        $this->_security = $security;
    }

    // liste des stocks pour une entreprise
    public function index(): Response
    {
        $produits = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $this->_security->getUser()->getId()));

        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize($produits, 'json', [
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

    // ajouter un stock nb : apres l auth en tant que entreprise
    public function addProduit(Request $request, ValidatorInterface $validator): Response
    {
        $produit = new Produit();
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $produit->setNom($donnees->nom);
        $produit->setDescription($donnees->description);
        $produit->setprix($donnees->prix);
        $produit->setCreatedAt(new \DateTime());
        $produit->setUpdatedAt(null);
        $produit->setDeletedAt(null);
        $produit->setEntreprise($this->_security->getUser());
        //recuperer le promotion
        $entityManager = $this->getDoctrine()->getManager();
        $promotion = $entityManager->getRepository(Promotion::class) ->findOneBy(array('id'=>$donnees->promotion,'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
        if( $promotion  == null ){
        $produit->setPromotion($promotion);
    }
        $categorie = $entityManager->getRepository(Categorie::class)->findOneBy(array('id'=>$donnees->categorie,'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
        $produit->setCategorie($categorie);
        //image upload
        // $images = $request->files->get('images');
        // foreach ($images as $image){

        //     $fichier=md5(uniqid()) . '.' . $image->guessExtension();
        //     $image->move(
        //         $this->getParameter('images_directory'),
        //         $fichier
        //     );
        //     $img=new Image();
        //     $img->setNom($fichier);
        //     $produit->addImage($img);

        // }
        //fin image upload
        $errors = $validator->validate($produit);
        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else {
            // On sauvegarde en base
            $entityManager->persist($produit);
            $entityManager->flush();
            // On retourne la confirmation
            return new Response($produit->getId(), 201);
        }
    }

    public function updateProduit(?Produit $produit, Request $request, ValidatorInterface $validator): Response
    {
        $donnees = json_decode($request->getContent());

        // On initialise le code de réponse
        $code = 200;

        // Si le stock n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$produit ||  $this->_security->getUser() != $produit->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            $produit->setNom($donnees->nom);
            $produit->setDescription($donnees->description);
            $produit->setprix($donnees->prix);
            // $produit->setCreatedAt();
            $produit->setUpdatedAt(new \DateTime());
            $produit->setDeletedAt(null);
            $produit->setEntreprise($this->_security->getUser());
            //recuperer le promotion
            $entityManager = $this->getDoctrine()->getManager();
            $promotion = $entityManager->getRepository(Promotion::class) ->findOneBy(array('id'=>$donnees->categorie,'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
            $produit->setPromotion($promotion);
            $categorie = $entityManager->getRepository(Categorie::class)->findOneBy(array('id'=>$donnees->categorie,'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
            $produit->setCategorie($categorie);
            $errors = $validator->validate($produit);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager->persist($produit);
                $entityManager->flush();
                return new Response('ok', $code);
            }
        }
    }

    // remove product
    public function deleteProduit(? Produit $produit,StockRepository $StockRepository)
    {
        $code = 200;
        if (!$produit ||  $this->_security->getUser()->getId() != $produit->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $produit->setDeletedAt(new \DateTime());
          //  $StockRepository->removeAllStockProduct($produit->getId()) ;
            $this->getDoctrine()->getRepository(Stock::class)->removeAllStockProduct($produit->getId()) ;
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($produit);
            $entityManager->flush();
            return new Response('ok', $code);
        }
    }
}
