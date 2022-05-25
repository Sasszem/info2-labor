<?php

namespace HAM;

/**
 * List view for HAMs (seaching mostly)
 * Uses TableView to kill code
 */
class HAMListView extends \component\TableView
{
    protected static function fullSpec(): array
    {
        return [
            ['Callsign'],
            [
                'Name',
                'db' => 'uname',
                'transform' => fn ($val) => $val ?: '-',
            ],
            [
                'QTH',
                'transform' => fn ($val) => $val ?: '-',
            ],
            [
                'Email',
                'transform' => fn ($val, $row) => $row['email_visible'] ? $val : '-',
            ],
            [
                'Exam level',
                'param' => 'exam',
                'db' => 'exam_level',
                'options' => [
                    ['', 'Don\'t care'],
                    ['-', 'Other/not speficied'],
                    ['NNOVICE', '(National) novice'],
                    ['CEPT NOVICE', 'CEPT Novice'],
                    ['HAREC', 'HAREC'],
                ],
                'transform' => fn ($val) => $val==='NNOVICE' ? '(National) Novice' : $val,
            ],
            [
                'Morse exam',
                'param' => 'morseExam',
                'db' => 'morse_exam',
                'options' => [
                    ['', 'Don\'t care'],
                    ['YES', 'Yes'],
                    ['NO', 'No'],
                ],
                'transform' => fn ($val) => $val ? 'YES' : 'NO',
            ],
        ];
    }
    protected static function searchDesc(): string
    {
        return 'Search for HAMs. Leave unspecified columns empty!';
    }

    protected static function url(): string
    {
        return '/ham/';
    }
}
