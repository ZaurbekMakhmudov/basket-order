<?php

namespace App\BasketOrderBundle\Tests\Controller;

use App\BasketOrderBundle\Helper\ShopConst;
use App\BasketOrderBundle\Tests\BasketOrderTests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OrderActivateTest
 * @package App\BasketOrderBundle\Tests\Controller
 */
class OrderActivateTest extends BasketOrderTests
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->anonimId = time();
        $this->userId = time();
    }

    /**
     * @dataProvider confirmOrderStatusProvider
     */
    public function testOrderActivate($confirmedStatus, $params)
    {
        // order create
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
        $this->assertArrayHasKey('order', $confirmResponse);
        $this->assertEquals($confirmedStatus, $confirmResponse['order']['status']);
        $this->assertArrayHasKey('basket', $confirmResponse);
        $this->assertEquals(false, $confirmResponse['basket']['active']);

        // todo: IT-694 check calculate/generate
        // todo: IT-692 check events

        // order activate
        $status = ShopConst::STATUS_CRE;
        $orderId = $confirmResponse['order']['order_id'];
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

        // todo: IT-692 check events

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

}