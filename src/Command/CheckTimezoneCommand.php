<?php

namespace App\Command;

use App\Service\TimezoneCheckingService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckTimezoneCommand extends Command
{
    /**
     * Run every 4 in the morning
     * 0 4 * * * php /var/www/html/bin/console app:check-timezone
     */

    protected static $defaultName = 'app:check-timezone';
    protected static $defaultDescription = 'Checks the current server timezone date to validate timezone utc value';
    private $timezoneCheckingService;

    public function __construct(TimezoneCheckingService $timezoneCheckingService)
    {
        parent::__construct();
        $this->timezoneCheckingService = $timezoneCheckingService;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->timezoneCheckingService->validateAndSaveTimezoneData();

        $io->success('Command has been run!');

        return Command::SUCCESS;
    }
}
