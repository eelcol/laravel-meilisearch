<?php

return [
    'primaryKey' => 'id',

    // list the columns that are used to perform a search
    'search' => [],

    // list the columns that can be used to filter
    // only these columns can be used inside where() methods
    'filters' => [],

    // list the columns that can be used to sort on
    // only these columns can be used inside orderBy() methods
    'sortable' => [],

    // Built-in ranking rules that ensure relevancy in search results.
    // Contains the ranking rules sorted by order of importance
    // (arranged from the most important rule to the least important rule).
    // The sorting order can be appended, for example 'created_at:desc'.
    // Meilisearche's default ranking rules are applied:
    // https://docs.meilisearch.com/learn/configuration/settings.html#ranking-rules
    'ranking' => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness'],

    // set the total number of maximum hits
    // Meilisearch sets this automatically to 1.000
    // If you make a search request to this index, and that search contains over 1.000 documents
    // only the first 1.000 documents will be displayed. Even when using pagination
    // We set the default value to 10.000, but you can set it to any value you like
    'max_total_hits' => 10000,

    // set the maximum number of different facet values
    // Meilisearch sets this automatically to 100
    // this can be too limiting. We increase the value to 1.000 by default
    // but you are free to set it to any value you like
    'max_values_per_facet' => 1000,
];
