<?php

namespace HAM;

/**
 * List model for HAMs with search and pagination
 */
class HAMList extends \ModelBase
{
    public const PAGESIZE = 20;

    /**
     * List HAMs and return specific page. Nulled / empty parameters will get replaced with wildcards.
     * Returns ['res' => $result, 'pages' => $number_of_pages] or ['error'=>$error_string]
     */
    public function list(?string $callsign, ?string $email, ?string $name, ?string $qth, ?string $exam, ?string $morseExam, int $page): array
    {
        // preapre searchs
        // if omitted, they'll become '%%' so match all
        // (except null, but that can only happen on special places and we added IFNULLs)
        $callsign = "%$callsign%";
        $email = "%$email%";
        $name = "%$name%";
        $qth = "%$qth%";
        $exam = $exam ?: '%';

        // condition morse exam
        // the sql query has an additional parameter that disables searching by that
        // which should be set if it's not a valid bool value
        // problem is, empty string, coming from omitted search IS a valid bool (false)
        // so in that case we just update it to something that is not
        $morseExam = $morseExam === '' ? 'NOT_VALID_BOOL' : $morseExam;
        $searchMorseExam = !is_null(filter_var($morseExam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
        $morseExam = filter_var($morseExam, FILTER_VALIDATE_BOOLEAN);

        // primitive offset-based pagination
        // this could be done faster, smarter, more effective
        // but I did not bother
        $offset = $page * self::PAGESIZE;
        $limit = self::PAGESIZE;

        $q =  $this->db->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM ham WHERE callsign LIKE ? AND IF(email_visible, email, \'\') LIKE ? AND IFNULL(uname,\'\') LIKE ? AND IFNULL(qth, \'\') LIKE ? AND exam_level LIKE ? AND IF(?, morse_exam = ?, true) ORDER BY callsign LIMIT ? OFFSET ?;');
        $q->bind_param('sssssiiii', $callsign, $email, $name, $qth, $exam, $searchMorseExam, $morseExam, $limit, $offset);
        if ($q->execute()) {
            /**
             * SQL_CALC_FOUND_ROWS and FOUND_ROWS enables to only query the big table once
             * it won't be that faster (still have to query it all), but it's still a bit better, and we also avoid code duplocation
             */
            $result = $q->get_result();
            $total = $this->db->query('SELECT FOUND_ROWS();')->fetch_row()[0];
            $pages = ceil($total / self::PAGESIZE);
            return [
                'res' => $result,
                'pages' => $pages,
            ];
        } else {
            return [
                'error' => $q->error
            ];
        }
    }
}

/*
Better pagination logic that might be implemented later

$firstCS - given by user or ''
we provide data and two new $firstCS for next an prev page
this way we don't use offset & limit so it faster on the DB side
but this also means we can't go to a random page (essentially we have a linked list)

$DATA = SELECT ... FROM ... WHERE ... AND callsign >= $firstCS ORDER BY callsign LIMIT $PAGESIZE;
$NEXTFIRST = SELECT ... FROM ... WHERE ... AND callsign >= $firstCS ORDER BY callsign LIMIT 1 OFFSET $PAGESIZE;
$PREVFIRST = SELECT ... FROM ... WHERE ... AND callsign <= $firstCS ORDER BY callsign DESC LIMIT 1 OFFSET $PAGESIZE;

$DATA = $BASEQ AND callsign >= $firstCS ORDER BY callsign LIMIT $PAGESIZE;
$NEXTFIRST = $BASEQ AND callsign >= $firstCS ORDER BY callsign LIMIT 1 OFFSET $PAGESIZE;
$PREVFIRST = $BASEQ AND callsign <= $firstCS ORDER BY callsign DESC LIMIT 1 OFFSET $PAGESIZE;
*/
