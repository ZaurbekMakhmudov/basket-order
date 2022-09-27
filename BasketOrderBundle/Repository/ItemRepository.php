<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Item;
use App\BasketOrderBundle\Helper\ItemHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{
    /**

     * ItemRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * @return mixed
     */
    public function findByNumberMax()
    {
        $q = $this->createQueryBuilder('i')
            ->select('MAX(i.id)')
            ->getQuery();
        $r = $q->getResult();

        return $r;
    }

    /**
     * @param Basket $basket
     * @return array|null
     */
    public function getItemByCashbox(Basket $basket)
    {
        $q = $this->createQueryBuilder('i')
            ->select('i.id,i.article, i.quantity,i.cost,i.barcode')// i.price, i.minPrice,
            ->andWhere('i.quantity >:quantityZero')
            ->andWhere('i.basketId =:basketId')
            ->setParameter('quantityZero', 0)
            ->setParameter('basketId', $basket->getId())
            ->getQuery();
        $rs = $q->getArrayResult();
        $out = ItemHelper::getItemByCashbox($basket, $rs);

        return $out;
    }

    /**
     * @param Basket $basket
     * @return mixed
     */
    public function agregateItemForCashbox(Basket $basket)
    {
        $q = $this->createQueryBuilder('i')
            ->andWhere('i.quantity >:quantityZero')
            ->andWhere('i.basketId =:basketId')
            ->setParameter('quantityZero', 0)
            ->setParameter('basketId', $basket->getId())
            ->getQuery();
        $rs = $q->getResult();

        $out = ItemHelper::aggrOrderItemsArray($rs);

        return $out;
//3081116   2+1 = 2
//3081117
//3081118
//3081119
//3081220
//3081221
//3081222
        //3112768 и 3112767 - акционный товар (те же трусы ток разных размеров)
    }
    public function agregateItemForStatusUpdate(Basket $basket)
    {
        $q = $this->createQueryBuilder('i')
            ->andWhere('i.basketId =:basketId')
            ->setParameter('basketId', $basket->getId())
            ->getQuery();
        $rs = $q->getResult();

        $out = ItemHelper::aggrOrderItemsArray($rs);

        return $out;
    }
    public function agregateItemForReceivedGW(Basket $basket)
    {
        $q = $this->createQueryBuilder('i')
            ->andWhere('i.basketId =:basketId')
            ->setParameter('basketId', $basket->getId())
            ->getQuery();
        $rs = $q->getResult();
        $out = [];
        if ($rs) {
            /** @var Item $item */
            foreach ($rs as $item) {
                $article = $item->getArticle();
                $quantity = $item->getQuantity();
                if (isset($out[$article])) {
                    $out[$article]->addQuantity($quantity);
                    $item->setBasketId(0);
                    unset($item);
                } else {
                    $out[$article] = $item;
                }
            }
        }

        return $out;
    }
    public function agregateItemForRM(Basket $basket)
    {
        $q = $this->createQueryBuilder('i')
            ->andWhere('i.quantity >:quantityZero')
            ->andWhere('i.basketId =:basketId')
            ->setParameter('quantityZero', 0)
            ->setParameter('basketId', $basket->getId())
            ->getQuery();
        $rs = $q->getResult();

        $out = [];
        if ($rs) {
            /** @var Item $item */
            foreach ($rs as $item) {
                $article = $item->getArticle();
                $quantity = $item->getQuantity();
                if (isset($out[$article])) {
                    $out[$article]->addQuantity($quantity);
                    $item->setBasketId(0);
                    unset($item);
                } else {
                    $out[$article] = $item;
                }
            }
        }

        return $out;
    }

    /**
     * @param Basket $basket
     * @param $items
     * @return array|null
     */
    public function getItemBarcodeByCashbox(Basket $basket, $items)
    {
        $q = $this->createQueryBuilder('i')
            ->select('i.id,i.quantity,i.barcode')
            ->andWhere('i.quantity >:quantityZero')
            ->andWhere('i.basketId =:basketId')
            ->setParameter('quantityZero', 0)
            ->setParameter('basketId', $basket->getId())
            ->getQuery();
        $rs = $q->getArrayResult();
        $out = [];

        if ($rs) {
            foreach ($rs as $r) {
                $quantity = $r['quantity'];
                $barcode = $r['barcode'];
                $key = $barcode;
                if (isset($out[$key])) {
                    $out[$key]['quantity'] = $out[$key]['quantity'] + $quantity;
                } else {
                    $out[$key] = [
                        'quantity' => $quantity,
                        'barcode' => $barcode,
                    ];
                }
            }
        }
        $output = [];
        foreach ($items as $item) {
            $key = $item['barcode'];
            $qty = $item['quantity'];
            if (isset($out[$key])) {
                $output[$key]['barcode'] = $key;
                $output[$key]['quantity'] = $out[$key]['quantity'] + $qty;
            } else {
                $output[$key] = [
                    'quantity' => $qty,
                    'barcode' => $key,
                ];
            }
        }
        $coupons = [];
        if ($basket->getCoupons()) {
            foreach ($basket->getCoupons() as $item) {
                $coupons[] = ['number' => $item['number']];
            }
        }
        $baskets = [
            'basketId' => $basket->getId(),
            'items' => $output,
            'coupons' => $coupons,
        ];

        $cardNumber = $basket->getCardNum();
        if (!empty($cardNumber)) {
            $baskets['cardNumber'] = $cardNumber;
        }

        return $baskets;
    }

    /**
     * @param Basket $basket
     * @return bool
     */
    public function isChangedItems(Basket $basket): bool
    {
        try {
            $count = $this
                ->createQueryBuilder('i')
                ->select('count(i.id)')
                ->andWhere('i.basketId =:basketId')
                ->andWhere('i.quantity != i.originalQuantity')
                ->setParameter('basketId', $basket->getId())
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            $count = 0;
        }

        return (bool)$count;
    }

}
