<?php

namespace Trepmag\Solr\Classes;

class Client {

    /**
     *
     * @var \Solarium\Client
     */
    protected static $instance = null;

    /**
     *
     * @return \Solarium\Client
     */
    public static function instance() {
        if (static::$instance === null) {
            $config = \Config::get('solr.endpoints');
            static::$instance = new \Solarium\Client($config);
        }
        return static::$instance;
    }

    protected function __construct() {

    }

    protected function __clone() {

    }

}
