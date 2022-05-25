<?php

/**
 * Base class for all Models used
 * Not mutch functionality in itself, but provides default constructor
 */
class ModelBase
{
    protected mysqli $db;

    /**
     * Default constructor is used when dependency injecting models into controlles static methods
     * as models are created on the fly with this constructor
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }
}
