<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\PartnerOrderData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method PartnerOrderData|null find($id, $lockMode = null, $lockVersion = null)
 * @method PartnerOrderData|null findOneBy(array $criteria, array $orderBy = null)
 * @method PartnerOrderData[]    findAll()
 * @method PartnerOrderData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerOrderDataRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerOrderData::class);
    }
}