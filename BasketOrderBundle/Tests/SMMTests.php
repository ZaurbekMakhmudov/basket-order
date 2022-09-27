<?php

namespace App\BasketOrderBundle\Tests;

use App\BasketOrderBundle\Service\SMMService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SMMTests
 * @package App\BasketOrderBundle\Tests\Controller
 */
class SMMTests extends WebTestCase
{
    protected function setUp(): void
    {
        defined('ROOT_DIR') or define('ROOT_DIR', __DIR__ . '/../../../../');
        $this->client = $this::createClient();
        $this->proccessId = getmypid();
        $this->authHeaders = [
            'HTTP_Authorization' => 'Basic c21tOjNOdXZleG5NbXh0S0xEVjM=',
            'Content-Type' => 'application/json',
        ];
        $container = static::$kernel->getContainer();
        $this->SMMService = $container->get(SMMService::class);
    }
}