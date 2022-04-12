<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Commande|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commande|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commande[]    findAll()
 * @method Commande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    // /**
    //  * @return Commande[] Returns an array of Commande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getAllCommande($value)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.Lignescommande', 'l')
            ->leftJoin('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('s.Entreprise=:val')
            ->orderBy('c.id', 'DESC')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
    }
    public function getAllCommandeRolePoste()
    {
        $status = array("confirmationPoste", "affectationPoste", "finie", "affecterLivreur");
        return $this->createQueryBuilder('c')
            ->leftJoin('c.Lignescommande', 'l')
            ->leftJoin('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('c.status IN (:status)')
            ->orderBy('c.id', 'DESC')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }
}
