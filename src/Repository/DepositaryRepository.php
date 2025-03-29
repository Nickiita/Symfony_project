<?php

namespace App\Repository;

use App\Entity\Depositary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Depositary>
 */
class DepositaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Depositary::class);
    }

    public function removeDepositary(Depositary $depositary): static
    {
        $this->getEntityManager()->remove($depositary);

        return $this;
    }   
}
