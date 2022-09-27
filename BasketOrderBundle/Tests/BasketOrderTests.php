<?php

namespace App\BasketOrderBundle\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class BasketOrderTests
 * @package App\BasketOrderBundle\Tests\Controller
 */
class BasketOrderTests extends WebTestCase
{

    const KEY_MP = 123456;

    protected $basketId;
    protected $userId;
    protected $anonimId;
    protected $card;
    protected $article;
    protected $articleName;
    protected KernelBrowser $client;
    protected array $authHeaders;

    protected function setUp(): void
    {
        defined('ROOT_DIR') or define('ROOT_DIR', __DIR__ . '/../../../../');
        $this->client = $this::createClient();
        $this->basketId = 1;
        $this->userId = 'test11';
        $this->anonimId = 'test11';
        $this->card = '2775123456789';
        $this->article = "3112812";
        $this->articleName = "test";

        $this->authHeaders = [
            'HTTP_X-RAINBOW-ESHOP-KEY' => self::KEY_MP,
            'Content-Type' => 'application/json',
        ];
    }

}