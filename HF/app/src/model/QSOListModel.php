<?php

namespace QSO;

/**
 * Handle QSOs as a list (pagination, search)
 */
class QSOListModel extends \ModelBase
{
    public const PAGESIZE = 20;

    /**
     * List QSOs with search. Omitted values will default to wildcards.
     * Returns ['res' => $result, 'pages' => $number_of_pages] or ['error'=>$error_string]
     */
    public function list(?string $callsign, ?string $mode, ?string $startDT, ?string $endDt, ?float $startFreq, ?float $endFreq, int $page)
    {
        // preapre searchs
        // if omitted, they'll become '%%' so match all
        // (except null, but that can only happen on special places and we added IFNULLs)
        $callsign = "%$callsign%";
        $mode = $mode ?: '%';

        // make 'SSB' match USB and LSB too
        $mode = $mode === 'SSB' ? 'SB' : $mode;
        $mode = "%$mode%";

        $startDT = $startDT ?: '1900-01-01 00:00:01';
        $endDt   = $endDt ?: '3000-01-01 00:00:01';
        $startFreq = $startFreq ?? 0.0;
        $endFreq = $endFreq ?? 3E12;

        // primitive offset-based pagination
        // this could be done faster, smarter, more effective
        // but I did not bother
        $offset = $page * self::PAGESIZE;
        $limit = self::PAGESIZE;

        $q =  $this->db->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM qso WHERE participants LIKE ? AND mode LIKE ? AND dtime >= ? AND dtime <= ? AND freq >= ? AND freq <= ? ORDER BY dtime desc LIMIT ? OFFSET ?;');
        $q->bind_param('ssssddii', $callsign, $mode, $startDT, $endDt, $startFreq, $endFreq, $limit, $offset);
        if ($q->execute()) {
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

    /**
     * Get QSOs made by an user with an optional search on the other side
     * Return all QSO data with 2 extra fields: list of already existing QSL ids and a bool indicating if we can make a QSL on this QSO
     *
     * @param string $userCallsign callsign of the user we search the QSOs of
     * @param string $searchedCallsign callsign to search for (* if set to '')
     * @param int $page page of the result to send
     * @return ?mysqli_result results with the two extra rows, null on failure
     */
    public function get_qsos_for_qsl(string $userCallsign, string $searchedCallsign, int $page): array
    {
        // huge query selects every QSO that is between $userCallsign and $searchedCallsign (wilcard if empty)
        // and also provides two columns: qsl_ids is the IDs of the existing QSLs concatenated with space separator and can_send_qsl is a bool
        // (true if other side is registered and no QSL already exists that is sent by us)
        // yeah i like doing stuff in database...


        $q = $this->db->prepare('SELECT SQL_CALC_FOUND_ROWS q.*, COUNT(l.id)>0 has_qsl, SUM(IF(l.recipient = h.id, 1, 0)) = 0 AND SUM(ISNULL(h.id)) = 0 can_send_qsl FROM qso q LEFT JOIN qsl l on l.qsoid = q.id LEFT JOIN ham h ON (h.callsign = q.ham_1_cs OR h.callsign = q.ham_2_cs) AND NOT h.callsign = ? WHERE q.participants LIKE ? AND q.participants LIKE ? GROUP BY q.id ORDER BY q.dtime DESC LIMIT ? OFFSET ?;');

        $userCallsignWildcard = "%$userCallsign%";
        $searchedCallsign = "%$searchedCallsign%";
        $offset = $page * self::PAGESIZE;
        $limit = self::PAGESIZE;

        $q->bind_param('sssii', $userCallsign, $userCallsignWildcard, $searchedCallsign, $limit, $offset);
        if ($q->execute()) {
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
