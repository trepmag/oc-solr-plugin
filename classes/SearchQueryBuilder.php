<?php

namespace Trepmag\Solr\Classes;

/**
 * Helper for building a solr query syntax.
 */
class SearchQueryBuilder {

    protected $keywords = [];
    protected $out = '';
    protected $expressionNeedsBuild = true;

    public function addKeyword($keyword, $op = 'AND') {

        if ($keyword instanceof self) {
            $s = '(' . $keyword->__toString() . ')';
        } else {
            $s = $keyword;
        }
        $this->keywords[] = [
            'keyword' => $s,
            'op' => $op,
        ];

        $this->expressionNeedsBuild = true;
        return $this;
    }

    protected function buildExpession($keywords) {

        if ($this->expressionNeedsBuild) {
            $this->out = '';
            foreach ($keywords as $item) {
                $keyword = $item['keyword'];
                $op = $item['op'];
                if ($this->out === '') {
                    $this->out = $keyword;
                } else {
                    $this->out .= " $op $keyword";
                }
            }
            $this->expressionNeedsBuild = false;
        }

        return $this->out;
    }

    public function __toString() {
        return $this->buildExpession($this->keywords);
    }

}
