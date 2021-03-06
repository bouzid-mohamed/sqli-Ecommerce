<?php

namespace App\Repository;

use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Categorie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Categorie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Categorie[]    findAll()
 * @method Categorie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    // /**
    //  * @return Categorie[] Returns an array of Categorie objects
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
    public function findOneBySomeField($value): ?Categorie
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    // remove all sub categ when categ is deleted 
    public function removeAllSubCats($value)
    {
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQueryBuilder();
        $query = $queryBuilder->update('App\Entity\Categorie', 'c')
            ->set('c.deletedAt', ':deletedAt')
            ->where('c.id = :editId')
            ->setParameter('deletedAt', new \DateTime())
            ->setParameter('editId', $value)
            ->getQuery()->execute();
    }

    public function getAllSearch($entreprise, $nom)
    {
        return $this->createQueryBuilder('c')
            ->where('(c.nom LIKE :paramNom )AND c.deletedAt IS NULL AND c.entreprise = :paramUser ')
            ->orderBy('c.id', 'DESC')
            ->setParameter('paramUser', $entreprise)
            ->setParameter('paramNom', '%' . $nom . '%')
            ->getQuery()
            ->getResult();
    }
}
