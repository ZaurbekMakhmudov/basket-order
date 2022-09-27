<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 21.07.19
 * Time: 20:38
 */

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Era\EshopOrderPosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;


class EshopOrderPositionRepository extends ServiceEntityRepository
{
    /**
     * EshopOrderPositionRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EshopOrderPosition::class);
    }

    /**
     * @param $orderId
     * @param $packetId
     * @return mixed
     */
    public function findBySentEshopOrderPositions($orderId, $packetId)
    {
        $q = $this->createQueryBuilder('ep')
            ->andWhere('ep.order_id =:order_id')
            ->setParameter('order_id', $orderId)
            ->andWhere('ep.packet_id =:packetId')
            ->setParameter('packetId', $packetId)
            ->addOrderBy('ep.product_id')
            ->getQuery();
        $items = $q->getResult();

        return $items;
    }

    /**
     * @param $orderId
     * @param $packetId
     * @return int|mixed|string
     */
    public function findEshopOrderPositions($orderId, $packetId)
    {
        return $this->createQueryBuilder('ep')
            ->andWhere('ep.order_id =:order_id')
            ->setParameter('order_id', $orderId)
            ->andWhere('ep.packet_id =:packetId')
            ->setParameter('packetId', $packetId)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }
}