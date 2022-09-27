<?php

namespace App\BasketOrderBundle\Command;

use App\BasketOrderBundle\Service\BaseService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncWithRMCommand extends Command
{
    protected static $defaultName = 'order:sync_rm';

    protected BaseService $baseService;

    protected $mailer;

    public function __construct(BaseService $baseService)
    {
        $this->baseService = $baseService;

        parent::__construct();
    }

    public function setVars($mailer)
    {
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->baseService->sendDiffOrders($this->mailer);
    }

}