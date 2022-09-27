<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\ShopConst;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method Basket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Basket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Basket[]    findAll()
 * @method Basket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BasketRepository extends ServiceEntityRepository
{
    /**
     * BasketRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Basket::class);
    }

    public function countRecords($option = [])
    {
        return $this->count($option);
    }

    public function getAbandonedBaskets(string $timeInterval)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->leftJoin(Order::class, 'o', Join::WITH, 'b.orderId = o.orderId');
        $qb->andWhere('b.active = :active');
        $qb->setParameter('active', ShopConst::ACTIVE_BASKET);
        $qb->andWhere('b.created < :created');
        $qb->setParameter('created', new \DateTime($timeInterval));
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('b.orderId'),
                $qb->expr()->eq('o.status', ':status'),
            ),
        );
        $qb->setParameter('status', ShopConst::STATUS_DRAFT);

        return $qb->getQuery()->getResult();
    }
}
