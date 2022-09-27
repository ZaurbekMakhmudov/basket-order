<?php

namespace App\BasketOrderBundle\Helper;

class SMMConst
{
    const METHOD = [
        "ISS" => 'closeBySell',
        "REE" => 'closeByCustomer',
        "RCS" => 'allPacking',
        "PFD" => 'allReject',
        "OPC" => 'partialPacking',
        "RFC" => 'cancelResult'
    ];

    const Events = [
        'App\BasketOrderBundle\Service\SMMService::allPacking' => 'smmPacking',
        'App\BasketOrderBundle\Service\SMMService::allReject' => 'smmReject',
        'App\BasketOrderBundle\Service\SMMService::sendItemToPacking' => 'smmPacking',
        'App\BasketOrderBundle\Service\SMMService::sendItemToReject' => 'smmReject',
        'App\BasketOrderBundle\Service\SMMService::closeBySell' => 'smmClose',
        'App\BasketOrderBundle\Service\SMMService::closeByCustomer' => 'smmClose',
        'App\BasketOrderBundle\Service\SMMService::cancelResult' => 'smmCancelResult'
    ];


    const DELIVERY_TYPE = "10";
    const PAYMENT_TYPE = "1";
    const SMM_SAP_ID = '1000052942';

}