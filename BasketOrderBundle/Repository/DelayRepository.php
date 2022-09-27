<?php

namespace App\BasketOrderBundle\Repository;

use App\BasketOrderBundle\Entity\Delay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Delay|null find($id, $lockMode = null, $lockVersion = null)
 * @method Delay|null findOneBy(array $criteria, array $orderBy = null)
 * @method Delay[]    findAll()
 * @method Delay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DelayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delay::class);
    }

    /**
     * @param string $keepDays
     * @return mixed
     */
    public function delOldDelay(string $keepDays)
    {
        return $this
            ->getEntityManager()
            ->createQuery("delete from App\BasketOrderBundle\Entity\Delay d where d.executed < DATE_SUB(CURRENT_DATE(), :day, 'day')")
            ->setParameter('day', $keepDays)
            ->execute();
    }
    // /**
    //  * @return Delay[] Returns an array of Delay objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Delay
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
