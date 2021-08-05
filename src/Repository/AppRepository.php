<?php

namespace App\Repository;

use App\Entity\App;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method App|null find($id, $lockMode = null, $lockVersion = null)
 * @method App|null findOneBy(array $criteria, array $orderBy = null)
 * @method App[]    findAll()
 * @method App[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, App::class);
    }

    public function findAllAppsWithSubscribersSorted()
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.subscribedUsers > 0')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAppTotals()
    {
        return $this->createQueryBuilder('a')
            ->select('sum(a.totalUsers), sum(a.subscribedUsers), sum(a.increase)')
            ->getQuery()
            ->getScalarResult();
    }


    // /**
    //  * @return App[] Returns an array of App objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?App
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
