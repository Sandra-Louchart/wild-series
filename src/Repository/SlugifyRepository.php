<?php

namespace App\Repository;

use App\Entity\Slugify;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Slugify|null find($id, $lockMode = null, $lockVersion = null)
 * @method Slugify|null findOneBy(array $criteria, array $orderBy = null)
 * @method Slugify[]    findAll()
 * @method Slugify[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SlugifyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slugify::class);
    }

    // /**
    //  * @return Slugify[] Returns an array of Slugify objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Slugify
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
