<?php

namespace App\Controller;

use App\Entity\Bon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;

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
    
    /**
     * @Route("/bon", name="bon")
     */
    public function index(): Response
    {
        return $this->render('bon/index.html.twig', [
            'controller_name' => 'BonController',
        ]);
    }

    public function addBon(Request $request,ValidatorInterface $validator): Response{
        $bon = new Bon() ;
         // On décode les données envoyées
         $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $bon->setCode($donnees->code);
        $bon->setReduction($donnees->reduction);
        $bon->setEntreprise($this->_security->getUser()) ;
        $errors = $validator->validate($bon);
        if (count($errors) > 0) {
            return new Response("failed", 400);
        } else{
        // On sauvegarde en base
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($bon);
        $entityManager->flush();
        // On retourne la confirmation
        return new Response($bon->getId(), 201);
        }

    }




}
