<?php

namespace component;

/**
 * Create a search modal for fuzzy search. Uses $fields to generate form
 */
class SearchModal
{
    public static function render(string $modalId, string $desc, string $url, array $fields)
    {
        $components = FormComponents::render($fields);

        return <<<END
        <div class="modal fade" id="{$modalId}" tabindex="-1" aria-labelledby="{$modalId}Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form class="mt-3" action="$url" method="GET" id="{$modalId}SearchForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="{$modalId}Label">Search</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <h4>$desc</h4>
                            $components
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Search 🔍</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        END;
    }
}
