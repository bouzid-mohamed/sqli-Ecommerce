<?php

namespace App\Controller;

use App\Entity\Bon;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Livreur;
use App\Entity\Stock;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;

class CommandeController extends AbstractController
{

    /**
     * @param Security
     */
    private $_security;

    private $mailer;
    private $twig;

    public function __construct(Security $security, MailerInterface $mailer, Environment $twig)
    {
        $this->_security = $security;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    // ajouter une commande
    public function addCommande(Request $request, ValidatorInterface $validator): Response
    {
        $commande = new Commande();
        // On décode les données envoyées 
        $donnees = json_decode($request->getContent());
        // On hydrate l'objet
        $commande->setClient($this->_security->getUser());
        $commande->setStatus('nouvelle');
        $commande->setNumTel($this->_security->getUser()->getNumTel());
        $commande->setAddresse($donnees->addresse);
        $commande->setGouvernerat($donnees->gouvernerat);
        $commande->setDelegation($donnees->delegation);
        $commande->setPays($donnees->pays);
        $commande->setCreatedAt(new \DateTime());
        $commande->setUpdatedAt(null);
        $data =  $donnees->lignesCommande;
        $p = 0;
        foreach ($data as $ligne) {
            $stock = $this->getDoctrine()->getRepository(Stock::class)->findOneBy(['id' => $ligne->id]);
            $prix = $stock->getProduit()->getPrix();
            $promo = 0;
            if ($stock->getProduit()->getPromotion() != null) {
                $promo = $stock->getProduit()->getPromotion()->getPourcentage();
            }
            $reduction = $prix * $promo / 100;
            $p += $ligne->quantite * ($prix - $reduction);
        }

        $errors = $validator->validate($commande);
        if (count($errors) > 0 && $this->verifCommande($data) == false) {
            return new Response("failed", 400);
        } else {
            // On sauvegarde en base
            $entityManager = $this->getDoctrine()->getManager();
            $stockCmd = $this->getDoctrine()->getRepository(Stock::class)->findOneBy(['id' =>  $data[0]->id]);

            $bon = $entityManager->getRepository(Bon::class)->findOneBy(array('code' => $donnees->bon, 'entreprise' =>  $stockCmd->getProduit()->getEntreprise()));
            $reductionBon = 0;
            if ($bon != null) {
                $reductionBon = $bon->getReduction();
            }
            if ($p >  $reductionBon) {
                $commande->setPrix($p - $reductionBon);
            } else {
                $commande->setPrix(0);
            }
            $entityManager->persist($commande);
            $entityManager->flush();
            //stoker les ligne de commande 
            foreach ($data as $ligne) {
                $lc = new LigneCommande();
                $lc->setQuantite($ligne->quantite);
                $lc->setStock($stock);
                $lc->setCommande($commande);
                $entityManager->persist($lc);
                $entityManager->flush();
            }
            // On retourne la confirmation
            return new Response($commande->getId(), 201);
        }
    }
    // pour verfier si la commande valide ou non => tous les produits sont de la meme entreprise
    public function verifCommande($data)
    {
        $entreprise = $data[0]->getStock()->getProduit()->getEntreprise();
        $count = 0;
        foreach ($data as $d) {
            $entreprisecomp = $d->getStock()->getProduit()->getEntreprise();
            if ($entreprisecomp != $entreprise) {
                $count = 1;
            }
        }
        if ($count == 0)
            return true;
        else return false;
    }
    // modifier le status d'une commande => confirmer !! role entreprise
    public function confirmerCommande(?Commande $commande,  ValidatorInterface $validator): Response
    {
        // On initialise le code de réponse
        $code = 200;
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager = $this->getDoctrine()->getManager();
        $lc = $entityManager->getRepository(LigneCommande::class)->findOneBy(array('commande' => $commande->getId()));


        // Si le stock n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$commande ||  $this->_security->getUser() != $lc->getStock()->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', 401);
        } else {
            // On hydrate l'objet
            $commande->setStatus("confirmationClient");

            $errors = $validator->validate($commande);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager->persist($commande);
                $entityManager->flush();
                $message = (new Email())
                    ->from('mohamed.bouzid1@esprit.tn')
                    ->to($commande->getClient()->getEmail())
                    ->subject(' Confirmation de la commande ')
                    ->html($this->twig->render(
                        'commande/mailConfirmer.html.twig',
                        ['text' => 'Confirmation de la commande ']

                    ));
                $this->mailer->send($message);
                return new Response('ok', $code);
            }
        }
    }

    // modifier le status d'une commande => affecter à la poste  !! role entreprise
    public function affecterposte(?Commande $commande,  ValidatorInterface $validator): Response
    {
        // On initialise le code de réponse
        $code = 200;
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager = $this->getDoctrine()->getManager();
        $lc = $entityManager->getRepository(LigneCommande::class)->findOneBy(array('commande' => $commande->getId()));


        // Si le stock n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$commande ||  $this->_security->getUser() != $lc->getStock()->getEntreprise()) {
            // On interdit l accés
            $code = 403;
            return new Response('error', 401);
        } else {
            // On hydrate l'objet
            $commande->setStatus("affectationPoste");

            $errors = $validator->validate($commande);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager->persist($commande);
                $entityManager->flush();
                $message = (new Email())
                    ->from('mohamed.bouzid1@esprit.tn')
                    ->to($commande->getClient()->getEmail())
                    ->subject(' Commande affercter à la poste')
                    ->html($this->twig->render(
                        'commande/mailConfirmer.html.twig',
                        ['text' => 'Commande affercter à la poste']

                    ));
                $this->mailer->send($message);
                return new Response('ok', $code);
            }
        }
    }

    // modifier le status d'une commande => confirmer par la poste !! authentifier en tant que poste !! et affectation d un livreur pour passer la commmande 
    public function confirmationPoste(?Commande $commande, Request $request, ValidatorInterface $validator): Response
    {
        // On initialise le code de réponse
        $code = 200;
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager = $this->getDoctrine()->getManager();
        $donnees = json_decode($request->getContent());
        $livreur = new Livreur();


        // Si le stock n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$commande) {
            // On interdit l accés
            $code = 403;
            return new Response('error', 401);
        } else {
            // On hydrate l'objet
            $commande->setStatus("confirmationPoste");
            // affecter un livreur pour passer la commande 
            $livreur = $entityManager->getRepository(Livreur::class)->findOneBy(array('id' => $donnees->livreur));
            $commande->setLivreur($livreur);

            $errors = $validator->validate($commande);
            if (count($errors) > 0 && $livreur == null) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager->persist($commande);
                $entityManager->flush();
                $message = (new Email())
                    ->from('mohamed.bouzid1@esprit.tn')
                    ->to($commande->getClient()->getEmail())
                    ->subject(' Commande confirmée par la poste')
                    ->html($this->twig->render(
                        'commande/mailConfirmer.html.twig',
                        ['text' => 'Commande confirmée par la poste']

                    ));
                $this->mailer->send($message);
                return new Response('ok', $code);
            }
        }
    }

    // modifier le status d'une commande => anullee !! authentifier en tant que poste ou entreprise ou livreur 
    public function annuleeCommande(?Commande $commande,  ValidatorInterface $validator): Response
    {
        // On initialise le code de réponse
        $code = 200;
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager = $this->getDoctrine()->getManager();
        $lc = $entityManager->getRepository(LigneCommande::class)->findOneBy(array('commande' => $commande->getId()));
        // role poste ou livreur ou entreprise qui a cette commande 
        if (!$commande || (!((in_array('ROLE_ENTREPRISE', $this->_security->getUser()->getRoles(), true)) || ($this->_security->getUser() == $lc->getStock()->getEntreprise())))) {
            // On interdit l accés
            $code = 403;
            return new Response('error', 401);
        } else {
            // On hydrate l'objet
            $commande->setStatus("annulee");
            $errors = $validator->validate($commande);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager->persist($commande);
                $entityManager->flush();
                $message = (new Email())
                    ->from('mohamed.bouzid1@esprit.tn')
                    ->to($commande->getClient()->getEmail())
                    ->subject('Commande annulée')
                    ->html($this->twig->render(
                        'commande/mailConfirmer.html.twig',
                        ['text' => 'Commande annulée']

                    ));
                $this->mailer->send($message);
                return new Response('ok', $code);
            }
        }
    }

    // finir une commande 
    public function finirCommande(?Commande $commande, ValidatorInterface $validator): Response
    {
        // On initialise le code de réponse
        $code = 200;
        // Si le stock n'est pas trouvé et l utilisateur n a pas le privllege de modifier
        if (!$commande || ($this->_security->getUser() != $commande->getLivreur())) {
            // On interdit l accés
            $code = 403;
            return new Response('error', 401);
        } else {
            // On hydrate l'objet
            $commande->setStatus("annulee");


            $errors = $validator->validate($commande);
            if (count($errors) > 0) {
                return new Response('Failed', 401);
            } else {
                // On sauvegarde en base
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($commande);
                $entityManager->flush();
                $message = (new Email())
                    ->from('mohamed.bouzid1@esprit.tn')
                    ->to($commande->getClient()->getEmail())
                    ->subject(' MERCI D AVOIR EFFECTUÉ VOS ACHATS SUR ....... ')
                    ->html($this->twig->render(
                        'commande/mailConfirmer.html.twig',
                        ['text' => 'MERCI D AVOIR EFFECTUÉ VOS ACHATS SUR .......']

                    ));
                $this->mailer->send($message);
                return new Response('ok', $code);
            }
        }
    }
    public function getAllpagination(PaginatorInterface $paginator, Request $request): Response
    {
        $commandesData = $this->getDoctrine()->getRepository(Commande::class)->getAllCommande($this->_security->getUser());
        $commandes = $paginator->paginate(
            $commandesData, // Requête contenant les données à paginer (ici nos articles)
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
        $jsonContent = $serializer->serialize([$commandes, 'pagination' =>   ceil($commandes->getTotalItemCount() / 16)], 'json', [
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
}
