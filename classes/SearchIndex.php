<?php

namespace Trepmag\Solr\Classes;

use Solarium\Core\Query\DocumentInterface;
use View;

/**
 * Defines for a given object (might be Model object or any other type):
 * - what to push in the index: see buildDoc()
 * - when the index must be updated (index crud): see getUpdateEventClass()
 */
abstract class SearchIndex {

    // Default fields value to index
    protected $id;
    protected $type;
    protected $is_hidden;
    protected $mtimeDate;
    protected $title = [];
    protected $abstract = [];

    // Other attributes
    protected $localeCodes = [];
    public static $DT_FORMAT = 'Y-m-d\TH:i:s\Z';
    private $path_hint_view_document_text = 'trepmag.solr::trepmag.solr.document-text';

    public function __construct() {

        // Find a custom template for text doc
        $path_hint = implode('.', array_slice(explode('\\', strtolower(get_class($this))), 0, 2));
        $path_hint_view = "$path_hint::trepmag.solr.document-text";
        if (View::exists($path_hint_view)) {
            $this->path_hint_view_document_text = $path_hint_view;
        }

        // Set local codes
        if (class_exists('RainLab\Translate\Classes\Translator')) {
            foreach (\RainLab\Translate\Models\Locale::orderByDesc('is_default')->orderBy('sort_order')->get() as $locale) {
                $this->localeCodes[] = $locale->code;
            }
        }
        else {
            $this->localeCodes[] = 'en';
        }
    }

    abstract public static function getObjectClass();

    abstract public static function getUpdateEventClass();

    /**
     * Set default object fields values to be indexed.
     *
     * @param type $object, instance of a model to be indexed
     */
    abstract protected function setDefaultFieldsValues($object);

    public function buildDoc($object, DocumentInterface $doc) {

        // Set default fields values
        $this->setDefaultFieldsValues($object);

        // Build doc
        $doc->id = $this->buildDocId($this->id);
        $doc->type_label_s = $this->type;
        $doc->is_hidden_i = $this->is_hidden;
        if ($this->mtimeDate) {
            $doc->mtime_dt = $this->mtimeDate->format(self::$DT_FORMAT);
        }
        foreach ($this->localeCodes as $localeCode) {
            $doc->{"title_" . $localeCode . "_ts"} = $this->title[$localeCode];
            $doc->{"title_txt_$localeCode"} = $this->title[$localeCode];
            $doc->{"abstract_txt_$localeCode"} = $this->abstract[$localeCode];
        }

        // Fill in the _text_txt_* fields
        foreach ($this->localeCodes as $localeCode) {
            $doc->{"_text_txt_$localeCode"} = $this->buildDocText($object, $doc, $localeCode);
        }
        return $doc;
    }

    public function buildDocId($id) {
        return $this::getObjectClass() . ':' . $id;
    }

    public function buildDocText($object, DocumentInterface $doc, $localeCode) {
        $view = view($this->path_hint_view_document_text, [
            'object' => $object,
            'doc' => $doc,
            'localeCode' => $localeCode,
        ]);
        return $view->render();
    }

    abstract public function getObjects();
}
