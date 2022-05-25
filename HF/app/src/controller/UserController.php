<?php

namespace User;

require_once 'lib.php';

/**
 * Controller for user actions (register, login, logout, profile update). Does server-side validation.
 */
class UserController
{
    /**
     * Check if a callsign is valid
     */
    protected static function _is_valid_callsign(string $callsign): bool
    {
        $reg = '/[A-Za-z0-9]{1,3}[0-9][A-Za-z]{1,5}/i';
        return \preg_match($reg, $callsign)==1;
    }

    /**
     * Check if an email is valid
     */
    protected static function _is_valid_email(string $email): bool
    {
        return \filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate new user, return errors as multi-line string or empty string if everything is ok
     */
    protected static function _validate_new_user(UserModel $model, string $callsign, string $password, string $email): ?string
    {
        $errors = '';
        if (!self::_is_valid_callsign($callsign)) {
            $errors .= 'Invalid callsign!\n';
        }
        if (!self::_is_valid_email($email)) {
            $errors .= 'Invalid email!\n';
        }
        if ($model->_callsign_exists($callsign)) {
            $errors .= "User with callsign $callsign already has an account!\n";
        }
        return $errors;
    }

    /**
     * User registration entry point
     *
     * Method: POST
     * Parameters:
     * - callsign: string
     * - password: string
     * - email: string
     */
    public static function register(UserModel $model)
    {
        $callsign = $_POST['callsign'] ?? null;
        $password = $_POST['password'] ?? null;
        $email = $_POST['email'] ?? null;

        if (is_null($callsign) || is_null($password) || is_null($email)) {
            redirect('/user/register', 'Part of the request was missing');
        }

        $errors = self::_validate_new_user($model, $callsign, $password, $email);
        if ($errors !== '') {
            redirect('/user/register', $errors);
        } else {
            $errors = $model->register($callsign, $password, $email);
            if (!is_null($errors)) {
                redirect('/user/register', $errors);
            } else {
                $model->login($callsign, $password);
                redirect('/user/profile');
            }
        }
    }

    /**
     * Validate login and log in if ok
     *
     * Method: POST
     * Parameters:
     * - callsign: string
     * - password: string
     *
     */
    public static function login(UserModel $model)
    {
        $password = $_POST['password'] ?? null;
        $callsign = $_POST['callsign'] ?? null;

        if (is_null($callsign) || is_null($password)) {
            redirect('/user/login', 'Part of the request was missing');
        }

        if ($model->is_login_valid($callsign, $password)) {
            $model->login($callsign, $password);
            redirect('/user/profile');
        } else {
            redirect('/user/login', 'Invalid callsign and/or password!');
        }
    }

    /**
     * Method: POST
     * Parameters:
     * none
     */
    public static function logout(UserModel $model)
    {
        $model->logout();
        redirect('/');
    }

    /**
     * Render user progile
     *
     * Method: GET
     * Parameters:
     * none, user must be logged in
     */
    public static function profile(UserModel $model)
    {
        return \User\UserProfile::render($model);
    }

    /**
     * Update profile
     * Method: POST
     * Parameters:
     * - email: string
     * - password: string
     * - emailVisible: bool
     * - name: string
     * - qth: string
     * - examLevel: string (enum)
     * - morseExam: bool
     * - theme: int
     */
    public static function updateProfile(UserModel $model)
    {
        $defaults = array_fill_keys(
            ['email','password','emailVisible','name','qth','examLevel','morseExam', 'theme'],
            null
        );
        [
            'email' => $email,
            'password' => $password,
            'emailVisible' => $emailVisible,
            'name' => $name,
            'qth' => $qth,
            'examLevel' => $examLevel,
            'morseExam' => $morseExam,
            'theme' => $selectedTheme
        ] = $_POST + $defaults;

        $selectedTheme = $selectedTheme ?: 0;

        // only need to validate email and exam level
        // bools should be default-to-no
        $errors = '';
        if (!self::_is_valid_email($email)) {
            $errors .= 'Invalid email address!';
        }

        if (!in_array($examLevel, ['NNOVICE', 'CEPT NOVICE', 'HAREC', '-'])) {
            $errors .= 'Invalid exam level!';
        }
        if ($selectedTheme < 0 || $selectedTheme > 6) {
            $errors .= 'Invalid theme!';
        }

        if ($errors==='') {
            $id = $model->currentUser()['id'];
            $emailVisible = filter_var($emailVisible, FILTER_VALIDATE_BOOLEAN);
            $morseExam = filter_var($morseExam, FILTER_VALIDATE_BOOLEAN);
            $model->update($id, $email, $password, $emailVisible, $name, $qth, $examLevel, $morseExam, $selectedTheme);
            LoggedInUser::setTheme($selectedTheme);
            redirect('/user/profile');
        } else {
            redirect('/user/profile', $errors);
        }
    }
}
