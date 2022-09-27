<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 02.12.19
 * Time: 22:47
 */

namespace App\BasketOrderBundle\Traits;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Era\EshopOrder;
use App\BasketOrderBundle\Era\EshopOrderPosition;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use Symfony\Component\HttpFoundation\Response;

trait _EshopServiceTrait
{
    public function sendEshopOrderData(Order $order, Basket $basket, $items, $forcedStatus = null)
    {
        $today = DateTimeHelper::getInstance()->getDateCurrent();
        /** @var EshopOrder $eOrder */
        $status = $forcedStatus ?: $order->getStatus();
        $eOrder = $this->repoEshoOrder->findOneBy(['order_id' => $order->getOrderId(), 'order_status' => $status]);
        if ($eOrder) {
            $shopDate = $eOrder->getProcessedByEshopDate();
            if (!$shopDate) {
                $eOrder->setProcessedByEshopDate($today);
                $this->_persist($eOrder, 'era');
                $message = 'update date for eshoporder: ' . DateTimeHelper::getInstance()->getDateString();
            } else {
                $message = 'date for eshoporder is exist: ' . $shopDate->format('Y-m-d H:i:s');
            }
        } else {
            $eOrder = $this->setEshopFields($order,$basket,$items, $forcedStatus);
            $message = 'send data to Eshop';
        }

        $out = [
            'result' => Response::HTTP_OK,
            'message' => $message,
            'eshopOrder' => $eOrder, //->iterateVisible(),
        ];
        $str = json_encode($out, JSON_UNESCAPED_UNICODE);

        return $out;
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @param $items
     * @param null $forcedStatus
     * @return EshopOrder
     */
    protected function setEshopFields(Order $order,Basket $basket, $items, $forcedStatus = null)
    {
        $today = DateTimeHelper::getInstance()->getDateCurrent();
        /** @var Basket $basket */
        $order->setEshopOrderData($today);

        $eshopOrder = new EshopOrder();
        $status = $forcedStatus ?: $order->getStatus();

        $eshopOrder->setPacketId($order->getPacketId());
        $eshopOrder->setOrderId($order->getOrderId());
        $eshopOrder->setOrderStatus($status);

        $nomCard = $basket->getCardNum();
        $nomCard ? $eshopOrder->setUserDcardId($nomCard) : $eshopOrder->setUserDcardId('');

        $eshopOrder->setOrderDate($order->getEshopDate());
        $eshopOrder->setOrderPaymentType($order->getPaymentType());
        $eshopOrder->setProductPricelistId($order->getProductPricelistId());

        $eshopOrder->setProcessedByEshopErrorMessage($order->getProcessedByEshopErrorMessage());
        $eshopOrder->setProcessedByEshopDate($today);

        $deliveryType = $this->converDeliveryCode($order);
        $eshopOrder->setOrderDeliveryType($deliveryType);
        $eshopOrder->setEsDeliveryScheme($order->getDeliveryScheme());

        $eshopOrder->setOrderLogagentName($order->getDeliveryLogagentName());
        $eshopOrder->setOrderLogagentPhone($order->getDeliveryLogagentPhone());
        $eshopOrder->setOrderLogagentEmail($order->getDeliveryLogagentEmail());

        $eshopOrder->setOrderDeliveryLogagentGln($order->getOrderDeliveryLogagentGln());
        $eshopOrder->setOrderDeliverypointName($order->getDeliveryName());
        $eshopOrder->setOrderDeliverypointPhone($order->getDeliveryPhone());
        $eshopOrder->setOrderDeliverypointEmail($order->getDeliveryEmail());
        $eshopOrder->setOrderDeliverypointAddress($order->getDeliveryAddress());
        $eshopOrder->setOrderDeliverypointGln($order->getDeliveryPointGln());

        $eshopOrder->setOrderDeliveryCustomerStreet($order->getCustomerStreet());
        $eshopOrder->setOrderDeliveryCustomerBuilding($order->getCustomerBuilding());
        $eshopOrder->setOrderDeliveryCustomerFlat($order->getCustomerFlat());
        $eshopOrder->setOrderDeliveryCustomerHouse($order->getCustomerHouse());

        $eshopOrder->setOrderDeliveryCustomerCity($order->getCustomerCity());
        $eshopOrder->setOrderDeliveryCustomerPostIndex($order->getCustomerPostIndex());

        $date = DateTimeHelper::getInstance()->getDateOnly($order->getCustomerDate());
        $eshopOrder->setOrderDeliveryCustomerDate($date);
        $eshopOrder->setOrderDeliveryCustomerTime($order->getCustomerTime());

        $eshopOrder->setOrderCustomerName($order->getCustomerName());
        $eshopOrder->setOrderCustomerPhone($order->getCustomerPhone());
        $eshopOrder->setOrderCustomerEmail($order->getCustomerEmail());

        $eshopOrder->setCustomerComment($order->getCustomerComment());

        $date = DateTimeHelper::getInstance()->getDateOnly($order->getCustomerDesiredDate());
        $eshopOrder->setCustomerDesiredDate($date);
        $eshopOrder->setCustomerDesiredTimeFrom($order->getCustomerDesiredTimeFrom());
        $eshopOrder->setCustomerDesiredTimeTo($order->getCustomerDesiredTimeTo());

        $eshopOrder->setClientId($order->getClientId());
        $eshopOrder->setOrderCustomerEmail($order->getCustomerEmail());

        $eshopOrder->setDateInsert($today->format(ShopConst::ORDER_FORMAT_DATE_TIME));

        $summa = $order->getOrderSum();
        $eshopOrder->setDeliveryCostSum($order->getDeliveryCostSum());
        $eshopOrder->setOrderSum($summa);

        $eshopOrder->setOrderSourceIdentifier($order->getSourceIdentifier());
        $eshopOrder->setProductPricelistParam($order->getProductPricelistParam());

        $clientId = $order->getClientId(); //        "client_id": "9900000001",
        if ($clientId) {
            $eshopOrder->setClientId($clientId);
        } else {
            $eshopOrder->setClientId("9900000001");
        }
        $eshopOrder->setOrderDeliveryLogagentDate($order->getDeliveryLogagentDate());
        $eshopOrder->setOrderDeliveryLogagentTime($order->getDeliveryLogagentTime());
        $eshopOrder->setOrderDeliveryLogagentGln($order->getOrderDeliveryLogagentGln());
        $eshopOrder->setOrderDeliverypointGln($order->getDeliveryPointGln());
        $eshopOrder->setOrderDeliverypointName($order->getDeliveryName());

        $orderPositions = [];
        if ($items) {
            foreach ($items as $item) {
                $eshopOrderPosition = new EshopOrderPosition();
                $number = $order->getOrderId();
                $article = $item['article']; //->getArticle();
                $name = $item['name']; //->getName();
                $amount = $item['amount']; //->getQuantity();
                $amounts = $item['amounts'] ? $item['amounts'] : 0; //->getAmounts();
                $cost = $item['cost'] ? $item['cost'] : 0;
                $costOneUnit = $item['costOneUnit'] ? $item['costOneUnit'] : 0; //->getCostOneUnit();
                $barcode = $item['barcode']; //->getBarcode();
                $eshopOrderPosition->setProductAmount($amount) ;
                $eshopOrderPosition->setProductUnitPrice($costOneUnit) ;

                $number ? $eshopOrderPosition->setOrderId($number) : null;
                $article ? $eshopOrderPosition->setProductId($article) : null;
                $name ? $eshopOrderPosition->setProductName($name) : null;
                $barcode ? $eshopOrderPosition->setProductEan($barcode) : null;
                $eshopOrderPosition->setProductDiscount(0);
                $eshopOrderPosition->setProcessedByEshopDate($today);
                $eshopOrderPosition->setPacketId($order->getPacketId());
                $eshopOrderPosition->setBonusEarn($amounts);
                $eshopOrderPosition->setCoupon($this->makeEshopCoupon($order, $item));
                $eshopOrderPosition->setStoGood($cost);
                $orderPositions[] = $eshopOrderPosition;
                $this->_persist($eshopOrderPosition, 'era');
            }
        }
        $this->_persist($order);
        $this->_persist($eshopOrder, 'era');

        return $eshopOrder;

//        МП и Сайт
//Курьер и ПВЗ
//подробно в файле
//victor чобы тебя не грузить напишу тут основное
//также для E
//для типа заказ E  нужно в поле order_deliverypoint_name просто заполнить город доставки
//'order_delivery_logagent_gln = 1000043430' всегда
//'order_deliverypoint_gln = 1000043430/Курьер' всегда

//для типа заказа W
//order_deliverypoint_gln
//заполняется не тем GLN
//я не скажу как в интеграции поля называются, но там должно быть 2ое полее с коротким GLN
//они оба отличаются только числом на конце 1001
//вот сейчас берется длинное с 1001, а надо брать без 1001
//все отражено в комнтариях и примечаниях в файле
//это пока все
//вопросы?)

//  CRE E                                       CRE W
//"2832840"                                 //"2836178"	                                                     `id` INT(11) NOT NULL AUTO_INCREMENT,
//helper::getGuid()           "{4B787550-EA57-6A60-DBE6-E94F04E79888}"                                        packet_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ID пакета для контроля порций обмена',
//order::order_number         "UR-206473"                               //"UR-206640"	                     `order_id` VARCHAR(255) NULL DEFAULT '' COMMENT 'id заказа',
//order::status             "CRE"                                     //"CRE"	                             `order_status` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Статус заказа в eshop',
//order::cost               "2123,00"                                 //"853,00"	                         `order_sum` DECIMAL(15,2) NULL DEFAULT '0.00' COMMENT 'Сумма заказа',
//order::created         "2019-07-17 03:06:47"                     //"2019-07-18 17:53:34"	                 `order_date` DATETIME NULL DEFAULT NULL COMMENT 'Дата заказа',
//order::type_opl                           "0"                                       //"0"	                 `order_payment_type` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Тип оплаты',
//                  "6000000009"                              //"6000000009"	                             `order_source_identifier` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Идентификатор источника заказа',
//"E"                                       //"W"	                                                         `order_delivery_type` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Способ доставки: Доставка или Самовывоз',
//""                                        //"Логистика Сервис"	                                         `order_logagent_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Наименование логагента',
//\N                                        //\N	                                                         `order_logagent_phone` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Телефон логагента',
//\N                                        //\N	                                                         `order_logagent_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'E-mail логагента',
//\N                                        //\N	                                                         `order_delivery_address` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Адрес доставки',
//"2019-07-18 03:06:47"                     //"2019-07-19 17:53:34"	                                         `order_delivery_logagent_date` DATETIME NULL DEFAULT NULL COMMENT 'Дата доставки логагенту',
//\N                                        //\N	                                                         `order_delivery_logagent_time` VARCHAR(32) NULL DEFAULT NULL COMMENT 'Желаемое время доставки логагенту (строка)',
//"1000043430"                              //"9760000000"	                                                 `order_delivery_logagent_gln` VARCHAR(13) NULL DEFAULT NULL COMMENT 'Код логагента в системе партнёра',
//"Санкт-Петербург"                         //"Магазин ""Улыбка Радуги"" - Санкт-Петербург, пр. Ветеранов"	 `order_deliverypoint_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Наименование ПВЗ',
//\N                                        //\N	                                                         `order_deliverypoint_phone` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Телефон ПВЗ',
//\N                                        //\N	                                                         `order_deliverypoint_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'E-mail ПВЗ',
//""                                        //"Санкт-Петербург, Ветеранов пр., д. 105"	                     `order_deliverypoint_address` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Адрес ПВЗ',
//"1000043430/Курьер"                       //"4607181504370"	                                             `order_deliverypoint_gln` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Код деливерипоинта в системе партнёра',
//\N                                        //\N	                                                         `order_delivery_customer_time` VARCHAR(11) NULL DEFAULT NULL COMMENT 'Желаемое время доставки конечному покупателю (строка)',
//"Санкт-Петербург"                         //"Санкт-Петербург"	                                             `order_delivery_customer_city` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Город конечного покупателя',
//"ш. Ланское"                              //\N	                                                         `order_delivery_customer_street` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//"1"                                       //\N	                                                         `order_delivery_customer_building` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//"710"                                     //\N	                                                         `order_delivery_customer_flat` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//"190000"                                  //"0"	                                                         `order_delivery_customer_post_index` INT(8) NULL DEFAULT NULL,
//"14"                                      //\N	                                                         `order_delivery_customer_house` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//\N                                        //\N	                                                         `order_delivery_customer_date` DATETIME NULL DEFAULT NULL COMMENT 'Дата доставки конечному покупателю',
//"Олеся"                                   //"Александра"	                                                 `order_customer_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ФИО конечного покупателя',
//"+70001234567"                            //"+70001234567"	                                             `order_customer_phone` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Телефон конечного покупателя',
//"name@mail.ru"                            //"name@mail.ru"	                                             `order_customer_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'E-mail конечного покупателя',
//"0,00"                                    //"0,00"	                                                     `delivery_cost_sum` DECIMAL(15,2) NULL DEFAULT '0.00' COMMENT 'Стоимость доставки',
//"9900000001"                              //"9900000001"	                                                 `client_id` VARCHAR(20) NULL DEFAULT NULL COMMENT 'ID клиента',
//""                                        //""	                                                         `customer_comment` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Примечание к заказу',
//"2019-07-19"                              //"2019-07-18"	                                                 `customer_desired_date` DATE NULL DEFAULT NULL,
//"11:00"                                   //""	                                                         `customer_desired_time_from` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//"14:00"                                   //""                                                             `customer_desired_time_to` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//\N                                        //\N	                                                         `product_pricelist_id` VARCHAR(5) NULL DEFAULT NULL COMMENT 'Идентификатор прайс-листа',
//"9900000009"                              //"9900000009"	                                                 `product_pricelist_param` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Дополнительный параметр к типу цены',
//"2019-07-17 03:13:15"                     //"2019-07-18 17:55:21"	                                         `processed_by_era_date` DATETIME NULL DEFAULT NULL,
//"2019-07-17 03:12:03"                     //"2019-07-18 17:54:04"	                                         `processed_by_eshop_date` DATETIME NULL DEFAULT NULL,
//""                                        //""	                                                         `processed_by_era_error_message` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Сообщение об ошибке обработки от Эры',
//""                                        //""	                                                         `processed_by_eshop_error_message` VARCHAR(255) NOT NULL,
//"2019-07-17 05:12:03"                     //"2019-07-18 19:54:04"	                                         `date_insert` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
//nom_card      "2775398190975"                           //"2775990172409"	                                 `user_dcard_id` VARCHAR(255) NOT NULL,


//                            "12568798"	                                         `id` INT(11) NOT NULL AUTO_INCREMENT,
//helper::getGuid()           "{A4662C94-13B1-E3B2-6E13-870F622AE0AC}"	             `packet_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ID пакета для контроля порций обмена',
//order::orderNumber          "ES-201107"	                                         `order_id` VARCHAR(255) NULL DEFAULT '' COMMENT 'ID заказа',
//item::article               "3053150"	                                             `product_id` INT(11) NULL DEFAULT '0',
//item::name                  "Крем - гель Eveline Slim Extreme 3D термоактивный для `product_name` VARCHAR(255) NULL DEFAULT NULL,
//item::quantity              "1"	                                                 `product_amount` BIGINT(20) NULL DEFAULT '0' COMMENT 'Количество товара',
//item::price                 "175,0000"	                                         `product_unit_price` DECIMAL(15,4) NULL DEFAULT '0.0000' COMMENT 'Цена товара',
//                            "2019-06-02 09:55:17"	                                 `processed_by_era_date` DATETIME NULL DEFAULT NULL,
//currentDate                 "2019-06-02 09:50:19"	                                 `processed_by_eshop_date` DATETIME NULL DEFAULT NULL,
//item::discount              "0"	                                                 `product_discount` INT(11) NOT NULL DEFAULT '0' COMMENT 'Размер скидки (5% для зарегавшегося, 10% для повторного заказа)',
//                            "2001000059225"                                        `product_ean` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ШК товара',
//                            "21901"                                                `product_pricelist_id` VARCHAR(5) NULL DEFAULT NULL,
//                            \N	                                                 `coupon` VARCHAR(100) NULL DEFAULT NULL,
//                            12                                                     `bonus_earn` SMALLINT(6) NULL DEFAULT NULL,


//`id` INT(11) NOT NULL AUTO_INCREMENT,
//`basket_id` INT(11) NOT NULL,
//`price` DECIMAL(10,2) NOT NULL,
//`quantity` INT(11) NOT NULL,
//`product_image_url` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
//`weight` DECIMAL(10,2) NOT NULL,
//`discount` DECIMAL(10,2) NULL DEFAULT NULL,
//`bonus` INT(11) NULL DEFAULT NULL,
//`old_cost` DECIMAL(10,2) NULL DEFAULT NULL,
//`article` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
//`discounts` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
//`measure` DECIMAL(10,3) NULL DEFAULT NULL,
//`measure_name` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
//`payment_method` INT(11) NULL DEFAULT NULL,
//`payment_object` INT(11) NULL DEFAULT NULL,
//`pos_num` INT(11) NULL DEFAULT NULL,
//`tara_mode` INT(11) NULL DEFAULT NULL,
//`vat_code` INT(11) NULL DEFAULT NULL,
//`vat_rate` INT(11) NULL DEFAULT NULL,
//`vat_sum` DECIMAL(18,2) NULL DEFAULT NULL,
//`cost` DECIMAL(10,2) NULL DEFAULT NULL,
//`earned_bonuses` LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
//`name` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
//`min_price` DECIMAL(10,2) NULL DEFAULT NULL,
//`barcode` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
//`dept` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
//`volume` DECIMAL(10,2) NULL DEFAULT NULL,


//  `id` INT(11) NOT NULL AUTO_INCREMENT,
//	`order_id` VARCHAR(255) NULL DEFAULT '' COMMENT 'ID заказа',
//	`product_id` INT(11) NULL DEFAULT '0',
//	`product_name` VARCHAR(255) NULL DEFAULT NULL,
//	`product_amount` BIGINT(20) NULL DEFAULT '0' COMMENT 'Количество товара',
//	`product_unit_price` DECIMAL(15,4) NULL DEFAULT '0.0000' COMMENT 'Цена товара',
//	`product_ean` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ШК товара',
//	`product_discount` INT(11) NOT NULL DEFAULT '0' COMMENT 'Размер скидки (5% для зарегавшегося, 10% для повторного заказа)',
//	`processed_by_era_date` DATETIME NULL DEFAULT NULL,
//	`processed_by_eshop_date` DATETIME NULL DEFAULT NULL,
//	`packet_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ID пакета для контроля порций обмена',
//	`product_pricelist_id` VARCHAR(5) NULL DEFAULT NULL,
//	`coupon` VARCHAR(100) NULL DEFAULT NULL,
//	`bonus_earn` SMALLINT(6) NULL DEFAULT NULL,


//order::delivery.type          `order_delivery_type` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Способ доставки: Доставка или Самовывоз',
//                              `order_logagent_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Наименование логагента',
//                              `order_logagent_phone` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Телефон логагента',
//                              `order_logagent_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'E-mail логагента',
//                              `order_delivery_logagent_gln` VARCHAR(13) NULL DEFAULT NULL COMMENT 'Код логагента в системе партнёра',
//                              `order_deliverypoint_gln` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Код деливерипоинта в системе партнёра',
//                              `order_delivery_address` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Адрес доставки',
//                              `order_delivery_logagent_date` DATETIME NULL DEFAULT NULL COMMENT 'Дата доставки логагенту',
//                              `order_delivery_logagent_time` VARCHAR(32) NULL DEFAULT NULL COMMENT 'Желаемое время доставки логагенту (строка)',
//                              `order_deliverypoint_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Наименование ПВЗ',
//                              `order_deliverypoint_phone` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Телефон ПВЗ',
//                              `order_deliverypoint_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'E-mail ПВЗ',
//                              `order_deliverypoint_address` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Адрес ПВЗ',
//order::customer               `order_delivery_customer_time` VARCHAR(11) NULL DEFAULT NULL COMMENT 'Желаемое время доставки конечному покупателю (строка)',
//order::customer.city          `order_delivery_customer_city` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Город конечного покупателя',
//order::customer.street        `order_delivery_customer_street` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//order::customer               `order_delivery_customer_building` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//order::customer.flat          `order_delivery_customer_flat` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//order::customer.post_index    `order_delivery_customer_post_index` INT(8) NULL DEFAULT NULL,
//order::customer.house         `order_delivery_customer_house` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//                              `order_delivery_customer_date` DATETIME NULL DEFAULT NULL COMMENT 'Дата доставки конечному покупателю',
//order::customer.name          `order_customer_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ФИО конечного покупателя',
//order::customer.phone         `order_customer_phone` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Телефон конечного покупателя',
//order::customer.email         `order_customer_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'E-mail конечного покупателя',
//order::delivery.sum           `delivery_cost_sum` DECIMAL(15,2) NULL DEFAULT '0.00' COMMENT 'Стоимость доставки',
//order::comment                `customer_comment` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Примечание к заказу',
//                              `customer_desired_date` DATE NULL DEFAULT NULL,
//order::delivery.start         `customer_desired_time_from` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
//order::delivery.end           `customer_desired_time_to` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',


//id INT(11) NOT NULL AUTO_INCREMENT,
//order_number VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
//user_id INT(11) NOT NULL,
//status VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
//created DATETIME NOT NULL,
//updated DATETIME NOT NULL,
//payed DATETIME NULL DEFAULT NULL,
//discount DECIMAL(18,2) NULL DEFAULT NULL,
//nom_card VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
//type_opl INT(11) NOT NULL,
//comment LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
//actions VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
//customer LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
//delivery LONGTEXT NULL COLLATE 'utf8mb4_unicode_ci',
//price DECIMAL(18,2) NULL DEFAULT NULL,
//cost DECIMAL(18,2) NULL DEFAULT NULL,
    }
    public function sendEshopOrderGWData(Order $order, Basket $basket, $items)
    {
        $eOrder = $this->setEshopFields($order,$basket,$items);
        $message = 'send data to Eshop';

        $out = [
            'result' => Response::HTTP_OK,
            'message' => $message,
            'eshopOrder' => $eOrder, //->iterateVisible(),
        ];
        $str = json_encode($out, JSON_UNESCAPED_UNICODE);

        return $out;
    }

    /**
     * @param Order $order
     * @param Item $item
     * @return string
     */
    public function makeEshopCoupon(Order $order, $item)
    {
        $out = '';
        $separator = ',';
        // order - coupons - code
        $orderActions = $order->getActions();
        $orderCoupons = $orderActions['coupons'] ?? null;
        if ($orderCoupons != null) {
            foreach ($orderCoupons as $key => $coupon) {
                $out .= $coupon['code'] . $separator;
            }
        }
        // item - discounts - discountcode
        $discountCode = $item['discountCode'] ?? null;
        if ($discountCode != null) {
            $out .= $discountCode . $separator;
        }
        return $out;
    }
}