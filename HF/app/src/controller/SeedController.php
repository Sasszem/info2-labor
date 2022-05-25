<?php

namespace DEV;

require_once 'lib.php';

// we won't only be using models for db manipulation
require_once 'db.php';


/**
 * random capital letter
 */
function random_char()
{
    $chars = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    shuffle($chars);
    return $chars[0];
}

/**
 * Generate random user as array
 */
function gen_random_user()
{
    $suffix_len = rand(2, 4);
    $suffix = '';
    for ($j = 0; $j < $suffix_len; $j++) {
        $suffix .= random_char();
    }

    $regionCode = rand(0, 9);

    $callsign = "GEN$regionCode$suffix";
    $email = 'TEST_EMAIL@Q6K.dev';
    $password = 'GENERATED';

    $morseExam = (rand(0, 1) === 1);
    $examLevel = ['NNOVICE', 'CEPT NOVICE', 'HAREC', '-'][rand(0, 3)];

    return [
        'callsign' => $callsign,
        'email' => $email,
        'password' => $password,
        'examLevel' => $examLevel,
        'morseExam' => $morseExam,
    ];
}

/**
 * Generate random decorated(prefix, suffix) callsign, base random chosen from $options
 */
function randomCSWithSuffix(array $options): string
{
    $base = $options[rand(0, count($options)-1)];

    if (rand(0, 1) === 1) {
        $base = $base . '/' . random_char() . random_char();
    }

    if (rand(0, 1) === 1) {
        $base = random_char() . random_char() . '/' . $base;
    }

    return $base;
}

/**
 * Controller for seeding data for DEV purposes
 */
class SeedController
{
    /**
     * Seed $count new users to $db
     */
    protected static function _seedUsers($db, int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        $model = new \User\UserModel($db);

        for ($i = 0; $i < $count; $i++) {
            [
                'callsign' => $callsign,
                'email' => $email,
                'password' => $password,
                'examLevel' => $examLevel,
                'morseExam' => $morseExam,
            ] = gen_random_user();

            if ($model->_callsign_exists($callsign)) {
                $i++;
            } else {
                $errors = $model->register($callsign, $password, $email);
                // update their profile with some info
                // in a framework, this would be done via an ORM model class
                // but I'm already in a hurry with this project so this will do for now

                $emailVis = 1;
                $q = $db->prepare('UPDATE ham SET exam_level = ?, morse_exam = ?, email_visible = ? WHERE callsign = ?;');
                $q->bind_param('siis', $examLevel, $morseExam, $emailVis, $callsign);
                $q->execute();
            }
        }
        return $count;
    }

    /**
     * seed $count new QSOs to $db
     */
    protected static function _seedQSOs($db, int $count): int
    {
        $model = new \QSO\QSOModel($db);

        $existing_callsigns = array_map(fn ($row) => $row['callsign'], $db->query('SELECT callsign FROM ham;')->fetch_all(MYSQLI_ASSOC));
        $new_callsigns = array_map(fn ($_) => gen_random_user()['callsign'], array_fill(0, count($existing_callsigns), 1));
        $existing_and_new_callsigns = array_merge($existing_callsigns, $new_callsigns);

        for ($i = 0; $i < $count; $i++) {
            $dtime = date("Y-m-d H:i:s", rand(time() - 356*24*3600, time()));

            $callsign_1 = randomCSWithSuffix($existing_callsigns);
            $callsign_2 = randomCSWithSuffix($existing_and_new_callsigns);

            $mode = ['CW', 'DSB', 'LSB', 'USB', 'NFM', 'RTTY'][rand(0, 5)];
            $freq = rand(1000000, 500000000);

            $model->newQso($callsign_1, $callsign_2, $dtime, $mode, $freq, 12, 34);
        }


        return $count;
    }

    protected static function _getMessage($users, $qsos): string
    {
        return "Seeded $users new users and $qsos new QSOs!";
    }

    /**
     * Method: POST
     * Parameters:
     * - users: int
     * - qsos: int
     */
    public static function seed()
    {
        $defaults = array_fill_keys(['users', 'qsos'], 0);
        [
            'users' => $users,
            'qsos' => $qsos,
        ] = $_POST + $defaults;

        // we are bypassing models for db access
        $db = \db\getConnection();
        $users = self::_seedUsers($db, intval($users));
        $qsos = self::_seedQSOs($db, intval($qsos));
        redirect('/dev/seed', null, self::_getMessage($users, $qsos));
    }
}
