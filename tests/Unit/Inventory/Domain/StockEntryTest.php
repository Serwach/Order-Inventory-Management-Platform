<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain;

use App\Inventory\Domain\Entity\StockEntry;
use App\Inventory\Domain\Event\InventoryAdjusted;
use App\Inventory\Domain\Event\StockReserved;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

final class StockEntryTest extends TestCase
{
    private function buildEntry(): StockEntry
    {
        return StockEntry::create(
            id: '018eefce-0000-7000-0000-000000000001',
            organizationId: '018eefce-0000-7000-0000-000000000002',
            productId: '018eefce-0000-7000-0000-000000000003',
            warehouseId: 'default',
        );
    }

    public function test_new_entry_has_zero_stock(): void
    {
        $entry = $this->buildEntry();

        self::assertSame(0, $entry->onHand()->value());
        self::assertSame(0, $entry->reserved()->value());
        self::assertSame(0, $entry->available()->value());
    }

    public function test_adjust_increases_on_hand_stock(): void
    {
        $entry = $this->buildEntry();
        $entry->adjust(Quantity::of(50), 'initial_stock', 'PO-001');

        self::assertSame(50, $entry->onHand()->value());
        self::assertSame(50, $entry->available()->value());
    }

    public function test_adjust_raises_inventory_adjusted_event(): void
    {
        $entry = $this->buildEntry();
        $entry->adjust(Quantity::of(10), 'test', 'ref');

        $events = $entry->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(InventoryAdjusted::class, $events[0]);
        /** @var InventoryAdjusted $event */
        $event = $events[0];
        self::assertSame(10, $event->delta);
    }

    public function test_reserve_reduces_available_quantity(): void
    {
        $entry = $this->buildEntry();
        $entry->adjust(Quantity::of(20), 'initial_stock', 'PO-001');
        $entry->pullDomainEvents();

        $entry->reserve(Quantity::of(5), 'order-abc');

        self::assertSame(20, $entry->onHand()->value());
        self::assertSame(5, $entry->reserved()->value());
        self::assertSame(15, $entry->available()->value());
    }

    public function test_reserve_raises_stock_reserved_event(): void
    {
        $entry = $this->buildEntry();
        $entry->adjust(Quantity::of(10), 'initial_stock', 'PO-001');
        $entry->pullDomainEvents();

        $entry->reserve(Quantity::of(3), 'order-abc');

        $events = $entry->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(StockReserved::class, $events[0]);
    }

    public function test_reserve_throws_when_insufficient_stock(): void
    {
        $entry = $this->buildEntry();
        $entry->adjust(Quantity::of(5), 'initial_stock', 'PO-001');

        $this->expectException(InsufficientStockException::class);

        $entry->reserve(Quantity::of(10), 'order-abc');
    }

    public function test_release_reservation_restores_available_quantity(): void
    {
        $entry = $this->buildEntry();
        $entry->adjust(Quantity::of(10), 'initial_stock', 'PO-001');

        $reservation = $entry->reserve(Quantity::of(5), 'order-abc');
        $entry->releaseReservation($reservation);

        self::assertSame(0, $entry->reserved()->value());
        self::assertSame(10, $entry->available()->value());
    }

    public function test_fulfil_decreases_on_hand_and_reserved(): void
    {
        $entry = $this->buildEntry();
        $entry->adjust(Quantity::of(10), 'initial_stock', 'PO-001');

        $reservation = $entry->reserve(Quantity::of(3), 'order-abc');
        $entry->fulfil($reservation);

        self::assertSame(7, $entry->onHand()->value());
        self::assertSame(0, $entry->reserved()->value());
    }
}
