<?php

namespace App\BasketOrderBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Stopwatch\Stopwatch;

class KernelRequestListener
{

    public Stopwatch $stopWatch;
    private LoggerInterface $logger;


    public function __construct(Stopwatch $stopWatch, LoggerInterface $logger)
    {
        $this->stopWatch = $stopWatch;
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        try {
            $this->stopWatch->start('kernel.request');
        } catch (\Exception $exception) {
            $this->logger->error('kernel.request start', ['exception' => $exception]);
        }
    }
}