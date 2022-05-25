<?php

namespace QSO;

require_once 'lib.php';

/**
 * Controller for QSOs (listing, new QSO)
 */
class QSOController
{
    /**
     * Paginated fuzzy-search
     * Method: GET
     * Parameters:
     * - callsign: string
     * - mode: string
     * - startDT: datetime
     * - endDT: datetime
     * - startFreq: float
     * - endFreq: float
     * - page: int - default 0
     * Ommitted values will be wildcarded
     */
    public static function list(QSOListModel $model)
    {
        $defaults = array_fill_keys(['callsign', 'mode', 'startDT', 'endDT', 'startFreq', 'endFreq', 'page'], '');

        [
            'callsign' => $callsign,
            'mode' => $mode,
            'startDT' => $startDT,
            'endDT' => $endDT,
            'startFreq' => $startFreq,
            'endFreq' => $endFreq,
            'page' => $page,
        ] = $_GET + $defaults;

        $page = intval($_GET['page'] ?? 0);
        $startFreq = filter_var($startFreq, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        $endFreq = filter_var($endFreq, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

        $results = $model->list($callsign, $mode, $startDT, $endDT, $startFreq, $endFreq, $page);

        [
            'pages' => $pages,
            'res' => $result,
        ] = $results;

        return QSOListView::render($result, $page, $pages);
    }

    /**
     * Create new QSO
     * Method: POST
     * Parameters:
     * - callsign: string
     * - callsign2: string
     * - date: datetime
     * - freq: string
     * - mode: string
     * - report: int
     * - report2: int
     * - confirm: bool - used to confirm when a similar QSO already exists
     * - page: int
     */
    public static function newQSO(QSOModel $model)
    {
        $defaults = array_fill_keys(['callsign', 'callsign2', 'date', 'freq', 'report', 'report2', 'mode', 'confirm'], '');

        [
            'callsign' => $callsign_1,
            'callsign2' => $callsign_2,
            'date' => $datetime,
            'freq' => $freq_raw,
            'mode' => $mode,
            'report' => $report_1,
            'report2' => $report_2,
            'confirm' => $confirm,
        ] = $_POST + $defaults;


        // if something is missing
        if (in_array('', [$callsign_1, $callsign_2, $datetime, $freq_raw, $report_1, $report_2, $mode])) {
            redirect('/qso/new', 'Part of the request is missing!');
        }


        // some format / error checking
        $reg = '/([A-Za-z0-9]{1,3}\/)?([A-Za-z0-9]{1,3}[0-9][A-Za-z]{1,5})(\/[A-Za-z0-9]{1,3})?/i';
        $userCallsign = \User\LoggedInUser::getCallsign();

        $errors = '';
        if (preg_match($reg, $callsign_1)!=1) {
            $errors .= 'Your callsign is not valid\n';
        }
        if (preg_match($reg, $callsign_2)!=1) {
            $errors .= 'The callsign of the other HAM is not valid\n';
        }
        if (preg_match('/([0-9]\s?)+(\.([0-9]\s?))?\s?([kKmM])?([hH][zZ])?/i', $freq_raw) != 1) {
            $errors .= 'Invalid frequency!';
        }
        if (strpos($callsign_1, $userCallsign)===false) {
            $errors .= 'Your callsign must be yours!\n';
        }
        if ($errors != '') {
            redirect('/qso/new', $errors);
        }


        // check if a similar QSO already exists
        if ($model->similarQSO($callsign_1, $callsign_2, $datetime) && !$confirm) {
            return self::similarQSOConfirm($callsign_1, $callsign_2, $datetime, $freq_raw, $report_1, $report_2, $mode);
        }

        $freq = $model->parse_freq($freq_raw);
        $model->newQso($callsign_1, $callsign_2, $datetime, $mode, $freq, $report_1, $report_2);

        redirect('/qso/');
    }

    /**
     * List own qsos with fuzzy search on callsign
     * Method: GET
     * Parameters:
     * - callsign: string
     * - page: int
     */
    public static function ownQSOs(QSOListModel $qsoModel, \User\UserModel $userModel)
    {
        $defaults = array_fill_keys(['callsign', 'page'], '');

        [
            'callsign' => $callsign,
            'page' => $page,
        ] = $_GET + $defaults;

        $page = intval($_GET['page'] ?? 0);

        $results = $qsoModel->get_qsos_for_qsl($userModel->currentUser()['callsign'], $callsign, $page);

        [
            'pages' => $pages,
            'res' => $result,
        ] = $results;

        return OwnQSOList::render($result, $page, $pages);
    }

    /**
     * Simlar QSO confirmation form
     * Has all the info as before, just hidden
     * Ans a plus field called 'confirm'
     */
    protected static function similarQSOConfirm($callsign_1, $callsign_2, $datetime, $freq_raw, $report_1, $report_2, $mode)
    {
        return <<<END
        <form class="card mt-3" action="/qso/new" method="POST">
            <div class="card-header">
                New QSO confirmation
            </div>
            <div class="card-body">
                <p>
                    A similar QSO already exists. Do you want to continue?
                </p>

                <input type="hidden" name="callsign" value="$callsign_1">
                <input type="hidden" name="callsign2" value="$callsign_2">
                <input type="hidden" name="date" value="$datetime">
                <input type="hidden" name="freq" value="$freq_raw">
                <input type="hidden" name="mode" value="$mode">
                <input type="hidden" name="report" value="$report_1">
                <input type="hidden" name="report2" value="$report_2">
                <input type="hidden" name="confirm" value="true">
                <button type="submit" class="btn btn-primary">Continue</button>
                <a href="/qso/" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        END;
    }
}
