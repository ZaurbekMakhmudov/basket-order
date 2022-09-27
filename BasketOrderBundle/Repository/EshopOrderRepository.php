<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 21.07.19
 * Time: 20:37
 */

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Era\EshopOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class EshopOrderRepository extends ServiceEntityRepository
{
    /**
     * EshopOrderRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EshopOrder::class);
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function findBySentEshopOrder($orderId)
    {
        $q = $this->createQueryBuilder('eo')
            ->andWhere('eo.order_id =:orderId')
            ->andWhere('eo.processed_by_eshop_date is null')
            ->setParameter('orderId', $orderId)
            ->setMaxResults(1)
            ->addOrderBy('eo.id', 'desc')
            ->getQuery();

        try {
            $items = $q->getOneOrNullResult();
        } catch (NoResultException $e) {
            return null;
        }


        return $items;
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function findBySentEshopOrders($orderId)
    {
        $q = $this->createQueryBuilder('eo')
            ->andWhere('eo.order_id =:orderId')
            ->setParameter('orderId', $orderId)
            ->addOrderBy('eo.id')
            ->getQuery();
        $items = $q->getResult();

        return $items;
    }

    public function countRecords($orderId)
    {
        $where = ['order_id' => $orderId];
        $out = $this->count($where);

        return $out;
    }
}