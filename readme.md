# Laravel Meilisearch with QueryBuilder
When you want to use Meilisearch in your Laravel application, you can use Laravel Scout. This is an easy way to sync your models to Meilisearch and quickly search models using Meilisearch. However, sometimes Laravel Scout is not enough. For example if you want:

- More control over your Meilisearch database: do not only save models for example.
- Set searchable, filterable or sortable attributes.
- Perform more complex queries to Meilisearch, for example with multiple filters.
- Use a query builder to fetch data from the Meilisearch database.
- To use some functionalities that are not available in Meilisearch out-of-the-box. For example, displaying documents in random order or displaying facets that are not available in the filtered documents.

This package deals with these kind of situations. You decide which information to send to Meilisearch, and which information you want back. The query builder specifically built for Meilisearch helps to build more complex queries.

When using this package, you should determine yourself when and which data you sent to Meilisearch. So if you automatically want to sent models to Meilisearch after a model is saved or created, Laravel Scout might be a better solution.

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

## Master data in Meilisearch
All the functionalities that are mentioned in the Meilisearch docs are available in this package. The most important functionalities are listed below:

### Insert data
To insert a document in the index `products`, you can do 1 of the following:
```
Meilisearch::addDocument('products', [
    'id' => 1,
    'title' => 'iPhone SE'
]);
```
```
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
```
- toMeilisearch()
- toSearchableArray()
- toArray()
```

A `Product` model for example, can look like the following:

```
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
```
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
```
$products = App\Models\Product::all();
Meilisearch::addDocuments('products', $products);
```

### Retrieve data
Documents of an index can be retrieved using the `getDocuments` method. When you want to apply filters, this can also be applied using this method. However, I recommend using the query builder when you want to apply filters or do sorting.

```
$documents = Meilisearch::getDocuments('products');
```

### Use the query builder
If you want to apply filtering or sorting, I recommend using the query builder. You can take a look in the tests folder to see some examples. A few simple examples are listed below.

#### Filter on attribute
Simple filtering can be done using the `where` method:
```
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->get();

$documents = MeilisearchQuery::index('products')
    ->where('price', '<', 100)
    ->get();
```

Multiple `wheres` can also be combined:
```
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->where('price', '<', 100)
    ->get();
```

#### Filter with 'or'
Currently, it is not possible to filter with 'OR' on the top-level. If you want to filter with 'OR', you have to create a 'where-group' first. The following call will generate an error:
```
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->orWhere('title', '=', 'Samsung Galaxy')
    ->get();
```

The following code will work however:
```
$documents = MeilisearchQuery::index('products')
    ->where(function ($q) {
        $q->where('title', '=', 'iPhone SE');
        $q->orWhere('title', '=', 'Samsung Galaxy');
    })
    ->get();
```

This is because of the way Meilisearch filters work, and how this package renders the filters. It also prevents possible issues when combining 'AND' and 'OR' statements. For example, the following query could return unexpected results:

```
$documents = MeilisearchQuery::index('products')
    ->where('title', '=', 'iPhone SE')
    ->orWhere('title', '=', 'Samsung Galaxy')
    ->where('price', '<', 100)
    ->get();
```

Should this query be:
```
- (title = 'iPhone SE' OR title = 'Samsung Galaxy') AND price < 100
- title = 'iPhone SE' OR (title = 'Samsung Galaxy' AND price < 100)
- etc...
```

So for now, when using an 'OR' statement, you should start a `where`-group first.

#### Where in
This works best with arrays. For example, you have a product with multiple categories:
```
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
```
MeilisearchQuery::index('products')
    ->whereIn('categories', ['phones', 'iphones'])
    ->get();
```

The `whereIn` method will check if *at least 1 of the values* is present on the model. So the query above, will return *all* documents.

#### Where matches
The `whereIn` method will check if at least 1 of the values is present on the model. The `whereMatches` method will check if *ALL* values are present on the model:

```
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

### Using facets
Columns that are attributed as `filterable` can be used in facets. The querybuilder will return these facets with a product-count attached to it. The facets can be defined by using the `setFacets` or `addFacet` methods:

```
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

In the current version of Meilisearch, facets of an attribute are not returned when you are filtering on an attribute. For example, when you run the above query, the colors `grey`, `silver`, `gold`, `yellow` are returned. Next, you only want to display the products with a `yellow` color. So you apply a filter:

```
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->where('color', '=', 'yellow')
    ->setFacets([
        'color',
        'brand'
    ])
    ->get();
```

However when you do this, the facet `color` will now only return `yellow`. That makes it more difficult to display all the possible colors to the end-user. Thats why this package has a `keepFacetsInMetadata` method. You can apply filters inside this method, which will not be applied when fetching metadata.

When using the `keepFacetsInMetadata` method, the package will create 2 Meilisearch queries. 1 query with all the filters applied to fetch the products, and 1 query with some of the filters to fetch the metadata (facets). So the above example can be changed to the following:

```
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->keepFacetsInMetadata(function ($q) {
        $q->where('color', '=', 'yellow');
    })
    ->setFacets([
        'color',
        'brand'
    ])
    ->get();
```

When doing this, the returned data will have all the facets that are available on the products in the category `phones`. So you can easily display all the colors that are available, even when you are filtering on a color.

Be aware that this method will generate another query. Because most of the times the Meilisearch queries are very fast (< 10ms), I believe this will not cause any significant impact on the site speed.

### Limits and offsets
Limits and offsets can easily be added to the query. The following query will return 10 results, starting from the 20th result:

```
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->limit(10)
    ->offset(20)
    ->get();
```

### Paginate the results
Just like using the Laravel querybuilder for the database, you can paginate the results coming from Meilisearch. Simply use the `paginate` method. When using this method, earlier calls to `limit` and `offset` are ignored.

```
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->paginate(10);
```

Optionally supply the name of the query-parameter to use to fetch the current page. 'page' is used by default.

```
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->paginate(10, 'pageNumber');
```

### Sort the results
#### In random order
Out of the box, Meilisearch does not offer the option to randomly order documents. However, sometimes you want to display a few random products. To make this possible, this package adds this functionality. Be aware that the package will make a query to your Meilisearch database *for every random element*, plus 1 extra query. So if you want to fetch 100 documents in random order, there will be 101 queries made. Meilisearch queries are very fast, however when you make this kind of number of queries, it can still become slow. So I recommend to use this method only with a low number of documents (less than 10), or for example cache the results.

```
MeilisearchQuery::index('products')
    ->where('categories', '=', 'phones')
    ->inRandomOrder()
    ->limit(10)
    ->get();
```