<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    // /**
    //  * @return Client[] Returns an array of Client objects
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
    public function findOneBySomeField($value): ?Client
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getAllCommandeClient($entreprise, $cl)
    {
        return $this->createQueryBuilder('client')
            ->leftJoin('client.commande', 'c')
            ->leftJoin('c.Lignescommande', 'l')
            ->leftJoin('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('c.client = client.id')
            ->where('l.stock= s.id')
            ->where('(c.client =: paramClient  )AND s.Entreprise=:val  ')
            ->orderBy('c.id', 'DESC')
            ->setParameter('val', $entreprise)
            ->setParameter('paramClient', $cl)
            ->getQuery()
            ->getResult();
    }
}
