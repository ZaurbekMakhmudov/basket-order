<?php

declare(strict_types=1);

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\OrderRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderRequest[]    findAll()
 * @method OrderRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRequestRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderRequest::class);
    }
}
