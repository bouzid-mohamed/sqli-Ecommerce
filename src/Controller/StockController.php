<?php

namespace App\Controller;

use App\Entity\Produit;
use Symfony\Component\Security\Core\Security;
use App\Entity\Stock;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface; // Nous appelons le bundle KNP Paginator


class StockController extends AbstractController
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
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        if ($request->get('search')) {
            $stocksdata = $this->getDoctrine()->getRepository(Stock::class)->getAllSearch($this->_security->getUser(), $request->get('search'));
        } else {
            $stocksdata = $this->getDoctrine()->getRepository(Stock::class)->findBy(array('deletedAt' => null, 'Entreprise' => $this->_security->getUser()->getId()), ['id' => 'DESC']);
        }


        $stocks = $paginator->paginate(
            $stocksdata, // Requête contenant les données à paginer (ici nos articles)
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
        $jsonContent = $serializer->serialize([$stocks, 'pagination' =>   ceil($stocks->getTotalItemCount() / 16)], 'json', [
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
    public function addStock(Request $request, ValidatorInterface $validator): Response
    {
        $stock = new Stock();
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $stock->setCouleur($donnees->couleur);
        $stock->setQuantite($donnees->quantite);
        $stock->setTaille($donnees->taille);
        $stock->setEntreprise($this->_security->getUser());
        //recuperer le produit 
        $entityManager = $this->getDoctrine()->getManager();
        $prod = $entityManager->getRepository(Produit::class)->findOneBy(array('id' => $donnees->produit, 'deletedAt' => null, 'Entreprise' => $this->_security->getUser()->getId()));
        $stock->setProduit($prod);
        $errors = $validator->validate($stock);
        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else {
            // On sauvegarde en base
            $entityManager->persist($stock);
            $entityManager->flush();
            // On retourne la confirmation
            return new Response($stock->getId(), 201);
        }
    }

    // modifier un stock
    public function updateStock(?Stock $stock, Request $request, ValidatorInterface $validator): Response
    {
        $donnees = json_decode($request->getContent());

        // On initialise le code de réponse
        $code = 200;

        // Si le stock n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$stock ||  $this->_security->getUser() != $stock->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            $stock->setCouleur($donnees->couleur);
            $stock->setQuantite($donnees->quantite);
            $stock->setTaille($donnees->taille);
            $stock->setEntreprise($this->_security->getUser());
            //recuperer le produit 
            $entityManager = $this->getDoctrine()->getManager();
            $prod = $entityManager->getRepository(Produit::class)->findOneBy(array('id' => $donnees->produit, 'deletedAt' => null, 'Entreprise' => $this->_security->getUser()->getId()));
            $stock->setProduit($prod);

            $errors = $validator->validate($stock);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager->persist($stock);
                $entityManager->flush();
                return new Response('ok', $code);
            }
        }
    }

    // remove stock
    public function deleteStock(?Stock $stock)
    {
        $code = 200;
        if (!$stock ||  $this->_security->getUser()->getId() != $stock->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $stock->setDeletedAt(new \DateTime());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($stock);
            $entityManager->flush();
            return new Response('ok', $code);
        }
    }

    //show stock
    public function show(?Stock $stock): Response
    {

        if (!$stock ||  $this->_security->getUser()->getId() != $stock->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error access', $code);
        } else {
            $s = $this->getDoctrine()->getRepository(Stock::class)->findBy(['id' => $stock->getId(), 'deletedAt' => null]);
            if ($s == null) {
                $code = 404;
                return new Response('error', $code);
            }
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($s, 'json', [
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
