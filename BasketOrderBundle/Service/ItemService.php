<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 26.07.19
 * Time: 15:50
 */

namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\Item;
use App\CashboxBundle\Service\Cashbox\CashboxService;
use App\CashboxBundle\Service\MailerError\MailerErrorService;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ItemService extends BaseService
{
    /**
     * ItemService constructor.
     * @param CashboxService $cashbox
     * @param DelayService $delayService
     * @param CurlWrapper $curlWrapper
     * @param LoggerInterface $logger
     * @param ManagerRegistry $doctrine
     * @param $cashboxShop
     */
    function __construct(
        CashboxService $cashbox,
        DelayService $delayService,
        CurlWrapper $curlWrapper,
        ManagerRegistry $doctrine,
        $cashboxShop
    )
    {
        parent::__construct(
            $cashbox,
            $delayService,
            $curlWrapper,
            $doctrine,
            $cashboxShop
        );
    }

    /**
     * @param array $options
     * @return Item[]|array|\object[]
     */
    public function findBy($options = [])
    {
        $items = [];
        if ($options) {
            $items = $this->repoItem->findBy($options);
        }

        return $items;
    }

    /**
     * @param array $options
     * @return Item|null|object
     */
    public function findOneBy($options = [])
    {
        $item = null;
        if ($options) {
            $item = $this->repoItem->findOneBy($options);
        }

        return $item;
    }

    /**
     * @return Item[]|\object[]
     */
    public function findAll()
    {
        $items = $this->repoItem->findAll();

        return $items;
    }
}