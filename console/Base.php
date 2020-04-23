<?php

namespace Trepmag\Solr\Console;

use Illuminate\Console\Command;

class Base extends Command {

    /**
     *
     * @var \Solarium\Client
     */
    protected $client = NULL;

    public function __construct($name = null) {
        parent::__construct($name);
        $this->client = \Trepmag\Solr\Classes\Client::instance();
    }
}
