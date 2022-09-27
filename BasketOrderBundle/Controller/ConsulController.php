<?php

namespace App\BasketOrderBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use SensioLabs\Consul\ServiceFactory;
use SensioLabs\Consul\Services\Agent;
use SensioLabs\Consul\Services\AgentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsulController extends BaseController
{
    /**
     * @Rest\Get("/consul/health")
     */
    public function health(Request $request)
    {
        return Response::HTTP_OK;
    }

    /**
     * @Rest\Get("/consul/regservice")
     */
    public function regService(string $consulURL, string $consulToken, string $service)
    {
        $option = [
            'base_uri' => "$consulURL",
            'headers' => ['Authorization' => "Bearer $consulToken"]
        ];
        $sf = new ServiceFactory($option);
        $agent = $sf->get(AgentInterface::class);
        /** @var Agent $agent */
        $agent->registerService($service);
    }

}