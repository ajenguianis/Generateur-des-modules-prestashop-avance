<?php

namespace App\Repository;

use App\Entity\TableMapping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TableMapping|null find($id, $lockMode = null, $lockVersion = null)
 * @method TableMapping|null findOneBy(array $criteria, array $orderBy = null)
 * @method TableMapping[]    findAll()
 * @method TableMapping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TableMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TableMapping::class);
    }

    // /**
    //  * @return TableMapping[] Returns an array of TableMapping objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TableMapping
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
