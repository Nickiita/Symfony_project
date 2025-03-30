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
    private float $frozenBalance = 0;

    /**
     * @var Collection<int, Depositary>
     */
    #[ORM\OneToMany(targetEntity: Depositary::class, mappedBy: 'portfolio', cascade: ['persist', 'remove'])]
    private Collection $depositaries;

    public function __construct()
    {
        $this->depositaries = new ArrayCollection();
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
        return $this->frozenBalance;
    }
 
    public function setFreezeBalance(float $frozenBalance): static
    {
        $this->frozenBalance = $frozenBalance;
 
        return $this;
    }
 
    public function addFreezeBalance(float $sum): static
    {
        $this->frozenBalance += $sum;
 
        return $this;
    }
 
    public function subFreezeBalance(float $sum): static
    {
        $this->frozenBalance -= $sum;
 
        return $this;
    }
 
    public function getAvailableBalance(): ?float
    {
        return $this->balance - $this->frozenBalance;
    }
}
