<?php

namespace QSO;

/**
 * QSO list used for Own QSOs
 * A simpler version of the normal QSOList
 */
class OwnQSOList extends \component\TableView
{
    protected static function fullSpec(): array
    {
        return [
            // HAMs only for display
            [
                'Used callsign',
                'db' => 'ham_1_cs',
                'search' => 'no',
                // conditionally swap the columns with these transforms on the two columns
                'transform' => fn ($cs, $row) => $cs === \User\LoggedInUser::getCallsign() ? $row['ham_1'] : $row['ham_2'],
            ],
            [
                'Contact',
                'db' => 'ham_2_cs',
                'search' => 'no',
                'transform' => fn ($cs, $row) => $cs === \User\LoggedInUser::getCallsign() ? $row['ham_1'] : $row['ham_2'],
            ],
            // for search, we add one that will only search
            [
                'Callsign',
                'display' => 'no',
            ],
            [
                'Date & Time (UTC)',
                'db' => 'dtime',
                'search' => 'no',
            ],
            [
                'Mode',
                'search' => 'no',
            ],

            [
                'Frequency',
                'db' => 'freq',
                'search' => 'no',
            ],

            // these two columns can't be searched at all
            [
                'Received report',
                'db' => 'ham_1_cs',
                'search' => 'no',
                'transform' => fn ($cs, $row) => $cs === \User\LoggedInUser::getCallsign() ? $row['report_1'] : $row['report_2']
            ],
            [
                'Sent report',
                'db' => 'ham_2_cs',
                'search' => 'no',
                'transform' => fn ($cs, $row) => $cs === \User\LoggedInUser::getCallsign() ? $row['report_1'] : $row['report_2']
            ],
            // this column is db-generated and will have controls
            [
                'QSL',
                'db' => 'has_qsl',
                'search' => 'no',
                'transform' => function ($has_qsls, $row) {
                    $qsoid = $row['id'];
                    $image_buttons = $has_qsls ? "<a href=\"/qsl/view/?qso=$qsoid\" class=\"text-decoration-none\">🖼</a>" : '';
                    $new_qsl_link = $row['can_send_qsl']===1 ? "<a href=\"/qsl/new?qsoid=$qsoid\" class=\"text-decoration-none\">➕</a>" : '';
                    return $image_buttons . $new_qsl_link;
                }
            ]

        ];
    }

    protected static function searchDesc(): string
    {
        return 'Search for QSO';
    }

    protected static function url(): string
    {
        return '/qso/own';
    }
}
