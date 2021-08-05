<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Notification;
use App\Form\CampaignType;
use App\Repository\CampaignRepository;
use App\Repository\IconRepository;
use App\Repository\ImageRepository;
use App\Repository\NotificationRepository;
use App\Repository\ScheduleRepository;
use App\Service\CampaignService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/campaign")
 * Class CampaignController
 * @package App\Controller
 */
class CampaignController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var NotificationRepository
     */
    private $notificationRepository;
    /**
     * @var CampaignService
     */
    private $campaignService;
    /**
     * @var ScheduleRepository
     */
    private $scheduleRepository;
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var IconRepository
     */
    private $iconRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        CampaignService $campaignService,
        ScheduleRepository $scheduleRepository,
        CampaignRepository $campaignRepository,
        ImageRepository $imageRepository,
        IconRepository $iconRepository
    )
    {
        $this->em = $entityManager;
        $this->notificationRepository = $notificationRepository;
        $this->campaignService = $campaignService;
        $this->scheduleRepository = $scheduleRepository;
        $this->campaignRepository = $campaignRepository;
        $this->imageRepository = $imageRepository;
        $this->iconRepository = $iconRepository;
    }

    /**
     * @Route("/create", name="create_new_campaign")
     */
    public function createNewCampaign(Request $request)
    {
        $campaign = new Campaign();
        $form = $this->createForm(CampaignType::class, $campaign, [
            'action' => $this->generateUrl('create_new_campaign'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $campaign->setUser($this->getUser());
            $this->em->persist($campaign);
            $this->em->flush();

            $this->addFlash('success', 'Campaign '.$form->getData()->getName().' created');

            if ($form->getClickedButton()->getName() === "manage") {
                return $this->redirectToRoute('view_campaign', ['id' => $campaign->getId()]);
            } else {
                return $this->redirectToRoute('show_campaigns');
            }
        }
        return $this->render('main/_modal.html.twig', [
            'form' => $form->createView(),
            'title' => 'Create a campaign'
        ]);
    }

    /**
     * @Route("/view/{id}", name="view_campaign")
     */
    public function viewCampaign(Campaign $campaign)
    {
        return $this->render('main/campaign/view.html.twig', [
            'campaign' => $campaign,
            'notifications' => $campaign->getNotifications(),
            'notAssignedNotifications' => $this->notificationRepository->findAllNotificationsByCountryNotAssignedToCurrentCampaign($campaign),
            'images' => $this->imageRepository->findBy([],['id' => 'DESC']),
            'icons' => $this->iconRepository->findBy([],['id' => 'DESC']),
        ]);
    }

    /**
     * @Route("/multiple-action", name="multiple_action_campaign")
     */
    public function multipleCampaignActions(Request $request)
    {
        $campaignIds = ($request->request->get('campaigns'));
        $action = ($request->request->get('action'));
        $selectedCampaigns = [];

        foreach ($campaignIds as $campaignId) {
            $selectedCampaigns[] = $this->campaignRepository->findOneBy(['id' => $campaignId]);
        }

        $processedNotificationCount = $this->campaignService->changeMultipleCampaignsAndNotifications($selectedCampaigns, $action);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('result' => true, 'processed' => $processedNotificationCount));
        }
        return true;
    }

    /**
     * @Route("/control", name="control_campaign")
     */
    public function controlCampaign(Request $request)
    {
        $campaignId = ($request->request->get('campaign'));
        $action = ($request->request->get('action'));
        $campaign = $this->campaignRepository->findOneBy(['id' => $campaignId]);
        $totalNotificationsProcessed = 0;

        if ($action == "resume") {
            $campaign->setPaused(false);
            $result = $this->campaignService->manageNotificationStatus($campaign, false);
        } else {
            $campaign->setPaused(true);
            $result = $this->campaignService->manageNotificationStatus($campaign, true);
        }
        $this->em->flush();
        $totalNotificationsProcessed += $result;

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('result' => true, 'processed' => $totalNotificationsProcessed));
        }
        return true;
    }

    /**
     * @Route("/delete", name="delete_campaign")
     */
    public function deleteCampaign(Request $request)
    {
        $campaignId = ($request->request->get('campaign'));
        $countryId = ($request->request->get('country'));
        $campaign = $this->campaignRepository->findOneBy(['id' => $campaignId]);

        $totalNotificationsDeleted = $this->campaignService->removeCampaignNotifications($campaign);
        $this->em->remove($campaign);
        $this->em->flush();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('result' => true, 'remaining' => $this->campaignRepository->getCampaignCountByCountryId($countryId),'processed' => $totalNotificationsDeleted));
        }
        return true;
    }

    /**
     * @Route("/delete-notification", name="delete_single_notification_from_campaign")
     */
    public function deleteCampaignNotification(Request $request)
    {
        $notificationId = ($request->request->get('notification'));
        $notification = $this->notificationRepository->findOneBy(['id' => $notificationId]);

        $weekdays = $notification->getSchedule()->getWeekdays();
        foreach ($weekdays as $weekday) {
            $this->em->remove($weekday);
        }
        $this->em->remove($notification->getSchedule());

        foreach ($notification->getNotificationData() as $notificationData) {
            $this->em->remove($notificationData);
        }

        if ($notification->getNotificationStats()) {
            $this->em->remove($notification->getNotificationStats());
        }

        $this->em->remove($notification);
        $this->em->flush();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('result' => true));
        }
        return true;
    }

    /**
     * @Route("/control-single", name="control_single_notification")
     */
    public function controlCampaignNotification(Request $request)
    {
        $notificationId = ($request->request->get('notification'));
        $action = ($request->request->get('action')) === "pause" ? true : false;
        $notification = $this->notificationRepository->findOneBy(['id' => $notificationId]);

        $notification->setPaused($action);
        $this->em->persist($notification);
        $this->em->flush();

        $finalTime = "";
        $date = $notification->getSchedule()->getTime();
        if ($date) {
            $finalTime = $date->format('H:i:s');
            date($finalTime);
        }

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('result' => true, 'time' => $finalTime));
        }
        return true;
    }

    /**
     * @Route("/add-notifications/{campaign}", name="add_notifications_to_campaign")
     */
    public function addNotificationsToCampaign(Request $request, Campaign $campaign)
    {
        $availableNotifications = $this->notificationRepository->findAllNotificationsByCountryNotAssignedToCurrentCampaign($campaign);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('add_notifications_to_campaign',
                ['campaign' => $campaign->getId()]))
            ->add("notifications", EntityType::class, [
                'label' => 'Notifications',
                'multiple' => true,
                'expanded' => true,
                'class' => Notification::class,
                'choices' => $availableNotifications,
                'choice_label' => 'name'
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notifications = $form->getData();

            $count = $this->campaignService->copyNotifications($notifications, $campaign);

            $amountText = $count > 1 ? "notifications" : "notification";
            $this->addFlash('success', $count . " " . $amountText . ' assigned to this campaign');
            return $this->redirectToRoute('view_campaign', ['id' => $campaign->getId()]);
        }
        return $this->render('main/campaign/modal.html.twig', [
            'form' => $form->createView(),
            'title' => 'Assign notifications to this campaign'
        ]);
    }

    /**
     * @Route("/save-time", name="save_campaign_schedule_time")
     */
    public function saveCampaignScheduleTime(Request $request)
    {
        $scheduleId = $request->request->get('scheduleId');
        $time = $request->request->get('time');

        $savedTime = $this->campaignService->processSavedTime($scheduleId, $time);
        $closestDate = $this->campaignService->findClosestDate($scheduleId);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('result' => true, 'time' => $savedTime, 'date' => $closestDate));
        }
        return true;
    }

    /**
     * @Route("/save-weekdays", name="save_campaign_schedule_weekdays")
     */
    public function saveCampaignScheduleWeekdays(Request $request)
    {
        $weekdays = ($request->request->get('weekdays'));
        $scheduleId = ($request->request->get('schedule'));

        $result = $this->campaignService->processSavedDays($scheduleId, $weekdays);
        $schedule = $this->scheduleRepository->findOneBy(['id' => $scheduleId]);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('response' => $result, 'time' => $schedule->getTime()->format('H:i:s')));
        }
        return true;
    }

    /**
     * @Route("/find-closest-date", name="find_closest_day")
     */
    public function findClosestDate(Request $request) {
        $scheduleId = ($request->request->get('schedule'));

        $closestDate = $this->campaignService->findClosestDate($scheduleId);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('date' => $closestDate));
        }
        return true;
    }

    /**
     * @Route("/remaining-campaigns-by-country", name="find_campaign_count_by_country")
     */
    public function findCampaignCountByCountry(Request $request) {
        $countryId = ($request->request->get('country'));

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('country' => $countryId, 'remaining' => $this->campaignRepository->getCampaignCountByCountryId($countryId)));
        }
        return true;
    }

    /**
     * @Route("/edit-campaign-name", name="edit_campaign_name")
     */
    public function editCampaignName(Request $request) {
        $newCampaignName = ($request->request->get('name'));
        $campaignId = ($request->request->get('campaignId'));

        /** @var Campaign $campaign */
        $campaign = $this->campaignRepository->findOneBy(['id' => $campaignId]);

        foreach ($campaign->getNotifications() as $notification) {
            $notification->setName(str_replace($campaign->getName(), $newCampaignName, $notification->getName()));
            $this->em->persist($notification);
        }

        $campaign->setName($newCampaignName);
        $this->em->flush();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('name' => $newCampaignName));
        }
        return true;
    }
}