<?php

namespace App\Service;

use App\Entity\Settings;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

class TimezoneCheckingService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Checks the current server timezone for winter or summer time
     */
    public function validateTimezoneForSummerTime(string $timezone = null): string
    {
        if (!isset($timezone)) {
            $timezone = 'Europe/Vilnius';
        }

        $date = new DateTime("now", new DateTimeZone($timezone));
        $currentDate = $date->format('Y-m-d');
        $currentYear = $date->format('Y');

        $targetMonths = [
            'March',
            'October'
        ];

        $calculatedMonths = [];
        foreach ($targetMonths as $month) {
            $calculatedMonths[$month] = date('Y-m-d', strtotime('last sunday of ' . $month . ' ' . $currentYear));
        }

        if ($currentDate > $calculatedMonths[$targetMonths[0]] && $currentDate < $calculatedMonths[$targetMonths[1]]) {
            return "UTC+0300";
        }

        return "UTC+0200";
    }

    /**
     * Saves the new data that timezone checker found
     */
    public function validateAndSaveTimezoneData(): void
    {
        $timezone = $this->validateTimezoneForSummerTime();

        /** @var Settings $settings */
        $settings = $this->entityManager->getRepository(Settings::class)->findOneBy(['id' => 1]);
        $settings->setCheckDate(new DateTime('Europe/Vilnius'));
        $settings->setTimezone($timezone);

        $this->entityManager->persist($settings);
        $this->entityManager->flush();
    }
}