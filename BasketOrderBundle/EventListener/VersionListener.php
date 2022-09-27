<?php

namespace App\BasketOrderBundle\EventListener;

use FOS\RestBundle\View\ConfigurableViewHandlerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class VersionListener
{
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @param ViewHandlerInterface $viewHandler
     */
    public function __construct(ViewHandlerInterface $viewHandler)
    {
        $this->viewHandler = $viewHandler;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        //$version = str_replace('v', '', $this->resolveVersion($request));
        $version = $this->resolveVersion($request);

        $request->attributes->set('version', $version);

        if ($this->viewHandler instanceof ConfigurableViewHandlerInterface) {
            $this->viewHandler->setExclusionStrategyVersion($version);
        }
    }

    /**
     * @param Request $request
     *
     * @return float|int|string
     */
    private function resolveVersion(Request $request)
    {

        $versions = isset($_ENV['API_VERSIONS']) ? $_ENV['API_VERSIONS'] : [];
        if($versions){
            $versions = explode('|', $versions);
        }
        $aVersion = isset($_ENV['API_VERSION']) ? $_ENV['API_VERSION'] : '1';
        $version = $request->attributes->get('version');

        $version = $version ? $version : $request->get('version', $aVersion);

        $version = ($version and in_array($version, $versions)) ? $version : $aVersion;

        return is_scalar($version) ? $version : floatval($version);
    }
}
