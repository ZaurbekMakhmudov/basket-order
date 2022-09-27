<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\AppHelper;
use App\BasketOrderBundle\Helper\ShopConst;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    /**
     * OrderRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function countRecords($status = null)
    {
        if ($status) {
            $status = strtoupper($status);
            $where = ['status' => $status];
        } else {
            $where = [];
        }

        return $this->count($where);
    }

    /**
     * @return mixed
     */
    public function findByNumberMax()
    {
        $q = $this->createQueryBuilder('o')
            ->select('MAX(o.id)')
            ->getQuery();
        $r = $q->getSingleScalarResult();

        return $r;
    }

    /**
     * @return array
     */
    public function findByOrderAviable($days=null,$code=null)
    {
        $str = AppHelper::jsonFromArray($days);
        if($days == ShopConst::STATUS_ONL){
            $listStatusAviable = ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_DAY_ONL);
        }elseif($days == ShopConst::STATUS_ISS ){
            $listStatusAviable = ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_DAY_ISS);
        }elseif($days == ShopConst::STATUS_RFC ){
            $listStatusAviable = ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_DAY_RFC);
        }elseif($days == '1' ){
            $listStatusAviable = ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_DAY);
        }else{
            $listStatusAviable = ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_AVIABLE);
        }

        sort($listStatusAviable);
        $q = $this->createQueryBuilder('o')
            ->andWhere('o.status in (:states)')
            ->setParameter('states', $listStatusAviable)
            ->andWhere('o.deliveryType not in (:delivery)')
            ->setParameter('delivery', ShopConst::listDeliveryTypeRM())
            ->orderBy('o.id')
            ;
        if($code){
            $q
                ->andWhere('o.orderId =:code')
                ->setParameter('code', $code);

        }
        $r = $q->getQuery();
        $items = $r->getResult();

        return $items;
    }

    /**
     * @param $orderId
     * @param $status
     * @param $today
     * @return mixed
     */
    public function updateStatus($orderId, $status, $today)
    {
        $q = $this->createQueryBuilder('o')
            ->update()
            ->set('o.status', '?2')
            ->set('o.updated', '?3')
            ->andWhere('o.orderId = ?1')
            ->andWhere('o.status != ?2')
            ->setParameter(1, $orderId)
            ->setParameter(2, $status)
            ->setParameter(3, $today)
            ->getQuery();
        return $q->execute();
    }

    /**
     * @param string $userId
     * @return int
     */
    public function findNotProcessedOrdersCount(string $userId): int
    {
        try {

            return $this
                ->createQueryBuilder('o')
                ->select('count(o.id)')
                ->andWhere('o.userId = :userId')
                ->setParameter('userId', $userId)
                ->andWhere('o.status not in (:states)')
                ->setParameter('states', ShopConst::getMappedStatuses(ShopConst::MAPPING_STATUS_NOT_PROCESSED))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {

            return 0;
        }
    }

    /**
     * @param string $userId
     * @return int|mixed|string
     */
    public function findOrderDeliveryPoints(string $userId)
    {
        return $this
            ->createQueryBuilder('o')
            ->select('o.deliveryPointId')
            ->distinct()
            ->andWhere('length(o.deliveryPointId) > 0')
            ->andWhere('o.userId = :userId')
            ->setParameter('userId', $userId)
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOrders(array $orderIds)
    {
        return $this
            ->createQueryBuilder('o')
            ->andWhere('o.orderId in (:orderIds)')
            ->setParameter('orderIds', $orderIds)
            ->getQuery()
            ->getResult();
    }
}
