# laravel-review, effortlessly add reviews to any Laravel model.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fajarwz/laravel-review.svg?style=flat-square)](https://packagist.org/packages/fajarwz/laravel-review)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/fajarwz/laravel-review/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/fajarwz/laravel-review/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/fajarwz/laravel-review/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/fajarwz/laravel-review/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/fajarwz/laravel-review.svg?style=flat-square)](https://packagist.org/packages/fajarwz/laravel-review)

Flexible and powerful review system for Laravel, let any model review and be reviewed.

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

### Creating a Review

```php
$user = User::find(1);
$product = Product::find(1);

// Create a new approved review
$user->review($product, 4.5, 'Great product!');

// Create a new unapproved review (requires approval)
$user->review($product, 3.0, 'Needs improvement', false);
```

The review method takes three required parameters: the reviewable model, the rating, and the review content. An optional fourth parameter, `$isApproved`, can be set to false to create an unapproved review.

Only approved reviews are calculated in the `review_summaries` table. Updating an unapproved review will not affect the summary.

If the reviewer model has already submitted a review for the same reviewable model, a `DuplicateReviewException` will be thrown.

To update a review, use `updateReview()` instead.

### Updating a review

```php
$user->updateReview($product, 5, 'Product is even better now!');
```

### Unreviewing a model

To cancel an existing review:

```php
$user->unreview($product);
```

If the reviewer model has not previously reviewed the model, a `ReviewNotFoundException` will be thrown.

### Approve a review

```php
$review = $product->receivedReviews()->first();

$review->approve();
```

### Unapprove a review

```php
$review = $product->receivedReviews()->first();

$review->unapprove();
```

### Get all received reviews

By default, only approved reviews are retrieved.

```php
$product->receivedReviews()->get();
```

To retrieve all reviews, including unapproved ones, use the `withUnapproved()`:

```php
$product->receivedReviews()->withUnapproved()->get();
```

To include the reviewer information:

```php
Product::with('receivedReviews.reviewer')->paginate();
```

This query will eager load the reviewer information for each received review.

**Note:** Consider using appropriate eager loading strategies based on your application's needs to optimize query performance.

### Get all given reviews

To get all reviews given by a model:

```php
$user->givenReviews()->get();
```

To include the reviewable model information for each review:

```php
User::with('givenReviews.reviewable')->paginate();
```

This will eager load the reviewable model for each review given by the model.

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
