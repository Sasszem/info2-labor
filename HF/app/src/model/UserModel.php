<?php

namespace User;

/**
 * User model for user-related actions
 * Uses the ham table
 */
class UserModel extends \ModelBase
{
    /**
     * Check if there is a user with a given callsign
     * @param string $callsign callsign to check
     * @return bool if the callsign is already in the db
     */
    public function _callsign_exists(string $callsign): bool
    {
        $q = $this->db->prepare('SELECT id FROM ham WHERE callsign = ?;');
        $q->bind_param('s', $callsign);
        $q->execute();
        return ($q->get_result()->num_rows > 0);
    }

    /**
     * Get user from db by callsign
     * @param string $callsign to look up
     * @return ?array database row or null
     */
    protected function _get_user_by_callsign(string $callsign): ?array
    {
        $q = $this->db->prepare('SELECT * FROM ham WHERE callsign = ?;');
        $q->bind_param('s', $callsign);
        $q->execute();
        $r = $q->get_result();
        if ($r->num_rows > 0) {
            return $r->fetch_assoc();
        }
        return null;
    }

    /**
     * validate login
     * @param string $callsign callsign of the user trying to sign in
     * @param string $password password of the user trying to sign in
     * @return bool if the login is valid
     */
    public function is_login_valid(string $callsign, string $password): bool
    {
        $user = $this->_get_user_by_callsign($callsign);
        if (is_null($user)) {
            return false;
        }
        return password_verify($password, $user['passwd']);
    }

    /**
     * Log in (if credentials are valid)
     */
    public function login(string $callsign, string $password)
    {
        if (!$this->is_login_valid($callsign, $password)) {
            return;
        }

        $user = $this->_get_user_by_callsign($callsign);
        LoggedInUser::login($callsign, $user['dev'], $user['theme']);
    }

    /**
     * Log out
     */
    public function logout()
    {
        LoggedInUser::logout();
    }

    /**
     * Register user. This method does not do too mutch validation, that is the responsibility of the controller.
     * Returns error or null
     */
    public function register(string $callsign, string $password, string $email): ?string
    {
        // we assume the controller have checked everything
        // but we also return any mysql errors we had
        $q = $this->db->prepare('INSERT INTO ham (email, passwd, callsign) VALUES (?, ?, ?);');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $q->bind_param('sss', $email, $hash, $callsign);
        if ($q->execute()) {
            return null;
        } else {
            return $q->error;
        }
    }

    /**
     * Update user profile. No checks, it's the controllers responsibility
     */
    public function update(int $id, string $email, ?string $password, bool $emailVisible, string $name, string $qth, string $examLevel, bool $morseExam, int $theme)
    {
        $pd = $password ? password_hash($password, PASSWORD_DEFAULT) : $this->currentUser()['passwd'];

        $q = $this->db->prepare('UPDATE ham SET email = ?, passwd = ?, uname = ?, qth = ?, exam_level = ?, morse_exam = ?, email_visible = ?, theme = ? WHERE id = ?;');
        $q->bind_param('sssssiiii', $email, $pd, $name, $qth, $examLevel, $morseExam, $emailVisible, $theme, $id);
        $q->execute();
    }


    /**
     * Get the current user's DB row (if logged in)
     */
    public function currentUser(): ?array
    {
        if (!LoggedInUser::isLoggedIn()) {
            return null;
        }
        return $this->_get_user_by_callsign(LoggedInUser::getCallsign());
    }
}
