<?php

namespace App\BasketOrderBundle\Tests\Controller;

use App\BasketOrderBundle\Tests\BasketOrderTests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketControllerTest
 * @package App\BasketOrderBundle\Tests\Controller
 */
class BasketControllerTest extends BasketOrderTests
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param $responseCode
     * @param $message
     * @param $anonimId
     * @covers \App\BasketOrderBundle\Controller\BasketController::info
     * @dataProvider providertestGetBasketVar
     */
    public function testGetBasketVar($responseCode, $message, $anonimId)
    {
        $this->client->request('GET',
            '/basket',
            ['anonim_id' => $anonimId],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals($responseCode, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals($message, $content['message']);
    }

    /**
     * @return array
     */
    public function providertestGetBasketVar()
    {
        return
            [
                [Response::HTTP_BAD_REQUEST,  'wrong request', ''],
                [Response::HTTP_OK,  'create new basket', time()],
            ];
    }


    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::info
     */
    public function testGetBasket()
    {
        $this->client->request('GET',
            '/basket',
            ['anonim_id' => time(), 'user_id' => time()],
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
     * @covers \App\BasketOrderBundle\Controller\BasketController::list
     * @dataProvider providerTestGetBaskets
     */
    public function testGetBaskets($responseCode, $message, $anonimId, $userId)
    {
        $this->client->request('GET',
            '/baskets',
            ['anonim_id' => $anonimId, 'user_id' => $userId],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $this->assertEquals($responseCode, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals($message, $content['message']);

    }

    /**
     * @return array
     */
    public function providerTestGetBaskets()
    {
        return
            [
                [Response::HTTP_OK, 'list baskets for anonim user', 'd249a3b0-f7d5-11e8-bd95-5254006028f3', 'd249a3b0-f7d5-11e8-bd95-5254006028f3'],
                [Response::HTTP_BAD_REQUEST, 'anonim ID required', '', 'd249a3b0-f7d5-11e8-bd95-5254006028f3'],
                [Response::HTTP_BAD_REQUEST, 'user ID required', 'd249a3b0-f7d5-11e8-bd95-5254006028f3', ''],
                [Response::HTTP_BAD_REQUEST, 'anonim ID required', '', ''],
            ];
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::add
     */
    public function testAdd2BasketNoBasketId()
    {
        $body = [
            "items" => [
                [
                    "name" => "Джедайская палка Мосина",
                    "price" => 193,
                    "quantity" => 1,
                    "weight" => 1.2,
                    "article" => "3110277",
                    "volume" => 1.235
                ]
            ]
        ];
        $this->client->request('POST',
            '/basket/0/add',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = ['message' => 'wrong request'];
        $expectedJson = json_encode($expectedMessage, JSON_UNESCAPED_UNICODE);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($response->getContent(), $expectedJson);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::add
     * @depends testGetBasket
     */
    public function testAdd2BasketEmptyItems($basketId)
    {
        $body = [
            "items" => []
        ];
        $this->client->request('POST',
            "/basket/{$basketId}/add",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'bad request';
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::add
     * @depends testGetBasket
     */
    public function testAdd2Basket($basketId)
    {
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
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::add
     * @depends testGetBasket
     */
    public function testAdd2BasketReAddProduct($basketId)
    {
        $body = [
            "items" => [
                [
                    "name" => "Джедайская палка Мосина",
                    "price" => 300,
                    "quantity" => 1,
                    "weight" => 10,
                    "article" => "3110277",
                    "volume" => 10
                ]
            ]
        ];
        // first time add Item
        $this->client->request('POST',
            "/basket/{$basketId}/add",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        //second time add same Item
        $this->client->request('POST',
            "/basket/{$basketId}/add",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response2 = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response2->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::add
     * @depends testGetBasket
     */
    public function testAdd2BasketNonExistingProduct($basketId)
    {
        $article = 'fakearticle';
        $body = [
            "items" => [
                [
                    "name" => "Джедайская палка Мосина",
                    "price" => 300,
                    "quantity" => 1,
                    "weight" => 10,
                    "article" => $article,
                    "volume" => 10
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
        $expectedMessage = "Штрих-код {$article} не найден";
        $responseContent = json_encode(json_decode($response->getContent(),1), JSON_UNESCAPED_UNICODE);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $responseContent);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::remove
     * @depends testGetBasket
     */
    public function testDeleteItemFromBasket($basketId)
    {
        $article = 'fakearticle';
        $body = [
            "article" => $article
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/remove",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = "item {$article} from basket removed";
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::coupon
     * @depends testGetBasket
     */
    public function testRequiredCouponIntoBody($basketId)
    {
        $this->client->request('PATCH',
            "/basket/{$basketId}/coupon",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'coupons is required';
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::card
     * @depends testGetBasket
     */
    public function testRequiredCardIntoBody($basketId)
    {
        $this->client->request('PATCH',
            "/basket/{$basketId}/card",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'card ID wrong\/required';
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::payment
     * @depends testGetBasket
     */
    public function testSetPayment($basketId)
    {
        $data = [
            "payment_type" => 0,
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/payment",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'payment type set into basket';
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::remove
     * @depends testGetBasket
     */
    public function testDeleteItemFromBasket2($basketId)
    {
        $body = [
            "article" => $this->article
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/remove",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = "item {$this->article} from basket removed";
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::remove
     * @depends testGetBasket
     */
    public function testDeleteNonAddedItemFromBasket($basketId)
    {
        $this->article = '97979797';
        $body = [
            "article" => $this->article
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/remove",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = "item {$this->article} not found";
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::remove
     * @depends testGetBasket
     */
    public function testDeleteNonExistsItemFromBasket($basketId)
    {
        $body = [
            "article" => ""
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/remove",
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'article not defined';
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::clearBasket
     * @depends testGetBasket
     */
    public function testClear($basketId)
    {
        $this->client->request('DELETE',
            "/basket/{$basketId}/clear",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'basket cleared';
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::clearBasket
     */
    public function testClearNotExistsBasket()
    {
        // basketId = 0 несуществующая корзина
        $this->client->request('DELETE',
            "/basket/6788567865789/clear",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'basket not found';
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::add
     * @depends testGetBasket
     */
    public function testAdd2Basket2($basketId)
    {
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
        $this->assertArrayHasKey('order_id', $content['order']);
        $this->assertArrayHasKey('user_id', $content['order']);
        $this->assertArrayHasKey('status', $content['order']);
        $this->assertArrayHasKey('created', $content['order']);
        $this->assertArrayHasKey('updated', $content['order']);
        $this->assertArrayHasKey('price', $content['order']);
        $this->assertArrayHasKey('cost', $content['order']);
        $this->assertEquals($this->userId, $content['order']['user_id'], 'Order_user_id equal sending user_id');
        $this->assertArrayHasKey('items', $content);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::checkout
     */
    public function testCreateFromNonExistsBasketOrder()
    {
        // basketId = 0 несуществующая корзина
        $this->client->request('GET',
            "/basket/0/checkout?user_id={$this->userId}",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'wrong request';
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::checkout
     */
    public function testCreateFromNotFoundBasketOrder()
    {
        // basketId != 0 несуществующая корзина
        $this->client->request('GET',
            "/basket/99999999999999/checkout?user_id={$this->userId}",
            [],
            [],
            $this->authHeaders
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'basket not found';
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::info
     */
    public function testGetBasketUpdateCounters()
    {
        $this->client->request('GET',
            '/basket',
            ['anonim_id' => $this->anonimId.'-uc', 'user_id' => $this->userId.'-uc'],
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
     * @depends testGetBasketUpdateCounters
     */
    public function testAdd2BasketUpdateCounters($basketId)
    {
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
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::updateCounters
     * @depends testGetBasketUpdateCounters
     */
    public function testUpdateCounters($basketId)
    {
        $data = [
            "article" => $this->article,
            "item_qty" => 3
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/updateCounters",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::updateCounters
     * @depends testGetBasketUpdateCounters
     */
    public function testUpdateCountersNonExistArticle($basketId)
    {
        $this->article = 'fakearticle';
        $data = [
            "article" => $this->article,
            "item_qty" => 3
        ];

        $this->client->request('PATCH',
            "/basket/{$basketId}/updateCounters",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = "item <{$this->article}> not found";
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::updateCounters
     * @depends testGetBasketUpdateCounters
     */
    public function testUpdateCountersNonSetQuantity($basketId)
    {
        $data = [
            "article" => $this->article,
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/updateCounters",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'Qty not defined';
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::updateCounters
     * @depends testGetBasketUpdateCounters
     */
    public function testUpdateCountersOutOfRangeQuantity($basketId)
    {
        $data = [
            "article" => $this->article,
            "item_qty" => 0
        ];
        $this->client->request('PATCH',
            "/basket/{$basketId}/updateCounters",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'postData is null';
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\BasketController::updateCounters
     */
    public function testUpdateCountersNonExistsBasket()
    {
        $data = [
            "article" => $this->article,
            "item_qty" => 3
        ];
        $this->client->request('PATCH',
            "/basket/9999999999999/updateCounters",
            [],
            [],
            $this->authHeaders,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $expectedMessage = 'basket not found';
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertContains($expectedMessage, $response->getContent());
    }

}