<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Entreprise;
use App\Entity\Image;
use App\Entity\LigneCommande;
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
use Knp\Component\Pager\PaginatorInterface; // Nous appelons le bundle KNP Paginator
use Symfony\Component\Validator\Constraints\NotNull;

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
    public function index(PaginatorInterface $paginator, Request $request): Response
    {

        if ($request->get('search')) {
            $donnees = $this->getDoctrine()->getRepository(Produit::class)->getAllSearch($this->_security->getUser(), $request->get('search'));
        } else {

            if ($request->get('order')) {
                if ($request->get('order') == 1) {
                    $or = 'ASC';
                } else {
                    $or = 'DESC';
                }
            }

            if (!$request->get('filter')) {
                if (!$request->get('order')) {
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $this->_security->getUser()->getId()), ['id' => 'DESC']);
                } else {
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $this->_security->getUser()->getId()), ['prix' => $or]);
                }
            } else {
                if (!$request->get('order')) {
                    $pieces = explode(",", $request->query->get('filter'));
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->getAllFilter($pieces, $this->_security->getUser());
                } else {
                    $pieces = explode(",", $request->query->get('filter'));
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->getAllFilterOrder($pieces, $this->_security->getUser(), $or);
                }
            }
        }
        $produits = $paginator->paginate(
            $donnees, // Requête contenant les données à paginer (ici nos articles)
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
        $jsonContent = $serializer->serialize([$produits, 'pagination' =>   ceil($produits->getTotalItemCount() / 10)], 'json', [

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


    // liste des prods pour une entreprise
    public function getAll(): Response
    {
        $produits = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $this->_security->getUser()->getId()), ['id' => 'DESC']);

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
        //  $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $produit->setNom($request->get('nom'));
        $produit->setDescription($request->get('description'));
        $produit->setprix(intval($request->get('prix')));
        $produit->setCreatedAt(new \DateTime());
        $produit->setUpdatedAt(null);
        $produit->setDeletedAt(null);
        $produit->setEntreprise($this->_security->getUser());
        //recuperer le promotion
        $entityManager = $this->getDoctrine()->getManager();
        $promotion = $entityManager->getRepository(Promotion::class)->findOneBy(array('id' => intval($request->get('promotion')), 'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
        if ($promotion  != null) {
            $produit->setPromotion($promotion);
        }
        $categorie = $entityManager->getRepository(Categorie::class)->findOneBy(array('id' => intval($request->get('categorie')), 'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
        $produit->setCategorie($categorie);

        //fin image upload

        $errors = $validator->validate($produit);
        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else {
            // On sauvegarde en base
            $entityManager->persist($produit);
            $entityManager->flush();

            //image upload
            if ($images = $request->files->get('assets'))


                foreach ($images as $image) {

                    $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                    $image->move(
                        $this->getParameter('images_directory'),
                        $fichier
                    );
                    $img = new Image();
                    $img->setNom($fichier);
                    $img->setProduit($produit);
                    $entityManager->persist($img);
                    $entityManager->flush();
                }

            //retourner un json
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($produit, 'json', [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }



    public function updateProduit(?Produit $produit, Request $request, ValidatorInterface $validator): Response
    {

        // On initialise le code de réponse
        $code = 200;

        // Si le stock n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$produit ||  $this->_security->getUser() != $produit->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            $produit->setNom($request->get('nom'));
            $produit->setDescription($request->get('description'));
            $produit->setprix(intval($request->get('prix')));
            $produit->setCreatedAt(new \DateTime());
            $produit->setUpdatedAt(null);
            $produit->setDeletedAt(null);
            $produit->setEntreprise($this->_security->getUser());
            //recuperer le promotion
            $entityManager = $this->getDoctrine()->getManager();
            $promotion = $entityManager->getRepository(Promotion::class)->findOneBy(array('id' => intval($request->get('promotion')), 'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
            if ($promotion  != null) {
                $produit->setPromotion($promotion);
            }
            $categorie = $entityManager->getRepository(Categorie::class)->findOneBy(array('id' => intval($request->get('categorie')), 'deletedAt' => null, 'entreprise' => $this->_security->getUser()->getId()));
            $produit->setCategorie($categorie);
            $errors = $validator->validate($produit);
            if (count($errors) > 0) {
                return new Response("failed", 400);
            } else {
                // On sauvegarde en base
                $entityManager->persist($produit);
                $entityManager->flush();

                //image upload
                if ($images = $request->files->get('assets'))


                    foreach ($images as $image) {

                        $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                        $image->move(
                            $this->getParameter('images_directory'),
                            $fichier
                        );
                        $img = new Image();
                        $img->setNom($fichier);
                        $img->setProduit($produit);
                        $entityManager->persist($img);
                        $entityManager->flush();
                    }
                //retourner un json
                $encoders = [new JsonEncoder()];
                $normalizers = [new ObjectNormalizer()];
                $serializer = new Serializer($normalizers, $encoders);
                $jsonContent = $serializer->serialize($produit, 'json', [
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

    // remove product
    public function deleteProduit(?Produit $produit, StockRepository $StockRepository)
    {
        $code = 200;
        if (!$produit ||  $this->_security->getUser()->getId() != $produit->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $produit->setDeletedAt(new \DateTime());
            //  $StockRepository->removeAllStockProduct($produit->getId()) ;
            $this->getDoctrine()->getRepository(Stock::class)->removeAllStockProduct($produit->getId());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($produit);
            $entityManager->flush();
            foreach ($produit->getStoks() as  $s) {
                $s->setDeletedAt(new \DateTime());
                $entityManager->persist($s);
                $entityManager->flush();
            }
            return new Response(1, $code);
        }
    }

    public function show(?Produit $produit): Response
    {

        if (!$produit ||  $this->_security->getUser()->getId() != $produit->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $p = $this->getDoctrine()->getRepository(Produit::class)->findBy(['id' => $produit->getId(), 'deletedAt' => null]);
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

    // remove image
    public function deleteImage(?Image $image)
    {
        $code = 200;
        if (!$image ||  $this->_security->getUser()->getId() != $image->getProduit()->getEntreprise()->getId() || $image->getProduit()->getImages()[1] == null) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            $nom = $image->getNom();
            unlink($this->getParameter('images_directory') . '/' . $nom);
            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();
            return new Response(1, $code);
        }
    }

    //get all filter 
    // liste des stocks pour une entreprise
    public function getAllFilter(PaginatorInterface $paginator, Request $request): Response
    {
        $donnees = $this->getDoctrine()->getRepository(Produit::class)->getAllFilter($request->query->get('filter'), $this->_security->getUser());
        $produits = $paginator->paginate(
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
        $jsonContent = $serializer->serialize([$produits, 'pagination' =>   ceil($produits->getTotalItemCount() / 16)], 'json', [

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

    //get all filter 
    // liste des stocks pour une entreprise
    public function relatedProducts(?Produit $produit, ?Entreprise $entreprise): Response
    {
        $donnees = $this->getDoctrine()->getRepository(Produit::class)->relatedProducts($produit, $entreprise);

        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize($donnees, 'json', [

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


    // liste des produits pour une entreprise (front)
    public function produitsEntreprise(?Entreprise $entreprise, PaginatorInterface $paginator, Request $request): Response
    {
        if (!$entreprise) {
            $code = 404;
            return new Response('error', $code);
        }

        if ($request->get('search')) {
            $donnees = $this->getDoctrine()->getRepository(Produit::class)->getAllSearch($entreprise, $request->get('search'));
        } else {

            if ($request->get('order')) {
                if ($request->get('order') == 1) {
                    $or = 'ASC';
                } else {
                    $or = 'DESC';
                }
            }

            if (!$request->get('filter')) {
                if (!$request->get('order')) {
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $entreprise->getId()), ['id' => 'DESC']);
                } else {
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->findBy(array('deletedAt' => null, 'Entreprise' => $entreprise->getId()), ['prix' => $or]);
                }
            } else {
                if (!$request->get('order')) {
                    $pieces = explode(",", $request->query->get('filter'));
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->getAllFilter($pieces, $entreprise);
                } else {
                    $pieces = explode(",", $request->query->get('filter'));
                    $donnees = $this->getDoctrine()->getRepository(Produit::class)->getAllFilterOrder($pieces, $entreprise, $or);
                }
            }
        }
        $produits = $paginator->paginate(
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
        $jsonContent = $serializer->serialize([$produits, 'pagination' =>   ceil($produits->getTotalItemCount() / 16), 'entreprise' => $entreprise], 'json', [

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

    public function showProduitEntreprise($entreprise,  $produit): Response
    {
        //  $e = new Entreprise();
        $e = $this->getDoctrine()->getRepository(Entreprise::class)->findBy(['id' => $entreprise]);
        $p = $this->getDoctrine()->getRepository(Produit::class)->findBy(['id' => $produit, 'deletedAt' => null, 'Entreprise' => $e]);


        //      $p = $this->getDoctrine()->getRepository(Produit::class)->findBy(['id' => $produit->getId(), 'deletedAt' => null]);
        if ($p == null || $e == null) {
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


    //produits page home
    public function showProduitHome(?Entreprise $entreprise): Response
    {
        $meilleurPrix = $this->getDoctrine()->getRepository(Produit::class)->findBy(['deletedAt' => null, 'Entreprise' => $entreprise], ['prix' => 'ASC'], 8);
        $nouveaux = $this->getDoctrine()->getRepository(Produit::class)->findBy(['deletedAt' => null, 'Entreprise' => $entreprise], ['id' => 'DESC'], 8);
        $avecPromos = $this->getDoctrine()->getRepository(Produit::class)->getAllAvecPromo($entreprise);
        $vendu = $this->getDoctrine()->getRepository(LigneCommande::class)->getProductsPlusVendus($entreprise);
        $p = array();
        $p2 = array();
        $i = 0;
        $ip2 = 0;

        foreach ($vendu as $v) {

            $a = $this->getProductStock($v['ids']);
            if (!in_array($a->getId(), $p2) && $a->getDeletedAt() == null) {
                $p2[$ip2] =  $a->getId();
                $ip2++;
                $p[$i] = $a;
                $i++;
            }
        }

        if (!$entreprise) {
            $code = 404;
            return new Response('error', $code);
        }
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize(['0' => $meilleurPrix,    '1'  => $nouveaux, '2' => $avecPromos, '3' => $p], 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        $response = new Response($jsonContent);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function getProductStock(?int $s)
    {
        $s = $this->getDoctrine()->getRepository(Stock::class)->findOneBy(['id' => $s]);

        return ($s->getProduit());
    }
}
