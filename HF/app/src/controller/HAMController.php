<?php

namespace HAM;

/**
 * HAM listing and fuzzy search controller
 */
class HAMController
{
    /**
     * Method: POST
     * Parameters:
     * - callsign: string
     * - email: string
     * - name: string
     * - qth: string
     * - exam: string
     * - morseExam: bool
     * - page: int
     */
    public static function list(HAMList $model)
    {
        $defaults = array_fill_keys(['callsign', 'email', 'name', 'qth', 'exam', 'morseExam', 'page'], '');

        [
            'callsign' => $callsign,
            'email' => $email,
            'name' => $name,
            'qth' => $qth,
            'exam' => $exam,
            'morseExam' => $morseExam,
            'page' => $page,
        ] = $_GET + $defaults;

        $page = intval($_GET['page'] ?? 0);

        $results = $model->list($callsign, $email, $name, $qth, $exam, $morseExam, $page);

        [
            'pages' => $pages,
            'res' => $result,
        ] = $results;
        return HAMListView::render($result, $page, $pages);
    }
}
