<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 04.03.17
 * Time: 22:43
 */

namespace App\BasketOrderBundle\Helper;

class ShopConst
{
    const ACTIVE_BASKET = 1;
    const NOT_ACTIVE_BASKET = 0;

    const COUPON_TYPE_DISABLED      = -1;
    const COUPON_TYPE_ONCE          = 1;
    const COUPON_TYPE_1_ORDER       = 2;
    const COUPON_TYPE_2_ORDER       = 3;
    const COUPON_TYPE_3_ORDER       = 4;
    const COUPON_TYPE_DELIVERY_FREE = 5;

    const COUPON_STATUS_ACTIVE = 'Проверено';

    const OVERTIME_TEXT_DEFAULT = 'Срок хранения 2 дня';

    const CODE_IS_NEW = 1;
    const IS_IMITATION_CURL_RM = 'success';
    const IS_IMITATION_CURL = false;

    const DC_SAP_ID = '1000027376';
    const UR_SAP_ID = '6000000009';

    const LOG_FILE_ORDER = 'info_order-';
    const LOG_FILE_BASKET = 'info_basket-';

    const GW_ES_ORDER_CREATE = 'ordercreate';
    const GW_ES_ORDER_CHANGE_SOST = 'orderstatusset';
    const GW_ES_ORDER_PAYMENT_SET = 'orderpaymentset';
    const GW_ES_ORDER_LIST = 'orderlistget';
    const GW_ES_ORDER_INFO = 'orderinfoget';
    const GW_ES_ORDER_UPDATE = 'orderupdate';

    const ORDER_PATTERN_DATE = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
    const ORDER_PATTERN_TIME = "/^([0-1]\d|2[0-3]):[0-5]\d$/";
    const ORDER_PATTERN_TIME_FULL = "/^([0-1]\d|2[0-3])(:[0-5]\d){2}$/";
    const ORDER_PATTERN_DATE_TIME = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) ([0-1]\d|2[0-3])(:[0-5]\d){2}$/";

    const ORDER_PATTERN_DELIVERY_POINT_GLN = "/^[0-9]{10}\/[0-9]{6}\/[0-9]{3}-[0-9]{1}\/\{[0-9A-Z]{8}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{12}\}$/";
    const ORDER_PATTERN_DELIVERY_POINT_GLN_EASY = "/";

    const ORDER_FORMAT_DATE_TIME = 'Y-m-d H:i:s';
    const ORDER_FORMAT_DATE = 'Y-m-d';
    const ORDER_FORMAT_TIME = 'H:i';
    const ORDER_FORMAT_TIME_FULL = 'H:i:s';

    const SKIP_OPC_STATUS = '0';

    const COMMUNICATOR_SCRIPT = '/eshop/order_status/update';

    const MAPPING_STATUS_DAY           = 'day';
    const MAPPING_STATUS_DAY_ONL       = 'dayONL';
    const MAPPING_STATUS_DAY_ISS       = 'dayISS';
    const MAPPING_STATUS_DAY_RFC       = 'dayRFC';
    const MAPPING_STATUS_AVIABLE       = 'aviable';
    const MAPPING_STATUS_FINAL         = 'final';
    const MAPPING_STATUS_CANCELED      = 'canceled';
    const MAPPING_STATUS_NOT_PROCESSED = 'notProcessed';
    const MAPPING_STATUS_RECALC        = 'recalc';
    const MAPPING_STATUS_ALL           = 'all';


    const STATUS_DRAFT = 'DRAFT';
    const STATUS_NEW = 'NEW';
    const STATUS_CAL = 'CAL';
    const STATUS_CAL2 = 'CAL2-';
    const STATUS_CNC = 'CNC';
    const STATUS_PCRE = 'PCRE';
    const STATUS_CRE = 'CRE';
    const STATUS_DCH = 'DCH';
    const STATUS_EML = 'EML';
    const STATUS_INV = 'INV';
    const STATUS_ISS = 'ISS';
    const STATUS_OPC = 'OPC';
    const STATUS_PRW = 'PRW';
    const STATUS_EXP = 'EXP';
    const STATUS_PYD = 'PYD';
    const STATUS_PYS = 'PYS';
    const STATUS_RCS = 'RCS';
    const STATUS_RCW = 'RCW';
    const STATUS_REA = 'REA';
    const STATUS_REO = 'REO';
    const STATUS_RER = 'RER';
    const STATUS_RES = 'RES';
    const STATUS_REV = 'REV';
    const STATUS_RFA = 'RFA';
    const STATUS_RFC = 'RFC';
    const STATUS_RFD = 'RFD';
    const STATUS_RFS = 'RFS';
    const STATUS_RFW = 'RFW';
    const STATUS_SHS = 'SHS';
    const STATUS_SHC = 'SHC';
    const STATUS_WTW = 'WTW';
    const STATUS_REE = 'REE';
    const STATUS_RPY = 'RPY';
    const STATUS_CAL_ = 'CAL-';
    const STATUS_RBOD = 'RBOD';
    const STATUS_RFP = 'RFP';
    const STATUS_RFB = 'RFB';
    const STATUS_ONL = 'ONL';
    const STATUS_INC = 'INC';
    const STATUS_FFM = 'FFM';
    const STATUS_PFD = 'PFD';
    const STATUS_ICC = 'ICC';
    const STATUS_WTS = 'WTS';
    const STATUS_BLC = 'BLC';

    const STATUS_DRAFT_TXT = 'Черновик';
    const STATUS_BLC_TXT = 'Принудительная блокировка заказа';
    const STATUS_NEW_TXT = 'посылка создана';
    const STATUS_CAL_TXT = 'Звонок клиенту. Договоренность о заборе заказа'; // CAL
    const STATUS_CAL2_TXT = 'Мы звонили 2 раза, но недозвонились CAL2-'; // CAL2-
    const STATUS_CNC_TXT = 'Заказ подтвержден'; // CNC
    const STATUS_PCRE_TXT = 'Приняли заказ в предобработку'; // PCRE
    const STATUS_CRE_TXT = 'Приняли заказ в обработку'; // CRE
    const STATUS_DCH_TXT = 'Изменение данных для курьерской доставки'; //  DCH
    const STATUS_EML_TXT = 'Заказ в ПВЗ и требует выдачи'; //  EML
    const STATUS_INV_TXT = 'Собран полностью'; //'Заказ в обработке'; //  INV
    const STATUS_ISS_TXT = 'Заказ выдан и оплачен'; // ISS
    const STATUS_OPC_TXT = 'Ожидаем подтверждение изменений в заказе'; //  OPC
    const STATUS_PRW_TXT = 'Заказ собран на складе'; //  PRW
    const STATUS_EXP_TXT = 'Заказ выдан курьеру'; //  EXP
    const STATUS_PYD_TXT = 'Заказ подтвержден/оплачен'; //  PYD
    const STATUS_PYS_TXT = 'Возврат платежа'; //  PYS
    const STATUS_RCS_TXT = 'Заказ доставлен по месту выдачи'; //  RCS
    const STATUS_RCW_TXT = 'Заказ передан на доставку'; //  RCW
    const STATUS_REA_TXT = 'Возврат заказа на склад'; // REA
    const STATUS_REO_TXT = 'Возврат заказа на склад'; //  REO
    const STATUS_RER_TXT = 'Возврат заказа на склад'; //  RER
    const STATUS_RES_TXT = 'Заказ возвращен на склад'; //  RES
    const STATUS_REV_TXT = 'Возврат получен на складе'; // REV
    const STATUS_RFA_TXT = 'Отклонен через КЦ'; //  RFA
    const STATUS_RFC_TXT = 'Отклонен клиентом'; //  RFC Сброс изменений по заказу и переотправка в RM  RFC
    const STATUS_RFD_TXT = 'Заказ полностью отменен складом'; //  RFD
    const STATUS_RFS_TXT = 'Отказ клиента'; // RFS
    const STATUS_RFW_TXT = 'Заказ отменен складом'; //  RFW
    const STATUS_SHS_TXT = 'Заказ в пути'; //  SHS
    const STATUS_SHC_TXT = 'Информирование об изменениях в заказе'; //  SHC
    const STATUS_WTW_TXT = 'Ожидает поставку на склад'; // WTW
    const STATUS_REE_TXT = 'Возврат заказа на склад'; // REE
    const STATUS_RPY_TXT = 'Возврат баллов по заказу'; // RPY
    const STATUS_CAL__TXT = 'Мы звонили, но недозвонились'; // CAL-
    const STATUS_RBOD_TXT = 'Отклонен оператором. Заказ уже подключенного тарифа'; // RBOD
    const STATUS_RFP_TXT = 'Отклонен оператором. Расхождения в данных по карте'; //. RFP
    const STATUS_RFB_TXT = 'Отклонен оператором. Недостаточно средств на карте'; //. RFB
    const STATUS_ONL_TXT = 'Ожидает оплаты';

    const STATUS_INC_TXT = 'Подобран частично';
    const STATUS_FFM_TXT = 'Добавлены коды маркировок с полным подбором';
    const STATUS_PFD_TXT = 'Ни один из товаров из заказа не найден';
    const STATUS_ICC_TXT = 'Ожидаем подтверждение изменений'; // ICC
    const STATUS_WTS_TXT = 'Приняли заказ в обработку';

    const STATUS_PAYING_TXT = self::STATUS_ONL_TXT; //'Ожидает оплаты';
    const STATUS_HAND_TXT = 'Обработка';
    const STATUS_WAY_TXT = 'В пути';
    const STATUS_READY_TXT = 'Готов к выдаче';
    const STATUS_OK_TXT = 'Выполнен';
    const STATUS_CANCELED_TXT = 'Отменен';
    const STATUS_RETURN_TXT = 'Возврат';

    const STATUS_PAYING = self::STATUS_ONL; //'Ожидает оплаты';
    const STATUS_HAND = 'Обработка';
    const STATUS_WAY = 'В пути';
    const STATUS_READY = 'Готов к выдаче';
    const STATUS_OK = 'Выполнен';
    const STATUS_CANCELED = 'Отменен';
    const STATUS_RETURN = 'Возврат';

    const DELIVERY_TYPE_E = 'E'; // курьер
    const DELIVERY_TYPE_W = 'W'; // самовывоз
    const DELIVERY_TYPE_W10 = '10'; // PickUpInStore
    const DELIVERY_TYPE_W3 = '3'; // самовывоз
    const DELIVERY_KEY_TYPE_E = '1'; // курьер
    const DELIVERY_KEY_TYPE_W = '2'; // самовывоз
    const DELIVERY_KEY_TYPE_W10 = '10'; // PickUpInStore
    const DELIVERY_KEY_TYPE_W12 = '12'; // PickUpInStore Express
    const DELIVERY_KEY_TYPE_W13 = '13'; // Partner assembled
    const DELIVERY_KEY_TYPE_W3 = '3'; // самовывоз

    const DELIVERY_SCHEME_1 = 1; // 1 контурная - курьер
    const DELIVERY_SCHEME_2 = 2; // 2 контурная - доставка в составе УР

    const PAYMENT_KEY_TYPE_O = '1'; // ONLINE
    const PAYMENT_KEY_TYPE_C = '0'; // cash

    const DST_RM = 'RM';
    const DST_MP = 'MP';

    const ORDER_METHODS = [
      'order' => 'App\BasketOrderBundle\Controller\OrderController::order',
      'setStatus' => 'App\BasketOrderBundle\Controller\OrderController::setStatus',
      'info' => 'App\BasketOrderBundle\Controller\OrderController::info',
      'confirmPaymentInformation' => 'App\BasketOrderBundle\Controller\OrderController::confirmPaymentInformation',
      'updateOrder' => 'App\BasketOrderBundle\Controller\OrderController::updateOrder',
      'infoByPartnerOrderId' => 'App\BasketOrderBundle\Controller\OrderController::infoByPartnerOrderId'
    ];

    const DC_METHODS = [
        'getDCToken' => 'App\BasketOrderBundle\Controller\DeliveryClubController::getDCToken',
        'createDCOrder' => 'App\BasketOrderBundle\Controller\DeliveryClubController::createDCOrder',
        'getDCOrder' => 'App\BasketOrderBundle\Controller\DeliveryClubController::getDCOrder',
        'setDCStatus' => 'App\BasketOrderBundle\Controller\DeliveryClubController::setDCStatus'
    ];

    protected static array $overtimeText = [
        self::DELIVERY_KEY_TYPE_W => self::OVERTIME_TEXT_DEFAULT,
    ];

    protected static array $deliveryType = [
        self::DELIVERY_KEY_TYPE_E => self::DELIVERY_TYPE_E,
        self::DELIVERY_KEY_TYPE_W => self::DELIVERY_TYPE_W,
    ];

    protected static array $communicatorScriptRM = [
        self::GW_ES_ORDER_CREATE => '/rm/order/create',
        self::GW_ES_ORDER_CHANGE_SOST => '/rm/order/status',
        self::GW_ES_ORDER_PAYMENT_SET => '/rm/order/payment',
    ];

    protected static array $paymentStatusRM = [
        self::STATUS_PYD => 1,
        self::STATUS_PYS => -1,
    ];

    protected static array $deliveryTypeRM = [
        self::DELIVERY_KEY_TYPE_W10,
        self::DELIVERY_KEY_TYPE_W12,
        self::DELIVERY_KEY_TYPE_W13,
    ];

    protected static array $deliverySchemes = [
        self::DELIVERY_SCHEME_1,
        self::DELIVERY_SCHEME_2,
    ];

    protected static array $dcPaymentTypeMapping = [
        'cash' => [ // - оплата наличными
            self::PAYMENT_KEY_TYPE_C,
        ],
        'card' => [ // - оплата картой
            self::PAYMENT_KEY_TYPE_C,
        ],
        'online' => [ // - онлайн оплата
            self::PAYMENT_KEY_TYPE_O,
        ]
    ];

    protected static array $SMMPaymentTypeMapping = [
        'online' => [ // - онлайн оплата
            self::PAYMENT_KEY_TYPE_O,
        ]
    ];

    // сверх-финальные статусы заказа, которые можно пере-выставить штатно после финальных статусов
    protected static array $overFinalOrderStatusServices = [
        self::DC_SAP_ID => [
            self::STATUS_ISS => self::STATUS_INC, // ISS (Заказ выдан и оплачен) разрешенно переопределить в INC (Произведен вычерк) для особых сервисов (Delivery Club)
        ],
        self::UR_SAP_ID => [
            self::STATUS_RFC => self::STATUS_ISS, // RFC (Отклонен клиентом) разрешено переопределить в ISS (Заказ выдан и оплачен
        ],
    ];

    protected static array $mappingStatus = [
        self::MAPPING_STATUS_FINAL => [ // финальные статусы
            self::STATUS_RFA,     // Отклонен через КЦ
            self::STATUS_RFC,     // Отклонен клиентом
            self::STATUS_RFW,     // Заказ отменен складом
            self::STATUS_RFD,     // Заказ полностью отменен складом
            self::STATUS_RER,     // Возврат заказа на склад
            self::STATUS_REO,     // Возврат заказа на склад
            self::STATUS_REA,     // Возврат заказа на склад
            self::STATUS_REE,     // Возврат заказа на склад
            self::STATUS_ISS,     // Заказ выдан и оплачен
            self::STATUS_BLC,     // Принудительная блокировка заказа
        ],
        self::MAPPING_STATUS_CANCELED => [ // отмененные сатусы
            self::STATUS_RFA,     // Отклонен через КЦ
            self::STATUS_RFC,     // Отклонен клиентом
            self::STATUS_RFW,     // Заказ отменен складом
            self::STATUS_RFD,     // Заказ полностью отменен складом
            self::STATUS_RER,     // Возврат заказа на склад
            self::STATUS_REO,     // Возврат заказа на склад
            self::STATUS_REA,     // Возврат заказа на склад
            self::STATUS_REE,     // Возврат заказа на склад
            self::STATUS_RES,     // Заказ возвращен на склад
            self::STATUS_PFD,     // Ни один из товаров из заказа не найден
        ],
        self::MAPPING_STATUS_NOT_PROCESSED => [ // статусы "не в работе"
            self::STATUS_DRAFT,   // Черновик
            self::STATUS_RFA,     // Отклонен через КЦ
            self::STATUS_RFC,     // Отклонен клиентом
            self::STATUS_RFW,     // Заказ отменен складом
            self::STATUS_RFD,     // Заказ полностью отменен складом
            self::STATUS_RER,     // Возврат заказа на склад
            self::STATUS_REO,     // Возврат заказа на склад
            self::STATUS_REA,     // Возврат заказа на склад
            self::STATUS_REE,     // Возврат заказа на склад
            self::STATUS_RES,     // Заказ возвращен на склад
            self::STATUS_PFD,     // Ни один из товаров из заказа не найден
        ],
        self::MAPPING_STATUS_DAY => [ // статусы дня при обработке шлюзовой таблицы
            self::STATUS_ONL,
            self::STATUS_ISS,
            self::STATUS_RFC,
        ],
        self::MAPPING_STATUS_DAY_ONL => [ // статус дня ONL при обработке шлюзовой таблицы
            self::STATUS_ONL,
        ],
        self::MAPPING_STATUS_DAY_ISS => [ // статус дня ISS при обработке шлюзовой таблицы
            self::STATUS_ISS,
        ],
        self::MAPPING_STATUS_DAY_RFC => [ // статус дня RFC при обработке шлюзовой таблицы
            self::STATUS_RFC,
        ],
        self::MAPPING_STATUS_RECALC => [ // статусы с вычерками при обработке шлюзовой таблицы
            self::STATUS_INV,
            self::STATUS_SHS,
        ],
        self::MAPPING_STATUS_AVIABLE => [ // статусы для консольной обработки шлюзовой таблицы
            self::STATUS_NEW,
            self::STATUS_CAL,
            self::STATUS_CAL2,
            self::STATUS_CNC,
            self::STATUS_CRE,
            self::STATUS_DCH,
            self::STATUS_EML,
            self::STATUS_INV,
            self::STATUS_INC,
            self::STATUS_FFM,
            self::STATUS_PFD,
            self::STATUS_OPC,
            self::STATUS_PRW,
            self::STATUS_PYD,
            self::STATUS_RCS,
            self::STATUS_RCW,
            self::STATUS_REA,
            self::STATUS_REO,
            self::STATUS_RER,
            self::STATUS_RES,
            self::STATUS_RFA,
            self::STATUS_RFD,
            self::STATUS_RFS,
            self::STATUS_RFW,
            self::STATUS_SHS,
            self::STATUS_REE,
            self::STATUS_RPY,
            self::STATUS_CAL_,
            self::STATUS_RBOD,
            self::STATUS_RFP,
            self::STATUS_RFB,
            self::STATUS_WTW,
            self::STATUS_REV,
            self::STATUS_ICC,
            self::STATUS_WTS,
        ],
        self::MAPPING_STATUS_ALL => [ // все статусы
            self::STATUS_DRAFT,
            self::STATUS_NEW,
            self::STATUS_CAL,
            self::STATUS_CAL2,
            self::STATUS_CNC,
            self::STATUS_PCRE,
            self::STATUS_CRE,
            self::STATUS_DCH,
            self::STATUS_EML,
            self::STATUS_INV,
            self::STATUS_ISS,
            self::STATUS_OPC,
            self::STATUS_PRW,
            self::STATUS_EXP,
            self::STATUS_PYD,
            self::STATUS_PYS,
            self::STATUS_RCS,
            self::STATUS_RCW,
            self::STATUS_REA,
            self::STATUS_REO,
            self::STATUS_RER,
            self::STATUS_RES,
            self::STATUS_REV,
            self::STATUS_RFA,
            self::STATUS_RFC,
            self::STATUS_RFD,
            self::STATUS_RFS,
            self::STATUS_RFW,
            self::STATUS_SHS,
            self::STATUS_SHC,
            self::STATUS_WTW,
            self::STATUS_REE,
            self::STATUS_RPY,
            self::STATUS_CAL_,
            self::STATUS_RBOD,
            self::STATUS_RFP,
            self::STATUS_RFB,
            self::STATUS_ONL,
            self::STATUS_INC,
            self::STATUS_FFM,
            self::STATUS_PFD,
            self::STATUS_ICC,
            self::STATUS_WTS,
            self::STATUS_BLC,
        ],
    ];

    protected static array $dcStatusMapping = [
        'created' => [ // - заказ создан
            self::STATUS_DRAFT,
            self::STATUS_ONL,
            ],
        'accepted' => [ //  - заказ подтвержден
            self::STATUS_CRE,
            ],
        'handed_over_for_picking' => [ //  - передан на комплектовку
            self::STATUS_WTS,
            self::STATUS_INC,
            self::STATUS_OPC,
            self::STATUS_PRW,
            ],
        'handed_over_for_delivery' => [ //  - передан в доставку
            self::STATUS_EXP,
            ],
        'on_the_way' => [ //  - в доставке
            self::STATUS_EXP,
            ],
        'delivered' => [ //  - доставлен
            self::STATUS_ISS,
            ],
        'canceled' => [ //  - отменен.
            self::STATUS_RFC,     // Отклонен клиентом
            self::STATUS_REE,     // Возврат заказа на склад
            self::STATUS_RFA,     // Отклонен через КЦ
            self::STATUS_RFW,     // Заказ отменен складом
            self::STATUS_RFD,     // Заказ полностью отменен складом
            self::STATUS_RER,     // Возврат заказа на склад
            self::STATUS_REO,     // Возврат заказа на склад
            self::STATUS_REA,     // Возврат заказа на склад
            self::STATUS_RES,     // Заказ возвращен на склад
            self::STATUS_PFD,     // Ни один из товаров из заказа не найден
            self::STATUS_BLC,     // Принудительная блокировка заказа
        ],
    ];

    protected static array $SMMStatusMapping = [
        'created' => [ // - заказ создан
            self::STATUS_DRAFT,
            self::STATUS_ONL,
        ],
        'accepted' => [ //  - заказ подтвержден
            self::STATUS_CRE,
        ],
        'handed_over_for_picking' => [ //  - передан на комплектовку
            self::STATUS_WTS,
            self::STATUS_INC,
            self::STATUS_OPC,
            self::STATUS_PRW,
        ],
        'handed_over_for_delivery' => [ //  - передан в доставку
            self::STATUS_EXP,
        ],
        'on_the_way' => [ //  - в доставке
            self::STATUS_EXP,
        ],
        'delivered' => [ //  - доставлен
            self::STATUS_ISS,
        ],
        'canceled' => [ //  - отменен.
            self::STATUS_RFC,     // Отклонен клиентом
            self::STATUS_REE,     // Возврат заказа на склад
            self::STATUS_RFA,     // Отклонен через КЦ
            self::STATUS_RFW,     // Заказ отменен складом
            self::STATUS_RFD,     // Заказ полностью отменен складом
            self::STATUS_RER,     // Возврат заказа на склад
            self::STATUS_REO,     // Возврат заказа на склад
            self::STATUS_REA,     // Возврат заказа на склад
            self::STATUS_RES,     // Заказ возвращен на склад
            self::STATUS_PFD,     // Ни один из товаров из заказа не найден
            self::STATUS_BLC,     // Принудительная блокировка заказа
        ]
    ];

    // не считать расхождениями следующие пары статусов (СЗ => РМ)
    protected static array $orderStatusDifferentException = [
        [self::STATUS_RFC => self::STATUS_REE],
        [self::STATUS_RFC => self::STATUS_PFD],
    ];

    public static function getOrderStatusDifferentException(): array
    {
        return self::$orderStatusDifferentException;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public static function findDeliveryType($key)
    {
        if ($key !== null and isset(static::$deliveryType[$key])) {

            return static::$deliveryType[$key];
        }

        return null;
    }

    /**
     * @param null $key
     * @return mixed|null
     */
    public static function findCouponOnlineTitle($key = null)
    {
        return 'Купон';
    }

    /**
     * @return string
     */
    static public function getGuid()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                . chr(125);// "}"
            return $uuid;
        }
    }

    static public function getIdentifier()
    {
        $uid = static::getGuid();
        $uid = str_replace(['{', '}'], ['', ''], $uid);

        return $uid;
    }

    /**
     * mt_rand
     * Генерируем хеш
     */
    public static function genHash($items = null, $softCheque = null)
    {
        $hash = '';
        if($items){
            $hashStr = isset($items['basketId']) ? (string)$items['basketId'] : null;
            $cardNumber = isset($items['cardNumber']) ? (string)$items['cardNumber'] : null;
            $hashStr .= '-' . $cardNumber;
            $coupons = isset($items['coupons']) ? $items['coupons'] : null;
            if($coupons and is_array($coupons)){
                foreach ($coupons as $coupon){
                    $number = isset($coupon['number']) ? $coupon['number'] : null;
                    $hashStr .= $number;
                }
            }
            if ($items and !empty($items['items'])) {
                foreach ($items['items'] as $item) {
                    $quantity = $item['quantity'];
                    $article = isset($item['article']) ? $item['article'] : (isset($item['barcode']) ? $item['barcode'] : null);

                    $hashStr .= '-' . $quantity . '-' . $article;
                }
            }
            $hashStr .= $softCheque;
            $hash = md5($hashStr);
        }

        return $hash;
    }

    /**
     * @return array
     */
    public static function listOverFinalOrderStatusServices()
    {
        return static::$overFinalOrderStatusServices;
    }

    /**
     * @param $key
     * @return string|null
     */
    public static function getCommunicatorScriptRM($key)
    {
        if ($key !== null and isset(static::$communicatorScriptRM[$key])) {

            return static::$communicatorScriptRM[$key];
        }

        return null;
    }

    /**
     * @param $key
     * @return int|null
     */
    public static function getPaymentStatusRM($key)
    {
        if( array_key_exists($key, static::$paymentStatusRM) ) {

            return static::$paymentStatusRM[$key];
        }

        return null;
    }

    /**
     * @param $paymentStatus
     * @return bool
     */
    public static function isPaymentStatusRM($paymentStatus) {

        return array_key_exists($paymentStatus, static::$paymentStatusRM);
    }

    /**
     * @param $paymentInformationStatus
     * @return false|int|string
     */
    public static function makeOrderStatusFromPaymentInformationStatus($paymentInformationStatus) {

        return array_search($paymentInformationStatus, static::$paymentStatusRM);
    }

    /**
     * @param $deliveryType
     * @return bool
     */
    public static function isDeliveryTypeRM($deliveryType) {

        return in_array($deliveryType, static::$deliveryTypeRM);
    }

    /**
     * @return array|string[]
     */
    public static function listDeliveryTypeRM()
    {

        return static::$deliveryTypeRM;
    }

    /**
     * @param $status
     * @return false|int|string
     */
    public static function getDCStatusFromStatus($status)
    {
        foreach(static::$dcStatusMapping as $dcStatus => $statuses) {
            if(in_array($status, $statuses)) {

                return $dcStatus;
            }
        }

        return 'created';
    }

    /**
     * @param $dcStatus
     * @return false|mixed
     */
    public static function getStatusFromDCStatus($dcStatus)
    {
        if( array_key_exists($dcStatus, static::$dcStatusMapping) ) {

            return static::$dcStatusMapping[$dcStatus][0];
        }

        return null;
    }

    public static function getStatusFromSMMStatus($status)
    {
        if( array_key_exists($status, static::$SMMStatusMapping) ) {

            return static::$SMMStatusMapping[$status][0];
        }

        return null;
    }

    /**
     * @param $dcPaymentType
     * @return mixed|null
     */
    public static function getPaymentTypeFromDC($dcPaymentType)
    {
        if( array_key_exists($dcPaymentType, static::$dcPaymentTypeMapping) ) {

            return static::$dcPaymentTypeMapping[$dcPaymentType][0];
        }

        return null;
    }

    public static function getPaymentTypeFromSMM($paymentType)
    {
        if( array_key_exists($paymentType, static::$SMMPaymentTypeMapping) ) {

            return static::$SMMPaymentTypeMapping[$paymentType][0];
        }

        return null;
    }

    /**
     * @param string $mappingKey
     * @return mixed|string[]|null
     */
    public static function getMappedStatuses(string $mappingKey)
    {
        if( array_key_exists($mappingKey, static::$mappingStatus) ) {

            return static::$mappingStatus[$mappingKey];
        }

        return null;
    }

    /**
     * @param string $mappingKey
     * @param string $status
     * @return bool
     */
    public static function isMappedStatus(string $mappingKey, string $status): bool
    {
        if( array_key_exists($mappingKey, static::$mappingStatus) ) {

            return in_array($status, static::$mappingStatus[$mappingKey]);
        }

        return false;
    }

    /**
     * @param string $status
     * @return string
     */
    public static function getStatusInfo(string $status): string
    {
        $const = '::STATUS_' . $status . '_TXT';
        if( defined(ShopConst::class . $const) ) {
            $info = constant(ShopConst::class . $const);
        } else {
            $info = 'undefined status info';
        }

        return $info;
    }

    /**
     * @param $deliveryScheme
     * @return bool
     */
    public static function isValidDeliveryScheme($deliveryScheme): bool
    {
        return in_array($deliveryScheme, static::$deliverySchemes);
    }

    /**
     * @param int $deliveryType
     * @return string|null
     */
    public static function getOvertimeText(int $deliveryType): ?string
    {
        if( array_key_exists($deliveryType, static::$overtimeText) ) {

            return static::$overtimeText[$deliveryType];
        }

        return null;
    }
}