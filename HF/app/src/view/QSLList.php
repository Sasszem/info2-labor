<?php

namespace QSL;

/**
 * QSL list view. Used both for inbox and sent QSLs
 * Uses the magic of TableView to eridicate code.
 */
class QSLList extends \component\TableView
{
    protected static function fullSpec(): array
    {
        return [
            // HAMs only for display
            [
                'Partner',
                'db' => 'cs',
                'search' => 'no',
            ],

            [
                'QSL',
                'db' => 'id',
                'search' => 'no',
                'transform' => function ($id, $row) {
                    return <<<END
                    <img src="/qsl/image?id=$id" class="img-fluid" style="height: 200px;">
                    END;
                },
                'class' => 'text-center',
            ],
            [
                'Accepted',
                'search'=>'no',
                'transform' => fn ($x) => $x ? '✔' : '❌',
            ],
        ];
    }
    /**
     * By not specifying other methods we disabled searching
     */
}
