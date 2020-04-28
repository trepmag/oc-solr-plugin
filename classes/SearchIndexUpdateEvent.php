<?php

namespace Trepmag\Solr\Classes;

use Config;

/*
 * Search index update event.
 *
 * @todo:
 * - No Cms\Classes\Page update or delete event exists; need to implements a patch at core level
 * - Remove item from index on rainabPage delete (needs this pull request to be merged: https://github.com/rainlab/pages-plugin/pull/403)
 */

class SearchIndexUpdateEvent {

    private $solrClient = NULL;
    private $searchIndexclasses = NULL; # SearchIndex classes

    function __construct() {
        $this->solrClient = \Trepmag\Solr\Classes\Client::instance();
        $this->searchIndexclasses = Config::get('solr.search_index_classes', []);
    }

    public function subscribe($events) {
        $events->listen('eloquent.saved: *', $this->getClass() . '@handleModelUpdate');
        $events->listen('eloquent.deleted: *', $this->getClass() . '@handleModelDelete');
        $events->listen('pages.object.save', $this->getClass() . '@handleRainlabPageUpdate');
    }

    public function handleModelUpdate(\October\Rain\Database\Model $object) {

        $searchIndexClass = $this->getSearchIndexclassByObjectClass(get_class($object));
        if ($searchIndexClass) {

            // Get search indexing material
            $searchIndex = new $searchIndexClass;
            $update = $this->solrClient->createUpdate();
            $document = $update->createDocument();

            // Apply indexing
            $document = $searchIndex->buildDoc($object, $document);
            $update->addDocument($document);
            $update->addCommit();
            $result = $this->solrClient->update($update);
        }
    }

    public function handleModelDelete(\October\Rain\Database\Model $object) {

        $searchIndexClass = $this->getSearchIndexclassByObjectClass(get_class($object));
        if ($searchIndexClass) {

            // Get search indexing material
            $searchIndex = new $searchIndexClass;
            $update = $this->solrClient->createUpdate();
            $helper = $update->getHelper();
            $document = $update->createDocument();

            // Apply indexing operation
            $document = $searchIndex->buildDoc($object, $document);
            $update->addDeleteQuery('id:' . $helper->escapeTerm($document->id));
            $update->addCommit();
            $result = $this->solrClient->update($update);
        }
    }

    /**
     *
     * @param \RainLab\Pages\Controllers\Index $index
     * @param \RainLab\Pages\Classes\Page $object
     * @param type $type
     */
    public function handleRainlabPageUpdate(\RainLab\Pages\Controllers\Index $index, $object, $type) {
        $searchIndexClass = $this->getSearchIndexclassByObjectClass(get_class($object));
        if ($searchIndexClass) {

            // Get search indexing material
            $searchIndex = new $searchIndexClass;
            $update = $this->solrClient->createUpdate();
            $document = $update->createDocument();

            // Apply indexing
            $document = $searchIndex->buildDoc($object, $document);
            $update->addDocument($document);
            $update->addCommit();
            $result = $this->solrClient->update($update);
        }
    }

    public function getSearchIndexclassByObjectClass($objectClass) {
        static $out = [];

        if (!isset($out[$objectClass])) {
            $out[$objectClass] = null;
            foreach ($this->searchIndexclasses as $class) {
                if ($class::getObjectClass() === $objectClass) {
                    $out[$objectClass] = $class;
                    break;
                }
            }
        }

        return $out[$objectClass];
    }

    public function getClass() {
        return '\\' . get_class();
    }

}
