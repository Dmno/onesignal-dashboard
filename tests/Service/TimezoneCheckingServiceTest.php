<?php

namespace App\Tests\Service;

use App\Service\TimezoneCheckingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TimezoneCheckingServiceTest extends TestCase
{
    public function testCorrectTimezoneIsReturned(): void
    {
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $timezoneChecker = new TimezoneCheckingService($entityManagerMock);

        $this->assertEquals('UTC+0300', $timezoneChecker->validateTimezoneForSummerTime('Europe/Vilnius'));
    }
}