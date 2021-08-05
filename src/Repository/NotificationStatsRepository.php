<?php

namespace App\Repository;

use App\Entity\NotificationStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotificationStats|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationStats|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationStats[]    findAll()
 * @method NotificationStats[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationStats::class);
    }

    public function getNotificationStatisticsArray(int $id) {
        return $this->createQueryBuilder('s')
            ->andWhere('s.notification = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();
    }

    // /**
    //  * @return NotificationStats[] Returns an array of NotificationStats objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NotificationStats
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
