<?php

namespace Trepmag\Solr\Classes;

/*
 * Search index update event for Cms\Classes\Page objects.
 *
 * @todo:
 * - No Cms\Classes\Page update or delete event exists; need to implements a patch at core level
 */

class SearchIndexUpdateEventPage extends SearchIndexUpdateEvent {

    public function subscribe($events) {
        // todo!
    }

}
