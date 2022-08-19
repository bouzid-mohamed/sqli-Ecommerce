<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Produit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Produit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Produit[]    findAll()
 * @method Produit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    // /**
    //  * @return Produit[] Returns an array of Produit objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Produit
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getAllFilter($arr, $entreprise)
    {

        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->leftJoin('c.catPere', 'pere')
            ->leftJoin('pere.catPere', 'gp')
            ->where('p.categorie= c')
            ->where('c.catPere= pere')
            ->where('pere.catPere = gp')
            ->where('( c.id IN (:status) OR pere.id IN (:status) OR gp.id IN (:status) ) AND p.deletedAt IS NULL AND p.Entreprise = :paramUser ')
            ->orderBy('c.id', 'DESC')
            ->setParameter('status', $arr)
            ->setParameter('paramUser', $entreprise)
            ->getQuery()
            ->getResult();
    }

    public function getAllFilterOrder($arr, $entreprise, $or)
    {

        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->leftJoin('c.catPere', 'pere')
            ->leftJoin('pere.catPere', 'gp')
            ->where('p.categorie= c')
            ->where('c.catPere= pere')
            ->where('pere.catPere = gp')
            ->where('( c.id IN (:status) OR pere.id IN (:status) OR gp.id IN (:status) ) AND p.deletedAt IS NULL AND p.Entreprise = :paramUser ')
            ->orderBy('p.prix', $or)
            ->setParameter('status', $arr)
            ->setParameter('paramUser', $entreprise)
            ->getQuery()
            ->getResult();
    }

    public function getAllSearch($entreprise, $nom)
    {
        return $this->createQueryBuilder('p')
            ->join('p.categorie', 'c')
            ->where('p.categorie= c')
            ->where('(p.nom LIKE :paramNom OR c.nom LIKE :paramNom )AND p.deletedAt IS NULL AND p.Entreprise = :paramUser ')
            ->orderBy('p.id', 'DESC')
            ->setParameter('paramUser', $entreprise)
            ->setParameter('paramNom', '%' . $nom . '%')
            ->getQuery()
            ->getResult();
    }

    public function getAllAvecPromo($entreprise)
    {
        return $this->createQueryBuilder('p')
            ->where('(p.promotion IS NOT NULL  )AND p.deletedAt IS NULL AND p.Entreprise = :paramUser ')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(8)
            ->setParameter('paramUser', $entreprise)
            ->getQuery()
            ->getResult();
    }
}
