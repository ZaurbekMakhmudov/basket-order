<?php

namespace App\BasketOrderBundle\SwgModel;

use Swagger\Annotations as SWG;

/**
 * App\BasketOrderBundle\SwgModel\Order
 * @package App\BasketOrderBundle\SwgModel
 */
class Order
{
    /**
     * order_id
     * @SWG\Property(type="string", example="UR-19233-9570")
     */
    private $orderId;
    /**
     * client_id
     * @SWG\Property(type="string", example="df9ef49e-7116-47ca-abe8-d1741d49a527")
     */
    private $userId;
    /**
     * order_status
     * @SWG\Property(type="string", example="RCW")
     */
    private $status;
    /**
     * order_payment_type` 'Тип оплаты',
     * @SWG\Property(type="string", example="0")
     */
    private $paymentType;
    /**
     * <summa_first>1123.00</summa_first>    сумма заказа до скидок
     * @SWG\Property(type="string", example="488")
     */
    private $price;
    /**
     * order_sum` DECIMAL(15,2) NULL DEFAULT '0.00' COMMENT 'Сумма заказа',
     * @SWG\Property(type="string", example="488")
     */
    private $cost;
    /**
     * @SWG\Property(type="string", example="comment")
     */
    private $comment;
    /**
     * order_delivery_type
     * @SWG\Property(type="string", example="2")
     */
    private $deliveryType;
}
/**
 *
 *
 *
UR-19393-9197
UR-19895-9226
UR-19639-9232
UR-19828-9205
UR-19880-9303
UR-19626-9109
UR-19759-9319
UR-19369-3565
UR-19861-9362
UR-19483-9377
UR-19502-9395
UR-19814-9403
UR-19866-9404
UR-19875-9438
UR-19499-9449
UR-19917-9456
UR-19151-9458
UR-19483-9461
UR-19875-9247
UR-19771-9477
UR-19335-9478
UR-19860-9481
UR-19609-9499
UR-19357-9533
UR-19698-9542
UR-19527-9548
UR-19850-9551
UR-19514-9552
UR-19810-9568
UR-19328-9578
UR-19880-9592
UR-19431-9597

 *
 */
