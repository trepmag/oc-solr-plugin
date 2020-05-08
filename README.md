# Solr

This OctoberCMS plugin provides an interface with Solarium for indexing,
searching and displaying results.

## Requirements

A Solr instance must be accessible (https://lucene.apache.org/solr/); check
Solarium PHP Solr client library own requirements (https://github.com/solariumphp/solarium).

## Usage

A default search index class for Rainlab Static Pages is provided. Just copy and adapt the enclosed `config/solr.php` file into the OctoberCMS root dir (e.g.: `src/config/solr.php`).

Index your content using the artisan `solr:update_index` (see Artisan console
tools below).

Add the `solrSearch` component to a page (https://octobercms.com/docs/cms/components).

### solrSearch component

The `solrSearch` component defines the following properties:

#### pageSize

No comments...

#### facetFields

Insert Solr fields separated by comma(s) to be used as facet. To define a pivot,
combines Solr fields by separating them with a dash.

Example:
```
facetFields = "type_label_str,datatype_category_str-datatype_str,categories_str"
```

#### jsonFacetApi

See https://lucene.apache.org/solr/guide/json-facet-api.html.

Example:
```
facetFields = "{"year":{"type":"terms","field":"year_i","sort":{"index":"desc"}}}"
```

#### urlQueryStringFilter

Pass an url with query string (or only the query string part) that apply the
desired filter.

Example:
```
urlQueryStringFilter = "q=med&facets%5Btype_label_str%3AData+Source%5D=type_label_str%3A%22Data+Source%22"
```

Warning: double quote must be escaped as `%22`.

#### edismaxOptions

Set edismax options (which includes dismax) one by line. See [Solarium PHP DisMax component](https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/dismax-component/).

Example:
```
queryfields=title_txt_en^1 _text_txt_en
boostquery=type_label_s:\"Page\"^2
...
```

#### Example
```
title = "Search"
url = "/search/"
layout = "simple-page"
is_hidden = 0

[solrSearch]
pageSize = 10
facetFields = "type_label_str,datatype_category_str-datatype_str,categories_str"
==
{% component 'solrSearch' %}
```

## Console

Here are the available Artisan console tools:
```sh
solr
 solr:empty_index   Empty the Solr server search index.
 solr:ping          Check the connection to the Solr server and the health of the Solr server.
 solr:update_index  Update the Solr server search index.
```

## Implements a indexer

To index alternate content you need to implements the `\Trepmag\Solr\Classes\SearchIndex`
class.

As an example see the existing one for the Rainlab Static Pages content in `classes/SearchIndexRainlabPage.php`

And here is an example which index a `Model`:
```php
<?php

namespace Foo\Bar\Classes;

use Trepmag\Solr\Classes\SearchIndex;
use Foo\Bar\Models\MyData;
use Solarium\Core\Query\DocumentInterface;

class SearchIndexMyData extends SearchIndex {

    public static function getObjectClass() {
        return \Foo\Bar\Models\MyData::class;
    }

    // Set default fields values to be indexed
    protected function setDefaultFieldsValues($object) {
        $this->id = $object->dataid;
        $this->type = 'Data Source';
        $this->is_hidden = $object->datadisplay ? 0 : 1;
        $this->mtimeDate = \DateTime::createFromFormat('Y-m-d', $object->pubdate);
        $this->title = $object->title;
        $this->abstract = $object->abstract;
    }

    // Optionally override the following method to index other fields
    public function buildDoc($object, DocumentInterface $doc) {
        parent::buildDoc($object, $doc);

        // Other fields to add to the index
        $doc->datatype_s = $object->DataType->datatype;

        return $doc;
    }

    public function getObject() {
        $myDatas = MyData::whereNotNull('dataid')->Where('dataid', '<>', '');
        return $myDatas->get();
    }

}
```

Then register the search index class to `config/solr.php` as follow:
```php
<?php

return [
    'endpoints' => [
        'endpoint' => [
            'localhost' => [
                'host' => 'solr',
                'port' => 8983,
                'path' => '/',
                'core' => 'lando',
            ]
        ]
    ],
    'search_index_classes' => [
        \Trepmag\Solr\Classes\SearchIndexRainlabPage::class,
        \Foo\Bar\Classes\SearchIndexMyData::class, // Here...
    ],
];
```
