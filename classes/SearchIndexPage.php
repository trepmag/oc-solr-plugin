<?php

namespace Trepmag\Solr\Classes;

use Cms\Classes\Page;

class SearchIndexPage extends SearchIndex {

    public static function getObjectClass() {
        return Page::class;
    }

    protected function setDefaultFieldsValues($object) {
        $this->id = $object->id;
        $this->type = 'Page';
        $this->is_hidden = $object->getAttribute('is_hidden');
        $this->mtimeDate = \DateTime::createFromFormat('U', $object->mtime);
        foreach ($this->localeCodes as $localCode) {
            $this->title[$localCode] = $object->getAttributeTranslated('title', $localCode);
            $this->abstract[$localCode] = $object->getAttributeTranslated('description', $localCode);
        }
    }

    /**
     * Get all pages without url parameter.
     */
    public function getObjects() {
        $pages = [];
        foreach (Page::all() as $page) {
            if (preg_match('/\:\w/', $page->getAttribute('url')) === 0) {
                $pages[] = $page;
            }
        }
        return $pages;
    }

}
