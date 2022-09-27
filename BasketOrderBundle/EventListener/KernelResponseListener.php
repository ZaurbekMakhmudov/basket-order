<?php

namespace App\BasketOrderBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Stopwatch\Stopwatch;

class KernelResponseListener
{
    private LoggerInterface $logger;
    public Stopwatch $stopWatch;

    public function __construct(LoggerInterface $logger, Stopwatch $stopWatch)
    {
        $this->logger = $logger;
        $this->stopWatch = $stopWatch;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        try {
            $eventTime = round($this->stopWatch->stop('kernel.controller')->getDuration(), 1);

            $this->logger->info("kernel.controller", ['elapsed_time' => $eventTime]);
        } catch (\Exception $exception) {
            $this->logger->error('kernel.controller stop', ['exception' => $exception]);
        }

        try {
            $this->stopWatch->start('kernel.response');
        } catch (\Exception $exception) {
            $this->logger->error('kernel.response start', ['exception' => $exception]);
        }
    }
}