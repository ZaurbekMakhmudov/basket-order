<?php

namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\Coupon;
use App\BasketOrderBundle\Entity\CouponRestriction;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Enqueue\AmqpExt\AmqpConnectionFactory;
use Exception;
use Interop\Amqp\AmqpDestination;
use Psr\Log\LoggerInterface;
use Avtonom\GlobalEventBundle\Manager\GlobalEventManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class QueueService
 * @package App\BasketOrderBundle\Service
 */
class QueueService
{
    /**
     * @var ObjectManager
     */
    public $entityManager;
    /**
     * @var GlobalEventManager
     */
    private $gem;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * QueueService constructor.
     * @param EntityManagerInterface $entityManager
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    )
    {
        $connection = new AmqpConnectionFactory($_ENV['ENQUEUE_DSN']);
        $context = $connection->createContext();
        $topic = $context->createTopic('gateway.main');
        $topic->setArgument('durable', true);
        $topic->setFlags(AmqpDestination::FLAG_DURABLE);
        $topic->setType('topic');
        $context->declareTopic($topic);


        $this->entityManager =  $entityManager;
        $this->logger = $logger;
        $this->gem = new GlobalEventManager(
            $dispatcher,
            $logger,
            'Avtonom\GlobalEventBundle\Builder\GlobalEventBuilder',
            'Avtonom\GlobalEventBundle\Event\GlobalEvent',
            'Avtonom\GlobalEventBundle\Error\GlobalEventError');
    }

    /**
     * @param $msg
     * @return bool
     * @throws Exception
     */
    public function processing($msg): bool
    {
        $response = true;
        $couponRestrictionDatas = $couponData = null;
        $event = $this->gem->createFromJson($msg);
        foreach ($event->getParameters() as $keyp => $parameter) {
            if( $parameter['entity_type'] != 'coupons' ) {
                continue;
            }

            foreach($parameter['properties'] as $propertyField => $val) {
                if($propertyField != 'COUPON_RESTRICTIONS_SAP_22') {
                    foreach ($val['values'] as $keyv => $value) {
                        $propertyValue = $value['name'];
                    }
                    $couponData[$propertyField] = $propertyValue;
                } else {
                    foreach ($val['values'] as $keyr => $restrictions) {
                        foreach ($restrictions['data']['properties'] as $restrictionField => $restrictionData) {
                            $propertyValue = $value['name'];
                            foreach ($restrictionData['values'][0] as $keyrv => $restrictionValues) {
                                $propertyValue = $value['name'];
                                $restrictionValue = $restrictionValues['name'];
                            }
                            $couponRestrictionDatas[$keyr][$restrictionField] = $restrictionValue;
                        }
                    }
                }
            }

            if($couponData) {
                $couponData = $_ENV['APP_ENV'] == 'dev' ? $this->couponFieldEnvMapping($couponData) : $couponData;
                $coupon = new Coupon();
                !empty($couponData['STATUS_AUTONAME'])            ? $coupon->setStatusAutoname($couponData['STATUS_AUTONAME']) : null;
                !empty($couponData['CREATOR'])                    ? $coupon->setCreator($couponData['CREATOR']) : null;
                !empty($couponData['PRICE_SAP_22'])               ? $coupon->setPriceSap($couponData['PRICE_SAP_22']) : null ;
                !empty($couponData['DISCOUNT_VALUE_SAP_22'])      ? $coupon->setDiscountValueSap($couponData['DISCOUNT_VALUE_SAP_22']) : null;
                !empty($couponData['CODE_RECORD_SAP_22'])         ? $coupon->setCodeRecordSap($couponData['CODE_RECORD_SAP_22']) : null;
                !empty($couponData['NAME_TYPE_RECORD_SAP_22'])    ? $coupon->setNameTypeRecordSap($couponData['NAME_TYPE_RECORD_SAP_22']) : null;
                !empty($couponData['TYPE_RECORD_COMMENT_SAP_22']) ? $coupon->setTypeRecordCommentSap($couponData['TYPE_RECORD_COMMENT_SAP_22']) : null;
                !empty($couponData['zaniato_do'])                 ? $coupon->setZaniatoDo(new DateTime($couponData['zaniato_do'])) : null;
                !empty($couponData['COUPON_ID'])                  ? $coupon->setCouponId($couponData['COUPON_ID']) : null;
                !empty($couponData['NAME_RECORD_SAP_22'])         ? $coupon->setNameRecordSap($couponData['NAME_RECORD_SAP_22']) : null;
                !empty($couponData['DAT_BEGIN_SAP_22'])           ? $coupon->setDatBeginSap(new DateTime($couponData['DAT_BEGIN_SAP_22'])) : null;
                !empty($couponData['DAT_END_SAP_22'])             ? $coupon->setDatEndSap(new DateTime($couponData['DAT_END_SAP_22'])) : null;
                !empty($couponData['CODE_DETAIL_SAP_22'])         ? $coupon->setCodeDetailSap($couponData['CODE_DETAIL_SAP_22']) : null;
                !empty($couponData['AN_VERIFIED_DATETIME'])       ? $coupon->setAnVerifiedDateTime(new DateTime($couponData['AN_VERIFIED_DATETIME'])) : null;
                !empty($couponData['AN_VERIFIED_DATE'])           ? $coupon->setAnVerifiedDate(new DateTime($couponData['AN_VERIFIED_DATE'])) : null;
                $coupon->setInsertedAt(new DateTime());
                $this->entityManager->persist($coupon);

                if($coupon && $couponRestrictionDatas) {
                    foreach ($couponRestrictionDatas as $key => $couponRestrictionData) {
                        $couponRestriction = new CouponRestriction();
                        $couponRestriction->setCreator($couponRestrictionData['CREATOR']);
                        $couponRestriction->setZaniatoDo(new DateTime($couponRestrictionData['zaniato_do']));
                        $couponRestriction->setIdientifikatorRestrictionsCoupons($couponRestrictionData['idientifikator_restrictions_coupons']);
                        $couponRestriction->setNameAdvPromoConditionSap($couponRestrictionData['NAME_ADV_PROMO_CONDITION_SAP_29']);
                        $couponRestriction->setNameAdvPromoConditionFullSap($couponRestrictionData['NAME_ADV_PROMO_CONDITION_FULL_SAP_29']);
                        $couponRestriction->addCoupon($coupon);
                        $couponRestriction->setInsertedAt(new DateTime());
                        $this->entityManager->persist($couponRestriction);
                    }
                }
                $this->entityManager->flush();
            }
        }

        return $response;
    }

    /**
     * @param array $couponData
     * @return array
     */
    private function couponFieldEnvMapping(array $couponData): array
    {
        $couponFieldEnvMapping = [
            'UNDEFINED_PROPERTY_5b519f23914c1' => 'CODE_DETAIL_SAP_22',
            'UNDEFINED_PROPERTY_5b519effa3379' => 'NAME_TYPE_RECORD_SAP_22',
            'UNDEFINED_PROPERTY_5b519f1001967' => 'TYPE_RECORD_COMMENT_SAP_22',
            'UNDEFINED_PROPERTY_5b519f434582c' => 'DAT_BEGIN_SAP_22',
            'UNDEFINED_PROPERTY_5b519f53213cc' => 'DAT_END_SAP_22',
        ];
        foreach ($couponFieldEnvMapping as $oldKey => $newKey) {
            if(!empty($couponData[$oldKey])) {
                $couponData[$newKey] = $couponData[$oldKey];
            }
        }

        return $couponData;
    }

}