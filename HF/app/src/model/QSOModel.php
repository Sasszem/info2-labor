<?php

namespace QSO;

/**
 * QSO model for handling single QSOs (for lists there's QSOListModel)
 */
class QSOModel extends \ModelBase
{
    /**
     * Create a QSO with required data. Returns error string or null
     */
    public function newQso(string $callsign_1, string $callsign_2, string $datetime, string $mode, float $freq, int $report_1, int $report_2): ?string
    {
        $q = $this->db->prepare('INSERT INTO qso (ham_1, ham_2, dtime, mode, freq, report_1, report_2) VALUES (?, ?, ?, ?, ?, ?, ?);');
        $q->bind_param('ssssdii', $callsign_1, $callsign_2, $datetime, $mode, $freq, $report_1, $report_2);

        if ($q->execute()) {
            return null;
        } else {
            return $q->error;
        }
    }

    /**
     * Check if a similar qso (between the same two parties) exists within a given timeframe
     */
    public function similarQSO(string $callsign_1, string $callsign_2, string $datetime): bool
    {
        $maxPrevDate = date_add(
            new \DateTime($datetime),
            \DateInterval::createFromDateString('-10 minutes')
        )->format('Y-m-d H:i:s');

        $res = $datetime;

        preg_match('/([A-Za-z0-9]{1,3}\/)?([A-Za-z0-9]{1,3}[0-9][A-Za-z]{1,5})(\/[A-Za-z0-9]{1,3})?/', $callsign_1, $matches_1);
        $callsign_1 = $matches_1[2];

        preg_match('/([A-Za-z0-9]{1,3}\/)?([A-Za-z0-9]{1,3}[0-9][A-Za-z]{1,5})(\/[A-Za-z0-9]{1,3})?/', $callsign_2, $matches_2);
        $callsign_2 = $matches_2[2];

        $callsign_1 = "%$callsign_1%";
        $callsign_2 = "%$callsign_2%";

        $q = $this->db->prepare('SELECT * FROM qso WHERE participants LIKE ? AND participants LIKE ? AND dtime >= ? and dtime <= ?;');
        $q->bind_param('ssss', $callsign_1, $callsign_2, $maxPrevDate, $datetime);
        if ($q->execute()) {
            return $q->get_result()->num_rows > 0;
        }
        return false;
    }

    /**
     * Get IDs of users who participated in a given QSO
     * @param int $qsoid id of the qso to check
     * @return array IDs of the users who participated in that QSO. Can be 0, 1 or 2 in length
     */
    public function getParticipants(int $qsoid): array
    {
        $q = $this->db->prepare('SELECT h.id  FROM ham h INNER JOIN qso q ON q.ham_1_cs = h.callsign OR q.ham_2_cs = h.callsign WHERE q.id = ?;');
        $q->bind_param('i', $qsoid);

        if (!$q->execute()) {
            return [];
        } else {
            $rows = $q->get_result()->fetch_all();
            return array_map(fn ($r) => $r[0], $rows);
        }
    }

    /**
     * Get all QSO details by ID
     * @param int $qsoid ID to look up
     * @return array row or null
     */
    public function get_by_id(int $qsoid): ?array
    {
        $q = $this->db->prepare('SELECT * FROM qso WHERE id = ?;');
        $q->bind_param('i', $qsoid);

        if (!$q->execute()) {
            return null;
        } else {
            return $q->get_result()->fetch_assoc();
        }
    }

    /**
     * Parse a frequency string with SI prefixes and Hz unit
     * "14.318MHz"->14318000
     * Hz is optional, everything is case-insensitive
     */
    public static function parse_freq(string $freq_raw): float
    {
        $freq_raw = strtolower($freq_raw);
        $freq_raw = str_replace('hz', '', $freq_raw);
        $freq_raw = str_replace(' ', '', $freq_raw);

        if (substr($freq_raw, -1) === 'k') {
            return floatval(substr($freq_raw, 0, -1)) * 1000;
        }
        if (substr($freq_raw, -1) === 'm') {
            return floatval(substr($freq_raw, 0, -1)) * 1000000;
        }
        return floatval($freq_raw);
    }
}
