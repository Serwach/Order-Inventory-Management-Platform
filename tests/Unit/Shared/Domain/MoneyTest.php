<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain;

use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_of_creates_money_with_cents_and_currency(): void
    {
        $money = Money::of(1099, 'USD');

        self::assertSame(1099, $money->amount());
        self::assertSame('USD', $money->currency());
    }

    public function test_zero_creates_money_with_zero_amount(): void
    {
        $money = Money::zero('EUR');

        self::assertTrue($money->isZero());
        self::assertSame(0, $money->amount());
    }

    public function test_add_returns_new_money_with_sum(): void
    {
        $a = Money::of(500, 'USD');
        $b = Money::of(300, 'USD');

        $result = $a->add($b);

        self::assertSame(800, $result->amount());
    }

    public function test_add_throws_on_currency_mismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');

        Money::of(100, 'USD')->add(Money::of(100, 'EUR'));
    }

    public function test_multiply_returns_new_money_scaled_by_factor(): void
    {
        $money = Money::of(1000, 'USD');

        self::assertSame(3000, $money->multiply(3)->amount());
    }

    public function test_subtract_returns_correct_result(): void
    {
        $result = Money::of(1000, 'USD')->subtract(Money::of(250, 'USD'));

        self::assertSame(750, $result->amount());
    }

    public function test_subtract_throws_when_result_would_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::of(100, 'USD')->subtract(Money::of(200, 'USD'));
    }

    public function test_of_throws_on_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('negative');

        Money::of(-1, 'USD');
    }

    public function test_of_throws_on_invalid_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('currency code');

        Money::of(100, 'US');
    }

    public function test_equals_returns_true_for_same_amount_and_currency(): void
    {
        $a = Money::of(500, 'USD');
        $b = Money::of(500, 'USD');

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_amount(): void
    {
        self::assertFalse(Money::of(100, 'USD')->equals(Money::of(200, 'USD')));
    }

    public function test_formatted_returns_human_readable_string(): void
    {
        self::assertSame('USD 10.99', Money::of(1099, 'USD')->formatted());
    }

    public function test_greater_than_comparison(): void
    {
        self::assertTrue(Money::of(200, 'USD')->greaterThan(Money::of(100, 'USD')));
        self::assertFalse(Money::of(100, 'USD')->greaterThan(Money::of(200, 'USD')));
    }
}
