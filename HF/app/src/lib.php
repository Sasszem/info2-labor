<?php
/**
 * Various small helper functions who are reused but could not fit elsewhere
 */

/**
 * Redirect with error & message then stop.
 * Sends redirect (location) header, sets 'message' and 'error' in session and calls exit();
 *
 * @param string $loc location to redirect to
 * @param ?string $error error to show
 * @param ?string $message error to show
 */
function redirect(string $loc, ?string $error = null, ?string $message = null)
{
    if ($error) {
        $_SESSION['error'] = $error;
    }

    if ($message) {
        $_SESSION['message'] = $message;
    }

    header("Location: $loc", true, 301);
    exit();
}

/**
 * format frequency in SI units, with 3 decimal places
 * @param float $freq frequency to format
 * @return string formatted frequency
 */
function formatFreq(float $freq): string
{
    $prefixes = [
        [1000000000, 'G'],
        [1000000, 'M'],
        [1000, 'k'],
    ];
    foreach ($prefixes as $pref) {
        [$mul, $p] = $pref;
        if ($freq > $mul) {
            $freq = round($freq / $mul, 3);
            return "$freq {$p}Hz";
        }
    }
    return "$freq Hz";
}

/**
 * Update current GET url with parameters
 * Will remove nulls
 * @param array $values values to add/set to GET. Adding a NULL removes already existing key!
 * @return string new url with GET parameters
 */
function updateGET(array $values): string
{
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) .'?'. http_build_query(array_filter(array_merge($_GET, $values), fn ($v) => !is_null($v)));
}
