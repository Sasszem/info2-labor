<?php

/**
 * Main entry point to the APP
 * start session, configure router, do routing and start dispatch
 */

require 'autoload.php';
require_once 'Router.php';

session_start();
$R = new \Routing\Router();

/**
 * Index route
 */
$R->get('^/$')->render('/static/index.html');

/**
 * Default (404) route. Can be configured to show debug info
 */
$R->default()->action(function ($uri) {
    http_response_code(404);

    if (!array_key_exists('DEBUG_MODE', $_ENV)) {
        return '<h1>404</h1>The requested location did not match any routes';
    }

    $ret = '';
    $ret .= <<<END
    <h1>404</h1>The requested location did not match any routes
    <table border=2>
        <tr>
            <th>VAR</th>
            <th>VAL</th>
        </tr>
    END;

    function row($name, $var): string
    {
        $exp = var_export($var, true);
        $ret = '';
        $ret .= '<tr>';
        $ret .= "<td>{$name}</td>";
        $ret .= "<td>{$exp}</td>";
        $ret .= '</tr>';
        return $ret;
    }
    $ret .= row('REQUEST_URI', $_SERVER['REQUEST_URI']);
    $ret .= row('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
    $ret .= row('GET PARAMS', $_GET);
    $ret .= row('POST PARAMS', $_POST);
    $ret .= row('Other post', urlencode(file_get_contents('php://input')));
    $ret .= row('DEBUG_MODE', $_ENV['DEBUG_MODE']);
    $ret .= '</table>';
    return $ret;
});



/**
 * User stuff - register, login, logout & profile
 */

$R->get('^/user/register/?$')->render('/static/user/UserRegister.html');
$R->post('^/user/register/?$')->controller(\User\UserController::class, 'register');

$R->get('^/user/login/?$')->render('/static/user/UserLogin.html');
$R->post('^/user/login/?$')->controller(\User\UserController::class, 'login');

$R->get('^/user/logout/?$')->controller(\User\UserController::class, 'logout')->requireLogin();

$R->get('^/user/profile/?$')->controller(\User\UserController::class, 'profile')->requireLogin();
$R->post('^/user/profile/?$')->controller(\User\UserController::class, 'updateProfile')->requireLogin();

/**
 * HAM listing
 */
$R->get('^/ham/?$')->controller(\HAM\HAMController::class, 'list');

/**
 * QSO listing & creation
 */
$R->get('^/qso/?$')->controller(\QSO\QSOController::class, 'list');

$R->get('^/qso/new/?$')->render('/static/qso/newqso.html')->requireLogin();
$R->post('^/qso/new/?$')->controller(\QSO\QSOController::class, 'newQSO')->requireLogin();
$R->get('^/qso/own/?$')->controller(\QSO\QSOController::class, 'ownQSOs')->requireLogin();

/**
 * QSL listing & creation
 */

$R->get('^/qsl/view/?$')->controller(\QSL\QSLController::class, 'view')->requireLogin();
$R->get('^/qsl/new/?$')->controller(\QSL\QSLController::class, 'newQSLForm')->requireLogin();
$R->post('^/qsl/new/?$')->controller(\QSL\QSLController::class, 'newQSL')->requireLogin();
$R->post('^/qsl/accept/?$')->controller(\QSL\QSLController::class, 'acceptQSL')->requireLogin();
$R->get('^/qsl/image/?$')->controller(\QSL\QSLController::class, 'getImage')->requireLogin();
$R->get('^/qsl((/inbox)|(/sent))?/?$')->controller(\QSL\QSLController::class, 'list')->requireLogin();

/**
 * Dev stuf. Database edit and seed.
 */

$R->get('^/dev/table/[a-zA-Z0-9_]+/?$')->controller(\DEV\DevController::class, 'table')->requireDev();
$R->get('^/dev/table/?$')->controller(\DEV\DevController::class, 'listTables')->requireDev();
$R->get('^/dev/?$')->controller(\DEV\DevController::class, 'listTables')->requireDev();
$R->get('^/dev/seed/?$')->render('/static/dev/seed.html')->requireDev();
$R->post('^/dev/seed/?$')->controller(\DEV\SeedController::class, 'seed')->requireDev();


/**
 * Start working
 */

$R->run(MainView::render(...));
