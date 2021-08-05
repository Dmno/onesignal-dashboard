<?php

namespace App\Command;

use App\Service\OneSignalService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 10 * * * * php /var/www/html/bin/console app:update-apps
 * Class UpdateAppsCommand
 * @package App\Command
 */
class UpdateAppsCommand extends Command
{
    private $oneSignalService;

    public function __construct(OneSignalService $oneSignalService)
    {
        parent::__construct();
        $this->oneSignalService = $oneSignalService;
    }

    protected static $defaultName = 'app:update-apps';

    protected function configure()
    {
        $this->setDescription('Update all onesignal apps');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->oneSignalService->getAllApps();

        $io->success('Apps have been updated');
        return 0;
    }
}
