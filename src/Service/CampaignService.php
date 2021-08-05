<?php


namespace App\Service;


use App\Entity\Campaign;
use App\Entity\Notification;
use App\Entity\Schedule;
use App\Entity\Weekdays;
use App\Repository\ScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;

class CampaignService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ScheduleRepository
     */
    private $scheduleRepository;

    public function __construct(EntityManagerInterface $entityManager, ScheduleRepository $scheduleRepository)
    {
        $this->em = $entityManager;
        $this->scheduleRepository = $scheduleRepository;
    }

    public function copyNotifications(array $notifications, Campaign $campaign)
    {
        $counter = 0;

        /** @var Notification $notification */
        foreach ($notifications['notifications'] as $notification) {
            $this->copyNotificationEntityWithoutRelations($notification, $campaign);
            $counter++;
        }
        $this->em->flush();

        return $counter;
    }

    public function createWeekdayObject(string $weekday, \DateTime $currentDateTime, Schedule $schedule)
    {
        $weekdayObject = new Weekdays();
        $weekdayObject->setDay($weekday);
        $weekdayObject->setDate($currentDateTime);
        $weekdayObject->setSchedule($schedule);
        $this->em->persist($weekdayObject);
        return true;
    }

    public function processSavedTime(int $scheduleId, string $time)
    {
        $timeString = strtotime($time);
        $newTime = date('H:i:s', $timeString);
        $finalTime = new \DateTime($newTime);
        $schedule = $this->scheduleRepository->findOneBy(['id' => $scheduleId]);
        $schedule->setTime($finalTime);
        $this->em->flush();

        $scheduledWeekdays = $schedule->getWeekdays();
        if (!empty($scheduledWeekdays)) {
            $currentDateTime = new \DateTime("now", new \DateTimeZone('Europe/Vilnius'));
            $currentDate = $currentDateTime->format('Y-m-d');
            $currentDay = date('l', strtotime($currentDate));
            $scheduledTime = $schedule->getTime()->format('H:i:s');

            foreach ($scheduledWeekdays as $scheduledWeekday) {
                if ($currentDay === $scheduledWeekday->getDay()) {
                    $currentTime = new \DateTime("now", new \DateTimeZone('Europe/Vilnius'));
                    $time = $currentTime->format('H:i:s');

                    if ($scheduledTime < $time) {
                        $newDate = $currentDateTime->modify('next ' . $scheduledWeekday->getDay());
                    } else {
                        $newDate = $currentDateTime;
                    }
                    $scheduledWeekday->setDate($newDate);
                    $this->em->flush();
                }
            }
        }
        return $newTime;
    }

    public function processSavedDays(int $scheduleId, array $weekdays = null)
    {
        /** @var Schedule $schedule */
        $schedule = $this->scheduleRepository->findOneBy(['id' => $scheduleId]);
        $alreadySelectedDays = [];

        foreach ($schedule->getWeekdays() as $scheduledWeekday) {
            // IF the selected weekday array is not empty do this
            if (!empty($weekdays)) {
                if (!in_array($scheduledWeekday->getDay(), $weekdays)) {
                    $this->em->remove($scheduledWeekday);
                } else {
                    $alreadySelectedDays[] = $scheduledWeekday->getDay();
                }
            } else {
                // If the weekday array was empty remove the existing records
                $this->em->remove($scheduledWeekday);
            }
            $this->em->flush();
        }

        if (!empty($weekdays)) {
            $currentDateTime = new \DateTime("now", new \DateTimeZone('Europe/Vilnius'));
            $currentDate = $currentDateTime->format('Y-m-d');
            $currentDay = date('l', strtotime($currentDate));
            $scheduledTime = $schedule->getTime()->format('H:i:s');

            foreach ($weekdays as $weekday) {
                if (!in_array($weekday, $alreadySelectedDays)) {
                    // If the current weekday matches the passed weekday run a test for the current time
                    if ($currentDay === $weekday) {
                        $currentTime = new \DateTime("now", new \DateTimeZone('Europe/Vilnius'));
                        $time = $currentTime->format('H:i:s');

                        // If the scheduled time has already passed compared to the current time, then set the scheduled date for the next weekday
                        if ($scheduledTime < $time) {
                            $tempDate = clone $currentDateTime;
                            $nextWeekday = $tempDate->modify('next ' . $weekday);
                            $this->createWeekdayObject($weekday, $nextWeekday, $schedule);
                        } else {
                            $this->createWeekdayObject($weekday, $currentDateTime, $schedule);
                        }
                    } else {
                        $tempDate = clone $currentDateTime;
                        $nextWeekday = $tempDate->modify('next ' . $weekday);
                        $this->createWeekdayObject($weekday, $nextWeekday, $schedule);
                    }
                }
            }
            $this->em->flush();
        }
        return true;
    }

    public function findClosestDate(int $scheduleId) {
        /** @var Schedule $schedule */
        $schedule = $this->scheduleRepository->findOneBy(['id' => $scheduleId]);

        $scheduledWeekdays = [];
        $closestDate = "";
        $i = 0;

        foreach ($schedule->getWeekdays() as $weekday) {
            $scheduledWeekdays[] = $weekday;
        }

        if (!empty($scheduledWeekdays)) {
            foreach ($scheduledWeekdays as $scheduledWeekday) {
                if ($i == 0) {
                    $closestDate = $scheduledWeekday->getDate();
                    $i = 1;
                } else {
                    if ($scheduledWeekday->getDate() < $closestDate) {
                        $closestDate = $scheduledWeekday->getDate();
                    }
                }
            }
            return $closestDate->format('Y-m-d');
        }
        return false;
    }

    public function copyNotificationEntityWithoutRelations(Notification $notification, Campaign $campaign)
    {
        $notificationCopy = new Notification();
        $notificationCopy->setName($campaign->getName() . " " . $notification->getName());
        $notificationCopy->setCountry($notification->getCountry());
        $notificationCopy->setTitle($notification->getTitle());
        $notificationCopy->setMessage($notification->getMessage());
        $notificationCopy->setImage($notification->getImage());
        $notificationCopy->setIcon($notification->getIcon());
        $notificationCopy->setUrl($notification->getUrl());
        $notificationCopy->setSaved(false);
        $notificationCopy->setSends(0);
        $notificationCopy->setType('campaign');
        $notificationCopy->setOriginal($notification->getId());
        $notificationCopy->setUser($notification->getUser());

        if ($campaign->getPaused()) {
            $notificationCopy->setPaused('true');
        }
        $this->em->persist($notificationCopy);

        /** @var Schedule $scheduleCopy */
        $scheduleCopy = clone $notification->getSchedule();
        $scheduleCopy->setNotification($notificationCopy);
        $scheduleCopy->setDelivery("immediately");
        $scheduleCopy->setDate("");
        $this->em->persist($scheduleCopy);

        $campaign->addNotification($notificationCopy);
    }

    public function manageNotificationStatus(Campaign $campaign, string $status) {
        $processedNotifications = 0;
        foreach ($campaign->getNotifications() as $campaignNotification) {
            $campaignNotification->setPaused($status);
            $this->em->persist($campaignNotification);
            $processedNotifications++;
        }
        return $processedNotifications;
    }

    public function removeCampaignNotifications(Campaign $campaign)
    {
        $processedNotifications = 0;

        foreach ($campaign->getNotifications() as $campaignNotification) {
            $weekdays = $campaignNotification->getSchedule()->getWeekdays();
            foreach ($weekdays as $weekday) {
                $this->em->remove($weekday);
            }

            foreach ($campaignNotification->getNotificationData() as $notificationData) {
                $this->em->remove($notificationData);
            }

            if ($campaignNotification->getNotificationStats()) {
                $this->em->remove($campaignNotification->getNotificationStats());
            }

            $this->em->remove($campaignNotification->getSchedule());
            $this->em->remove($campaignNotification);
            $processedNotifications++;
        }
        return $processedNotifications;
    }

    public function changeMultipleCampaignsAndNotifications(array $campaigns, string $action)
    {
        $totalProcessedNotifications = 0;

        if (!empty($campaigns)) {
            /** @var Campaign $campaign */
            foreach ($campaigns as $campaign) {
                if ($action == "resume") {
                    $campaign->setPaused(false);
                    $result = $this->manageNotificationStatus($campaign, false);
                    $totalProcessedNotifications += $result;
                }

                if ($action == "pause") {
                    $campaign->setPaused(true);
                    $result = $this->manageNotificationStatus($campaign, true);
                    $totalProcessedNotifications += $result;
                }

                if ($action == "delete") {
                    $result = $this->removeCampaignNotifications($campaign);
                    $this->em->remove($campaign);
                    $totalProcessedNotifications += $result;
                }
            }
            $this->em->flush();
            return $totalProcessedNotifications;
        } else {
            return false;
        }
    }
}