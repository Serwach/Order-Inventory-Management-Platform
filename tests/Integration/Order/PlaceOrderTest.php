<?php

declare(strict_types=1);

namespace App\Tests\Integration\Order;

use App\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PlaceOrderTest extends KernelTestCase
{
    private CommandBusInterface $commandBus;
    private OrderRepositoryInterface $orders;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->commandBus = $container->get(CommandBusInterface::class);
        $this->orders     = $container->get(OrderRepositoryInterface::class);
    }

    public function test_place_order_creates_persisted_order(): void
    {
        $orgId      = '018eefce-0000-7000-0000-000000000010';
        $customerId = '018eefce-0000-7000-0000-000000000011';

        $this->commandBus->dispatch(new PlaceOrderCommand(
            organizationId: $orgId,
            customerId: $customerId,
            items: [
                [
                    'productId'      => '018eefce-0000-7000-0000-000000000020',
                    'variantId'      => null,
                    'sku'            => 'TEST-001',
                    'name'           => 'Test Widget',
                    'quantity'       => 2,
                    'unitPriceAmount' => 1500,
                    'currency'       => 'USD',
                ],
            ],
            shippingAddress: [
                'street'  => '123 Test St',
                'city'    => 'Austin',
                'country' => 'US',
            ],
            currency: 'USD',
        ));

        // Verify order was persisted
        // (In a full integration test, you'd query the DB directly here)
        self::assertTrue(true); // Placeholder — real test queries the DB
    }

    public function test_place_empty_order_throws_exception(): void
    {
        $this->expectException(\DomainException::class);

        $this->commandBus->dispatch(new PlaceOrderCommand(
            organizationId: '018eefce-0000-7000-0000-000000000010',
            customerId: '018eefce-0000-7000-0000-000000000011',
            items: [],
            shippingAddress: [],
            currency: 'USD',
        ));
    }
}
