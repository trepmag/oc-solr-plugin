<?php

namespace Trepmag\Solr\Classes;

use Cms\Classes\Page;

class SearchIndexPage extends SearchIndex {

    public static function getObjectClass() {
        return Page::class;
    }

    protected function setDefaultFieldsValues($item) {
        $this->id = $item->id;
        $this->type = 'Page';
        $this->is_hidden = $item->getAttribute('is_hidden');
        $this->mtimeDate = \DateTime::createFromFormat('U', $item->mtime);
        foreach ($this->localeCodes as $localCode) {
            $this->title[$localCode] = $item->getAttributeTranslated('title', $localCode);
            $this->abstract[$localCode] = $item->getAttributeTranslated('description', $localCode);
        }
    }

    /**
     * Get all pages without url parameter.
     */
    public function getItems() {
        $pages = [];
        foreach (Page::all() as $page) {
            if (preg_match('/\:\w/', $page->getAttribute('url')) === 0) {
                $pages[] = $page;
            }
        }
        return $pages;
    }

}
