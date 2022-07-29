<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\OrderBy;
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
        $status = array("confirmationPoste", "affectationPoste", "finie", "affecterLivreur", 'retour');
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

    public function getAllCommandeRolePosteSearch($nom)
    {
        $status = array("confirmationPoste", "affectationPoste", "finie", "affecterLivreur", 'retour');
        return $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')
            ->leftJoin('c.Lignescommande', 'l')
            ->leftJoin('l.stock', 's')
            ->leftJoin('s.Entreprise', 'e')
            ->where('l.commande= c.id')
            ->where('c.client = client.id')
            ->where('l.stock= s.id')
            ->where('s.entreprise = e')
            ->where('(c.id = :nom OR e.nom LIKE :paramNom OR  client.nom LIKE :paramNom OR client.email LIKE :paramNom OR client.numTel LIKE :paramNom OR c.gouvernerat LIKE :paramNom OR c.delegation LIKE :paramNom OR c.prix LIKE :paramNom OR c.status LIKE :paramNom   )')
            ->orderBy('c.id', 'DESC')
            ->setParameter('paramNom', '%' . $nom . '%')
            ->setParameter('nom',  $nom)
            ->getQuery()
            ->getResult();
    }
    public function getAllCommandeRoleLivreurSearch($user, $nom)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')
            ->leftJoin('c.Lignescommande', 'l')
            ->leftJoin('l.stock', 's')
            ->leftJoin('s.Entreprise', 'e')
            ->where('l.commande= c.id')
            ->where('c.client = client.id')
            ->where('l.stock= s.id')
            ->where('s.entreprise = e')
            ->where('(c.id = :nom OR e.nom LIKE :paramNom OR  client.nom LIKE :paramNom OR client.email LIKE :paramNom OR client.numTel LIKE :paramNom OR e.numTel LIKE :paramNom OR c.gouvernerat LIKE :paramNom OR c.delegation LIKE :paramNom OR c.addresse LIKE :paramNom OR c.prix LIKE :paramNom OR c.status LIKE :paramNom   )AND c.livreur=:user  ')
            ->orderBy('c.id', 'DESC')
            ->setParameter('paramNom', '%' . $nom . '%')
            ->setParameter('nom',  $nom)
            ->setParameter('user',  $user)
            ->getQuery()
            ->getResult();
    }

    public function getAllSearch($entreprise, $nom)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')
            ->leftJoin('c.Lignescommande', 'l')
            ->leftJoin('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('c.client = client.id')
            ->where('l.stock= s.id')
            ->where('(c.id = :nom OR client.nom LIKE :paramNom OR client.email LIKE :paramNom OR client.numTel LIKE :paramNom OR c.gouvernerat LIKE :paramNom OR c.delegation LIKE :paramNom OR c.prix LIKE :paramNom OR c.status LIKE :paramNom   )AND s.Entreprise=:val  ')
            ->orderBy('c.id', 'DESC')
            ->setParameter('val', $entreprise)
            ->setParameter('paramNom', '%' . $nom . '%')
            ->setParameter('nom',  $nom)
            ->getQuery()
            ->getResult();
    }
    //dashboard enterprise
    public function getEntrepriseStatics($entreprise)
    {
        return $this->createQueryBuilder('c')
            ->select('count(distinct((c.id) ))AS nbrCmd,MONTH((c.createdAt )) AS month ,YEAR((c.createdAt )) AS year ,c.status AS status')
            ->join('c.Lignescommande', 'l')
            ->join('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('s.Entreprise=:val  ')
            ->groupBy('month,year,status')
            ->addSelect('sum(c.prix)/count(l.id) AS prix')
            ->setParameter('val', $entreprise)
            ->getQuery()
            ->getResult();
    }
    //dashboard enterprise
    public function getEntrepriseStaticsPrix($entreprise)
    {
        return $this->createQueryBuilder('c')
            ->select(' c.status AS status ,sum(c.prix)/count(l.id) AS prix')
            ->leftJoin('c.Lignescommande', 'l')
            ->leftJoin('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('s.Entreprise=:val  ')
            ->groupBy('status')
            ->setParameter('val', $entreprise)
            ->getQuery()
            ->getResult();
    }
    //dashboard poste
    public function getPostStatics()
    {
        $status = array("confirmationPoste", "affectationPoste", "finie", "affecterLivreur", "retour");

        return $this->createQueryBuilder('c')
            ->select('count(distinct((c.id) ))AS nbrCmd,MONTH((c.createdAt )) AS month ,YEAR((c.createdAt )) AS year ,c.status AS status')
            ->join('c.Lignescommande', 'l')
            ->join('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('  c.status IN (:status) ')
            ->groupBy('month,year,status')
            ->addSelect('sum(c.prix)/count(l.id) AS prix')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }
    //dashboard livreur
    public function getLivreurStatics($user)
    {

        return $this->createQueryBuilder('c')
            ->select('count(distinct((c.id) ))AS nbrCmd,MONTH((c.createdAt )) AS month ,YEAR((c.createdAt )) AS year ,c.status AS status')
            ->join('c.Lignescommande', 'l')
            ->join('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('   c.livreur=:user ')
            ->groupBy('month,year,status')
            ->addSelect('sum(c.prix)/count(l.id) AS prix')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    // fidelité client 
    public function getClientsStatics($entreprise)
    {


        return $this->createQueryBuilder('c')
            ->select('count(distinct((c.id) ))AS nbrCmd,client.email AS email ,client.nom AS nom , client.prenom AS prenom,client.numTel AS numTel , client.id AS idClient')
            ->join('c.client', 'client')
            ->join('c.Lignescommande', 'l')
            ->join('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('s.Entreprise=:val AND c.status= :statu')
            ->groupBy('client.id')
            ->addSelect('AVG(c.prix) * count(distinct((c.id) )) AS prix')
            ->orderBy('prix', 'DESC')
            ->setParameter('val', $entreprise)
            ->setParameter('statu', 'finie')
            ->getQuery()
            ->getResult();
    }
    // evolution client
    public function getClientsEvo($entreprise)
    {


        return $this->createQueryBuilder('c')
            ->select('count(distinct((client.id) ))AS nbrClient,MONTH((client.created_at )) AS month ,YEAR((client.created_at )) AS year ')
            ->join('c.client', 'client')
            ->join('c.Lignescommande', 'l')
            ->join('l.stock', 's')
            ->where('l.commande= c.id')
            ->where('l.stock= s.id')
            ->where('s.Entreprise=:val  ')
            ->groupBy('month,year')
            ->setParameter('val', $entreprise)
            ->getQuery()
            ->getResult();
    }

    // fidelité client 
    public function getLivreurCommandeStatics($poste)
    {


        return $this->createQueryBuilder('c')
            ->leftJoin('c.livreur', 'livreur')
            ->select('count(distinct((c.id) ))AS nbrCmd,livreur.id AS id,livreur.email AS email ,livreur.nom AS nom , livreur.prenom AS prenom,livreur.numTel AS numTel ')
            ->where('livreur.poste=:val ')
            ->groupBy('livreur.id')
            ->orderBy('nbrCmd')
            ->setParameter('val', $poste)
            ->getQuery()
            ->getResult();
    }
    // evolution client pour un livreur 

    public function getClientsLivreurEvo($livreur)
    {
        return $this->createQueryBuilder('c')
            ->select('count(distinct((client.id) ))AS nbrClient,MONTH((client.created_at )) AS month ,YEAR((client.created_at )) AS year ')
            ->join('c.client', 'client')
            ->where('c.livreur=:val  ')
            ->groupBy('month,year')
            ->setParameter('val', $livreur)
            ->getQuery()
            ->getResult();
    }

    // fidelité client livreurs
    public function getLivreurClientsStatics($livreur)
    {


        return $this->createQueryBuilder('c')
            ->select('count(distinct((c.id) ))AS nbrCmd,client.email AS email ,client.nom AS nom , client.prenom AS prenom,client.numTel AS numTel , client.id AS idClient')
            ->join('c.client', 'client')
            ->where('c.livreur=:val AND c.status= :statu')
            ->groupBy('client.id')
            ->addSelect('AVG(c.prix) * count(distinct((c.id) )) AS prix')
            ->orderBy('prix', 'DESC')
            ->setParameter('val', $livreur)
            ->setParameter('statu', 'finie')
            ->getQuery()
            ->getResult();
    }
}
