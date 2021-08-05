<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\App;
use App\Entity\Notification;
use App\Entity\NotificationData;
use App\Entity\NotificationStats;
use App\Entity\Schedule;
use App\Entity\User;
use App\Form\Model\AppRecordModel;
use App\Form\Model\NotificationAndScheduleDuplicationModel;
use App\Form\Model\NotificationAndScheduleModel;
use App\Repository\AccountRepository;
use App\Repository\AppRepository;
use App\Repository\CampaignRepository;
use App\Repository\CountryRepository;
use App\Repository\IconRepository;
use App\Repository\ImageRepository;
use App\Repository\NotificationDataRepository;
use App\Repository\NotificationRepository;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class OneSignalService
{
    public const API_URL = "https://onesignal.com/api/v1/";

    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var AppRepository
     */
    private $appRepository;
    /**
     * @var AccountRepository
     */
    private $accountRepository;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var IconRepository
     */
    private $iconRepository;
    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var CountryRepository
     */
    private $countryRepository;
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;
    /**
     * @var NotificationDataRepository
     */
    private $notificationDataRepository;
    /**
     * @var SettingsRepository
     */
    private $settingsRepository;
    /**
     * @var NotificationRepository
     */
    private $notificationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AppRepository $appRepository,
        AccountRepository $accountRepository,
        ImageRepository $imageRepository,
        IconRepository $iconRepository,
        FlashBagInterface $flashBag,
        CountryRepository $countryRepository,
        CampaignRepository $campaignRepository,
        NotificationDataRepository $notificationDataRepository,
        SettingsRepository $settingsRepository,
        NotificationRepository $notificationRepository
    )
    {
        $this->client = new Client();
        $this->em = $entityManager;
        $this->appRepository = $appRepository;
        $this->accountRepository = $accountRepository;
        $this->imageRepository = $imageRepository;
        $this->iconRepository = $iconRepository;
        $this->flashBag = $flashBag;
        $this->countryRepository = $countryRepository;
        $this->campaignRepository = $campaignRepository;
        $this->notificationDataRepository = $notificationDataRepository;
        $this->settingsRepository = $settingsRepository;
        $this->notificationRepository = $notificationRepository;
    }

    public function getResponse(string $url, $method, string $token = null)
    {
        $response = $this->client->request(
            $method,
            $url,
            [
                'headers' => ['Authorization' => "Basic " . $token],
            ]
        );
        return $response;
    }

    public function createANotificationDataTransferObject(array $data = null, Notification $notification = null)
    {
        if ($data) {
            $object = new NotificationAndScheduleModel($data['name'], $data['country'], $data['title'], $data['message'], $data['icon'], $data['image'], $data['url'], $data['delivery'], $data['date'], $data['optimisation'], $data['store']);
        } else {
            $icon = $notification->getIcon() ? $notification->getIcon()->getTitle() : "";
            $image = $notification->getImage() ? $notification->getImage()->getTitle() : "";
            $object = new NotificationAndScheduleModel($notification->getName(), $notification->getCountry(), $notification->getTitle(), $notification->getMessage(), $icon, $image, $notification->getUrl(), $notification->getSchedule()->getDelivery(), $notification->getSchedule()->getDate(), $notification->getSchedule()->getOptimisation(), $notification->getSaved());
        }
        return $object;
    }

    public function createNotificationObject(NotificationAndScheduleModel $notificationDTO, User $user)
    {
        $notification = new Notification();
        $notification->setName($notificationDTO->getName());
        $notification->setCountry($notificationDTO->getCountry());
        $notification->setTitle($notificationDTO->getTitle());
        $notification->setMessage($notificationDTO->getMessage());
        if ($notificationDTO->getIcon()) {
            $iconObject = $this->iconRepository->findOneBy(['title' => $notificationDTO->getIcon()]);
            $notification->setIcon($iconObject);
        }
        if ($notificationDTO->getImage()) {
            $imageObject = $this->imageRepository->findOneBy(['title' => $notificationDTO->getImage()]);
            $notification->setImage($imageObject);
        }
        $notification->setUrl($notificationDTO->getUrl());
        if ($notificationDTO->isStore()) {
            $notification->setSaved(true);
        }
        $notification->setType('regular');
        $notification->setUser($user);
        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    public function createScheduleObject(object $data, Notification $notification)
    {
        $schedule = new Schedule();
        $schedule->setDelivery($data->delivery);
        $schedule->setDate($data->date);
        $schedule->setOptimisation($data->optimisation);
        $schedule->setNotification($notification);
        $this->em->persist($schedule);
        $this->em->flush();

        return $schedule;
    }

    public function getAllApps()
    {
        $account = $this->accountRepository->findOneBy(['id' => '1']);
        $response = $this->getResponse(self::API_URL . "apps", "GET", $account->getApiKey());
        $apps = json_decode($response->getBody(), true);

        $appObjects = [];

        foreach ($apps as $app) {
            if ($app['chrome_web_origin'] != null) {
                $appObject = new AppRecordModel($app['id'], $app['name'], $app['chrome_web_origin'], $app['players'], $app['messageable_players'], $app['basic_auth_key']);
                $appObjects[] = $appObject;
            }
        }

        $appsInServer = $this->appRepository->findAll();
        $newApps = $this->updateApps(array_reverse($appObjects, true), $appsInServer, $account);

        return $newApps;
    }

    public function createAppObject(AppRecordModel $appObject, Account $account)
    {
        $app = new App();
        $app->setAppId($appObject->getAppId());
        $app->setName($appObject->getName());
        $app->setDomain($appObject->getDomain());
        $app->setTotalUsers($appObject->getTotalUsers());
        $app->setSubscribedUsers($appObject->getSubscribedUsers());
        $app->setAuthKey($appObject->getAuthKey());
        $app->setLastCheck(new \DateTime('Europe/Vilnius'));
        $app->setAccount($account);
        $this->em->persist($app);
    }

    public function updateApps(array $appObjects, array $appsInServer, Account $account)
    {
        $newApps = [];
        if (empty($appsInServer)) {
            /** @var AppRecordModel $appObject */
            foreach ($appObjects as $appObject) {
                $this->createAppObject($appObject, $account);
                $newApps[] = $appObject->getName();
            }
        } else {
            /** @var AppRecordModel $appObject */
            foreach ($appObjects as $appObject) {
                $app = $this->appRepository->findOneBy(['authKey' => $appObject->getAuthKey()]);
                if (!$app) {
                    $this->createAppObject($appObject, $account);
                    $newApps[] = $appObject->getName();
                } else {
                    $increase = $appObject->getSubscribedUsers()-$app->getSubscribedUsers();
                    $app->setName($appObject->getName());
                    $app->setDomain($appObject->getDomain());
                    $app->setTotalUsers($appObject->getTotalUsers());
                    $app->setSubscribedUsers($appObject->getSubscribedUsers());
                    $app->setIncrease($increase);
                    $app->setLastCheck(new \DateTime('Europe/Vilnius'));
                }
            }
        }
        $this->em->flush();
        return $newApps;
    }

    public function handleNotificationCreationProcess(NotificationAndScheduleModel $notificationAndScheduleModel, User $user, string $type, $campaignId)
    {
        $notification = $this->createNotificationObject($notificationAndScheduleModel, $user);
        $schedule = $this->createScheduleObject($notificationAndScheduleModel, $notification);

        if ($type === "campaign") {
            $notification->setType($type);
            $this->em->persist($notification);

            $campaign = $this->campaignRepository->findOneBy(['id' => $campaignId]);
            if ($campaign->getPaused()) {
                $notification->setPaused('true');
            }

            $campaign->addNotification($notification);
            $this->em->flush();
        }

        if ($notification->getSaved() === false && $type !== "campaign") {
            $this->sendANotification($notification, $schedule);
        } else if($type === "campaign") {
            $this->flashBag->add('success', 'Notification ' . $notification->getName() .' saved and assigned!');
        } else {
            $this->flashBag->add('success', 'Notification saved');
        }
    }

    public function sendANotification(Notification $notification, Schedule $schedule)
    {
        $apps = $this->appRepository->findAllAppsWithSubscribersSorted();
        $settings = $this->settingsRepository->findOneBy(['id' => '1']);
        $successfulResponses = [];
        $failedResponses = [];

        $headings = ['en' => $notification->getTitle()];
        $content = ['en' => $notification->getMessage()];

        $image = $notification->getImage() ? $settings->getDomain()."/images/" . $notification->getImage()->getTitle() : NULL;
        $icon = $notification->getIcon() ? $settings->getDomain()."/icons/" . $notification->getIcon()->getTitle() : NULL;

        foreach ($apps as $app) {

            if ($schedule->getDelivery() === "immediately") {
                $body = $this->sendNow($app, $headings, $notification, $content, $image, $icon);
                $notification->setLastSent(new \DateTime('Europe/Vilnius'));
            } else {
                $body = $this->sendlater($app, $headings, $notification, $content, $schedule, $image, $icon);
            }

            $response = $this->notificationRequest($app, $body);

            if (is_numeric($response['recipients'])) {
                $successfulResponses[] = $response['recipients'];

                /** @var NotificationData $existingNotificationData */
                $existingNotificationData = $this->notificationDataRepository->findOneBy(['notification' => $notification->getId(), 'app' => $app->getId()]);

                if ($existingNotificationData) {
                    if ($response['id']) {
                        $existingNotificationData->setSentNotificationId($response['id']);
                        $existingNotificationData->setDate(new \DateTime('Europe/Vilnius'));
                        $this->em->persist($existingNotificationData);
                    }
                } else {
                    if ($response['id']) {
                        /** @var NotificationData $notificationData */
                        $notificationData = new NotificationData();
                        $notificationData->setNotification($notification);
                        $notificationData->setApp($app);
                        $notificationData->setSentNotificationId($response['id']);
                        $notificationData->setDate(new \DateTime('Europe/Vilnius'));
                        $this->em->persist($notificationData);
                    }
                }
            } else {
                $failedResponses[] = $app->getDomain();
            }
        }

        $sent = 0;
        if (!empty($successfulResponses)) {
            foreach ($successfulResponses as $response) {
                $sent = $sent + $response;
            }

            $totalAmountSent = $notification->getTimesSent() + 1;
            $totalRecipients = $notification->getSends() + $sent;
            $notification->setSends($totalRecipients);
            $notification->setTimesSent($totalAmountSent);
            $this->em->flush();
        }

        $this->determineNotificationResultMessage($sent, $schedule->getDelivery(), $failedResponses, $schedule);
        return true;
    }

    public function notificationRequest(App $app, array $body)
    {
        try {
            $response = $this->client->request(
                'POST',
                self::API_URL . "notifications",
                [
                    'headers' => [
                        'Content-Type' => 'application/json; charset=utf-8',
                        'Authorization' => "Basic " . $app->getAuthKey()
                    ],
                    'json' => $body
                ]
            );
            $notificationResponse = json_decode($response->getBody(), true);
            return $notificationResponse;

        } catch (GuzzleException $e) {
            if ($e->getCode() === 524 || $e->getCode() === 523 || $e->getCode() === 429) {
                return false;
            }
        }
        return false;
    }

    public function sendNow(App $app, array $headings, Notification $notification, array $content, string $image = null, string $icon = null)
    {
        $body = [
            'app_id' => $app->getAppId(),
            'headings' => $headings,
            'filters' => [['field' => 'country', 'relation' => '=', 'value' => $notification->getCountry()->getShort()]],
            'chrome_web_image' => $image,
            'chrome_web_icon' => $icon,
            'url' => $notification->getUrl(),
            'content_available' => true,
            'contents' => $content
        ];
        return $body;
    }

    public function sendlater(App $app, array $headings, Notification $notification, array $content, Schedule $schedule, string $image = null, string $icon = null)
    {
        $body = [
            'app_id' => $app->getAppId(),
            'headings' => $headings,
            'filters' => [['field' => 'country', 'relation' => '=', 'value' => $notification->getCountry()->getShort()]],
            'send_after' => $schedule->getDate() . ' UTC+0300',
            'chrome_web_image' => $image,
            'chrome_web_icon' => $icon,
            'url' => $notification->getUrl(),
            'content_available' => true,
            'contents' => $content
        ];
        return $body;
    }

    public function determineNotificationResultMessage(int $sent, string $delivery, array $failedResponses, Schedule $schedule)
    {
        $type = "success";
        if ($sent == 0 && $delivery === "immediately") {$type = "warning";} elseif ($sent == 0 && $delivery !== "immediately") {$type = "warning";}
        $failedMessage = !empty($failedResponses) ? ", failed domains: " . implode(", ", $failedResponses) : NULL;

        if ($sent != 0 && $delivery === "immediately") {
            $message = "Sent the notification to " . $sent . " users" . $failedMessage;
        } elseif ($sent != 0 && $delivery !== "immediately") {
            $message = "Notification was scheduled to send to " . $sent . " users" . $failedMessage;
        } else {
            $message = "There were 0 recipients or an error occurred sending the notification" . $failedMessage;

            $schedule->setDelivery("immediately");
            $schedule->setDate(NULL);
            $this->em->flush();
        }

        $this->flashBag->add($type, $message);
        return true;
    }

    public function cancelScheduledNotification(Notification $notification)
    {
        $successful = 0;
        $failed = 0;

        /** @var NotificationData $notificationData */
        $notificationDatas = $this->notificationDataRepository->findBy(['notification' => $notification]);

        /** @var App $app */
        foreach ($notificationDatas as $notificationData) {
            $response = $this->client->request(
                'DELETE',
                self::API_URL . "notifications/".$notificationData->getSentNotificationId()."?app_id=".$notificationData->getApp()->getAppId(),
                [
                    'headers' => [
                        'Content-Type' => 'application/json; charset=utf-8',
                        'Authorization' => "Basic " . $notificationData->getApp()->getAuthKey()
                    ]
                ]
            );
            $notificationResponse = json_decode($response->getBody(), true);
            if ($notificationResponse['success']) {
                  $successful++;
                  $this->em->remove($notificationData);
              } else {
                  $failed++;
              }
        }
        $this->em->flush();

        if ($successful > $failed) {
            $notification->getSchedule()->setDate("");
            $notification->getSchedule()->setDelivery("immediately");
            $this->em->flush();

            return true;
        } else {
            return false;
        }
    }

    public function notificationStatisticsRequest(string $notificationId, App $app)
    {
        $response = $this->client->request(
            "GET",
            self::API_URL . "notifications/".$notificationId."?app_id=".$app->getAppId(),
            [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => "Basic " . $app->getAuthKey()
                ]
            ]
        );
        return json_decode($response->getBody(), true);
    }

    public function fetchNotificationStatistics()
    {
        $currentDate = new \DateTime('Europe/Vilnius');
        $statisticResults = [];
        $distinctNotificationIds = [];
        $notificationStatisticsFetched = 0;
        $notificationDatas = $this->notificationDataRepository->findDataByDate();

        /** @var NotificationData $notificationData */
        foreach ($notificationDatas as $notificationData) {
            $result = $this->notificationStatisticsRequest($notificationData->getSentNotificationId(), $notificationData->getApp());
            $notificationData->setCheckCount($notificationData->getCheckCount()+1);
            $this->em->persist($notificationData);

            $statisticResults[] = [
                'id' => $notificationData->getNotification()->getId(),
                'receivers' => $result['received'],
                'conversions' => $result['converted']
            ];

            if(!isset($distinctNotificationIds[$notificationData->getNotification()->getId()])) {
                $distinctNotificationIds[$notificationData->getNotification()->getId()] = $notificationData->getNotification()->getId();
            }
        }
        $totalStatisticsResults = [];

        foreach ($distinctNotificationIds as $distinctNotificationId) {
            $totalReceivers = 0;
            $totalConversions = 0;

            foreach ($statisticResults as $statisticResult) {
                if ($statisticResult['id'] === $distinctNotificationId) {
                    $totalReceivers += $statisticResult['receivers'];
                    $totalConversions += $statisticResult['conversions'];
                }
            }

            $totalStatisticsResults[] = [
                'id' => $distinctNotificationId,
                'receivers' => $totalReceivers,
                'conversions' => $totalConversions
            ];
            $notificationStatisticsFetched++;
        }

        foreach ($totalStatisticsResults as $totalStatisticsResult) {
            $notificationObject = $this->notificationRepository->findOneBy(['id' => $totalStatisticsResult['id']]);

            if ($notificationObject->getNotificationStats()) {
                $currentNotificationStats = $notificationObject->getNotificationStats();
                $currentTotalReceivers = $currentNotificationStats->getTotalReceivers();
                $currentTotalConversions = $currentNotificationStats->getTotalConversions();
                $newTotalReceivers = $totalStatisticsResult['receivers'] > $currentTotalReceivers ? $totalStatisticsResult['receivers'] - $currentTotalReceivers : 0;
                $newTotalConversions = $totalStatisticsResult['conversions'] > $currentTotalConversions ? $totalStatisticsResult['conversions'] - $currentTotalConversions : 0;

                if ($newTotalReceivers) {
                    $currentNotificationStats->setTotalReceivers($currentTotalReceivers+$newTotalReceivers);
                }

                if ($newTotalConversions) {
                    $currentNotificationStats->setTotalConversions($currentTotalConversions+$newTotalConversions);
                }

                $currentNotificationStats->setLastCheckReceivers($totalStatisticsResult['receivers']);
                $currentNotificationStats->setLastCheckConversions($totalStatisticsResult['conversions']);
                $currentNotificationStats->setLastCheckDate($currentDate);
                $currentNotificationStats->setCheckCount($currentNotificationStats->getCheckCount()+1);
                $this->em->persist($currentNotificationStats);
            } else {
                $notificationStats = new NotificationStats();
                $notificationStats->setNotification($notificationObject);
                $notificationStats->setTotalReceivers($totalStatisticsResult['receivers']);
                $notificationStats->setTotalConversions($totalStatisticsResult['conversions']);
                $notificationStats->setFirstCheckReceivers($totalStatisticsResult['receivers']);
                $notificationStats->setFirstCheckConversions($totalStatisticsResult['conversions']);
                $notificationStats->setLastCheckReceivers($totalStatisticsResult['receivers']);
                $notificationStats->setLastCheckConversions($totalStatisticsResult['conversions']);
                $notificationStats->setLastCheckDate($currentDate);
                $notificationStats->setCheckCount(1);
                $this->em->persist($notificationStats);
            }
            $this->em->flush();
        }
        return $notificationStatisticsFetched;
    }

    public function testSuppliedApiKey(string $apiKey)
    {
        try {
            $response = $this->getResponse(self::API_URL . "apps", "GET", $apiKey);
            $apps = json_decode($response->getBody(), true);

            if (count($apps)) {
                return true;
            } else {
                return 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}




/** TESTS -------------------------------------------------------------------------------------------------------------- */

//        $app = $this->appRepository->findOneBy(['id' => '25']);
//$response = $this->client->request(
//    'POST',
//    self::API_URL . "notifications",
//    [
//        'headers' => ['Authorization' => "Basic " . $account->getAuthKey()],
//        'json' => [
//            'app_id' => $app->getAppId(),
//            'headings' => $headings,
////                    'filters' => [["field" => "tag", "key" => "country", "relation" => "=", "value" => $notification->getCountry()]],
//            'filters' => [['field' => 'country', 'relation' => '=', 'value' => $notification->getCountry()]],
////                    'send_after' => $schedule->getDate() . ' UTC+0300',
////                    'chrome_web_image' => $notification->getImage(),
//            'chrome_web_image' => 'http://www.platforms.co.in/uploads/logo.png',
////                    'chrome_web_icon' => $notification->getIcon(),
//            'chrome_web_icon' => 'http://www.platforms.co.in/uploads/logo.png',
//            'url' => $notification->getUrl(),
//            'content_available' => true,
//            'contents' => $content
//        ]
//    ]
//);

// SEND TO ONE GUY
//        $response = $this->client->request(
//                'POST',
//                self::API_URL . "notifications",
//                [
//                    'headers' => ['Authorization' => "Basic " . $account->getAuthKey()],
//                    'json' => [
//                        'app_id' => $app->getAppId(),
//                    'include_player_ids' => ['ffdb619c-1c0d-4f35-ba5f-dfb00c060fd0'],
//                        'headings' => $headings,
////                    'filters' => [["field" => "tag", "key" => "country", "relation" => "=", "value" => $notification->getCountry()]],
////                        'filters' => [['field' => 'country', 'relation' => '=', 'value' => $notification->getCountry()]],
////                    'send_after' => $schedule->getDate() . ' UTC+0300',
//                    'chrome_web_image' => $notification->getImage(),
////                        'chrome_web_image' => 'http://www.platforms.co.in/uploads/logo.png',
//                    'chrome_web_icon' => $notification->getIcon(),
////                        'chrome_web_icon' => 'http://www.platforms.co.in/uploads/logo.png',
//                        'url' => $notification->getUrl(),
//                        'content_available' => true,
//                        'contents' => $content
//                    ]
//                ]
//            );
//
//            $result = json_decode($response->getBody(), true);
//
//            if($result['id']) {
//                $notificationId = new NotificationData();
//                $notificationId->setSentNotificationId($result['id']);
//                $notificationId->setNotification($notification);
//                $notificationId->setApp($account);
//                $this->em->persist($notificationId);
//                $this->em->flush();
//            }
//
//            dd($notificationId, $notification);

//            'include_player_ids' => ['d62eab99-a604-4e65-9818-9be5bb188027'],

// SEND TO MANY
// ffdb619c-1c0d-4f35-ba5f-dfb00c060fd0 pc

// f7eeb8a1-a86a-421b-a918-3310332f3ddc phone
