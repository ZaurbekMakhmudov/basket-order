<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\CouponUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CouponUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method CouponUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method CouponUser[]    findAll()
 * @method CouponUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CouponUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CouponUser::class);
    }

    /**
     * @param string $userId
     * @param string $couponNumber
     * @return int
     */
    public function getCountCouponUser(string $userId, string $couponNumber): int
    {
        try {

            return $this
                ->createQueryBuilder('c')
                ->select('count(c.id)')
                ->andWhere('c.userId = :userId')
                ->setParameter('userId', $userId)
                ->andWhere('c.couponNumber = :couponNumber')
                ->setParameter('couponNumber', $couponNumber)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {

            return 1;
        }
    }

    // /**
    //  * @return CouponUser[] Returns an array of CouponUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CouponUser
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
