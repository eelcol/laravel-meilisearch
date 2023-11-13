# Laravel Meilisearch with QueryBuilder
When you want to use Meilisearch in your Laravel application, you can use Laravel Scout. This is an easy way to sync your models to Meilisearch and quickly search models using Meilisearch. However, sometimes Laravel Scout is not enough. For example if you want:

- More control over your Meilisearch database: do not only save models for example.
- Set searchable, filterable or sortable attributes.
- Perform more complex queries to Meilisearch, for example with multiple filters.
- Use a query builder to fetch data from the Meilisearch database.
- To use some functionalities that are not available in Meilisearch out-of-the-box. For example, displaying documents in random order or displaying facets that are not available in the filtered documents.

This package deals with these kind of situations. You decide which information to send to Meilisearch, and which information you want back. The query builder specifically built for Meilisearch helps to build more complex queries.

When using this package, you should determine yourself when and which data you sent to Meilisearch. So if you automatically want to sent models to Meilisearch after a model is saved or created, Laravel Scout might be a better solution.

## Compatibility with Meilisearch
Currently, this package supports Meilisearch up to version 0.27. Version 0.28 of Meilisearch introduced some breaking changes. A new version of this package compatible with 0.28 will be released soon.

## Installation
Determine which version you need based on the Meilisearch version:

| Meilisearch version | Package version |
|---------------------|-----------------|
| Up to 0.27          | <=1             |
| 0.28                | ^1.0            |
| 0.30                | ~2.0.0          |
| 1.0.*               | ~2.0.0          |
| 1.1.*               | ~2.1.0          |
| 1.2.*               | ~2.2.0         |

So for example, when using Meilisearch 1.0.2, use the following command:

```
composer require eelcol/laravel-meilisearch:~2.0.0
```

### Setup .env
Change your .env to include the following variables:
```
MEILISEARCH_HOST=...
MEILISEARCH_KEY=...
```
When not using a Meilisearch key, the .env variable MEILISEARCH_KEY can be any value.

### Publish assets

```
php artisan vendor:publish --tag=laravel-meilisearch
```

## Getting started
### Create an index
First you need to create an index to save documents to. For example, you need an index to save our products catalogue to. So the following command can be used:

```
php artisan meilisearch:create-index products
```

This command will create a file `database/meilisearch/products.php`. In this file, you can adjust settings for this index. This is not required, however it is highly recommended. If you leave the standard settings, Meilisearch will use all columns of your data to search on. To achieve this, Meilisearch must index all columns of your data. This will take a longer time, and uses more server resources. That's why it is recommended to specify which columns should be searchable, filterable and sortable.

Everytime you want to change something to the settings, simply change this file. After the changes, run the command below.

### Migrate the index to the Meilisearch database
Now the index has to be actually created. To achieve this, run the following command:

```
php artisan meilisearch:set-index-settings
```

Compare this to the database migrations of Laravel. First you have to create a database migration, next you have to run the migration to actually create the table, or make the adjustment.

Run this command *every time you make changes to the `database/meilisearch/products.php` file*. Also, run this command *on every deployment*, so you have an up-to-date Meilisearch instance in production.

When you want to set the index settings on a different Meilisearch installation, you can use the `--mshost` and `--mskey` options:

```
php artisan meilisearch:set-index-settings --mshost=http://another-meilisearch-installation:7700 --mskey=secret-key
```

## Master data in Meilisearch
All the functionalities that are mentioned in the Meilisearch docs are available in this package. The most important functionalities are listed below:

### Insert data
To insert a document in the index `products`, you can do 1 of the following:
```php
Meilisearch::addDocument('products', [
    'id' => 1,
    'title' => 'iPhone SE'
]);
```
```php
Meilisearch::addDocuments('products', [
    [
        'id' => 1,
        'title' => 'iPhone SE'
    ],
    [
        'id' => 2,
        'title' => 'Samsung Galaxy'
    ]
]);
```

You can also directly insert a model or collection. A model gets converted to an array. In order to do this, the package check if the following methods exists on the object, in the following order:
```php
- toMeilisearch()
- toSearchableArray()
- toArray()
```

A `Product` model for example, can look like the following:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function toMeilisearch(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => Str::slug($this->title),
        ];
    }
}
```

The model can be inserted like this:
```php
$product = App\Models\Product::find(1);
Meilisearch::addDocument('products', $product);

// the product will be inserted like:
// [
//      'id' => 1,
//      'title' => 'iPhone SE',
//      'slug' => 'iphone-se'
// ]
```

A collection can also be directly inserted:
```php
$products = App\Models\Product::all();
Meilisearch::addDocuments('products', $products);
```

### Retrieve data
Documents of an index can be retrieved using the `getDocuments` method. When you want to apply filters, using the query builder is advised. The data is automaticly paginated.

```php
$documents = Meilisearch::getDocuments('products');
```

### Delete documents
Documents can be deleted by ID using the `deleteDocument` or `deleteDocuments` methods. Both methods return a `MeilisearchTask` object.

```php
$task = Meilisearch::deleteDocument(index: 'products', id: 1);
$task = Meilisearch::deleteDocuments(index: 'products', ids: [1,2,3]);
```

Documents can also be deleted using the query builder, see below.

### Use the query builder
If you want to apply filtering or sorting, I recommend using the query builder. You can take a look in the tests folder to see some examples. A few simple examples are listed below.

#### Filter on attribute
Simple filtering can be done using the `where` method:
```php
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->get();

$documents = MeilisearchQuery::index('products')
    ->where('price', '<', 100)
    ->get();
```

Multiple `wheres` can also be combined:
```php
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->where('price', '<', 100)
    ->get();
```

#### Filter with 'or'
Currently, it is not possible to filter with 'OR' on the top-level. If you want to filter with 'OR', you have to create a 'where-group' first. The following call will generate an error:
```php
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->orWhere('title', '=', 'Samsung Galaxy')
    ->get();
```

The following code will work however:
```php
$documents = MeilisearchQuery::index('products')
    ->where(function ($q) {
        $q->where('title', '=', 'iPhone SE');
        $q->orWhere('title', '=', 'Samsung Galaxy');
    })
    ->get();
```

This is because of the way Meilisearch filters work, and how this package renders the filters. It also prevents possible issues when combining 'AND' and 'OR' statements. For example, the following query could return unexpected results:

```php
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->orWhere('title', '=', 'Samsung Galaxy')
    ->where('price', '<', 100)
    ->get();
```

Should this query be:
```sql
- (title = 'iPhone SE' OR title = 'Samsung Galaxy') AND price < 100
- title = 'iPhone SE' OR (title = 'Samsung Galaxy' AND price < 100)
- etc...
```

So for now, when using an 'OR' statement, you should start a `where`-group first.

#### Where in
This works best with arrays. For example, you have a product with multiple categories:
```php
[
    'id' => 1,
    'title' => 'iPhone SE',
    'categories' => [
        'phones',
        'smartphones',
        'iphones'
    ],
    'id' => 2,
    'title' => 'Samsung Galaxy',
    'categories' => [
        'phones',
        'smartphones',
        'samsung'
    ],
]
```
This data can be queried:
```php
MeilisearchQuery::index('products')
    ->whereIn('categories', ['phones', 'iphones'])
    ->get();
```

The `whereIn` method will check if *at least 1 of the values* is present on the model. So the query above, will return *all* documents.

#### Where matches
The `whereIn` method will check if at least 1 of the values is present on the model. The `whereMatches` method will check if *ALL* values are present on the model:

```php
// this query will return both iPhone SE and Samsung Galaxy
MeilisearchQuery::index('products')
    ->whereMatches('categories', ['phones', 'smartphones'])
    ->get();

// this query will return ONLY the iPhone SE
MeilisearchQuery::index('products')
    ->whereMatches('categories', ['phones', 'iphone'])
    ->get();

// this query will return ONLY the Samsung Galaxy
MeilisearchQuery::index('products')
    ->whereMatches('categories', ['phones', 'samsung'])
    ->get();
```

#### Empty data
`whereIsEmpty` or `whereNotEmpty` can be used to select documents that have empty values for the given attribute. This matches the following JSON values: `"", [], {}`.

`whereNull` or `whereNotNull` can be used to select documents that have `NULL` values for the given attribute.

```php
MeilisearchQuery::index('products')
    ->whereEmpty('brand')
    ->get();

MeilisearchQuery::index('products')
    ->whereNull('brand')
    ->get();
```


### Using facets
Columns that are attributed as `filterable` can be used in facets. The querybuilder will return these facets with a product-count attached to it. The facets can be defined by using the `setFacets` or `addFacet` methods:

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->setFacets([
        'color',
        'brand'
    ])
    ->get();

MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->addFacet('color')
    ->addFacet('brand')
    ->get();
```

### Delete documents using the querybuilder

You can use filters on a query to delete documents. Other elements on the query, such as pagination or sorting will *not* be applied.

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->delete();
```

The query above will delete all products with the category `phones`.

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->orderBy('title')
    ->limit(20)
    ->delete();
```

The query above is exactly the same as the other query! Remember: when deleting documents using the query builder, only filters will be applied. Limits, ordering and other manipulations will not be applied.

### Disjunctive facets distribution

In the current version of Meilisearch, facets of an attribute are not returned when you are filtering on that specific attribute. See the following discussion: https://github.com/meilisearch/product/discussions/187

For example, when you run the above query, the colors `grey`, `silver`, `gold`, `yellow` are returned. Next, you only want to display the products with a `yellow` color. So you apply a filter:

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->where('color', '=', 'yellow')
    ->setFacets([
        'color',
        'brand',
        'size',
    ])
    ->get();
```

However when you do this, the facet `color` will now only return `yellow`. That makes it more difficult to display all the possible colors to the end-user. Thats why this package has a `keepFacetsInMetadata` method. You can apply filters inside this method, which will not be applied when fetching metadata.

Starting from Meilisearch version 1.1, this problem can be solved using the `multi-search` endpoint. This is how I solved this issue in this package. The package will make an extra query for every filter used in the query. But *all* queries are combined in a single request, to reduce the number of resources needed. Take a look at the following example:

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->keepFacetsInMetadata(function ($q) {
        $q->where('color', '=', 'yellow');
        $q->where('size', '=', 'XL');
    })
    ->setFacets([
        'color',
        'brand',
        'size',
    ])
    ->get();
```

This query will make the following requests:
- Get all brands, with products that are in the phones category and match color=yellow and size=XL
- Get all colors, with products that are in the phones category and match size=XL
- Get all sizes, with products that are in the phones category and match color=yellow

Since version 1.1 of Meilisearch, this is the recommended way of using multi-select facets.

### Limits and offsets
Limits and offsets can easily be added to the query. The following query will return 10 results, starting from the 20th result:

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->limit(10)
    ->offset(20)
    ->get();
```

### Paginate the results
Just like using the Laravel querybuilder for the database, you can paginate the results coming from Meilisearch. Simply use the `paginate` method. When using this method, earlier calls to `limit` and `offset` are ignored.

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->paginate(10);
```

Optionally supply the name of the query-parameter to use to fetch the current page. 'page' is used by default.

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->paginate(10, 'pageNumber');
```

### Sort the results
#### In random order
Out of the box, Meilisearch does not offer the option to randomly order documents. However, sometimes you want to display a few random products. To make this possible, this package adds this functionality. Be aware that the package will make a query to your Meilisearch database *for every random element*, plus 1 extra query. So if you want to fetch 100 documents in random order, there will be 101 queries made. Meilisearch queries are very fast, however when you make this kind of number of queries, it can still become slow. So I recommend to use this method only with a low number of documents (less than 10), or for example cache the results.

```php
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->inRandomOrder()
    ->limit(10)
    ->get();
```
