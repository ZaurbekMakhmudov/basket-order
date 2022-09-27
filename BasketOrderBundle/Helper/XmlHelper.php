<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 06.09.19
 * Time: 17:35
 */

namespace App\BasketOrderBundle\Helper;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Order;
use Symfony\Component\HttpFoundation\Response;

class XmlHelper
{
    static public function getDomDocument(Order $order, Basket $basket, array $items)
    {
        $dom = new \DOMDocument();
        $error = $code = $line = null;
        try {
            $orderBlock = XmlHelper::getOrder($dom, $order, $basket);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
            $line = $e->getLine();
        }
        try {
            $customer = XmlHelper::getCustomer($dom, $order);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
            $line = $e->getLine();
        }
        try {
            $delivery = XmlHelper::getDelivery($dom, $order);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
            $line = $e->getLine();
        }
        try {
            $orderItems = XmlHelper::getItems($dom, $items, $order);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
            $line = $e->getLine();
        }
        try {
            $payment = XmlHelper::getPayment($dom, $order);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
            $line = $e->getLine();
        }
        if ($error) {
            $result = Response::HTTP_INTERNAL_SERVER_ERROR;
            $errorMessage = 'xml processing error: ' . $error;
            $out = [
                'result' => $result,
                'message' => $errorMessage,
                'error' => $error,
                'code' => $code,
                'line' => $line,
            ];

            return $out;
        }
        $orderBlock->appendChild($customer);
        $orderBlock->appendChild($delivery);
        $orderBlock->appendChild($orderItems);
        $orderBlock->appendChild($payment);
        $dom->appendChild($orderBlock);
        $xmlContent = $dom->saveXML();
        $xmlContent = XmlHelper::prepareXml($xmlContent);

        $out = [
            'result' => Response::HTTP_OK,
            'xmlContent' => $xmlContent,
        ];

        return $out;
    }

    /**
     * @param \DOMDocument $dom
     * @param Order $order
     * @param Basket $basket
     * @return \DOMElement
     */
    static public function getOrder(\DOMDocument $dom, Order $order, Basket $basket)
    {
        $orderBlock = $dom->createElement('order');
        self::appendCreatedElement($dom, $orderBlock, 'order_number',         $order->getOrderId() );
        self::appendCreatedElement($dom, $orderBlock, 'order_status',         $order->getOrderStatus() );
        self::appendCreatedElement($dom, $orderBlock, 'order_number_partner', $order->getOrderIdPartner() );
        self::appendCreatedElement($dom, $orderBlock, 'user_id',              $order->getUserId() );
        self::appendCreatedElement($dom, $orderBlock, 'order_source',         $order->getSourceIdentifier() );
        self::appendCreatedElement($dom, $orderBlock, 'transaction',          $order->getPacketId() );
        self::appendCreatedElement($dom, $orderBlock, 'nom_card',             $basket->getCardNum() );
        self::appendCreatedElement($dom, $orderBlock, 'nom_card_partner',     $basket->getCardNumPartner() );
        self::appendCreatedElement($dom, $orderBlock, 'type_opl',             $order->getPaymentType() );
        self::appendCreatedElement($dom, $orderBlock, 'order_bonus',          $basket->getPointsForEarn() );
        self::appendCreatedElement($dom, $orderBlock, 'summa_first',          $order->getPrice() );
        self::appendCreatedElement($dom, $orderBlock, 'summa',                $order->getCost() );
        self::appendCreatedElement($dom, $orderBlock, 'comment',              $order->getComment() );
        self::appendCreatedElement($dom, $orderBlock, 'order_date',           $order->getOrderDate()->format('Y-m-d H:i:s') );
        self::appendCreatedElement($dom, $orderBlock, 'actions',              json_encode($order->getActions(), JSON_UNESCAPED_UNICODE) );
        $order->isConfirm() ? self::appendCreatedElement($dom, $orderBlock, 'soft_cheque', $basket->getSoftCheque() ) : null;

        return $orderBlock;
    }

    /**
     * @param \DOMDocument $dom
     * @param Order $order
     * @return \DOMElement
     */
    static public function getCustomer(\DOMDocument $dom, Order $order)
    {
        $customer = $dom->createElement('customer');
        self::appendCreatedElement($dom, $customer, 'customer_city',       $order->getCustomerCity() );
        self::appendCreatedElement($dom, $customer, 'customer_post_index', $order->getCustomerPostIndex() );
        self::appendCreatedElement($dom, $customer, 'customer_street',     $order->getCustomerStreet() );
        self::appendCreatedElement($dom, $customer, 'customer_house',      $order->getCustomerHouse() );
        self::appendCreatedElement($dom, $customer, 'customer_building',   $order->getCustomerBuilding() );
        self::appendCreatedElement($dom, $customer, 'customer_flat',       $order->getCustomerFlat() );
        self::appendCreatedElement($dom, $customer, 'customer_name',       $order->getCustomerName() );
        self::appendCreatedElement($dom, $customer, 'customer_phone',      $order->getCustomerPhone() );
        self::appendCreatedElement($dom, $customer, 'customer_email',      $order->getCustomerEmail() );
        self::appendCreatedElement($dom, $customer, 'customer_comment',    $order->getCustomerComment() );

        return $customer;
    }

    /**
     * @param \DOMDocument $dom
     * @param Order $order
     * @return \DOMElement
     */
    static public function getDelivery(\DOMDocument $dom, Order $order)
    {
        $delivery = $dom->createElement('delivery');
        $datDeliveryStart = DateTimeHelper::getInstance()->getDateString($order->getOrderDeliveryLogagentDate(), null, true);
        self::appendCreatedElement($dom, $delivery, 'delivery_type',        $order->getDeliveryType() );
        self::appendCreatedElement($dom, $delivery, 'id_delivery_point',    $order->getDeliveryPointId() );
        self::appendCreatedElement($dom, $delivery, 'delivery_point_gln',   $order->getDeliveryPointGln() );
        self::appendCreatedElement($dom, $delivery, 'logagent_gln',         $order->getLogagentGln() );
        self::appendCreatedElement($dom, $delivery, 'delivery_sum',         $order->getDeliveryCostSum() );
        self::appendCreatedElement($dom, $delivery, 'delivery_sum_partner', $order->getDeliveryCostSumPartner() );
        self::appendCreatedElement($dom, $delivery, 'delivery_sum_first',   $order->getDeliveryCostSum() );
        self::appendCreatedElement($dom, $delivery, 'dat_delivery_start',   $datDeliveryStart );
        self::appendCreatedElement($dom, $delivery, 'dat_delivery_end',     $order->getDeliveryLogagentTime() );
        self::appendCreatedElement($dom, $delivery, 'delivery_scheme',      $order->getDeliveryScheme() );

        return $delivery;
    }

    /**
     * @param \DOMDocument $dom
     * @param $items
     * @return \DOMElement
     */
    static public function getItems(\DOMDocument $dom, $items, Order $order)
    {
        $orderItems = $dom->createElement('order_items');
        if ($items) {
            foreach ($items as $item) {
                $orderItem = $dom->createElement('item');
                self::appendCreatedElement($dom, $orderItem, 'item_id',     $item['item_id']);
                self::appendCreatedElement($dom, $orderItem, 'id_good',     $item['id_good']);
                self::appendCreatedElement($dom, $orderItem, 'kol_good',    $item['kol_good']);
                self::appendCreatedElement($dom, $orderItem, 'price_first', $item['price_first']);
                self::appendCreatedElement($dom, $orderItem, 'skid',        $item['skid']);
                self::appendCreatedElement($dom, $orderItem, 'price',       $item['price']);
                self::appendCreatedElement($dom, $orderItem, 'sto_good',    $item['sto_good']);
                self::appendCreatedElement($dom, $orderItem, 'ean',         $item['barcode']);
                self::appendCreatedElement($dom, $orderItem, 'bonus',       $item['bonus']);
                self::appendCreatedArrayElement($dom, $orderItem, 'discounts', $item['discounts'], 'discount');
                self::appendCreatedArrayElement($dom, $orderItem, 'earnedbonuses', $item['earnedBonuses'], 'earnedbonus');
                if ( !empty($item['excisemark']) ) {
                    foreach ($item['excisemark'] as $excisemark){
                        $labels = $dom->createElement('labels');
                        self::appendCreatedElement($dom, $labels, 'label', array_pop($excisemark));
                        $orderItem->appendChild($labels);
                    }
                } else {
                    $labels = $dom->createElement('labels');
                    $orderItem->appendChild($labels);
                }

                $orderItems->appendChild($orderItem);
            }
        }

        return $orderItems;
    }

    /**
     * @param \DOMDocument $dom
     * @param Order $order
     * @return \DOMElement
     */
    static public function getPayment(\DOMDocument $dom, Order $order) {
        $payment = $dom->createElement('payment');
        $payDate = DateTimeHelper::getInstance()->getDateString($order->getPaymentInformationDate(), null, true);
        self::appendCreatedElement($dom, $payment, 'pay_status', $order->getPaymentInformationStatus() );
        self::appendCreatedElement($dom, $payment, 'pay_summa',  $order->getPaymentInformationAmount() );
        self::appendCreatedElement($dom, $payment, 'pay_date',   $payDate );

        return $payment;
    }

    static public function prepareXml($xmlContent)
    {
        $xmlContent = str_replace('<?xml version="1.0"?>', '', $xmlContent);
        $xmlContent = str_replace("\n", '', $xmlContent);

        return $xmlContent;
    }

    static private function appendCreatedElement($dom, $parent, $elementName, $elementValue)
    {
        !is_null($elementValue) ? $parent->appendChild( $dom->createElement($elementName, $elementValue) ) : null;
    }

    static private function appendCreatedArrayElement($dom, $parent, $rootElementName, $rootElementValues, $childElementName = null)
    {
        if( $rootElementValues ) {
            $rootElement = $dom->createElement($rootElementName);
            foreach ($rootElementValues as $rootElementValue) {
                $childElement = $dom->createElement($childElementName);
                foreach($rootElementValue as $key => $val) {
                    $val ? $childElement->appendChild( $dom->createElement($key, $val) ) : null;
                }
                $rootElement->appendChild($childElement);
            }
            $parent->appendChild($rootElement);
        }
    }
}