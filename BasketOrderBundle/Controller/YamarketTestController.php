<?php

namespace App\BasketOrderBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class YamarketTestController extends AbstractController
{
    /**
     * @Route("/yamarket/stocks", methods={"GET","POST"})
     */
    public function stocks(Request $request): Response
    {
        $requestBody = json_decode($request->getContent() ?? null, 1);
        $skusOut = [];
        if(!empty($requestBody['skus'])) {
            foreach ($requestBody['skus'] as $sku) {
                $skusOut[] = [
                    'sku' => $sku,
                    'warehouseId' => $requestBody['warehouseId'],
                    'items' => [
                        [
                            'type' => 'FIT',
                            'count' => 10,
                            'updatedAt' => (new \DateTime())->format('Y-m-d\TH:i:sP'),
                        ],
                    ],
                ];
            }
        }
        $responseBody = json_encode(['skus' => $skusOut]);
        return new Response($responseBody, 200);
    }

    /**
     * @Route("/yamarket{path}", requirements={"path"=".*"})
     */
    public function notFound(): Response
    {
        return new Response(null, 404);
    }

}
