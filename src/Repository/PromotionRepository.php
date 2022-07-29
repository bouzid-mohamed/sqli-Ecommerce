<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Promotion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Promotion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Promotion[]    findAll()
 * @method Promotion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    // /**
    //  * @return Promotion[] Returns an array of Promotion objects
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
    public function findOneBySomeField($value): ?Promotion
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getAllSearch($entreprise, $nom)
    {
        return $this->createQueryBuilder('p')
            ->where('(p.nom LIKE :paramNom OR p.description LIKE :paramNom OR  p.pourcentage =  :paramReduction  OR DATE_FORMAT(p.dateDebut , :dformat) = :paramReduction OR DATE_FORMAT(p.dateFin , :dformat) = :paramReduction	)  AND p.entreprise = :paramUser AND p.deletedAt IS NULL ')
            ->orderBy('p.id', 'DESC')
            ->setParameter('paramUser', $entreprise)
            ->setParameter('paramNom', '%' . $nom . '%')
            ->setParameter('paramReduction',  $nom)
            ->setParameter('dformat',  '%m/%d/%Y')
            ->getQuery()
            ->getResult();
    }
}
