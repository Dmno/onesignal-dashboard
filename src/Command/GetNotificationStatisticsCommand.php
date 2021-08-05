<?php

namespace App\Command;

use App\Service\OneSignalService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetNotificationStatisticsCommand extends Command
{
    /** Run this command every hour
     * 0 * * * * php /var/www/html/bin/console app:get-notification-statistics
     */
    protected static $defaultName = 'app:get-notification-statistics';
    /**
     * @var OneSignalService
     */
    private $oneSignalService;

    public function __construct(
        OneSignalService $oneSignalService
    )
    {
        parent::__construct();
        $this->oneSignalService = $oneSignalService;
    }

    protected function configure()
    {
        $this->setDescription('This command fetches and updates notification statistics');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->oneSignalService->fetchNotificationStatistics();

        if ($result > 0) {
            $io->success($result . ' notification statistics have been fetched and updated!');
        } else {
            $io->warning('There are no notifications to fetch stats for at this time.');
        }

        return Command::SUCCESS;
    }
}
