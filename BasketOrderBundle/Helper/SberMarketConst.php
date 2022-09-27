<?php

namespace App\BasketOrderBundle\Helper;

class SberMarketConst
{
    const EVENTS = [
      'order.created' => 'created',
      'order.assembled' => 'assembled',
      'order.canceled' => 'canceled',
      'order.delivered' => 'delivered'
    ];
    const EVENTS_DESCRIPTION = [
      'order.created' => 'Создание заказа',
      'order.assembled' => 'Заказ собран',
      'order.canceled' => 'Отмена заказа',
      'order.delivered'  => 'Заказ доставлен'
    ];
    const ERRORS = [
      'EF' => ['code' => 500, 'message' => 'Event failed'],
      'ENF' => ['code' => 400, 'message' => 'Event not found'],
      'ONF' => ['code' => 500, 'message' => 'Order not found'],
      'RNF' => ['code' => 404, 'message' => 'Route not found']
    ];
    const STATUS = [
        'accepted' => 'CRE',
        'delivered' => 'ISS',
        'canceled' => 'RFC'
    ];
    const PAYMENT_TYPE = '0';
    const DELIVERY_TYPE = '13';
    const SBERMARKET_SAP_ID = '1000051494';
}
