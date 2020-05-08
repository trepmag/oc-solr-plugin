<?php

namespace Trepmag\Solr\Classes;

use Config;

/*
 * Search index update event for objects; defines update listner and handlers.
 *
 * See OctoberCMS Events:
 * https://octobercms.com/docs/services/events#event-class-subscribe
 *
 * @todo:
 * - No Cms\Classes\Page update or delete event exists; need to implements a patch at core level
 * - Remove object from index on rainabPage delete (needs this pull request to be merged: https://github.com/rainlab/pages-plugin/pull/403)
 */

abstract class SearchIndexUpdateEvent {

    private $solrClient = NULL;
    private $searchIndexclasses = NULL; # SearchIndex classes

    function __construct() {
        $this->solrClient = \Trepmag\Solr\Classes\Client::instance();
        $this->searchIndexclasses = Config::get('solr.search_index_classes', []);
    }

    public abstract function subscribe($events);

    protected function Update($object) {
        $searchIndexClass = $this->getSearchIndexclassByObjectClass(get_class($object));
        if ($searchIndexClass) {

            // Build document to index
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

    protected function Delete($object) {
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

    protected function getSearchIndexclassByObjectClass($objectClass) {
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

}
