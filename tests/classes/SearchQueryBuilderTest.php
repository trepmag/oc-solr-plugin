<?php

namespace Trepmag\Solr\Tests\Classes;

use PluginTestCase;
use Trepmag\Solr\Classes\SearchQueryBuilder;

class SearchQueryBuilderTest extends PluginTestCase {

    public function testOneKeyword() {
        $qb = new SearchQueryBuilder();
        $qb->addKeyword('Salade');
        $this->assertEquals($qb->__toString(), 'Salade');
    }

    public function testTwoKeyword() {
        $qb = new SearchQueryBuilder();
        $qb->addKeyword('Salade');
        $qb->addKeyword('Cheese');

        $this->assertEquals($qb->__toString(), 'Salade AND Cheese');
    }

    public function testSubKeyword() {
        $qb1 = new SearchQueryBuilder();
        $qb1->addKeyword('Cheese');
        $qb1->addKeyword('Tomate', 'OR');

        $qb = new SearchQueryBuilder();
        $qb->addKeyword('Dinner');
        $qb->addKeyword($qb1);

        $this->assertEquals($qb->__toString(), 'Dinner AND (Cheese OR Tomate)');
    }

}
