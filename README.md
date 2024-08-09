# laravel-review, effortlessly add reviews to any Laravel model.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fajarwz/laravel-review.svg?style=flat-square)](https://packagist.org/packages/fajarwz/laravel-review)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/fajarwz/laravel-review/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/fajarwz/laravel-review/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/fajarwz/laravel-review/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/fajarwz/laravel-review/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/fajarwz/laravel-review.svg?style=flat-square)](https://packagist.org/packages/fajarwz/laravel-review)

Flexible and powerful review system for Laravel, let any model review and be reviewed.

## Support us

Click the sponsor button.

## Installation

You can install the package via composer:

```bash
composer require fajarwz/laravel-review
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-review_migrations"
php artisan migrate
```

## Setup

Include the necessary traits:

```php
use Fajarwz\LaravelReview\CanBeReviewed;
use Fajarwz\LaravelReview\CanReview;

// Reviewed model
class Product extends Model
{
    use CanBeReviewed;
}

// Reviewer model
class User extends Model
{
    use CanReview;
}
```

## Usage

### Create a review

```php
$user = User::find(1);
$product = Product::find(1);

// Review is approved by default
$user->review($product, 4.5, 'Great product!');

// Review with approval
$user->review($product, 4.5, 'Great product!', false);
```

### Updating a review

```php
$user->updateReview($product, 5, 'Product is even better now!');
```

### Unreview (cancel review) a model

```php
$user->unreview($product);
```

### Get all received reviews for a reviewed model

```php
$product->receivedReviews()->get();

// Get all reviews with the reviewer model
Product::with('receivedReviews.reviewer')->find(1)->get();
```

### Get all given reviews given by a reviewer model

```php
$user->givenReviews()->get();

// Get all given reviews with the reviewable model
User::with('givenReviews.reviewable')->find(1)->get();
```

### Checking if a reviewer model has reviewed a model

```php
if ($user->hasReviewed($product)) {
    // User has reviewed the product
}
```

### Accessing review summary of a reviewed model

```php
// Access review summary properties
$product->reviewSummary;

// Access the average rating
$product->reviewSummary->average_rating;

// Access the review count
$product->reviewSummary->review_count;
```

## Testing
- TODO

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

We welcome contributions to improve this package! Submit a pull request and clearly describe your changes.

## Security Vulnerabilities

Please contact [hi@fajarwz.com](mailto:hi@fajarwz.com)

## Credits

- [fajarwz](https://github.com/fajarwz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
