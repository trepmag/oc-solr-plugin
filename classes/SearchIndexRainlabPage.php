<?php

namespace Trepmag\Solr\Classes;

use RainLab\Pages\Classes\Page;

class SearchIndexRainlabPage extends SearchIndex {

    public static function getObjectClass() {
        return \RainLab\Pages\Classes\Page::class;
    }

    protected function setDefaultFieldsValues($object) {
        $this->id = $object->id;
        $this->type = 'Page';
        $this->is_hidden = $object->viewBag['is_hidden'];
        $this->mtimeDate = \DateTime::createFromFormat('U', $object->mtime);
        foreach ($this->localeCodes as $localCode) {
            $this->title[$localCode] = $object->getAttributeTranslated('viewBag', $localCode)['title'];
            $this->abstract[$localCode] = $object->getAttributeTranslated('markup', $localCode);
        }
    }

    public function getObjects() {
        $pages = Page::all();
        return $pages;
    }

    public static function getUpdateEventClass() {
        return SearchIndexUpdateEventRainlabPage::class;
    }

}
