<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Domain;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderCancelled;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Event\PaymentConfirmed;
use App\Order\Domain\Exception\InvalidOrderTransitionException;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderNumber;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    private function buildOrder(array $items = []): Order
    {
        $defaultItems = [
            [
                'productId'      => '018eefce-0000-7000-0000-000000000001',
                'variantId'      => null,
                'sku'            => 'PROD-001',
                'name'           => 'Widget Pro',
                'quantity'       => 2,
                'unitPrice'      => Money::of(2999, 'USD'),
            ],
        ];

        return Order::place(
            id: OrderId::generate(),
            organizationId: '018eefce-0000-7000-0000-000000000002',
            customerId: '018eefce-0000-7000-0000-000000000003',
            number: OrderNumber::generate(2024, 1),
            itemData: $items ?: $defaultItems,
            shippingAddress: ['street' => '123 Main St', 'city' => 'Austin', 'country' => 'US'],
            currency: 'USD',
        );
    }

    public function test_place_creates_order_in_pending_status(): void
    {
        $order = $this->buildOrder();

        self::assertSame(OrderStatus::PENDING, $order->status());
        self::assertTrue($order->hasPendingEvents());
    }

    public function test_place_raises_order_created_event(): void
    {
        $order  = $this->buildOrder();
        $events = $order->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrderCreated::class, $events[0]);
        /** @var OrderCreated $event */
        $event = $events[0];
        self::assertSame('order.created', $event->eventName());
    }

    public function test_place_calculates_correct_total(): void
    {
        $order = $this->buildOrder([
            [
                'productId'  => '018eefce-0000-7000-0000-000000000001',
                'variantId'  => null,
                'sku'        => 'PROD-001',
                'name'       => 'Widget',
                'quantity'   => 3,
                'unitPrice'  => Money::of(1000, 'USD'),
            ],
            [
                'productId'  => '018eefce-0000-7000-0000-000000000004',
                'variantId'  => null,
                'sku'        => 'PROD-002',
                'name'       => 'Gadget',
                'quantity'   => 1,
                'unitPrice'  => Money::of(500, 'USD'),
            ],
        ]);

        // 3 × $10.00 + 1 × $5.00 = $35.00 = 3500 cents
        self::assertSame(3500, $order->total()->amount());
    }

    public function test_confirm_transitions_order_to_confirmed(): void
    {
        $order = $this->buildOrder();
        $order->pullDomainEvents();

        $order->confirm();

        self::assertSame(OrderStatus::CONFIRMED, $order->status());
        self::assertNotNull($order->confirmedAt());
    }

    public function test_mark_as_paid_transitions_confirmed_order_to_paid(): void
    {
        $order = $this->buildOrder();
        $order->confirm();
        $order->pullDomainEvents();

        $order->markAsPaid('pay_abc123');

        self::assertSame(OrderStatus::PAID, $order->status());
        self::assertNotNull($order->paidAt());

        $events = $order->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PaymentConfirmed::class, $events[0]);
    }

    public function test_cancel_transitions_pending_order_to_cancelled(): void
    {
        $order = $this->buildOrder();
        $order->pullDomainEvents();

        $order->cancel('Changed mind');

        self::assertSame(OrderStatus::CANCELLED, $order->status());
        self::assertSame('Changed mind', $order->cancellationReason());

        $events = $order->pullDomainEvents();
        self::assertInstanceOf(OrderCancelled::class, $events[0]);
    }

    public function test_cancel_shipped_order_throws_exception(): void
    {
        $order = $this->buildOrder();
        $order->confirm();
        $order->markAsPaid('pay_123');
        $order->ship('TRACK123', 'FedEx');

        $this->expectException(InvalidOrderTransitionException::class);

        $order->cancel('Too late');
    }

    public function test_mark_as_paid_on_pending_order_throws_exception(): void
    {
        $order = $this->buildOrder();

        $this->expectException(InvalidOrderTransitionException::class);

        $order->markAsPaid('pay_123');
    }

    public function test_place_empty_order_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('at least one item');

        Order::place(
            id: OrderId::generate(),
            organizationId: '018eefce-0000-7000-0000-000000000002',
            customerId: '018eefce-0000-7000-0000-000000000003',
            number: OrderNumber::generate(2024, 1),
            itemData: [],
            shippingAddress: [],
            currency: 'USD',
        );
    }

    public function test_pulling_events_clears_the_queue(): void
    {
        $order = $this->buildOrder();

        self::assertTrue($order->hasPendingEvents());
        $order->pullDomainEvents();
        self::assertFalse($order->hasPendingEvents());
    }
}
