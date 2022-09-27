<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\PartnerPropertyType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PartnerPropertyType|null find($id, $lockMode = null, $lockVersion = null)
 * @method PartnerPropertyType|null findOneBy(array $criteria, array $orderBy = null)
 * @method PartnerPropertyType[]    findAll()
 * @method PartnerPropertyType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerPropertyTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerPropertyType::class);
    }
}