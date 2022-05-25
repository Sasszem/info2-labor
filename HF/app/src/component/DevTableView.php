<?php

namespace component;

/**
 * Copy of TableView with dynamic settings
 */
class DevTableView
{
    public static function render($result, $currentPage, $numPages, $fullSpec, $url, $searchDesc, $customHTML = '')
    {
        $modal = SearchModal::render('searchModal', $searchDesc, $url, $fullSpec);
        $navbar = PageSelector::render($numPages, $currentPage, $url, 'searchModal');
        $table = Table::render($result, $fullSpec);
        return $table . $navbar . $modal . $customHTML;
    }
}
