<?php

namespace App\Controller;

use App\Entity\App;
use App\Entity\Icon;
use App\Entity\Image;
use App\Entity\Notification;
use App\Entity\Settings;
use App\Entity\User;
use App\Repository\AccountRepository;
use App\Repository\AppRepository;
use App\Repository\CampaignRepository;
use App\Repository\CountryRepository;
use App\Repository\IconRepository;
use App\Repository\ImageRepository;
use App\Repository\NotificationRepository;
use App\Repository\ScheduleRepository;
use App\Repository\SettingsRepository;
use App\Service\CountryService;
use App\Service\ImageService;
use App\Service\OneSignalService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class MainController extends AbstractController
{
    /**
     * @var OneSignalService
     */
    private $oneSignalService;
    /**
     * @var AppRepository
     */
    private $appRepository;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var NotificationRepository
     */
    private $notificationRepository;
    /**
     * @var ScheduleRepository
     */
    private $scheduleRepository;
    /**
     * @var CountryRepository
     */
    private $countryRepository;
    /**
     * @var CountryService
     */
    private $countryService;
    /**
     * @var Security
     */
    private $security;
    /**
     * @var SettingsRepository
     */
    private $settingsRepository;
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
     * @var ImageService
     */
    private $imageService;

    public function __construct(
        OneSignalService $oneSignalService,
        AppRepository $appRepository,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        ScheduleRepository $scheduleRepository,
        CountryRepository $countryRepository,
        CountryService $countryService,
        Security $security,
        SettingsRepository $settingsRepository,
        AccountRepository $accountRepository,
        ImageRepository $imageRepository,
        IconRepository $iconRepository,
        ImageService $imageService
    )
    {
        $this->oneSignalService = $oneSignalService;
        $this->appRepository = $appRepository;
        $this->em = $entityManager;
        $this->notificationRepository = $notificationRepository;
        $this->scheduleRepository = $scheduleRepository;
        $this->countryRepository = $countryRepository;
        $this->countryService = $countryService;
        $this->security = $security;
        $this->settingsRepository = $settingsRepository;
        $this->accountRepository = $accountRepository;
        $this->imageRepository = $imageRepository;
        $this->iconRepository = $iconRepository;
        $this->imageService = $imageService;
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/", name="main_page")
     */
    public function showMainPage()
    {
        $countryCount = $this->countryRepository->getCountryCount();
        if (!$countryCount) {
            $this->countryService->createCountryObjects();
        }

        return $this->render('main/index.html.twig', [
            'apps' => $this->appRepository->findAllAppsWithSubscribersSorted(),
            'totals' => $this->appRepository->getAppTotals(),
            'account' => $this->accountRepository->findOneBy(['id'=> 1])
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/notifications", name="show_all_notifications")
     */
    public function showAllNotifications(Request $request)
    {
        $schedules = $this->scheduleRepository->findAll();
        $pageLimit = $this->getUser()->getPageLimit() ? $this->getUser()->getPageLimit() : 5;

        return $this->render('main/notification/notifications.html.twig', [
            'query' => $request->query->get('query'),
            'notifications' => $this->notificationRepository->findNotificationsPaginatedWithSearch($pageLimit, $request->query->get('query'), $request->query->get('page')),
            'images' => $this->imageRepository->findBy([],['id' => 'DESC']),
            'icons' => $this->iconRepository->findBy([],['id' => 'DESC']),
            'schedules' => $schedules
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/control-panel", name="control_panel")
     */
    public function showControlPanel(Request $request)
    {
        return $this->render('main/control_panel.html.twig', [
            'account' => $this->accountRepository->findOneBy(['id'=> 1]),
            'images' => $this->imageRepository->findBy([],['id' => 'DESC']),
            'icons' => $this->iconRepository->findBy([],['id' => 'DESC'])
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/get-server-settings", name="get_server_settings")
     */
    public function getServerSettings(Request $request)
    {
        $settings = $this->settingsRepository->findOneBy(['id' => '1']);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(['domain' => $settings->getDomain()]);
        }
        return true;
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/save-server-settings", name="save_server_settings")
     */
    public function saveServerSettings(Request $request)
    {
        $domain = $request->request->get('domain');

        /** @var Settings $settings */
        $settings = $this->settingsRepository->findOneBy(['id' => '1']);
        $settings->setDomain($domain);
        $this->em->flush();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(['result' => true, 'domain' => $domain]);
        }
        return true;
    }

    /**
     * @Route("/campaigns", name="show_campaigns")
     */
    public function showCampaigns(CampaignRepository $campaignRepository)
    {
        return $this->render('main/campaign/campaigns.html.twig',[
            'campaigns' => $campaignRepository->findBy([],['id' => 'DESC']),
            'countries' => $this->countryRepository->findAll()
        ]);
    }

    /**
     * @Route("/change-links", name="change_notification_links")
     */
    public function changeNotificationLinks(Request $request)
    {
        $linkFrom = rtrim($request->request->get('linkFrom'),'/');
        $linkTo = rtrim($request->request->get('linkTo'),'/');
        $totalChanged = 0;

        $notifications = $this->notificationRepository->findNotificationsByUrl($linkFrom);

        if (!empty($notifications)) {
            /** @var Notification $notification */
            foreach ($notifications as $notification) {
                $notification->setUrl(str_replace($linkFrom, $linkTo, $notification->getUrl()));
                $this->em->persist($notification);
                $totalChanged++;
            }
            $this->em->flush();

            $response = ['result' => true, 'from' => $linkFrom, 'to' => $linkTo, 'total' => $totalChanged];
        } else {
            $response = ['result' => false, 'from' => $linkFrom, 'to' => $linkTo];
        }

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse($response);
        }
        return true;
    }

    /**
     * @Route("/save-user-settings", name="save_user_settings")
     */
    public function saveUserSettings(Request $request)
    {
        $pageLimit = $request->request->get('pageLimit');
        $color = $request->request->get('color');

        if ($pageLimit || $color) {
            /** User $user */
            $user = $this->getUser();
            $user->setPageLimit($pageLimit);
            $user->setColor($color);
            $this->em->flush();

            $response = ['result' => true, 'pageLimit' => $pageLimit, 'color' => $color];
        } else {
            $response = ['result' => false];
        }

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse($response);
        }
        return true;
    }

    /**
     * @Route("/get-settings-timezone", name="get_timezone")
     */
    public function getSettingsTimezone(Request $request)
    {
        /** @var Settings $settings */
        $settings = $this->settingsRepository->findOneBy(['id' => 1]);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse($settings->getTimezone());
        }
        return true;
    }

    /**
     * @Route("/generate-invite-code", name="generate_invite_code")
     */
    public function generateInviteCode(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $generatedInviteCode = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 5)), 0, 20);
        $user->setInviteCode($generatedInviteCode);
        $this->em->flush();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(['result' => true, 'generatedCode' => $generatedInviteCode]);
        }
        return true;
    }

    /**
     * @Route("/test-api-key", name="test_api_key")
     */
    public function testOSApiKey(Request $request)
    {
        $apiKey = $request->request->get('apiKey');
        $result = $this->oneSignalService->testSuppliedApiKey($apiKey);

        if ($result) {
            $response = ['result' => true, 'status' => 'setup'];
        } else if (is_numeric($result)) {
            $response = ['result' => true, 'status' => 'skip'];
        } else if (!$result) {
            $response = ['result' => false, 'status' => 'bad key'];
        }

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse($response);
        }
        return true;
    }

    /**
     * @Route("/save-api-key", name="save_api_key")
     */
    public function saveOSApiKey(Request $request)
    {
        $apiKey = $request->request->get('apiKey');
        $action = $request->request->get('action');
        $newAppCount = 0;

        $account = $this->accountRepository->findOneBy(['id'=> 1]);

        /** @var App $app */
        foreach($account->getApps() as $app) {
            foreach ($app->getNotificationData() as $notificationData) {
                $this->em->remove($notificationData);
            }
            $this->em->remove($app);
        }

        $account->setApiKey($apiKey);
        $this->em->persist($account);

        if ($action === "setup") {
            $newAppCount = count($this->oneSignalService->getAllApps());
        }

        $this->em->flush();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(['result' => true, 'appCount' => $newAppCount]);
        }
        return true;
    }

    /**
     * @Route("/delete-pictures", name="delete_pictures")
     */
    public function deletePictures(Request $request)
    {
        $imageIds = ($request->request->get('images'));
        $iconIds = ($request->request->get('icons'));
        $mainFolder = $this->getParameter('kernel.project_dir').'/public/';
        $removedImageCount = 0;
        $removedIconCount = 0;

        if (!empty($imageIds)) {
            $removedImageCount = $this->imageService->deletePicturesByType($imageIds, $mainFolder, "images");
        }

        if (!empty($iconIds)) {
            $removedIconCount = $this->imageService->deletePicturesByType($iconIds, $mainFolder, "icons");
        }
        $this->em->flush();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(['result' => true, 'removedImages' => $removedImageCount, 'removedIcons' => $removedIconCount]);
        }
        return true;
    }
}