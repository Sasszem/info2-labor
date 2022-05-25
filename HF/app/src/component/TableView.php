<?php

namespace component;

/**
 * Glue PageSelector, SearchModal and Table together to forge a magic blade
 */
class TableView
{
    protected static function fullSpec(): array
    {
        return [];
    }
    protected static function searchDesc(): string
    {
        return '';
    }
    protected static function url(): string
    {
        return '';
    }

    protected static function customHTML(): string
    {
        return '';
    }

    /**
     * Render table with pagination and search modal
     */
    public static function render($result, $currentPage, $numPages)
    {
        $modal = static::searchDesc()==='' ? '' : SearchModal::render('searchModal', static::searchDesc(), static::url(), static::fullSpec());
        $navbar = PageSelector::render($numPages, $currentPage, static::url(), static::searchDesc() === '' ? null : 'searchModal');
        $table = Table::render($result, static::fullSpec());
        return $table . $navbar . $modal . static::customHTML();
    }
}
