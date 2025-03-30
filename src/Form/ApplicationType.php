<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\Depositary;
use App\Entity\Portfolio;
use App\Entity\Stock;
use App\Enums\ActionEnum;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ApplicationType extends AbstractType
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

        $builder
            ->add('quantity', IntegerType::class, [
                'constraints' => [
                    new Positive(['message' => 'Please enter a quantity more than zero']),
                    new Callback($this->validateQuantity(...))
                ]
            ])
            ->add('price', NumberType::class, [
                'constraints' => [
                    new Positive(['message' => 'Please enter a price more than zero']),
                    new Callback($this->validatePrice(...))
                ]
                ]);

        if (!$options['is_edit']) {
            $builder->add('action', EnumType::class, [
                'class' => ActionEnum::class,
            ]);
        }
        
        $builder
            ->add('portfolio', EntityType::class, [
                'class' => Portfolio::class,
                'choice_label' => 'id',
                'query_builder' => function (EntityRepository $er) use ($user): QueryBuilder {
                    return $er->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->setParameter('user', $user)
                    ;
                },
                'constraints' => [
                    new Callback($this->validatePortfolioOwnership(...))
                ]
            ])
            ->add('stock', EntityType::class, [
                'class' => Stock::class,
                'choice_label' => fn(Stock $stock): string => "{$stock->getName()} | id: {$stock->getId()}",
                'constraints' => [
                    new Callback($this->validateStockOwnership(...))
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
            'old_quantity' => 0,
            'old_price' => 0.0,
            'is_edit' => false,
        ]);
    }

    private function validatePortfolioOwnership(mixed $portfolio, ExecutionContextInterface $context): void
    {
        if (!($portfolio instanceof Portfolio) || $portfolio->getUser() !== $this->security->getUser()) {
            $context
                ->buildViolation('You can\'t edit this portfolio')
                ->atPath('portfolio')
                ->addViolation()
            ;
        }
    }

    private function validateStockOwnership(mixed $stock, ExecutionContextInterface $context): void
    {
        $formData = $context->getRoot()->getData();
        /** @var ActionEnum $action */
        $action = $formData->getAction();
        /** @var Portfolio $portfolio */
        $portfolio = $formData->getPortfolio();

        if ($action === ActionEnum::SELL
            && !$portfolio->getDepositaries()->exists(
                function (int $key, Depositary $depositary) use ($stock) {
                    return $depositary->getStock()->getId() === $stock->getId();
                }
            )
        ) {
            $context
                ->buildViolation('This stock not exists in this portfolio')
                ->atPath('stock')
                ->addViolation()
            ;
        }
    }

    private function validateQuantity(mixed $quantity, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $formData = $form->getData();

        /** @var ActionEnum $action */
        $action = $formData->getAction();
        /** @var Portfolio $portfolio */
        $portfolio = $formData->getPortfolio();
        /** @var Stock $stock */
        $stock = $formData->getStock();

        $options = $form->getConfig()->getOptions();
        $oldQuantity = $options['old_quantity'] ?? 0;

        if ($action === ActionEnum::SELL) {
            $actualQuantity = $portfolio
                    ->getDepositaryByStock($stock)
                    ?->getActualQuantity()
                + $oldQuantity;

            if ($quantity > $actualQuantity) {
                $context
                    ->buildViolation("Not enough stock to sell. Available: $actualQuantity")
                    ->atPath('quantity')
                    ->addViolation()
                ;
            }
        }
    }

    private function validatePrice(mixed $price, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $formData = $form->getData();

        /** @var ActionEnum $action */
        $action = $formData->getAction();
        /** @var Portfolio $portfolio */
        $portfolio = $formData->getPortfolio();
        $quantity = $formData->getQuantity();

        $options = $form->getConfig()->getOptions();
        $oldPrice = $options['old_price'] ?? 0.0;
        $oldQuantity = $options['old_quantity'] ?? 0;

        if ($action === ActionEnum::BUY) {
            $availableBalance =
                $portfolio
                    ->getAvailableBalance()
                + $oldPrice
                * $oldQuantity
            ;

            if ($quantity * $price > $availableBalance) {
                $context
                    ->buildViolation("Not enough money to buy. Available: $availableBalance")
                    ->atPath('price')
                    ->addViolation()
                ;
            }
        }
    }
}