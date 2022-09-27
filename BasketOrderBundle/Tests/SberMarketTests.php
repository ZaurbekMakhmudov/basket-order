<?php

namespace App\BasketOrderBundle\Tests;

use App\BasketOrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SberMarketTests extends WebTestCase
{

    const KEY_MP = 123456;
    const SAP_ID = 1000051494;

    public function setUp(): void
    {
        defined('ROOT_DIR') or define('ROOT_DIR', __DIR__ . '/../../../../');
        $this->client = $this::createClient();
        $this->proccessId = getmypid();
        $this->authHeaders = [
            'HTTP_Authorization' => 'Basic c2Jlcm1hcmtldDp6c05RcWNnTllm',
            'Content-Type' => 'application/json',
            'HTTP_X-RAINBOW-ESHOP-KEY' => self::KEY_MP
        ];
        $this->sapId = self::SAP_ID;
    }
}