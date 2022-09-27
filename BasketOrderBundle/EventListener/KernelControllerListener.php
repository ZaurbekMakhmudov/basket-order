<?php

namespace App\BasketOrderBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Stopwatch\Stopwatch;

class KernelControllerListener
{
    private LoggerInterface $logger;
    public Stopwatch $stopWatch;

    public function __construct(LoggerInterface $logger, Stopwatch $stopWatch)
    {
        $this->logger = $logger;
        $this->stopWatch = $stopWatch;
    }

    public function onKernelController(ControllerEvent $event)
    {
        try {
            $eventTime = round($this->stopWatch->stop('kernel.request')->getDuration(), 1);

            $this->logger->info("kernel.request", ['elapsed_time' => $eventTime]);
        } catch (\Exception $exception) {
            $this->logger->error('kernel.request stop', ['exception' => $exception]);
        }

        try {
            $this->stopWatch->start('kernel.controller');
        } catch (\Exception $exception) {
            $this->logger->error('kernel.controller start', ['exception' => $exception]);
        }
    }
}