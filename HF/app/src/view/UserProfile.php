<?php

namespace User;

/**
 * View to render the user's profile page with the big user data update form
 * It's not a static file because it must have the old/current data when checking
 */
class UserProfile
{
    public static function render(UserModel $model): string
    {
        /**
         * get properties of current user from model
         */
        $user = $model->currentUser();
        $callsign = $user['callsign'];
        $email_visible_checked = $user['email_visible'] ? 'checked' : '';
        $email = $user['email'];
        $name = $user['uname'];
        $qth = $user['qth'];


        $themes = [
            [0,'Default',],
            [1,'Darkly',],
            [2,'Morph',],
            [3,'Quartz',],
            [4,'Sketchy',],
            [5,'United',],
            [6,'Vapor'],
        ];

        /**
         * Create options for dropdown based on $themes
         */
        $currentTheme = LoggedInUser::getTheme();
        $themeOptions = implode('\n', array_map(
            function ($r) use ($currentTheme) {
                $val = $r[0];
                $text = $r[1];
                $sel = $val === $currentTheme ? 'selected' : '';
                return "<option value=\"$val\" $sel>$text</option>";
            },
            $themes
        ));

        /**
         * Similar thing with exam levels
         */
        $examLevels = [
            ['-', 'Other/not speficied'],
            ['NNOVICE', '(National) novice'],
            ['CEPT NOVICE', 'CEPT Novice'],
            ['HAREC', 'HAREC'],
        ];
        $currentExamLevel = $user['exam_level'];
        $examLevelOptions = implode('\n', array_map(
            function ($r) use ($currentExamLevel) {
                $val = $r[0];
                $text = $r[1];
                $sel = $val === $currentExamLevel ? 'selected' : '';
                return "<option value=\"$val\" $sel>$text</option>";
            },
            $examLevels
        ));

        // setup switch to morse_exam
        $morse_exam_checked = $user['morse_exam'] ? 'checked' : '';


        // huge-ass inlined html
        // yeah rendering a form...
        return <<<END
        <form class="card mt-3" action="/user/profile/" method="POST">
            <div class="card-header">
                User $callsign
            </div>
            <div class="card-body">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">User profile</h5>
                        <p class="card-text">Basic settings for your user profile.</p>
                        <div class="mb-3">
                            <label for="callsignInput" class="form-label">Callsign</label>
                            <input type="text" class="form-control" id="callsignInput" aria-describedby="callsignHelp" name="callsign" value="$callsign" disabled>
                            <div id="callsignHelp" class="form-text">Your HAM callsign</div>
                        </div>
                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email</label>
                            <input type="email" class="form-control" id="emailInput" aria-describedby="emailHelp" name="email" value="$email">
                            <div id="emailHelp" class="form-text">Your E-mail address</div>
                        </div>
                        <div class="mb-3">
                            <label for="passwordInput" class="form-label">New password</label>
                            <input type="password" class="form-control" id="passwordInput" aria-describedby="passwordHelp" name="password">
                            <div id="passwordHelp" class="form-text">Set new password. <b>Leave blank to keep old password!</b></div>
                        </div>
                        <div class="mb-3">
                            <label for="themeInput" class="form-label">Selected theme</label>
                            <select class="form-select" id="themeInput" name="theme">
                                $themeOptions
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Optional extra info</h5>
                        <p class="card-text">Filling this out is optional, but all of <b>it will be public.</b></p>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" value="yes" id="emailVisibleInput" name="emailVisible" $email_visible_checked>
                            <label class="form-check-label" for="emailVisibleInput">
                                Make email public on HAMs list
                            </label>
                        </div>
                        <div class="mb-3">
                            <label for="nameInput" class="form-label">Name</label>
                            <input type="text" class="form-control" id="nameInput" name="name" value="$name">
                        </div>
                        <div class="mb-3">
                            <label for="qthInput" class="form-label">QTH</label>
                            <input type="text" class="form-control" id="qthInput" name="qth" value="$qth">
                        </div>
                        <div class="mb-3">
                            <label for="examLevelInput" class="form-label">Exam level</label>
                            <select class="form-select" id="examLevelInput" name="examLevel">
                                $examLevelOptions
                            </select>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" value="YES" id="morseExamInput" name="morseExam" $morse_exam_checked>
                            <label class="form-check-label" for="morseExamInput">
                                Have morse exam?
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Update profile</button>
            </div>
        </form>
        END;
    }
}
