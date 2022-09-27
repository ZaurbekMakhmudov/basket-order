<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 11.11.19
 * Time: 18:01
 *
 * Changed by Zaurbek Makhmudov
 * Date: 19.01.22
 * Time: 15:23
 */

namespace App\BasketOrderBundle\Traits;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Repository\BasketRepository;
use App\BasketOrderBundle\Repository\EshopOrderPositionRepository;
use App\BasketOrderBundle\Repository\EshopOrderRepository;
use App\BasketOrderBundle\Repository\ItemRepository;
use App\BasketOrderBundle\Repository\OrderHistoryRepository;
use App\BasketOrderBundle\Repository\OrderRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

trait _LogTrait
{
    public ManagerRegistry $doctrine;
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    public $em;
    /** @var \Doctrine\Common\Persistence\ObjectManager */
    public $emEra;
    /** @var ObjectRepository|BasketRepository */
    public $repoBasket;
    /** @var ObjectRepository|ItemRepository */
    public $repoItem;
    /** @var ObjectRepository|OrderRepository */
    public $repoOrder;
    /** @var ObjectRepository|OrderHistoryRepository */
    public $repoOrderHistory;
    /** @var ObjectRepository|EshopOrderRepository */
    public $repoEshoOrder;
    /** @var ObjectRepository|EshopOrderPositionRepository */
    public $repoEshoOrderPosition;

    /**
     * @return string
     */
    private function getNameLogFile($id = null)
    {
        if ($id === null) {
            return $this->nameLogFile;
        } else {
            return $this->nameLogFile . '-' . $id;
        }
    }
    /**
     * @param Order $order
     * @param Basket|null $basket
     * @param null $title
     * @param null $errorMessage
     */
    public function sendOrderToLogProd(Order $order, Basket $basket = null, $title = null, $isError = false)
    {
        $message = null;
        /** @var Basket $basket */
        $basket = $basket ? $basket : $this->repoBasket->findOneBy(['orderId' => $order->getOrderId()]);
        if ($basket) {
            $items = $basket ? $this->repoItem->findBy(['basketId' => $basket->getId()]) : [];
            $itemsMessage = $this->itemsMessage($items);
            $basketMessage = $this->basketMessage($basket);
        } else {
            $itemsMessage = null;
            $basketMessage = null;
        }
        $orderMessage = $this->orderMessage($order);

        if ($isError) {
            $level = 'error';
        } else {
            $level = 'success';
        }
        $message[] = ['level' => $level];
        $message[] = ['action' => $title];
        $message[] = $orderMessage;
        $message[] = $basketMessage;
        $message[] = $itemsMessage;

        $this->logService->create(debug_backtrace()[0], $message);

    }

    /**
     * @param Order $order
     * @return array
     */
    private function orderMessage(Order $order): array
    {
        return [
            'order' => $order->getOrderId(),
            'status' => $order->getStatus(),
            'create' => $order->getCreated()->format('Y-m-d H:i:s'),
            'price' => $order->getPrice(),
            'cost' => $order->getCost(),
            'payment_type' => $order->getPaymentType(),
            'delivery_type' => $order->getDeliveryType(),
            'pointId' => $order->getDeliveryPointId(),
            'pointGln' => $order->getDeliveryPointGln(),
            'logagentName' => $order->getDeliveryLogagentName(),
            'customerName' => $order->getCustomerName(),
            'deliveryName' => $order->getDeliveryName(),
            'deliveryAddress' => $order->getDeliveryAddress()
        ];
    }

    /**
     * @param Basket $basket
     * @return array
     */
    private function basketMessage(Basket $basket): array
    {
        return [
            'basket' => $basket->getId(),
            'device' => $basket->getAnonimId(),
            'create' => $basket->getCreated()->format('Y-m-d H:i:s'),
            'coupons' => $basket->getCouponsJson(),
            'card' => $basket->getCardNum(),
            'earn' => $basket->getPointsForEarn(),
            'weight' => $basket->getWeight(),
            'volume' => $basket->getVolume(),
            'softcheque' => $basket->getSoftCheque(),
            'identifier' => $basket->getIdentifier()
        ];
    }

    /**
     * @param $items
     * @return array|string
     */
    private function itemsMessage($items)
    {
        $out = [];
        if ($items) {
            /** @var Item $item */
            foreach ($items as $item) {
                if (is_array($item)) {
                    $article = $item['article'] ?? null;
                    $cost = $item['cost'] ?? null;
                    $price = $item['price'] ?? null;
                    $qty = $item['quantity'] ?? null;
                    $weigth = $item['weight'] ?? null;
                    $volume = $item['volume'] ?? null;
                    $amount = $item['amounts'] ?? null;
                    $discount = $item['discount'] ?? null;
                    $discounts = $item['discounts'] ?? null;
                } else {
                    $article = $item->getArticle();
                    $cost = $item->getCost();
                    $price = $item->getPrice();
                    $qty = $item->getQuantity();
                    $weigth = $item->getWeight();
                    $volume = $item->getVolume();
                    $amount = $item->getAmounts();
                    $discounts = $item->getDiscountsJsonString(); //[{"campaigncode":2069581681,"campaignname":"15% для ИМ","discountcode":1184868550,"discountmode":9,"discountname":"15% для ИМ","discountrate":0,"discountsum":12,"discounttype":2,"ispositiondiscount":1,"minpriceignored":true,"posnum":1}]
                    $discount = $item->getDiscountSum();
                }
                $outItem['article'] = $article;
                $outItem['cost'] = $cost;
                $outItem['price'] = $price;
                $outItem['qty'] = $qty;
                $outItem['weigth'] = $weigth;
                $outItem['volume'] = $volume;
                $outItem['amount'] = $amount;
                $outItem['discount'] = $discount;
                $outItem['discounts'] = $discounts;
                $out[] = $outItem;
            }
        }

        return $out;
    }

    /**
     * @param $message
     */
    public function sendValidateErrorToLog($message)
    {
        $mess = $message['message'] ?? null;
        $requestBody = $message['request_body'] ?? null;
        $errors = $message['errors'] ?? null;

        $out = [
          'message' => $mess,
          'errors' => $errors
        ];
        $this->logService->create(debug_backtrace()[0], $out, 'ERROR', $requestBody);
    }

    /**
     * @param Basket|Order $object
     * @param Request $request
     */
    public function sendRequestToLog($object, Request $request)
    {
        $queryBody = $request->query->all();
        $requestBody = $request->request->all();
        $body = json_encode($queryBody + $requestBody, JSON_UNESCAPED_UNICODE);
        $out['logType'] = 'request';
        if ($object instanceof Basket) {
            $out['basketId'] = $object->getId();
            $out['type'] = 'basket';
        } else {
            $out['orderId'] = $object->getOrderId();
            $out['status'] = $object->getStatus();
            $out['type'] = 'order';
        }
        $out['price'] = $object->getPrice();
        $out['cost'] = $object->getCost();
        $out['body'] = $body;
        $this->logService->create(debug_backtrace()[0], $out);
    }

    /**
     * @param Basket $basket
     * @param $title
     * @param $body
     */
    public function sendRequestResponseCahboxToLog(Basket $basket, $title, $body)
    {
        if ($this->nameLogFile == 'info_basket') {
            $id = $basket->getId();
        } else {
            $id = $basket->getOrderId();
        }
        $userName = $this->getUser() ? $this->getUser()->getUsername() : null;
        $out['title'] = $title;
        $out['username'] = $userName;
        $out['basketId'] = $basket->getId();
        $out['price'] = $basket->getPrice();
        $out['cost'] = $basket->getCost();
        $out['body'] = $body;
        $this->logService->create(debug_backtrace()[0], $out);
    }

    /**
     * @param Order $order
     * @param $title
     * @param $body
     */
    public function sendRequestResponseRMLog(Order $order, $title, $body)
    {
        $userName = $this->getUser() ? $this->getUser()->getUsername() : null;
        $out['title'] = $title;
        $out['username'] = $userName;
        $out['orderId'] = $order->getOrderId();
        $out['price'] = $order->getPrice();
        $out['cost'] = $order->getCost();
        $out['body'] = $body;
        $this->logService->create(debug_backtrace()[0], $out);
    }
    /**
     * @param Order $order
     * @param $title
     * @param $result
     * @param null $mess
     */
    public function sendIntoLogOrder(Order $order, $title, $result, $mess = null)
    {
        /** @var Basket $basket */
        $basket = $order ? $this->repoBasket->findOneBy(['orderId' => $order->getOrderId()]) : new Basket();
        $items = $basket ? $this->repoItem->findBy(['basketId' => $basket->getId()]) : [];
        $items ? $basket->setItemsCount(count($items)) : null;
        $action = [$title => $result];
        $Items = $this->sendItems($items);
        $Basket = $this->sendBasket($basket);
        $Order = $this->sendOrder($order,$mess);
        $out[] = $action;
        $out[] = $Order;
        $out[] = $Basket;
        $out[] = $Items;
        $out[] = $mess;

        $this->logService->create(debug_backtrace()[0], $out);
    }
    /**
     * @param $items
     * @return array
     */
    private function sendItems($items = [])
    {
        $out = [];
        if ($items) {
            /** @var Item $item */
            foreach ($items as $item) {
                if (is_array($item)) {
                    $article = isset($item['article']) ? $item['article'] : null;
                    $cost = isset($item['cost']) ? $item['cost'] : null;
                    $price = isset($item['price']) ? $item['price'] : null;
                    $qty = isset($item['quantity']) ? $item['quantity'] : null;
                    $weigth = isset($item['weight']) ? $item['weight'] : null;
                    $volume = isset($item['volume']) ? $item['volume'] : null;
                    $amount = isset($item['amounts']) ? $item['amounts'] : null;
                    $discount = isset($item['discount']) ? $item['discount'] : null;
                } else {
                    $article = $item->getArticle();
                    $cost = $item->getCost();
                    $price = $item->getPrice();
                    $qty = $item->getQuantity();
                    $weigth = $item->getWeight();
                    $volume = $item->getVolume();
                    $amount = $item->getAmounts();
                    $discount = $item->getDiscountSum();
                }
                $out['article'] = $article;
                $out['price'] = $price;
                $out['cost'] = $cost;
                $out['qty'] = $qty;
                $out['weigth'] = $weigth;
                $out['volume'] = $volume;
                $out['amount'] = $amount;
                $out['discount'] = $discount;
            }
        }

        return $out;
    }
    /**
     * @param Basket $basket
     * @return array
     */
    private function sendBasket(Basket $basket = null, $mess=null)
    {
        $out = [];
        if ($basket) {
            $out['id'] = $basket->getId();
            $out['mess'] = is_array($mess) ? json_encode($mess, JSON_UNESCAPED_UNICODE) : $mess;
            $out['orderId'] = $basket->getOrderId();
            $out['price'] = $basket->getPrice();
            $out['cost'] = $basket->getCost();
            $out['cardNum'] = $basket->getCardNum();
            $out['coupons'] = json_encode($basket->getCoupons(), JSON_UNESCAPED_UNICODE);
            $out['identifier'] = $basket->getIdentifier();
            $out['softCheque'] = $basket->getSoftCheque();
            $out['anonimId'] = $basket->getAnonimId();
            $out['created'] = $basket->getCreated()->format('Y-m-d H:i:s');
            $out['weigth'] = $basket->getWeight();
            $out['volume'] = $basket->getVolume();
            $out['itemsCount'] = 'items: ' . $basket->getItemsCount();
        } else {
            $out = [];
        }

        return $out;
    }
    /**
     * @param Order $order
     * @return array
     */
    private function sendOrder(Order $order = null, $mess=null)
    {
        if (!$order) {

            return [];
        }
        $out['orderId'] = $order->getOrderId();
        $out['status'] = $order->getStatus();
        $out['mess'] = is_array($mess) ? json_encode($mess, JSON_UNESCAPED_UNICODE) : $mess;
        $out['price'] = $order->getPrice();
        $out['cost'] = $order->getCost();
        $out['deliveryType'] = $order->getDeliveryType();
        $out['paymentType'] = $order->getPaymentType();
        $out['deliveryPointId'] = $order->getDeliveryPointId();
        $out['userId'] = $order->getUserId();
        $out['eshopErrorMessage'] = $order->getEshopErrorMessage();
        $out['created'] = $order->getCreated()->format('Y-m-d H:i:s');
        $out['updated'] = $order->getUpdated()->format('Y-m-d H:i:s');
        $out['eshopDate'] = ((($order->getEshopDate()) ? $order->getEshopDate()->format('Y-m-d H:i:s') : null));

        return $out;
    }
    /**
     * @param Basket $basket
     * @param $title
     * @param $result
     * @param null $mess
     * @return Item[]|\object[]
     */
    public function sendBasketToLog(Basket $basket, $title, $result, $mess=null)
    {
        $items = $this->repoItem->findBy(['basketId' => $basket->getId()]);
        $basket->setItemsCount(count($items));

        $action = [$title => $result];
        $Items = $this->sendItems($items);
        $Basket = $this->sendBasket($basket,$mess);
        $out[] = $action;
        $out[] = $Basket;
        $out[] = $Items;
        if ($this->nameLogFile == 'info_basket') {
            $id = $basket->getId();
        } else {
            $id = $basket->getOrderId();
        }
        $this->logService->create(debug_backtrace()[0], json_encode($out, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        //(Basket $basket, $items, $action, $mess=null, $userName=null)
        //($basket, 'add_items-', $result, $message)

        return $items;
    }
    /**
     * @param $orders
     * @param $action
     */
    public function sendIntoLogOrders($orders, $action)
    {
        if ($orders) {
            foreach ($orders as $order) {
                $this->sendIntoLogOrder($order, null, $action);
            }
        }
    }
}