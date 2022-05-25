<?php

namespace component;

/**
 * Pages selector, with prev/next buttons, search icon, etc.
 */
class PageSelector
{
    /**
     * Make an interactive navbar for $numPages pages, $currentPage being selected, pointing to $url
     * If $searchModalId is specified, add a search button too
     */
    public static function render(int $numPages, int $currentPage, string $url, ?string $searchModalId): string
    {
        // prepare prev link
        // needs to be disabled (and link removed) if alredy at first page
        $prevDisabled = $currentPage == 0;
        $prevLink = $prevDisabled ? '' : "{$url}?" . self::updateLink($currentPage - 1);
        $prevDisabledClass = $prevDisabled ? 'disabled' : '';

        // similar to prev link
        $nextDisabled = $currentPage == $numPages - 1;
        $nextLink = $nextDisabled ? '' : "{$url}?" . self::updateLink($currentPage + 1);
        $nextDisabledClass = $nextDisabled ? 'disabled' : '';

        // all the buttons
        $buttons = implode('', array_map(function ($n) use ($currentPage, $url) {
            // if current page is this one, add class & aria
            $active = $currentPage == $n;
            $activeClass = $active ? 'active' : '';
            $activeAria = $active ? 'aria-current="page"' : '';

            // make updated link
            $url = $url . '?' . self::updateLink($n);

            return <<<END
            <li class="page-item $activeClass" $activeAria>
                <a class="page-link" href="$url">$n</a>
            </li>
            END;
        }, range(0, max($numPages - 1, 0))));


        // search link is it's own item if specified
        $searchLink = is_null($searchModalId) ? '' : <<<END
        <li class="page-item">
            <a class="page-link" aria-label="Search" data-bs-toggle="modal" data-bs-target="#{$searchModalId}">
            <span aria-hidden="true">🔍</span>
            </a>
        </li>
        END;

        // assemble component
        return <<<END
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item $prevDisabledClass">
                    <a class="page-link" href="$prevLink" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                $buttons
                
                <li class="page-item $nextDisabledClass">
                    <a class="page-link" href="$nextLink" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                $searchLink
            </ul>
        </nav>
        END;
    }

    protected static function updateLink(int $page): string
    {
        return http_build_query(array_merge($_GET, array("page"=>$page)));
    }
}
