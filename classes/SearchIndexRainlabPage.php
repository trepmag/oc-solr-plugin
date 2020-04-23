<?php

namespace Trepmag\Solr\Classes;

use RainLab\Pages\Classes\Page;

class SearchIndexRainlabPage extends SearchIndex {

    public static function getObjectClass() {
        return \RainLab\Pages\Classes\Page::class;
    }

    protected function setDefaultFieldsValues($item) {
        $this->id = $item->id;
        $this->type = 'Page';
        $this->is_hidden = $item->viewBag['is_hidden'];
        $this->mtimeDate = \DateTime::createFromFormat('U', $item->mtime);
        foreach ($this->localeCodes as $localCode) {
            $this->title[$localCode] = $item->getAttributeTranslated('viewBag', $localCode)['title'];
            $this->abstract[$localCode] = $item->getAttributeTranslated('markup', $localCode);
        }
    }

    public function getItems() {
        $pages = Page::all();
        return $pages;
    }

}
