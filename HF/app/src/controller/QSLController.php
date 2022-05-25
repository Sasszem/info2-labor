<?php

namespace QSL;

require_once 'lib.php';


/**
 * Check if an uploaded file is a valid image with
 * Checks file size and MIME type
 * @param array $F file to check (entry from $_FILES table)
 * @return string errors or ''
 */
function check_valid_uploaded_image(array $F): string
{
    if ($F['size'] > 60000) {
        return 'Image file is too big!';
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mimetype = $finfo->file($F['tmp_name']);
    if (!str_starts_with($mimetype, 'image')) {
        return 'Uploaded file is not an image!';
    }

    return '';
}

/**
 * return filesystem extension for an uploaded image file $F (entry from $_FILES)
 * Works by getting MIME type then looking it up.
 * Supports common image types from https://developer.mozilla.org/en-US/docs/web/http/basics_of_http/mime_types/common_types
 *
 * @param array $F file to get extension of (entry from $_FILES table)
 * @return string file extension
 */
function get_image_extension(array $F): string
{
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mimetype = $finfo->file($F['tmp_name']);

    return [
        'image/avif'                    => 'avif',
        'image/bmp'                     => 'bmp',
        'image/gif'                     => 'gif',
        'image/vnd.microsoft.icon'      => 'ico',
        'image/jpeg'                    => 'jpeg',
        'image/png'                     => 'png',
        'image/svg+xml'                 => 'svg',
        'image/tiff'                    => 'tiff',
        'image/webp'                    => 'webp',
    ][$mimetype];
}

/**
 * QSL controller
 * Send and receive QSLs
 */
class QSLController
{
    // defaulf for $_ENV['uploads_dir']
    protected const UPLOADS_DIR_DEFAULT = '/images/';

    /**
     * Entrypoint for adding a QSL
     *
     * Method: POST
     * Parameters:
     * - qsoid: int - id of the QSO to add QSL to
     * - imageFile: upload file - image to send with the QSO
     *
     * Creates a new QSL if a valid image is provided, both parties of the QSO are registered (and one of them is the user sending this) and it's not a duplicate QSL
     */
    public static function newQSL(QSLModel $qslModel, \QSO\QSOModel $qsoModel, \User\UserModel $userModel): ?string
    {
        $qsoid = filter_var($_POST['qsoid'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if (is_null($qsoid) || !array_key_exists('imageFile', $_FILES)) {
            // if something is missing
            redirect('/qsl/new', 'Part of the request is missing!');
        }

        $F = $_FILES['imageFile'];

        if ($error = check_valid_uploaded_image($F)) {
            redirect('/qsl/new', $error);
        }

        $userid = $userModel->currentUser()['id'];
        // check if no QSL exists for this QSO
        if ($qslModel->qsl_exists_for_qso($qsoid, $userid)) {
            redirect('/qsl/new', 'You have already sent a QSL for this QSO!');
        }

        $participants = $qsoModel->getParticipants($qsoid);

        // can't make QSL for a QSO we did not even make
        if (!in_array($userid, $participants)) {
            redirect('/qsl/new', 'Invalid QSO ID!');
        }

        // can only send QSLs to other registered HAMs in the database
        if (count($participants) < 2) {
            redirect('/qsl/new', 'Other party is not registered, can not send QSL!');
        }

        $recipient = array_values(array_filter($participants, fn ($uid) => $uid != $userid))[0];

        // upload file
        $extension = get_image_extension($F);
        // name should be something unique, so we do this:
        $filename = "qsl_{$qsoid}_{$userid}.{$extension}";
        $uploads_dir = ($_ENV['uploads_dir'] ?? self::UPLOADS_DIR_DEFAULT);
        move_uploaded_file($F['tmp_name'], "$uploads_dir/$filename");

        $qslModel->newQSL($userid, $recipient, $filename, $qsoid);

        redirect('/qso/own');
    }

    /**
     * Entrypoint for view QSL
     *
     * Method: GET
     * Parameters:
     * - qsoid: int - id of the QSO to view
     *
     * Might be suprising that we use a QSO id to view in QSL, but as we display every QSL associated with a QSO it's only logical
     */
    public static function view(QSLModel $qslModel, \User\UserModel $userModel, \QSO\QSOModel $qsoModel): ?string
    {
        $qsoid = filter_var($_GET['qso'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if (is_null($qsoid)) {
            redirect('/qso/own');
        }

        $userid = $userModel->currentUser()['id'];

        $participants = $qsoModel->getParticipants($qsoid);
        if (!in_array($userid, $participants)) {
            redirect('/qso/own');
        }

        $other_party = array_values(array_filter($participants, fn ($id) => $id != $userid))[0] ?? -1;

        if (!($qso = $qsoModel->get_by_id($qsoid))) {
            redirect('/qso/own');
        }

        $qsl_sent = $qslModel->get_by_qso_sender($qsoid, $userid);
        $qsl_received = $qslModel->get_by_qso_sender($qsoid, $other_party);

        return QSLView::render($qso, $qsl_sent, $qsl_received);
    }

    /**
     * Entrypoint for new QSL form
     *
     * Method: GET
     * Parameters:
     * - qsoid: int - id of the QSO to send QSL with
     *
     */
    public static function newQSLForm(\QSO\QSOModel $qsoModel, \User\UserModel $userModel, QSLModel $qslModel)
    {
        $qsoid = filter_var($_GET['qsoid'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if (is_null($qsoid)) {
            redirect('/qso/own');
        }

        $user = $userModel->currentUser();
        $userid = $user['id'];
        // check if no QSL exists for this QSO
        if ($qslModel->qsl_exists_for_qso($qsoid, $userid)) {
            redirect('/qso/own', 'You have already sent a QSL for this QSO!');
        }

        $participants = $qsoModel->getParticipants($qsoid);

        // can't make QSL for a QSO we did not even make
        if (!in_array($userid, $participants)) {
            redirect('/qso/own');
        }

        // can only send QSLs to other registered HAMs in the database
        if (count($participants) < 2) {
            redirect('/qso/own', 'Other party is not registered, can not send QSL!');
        }

        if (!($qso = $qsoModel->get_by_id($qsoid))) {
            redirect('/qso/own');
        }

        $user_callsign = $user['callsign'];
        $callsign = $qso['ham_1_cs']===$user_callsign ? $qso['ham_2'] : $qso['ham_1'];
        $date = $qso['dtime'];

        $form = file_get_contents('static/qsl/sendQSL.html');
        $form = str_replace('%CALLSIGN%', "$callsign", $form);
        $form = str_replace('%DATE%', "$date", $form);
        $form = str_replace('%QSOID%', "$qsoid", $form);
        return $form;
    }

    /**
     * Entrypoint for getting image for QSL.
     *
     * Method: GET
     * Parameters:
     * - id: int - id of the QSL to get image
     *
     * Only sends image if id is valid and the QSL belongs to the logged in user
     */
    public static function getImage(QSLModel $qslModel, \User\UserModel $userModel, \QSO\QSOModel $qsoModel): ?string
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if (is_null($id)) {
            return '';
        }

        // check if ID is valid
        if (!($qsl = $qslModel->get_qsl_by_id($id))) {
            return '';
        }

        // only the participants can see the image
        $participants = $qsoModel->getParticipants($qsl['qsoid']);
        if (!in_array(($userModel->currentUser() ?? ['id' => -1])['id'], $participants)) {
            return '';
        }

        // send the file specified by row
        $fname = $qsl['image_file'];
        $uploads_dir = $_ENV['uploads_dir'] ?? self::UPLOADS_DIR_DEFAULT;
        $filepath = "$uploads_dir/$fname";
        $ct = mime_content_type($filepath);
        header("Content-type: $ct");
        readfile($filepath);
        return null;
    }

    /**
     * Entry point for QSL accept
     *
     * Method: POST
     * Parameters:
     * - id: int - id of the QSL to accept
     *
     * Only accepts if it is sent to the current user.
     */
    public static function acceptQSL(QSLModel $qslModel, \User\UserModel $userModel)
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if (is_null($id)) {
            redirect('/qso/own');
        }

        $userid = $userModel->currentUser()['id'];
        $qslModel->accept($id, $userid);
        redirect("/qsl/");
    }

    /**
     * QSL LIST entrypoint
     * Listens for /qsl, /qsl/inbox and /qsl/sent
     * Serves static page for first, and generated list on others
     */
    public static function list(QSLModel $qslModel, \User\UserModel $userModel): ?string
    {
        $url = $_SERVER['REQUEST_URI'];
        preg_match('/^\/qsl((\/inbox)|(\/sent))?\/?$/i', $url, $matches);

        $sub = $matches[1] ?? '';
        if ($sub === '') {
            return file_get_contents('static/qsl/qsl.html');
        } else {
            $page = filter_var($_POST['page'] ?? null, FILTER_VALIDATE_INT);

            $userid = $userModel->currentUser()['id'];
            $results = $qslModel->list($userid, $sub === '/inbox', $page);

            [
                'pages' => $pages,
                'res' => $result,
            ] = $results;

            return QSLList::render($result, $page, $pages);
        }
    }
}
