<?php

namespace App\BasketOrderBundle\Tests\Controller;

use App\BasketOrderBundle\Tests\DeliveryClubTests;
use Symfony\Component\HttpFoundation\Response;

class DeliveryClubControllerTest extends DeliveryClubTests
{

    /**
     * @var mixed
     */
    private $token;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::dcGetToken
     * @test
     */
    public function dcGetTokenTest401()
    {
        $this->client->request('POST', '/authentication/token');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::dcGetToken
     * @test
     */
    public function dcGetTokenTest200()
    {
        $this->client->request('POST',
        '/authentication/token',
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        return json_decode($response->getContent(), true)['token'];
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::createOrder
     * @test
     */
    public function createOrderTest401()
    {
        $this->client->request('POST', '/stores/' . $this->proccessId . '/orders');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @depends dcGetTokenTest200
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::createOrder
     * @test
     */
    public function createOrderTest200($token)
    {
        $body = $this->getRequestBody();
        $this->client->request(
            'POST',
            '/stores/'. $this->proccessId .'/orders',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );

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
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::getOrder
     * @test
     */
    public function getOrderTest401()
    {
        $this->client->request('GET', '/stores/' . $this->proccessId . '/orders/' . $this->proccessId);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @depends dcGetTokenTest200
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::getOrder
     * @test
     */
    public function getOrderTest200($token)
    {
        $this->client->request(
            'GET',
            '/order/partner/' . $this->sapId . '/' . $this->proccessId,
            [],
            [],
            $this->authHeaders,
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->client->request(
          'GET',
            '/stores/' . $this->proccessId . '/orders/' . $response['order']['order_id'],
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::setStatus
     * @test
     */
    public function setStatusTest401()
    {
        $this->client->request('PUT', '/stores/' . $this->proccessId . '/orders/' . $this->proccessId . '/status');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @depends dcGetTokenTest200
     * @covers \App\BasketOrderBundle\Controller\DeliveryClubController::setStatus
     * @test
     */
    public function setStatusTest200($token)
    {
        $this->client->request(
            'GET',
            '/order/partner/' . $this->sapId . '/' . $this->proccessId,
            [],
            [],
            $this->authHeaders,
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->client->request(
            'PUT',
            '/stores/' . $this->proccessId . '/orders/' . $response['order']['order_id'] . '/status',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            json_encode(['status' => 'canceled'], JSON_UNESCAPED_UNICODE)
        );
        $this->client->request(
            'GET',
            '/order/partner/' . $this->sapId . '/' . $this->proccessId,
            [],
            [],
            $this->authHeaders,
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertEquals('RFC', $response['order']['status']);
    }

    public function getRequestBody(): array
    {
        return [
            'originalOrderId' => $this->proccessId,
            'customer' => [
                'phone' => '+79626822737',
                'name' => 'Ира',
            ],
            'delivery' => [
                'address' => [
                    'city' => [
                        'name' => 'Санкт-Петербург',
                        'code' => '',
                    ],
                    'street' => [
                        'name' => 'Варшавская улица',
                        'code' => '',
                    ],
                    'coordinates' => [
                        'latitude' => '59.849257',
                        'longitude' => '30.315572',
                    ],
                    'region' => 'Санкт-Петербург',
                    'houseNumber' => '108',
                    'flatNumber' => '',
                    'entrance' => '',
                    'intercom' => '',
                    'floor' => '',
                ],
                'expectedDateTime' => '2020-12-07T13:45:26+03:00',
            ],
            'payment' => [
                'type' => 'online',
                'requiredMoneyChange' => '0',
            ],
            'positions' => [
                [
                    'id' => '3122114',
                    'quantity' => 2,
                    'price' => '10',
                    'discountPrice' => '8',
                ],
            ],
            'total' => [
                'deliveryPrice' => '250',
                'totalPrice' => '20',
                'discountTotalPrice' => '16',
            ],
            'comment' => 'Отдать жму магазина УР тестирование',
        ];
    }


}