<?php

namespace App\Controller;

use App\Repository\IconRepository;
use App\Repository\ImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    /**
     * @var ImageRepository
     */
    private $imageRepository;
    /**
     * @var IconRepository
     */
    private $iconRepository;

    public function __construct(ImageRepository $imageRepository, IconRepository $iconRepository)
    {
        $this->imageRepository = $imageRepository;
        $this->iconRepository = $iconRepository;
    }

//    /**
//     * @Route("/", name="main_image_page")
//     */
//    public function showMainPage()
//    {
//        $this->imageRepository->findAll(),
//        $this->iconRepository->findAll()
//
//        return $this->render('main/index.html.twig', [
//            'apps' => $apps
//        ]);
//    }
}