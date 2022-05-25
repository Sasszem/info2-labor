<?php

namespace component;

/**
 * Display data in a table. Uses an array to specify columns with fransforms, values, classes & othe roptions
 */
class Table
{
    public static function render($result, array $spec)
    {
        $rows = '';
        foreach ($result as $row) {
            $cols = '';
            foreach ($spec as $field) {
                $name = $field[0];
                $key = $field['db'] ?? strtolower($name);
                $transform = $field['transform'] ?? fn ($x) => $x;

                $allowDisplay = filter_var($field['display'] ?? 'yes', FILTER_VALIDATE_BOOLEAN);
                if (!$allowDisplay) {
                    continue;
                }

                $class = $field['class'] ?? '';
                $value = $transform($row[$key], $row);
                $cols .= "<td class=\"$class\">$value</td>";
            }
            $rows .= <<<END
            <tr>
                $cols
            </tr>
            END;
        }
        $header = implode('', array_map(
            fn ($field) =>
                !filter_var($field['display']??'yes', FILTER_VALIDATE_BOOLEAN) ?
                    '' : '<th class="'. ($field['class'] ?? '') . '">' . $field[0] . '</th>',
            $spec
        ));

        return <<<END
        <table class="table table-striped">
            <tr>
                $header
            </tr>
            $rows
        </table>
        END;
    }
}
