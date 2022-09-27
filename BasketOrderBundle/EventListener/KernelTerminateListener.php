<?php

namespace App\BasketOrderBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Stopwatch\Stopwatch;

class KernelTerminateListener
{
    private LoggerInterface $logger;
    public Stopwatch $stopWatch;

    public function __construct(LoggerInterface $logger, Stopwatch $stopWatch)
    {
        $this->logger = $logger;
        $this->stopWatch = $stopWatch;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        try {
            $eventTime = round($this->stopWatch->stop('kernel.response')->getDuration(), 1);

            $this->logger->info("kernel.response", ['elapsed_time' => $eventTime]);
        } catch (\Exception $exception) {
            $this->logger->error('kernel.response stop', ['exception' => $exception]);
        }
    }
}