<?php

namespace NexusPHP\Components;
use Meilisearch\Client;

class Meili
{
    protected static $_meiliSearch;

    /**
     * @return mixed
     */
    public static function getMeiliSearch()
    {

        global $meilisearch_host, $meilisearch_key;

        if (self::$_meiliSearch === null) {
            self::$_meiliSearch = new Client($meilisearch_host, $meilisearch_key);
        }

        return self::$_meiliSearch;
    }
}
