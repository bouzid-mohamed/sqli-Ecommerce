<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\Media;
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


class MediaController extends AbstractController
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
        $medias = $this->getDoctrine()->getRepository(Media::class)->findBy(array('entreprise' => $this->_security->getUser()));


        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize($medias, 'json', [
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

    public function getMediasEntreprise(?Entreprise $entreprise): Response
    {
        $medias = $this->getDoctrine()->getRepository(Media::class)->findBy(array('entreprise' => $entreprise));


        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);

        // On convertit en json
        $jsonContent = $serializer->serialize($medias, 'json', [
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

    public function editMedia(?Media $media, Request $request, ValidatorInterface $validator): Response
    {
        // On initialise le code de réponse
        $code = 200;

        // Si le bon n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$media ||  $this->_security->getUser()->getId() != $media->getEntreprise()->getId()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', $code);
        } else {
            // On hydrate l'objet
            // On hydrate l'objet
            $media->setNom($media->getNom());
            $media->setTitre($request->get('titre'));
            $media->setDescription($request->get('description'));
            $media->setUrl($request->get('url'));

            $errors = $validator->validate($media);
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
                    $media->setImage($fichier);
                } else {
                    $media->setImage($media->getImage());
                }
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($media);
                $entityManager->flush();
                $encoders = [new JsonEncoder()];
                $normalizers = [new ObjectNormalizer()];
                $serializer = new Serializer($normalizers, $encoders);
                $jsonContent = $serializer->serialize($media, 'json', [
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

    public function showByID(?Media $media): Response
    {
        if (!$media) {
            // On interdit l accés
            $code = 404;
            return new Response('error', $code);
        } else {
            $media = $this->getDoctrine()->getRepository(Media::class)->findBy(['id' => $media->getId()]);
            if ($media == null) {
                $code = 404;
                return new Response('error', $code);
            }
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);
            $jsonContent = $serializer->serialize($media, 'json', [
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
