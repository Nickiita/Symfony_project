<?php
 
namespace App\Repository;
 
use App\Entity\DealLog;
use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
 
 
/**
* @extends ServiceEntityRepository<DealLog>
*/
class DealLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DealLog::class);
    }
 
    public function saveDealLog(DealLog $dealLog): void
    {
        $this->getEntityManager()->persist($dealLog);
        $this->getEntityManager()->flush();
    }
 
    /**
    * @param Stock $stock
    * @return array<DealLog>
    */
    public function findByStock(Stock $stock): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.stock = :stock')
            ->setParameter('stock', $stock)
            ->getQuery()
            ->getResult()
        ;
    }
 }