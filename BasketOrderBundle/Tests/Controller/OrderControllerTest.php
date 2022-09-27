<?php

namespace App\BasketOrderBundle\Tests\Controller;

use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Tests\BasketOrderTests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OrderControllerTest
 * @package App\BasketOrderBundle\Tests\Controller
 */
class OrderControllerTest extends BasketOrderTests
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = time();
        $this->anonimId = time();
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::info
     */
    public function test401()
    {
        $this->client->request('GET',
            '/order/12345'
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::info
     */
    public function test404()
    {
        $this->client->request('GET',
            '/order/12345',
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::info
     */
    public function testGetBasket()
    {
        $this->client->request('GET',
            '/basket',
            ['anonim_id' => $this->anonimId, 'user_id' => $this->userId],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $getBasketResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('basket', $getBasketResponse);
        $this->assertArrayHasKey('id', $getBasketResponse['basket']);

        return $getBasketResponse['basket']['id'];
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::add
     * @depends testGetBasket
     */
    public function testBasketAdd($basketId)
    {
        $body = [
            "items" => [
                [
                    "article" => $this->article,
                    "name" => $this->articleName,
                    "price" => 1,
                    "quantity" => 1,
                    "weight" => 1,
                    "volume" => 1
                ]
            ]
        ];
        $this->client->request('POST',
            "/basket/{$basketId}/add",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'articles added into basket';
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::checkout
     * @depends testGetBasket
     */
    public function testCreateOrder($basketId)
    {
        $this->client->request('GET',
            "/basket/{$basketId}/checkout?user_id={$this->userId}",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('order', $content);
        $this->assertArrayHasKey('id', $content['order']);

        return $content['order']['order_id'];
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::confirmPayment
     * @depends testCreateOrder
     */
    public function testPayment($orderId)
    {
        $body = [
            "payment_type" => "1"
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/payment",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::confirmCustomer
     * @depends testCreateOrder
     */
    public function testCustomer($orderId)
    {
        $body = [
            "payment_type" => "1",
            "delivery_type" => "10",
            "customer" => [
                "city" => "test",
                "name" => "test",
                "phone" => "test",
                "email" => "",
                "comment" => "test",
                "street" => "",
                "house" => "",
                "building" => "",
                "flat" => ""
            ]
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/customer",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::confirmDelivery
     * @depends testCreateOrder
     */
    public function testDelivery($orderId)
    {
        $body = [
            "payment_type" => "1",
            "delivery_type" => "10",
            "delivery" => [
                "type" => 10,
                "logagent_gln" => 9760000000,
                "logagent" => [
                    "name" => "Логистика Сервис"
                ],
                "point_id" => 1,
                "point_gln" => "4607181504394",
                "name" => "Шушары,  ш.",
                "address" => "Санкт-Петербург, Шушары, корп. 1",
                "cost_sum" => 0,
            ]
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/delivery",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::confirm
     * @depends testCreateOrder
     */
    public function testConfirm($orderId)
    {
        $body = [
            "payment_type" => "1",
            "delivery_type" => "10"
        ];
        $this->client->request('POST',
            "/order/{$orderId}/confirm",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::orderList
     * @dataProvider providerGetOrderList
     */
    public function testGetOrderList($resultCode, $existsFiels, $userId)
    {
        $this->client->request('GET',
            "/orders?user_id={$userId}",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals($resultCode, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey($existsFiels, $content);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::ordersSendsErrors
     */
    public function testOrdersSendsErrors()
    {
        $this->client->request('GET',
            "/orders/sends/errors",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals($content['message'], 'get orders with sends errors');
        $this->assertArrayHasKey('orders', $content);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::confirmPaymentInformation
     * @depends testCreateOrder
     */
    public function testPaymentInformation($orderId)
    {
        $body = [
            "status" => 1,
            "date" => "2020-01-01 01:01:01",
            "amount" => 1,
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/payment-information",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), 1);
        $this->assertArrayHasKey('order', $content);
        $this->assertArrayHasKey('payment_information', $content['order']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::updateOrder
     * @depends testCreateOrder
     */
    public function testOrderUpdate($orderId)
    {
        $status = ShopConst::STATUS_CRE;
        $body = [
            "status" => $status
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/update",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), 1);
        $this->assertArrayHasKey('order', $content);
        $this->assertArrayHasKey('status', $content['order']);
        $this->assertEquals($status, $content['order']['status']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::updateOrder
     * @depends testCreateOrder
     */
    public function testOrderUpdateIsFinalStatusPositive($orderId)
    {
        $status = ShopConst::STATUS_RFC;
        $body = [
            "status" => $status
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/update",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), 1);
        $this->assertArrayHasKey('order', $content);
        $this->assertArrayHasKey('status', $content['order']);
        $this->assertEquals($status, $content['order']['status']);

        $status = ShopConst::STATUS_ISS;
        $body = [
            "status" => $status
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/update",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), 1);
        $this->assertArrayHasKey('order', $content);
        $this->assertArrayHasKey('status', $content['order']);
        $this->assertEquals($status, $content['order']['status']);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::updateOrder
     * @depends testCreateOrder
     */
    public function testOrderUpdateIsFinalStatusNegative($orderId)
    {
        $status = ShopConst::STATUS_ISS;
        $body = [
            "status" => $status
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/update",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), 1);
        $this->assertArrayHasKey('order', $content);
        $this->assertArrayHasKey('status', $content['order']);
        $this->assertEquals($status, $content['order']['status']);

        $status = ShopConst::STATUS_INC;
        $body = [
            "status" => $status
        ];
        $this->client->request('PATCH',
            "/order/{$orderId}/update",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::getOrderHistory
     * @depends testCreateOrder
     */
    public function testOrderHistory($orderId)
    {
        $this->client->request('GET',
            "/order/{$orderId}/history",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), 1);
        $this->assertArrayHasKey('history', $content);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\OrderController::order
     * @dataProvider confirmOrderStatusProvider
     */
    public function testComplex($confirmedStatus, $params)
    {
        $body = [
            "payment_type" => $params['payment_type'],
            "delivery_type" => $params['delivery_type'],
            "customer" => [
                "city" => "Шушары",
                "name" => "Покупатель",
                "phone" => "123",
                "email" => "",
                "comment" => "test",
                "street" => "",
                "house" => "",
                "building" => "",
                "flat" => ""
            ],
            "delivery" => [
                "type" => $params['delivery_type'],
                "logagent_gln" => 9760000000,
                "logagent" => [
                    "name" => "Логистика Сервис"
                ],
                "point_id" => 439,
                "point_gln" => "4607181504394",
                "name" => "Шушары,  ш.",
                "address" => "Санкт-Петербург, Шушары, корп. 1",
                "cost_sum" => 0,
            ],
            "items" => [
                [
                    "name" => "test",
                    "price" => "2",
                    "cost" => "1",
                    "quantity" => 2,
                    "article" => "3121336",
                    "weight" => "1",
                    "volume" => "1",
                ],
            ],
        ];
        $this->client->request('POST',
            "/order?anonim_id={$this->anonimId}&user_id={$this->userId}",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $confirmResponse = json_decode($response->getContent(), true);
        $this->assertEquals($confirmedStatus, $confirmResponse['order']['status']);

        return $confirmResponse;
    }

    /**
     * @return array
     */
    public function confirmOrderStatusProvider()
    {
        return
            [
                [ShopConst::STATUS_ONL,  ['payment_type' => '1', 'delivery_type' => '10']],
                [ShopConst::STATUS_ONL,  ['payment_type' => '1', 'delivery_type' => '1']],
                [ShopConst::STATUS_PCRE, ['payment_type' => '0', 'delivery_type' => '10']],
                [ShopConst::STATUS_PCRE, ['payment_type' => '0', 'delivery_type' => '1']],
            ];
    }


    /**
     * @return array
     */
    public function providerGetOrderList()
    {
        return
            [
                [Response::HTTP_OK,          'orders',  'test1'   ],
                [Response::HTTP_OK,          'orders',  'anywrong'],
                [Response::HTTP_BAD_REQUEST, 'message', ''        ],
            ];
    }
}