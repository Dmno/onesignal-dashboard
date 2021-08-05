<?php

namespace App\Repository;

use App\Entity\Weekdays;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Weekdays|null find($id, $lockMode = null, $lockVersion = null)
 * @method Weekdays|null findOneBy(array $criteria, array $orderBy = null)
 * @method Weekdays[]    findAll()
 * @method Weekdays[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeekdaysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Weekdays::class);
    }

    // /**
    //  * @return Weekdays[] Returns an array of Weekdays objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Weekdays
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
