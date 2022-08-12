<?php

namespace App\Repository;

use App\Entity\Entreprise;
use App\Entity\LigneCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LigneCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method LigneCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method LigneCommande[]    findAll()
 * @method LigneCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LigneCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneCommande::class);
    }

    // /**
    //  * @return LigneCommande[] Returns an array of LigneCommande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LigneCommande
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getProductsPlusVendus($entreprise)
    {
        return $this->createQueryBuilder('l')
            ->select('sum(l.quantite) as q,s.id as ids')
            ->innerJoin('l.stock', 's')
            ->where('l.stock= s')
            ->where('s.Entreprise= :val')
            ->groupBy('l.stock')
            ->orderBy('q', 'DESC')
            ->setParameter('val', $entreprise)
            ->getQuery()
            ->getResult();
    }
}
