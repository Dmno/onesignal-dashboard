<?php

namespace App\Repository;

use App\Entity\NotificationData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotificationData|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationData|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationData[]    findAll()
 * @method NotificationData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationData::class);
    }

    public function findDataByDate()
    {
        $criteriaDate = (new \DateTime())->modify('-1 day')->format('Y-m-d');

        return $this->createQueryBuilder('d')
            ->andWhere('d.date > :criteriaDate and d.checkCount < 5')
            ->setParameter('criteriaDate', $criteriaDate)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return NotificationData[] Returns an array of NotificationData objects
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
    public function findOneBySomeField($value): ?NotificationData
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
