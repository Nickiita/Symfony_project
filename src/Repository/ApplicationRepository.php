<?php

namespace App\Repository;

use App\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function saveApplication(Application $application): void
    {
        $this->getEntityManager()->persist($application);
        $this->getEntityManager()->flush();
    }

    public function removeApplication(Application $application): void
    {
        $this->getEntityManager()->remove($application);
        $this->getEntityManager()->flush();
    } 

    public function findAppropriate(Application $application): ?Application
    {
        return $this
            ->createQueryBuilder('a')
            ->where('a.stock = :stock')
            ->andWhere('a.quantity = :quantity')
            ->andWhere('a.price = :price')
            ->andWhere('a.action = :action')
            ->andWhere('a.user != :user')

            ->setParameter('stock_id', $application->getStock()->getId())
            ->setParameter('quantity', $application->getQuantity())
            ->setParameter('price', $application->getPrice())
            ->setParameter('action', $application->getAction()->getOpposite())
            ->setParameter('user_id', $application->getUser())
            
            ->getQuery()
            ->getOneOrNullResult()
            ;           
    }

    public function saveChanges(): void
    {
        $this->getEntityManager()->flush();
    }
}
