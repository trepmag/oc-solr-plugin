<?php

namespace Trepmag\Solr\Classes;

/*
 * Search index update event for \October\Rain\Database\Model objects.
 */

class SearchIndexUpdateEventDbModel extends SearchIndexUpdateEvent {

    public function subscribe($events) {
        $events->listen('eloquent.saved: *', __CLASS__ . '@handleUpdate');
        $events->listen('eloquent.deleted: *', __CLASS__ . '@handleDelete');
    }

    public function handleUpdate(\October\Rain\Database\Model $object) {
        $this->Update($object);
    }

    public function handleDelete(\October\Rain\Database\Model $object) {
        $this->Delete($object);
    }

}
