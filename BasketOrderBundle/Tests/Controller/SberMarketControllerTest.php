<?php

namespace App\BasketOrderBundle\Tests\Controller;

use App\BasketOrderBundle\Tests\SberMarketTests;
use Symfony\Component\HttpFoundation\Response;

class SberMarketControllerTest extends SberMarketTests
{

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function getWebhookTest401()
    {
        $this->client->request(
          'POST',
          '/sbermarket/event'
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function getWebhookTest400()
    {
        $body = $this->getRequestBody('order.creatd');
        $this->client->request(
          'POST',
          '/sbermarket/event',
          [],
          [],
          $this->authHeaders,
          json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, json_decode($response->getContent(), true)['code']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function assembledTest500()
    {
        $body = $this->getRequestBody('order.assembled');
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, json_decode($response->getContent(), true)['code']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function deliveredTest500()
    {
        $body = $this->getRequestBody('order.delivered');
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, json_decode($response->getContent(), true)['code']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function canceledTest500()
    {
        $body = $this->getRequestBody('order.canceled');
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, json_decode($response->getContent(), true)['code']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function createdTest200()
    {
        $body = $this->getRequestBody('order.created', $this->proccessId);
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(
        ));
    }
    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function assembledTest200()
    {
        $body = $this->getRequestBody('order.assembled', $this->proccessId);
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->client->request(
            'GET',
            '/order/partner/' . $this->sapId . '/' . $this->proccessId,
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertEquals('CRE', $response['order']['status']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function deliveredTest200()
    {
       $body = $this->getRequestBody('order.delivered', $this->proccessId);
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $this->client->request(
            'GET',
            '/order/partner/' . $this->sapId . '/' . $this->proccessId,
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertEquals('ISS', $response['order']['status']);
    }
    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::getWebhook
     * @test
     */
    public function canceledTest200()
    {
       $body = $this->getRequestBody('order.assembled', $this->proccessId . 'A');
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $body = $this->getRequestBody('order.canceled', $this->proccessId . 'A');
        $this->client->request(
            'POST',
            '/sbermarket/event',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $this->client->request(
            'GET',
            '/order/partner/' . $this->sapId . '/' . $this->proccessId . 'A',
            [],
            [],
            $this->authHeaders,
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertEquals('RFC', $response['order']['status']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SberMarketController::notFound
     * @test
     */
    public function test404()
    {
        $this->client->request(
            'GET',
            '/sbermarket/' . $this->proccessId,
            [],
            [],
            $this->authHeaders,
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }



    public function getRequestBody($event = '', $id = ''): array
    {
        return [
            'event_type' => $event,
            'payload' => [
                'originalOrderId' => $id,
                'store_id' => '8001',
                'customer' => [
                    'name' => 'Тестовый клиент',
                    'phone' => '89000000000',
                ],
                'delivery' => [
                    'expectedFrom' => '2021-09-09T21:00:00+03:00',
                    'expectedTo' => '2021-09-09T21:30:00+03:00',
                ],
                'positions' => [
                    0 => [
                        'id' => '3001064',
                        'originalQuantity' => 5.0,
                        'quantity' => 5.0,
                        'price' => '224.91',
                        'discountPrice' => '250',
                        'replacedByID' => '',
                        'weight' => '700',
                        'totalPrice' => '1124.55',
                        'totalDiscountPrice' => '1250.00',
                    ],
                    1 => [
                        'id' => '3076192',
                        'originalQuantity' => 5.0,
                        'quantity' => 5.0,
                        'price' => '224.91',
                        'discountPrice' => '250',
                        'replacedByID' => '',
                        'weight' => '700',
                        'totalPrice' => '1124.55',
                        'totalDiscountPrice' => '1250.00',
                    ],
                ],
                'total' => [
                    'totalPrice' => '287.5',
                    'discountTotalPrice' => '287.5',
                ],
                'comment' => '',
                'replacementPolicy' => 'callOrRemove',
                'paymentMethods' => [
                    0 => 'CashDesk',
                    1 => 'SberGateway',
                ],
                'shippingMethod' => 'byCourier',
            ],
        ];

    }
}