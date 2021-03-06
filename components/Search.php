<?php

namespace Trepmag\Solr\Components;

use Request;
use BackendAuth;
use Trepmag\Solr\Classes\SearchQueryBuilder;
use Solarium\Component\Result\Facet;

class Search extends \Cms\Classes\ComponentBase {

    /* @var $client \Solarium\Client */
    protected $client = NULL;
    /* @var $query \Solarium\Core\Query\AbstractQuery|QueryInterface */
    protected $query = NULL;
    protected $solariumResultset = NULL;
    protected $edismaxOptions = [];
    protected $localeCodes = [];
    protected $facets = [];
    protected $facetsFields = [];
    protected $jsonFacetApi = [
      'string' => null,
      'array' => null,
    ];
    protected $facetFilterRequestInputName = 'f';
    protected $sortsDefaultDirections = [];
    protected $pageSize = 10;
    protected $pageStart = NULL;
    protected $rowStart = 0;
    protected $solrException = null;

    public function __construct(\Cms\Classes\CodeBase $cmsObject = null, $properties = array()) {
        parent::__construct($cmsObject, $properties);

        // Set local codes
        if (class_exists('RainLab\Translate\Classes\Translator')) {
            foreach (\RainLab\Translate\Models\Locale::orderByDesc('is_default')->orderBy('sort_order')->get() as $locale) {
                $this->localeCodes[] = $locale->code;
            }
        }
        else {
            $this->localeCodes[] = 'en';
        }

        // Set default edisxmax options
        $t = [];
        foreach ($this->localeCodes as $lc) {
            $t[] = "title_txt_$lc^1 _text_txt_$lc";
        }
        $this->edismaxOptions['queryfields'] = implode(' ', $t);

        // Set default sort directions
        $t = [
            'score' => 'desc',
            'mtime_dt' => 'desc',
        ];
        foreach ($this->localeCodes as $lc) {
            $t['title_' . $lc . '_ts'] = 'asc';
        }
        $this->sortsDefaultDirections = $t;
    }

    public function componentDetails() {
        return [
            'name' => 'Solr search',
            'description' => 'Displays a search user interface which includes a keyword input, facet(s) and a paginated results list.'
        ];
    }

    public function defineProperties() {
        return [
            'pageSize' => [
                'title' => 'Page size',
                'description' => 'Numper of items by page.',
                'default' => $this->pageSize,
                'type' => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'The Page Size property can contain only numeric symbols'
            ],
            'pageSizeInputKey' => [
                'title' => 'Page size input key',
                'description' => 'Allow to change the page size using an input (query string). Use a secret input key for security concerns!',
                'type' => 'string',
            ],
            'facetFields' => [
                'title' => 'Facet fields',
                'description' => "List of facet fields separated by comma(s) and dash(es) for facet pivot.",
                'default' => '',
                'type' => 'text',
                'validationPattern' => '^[\w-]+(,[\w-]+)*$',
                'validationMessage' => 'The Facet fields property can only contains non accentued word with no space.'
            ],
            'jsonFacetApi' => [
                'title' => 'JSON Facet API',
                'description' => 'See https://lucene.apache.org/solr/guide/json-facet-api.html.',
                'default' => '',
                'type' => 'text',
            ],
            'urlQueryStringFilter' => [
                'title' => 'Url query string filter',
                'description' => "Fitler passing an url query string filter (which might be overmerged by the real url query string).",
                'default' => '',
                'type' => 'text',
                'validationPattern' => '^[^"]+$',
                'validationMessage' => '"Double quote char must be encoded url-encoded: " => %22"',
            ],
            'edismaxOptions' => [
                'title' => 'Set edismax options',
                'description' => 'Set edismax options (which includes dismax) one by line as solarium vocabulary (e.g.: boostquery=type_label_s:"Page"^2).',
                'default' => '',
                'type' => 'text',
            ],
        ];
    }

    public function init() {
        $this->client = \Trepmag\Solr\Classes\Client::instance();
        $this->query = $this->client->createQuery($this->client::QUERY_SELECT);

        // Edismax
        $edismax = $this->query->getEDisMax();
        $this->edismaxOptions = $this->buildEdismaxOptions() + $this->edismaxOptions;
        foreach ($this->edismaxOptions as $option => $value) {
            $edismax->{'set' . $option}($value);
        }

        // Hide hidden items for visitors
        if (!BackendAuth::getUser()) {
            $this->query->addParam('fq', 'is_hidden_i:0');
        }

        // Get url query string filter if any
        $uqsf_array = [];
        if (!empty($this->property('urlQueryStringFilter'))) {
            $uqsf = htmlspecialchars_decode($this->property('urlQueryStringFilter'));
            if (strpos($uqsf, '?') !== false) {
                $uqsf = substr($uqsf, strpos($uqsf, '?')+1);
            }
            parse_str($uqsf, $uqsf_array);
        }

        // Keyword query conditions
        $qb = new SearchQueryBuilder();
        $keyword = '*:*';
        if (Request::input('q')) {
            $keyword = Request::input('q');
        }
        elseif (!empty($uqsf_array['q'])) {
            $keyword = $uqsf_array['q'];
        }
        $qb->addKeyword($keyword);

        // Facets field
        $this->facetsFields = !empty($this->property('facetFields')) ? explode(',', $this->property('facetFields')) : [];
        if ($this->getFacetsFields()) {
            // Create facets
            $facetSet = $this->query->getFacetSet();
            foreach ($this->getFacetsFields() as $facetField) {
                if (strpos($facetField, '-') === FALSE) {
                    $facetSet->createFacetField($facetField)->setField($facetField);
                }
                else {
                    // Pivot
                    $facet = $facetSet->createFacetPivot($facetField);
                    $facet->addFields(str_replace('-', ',', $facetField));
                }
            }
        }

        // JSON Facet API
        if (!empty($this->property('jsonFacetApi'))) {
            $this->jsonFacetApi['string'] = html_entity_decode($this->property('jsonFacetApi'));
            $this->jsonFacetApi['array'] = json_decode($this->jsonFacetApi['string'], true);
            $this->client->getPlugin('customizerequest')
              ->createCustomization('json.facet')
              ->setType('param')
              ->setName('json.facet')
              ->setValue($this->jsonFacetApi['string'])
            ;
        }

        // Re-key facet filter which might have been set in urlQueryString
        // property so it doesn't get removed by the facet query condition
        // bellow.
        if (!empty($uqsf_array[$this->facetFilterRequestInputName])) {
            $key_new = -1000;
            foreach ($uqsf_array[$this->facetFilterRequestInputName] as $key => $value) {
                if (is_int($key)) {
                    $uqsf_array[$this->facetFilterRequestInputName][$key_new--] = $value;
                    unset($uqsf_array[$this->facetFilterRequestInputName][$key]);
                }
            }
        }

        // Facet query conditions
        $fri = [];
        if (!empty($uqsf_array[$this->facetFilterRequestInputName])) {
            $fri =  $uqsf_array[$this->facetFilterRequestInputName];
        }
        $fri += $this->getFacetsRequestInput();
        foreach ($fri as $value) {
            $f_components = explode(':', $value);
            if (count($f_components)> 1) {
                $qb->addKeyword("$f_components[0]:" . $this->query->getHelper()->escapePhrase($f_components[1]));
            }
        }

        // Sort
        $uqsf_sort = !empty($uqsf_array['sort']) ? $uqsf_array['sort'] : Request::input('sort');
        if ($uqsf_sort) {
            foreach (explode(',', $uqsf_sort) as $sort) {
                if (preg_match('/(asc|desc)$/', $sort) === 0) {
                    $field = $sort;
                    $direction = $this->sortsDefaultDirections[$field];
                }
                else {
                    list($field, $direction) = explode(' ', $sort);
                }
                $this->query->addSort($field, $direction);
            }
        }
        else {
            $this->query->addSort('score', $this->sortsDefaultDirections['score']);
        }

        // Query
        $this->query->setQuery($qb);

        // Pagination
        $this->pageSize = $this->property('pageSize');
        if ($this->property('pageSizeInputKey') && Request::input($this->property('pageSizeInputKey'))) {
            $this->pageSize = Request::input($this->property('pageSizeInputKey'));
        }
        $this->pageStart = Request::input('page', 1);
        $this->rowStart = ($this->pageStart - 1) * $this->pageSize;
        $this->query->setStart($this->rowStart);
        $this->query->setRows($this->pageSize);

        // Results
        try {
            $this->solariumResultset = $this->client->execute($this->query);
        } catch (\Exception $e) {
            trace_log($e);
            $this->solrException = $e;
        }

        // Build facets structure
        $this->setFacets();
    }

    public function getSolariumResultset() {
        return $this->solariumResultset;
    }

    public function getModelResultset() {
        $out = [];
        foreach ($this->getSolariumResultset() as $document) {
            list($class, $id) = explode(':', $document->id);
            $out[] = $class::find($id);
        }
        return $out;
    }

    public function getResultsNumFound() {
        return $this->getSolariumResultset()->getNumFound();
    }

    public function onRender() {
        parent::onRender();

        if ($this->solrException) {
            $this->page['solrException'] = $this->solrException;
            return;
        }

        $this->page['pageStart'] = $this->pageStart;
        $this->page['pageSize'] = $this->pageSize;
        $this->page['rowStart'] = $this->rowStart + 1;
        $this->page['rowEnd'] = min([
            $this->rowStart + $this->pageSize,
            $this->getResultsNumFound(),
        ]);
        if ($this->pageStart - 1) {
            $this->page['pagePreviousUrl'] = $this->makeUrl('page', $this->pageStart - 1);
        }
        if ($this->getResultsNumFound() > $this->rowStart + $this->pageSize) {
            $this->page['pageNextUrl'] = $this->makeUrl('page', $this->pageStart + 1);
        }
        $this->page['resultsNumFound'] = $this->getResultsNumFound();
        $this->page['facets'] = $this->facets;
        $this->page['sorts'] = $this->query->getSorts();
        $sortsUrls = [];
        foreach ($this->sortsDefaultDirections as $field => $direction) {
            if (!empty($this->query->getSorts()[$field])) {
                $direction = $this->query->getSorts()[$field];
                $direction = $direction === 'asc' ? 'desc' : 'asc';
            }
            $sortsUrls[$field] = $this->makeUrl('sort', "$field $direction");
        }
        $this->page['sortsUrls'] = $sortsUrls;
        $this->page['localeCode'] = app()->getLocale();
    }

    public function setFacets() {
        if ($this->solariumResultset && $this->solariumResultset->getFacetSet()) {
            foreach ($this->solariumResultset->getFacetSet() as $facetField => $facet) {
              $this->setFacet($facetField, $this->solariumResultset->getFacetSet()->getFacet($facetField));
            }
        }
    }
    public function setFacet($field, $items): void {
        $this->facets[$field] = [
            'field' => $field,
            'items' => [],
        ];
        switch (get_class($items)) {
          case Facet\Field::class:
              foreach ($items as $value => $count) {
                  $this->facets[$field]['items'][$value] = $this->facetItemBuilder(
                          $field,
                          $value,
                          $count
                  );
              }
              break;
          case Facet\Pivot\Pivot::class:
              $this->facets[$field]['items'] = $this->facetPivotBuilder($items->getPivot());
              break;
          case Facet\Buckets::class:
              $this->facets[$field]['items'] = $this->facetBucketBuilder($items->getBuckets(), $this->jsonFacetApi['array'][$field]);
              break;
        }
    }

    public function getFacets() {
        return $this->facets;
    }

    function makeUrl($query_string, $value, $unsets = []) {
        $out = $this->controller->currentPageUrl();
        $fri = Request::input();
        $fri[$this->facetFilterRequestInputName] = $this->getFacetsRequestInput();
        if ($query_string) {
            $fri[$query_string] = $value;
        }
        foreach ($unsets as $unset => $subUnsets) {
            if (is_array($subUnsets)) {
                foreach ($subUnsets as $subUnset => $void) {
                    if (isset($fri[$unset][$subUnset])) {
                        unset($fri[$unset][$subUnset]);
                    }
                }
            } elseif (isset($fri[$unset])) {
                unset($fri[$unset]);
            }
        }
        if (!empty($fri)) {
            $out .= '?' . http_build_query($fri);
        }
        return $out;
    }

    public function getFacetsFields() {
        return $this->facetsFields;
    }

    private function getFacetsRequestInput($flip = false) {
        static $flipped;
        static $facets;

        if (!isset($facets)) {
            $facets = array_values(Request::input($this->facetFilterRequestInputName, []));
        }
        $out = $facets;
        if ($flip) {
            if (!isset($flipped)) {
                $flipped = array_flip($out);
            }
            $out = $flipped;
        }
        return $out;
    }

    /**
     *
     * @param Facet\Pivot\Pivot $pivot
     * @return array
     */
    private function facetPivotBuilder($pivot) {
        $out = [];

        foreach ($pivot as /* @var $pivotItem Facet\Pivot\PivotItem */ $pivotItem) {
            $out[$pivotItem->getValue()] = $this->facetItemBuilder(
                    $pivotItem->getField(),
                    $pivotItem->getValue(),
                    $pivotItem->getCount(),
                    $this->facetPivotBuilder($pivotItem->getPivot())
            );
        }

        return $out;
    }

    /**
     *
     * @param array $jsonFacetApi
     * @param Facet\Bucket $buckets
     * @return array
     */
    private function facetBucketBuilder($buckets, $jsonFacetApiArray) {
        $out = [];

        if (!empty($jsonFacetApiArray['facet'])) {
          $childFieldAlias = key($jsonFacetApiArray['facet']);
          $jsonFacetApiArraySub = !empty($jsonFacetApiArray['facet'][$childFieldAlias]) ? $jsonFacetApiArray['facet'][$childFieldAlias] : null;
        }

        if ($jsonFacetApiArray) {
            foreach ($buckets as /* @var $pivotItem Facet\Bucket */ $bucket) {
                $child = [];
                if (!empty($bucket->getFacets()['child'])) {
                  $child = $this->facetBucketBuilder($bucket->getFacets()['child']->getBuckets(), $jsonFacetApiArraySub);
                }
                $out[$bucket->getValue()] = $this->facetItemBuilder(
                        $jsonFacetApiArray['field'],
                        $bucket->getValue(),
                        $bucket->getCount(),
                        $child
                );
            }
        }

        return $out;
    }

    /**
     *
     * @param type $field
     * @param type $value
     * @param type $count
     * @param type $items
     * @return type
     */
    private function facetItemBuilder($field, $value, $count, $items = []) {
        $rif_flipped = $this->getFacetsRequestInput($flip = true);
        $filter_key = count($this->getFacetsRequestInput());
        $filter_value = "$field:$value";

        $url = null;
        $urlRemove = null;
        if (isset($rif_flipped[$filter_value])) {
            $filter_key_remove = $rif_flipped[$filter_value];
            $urlRemove = $this->makeUrl(NULL, NULL, $unsets = [
                $this->facetFilterRequestInputName => [$filter_key_remove = true]
            ]);
        }
        else {
            $url = $this->makeUrl("{$this->facetFilterRequestInputName}[$filter_key]", $filter_value, $unsets = ['page' => true]);
        }
        return [
            'field' => $field,
            'value' => $value,
            'count' => $count,
            'url' => $url,
            'urlRemove' => $urlRemove,
            'child' => [
                'field' => $field,
                'items' => $items,
            ],
        ];
    }

    public function getQuery() {
        return $this->query;
    }

    public function buildEdismaxOptions() {
        $out = [];
        if (!empty($this->property('edismaxOptions'))) {
            $edismaxOptions = preg_split("/\r\n|\n|\r/", $this->property('edismaxOptions'));
            foreach ($edismaxOptions as $edismaxOption) {
                if (preg_match("/^(?<key>[^\=]+)\=\s*(?<value>.+)$/", $edismaxOption, $matches)) {
                    $out[$matches['key']] = html_entity_decode($matches['value']);
                }
            }
        }

        return $out;
    }
}
