<?php

namespace App\Service;

use App\Entity\Icon;
use App\Entity\Image;
use App\Entity\User;
use App\Repository\IconRepository;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImageService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var IconRepository
     */
    private $iconRepository;

    public function __construct(ImageRepository $imageRepository, IconRepository $iconRepository,EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->imageRepository = $imageRepository;
        $this->iconRepository = $iconRepository;
    }

    public function generateImageName(string $fileName)
    {
        $split = explode('.', $fileName,2);
        $generateFileName = $split[0] . "_" . substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 5)), 0, 5);
        return $generateFileName . "." . $split[1];
    }

    public function createUploadObject(string $type, string $fileName, User $user)
    {
        $upload = $type === "image" ? new Image() : new Icon();
        $upload->setTitle($fileName);
        $upload->setUser($user);
        $this->em->persist($upload);
        $this->em->flush();
        return $upload;
    }

    public function checkAndProcessFile(string $type, string $fileName, User $user)
    {
        if ($type === "image") {
            if ($this->imageRepository->findOneBy(['title' => $fileName])) {
                $fileName = $this->generateImageName($fileName);
            }
        } else {
            if ($this->iconRepository->findOneBy(['title' => $fileName])) {
                $fileName = $this->generateImageName($fileName);
            }
        }

        return $this->createUploadObject($type, $fileName, $user);
    }

    public function deletePicturesByType(array $pictureIds, string $mainFolder, string $type)
    {
        $removedPictureCount = 0;

        foreach ($pictureIds as $pictureId) {
            $picture = $type === "images" ? $this->imageRepository->findOneBy(['id' => $pictureId]) : $this->iconRepository->findOneBy(['id' => $pictureId]);

            foreach ($picture->getNotifications() as $notification) {
                if ($type === "images") {
                    $notification->setImage(NULL);
                } elseif ($type === "icons") {
                    $notification->setIcon(NULL);
                }
                $this->em->persist($notification);
            }

            unlink($mainFolder . $type ."/" . $picture->getTitle());
            $this->em->remove($picture);
            $removedPictureCount++;
        }
        return $removedPictureCount;
    }
}