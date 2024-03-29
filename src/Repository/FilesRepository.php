<?php

namespace App\Repository;

use App\Entity\CustomerFiles;
use App\Entity\Files;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Files|null find($id, $lockMode = null, $lockVersion = null)
 * @method Files|null findOneBy(array $criteria, array $orderBy = null)
 * @method Files[]    findAll()
 * @method Files[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Files::class);
    }

    public function getStatut(CustomerFiles $customerFiles) {
        return $this->createQueryBuilder('f')
        ->select('document.id, document.name')
        ->distinct()
        ->leftJoin('f.document', 'document')
        ->leftJoin('f.customerFiles', 'customerFiles')
        ->where('customerFiles = :i')
        ->setParameter('i', $customerFiles)
        ->getQuery()
        ->getResult();
    }

    public function getFiles(CustomerFiles $customerFiles, $statut){
        return $this->createQueryBuilder('f')
        ->leftJoin('f.customerFiles', 'customerFiles')
        ->leftJoin('f.document', 'document')
        ->andWhere('customerFiles = :i')
        ->andWhere('document.id = :d')
        ->setParameter('i', $customerFiles)
        ->setParameter('d', $statut)
        ->getQuery()
        ->getResult();
    }

    // /**
    //  * @return Files[] Returns an array of Files objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Files
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
