<?php

namespace Trepmag\Solr\Classes;

/*
 * Search index update event for \RainLab\Pages\Classes\Page objects.
 *
 * @todo:
 * - Remove object from index on rainabPage delete (needs this pull request to be merged: https://github.com/rainlab/pages-plugin/pull/403)
 */

class SearchIndexUpdateEventRainlabPage extends SearchIndexUpdateEvent {

    public function subscribe($events) {
        $events->listen('pages.object.save', __CLASS__ . '@handleRainlabPageUpdate');
    }

    /**
     *
     * @param \RainLab\Pages\Controllers\Index $index
     * @param \RainLab\Pages\Classes\Page $object
     * @param type $type
     */
    public function handleRainlabPageUpdate(\RainLab\Pages\Controllers\Index $index, $object, $type) {
        $this->Update($object);
    }

}
