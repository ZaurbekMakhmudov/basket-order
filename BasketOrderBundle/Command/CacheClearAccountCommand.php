<?php

namespace App\BasketOrderBundle\Command;

use App\BasketOrderBundle\Service\TokenService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CacheClearAccountCommand
 * @package App\BasketOrderBundle\Command
 */
class CacheClearAccountCommand extends Command
{
    protected static $defaultName = 'cacheclearaccount';

    /**
     * @var OutputInterface
     */
    private OutputInterface $output;

    private TokenService $tokenService;

    /**
     * DelayDeleteCommand constructor.
     * @param TokenService $tokenService
     */
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Input account user name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $out = 'Cache clear ';
        $username = $input->getArgument('username');
        $out.= $this->tokenService->clearCachedData($username) ? 'Success' : 'Error';
        $this->output = $output;
        $output->writeln($out);
    }
}