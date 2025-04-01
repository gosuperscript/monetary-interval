# Monetary Interval Library

A PHP library for working with monetary intervals. It provides an elegant way to create, compare, and work with intervals of monetary values using standard interval notation.

## Installation

Install the package via Composer:

```bash
composer require superscript/interval
```

## Requirements

- PHP 8.3 or higher

## Usage

### Creating Intervals

You can create intervals from string notation:

```php
use Superscript\Interval\MonetaryInterval;

// Create from string notation
$interval = MonetaryInterval::fromString('[USD 1.00,USD 5.00]');  // Closed interval
$interval = MonetaryInterval::fromString('(USD 1.00,USD 5.00)');  // Open interval 
$interval = MonetaryInterval::fromString('[USD 1.00,USD 5.00)');  // Right-open interval
$interval = MonetaryInterval::fromString('(USD 1.00,USD 5.00]');  // Left-open interval
$interval = MonetaryInterval::fromString('[USD 1.00,)');      // Left-bounded interval (infinite upper bound)
$interval = MonetaryInterval::fromString('(,USD 1.00]');      // Right-bounded interval (infinite lower bound)
```

### Interval Notation

The library supports four types of interval notation:

- `[a,b]` - **Closed interval**: includes both monetary endpoints `a` and `b`. Example: `[USD 1.00,USD 5.00]` includes all values from USD 1.00 to USD 5.00.
- `(a,b)` - **Open interval**: excludes both monetary endpoints `a` and `b`. Example: `(USD 1.00,USD 5.00)` includes all values greater than USD 1.00 and less than USD 5.00.
- `[a,b)` - **Right-open interval**: includes `a` but not `b`. Example: `[USD 1.00,USD 5.00)` includes USD 1.00 and all values up to (but not including) USD 5.00.
- `(a,b]` - **Left-open interval**: excludes `a` but includes `b`. Example: `(USD 1.00,USD 5.00]` includes all values greater than USD 1.00 up to and including USD 5.00.

The inclusion or exclusion of the endpoints determines how comparisons behave. For example, if an interval is `(USD 1.00,USD 5.00)`, calling `$interval->isGreaterThan('USD 1.00')` will return `true` because USD 1.00 is not part of the interval. However, if the interval is `[USD 1.00,USD 5.00]`, then `$interval->isGreaterThanOrEqualTo('USD 1.00')` will return `true` since USD 1.00 is included.

Understanding this notation is crucial for interpreting comparison behavior correctly.

The library also supports unbounded intervals using empty endpoints. These are interpreted as extending to infinity:
  
- `[a,)` - Left-bounded interval: includes `a` and extends infinitely to the right.
- `(,b]` - Right-bounded interval: includes `b` and extends infinitely to the left.
- `(,)`  - Fully unbounded interval: represents all monetary values.
  
Internally, unbounded sides are represented using `PHP_INT_MIN` or `PHP_INT_MAX`.

### Canonical Money Format

Monetary values in this library follow the canonical money format: `CUR 0.00` — where:
  
- `CUR` is a 3-letter ISO 4217 currency code (e.g., `USD`, `EUR`, `GBP`)
- A space separates the currency code from the numeric value
- The amount uses two decimal places for clarity and consistency

For example:
  
- `USD 10.00` represents 10 US Dollars
- `EUR 0.99` represents 99 Euro cents

This notation ensures compatibility with [brick/money](https://github.com/brick/money) and safe handling of currency math.

### Interval Comparisons

```php
$interval = MonetaryInterval::fromString('[USD 2.00,USD 5.00]');

$interval->isGreaterThan('USD 1.00');      // true
$interval->isGreaterThanOrEqualTo('USD 2.00');  // true
$interval->isLessThan('USD 6.00');         // true
$interval->isLessThanOrEqualTo('USD 5.00');     // true
```

Comparisons such as `$interval->isGreaterThan($amount)` evaluate whether *all* monetary values within the interval are greater than the specified amount. So `[USD 2.00,USD 5.00]` is greater than `USD 1.00` (because every value from USD 2.00 to USD 5.00 is greater than USD 1.00), but not greater than `USD 2.00` unless the interval is open on the left side (e.g., `(USD 2.00,USD 5.00)`).

Similarly, `$interval->isLessThan($amount)` checks whether all values in the interval are less than the specified amount. `(USD 1.00,USD 5.00)` is less than `USD 6.00`, but not less than `USD 5.00` unless the interval excludes `USD 5.00` (e.g., `(USD 1.00,USD 5.00)` or `[USD 1.00,USD 5.00)`.

This logic allows for precise control over monetary comparisons, especially when you want to reason about bounds inclusivity.

## Testing

Run the test suite:

```bash
composer test
```

This includes:
- Static analysis (`composer test:types`)
- Unit tests with coverage (`composer test:unit`)
- Mutation testing (`composer test:infection`)

## License

MIT

## About

This package is developed and maintained by Superscript. This package uses [brick/money](https://github.com/brick/money) for safe monetary value handling.
