<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Produit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Knp\Component\Pager\PaginatorInterface; // Nous appelons le bundle KNP Paginator


class CategorieController extends AbstractController
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
        $categories = $this->getDoctrine()->getRepository(Categorie::class)->findBy(array('deletedAt' => null, 'entreprise' => $this->_security->getUser()), ['id' => 'DESC']);

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

    public function getAllPagination(PaginatorInterface $paginator, Request $request): Response
    {
        if ($request->get('search')) {
            $donnees = $this->getDoctrine()->getRepository(Categorie::class)->getAllSearch($this->_security->getUser(), $request->get('search'));
        } else {
            $donnees = $this->getDoctrine()->getRepository(Categorie::class)->findBy(array('deletedAt' => null, 'catPere' => null, 'entreprise' => $this->_security->getUser()), ['id' => 'DESC']);
        }
        $categories = $paginator->paginate(
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
        $jsonContent = $serializer->serialize([$categories, 'pagination' =>   ceil($categories->getTotalItemCount() / 16)], 'json', [

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


    // ajouter une categorie nb : apres l auth en tant que entreprise
    public function addCategorie(Request $request, ValidatorInterface $validator): Response
    {
        $categorie = new Categorie();
        // On décode les données envoyées
        $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $entityManager = $this->getDoctrine()->getManager();

        $verif = true;
        if ($donnees->categoriePere != null) {

            $cat = $entityManager->getRepository(Categorie::class)->findOneBy(array('id' => $donnees->categoriePere, 'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
            $verif = $this->verifFils($cat);
            $categorie->setCatPere($cat);
        } else {
            $categorie->setCatPere(null);
        }

        $categorie->setEntreprise($this->_security->getUser());
        $categorie->setNom($donnees->nom);
        $errors = $validator->validate($categorie);
        if (count($errors) > 0 || $verif == false) {
            return new Response("failed", 400);
        } else {
            // On sauvegarde en base
            $entityManager->persist($categorie);
            $entityManager->flush();
            // On retourne la confirmation
            return new Response($categorie->getId(), 201);
        }
    }

    // update categorie 
    public function updateCategorie(?Categorie $categorie, Request $request, ValidatorInterface $validator): Response
    {
        $donnees = json_decode($request->getContent());

        // On initialise le code de réponse
        $code = 200;

        // Si le bon n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$categorie ||  $this->_security->getUser() != $categorie->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            $entityManager = $this->getDoctrine()->getManager();

            if ($donnees->categoriePere != null) {
                $cat = $entityManager->getRepository(Categorie::class)->findOneBy(array('id' => $donnees->categoriePere, 'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
                $categorie->setCatFils($cat);
            } else {
                $categorie->setCatFils(null);
            }
            $categorie->setEntreprise($this->_security->getUser());
            $categorie->setNom($donnees->nom);

            $errors = $validator->validate($categorie);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($categorie);
                $entityManager->flush();
                return new Response('ok', $code);
            }
        }
    }

    // remove categorie
    public function deleteCategorie(?Categorie $categorie)
    {
        $code = 200;
        if (!$categorie ||  $this->_security->getUser()->getId() != $categorie->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $categorie->setDeletedAt(new \DateTime());
            $prods = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $this->_security->getUser(), 'categorie' => $categorie));
            if ($prods != null) {
                foreach ($prods as $pr) {
                    $pr->setDeletedAt(new \DateTime());
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($pr);

                    $entityManager->flush();
                }
            }

            if ($categorie->getCatFils() != null) {
                $this->getDoctrine()->getRepository(Categorie::class)->removeAllSubCats($categorie->getCatFils());
                foreach ($categorie->getCatFils() as $c) {
                    $produits = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $this->_security->getUser(), 'categorie' => $c));
                    if ($produits != null) {
                        foreach ($produits as $p) {
                            $p->setDeletedAt(new \DateTime());
                            $entityManager = $this->getDoctrine()->getManager();
                            $entityManager->persist($p);

                            $entityManager->flush();
                        }
                    }
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($categorie);
            $entityManager->flush();

            return new Response('ok', $code);
        }
    }


    public function verifFils(Categorie $cat)
    {
        if ($cat != null) {
            if ($cat->getCatPere() == null)
                return true;
            else if ($cat->getCatPere()->getCatPere() == null)
                return true;
            else return false;
        } else return false;
    }
}
