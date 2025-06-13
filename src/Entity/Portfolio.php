<?php

namespace App\Entity;

use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Twig\Error\RuntimeError;

#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'portfolios')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?float $balance = null;

    #[ORM\Column(options: ["default" => 0])]
    private float $freezeBalance = 0;

    /**
     * @var Collection<int, Depositary>
     */
    #[ORM\OneToMany(targetEntity: Depositary::class, mappedBy: 'portfolio', cascade: ['persist', 'remove'])]
    private Collection $depositaries;

    /**
     * @var Collection<int, DealLog>
     */
    #[ORM\OneToMany(targetEntity: DealLog::class, mappedBy: 'sellPortfolio')]
    private Collection $sellDealLogs;

    /**
     * @var Collection<int, DealLog>
     */
    #[ORM\OneToMany(targetEntity: DealLog::class, mappedBy: 'buyPortfolio')]
    private Collection $buyDealLogs;

    public function __construct()
    {
        $this->depositaries = new ArrayCollection();
        $this->sellDealLogs = new ArrayCollection();
        $this->buyDealLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function addBalance(float $sum): static
    {
        $this->balance += $sum;
 
        return $this;
    }

    public function subBalance(float $sum): static
    {
        $this->balance -= $sum;
 
        return $this;
    }

    /**
     * @return Collection<int, Depositary>
     */
    public function getDepositaries(): Collection
    {
        return $this->depositaries;
    }

    public function getDepositaryByStock(Stock $stock): ?Depositary
    {
        return $this->depositaries->findFirst(
            function (int $key, Depositary $depositary) use ($stock) {
                return $depositary->getStock()->getId() === $stock->getId();
            }
        );
    }

    public function addDepositaryQuantityByStock(Stock $stock, int $quantity): static
    {
        $depositary = $this->getDepositaryByStock($stock);
 
        if (!$depositary) {
            $depositary = (new Depositary())
                ->setStock($stock)
            ;
 
            $this->addDepositary($depositary);
        }
 
        $depositary->addQuantity($quantity);
 
        return $this;
    }
 
    public function subDepositaryQuantityByStock(Stock $stock, int $quantity): static
    {
        $depositary = $this->getDepositaryByStock($stock);
 
        $depositary->subQuantity($quantity);
        $depositary->subFreezeQuantity($quantity);
 
        if ($depositary->getQuantity() === 0) {
            $this->removeDepositary($depositary);
        }
 
        return $this;
    }
        
    public function addDepositary(Depositary $depositary): static
    {
        if (!$this->depositaries->contains($depositary)) {
            $this->depositaries->add($depositary);
            $depositary->setPortfolio($this);
        }

        return $this;
    }

    private function removeDepositary(Depositary $depositary): static
    {
        $this->depositaries->removeElement($depositary);
 
        return $this;
    }
    
    public function getFreezeBalance(): ?float
    {
        return $this->freezeBalance;
    }
 
    public function setFreezeBalance(float $freezeBalance): static
    {
        $this->freezeBalance = $freezeBalance;
 
        return $this;
    }
 
    public function addFreezeBalance(float $sum): static
    {
        $this->freezeBalance += $sum;
 
        return $this;
    }
 
    public function subFreezeBalance(float $sum): static
    {
        $this->freezeBalance -= $sum;
 
        return $this;
    }
 
    public function getAvailableBalance(): ?float
    {
        return $this->balance - $this->freezeBalance;
    }

    /**
     * @return Collection<int, DealLog>
     */
    public function getSellDealLogs(): Collection
    {
        return $this->sellDealLogs;
    }

    public function addSellDealLog(DealLog $sellDealLog): static
    {
        if (!$this->sellDealLogs->contains($sellDealLog)) {
            $this->sellDealLogs->add($sellDealLog);
            $sellDealLog->setSellPortfolio($this);
        }

        return $this;
    }

    public function removeSellDealLog(DealLog $sellDealLog): static
    {
        if ($this->sellDealLogs->removeElement($sellDealLog)) {
            // set the owning side to null (unless already changed)
            if ($sellDealLog->getSellPortfolio() === $this) {
                $sellDealLog->setSellPortfolio(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DealLog>
     */
    public function getBuyDealLogs(): Collection
    {
        return $this->buyDealLogs;
    }

    public function addBuyDealLog(DealLog $buyDealLog): static
    {
        if (!$this->buyDealLogs->contains($buyDealLog)) {
            $this->buyDealLogs->add($buyDealLog);
            $buyDealLog->setBuyPortfolio($this);
        }

        return $this;
    }

    public function removeBuyDealLog(DealLog $buyDealLog): static
    {
        if ($this->buyDealLogs->removeElement($buyDealLog)) {
            // set the owning side to null (unless already changed)
            if ($buyDealLog->getBuyPortfolio() === $this) {
                $buyDealLog->setBuyPortfolio(null);
            }
        }

        return $this;
    }
}
