<?php

namespace App\BasketOrderBundle\Command;

use App\BasketOrderBundle\Service\DelayService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DelayDeleteCommand
 * @package App\BasketOrderBundle\Command
 */
class DelayDeleteCommand extends Command
{
    protected static $defaultName = 'delay:delete';

    /**
     * @var OutputInterface
     */
    private OutputInterface $output;

    /**
     * @var DelayService
     */
    protected DelayService $delayService;

    /**
     * DelayDeleteCommand constructor.
     * @param DelayService $delayService
     */
    public function __construct(DelayService $delayService)
    {
        $this->delayService = $delayService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('keepDays', InputArgument::OPTIONAL, 'Keep days', '7');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $keepDays = $input->getArgument('keepDays');
        $this->output = $output;
        $delRows = $this->delayService->getRepo()->delOldDelay($keepDays);
        $output->writeln($delRows);
    }
}