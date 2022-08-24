<?php

// src/Command/ExampleCommand.php
namespace App\Command;

use App\Entity\Notification;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// 1. Import the ORM EntityManager Interface
use Doctrine\ORM\EntityManagerInterface;

class ControlOrderCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:run-order';

    // 2. Expose the EntityManager in the class level
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        // 3. Update the value of the private entityManager variable through injection
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        // ...
    }

    // 4. Use the entity manager in the command code ...
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->entityManager;
        $repo = $em->getRepository('App\Entity\Commande');
        $date = new DateTime('-1 week');
        $res = $repo->ShowexpiredCommande($date);
        if ($res != null) {
            foreach ($res as $commande) {
                if ($commande->getStatus() == 'nouvelle' || $commande->getStatus() == 'affectationPoste' || $commande->getStatus() == 'confirmationClient') {


                    $notification = new Notification();
                    $notification->setUser($commande->getLignesCommandes()->first()->getStock()->getEntreprise());
                    $notification->setText('votre commande n a pas respecter les délais d une semaine pour arriver au client ');
                    $notification->setCommande($commande);
                    $notification->setVu(0);
                    $em->persist($notification);
                    $em->flush();
                    $commande->setStatus("annulee");
                } else  if ($commande->getStatus() == 'confirmationPoste' || $commande->getStatus() == 'affecterLivreur') {
                    //notifier l entreprise
                    $notification = new Notification();
                    $notification->setUser($commande->getLignesCommandes()->first()->getStock()->getEntreprise());
                    $notification->setText('votre commande n a pas respecter les délais d une semaine pour arriver au client ');
                    $notification->setCommande($commande);
                    $notification->setVu(0);
                    $em->persist($notification);
                    $em->flush();
                    // notifier le livreur 
                    if ($commande->getStatus() == 'affecterLivreur') {
                        $notification = new Notification();
                        $notification->setUser($commande->getLivreur());
                        $notification->setText('votre commande n a pas respecter les délais d une semaine pour arriver au client ');
                        $notification->setCommande($commande);
                        $notification->setVu(0);
                        $em->persist($notification);
                        $em->flush();

                        //puis notifier la poste
                        $notification = new Notification();
                        $notification->setUser($commande->getLivreur()->getPoste());
                        $notification->setText('le livreur' . $commande->getLivreur()->getNom() . ' ' . $commande->getLivreur()->getPrenom() . 'n a pas respecter les délais d une semaine pour délivrer la commande ');
                        $notification->setCommande($commande);
                        $notification->setVu(0);
                        $em->persist($notification);
                        $em->flush();
                    } else {
                        $notification = new Notification();
                        $notification->setText('votre commande n a pas respecter les délais d une semaine pour arriver au client ');
                        $notification->setCommande($commande);
                        $notification->setVu(0);
                        $em->persist($notification);
                        $em->flush();
                    }

                    $commande->setStatus("retour");
                }
                $em->persist($commande);
                $em->flush();
            }
        }
        return 0;
    }
}
