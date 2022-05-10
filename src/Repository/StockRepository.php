<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    // /**
    //  * @return Stock[] Returns an array of Stock objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function removeAllStockProduct($value)
    {
        $entityManager = $this->getEntityManager();

        $queryBuilder = $entityManager->createQueryBuilder();

        $query = $queryBuilder->update('App\Entity\Stock', 's')
            ->set('s.deletedAt', ':deletedAt')
            ->where('s.produit = :editId')
            ->setParameter('deletedAt', new \DateTime())
            ->setParameter('editId', $value)
            ->getQuery();
        $result = $query->execute();
    }

    public function getAllSearch($entreprise, $nom)
    {
        return $this->createQueryBuilder('s')
            ->join('s.produit', 'p')
            ->join('p.categorie', 'c')
            ->where('s.produit = p')
            ->where('p.categorie= c')
            ->where('(s.taille LIKE :taille OR s.quantite LIKE :taille OR  p.nom LIKE :paramNom OR c.nom LIKE :paramNom )AND p.deletedAt IS NULL AND p.Entreprise = :paramUser ')
            ->orderBy('s.id', 'DESC')
            ->setParameter('paramUser', $entreprise)
            ->setParameter('paramNom', '%' . $nom . '%')
            ->setParameter('taille', $nom)
            ->getQuery()
            ->getResult();
    }
}
