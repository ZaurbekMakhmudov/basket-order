<?php

namespace App\BasketOrderBundle\Tests\Controller;

use App\BasketOrderBundle\Helper\XmlHelper;
use App\BasketOrderBundle\Service\BasketService;
use App\BasketOrderBundle\Service\ItemService;
use App\BasketOrderBundle\Service\OrderService;
use App\BasketOrderBundle\Service\SMMService;
use App\BasketOrderBundle\Tests\SMMTests;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class SMMControllerTest
 * @package App\BasketOrderBundle\Tests\Controller
 */
class SMMControllerTest extends SMMTests
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SMMController::new
     * @test
     */
    public function createOrderTest401()
    {
        $this->client->request('POST', '/smm/order/new');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SMMController::cancel
     * @test
     */
    public function cancelOrderTest401()
    {
        $this->client->request('POST', '/smm/order/cancel');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SMMController::new
     * @test
     */
    public function createOrderTest500()
    {
        $body = [
                'data' => [
                    'merchantId' => 3333,
                    'shipments' => [[
                            'shipmentId' => '',
                            'shipmentDate' => '2021-05-10T03:25:07+03:00',
                            'items' => [[
                                    'itemIndex' => '1',
                                    'goodsId' => '100025399397',
                                    'offerId' => '312306',
                                    'itemName' => 'Тестовый товар',
                                    'price' => 71909,
                                    'finalPrice' => 71409,
                                    'discounts' => [[
                                            'discountType' => 'undefined',
                                            'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                                            'discountAmount' => 500,
                                        ],
                                    ],
                                    'quantity' => 1,
                                    'taxRate' => '20',
                                    'reservationPerformed' => true,
                                    'isDigitalMarkRequired' => false,
                                ],
                            ],
                            'handover' => [
                                'packingDate' => '2021-05-10T11:00:00+03:00',
                                'reserveExpirationDate' => '2021-05-13T09:00:00+03:00',
                                'outletId' => '1',
                                'serviceScheme' => 'COLLECT_BY_CUSTOMER',
                                'depositedAmount' => 0,
                                'deliveryInterval' => NULL,
                                'deliveryId' => 9414 ,
                            ],
                            'customer' => [
                                'customerFullName' => 'Иванов Иван Иванович ',
                                'phone' => '79999999999',
                                'email' => 'test_test@gmail.com',
                                'address' => [
                                    'source' => 'Московская область, Солнечногорский район, деревня Черная Грязь, Промышленная улица, строение 2',
                                    'postalCode' => '141580',
                                    'fias' => [
                                        'regionId' => '50',
                                        'destination' => '',
                                    ],
                                    'geo' => [
                                        'lat' => '55.9699618',
                                        'lon' => '37.3187166',
                                    ],
                                    'access' => [
                                        'detachedHouse' => NULL,
                                        'entrance' => NULL,
                                        'floor' => NULL,
                                        'intercom' => NULL,
                                        'cargoElevator' => NULL,
                                        'comment' => NULL,
                                        'apartment' => NULL,
                                    ],
                                ],
                            ],
                            'flags' => NULL,
                        ],
                    ],
                ],
                'meta' => [
                    'source' => 'OMS',
                ],
        ];
        $this->client->request('POST',
            'smm/order/new',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertEquals($this->SMMService->getErrorArray(), $response);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SMMController::cancel
     * @test
     */
    public function cancelOrderTest500()
    {
        $body = [
            'data' => [
                'merchantId' => 1111,
                'shipments' => [[
                    'shipmentId' => '',
                    'items' => [[
                        'itemIndex' => '1',
                        'goodsId' => '100025399397',
                        'offerId' => '312306',
                    ],
                    ],
                ],
                ],
            ],
            'meta' => [
                'source' => 'OMS',
            ],
        ];
        $this->client->request('POST',
            'smm/order/cancel',
            [],
            [],
            $this->authHeaders,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertEquals($this->SMMService->getErrorArray(), $response);
    }

    /**
     * @covers \App\BasketOrderBundle\Controller\SMMController::new
     * @test
     */
   public function createOrderTest200()
   {
       $body = [
           'data' => [
               'merchantId' => 3333,
               'shipments' => [[
                       'shipmentId' => 'smm-test-' . $this->proccessId,
                       'shipmentDate' => '2021-05-10T03:25:07+03:00',
                       'items' => [[
                               'itemIndex' => '1',
                               'goodsId' => '100025399397',
                               'offerId' => '312306',
                               'itemName' => 'Тестовый товар',
                               'price' => 100,
                               'finalPrice' => 80,
                               'discounts' => [[
                                       'discountType' => 'undefined',
                                       'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                                       'discountAmount' => 20,
                                   ],
                               ],
                               'quantity' => 1,
                               'taxRate' => '20',
                               'reservationPerformed' => true,
                               'isDigitalMarkRequired' => false,
                           ],
                           [
                               'itemIndex' => '2',
                               'goodsId' => '100025399398',
                               'offerId' => '312307',
                               'itemName' => 'Тестовый товар 2',
                               'price' => 100,
                               'finalPrice' => 80,
                               'discounts' => [[
                                   'discountType' => 'undefined',
                                   'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                                   'discountAmount' => 20,
                               ],
                               ],
                               'quantity' => 1,
                               'taxRate' => '20',
                               'reservationPerformed' => true,
                               'isDigitalMarkRequired' => false,
                           ], [
                               'itemIndex' => '3',
                               'goodsId' => '100025399399',
                               'offerId' => '312309',
                               'itemName' => 'Тестовый товар 3',
                               'price' => 50,
                               'finalPrice' => 40,
                               'discounts' => [[
                                   'discountType' => 'undefined',
                                   'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                                   'discountAmount' => 10,
                               ],
                               ],
                               'quantity' => 1,
                               'taxRate' => '20',
                               'reservationPerformed' => true,
                               'isDigitalMarkRequired' => false,
                           ],
                           [
                               'itemIndex' => '4',
                               'goodsId' => '100025399399',
                               'offerId' => '312309',
                               'itemName' => 'Тестовый товар 3',
                               'price' => 50,
                               'finalPrice' => 40,
                               'discounts' => [[
                                   'discountType' => 'undefined',
                                   'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                                   'discountAmount' => 10,
                               ],
                               ],
                               'quantity' => 1,
                               'taxRate' => '20',
                               'reservationPerformed' => true,
                               'isDigitalMarkRequired' => false,
                           ]
                       ],
                       'handover' => [
                           'packingDate' => '2021-05-10T11:00:00+03:00',
                           'reserveExpirationDate' => '2021-05-13T09:00:00+03:00',
                           'outletId' => '1',
                           'serviceScheme' => 'COLLECT_BY_CUSTOMER',
                           'depositedAmount' => 240,
                           'deliveryInterval' => NULL,
                           'deliveryId' => 100 . $this->proccessId,
                       ],
                       'customer' => [
                           'customerFullName' => 'Иванов Иван Иванович ',
                           'phone' => '79999999999',
                           'email' => 'test_test@gmail.com',
                           'address' => [
                               'source' => 'Московская область, Солнечногорский район, деревня Черная Грязь, Промышленная улица, строение 2',
                               'postalCode' => '141580',
                               'fias' => [
                                   'regionId' => '50',
                                   'destination' => '',
                               ],
                               'geo' => [
                                   'lat' => '55.9699618',
                                   'lon' => '37.3187166',
                               ],
                               'access' => [
                                   'detachedHouse' => NULL,
                                   'entrance' => NULL,
                                   'floor' => NULL,
                                   'intercom' => NULL,
                                   'cargoElevator' => NULL,
                                   'comment' => NULL,
                                   'apartment' => NULL,
                               ],
                           ],
                       ],
                       'flags' => NULL,
                   ],
               ],
           ],
           'meta' => [
               'source' => 'OMS',
           ],
       ];
       $this->client->request('POST',
        '/smm/order/new',
        [],
        [],
        $this->authHeaders,
        json_encode($body, JSON_UNESCAPED_UNICODE)
       );

       $response = $this->client->getResponse();
       $response = json_decode($response->getContent(), true);
       $this->assertEquals($this->SMMService->getSuccessArray(), $response);
   }

    /**
     * @covers \App\BasketOrderBundle\Controller\SMMController::new
     * @test
     */
   public function sumTest()
   {
       $body = [
         'orderIdPartner' => '100' . $this->proccessId
       ];
       $this->client->request('POST',
           '/smm/order/get/items',
           [],
           [],
           $this->authHeaders,
           json_encode($body, JSON_UNESCAPED_UNICODE)
       );
       $items = json_decode($this->client->getResponse()->getContent(), true);
       foreach ($items as $item){
           $this->assertEquals($item['quantity'] * $item['cost_one_unit'], $item['cost']);
       }
   }

    /**
     * @test
     */
   public function prepaidTest()
   {
       $body = [
           'data' => [
               'merchantId' => 3333,
               'shipments' => [[
                   'shipmentId' => 'smm-test-prepaid',
                   'shipmentDate' => '2021-05-10T03:25:07+03:00',
                   'items' => [[
                       'itemIndex' => '1',
                       'goodsId' => '100025399397',
                       'offerId' => '312306',
                       'itemName' => 'Тестовый товар',
                       'price' => 100,
                       'finalPrice' => 80,
                       'discounts' => [[
                           'discountType' => 'undefined',
                           'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                           'discountAmount' => 20,
                       ],
                       ],
                       'quantity' => 1,
                       'taxRate' => '20',
                       'reservationPerformed' => true,
                       'isDigitalMarkRequired' => false,
                   ],
                       [
                           'itemIndex' => '2',
                           'goodsId' => '100025399398',
                           'offerId' => '312307',
                           'itemName' => 'Тестовый товар 2',
                           'price' => 100,
                           'finalPrice' => 80,
                           'discounts' => [[
                               'discountType' => 'undefined',
                               'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                               'discountAmount' => 20,
                           ],
                           ],
                           'quantity' => 1,
                           'taxRate' => '20',
                           'reservationPerformed' => true,
                           'isDigitalMarkRequired' => false,
                       ], [
                           'itemIndex' => '3',
                           'goodsId' => '100025399399',
                           'offerId' => '312309',
                           'itemName' => 'Тестовый товар 3',
                           'price' => 50,
                           'finalPrice' => 40,
                           'discounts' => [[
                               'discountType' => 'undefined',
                               'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                               'discountAmount' => 10,
                           ],
                           ],
                           'quantity' => 1,
                           'taxRate' => '20',
                           'reservationPerformed' => true,
                           'isDigitalMarkRequired' => false,
                       ],
                       [
                           'itemIndex' => '4',
                           'goodsId' => '100025399399',
                           'offerId' => '312309',
                           'itemName' => 'Тестовый товар 3',
                           'price' => 50,
                           'finalPrice' => 40,
                           'discounts' => [[
                               'discountType' => 'undefined',
                               'discountDescription' => '(Email goods::Лидогенерация3:Loy:Email goods)Промо',
                               'discountAmount' => 10,
                           ],
                           ],
                           'quantity' => 1,
                           'taxRate' => '20',
                           'reservationPerformed' => true,
                           'isDigitalMarkRequired' => false,
                       ]
                   ],
                   'handover' => [
                       'packingDate' => '2021-05-10T11:00:00+03:00',
                       'reserveExpirationDate' => '2021-05-13T09:00:00+03:00',
                       'outletId' => '1',
                       'serviceScheme' => 'COLLECT_BY_CUSTOMER',
                       'depositedAmount' => 0,
                       'deliveryInterval' => NULL,
                       'deliveryId' => 101,
                   ],
                   'customer' => [
                       'customerFullName' => 'Иванов Иван Иванович ',
                       'phone' => '79999999999',
                       'email' => 'test_test@gmail.com',
                       'address' => [
                           'source' => 'Московская область, Солнечногорский район, деревня Черная Грязь, Промышленная улица, строение 2',
                           'postalCode' => '141580',
                           'fias' => [
                               'regionId' => '50',
                               'destination' => '',
                           ],
                           'geo' => [
                               'lat' => '55.9699618',
                               'lon' => '37.3187166',
                           ],
                           'access' => [
                               'detachedHouse' => NULL,
                               'entrance' => NULL,
                               'floor' => NULL,
                               'intercom' => NULL,
                               'cargoElevator' => NULL,
                               'comment' => NULL,
                               'apartment' => NULL,
                           ],
                       ],
                   ],
                   'flags' => NULL,
               ],
               ],
           ],
           'meta' => [
               'source' => 'OMS',
           ],
       ];
       $this->client->request('POST',
           '/smm/order/new',
           [],
           [],
           $this->authHeaders,
           json_encode($body, JSON_UNESCAPED_UNICODE)
       );

       $response = $this->client->getResponse();
       $response = json_decode($response->getContent(), true);
       $this->assertEquals($this->SMMService->getErrorArray(), $response);
   }


    /**
     * @covers \App\BasketOrderBundle\Controller\SMMController::cancel
     * @test
     */
   public function cancelOrderTest200()
   {
       $body = [
               'data' => [
                   'merchantId' => 1111,
                   'shipments' => [[
                           'shipmentId' => 'smm-test-' . $this->proccessId,
                           'items' => [[
                                   'itemIndex' => '1',
                                   'goodsId' => '100025399397',
                                   'offerId' => '312306',
                               ],
                           ],
                       ],
                   ],
               ],
               'meta' => [
                   'source' => 'OMS',
               ],
       ];
       $this->client->request('POST',
           '/smm/order/cancel',
           [],
           [],
           $this->authHeaders,
           json_encode($body, JSON_UNESCAPED_UNICODE)
       );
       $response = $this->client->getResponse();
       $response = json_decode($response->getContent(), true);
       $this->assertEquals($this->SMMService->getSuccessArray(), $response);
   }




}