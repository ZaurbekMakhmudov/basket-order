<?php

namespace App\BasketOrderBundle\Tests\Controller;

use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Tests\BasketOrderTests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketCouponV2
 * @package App\BasketOrderBundle\Tests\Controller
 */
class BasketCouponV2 extends BasketOrderTests
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->anonimId = time();
        $this->userId = time();
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\V2BasketController::coupon
     * @dataProvider providertestAddCouponV2
     */
    public function testAddCouponV2($isSuccess, $couponNumberIn, $couponNumberOut)
    {
        $this->anonimId = $this->userId = time();
        // add basket
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
        $basketId = $getBasketResponse['basket']['id'];

        // add coupon
        $data = [
            "coupon" => [
                "number" => $couponNumberIn
            ]
        ];
        $this->client->request('PATCH',
            "/v2/basket/{$basketId}/coupon",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $getBasketResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('basket', $getBasketResponse);
        if($isSuccess) {
            $this->assertArrayHasKey('coupon_user', $getBasketResponse['basket']);
            $this->assertEquals($couponNumberOut, $getBasketResponse['basket']['coupon_user']);
            $this->assertArrayHasKey('coupons', $getBasketResponse);
            $this->assertArrayHasKey('number', $getBasketResponse['coupons'][0]);
            $this->assertEquals($couponNumberOut, $getBasketResponse['coupons'][0]['number']);
        } else {
            $this->assertArrayHasKey('couponNotifications', $getBasketResponse);
            $this->assertEquals('400', $getBasketResponse['couponNotifications'][0]['code']);
            $this->assertEquals('ERROR', $getBasketResponse['couponNotifications'][0]['level']);
            $this->assertEquals('Купон не применен к данной корзине', $getBasketResponse['couponNotifications'][0]['message']);
        }
    }

    /**
     * @return array
     */
    public function providertestAddCouponV2()
    {
        return
            [
                [true, 'q', 'Q'],
                [true, 'кУпОн', 'КУПОН'],
                [false, '~!@#$%^&*()_+', ''],
            ];
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\V2BasketController::coupon
     */
    public function testAddFirstOrderCouponV2()
    {
        $couponNumberFirstOrder = 'ПЕРВЫЙЗАКАЗ20';
        $this->anonimId = $this->userId = microtime();
        // add order ONL
        $body = [
            "payment_type" => '1',
            "delivery_type" => '10',
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
                "type" => 10,
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
        $this->assertArrayHasKey('order', $confirmResponse);
        $this->assertArrayHasKey('order_id', $confirmResponse['order']);
        $orderId = $confirmResponse['order']['order_id'];

        // add basket
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
        $basketId = $getBasketResponse['basket']['id'];

        // add items
        $body = [
            "items" => [
                [
                    "name" => "test",
                    "price" => 1,
                    "quantity" => 1,
                    "weight" => 1,
                    "article" => $this->article,
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

        // basket cgeckout
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
        $this->assertArrayHasKey('order_id', $content['order']);
        $this->assertArrayHasKey('user_id', $content['order']);
        $this->assertArrayHasKey('status', $content['order']);
        $this->assertArrayHasKey('created', $content['order']);
        $this->assertArrayHasKey('updated', $content['order']);
        $this->assertArrayHasKey('price', $content['order']);
        $this->assertArrayHasKey('cost', $content['order']);
        $this->assertEquals($this->userId, $content['order']['user_id'], 'Order_user_id equal sending user_id');
        $this->assertArrayHasKey('items', $content);

        // add coupon first order (do error bcoz prev order id ONL)
        $data = [
            "coupon" => [
                "number" => $couponNumberFirstOrder,
            ]
        ];
        $this->client->request('PATCH',
            "/v2/basket/{$basketId}/coupon",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $getBasketResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('basket', $getBasketResponse);
        $this->assertArrayHasKey('coupons', $getBasketResponse);
        $this->assertEquals($couponNumberFirstOrder, $getBasketResponse['coupons'][0]['number']);
/*
        $this->assertArrayHasKey('couponNotifications', $getBasketResponse);
        $this->assertEquals('404', $getBasketResponse['couponNotifications'][0]['code']);
        $this->assertEquals('NOTICE', $getBasketResponse['couponNotifications'][0]['level']);
        $this->assertEquals('Купон не применен к данной корзине', $getBasketResponse['couponNotifications'][0]['message']);
*/
        // update order to DRAFT
        $status = ShopConst::STATUS_DRAFT;
        $body = [
            "status" => $status,
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

        // add coupon first order (do success bcoz order is DRAFT)
        $data = [
            "coupon" => [
                "number" => $couponNumberFirstOrder
            ]
        ];
        $this->client->request('PATCH',
            "/v2/basket/{$basketId}/coupon",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $getBasketResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('basket', $getBasketResponse);
        $this->assertArrayHasKey('coupon_user', $getBasketResponse['basket']);
        $this->assertEquals($couponNumberFirstOrder, $getBasketResponse['basket']['coupon_user']);
        $this->assertArrayHasKey('coupons', $getBasketResponse);
        $this->assertArrayHasKey('number', $getBasketResponse['coupons'][0]);
        $this->assertEquals($couponNumberFirstOrder, $getBasketResponse['coupons'][0]['number']);
    }

}