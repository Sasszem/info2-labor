<?php

namespace QSO;

require_once 'lib.php';

/**
 * QSO list view
 * Implement table view with pagination help & search modal
 * Uses TableView's magic to exterminate code
 * but sadly, as we need some interval searches it's getting complicated
 * so I have seemingly duplicate columns that are either table-only or search only
 */
class QSOListView extends \component\TableView
{
    protected static function fullSpec(): array
    {
        return [
            // HAMs only for display
            [
                'HAM#1',
                'db' => 'ham_1',
                'search' => 'no',
            ],
            [
                'HAM#2',
                'db' => 'ham_2',
                'search' => 'no',
            ],
            // for search, we add one that will only search once and in the combined 'participants' column
            [
                'Callsign',
                'display' => 'no',
            ],

            // date & time also has different versions for search and display
            [
                'Date & Time (UTC)',
                'db' => 'dtime',
                'search' => 'no',
            ],

            // two pickers for local date & time
            [
                'Start date/time (UTC)',
                'param' => 'startDT',
                'inputType' => 'datetime-local',
                'display' => 'no',
            ],
            [
                'End date/time (UTC)',
                'param' => 'endDT',
                'inputType' => 'datetime-local',
                'display' => 'no',
            ],

            // mode is so special it' simple
            [
                'Mode',
                'options' => [
                    ['', 'Don\'t care'],
                    ['CW', 'CW'],
                    ['DSB', 'AM-DSB'],
                    ['SSB', 'SSB'],
                    ['LSB', 'LSB'],
                    ['USB', 'USB'],
                    ['NFM', 'NBFM'],
                    ['RTTY', 'RTTY'],
                ],
            ],

            // freq also has a single column, but can be searched as a range
            [
                'Frequency',
                'db' => 'freq',
                'search' => 'no',
                'transform' => formatFreq(...),
            ],
            [
                'Start frequency',
                'param' => 'startFreq',
                'display' => 'no',
            ],
            [
                'End frequency',
                'param' => 'endFreq',
                'display' => 'no',
            ],

            // these two columns can't be searched at all
            [
                'Report received by HAM#1',
                'db' => 'report_1',
                'search' => 'no',
            ],
            [
                'Report received by HAM#2',
                'db' => 'report_2',
                'search' => 'no',
            ],
        ];
    }

    protected static function customHTML(): string
    {
        /**
         * Add some JS to create band quickset buttons
         */
        return <<<'END'
        <script>
            bands = [
                ['80m', 3500000, 4000000],
                ['40m', 7000000, 7300000],
                ['20m', 14000000, 14350000],
                ['10m', 28000000, 29700000],
                ['2m', 144000000, 146000000],
                ['70cm', 430000000, 440000000],
            ];
            function setFreqs(event) {
                event.preventDefault();
                const target = event.target;
                document.getElementById('startFreqInput').value = target.getAttribute('startFreq');
                document.getElementById('endFreqInput').value = target.getAttribute('endFreq');
            }

            const modalBody = document.getElementById('endFreqInput').parentNode.parentNode;
            const buttonGroup = document.createElement('div');
            buttonGroup.className = 'mb-3';
            const buttons = bands.reduce((s,band) => s+`<a class="btn btn-primary bandButton" startFreq="${band[1]}" endFreq="${band[2]}">${band[0]}</a>`, '');
            buttonGroup.innerHTML = `<div class="btn-group"><labe>Band presets: </label>${buttons}</div>`;
            modalBody.appendChild(buttonGroup);

            const btns = document.querySelectorAll(".bandButton");
            for(let b of btns)
                b.onclick = setFreqs;
        </script>
        END;
    }

    /**
     * Set descriptiong
     */
    protected static function searchDesc(): string
    {
        return 'Search for QSOs. Leave unspecified columns empty!';
    }

    /**
     * Send form target url
     */
    protected static function url(): string
    {
        return '/qso/';
    }
}
