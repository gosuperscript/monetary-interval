<?php

declare(strict_types=1);

namespace Superscript\MonetaryInterval;

use Brick\Math\BigNumber;
use Brick\Money\Money;
use Stringable;
use Webmozart\Assert\Assert;

/**
 * @phpstan-consistent-constructor
 */
class MonetaryInterval implements Stringable
{
    public function __construct(
        public Money $left,
        public Money $right,
        public IntervalNotation $notation,
    ) {
        Assert::true($left->getCurrency()->is($this->right->getCurrency()), 'Left and right endpoints must have the same currency.');
        Assert::true($left->isLessThanOrEqualTo($right), sprintf('Left must be less than or equal to right. Got %s and %s', $left, $right));
    }

    public static function fromString(string $interval): static
    {
        preg_match(pattern: "/^(?<openingSymbol>[\[(])\s*(?<leftEndpoint>[A-Z]{3}\s*\d+(?:\.\d{1,2})?)?\s*,\s*(?<rightEndpoint>[A-Z]{3}\s*\d+(?:\.\d{1,2})?)?\s*(?<closingSymbol>[])])$/", subject: $interval, matches: $matches);

        if (empty($matches)) {
            throw new \InvalidArgumentException("Invalid interval: $interval");
        }

        $openingSymbol = $matches['openingSymbol'];
        $closingSymbol = $matches['closingSymbol'];
        $leftEndpoint = $matches['leftEndpoint'] ?? null;
        $rightEndpoint = $matches['rightEndpoint'] ?? null;

        if (empty($leftEndpoint)) {
            Assert::eq($openingSymbol, '(', 'Left endpoint must be defined when left side is closed.');
        }

        if (empty($rightEndpoint)) {
            Assert::eq($closingSymbol, ')', 'Right endpoint must be defined when right side is closed.');
        }

        $left = $leftEndpoint ? self::transformStringToMoney($leftEndpoint) : null;
        $right = $rightEndpoint ? self::transformStringToMoney($rightEndpoint) : null;

        if (! $left && ! $right) {
            throw new \InvalidArgumentException('At least one endpoint must be defined.');
        }

        $currency = $left !== null ? $left->getCurrency() : $right->getCurrency();

        return new static(
            left: $left ?? Money::of(PHP_INT_MIN, $currency),
            right: $right ?? Money::of(PHP_INT_MAX, $currency),
            notation: IntervalNotation::from($openingSymbol.$closingSymbol),
        );
    }

    public function isLessThan(Money|BigNumber|int|float $value): bool
    {
        return $this->notation->isRightOpen()
            ? $this->right->isLessThanOrEqualTo($value)
            : $this->right->isLessThan($value);
    }

    public function isLessThanOrEqualTo(Money|BigNumber|int|float $value): bool
    {
        return $this->right->isLessThanOrEqualTo($value);
    }

    public function isGreaterThan(Money|BigNumber|int|float|string $value): bool
    {
        return $this->notation->isLeftOpen()
            ? $this->left->isGreaterThanOrEqualTo($value)
            : $this->left->isGreaterThan($value);
    }

    public function isGreaterThanOrEqualTo(Money|BigNumber|int|float|string $value): bool
    {
        return $this->left->isGreaterThanOrEqualTo($value);
    }

    public function isEqualTo(MonetaryInterval $interval): bool
    {
        return $this->left->isEqualTo($interval->left) && $this->right->isEqualTo($interval->right) && $this->notation === $interval->notation;
    }

    public function __toString(): string
    {
        $left = $this->left->isEqualTo(PHP_INT_MIN) ? null : $this->left;
        $right = $this->right->isEqualTo(PHP_INT_MAX) ? null : $this->right;

        return "{$this->notation->openingSymbol()}{$left},{$right}{$this->notation->closingSymbol()}";
    }

    public static function transformStringToMoney(string $value): Money
    {
        $currency = substr($value, 0, 3);
        $amount = trim(substr($value, 3));

        return Money::of($amount, $currency);
    }
}
