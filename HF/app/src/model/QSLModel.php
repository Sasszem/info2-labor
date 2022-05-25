<?php

namespace QSL;

/**
 * QSL model class. Uses table qsl
 */
class QSLModel extends \ModelBase
{
    public const PAGESIZE = 5;

    /**
     * Create new QSL in database.
     * @param int $sender sender of the QSL (user id)
     * @param int $recipient recipient of the QSL (user id)
     * @param string $image_file filename of the image sent with the QSL
     * @param int $qsoid ID of the QSO this QSL is associated with
     * @return ?string error string or null
     */
    public function newQSL(int $sender, int $recipient, string $image_file, int $qsoid): ?string
    {
        $q = $this->db->prepare('INSERT INTO qsl (sender, recipient, image_file, accepted, qsoid) VALUES (?, ?, ?, false, ?);');
        $q->bind_param('iisi', $sender, $recipient, $image_file, $qsoid);

        if ($q->execute()) {
            return null;
        } else {
            return $q->error;
        }
    }

    /**
     * Check if an user has already sent a QSL for a given QSO
     * @param int $qsoid ID of the QSO to check
     * @param int $senderid ID of the user to check
     * @return bool if the user has alredady sent a QSL
     */
    public function qsl_exists_for_qso(int $qsoid, int $senderid): bool
    {
        $q = $this->db->prepare('SELECT * FROM qsl WHERE qsoid = ? AND sender = ?;');
        $q->bind_param('ii', $qsoid, $senderid);
        if ($q->execute()) {
            return $q->get_result()->num_rows > 0;
        }
        return true;
    }

    /**
     * Get QSO by ID. Returns null if QSL does not exists
     *
     * @param int $qslId ID of the QSL to lookup
     * @return array database row of the QSO
     */
    public function get_qsl_by_id(int $qslId): ?array
    {
        $q = $this->db->prepare('SELECT * FROM qsl WHERE id = ?;');
        $q->bind_param('i', $qslId);
        if ($q->execute()) {
            return $q->get_result()->fetch_assoc();
        }
        return null;
    }

    /**
     * Get QSL by QSO ID and sender id
     *
     * @param int $qsoid QSO to lookup
     * @param int $sender id of HAM to lookup
     * @return ?array row from db or null
     */
    public function get_by_qso_sender(int $qsoid, int $sender): ?array
    {
        $q = $this->db->prepare('SELECT * FROM qsl WHERE qsoid = ? AND sender = ?;');
        $q->bind_param('ii', $qsoid, $sender);
        if ($q->execute()) {
            return $q->get_result()->fetch_assoc();
        }
        return null;
    }

    /**
     * Accept a QSL by a given user
     *
     * @param int $id id of the QSL to accept
     * @param int $userid id of the user who wants to accept the QSL
     *
     */
    public function accept(int $id, int $userid)
    {
        $q = $this->db->prepare('UPDATE qsl SET accepted = true WHERE id = ? AND recipient = ?;');
        $q->bind_param('ii', $id, $userid);
        $q->execute();
    }

    /**
     * List QSLs
     *
     * @param int $ham_id HAM to list QSLs of
     * @param bool $inbox true to list incoming, false to list outgoung QSLs
     * @param int $page display page
     */
    public function list(int $ham_id, bool $inbox, int $page): array
    {
        $offset = $page * self::PAGESIZE;
        $limit = self::PAGESIZE;

        $q =  $this->db->prepare('SELECT SQL_CALC_FOUND_ROWS l .*, h.callsign cs FROM qsl l INNER JOIN ham h ON IF(?, sender, recipient)=h.id WHERE IF(?, recipient, sender) = ? ORDER BY qsoid DESC LIMIT ? OFFSET ?;');
        $q->bind_param('iiiii', $inbox, $inbox, $ham_id, $limit, $offset);
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
