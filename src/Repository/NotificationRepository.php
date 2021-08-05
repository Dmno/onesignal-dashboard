<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Notification::class);
        $this->paginator = $paginator;
    }

    public function getFullNotificationsWithJoinsById(int $notificationId)
    {
        $qb = $this->createQueryBuilder('n');

        return $qb
            ->leftJoin('n.country', 'a')
            ->leftJoin('n.image', 'i')
            ->leftJoin('n.icon', 'c')
            ->select('n.id', 'n.name', 'n.title', 'n.message', 'n.url', 'n.saved', 'n.sends', 'n.lastSent', 'n.original', 'a.short as country', 'i.title as image', 'c.title as icon')
            ->andWhere('n.id = :notificationId')
            ->setParameter('notificationId', $notificationId)
            ->groupBy('n.id')
            ->orderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getNotificationsWithJoins($qb = null)
    {
        $qb = $qb ?? $this->createQueryBuilder('h');

        return $qb
            ->leftJoin('h.country', 'a')
            ->leftJoin('h.image', 'i')
            ->leftJoin('h.icon', 'c')
            ->leftJoin('h.User', 'u')
            ->leftJoin('h.notificationStats', 's')
            ->select('h.id', 'h.name', 'h.title', 'h.message', 'h.url', 'h.saved', 'h.sends', 'h.lastSent', 'h.original', 'a.short as country', 'i.title as image', 'c.title as icon', 'u.id as user', 's.id as statistics')
            ->andWhere('h.type != :type')
            ->setParameter('type', 'campaign');
    }

    public function findNotificationsPaginatedWithSearch(int $pageLimit, ?string $query, ?int $page)
    {
        $pageNumber = $page === null ? $pageNumber = "1" : $pageNumber = $page;

        $qb = $this->createQueryBuilder('h');

        if (isset($query)) {
            $qb
                ->andWhere('h.name LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        } else {
            $qb
                ->orderBy('h.id', 'DESC');
        }

        $dbQ = $this->getNotificationsWithJoins($qb)
            ->orderBy('h.id', 'DESC')
            ->getQuery();

        return $this->paginator->paginate($dbQ, $pageNumber, $pageLimit);
    }

    public function findAllNotificationsByCountryNotAssignedToCurrentCampaign(Campaign $campaign)
    {
        $assignedNotifications = [];
        foreach ($campaign->getNotifications() as $campaignNotification) {
            $assignedNotifications[] = $campaignNotification;
        }
        $notAssignedNotifications = [];

        $notifications = $this->createQueryBuilder('n')
            ->where('n.country = :country')
            ->setParameter('country', $campaign->getCountry())
            ->orderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();

        /** @var Notification $notification */
        foreach ($notifications as $notification) {
            if (!in_array($notification, $assignedNotifications)) {
                $notAssignedNotifications[] = $notification;
            }
        }
        return $notAssignedNotifications;
    }

    public function findNotificationsByUrl(string $url) {
            return $this->createQueryBuilder('n')
            ->andWhere('n.url LIKE :url')
            ->setParameter('url', '%' . $url . '%')
            ->getQuery()
            ->getResult();
    }





    // /**
    //  * @return Notification[] Returns an array of Notification objects
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
    public function findOneBySomeField($value): ?Notification
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
