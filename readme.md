When you want to use Meilisearch in your Laravel application, you can use Laravel Scout. This is an easy way to sync your models to Meilisearch and quickly search models using Meilisearch. However, sometimes Laravel Scout is not enough. For example if you want:

- More control over your Meilisearch database: do not only save models for example.
- Set searchable, filterable or sortable attributes
- Perform more complex queries to Meilisearch, for example with multiple filters.

This package deals with these kind of situations. You decide which information to send to Meilisearch, and which information you want back. The query builder specifically built for Meilisearch helps to build more complex queries.

## Installation
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

## Getting started
### Create an index
First we need to create an index to save documents to. For example, we need an index to save our products catalogue to. So we can run the following command:

```
php artisan meilisearch:create-index products
```

This command will create a file `database/meilisearch/products.php`. In this file, you can adjust settings for this index. This is not required, however it is highly recommended. If you leave the standard settings, Meilisearch will use all columns of your data to search on. To achieve this, Meilisearch must index all columns of your data. This will take a longer time, and uses more server resources. That's why it is recommended to specify which columns should be searchable, filterable and sortable.

Everytime you want to change something to the settings, simply change this file. After the changes, run the command below.

### Migrate the index to the Meilisearch database
Now we have to actually create the index. To achieve this, run the following command:

```
php artisan meilisearch:set-index-settings
```

Compare this to the database migrations of Laravel. First you have to create a database migration, next you have to run the migration to actually create the table, or make the adjustment.

Run this command *every time you make changes to the `database/meilisearch/products.php` file*. Also, run this command *on every deployment*, so you have an up-to-date Meilisearch instance in production.