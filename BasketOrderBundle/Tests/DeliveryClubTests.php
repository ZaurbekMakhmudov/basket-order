<?php

namespace App\BasketOrderBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeliveryClubTests extends WebTestCase
{
    const KEY_MP = 123456;
    const SAP_ID = 1000027376;

    protected function setUp(): void
    {
        defined('ROOT_DIR') or define('ROOT_DIR', __DIR__ . '/../../../../');
        $this->client = $this::createClient();
        $this->proccessId = getmypid();
        $this->sapId = self::SAP_ID;
        $this->authHeaders = [
            'HTTP_Authorization' => 'Basic ZGM6M051dmV4bk1teHRLTERWMw==',
            'HTTP_X-RAINBOW-ESHOP-KEY' => self::KEY_MP,
            'Content-Type' => 'application/json',
        ];
    }
}