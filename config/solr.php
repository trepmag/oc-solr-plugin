<?php

return [
    'endpoints' => [
        'endpoint' => [
            'localhost' => [
                'host' => 'solr',
                'port' => 8983,
                'path' => '/',
                'core' => 'solr',
            // For Solr Cloud you need to provide a collection instead of core:
            // 'collection' => 'techproducts',
            ]
        ]
    ],
    'search_index_classes' => [
        \Trepmag\Solr\Classes\SearchIndexRainlabPage::class,
    ],
];
