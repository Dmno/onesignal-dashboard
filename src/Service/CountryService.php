<?php

namespace App\Service;

use App\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CountryService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ParameterBagInterface
     */
    private $dir;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->em = $entityManager;
        $this->dir = $parameterBag->get('kernel.project_dir');
    }

    public function createCountryObjects()
    {
        $destination = $this->dir.'/public/countries.json';
        $countryArrays = json_decode(file_get_contents($destination), true);

        foreach($countryArrays as $countryArray) {
            $country = new Country();
            $country->setTitle($countryArray['name']);
            $country->setShort($countryArray['code']);
            $this->em->persist($country);
        }
        $this->em->flush();

        return true;
    }
}