<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Form\Model\NotificationAndScheduleModel;
use App\Form\NotificationAndScheduleDuplicateType;
use App\Form\NotificationAndScheduleType;
use App\Repository\IconRepository;
use App\Repository\ImageRepository;
use App\Repository\NotificationRepository;
use App\Repository\NotificationStatsRepository;
use App\Repository\SettingsRepository;
use App\Service\ImageService;
use App\Service\OneSignalService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @IsGranted("ROLE_USER")
 * Class NotificationController
 * @package App\Controller
 */
class NotificationController extends AbstractController
{
    /**
     * @var OneSignalService
     */
    private $oneSignalService;
    /**
     * @var ImageService
     */
    private $imageService;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var IconRepository
     */
    private $iconRepository;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var NotificationRepository
     */
    private $notificationRepository;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var SettingsRepository
     */
    private $settingsRepository;
    /**
     * @var NotificationStatsRepository
     */
    private $notificationStatsRepository;

    public function __construct(
        OneSignalService $oneSignalService,
        ImageService $imageService,
        ImageRepository $imageRepository,
        IconRepository $iconRepository,
        SerializerInterface $serializer,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager,
        SettingsRepository $settingsRepository,
        NotificationStatsRepository $notificationStatsRepository
    )
    {
        $this->oneSignalService = $oneSignalService;
        $this->imageService = $imageService;
        $this->imageRepository = $imageRepository;
        $this->iconRepository = $iconRepository;
        $this->serializer = $serializer;
        $this->notificationRepository = $notificationRepository;
        $this->em = $entityManager;
        $this->settingsRepository = $settingsRepository;
        $this->notificationStatsRepository = $notificationStatsRepository;
    }

    /**
     * @Route("/create/{type}/{id}", name="create_notification")
     */
    public function createNotification(Request $request)
    {
        $type = $request->get('type');
        $campaignId = is_numeric($request->get('id')) ? $request->get('id') : false;

        $form = $this->createForm(NotificationAndScheduleType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $notificationDTO = $this->oneSignalService->createANotificationDataTransferObject($data, null);

            $this->oneSignalService->handleNotificationCreationProcess($notificationDTO, $this->getUser(), $type, $campaignId);

            if ($type === "new") {
                return $this->redirectToRoute('show_all_notifications');
            } else {
                return $this->redirectToRoute('view_campaign', array('id' => $campaignId));
            }
        }
        return $this->render('main/notification/notification_form.html.twig', [
            'form' => $form->createView(),
            'images' => $this->imageRepository->findBy([],['id' => 'DESC']),
            'icons' => $this->iconRepository->findBy([],['id' => 'DESC']),
            'title' => 'Creating a notification',
            'type' => $type
        ]);
    }

    /**
     * @Route("/resend-notification", name="notification_resend")
     */
    public function sendNotificationAgain(Request $request)
    {
        $notificationId = $request->request->get('notificationId');
        $delivery = $request->request->get('delivery');
        $date = $request->request->get('date');

        $notification = $this->notificationRepository->findOneBy(['id' => $notificationId]);
        $notification->setSaved(false);
        $schedule = $notification->getSchedule();
        $schedule->setDelivery($delivery);
        $schedule->setDate($date);
        $this->em->flush();

        $this->oneSignalService->sendANotification($notification, $schedule);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(['result' => true, 'id' => $notification->getId()]);
        }
        return true;
    }

    /**
     * @Route("/duplicate/{id}", name="notification_duplicate")
     */
    public function duplicateNotification(Request $request, Notification $notification)
    {
        $notificationDTO = $this->oneSignalService->createANotificationDataTransferObject(null, $notification);
        $form = $this->createForm(NotificationAndScheduleDuplicateType::class, $notificationDTO);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var NotificationAndScheduleModel $data */
            $data = $form->getData();

            $this->oneSignalService->handleNotificationCreationProcess($data, $this->getUser(), "NULL", 0);

            return $this->redirectToRoute('show_all_notifications');
        }
        return $this->render('main/notification/notification_form.html.twig', [
            'form' => $form->createView(),
            'images' => $this->imageRepository->findBy([],['id' => 'DESC']),
            'icons' => $this->iconRepository->findBy([],['id' => 'DESC']),
            'title' => 'Duplicating ' . $notification->getName(),
            'type' => 'copy'
        ]);
    }

    /**
     * @Route("/delete/{id}", name="notification_delete")
     */
    public function deleteNotification(Notification $notification, Request $request)
    {
        $notificationName = $notification->getName();
        $schedule = $notification->getSchedule();

        foreach ($notification->getNotificationData() as $notificationData) {
            $this->em->remove($notificationData);
        }

        if ($notification->getNotificationStats()) {
            $this->em->remove($notification->getNotificationStats());
        }

        $this->em->remove($schedule);
        $this->em->remove($notification);
        $this->em->flush();
        $this->addFlash('success', 'Notification ' . $notificationName . ' deleted!');

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    /**
     * @Route("/cancel-notification", name="notification_cancel")
     */
    public function cancelNotification(Request $request) {
        $notificationId = ($request->request->get('id'));
        $notification = $this->notificationRepository->findOneBy(['id' => $notificationId]);

        $response = $this->oneSignalService->cancelScheduledNotification($notification) ? ['result' => true] : ['result' => false];

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse($response);
        }
        return true;
    }

    /**
     * @Route("/get-notification", name="get_notification_for_editing")
     */
    public function getNotificationForEditing(Request $request)
    {
        $notificationId = ($request->request->get('id'));
        $notification = $this->notificationRepository->getFullNotificationsWithJoinsById($notificationId);
        $settings = $this->settingsRepository->findOneBy(['id' => '1']);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse([
                'notification' => $notification, 'domain' => $settings->getDomain()
            ]);
        }
        return true;
    }

    /**
     * @Route("/save-notification-edit", name="notification_save_edit")
     */
    public function saveNotificationEdit(Request $request)
    {
        $notificationId = $request->request->get('notificationId');
        $notificationName = $request->request->get('notificationName');
        $notificationTitle = $request->request->get('notificationTitle');
        $notificationMessage = $request->request->get('notificationMessage');
        $notificationIcon = $request->request->get('notificationIcon');
        $notificationImage = $request->request->get('notificationImage');
        $notificationUrl = $request->request->get('notificationUrl');

        $notification = $this->notificationRepository->findOneBy(['id' => $notificationId]);
        $notification->setName($notificationName);
        $notification->setTitle($notificationTitle);
        $notification->setMessage($notificationMessage);
        $notification->setIcon($notificationIcon ? $this->iconRepository->findOneBy(['title' => $notificationIcon]) : NULL);
        $notification->setImage($notificationImage ? $this->imageRepository->findOneBy(['title' => $notificationImage]) : NULL);
        $notification->setUrl($notificationUrl);
        $this->em->flush();

        $settings = $this->settingsRepository->findOneBy(['id' => '1']);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(['result' => true, 'id' => $notificationId, 'domain' => $settings->getDomain(), 'name' => $notificationName, 'title' => $notificationTitle, 'message' => $notificationMessage, 'icon' => $notificationIcon, 'image' => $notificationImage, 'url' => $notificationUrl]);
        }
        return true;
    }

    /**
     * @Route("/get-notification-and-schedule", name="get_notification_and_schedule")
     */
    public function getNotificationAndScheduleWithAjax(Request $request)
    {
        $notificationId = ($request->request->get('id'));
        $notification = $this->notificationRepository->findOneBy(["id" => $notificationId]);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array(
                'notification' => [
                    'name' => $notification->getName()
                ],
                'schedule' => [
                    'delivery' => $notification->getSchedule()->getDelivery(),
                    'date' => $notification->getSchedule()->getDate()
                ]));
        }
        return true;
    }

    /**
     * @Route("/save-image", name="save_notification_image")
     */
    public function saveNotificationImageOrIcon(Request $request)
    {
        $uploadedFiles = $request->files->get('upload');
        $type = $request->request->get('type');
        $mainFolder = $this->getParameter('kernel.project_dir').'/public/';
        $destination = $type === "image" ? $mainFolder . "images" : $mainFolder . "icons";
        $uploadCount = 0;

        foreach ($uploadedFiles as $uploadedFile) {
            $uploadObj[] = $uploadedFile->getClientOriginalName();
            $fileObject = $this->imageService->checkAndProcessFile($type, $uploadedFile->getClientOriginalName(), $this->getUser());
            $uploadedFile->move($destination, $fileObject->getTitle());
            $uploadCount++;
        }

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('uploads' => $uploadCount));
        }
        return true;
    }

    /**
     * @Route("/get-images", name="get_all_images")
     */
    public function getAllImagesWithAjax(Request $request)
    {
        $images = $this->imageRepository->getAllImagesInArray();
        $icons = $this->iconRepository->getAllIconsInArray();

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('images' => ['source' => 'images', 'data' => $images], 'icons' => ['source' => 'icons', 'data' => $icons]));
        }
        return true;
    }

    /**
     * @Route("/get-notification-statistics", name="get_notification_statistics")
     */
    public function getNotificationStatistics(Request $request)
    {
        $notificationId = ($request->request->get('id'));
        $notification = $this->notificationRepository->findOneBy(['id' => $notificationId]);

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('statistics' => $this->notificationStatsRepository->getNotificationStatisticsArray($notificationId), 'lastSent' => $notification->getLastSent()));
        }
        return true;
    }
}