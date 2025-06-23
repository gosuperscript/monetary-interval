<?php

namespace Superscript\MonetaryInterval\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Superscript\MonetaryInterval\IntervalNotation;
use Superscript\MonetaryInterval\MonetaryInterval;

#[CoversClass(MonetaryInterval::class)]
#[CoversClass(IntervalNotation::class)]
class IntervalTest extends TestCase
{
    #[Test]
    #[DataProvider('validCases')]
    public function it_parses_interval_from_string(string $input, string $expectedLeft, string $expectedRight, IntervalNotation $expectedNotation): void
    {
        $interval = MonetaryInterval::fromString(interval: $input);
        $this->assertSame($expectedLeft, (string) $interval->left);
        $this->assertSame($expectedRight, (string) $interval->right);
        $this->assertSame($expectedNotation, $interval->notation);
    }

    public static function validCases(): array
    {
        return [
            ['[GBP 1.50,GBP 2.00]', 'GBP 1.50', 'GBP 2.00', IntervalNotation::Closed],
            ['[GBP1.50,GBP2.00]', 'GBP 1.50', 'GBP 2.00', IntervalNotation::Closed],
            ['(USD 1.50,USD 2.00]', 'USD 1.50', 'USD 2.00', IntervalNotation::LeftOpen],
            ['[EUR 1.50,EUR 2.00)', 'EUR 1.50', 'EUR 2.00', IntervalNotation::RightOpen],
            ['(INR 1.50,INR 2.00)', 'INR 1.50', 'INR 2.00', IntervalNotation::Open],
            ['(,GBP2.00]', 'GBP '.PHP_INT_MIN.'.00', 'GBP 2.00', IntervalNotation::LeftOpen],
            ['[GBP1.00,)', 'GBP 1.00', 'GBP '.PHP_INT_MAX.'.00', IntervalNotation::RightOpen],
        ];
    }

    #[Test]
    #[DataProvider('invalidCases')]
    public function it_throws_exception_for_invalid_interval(string $input, string $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        MonetaryInterval::fromString($input);
    }

    public static function invalidCases(): array
    {
        return [
            ['(GBP1,GBP2', 'Invalid interval: (GBP1,GBP2'],
            ['GBP1,GBP2)', 'Invalid interval: GBP1,GBP2'],
            ['GBP1,GBP2', 'Invalid interval: GBP1,GBP2'],
            ['[GBP1|GBP2]', 'Invalid interval: [GBP1|GBP2]'],
            ['[GBP1GBP2]', 'Invalid interval: [GBP1GBP2]'],
            ['[[GBP1,GBP2)', 'Invalid interval: [[GBP1,GBP2)'],
            ['[GBP1,GBP2))', 'Invalid interval: [GBP1,GBP2))'],
            ['[GBP1,]', 'Right endpoint must be defined when right side is closed.'],
            ['[,GBP1]', 'Left endpoint must be defined when left side is closed.'],
            ['[1,2]', 'Invalid interval: [1,2]'],
            ['[GBP1,EUR2]', 'Left and right endpoints must have the same currency.'],
            ['(,)', 'At least one endpoint must be defined.'],
        ];
    }

    #[Test]
    public function left_number_can_not_be_bigger_than_right_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Left must be less than or equal to right. Got GBP 2.00 and GBP 1.00');
        MonetaryInterval::fromString('[GBP2,GBP1]');
    }

    #[Test]
    #[DataProvider('compareCases')]
    public function it_can_compare_intervals(string $input, string $comparator, mixed $value, bool $expectation): void
    {
        $interval = MonetaryInterval::fromString($input);
        $value = match (true) {
            is_string($value) => MonetaryInterval::transformStringToMoney($value),
            default => $value,
        };

        $this->assertEquals(match ($comparator) {
            '>' => $interval->isGreaterThan($value),
            '>=' => $interval->isGreaterThanOrEqualTo($value),
            '<' => $interval->isLessThan($value),
            '<=' => $interval->isLessThanOrEqualTo($value),
        }, $expectation);
    }

    public static function compareCases(): array
    {
        return [
            ['[GBP2.00,GBP5.00]', '>', 'GBP1.00', true],
            ['[GBP2.00,GBP5.00]', '>', 'GBP2.00', false],
            ['[GBP2.00,GBP5.00]', '>', 'GBP3.00', false],
            ['[GBP2.00,GBP5.00]', '>', 'GBP6.00', false],
            ['(GBP2.00,GBP5.00]', '>', 'GBP1.00', true],
            ['(GBP2.00,GBP5.00]', '>', 'GBP2.00', true],
            ['(GBP2.00,GBP5.00]', '>', 'GBP3.00', false],
            ['(GBP2.00,GBP5.00]', '>', 'GBP6.00', false],
            ['[GBP2.00,GBP5.00]', '>', 1, true],

            ['[GBP2.00,GBP5.00]', '>=', 'GBP1.00', true],
            ['[GBP2.00,GBP5.00]', '>=', 'GBP2.00', true],
            ['[GBP2.00,GBP5.00]', '>=', 'GBP3.00', false],
            ['[GBP2.00,GBP5.00]', '>=', 'GBP6.00', false],
            ['(GBP2.00,GBP5.00]', '>=', 'GBP1.00', true],
            ['(GBP2.00,GBP5.00]', '>=', 'GBP2.00', true],
            ['(GBP2.00,GBP5.00]', '>=', 'GBP3.00', false],
            ['(GBP2.00,GBP5.00]', '>=', 'GBP6.00', false],
            ['[GBP2.00,GBP5.00]', '>=', 1, true],

            ['[GBP2.00,GBP5.00]', '<', 'GBP2.00', false],
            ['[GBP2.00,GBP5.00]', '<', 'GBP4.00', false],
            ['[GBP2.00,GBP5.00]', '<', 'GBP5.00', false],
            ['[GBP2.00,GBP5.00]', '<', 'GBP6.00', true],
            ['[GBP2.00,GBP5.00)', '<', 'GBP2.00', false],
            ['[GBP2.00,GBP5.00)', '<', 'GBP3.00', false],
            ['[GBP2.00,GBP5.00)', '<', 'GBP5.00', true],
            ['[GBP2.00,GBP5.00)', '<', 'GBP6.00', true],
            ['[GBP2.00,GBP5.00]', '<', 6, true],

            ['[GBP2.00,GBP5.00]', '<=', 'GBP2.00', false],
            ['[GBP2.00,GBP5.00]', '<=', 'GBP4.00', false],
            ['[GBP2.00,GBP5.00]', '<=', 'GBP5.00', true],
            ['[GBP2.00,GBP5.00]', '<=', 'GBP6.00', true],
            ['[GBP2.00,GBP5.00)', '<=', 'GBP2.00', false],
            ['[GBP2.00,GBP5.00)', '<=', 'GBP3.00', false],
            ['[GBP2.00,GBP5.00)', '<=', 'GBP5.00', true],
            ['[GBP2.00,GBP5.00)', '<=', 'GBP6.00', true],
            ['[GBP2.00,GBP5.00)', '<=', 6, true],
        ];
    }

    #[Test]
    #[DataProvider('stringCases')]
    public function it_can_be_transformed_to_string(string $input): void
    {
        $interval = MonetaryInterval::fromString($input);
        $this->assertSame($input, (string) $interval);
    }

    public static function stringCases(): array
    {
        return [
            ['[GBP 2.00,GBP 5.00]'],
            ['(USD 2.00,USD 5.00)'],
            ['[GBP 2.00,GBP 5.00)'],
            ['(,GBP 5.00]'],
            ['[GBP 2.00,)'],
        ];
    }

    #[Test]
    public function it_can_compare_two_intervals(): void
    {
        $interval1 = MonetaryInterval::fromString('[GBP 1.00,GBP 2.00]');
        $interval2 = MonetaryInterval::fromString('[GBP 1.00,GBP 2.00]');
        $this->assertTrue($interval1->isEqualTo($interval2));

        $interval3 = MonetaryInterval::fromString('(GBP 1.00,GBP 2.00)');
        $this->assertFalse($interval1->isEqualTo($interval3));
    }
}
