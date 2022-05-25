<?php

namespace db;

/**
 * Connect to database
 * Since I'm using the OOP style I don't have to close the connection manually.
 * also I'm using a 'drop-in' (sic!) extension for mysqli that helped a bit with debugging
 * i had to modify it somewhat to make it work with newer php
 */
function getConnection()
{
    // using a static variable to simulate a singleton
    static $instance = null;
    if (is_null($instance)) {
        $instance = new \EMysqli\EMysqli($_ENV['db_host'], $_ENV['db_user'], $_ENV['db_password'], $_ENV['db_db']);
    }
    return $instance;
}
