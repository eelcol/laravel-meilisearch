# Installation

```
composer require eelcol/laravel-meilisearch
```

### Setup .env
Change your .env to include the following variables:
```
MEILISEARCH_HOST=...
MEILISEARCH_KEY=...
```

### Publish assets

```
php artisan vendor:publish --tag=laravel-meilisearch
```