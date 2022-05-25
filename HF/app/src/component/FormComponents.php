<?php

namespace component;

/**
 * Create form components from description. Mainly used by SearchModal
 */
class FormComponents
{
    public static function render(array $fields): string
    {
        return implode(array_map(function ($elementDesc) {
            $name = $elementDesc[0];
            $key = $elementDesc['param'] ?? strtolower($name);
            $options = $elementDesc['options'] ?? null;

            $allowSearch = $elementDesc['search'] ?? 'yes';
            $allowSearch = filter_var($allowSearch, FILTER_VALIDATE_BOOLEAN);
            if (!$allowSearch) {
                return '';
            }

            if (!is_null($options)) {
                // have to make a <select> or a <datalist>

                $datalist = filter_var($elementDesc['allowCustomOption'] ?? 'no', FILTER_VALIDATE_BOOLEAN);

                $selectedValue = $elementDesc['value'] ?? null;

                $optionTags = implode('\n', array_map(
                    function ($r) use ($datalist, $selectedValue) {
                        $val = $r[0];
                        $text = $r[1];
                        $value = $datalist ? '' : "value=\"$val\"";
                        $sel = $selectedValue === $val ? 'selected' : '';
                        return "<option $value $sel>$text</option>";
                    },
                    $options
                ));

                if ($datalist) {
                    $element = <<<END
                    <input type="text" list="{$key}List" name="$key">
                    <datalist id="{$key}List">
                        $optionTags
                    </datalist>
                    END;
                } else {
                    $element = <<<END
                    <select class="form-select" id="{$key}Input" name="$key">
                        $optionTags
                    </select>
                    END;
                }
            } else {
                // normal text or other field
                $inputType = $elementDesc['inputType'] ?? 'text';
                $value = $elementDesc['value'] ?? null;
                $value = $value ? "value=\"$value\"" : '';
                $element = <<<END
                <input type="$inputType" class="form-control" id="{$key}Input" name="$key" $value>
                END;
            }

            return <<<END
            <div class="mb-3">
                <label for="{$key}Input" class="form-label">$name</label>
                $element
            </div>
            END;
        }, $fields));
    }
}
