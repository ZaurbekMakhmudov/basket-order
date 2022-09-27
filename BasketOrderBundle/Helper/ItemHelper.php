<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 06.12.19
 * Time: 18:41
 */

namespace App\BasketOrderBundle\Helper;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;

class ItemHelper
{
    /**
     * @param $itemData
     * @param $items
     * @return Item|null
     */
    static public function addItems($itemData, $items)
    {
        $out = null;
        if($items){
            $articleData = isset($itemData['article']) ? $itemData['article'] : null;
            $barcodeData = isset($itemData['barcode']) ? $itemData['barcode'] : null;
            $articleData = $articleData ? $articleData : $barcodeData;

            /** @var Item $item */
            foreach ($items as $item){
                $article = $item->getArticle();
                $barcode = $item->getBarcode();
                $cost = $item->getCost();
                $article = $article ? $article : $barcode;
                if($article == $articleData and $cost>0){
                    $out = $item;
                    break;
                }
            }
        }

        return $out;
    }
    static public function getItemsForOrder($itemData)
    {
        $items = [];
        if($itemData){
            foreach ($itemData as $item) {
                $quantity = $item['quantity'];
                $article = $item['article'];
                if (isset($item['label_type']) && isset($item['lables'])) {
                    $tmctype = $item['label_type'];
                    $excisemark = $item['lables'];
                    $items[$article] = [
                        'quantity' => $quantity,
                        'article' => $article,
                        'tmctype' => $tmctype,
                        'excisemark' => $excisemark,
                    ];
                } else {
                    $items[$article] = [
                        'quantity' => $quantity,
                        'article' => $article,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @param $article
     * @param $cost
     * @return string
     */
    static public function getKeyItem($article, $cost)
    {
        $cost = (boolean)$cost;
        $cost = (string)$cost;
        $key = $article . '_' . $cost;

        return $key;
    }

    /**
     * @param Basket $basket
     * @param $items
     * @return array
     */
    static public function getItemByCashbox(Basket $basket, $items, $storeId=null)
    {
        $out = $baskets = [];
        if ($items) {
            foreach ($items as $key=>$item) {
                if (is_array($item)) {
                    $article = $item['article'];
                    $quantity = $item['quantity'];
                    $barcode = $item['barcode'];
                } else {
                    /** @var Item $item */
                    $article = $item->getArticle();
                    $quantity = $item->getQuantity();
                    $barcode = $item->getBarcode();
                }
                if($quantity > 0){
                    $out[$key]['quantity'] = $quantity;
                    $out[$key]['article'] = $article ;
                }
            }
        }
        if(($out)){
            $coupons = [];
            $items = $basket->getCoupons();
            if ($items) {
                foreach ($items as $item) {
                    $coupons[] = ['number' => $item['number']];
                }
            }
            $baskets = [
                'basketId' => $basket->getId(),
                'items' => $out,
                'coupons' => $coupons,
            ];

            $cardNumber = $basket->getCardNum();
            if (!empty($cardNumber)) {
                $baskets['cardNumber'] = $cardNumber;
            }
        }

        return $baskets;
    }
    /**
     * @param $output
     * @return array
     */
    static public function getItemsOut($output)
    {
        $out = [];
        $itemsOut = isset($output['items']) ? $output['items'] : [];
        foreach ($itemsOut as $itemOut) {
            $article = $itemOut['article'];
            $barcode = $itemOut['barcode'];
            $cost = $itemOut['cost'];
            $article = $article ? $article : $barcode;
            $key = self::getKeyItem($article, $cost);
            if(!isset($out[$key])) {
                $out[$key] = $itemOut;
            } else {
                $out[$key]['quantity'] += $itemOut['quantity'];
                $out[$key]['cost'] = round(
                    (($itemOut['cost'] + $out[$key]['cost']) / $out[$key]['quantity']) * $out[$key]['quantity'],
                    2
                );
            }
        }

        return $out;
    }
    static public function getCashboxOptions($postData, $urlCashBox)
    {
        $options = [
            CURLOPT_URL => $urlCashBox,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'X-RAINBOW-ESHOP-KEY:123456'
            ],
        ];

        return $options;
    }

    /**
     * @param Order $order
     * @param array|null $data
     * @return array
     */
    static public function communicatorData(Order $order, Array $data = null)
    {
        $communicatorData = [
            'doc' => $data ? $data : [
                "orderNumber" => $order->getOrderId(),
                "status" => $order->getStatus(),
            ],
            'params' => [
                'sender' => 'eshop',
            ]
        ];

        return $communicatorData;
    }
    static public function getArticleItem($requestBody)
    {
        $article = ($requestBody and isset($requestBody['article'])) ? $requestBody['article'] : null;
        $barcode = ($requestBody and isset($requestBody['barcode'])) ? $requestBody['barcode'] : null;
        $article = $article ? $article : $barcode;

        return $article;
    }
    static public function aggrOrderItemsArray($items)
    {
        $out = [];
        if ($items) {
            /** @var Item $item */
            foreach ($items as $key=>$item) {
                $article = $item->getArticle();
                $quantity = $item->getQuantity();
                $cost = round($item->getCost(true), 2);
                $costOneUnit = round((float)$item->getCostOneUnit(), 2);
                $price = round($item->getPrice(), 2);
                $skid = round($price * $quantity - $cost, 2);
                $discounts = $item->getDiscounts();
                $earnedBonuses = $item->getEarnedBonuses();
                if (!isset($out[$article])) {
                    $out[$article] = [
                        'item_id' => $item->getId(),
                        'id_good' => $item->getArticle(),
                        'kol_good' => $item->getQuantity(),
                        'price_first' =>  round($item->getPrice(), 2),
                        'skid' => round($item->getPrice() * $item->getQuantity() - $item->getCost(true), 2),
                        'price' => round($item->getCostOneUnit(), 2),
                        'sto_good' => round($item->getCost(true), 2),
                        'bonus' => $item->getBonus(),
                        'article' => $item->getArticle(),
                        'name' => $item->getName(),
                        'amount' => $item->getQuantity(),
                        'amounts' => $item->getAmounts(),
                        'cost' => round($item->getCost(), 2),
                        'costOneUnit' => round($item->getCostOneUnit(), 2),
                        'barcode' => $item->getBarcode(),
                        'discountName' => $item->getDiscountName(),
                        'discountCode' => $item->getDiscountCode(),
                        'discounts' => $item->getDiscounts(),
                        'earnedBonuses' => $item->getEarnedBonuses(),
                        'excisemark' => $item->getExcisemark(),
                    ];
                } else {
                    $out[$article]['costOneUnit'] = round(
                        ( $costOneUnit * $quantity + $out[$article]['costOneUnit'] * $out[$article]['kol_good'] ) /
                        ( $quantity + $out[$article]['kol_good'] ),
                        3);
                    $out[$article]['skid']+=$skid;
                    $out[$article]['kol_good']+=$quantity;
                    $out[$article]['amount']+=$quantity;
                    $out[$article]['discounts'] = array_merge($out[$article]['discounts'], $discounts);
                    $out[$article]['earnedBonuses'] = array_merge($out[$article]['earnedBonuses'], $earnedBonuses);
                }
            }
        }

        return $out;
    }

    /**
     * @param array $items
     * @return array
     */
    static public function aggrOrderCashboxItemsArray(array $items): array
    {
        $itemId = 0;
        $out = [];
        if ($items) {
            /** @var Item $item */
            foreach ($items as $key=>$item) {
                ++$itemId;
                $article = $item->getArticle();
                $quantity = $item->getQuantity();
                $cost = $item->getCost(true);
                $costOneUnit = $item->getCostOneUnit();
                $skid = round($item->getPrice() * $quantity - $cost, 2);
                $discounts = $item->getDiscounts();
                $earnedBonuses = $item->getEarnedBonuses();
                $excisemark = $item->getExcisemark();
                if (!isset($out[$article])) {
                    $out[$article] = [
                        'item_id' => $itemId,
                        'id_good' => $item->getArticle(),
                        'kol_good' => $item->getQuantity(),
                        'price_first' =>  $item->getPrice(), // цена без скидки (единицы товара)
                        'skid' => round($item->getPrice() * $item->getQuantity() - $item->getCost(true), 2), // сумма скидки (позиции суммарно)
                        'price' => $item->getCostOneUnit(), // цена со скидкой (единицы товара) = costOneUnit
                        'sto_good' => $item->getCost(true), // сумма (позиции суммарно)
                        'bonus' => $item->getBonus(),
                        'article' => $item->getArticle(),
                        'name' => $item->getName(),
                        'barcode' => $item->getBarcode(),
                        'discounts' => $item->getDiscounts(),
                        'earnedBonuses' => $item->getEarnedBonuses(),
                        'excisemark' => $item->getExcisemark(),
                    ];
                } else {
                    $out[$article]['price'] = round(
                        ( $costOneUnit * $quantity + $out[$article]['price'] * $out[$article]['kol_good'] ) /
                        ( $quantity + $out[$article]['kol_good'] ), 3);
                    $out[$article]['skid']+= $skid;
                    $out[$article]['sto_good'] = $cost + $out[$article]['sto_good'];
                    $out[$article]['kol_good'] = $quantity + $out[$article]['kol_good'];
                    $out[$article]['discounts'] = self::mergeArrays($out[$article]['discounts'], $discounts);
                    $out[$article]['earnedBonuses'] = self::mergeArrays($out[$article]['earnedBonuses'], $earnedBonuses);
                    $out[$article]['excisemark'] = self::mergeArrays($out[$article]['excisemark'], $excisemark);
                }
            }
        }

        return $out;
    }

    /**
     * @param array|null $array1
     * @param array|null $array2
     * @return array|null
     */
    static private function mergeArrays(array $array1 = null, array $array2 = null): ?array
    {
        $arrayOut = null;
        if(!is_null($array1)) {
            if(!is_null($array2)) {
                $arrayOut = array_merge($array1, $array2);
            } else {
                $arrayOut = $array1;
            }
        } else {
            if(!is_null($array2)) {
                $arrayOut = $array2;
            }
        }

        return $arrayOut;
    }

    static public function getItemsGW($items)
    {
        $out = [];
        foreach ($items as $item) {
            $id = $item['product_id'];
            $amount = $item['product_amount'];
            $cost = $item['product_unit_price'];
            $out[$id] = $item;
        }

        return $out;
    }

    /**
     * @param $items
     * @return array
     */
    static public function itemsArray($items)
    {
        foreach ($items as $item) {
          $row = [
            'item_id' => $item->getId(),
            'id_good' => $item->getArticle(),
            'kol_good' => $item->getQuantity(),
            'price_first' =>  $item->getPrice(),
            'skid' => round($item->getPrice() * $item->getQuantity() - $item->getCost(true), 2),
            'price' => $item->getCostOneUnit(),
            'sto_good' => $item->getCost(true),
            'bonus' => $item->getBonus(),
            'article' => $item->getArticle(),
            'name' => $item->getName(),
            'amount' => $item->getQuantity(),
            'amounts' => $item->getAmounts(),
            'cost' => $item->getCost(),
            'costOneUnit' => $item->getCostOneUnit(),
            'barcode' => $item->getBarcode(),
            'discountName' => $item->getDiscountName(),
            'discountCode' => $item->getDiscountCode(),
          ];
            $out[] = $row;
        }

        return $out;
    }
}
