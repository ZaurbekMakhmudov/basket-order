<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\PartnerItemData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PartnerItemData|null find($id, $lockMode = null, $lockVersion = null)
 * @method PartnerItemData|null findOneBy(array $criteria, array $orderBy = null)
 * @method PartnerItemData[]    findAll()
 * @method PartnerItemData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerItemDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerItemData::class);
    }

    public function findMaxIndex(string $partnerOrderId)
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare('SELECT MAX(`item_id`) as maxId FROM `partner_item_data` WHERE `partner_order_id` = "'. $partnerOrderId . '"');
        $stmt->execute();
        return $stmt->fetchAllAssociative();
    }


    // /**
    //  * @return PartnerItemData[] Returns an array of PartnerItemData objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PartnerItemData
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
