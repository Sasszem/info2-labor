<?php

/**
 * Giant class for main page layout
 */
class MainView
{
    /**
     * Get the currently active theme's URL
     */
    protected static function theme(): string
    {
        $urls = [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            'https://bootswatch.com/5/darkly/bootstrap.min.css',
            'https://bootswatch.com/5/morph/bootstrap.min.css',
            'https://bootswatch.com/5/quartz/bootstrap.min.css',
            'https://bootswatch.com/5/sketchy/bootstrap.min.css',
            'https://bootswatch.com/5/united/bootstrap.min.css',
            'https://bootswatch.com/5/vapor/bootstrap.min.css'
        ];
        return $urls[\User\LoggedInUser::getTheme()];
    }

    /**
     * Generate and error card or '' from the $_SESSION['error'] and clear the error
     */
    protected static function errorcard(): string
    {
        if (!array_key_exists('error', $_SESSION)) {
            return '';
        }
        $errors = $_SESSION['error'];
        unset($_SESSION['error']);
        return implode('', array_map(function ($error) {
            if ($error==='') {
                return;
            }
            return <<<END
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                $error
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            END;
        }, explode('\n', $errors)));
    }
    /**
     * Generate an message card for '' from the $_SESSION['message'] and clear the message
     */
    protected static function messageCard(): string
    {
        if (!array_key_exists('message', $_SESSION)) {
            return '';
        }
        $msgs = $_SESSION['message'];
        unset($_SESSION['message']);
        return implode('', array_map(function ($message) {
            if ($message==='') {
                return;
            }
            return <<<END
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                $message
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            END;
        }, explode('\n', $msgs)));
    }

    /**
     * User controls (login, logout, register) on the right side of the page
     * Gets dynamically generated based on login status
     */
    protected static function user_controls(): string
    {
        if (\User\LoggedInUser::isLoggedIn()) {
            return <<<END
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="btn btn-outline-danger" href="/user/logout">Logout</a>
                </li>
            </ul>
            END;
        } else {
            return <<<END
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="btn btn-outline-success" href="/user/login">Login</a>
                    <a class="btn btn-outline-secondary" href="/user/register">Register</a>
                </li>
            </ul>
            END;
        }
    }

    /**
     * Generate navigation links from a list
     */
    protected static function _navLinks(array $locations): string
    {
        // will need this for the locations
        // strip last / for compare
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $currentPath = substr($currentPath, -1)==='/' ? substr($currentPath, 0, -1) : $currentPath;

        // huge functional construct
        // creates simple and dropdown nav links and supports some basic guards like 'dev', 'user' and '!user'
        return implode('', array_map(function ($loc) use ($currentPath) {
            @[$txt, $url, $condition] = $loc;
            $condition = strtolower($condition ?? '');

            if ($condition === 'dev' && !\User\LoggedInUser::isDev()) {
                return '';
            }
            if ($condition === 'user' && !\User\LoggedInUser::isLoggedIn()) {
                return '';
            }
            if ($condition === '!user' && \User\LoggedInUser::isLoggedIn()) {
                return '';
            }

            if (is_string($url)) {
                // simple nav link

                $url_stripped = substr($url, -1)==='/' ? substr($url, 0, -1) : $url;
                $isSelected = $url_stripped === $currentPath;
                $active = $isSelected ? 'active' : '';
                $aria_active = $isSelected ? 'aria-current="page"' : '';


                return <<<END
                <li class="nav-item">
                    <a class="nav-link $active" $aria_active href="$url">$txt</a>
                </li>
                END;
            } elseif (is_array($url)) {
                // dropdown menu!
                $menuID = strtolower(explode(' ', $txt)[0]);

                $options = implode('', array_map(function ($desc) use ($currentPath) {
                    [$text, $link] = $desc;

                    $url_stripped = substr($link, -1)==='/' ? substr($link, 0, -1) : $link;
                    $isSelected = $url_stripped === $currentPath;
                    $active = $isSelected ? 'active' : '';
                    $aria_active = $isSelected ? 'aria-current="page"' : '';

                    return <<<END
                    <li><a class="dropdown-item $active" $aria_active href="$link">$text</a></li>
                    END;
                }, $url));


                $isSelected = strpos($options, 'active') !== false;
                $active = $isSelected ? 'active' : '';
                $aria_active = $isSelected ? 'aria-current="page"' : '';

                return <<<END
                <li class="nav-item dropdown" $aria_active>
                    <a class="nav-link dropdown-toggle $active" href="#" id="{$menuID}DropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        $txt
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="{$menuID}DropdownMenuLink">
                        $options
                    </ul>
                </li>
                END;
            }
        }, $locations));
    }

    /**
     * Render $stuff in the main view
     */
    public static function render(string $stuff)
    {
        if (!$stuff) {
            return;
        }

        $error_card = self::errorcard();
        $message_card = self::messageCard();
        $user_controls = self::user_controls();
        $styleUrl = self::theme();

        /**
         * Navbar setup
         */
        $locations = [
            ['HAMs', '/ham/'],
            ['QSOs', '/qso/', '!user'],
            [
                'QSOs',
                [
                    ['∀ All QSOs', '/qso/'],
                    ['🏠 Own QSOs', '/qso/own'],
                    ['➕ Add QSO', '/qso/new'],
                ],
                'user'
            ],
            ['QSLs', '/qsl/'],
            ['Profile', '/user/profile', 'user'],
            ['Dev', '/dev/', 'dev'],
        ];

        $navLinks = self::_navLinks($locations);

        /**
         * Tie everything together
         */
        echo <<<END
        <head>
        <link href="$styleUrl" rel="stylesheet" crossorigin="anonymous">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <a class="navbar-brand" href="/">Q6K</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav">
                            $navLinks
                        </ul>
                        $user_controls
                    </div>
                </div>
            </nav>
            <div class="container">
                $error_card
                $message_card
                $stuff
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
        </body>
        END;
    }
}
