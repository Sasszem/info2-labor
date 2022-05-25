<?php

namespace User;

class LoggedInUser
{
    /**
     * Session based login store so we avoid checking the database too often (and move the dependency to $_SESSION)
     * When a user logs in, we check the database, but then save the valid login with an expiration timestamp.
     * Every time we access the timestamp, we also update it, so every action resets the logout timer.
     *
     * We only store here the callsign of the user who is logged in, and the fact that it is a dev or not
     * the only caveats would be deleted user and the changing of the dev status in the middle of a login. Neither is a planned feature, so I'll pass
     */
    public const SESSION_LOGIN_CALLSIGN = 'user_logged_in_callsign';
    public const SESSION_LOGIN_DEV = 'user_logged_in_dev';
    public const SESSION_LOGIN_EXPIRE = 'user_login_expires';
    public const SESSION_LOGIN_THEME = 'user_logged_in_theme';
    public const LOGIN_VALID_SECONDS = 300; // 5 minutes


    /**
     * Check if login is still valid
     * and update it with 5 mins
     */
    protected static function checkAndUpdateTimestamp(): bool
    {
        if (!(array_key_exists(self::SESSION_LOGIN_EXPIRE, $_SESSION)&&array_key_exists(self::SESSION_LOGIN_CALLSIGN, $_SESSION))) {
            return false;
        }

        if ($_SESSION[self::SESSION_LOGIN_EXPIRE] < time()) {
            self::logout();
            return false;
        }

        $_SESSION[self::SESSION_LOGIN_EXPIRE] = time() + self::LOGIN_VALID_SECONDS;
        return true;
    }


    /**
     * Log the user out (who would have guessed?)
     */
    public static function logout()
    {
        unset($_SESSION[self::SESSION_LOGIN_EXPIRE]);
        unset($_SESSION[self::SESSION_LOGIN_CALLSIGN]);
        unset($_SESSION[self::SESSION_LOGIN_THEME]);
        unset($_SESSION[self::SESSION_LOGIN_DEV]);
    }

    /**
     * Check if any user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::checkAndUpdateTimestamp();
    }

    /**
     * Get callsign of the current user or null
     */
    public static function getCallsign(): ?string
    {
        if (self::isLoggedIn()) {
            return $_SESSION[self::SESSION_LOGIN_CALLSIGN];
        }
        return null;
    }

    /**
     * Get currently selected theme index (defaults to 0 if no user is logged in)
     */
    public static function getTheme(): int
    {
        if (self::isLoggedIn()) {
            return $_SESSION[self::SESSION_LOGIN_THEME];
        }
        return 0;
    }

    /**
     * Set theme. Only sets in SESSION, saving to database must be done in UserModel
     */
    public static function setTheme(int $theme)
    {
        if (self::isLoggedIn()) {
            $_SESSION[self::SESSION_LOGIN_THEME] = $theme;
        }
    }

    /**
     * Get if the current user has dev privilages
     */
    public static function isDev(): bool
    {
        return self::isLoggedIn() && $_SESSION[self::SESSION_LOGIN_DEV] === 'TRUE';
    }

    /**
     * Save login information in session. No validation at all, use UserModel to login safely!
     */
    public static function login(string $callsign, bool $dev, int $theme)
    {
        $_SESSION[self::SESSION_LOGIN_EXPIRE] = time() + self::LOGIN_VALID_SECONDS;
        $_SESSION[self::SESSION_LOGIN_CALLSIGN] = $callsign;
        $_SESSION[self::SESSION_LOGIN_THEME] = $theme;
        $_SESSION[self::SESSION_LOGIN_DEV] = $dev ? 'TRUE' : 'FALSE';
    }
}
