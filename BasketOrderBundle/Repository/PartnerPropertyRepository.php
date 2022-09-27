<?php

namespace App\BasketOrderBundle\Repository;


use App\BasketOrderBundle\Entity\PartnerProperty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PartnerProperty|null find($id, $lockMode = null, $lockVersion = null)
 * @method PartnerProperty|null findOneBy(array $criteria, array $orderBy = null)
 * @method PartnerProperty[]    findAll()
 * @method PartnerProperty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerPropertyRepository extends ServiceEntityRepository
{
    /**
     * PartnerPropertyRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerProperty::class);
    }
}