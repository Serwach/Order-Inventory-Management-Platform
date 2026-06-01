<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Domain;

use App\Order\Domain\ValueObject\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function test_pending_can_transition_to_confirmed_or_cancelled(): void
    {
        self::assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CONFIRMED));
        self::assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
        self::assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::PAID));
        self::assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::SHIPPED));
    }

    public function test_confirmed_can_transition_to_paid_or_cancelled(): void
    {
        self::assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::PAID));
        self::assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::CANCELLED));
        self::assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::SHIPPED));
    }

    public function test_paid_can_only_transition_to_shipped(): void
    {
        self::assertTrue(OrderStatus::PAID->canTransitionTo(OrderStatus::SHIPPED));
        self::assertFalse(OrderStatus::PAID->canTransitionTo(OrderStatus::CANCELLED));
        self::assertFalse(OrderStatus::PAID->canTransitionTo(OrderStatus::PENDING));
    }

    public function test_final_statuses_have_no_transitions(): void
    {
        self::assertEmpty(OrderStatus::DELIVERED->allowedTransitions());
        self::assertEmpty(OrderStatus::CANCELLED->allowedTransitions());
    }

    public function test_final_statuses_are_final(): void
    {
        self::assertTrue(OrderStatus::DELIVERED->isFinal());
        self::assertTrue(OrderStatus::CANCELLED->isFinal());
        self::assertFalse(OrderStatus::PENDING->isFinal());
        self::assertFalse(OrderStatus::PAID->isFinal());
    }
}
