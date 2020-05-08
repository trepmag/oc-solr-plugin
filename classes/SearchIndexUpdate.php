<?php

namespace Trepmag\Solr\Classes;

class SearchIndexUpdate {

    public function fire($job, $data) {
        $object = $data['object'];
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
        dump(__METHOD__);
//        dump($job);
//        dump($data);
        $job->delete();
    }

}
