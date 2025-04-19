<?php

namespace App\Repository;

use App\Entity\Hello;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hello>
 */
class HelloRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hello::class);
    }

    public function createLuckyNumber(string $number): Hello
    {
        $hello = new Hello();
        $hello->setLuckyNumber($number);
        $this->getEntityManager()->persist($hello);
        $this->getEntityManager()->flush();

        return $hello;
    }
}