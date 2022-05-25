<?php

namespace DEV;

require_once 'lib.php';

/**
 * DEV - Direct and Extremely Violent DB manipulation (TM)
 * aka last minute hack to edit all & delete all
 */
class DevController
{
    /**
     * Method: GET
     * Parameters:
     * - table: string - in URL, as /dev/table/ham -> $table = 'ham'
     * - page: int
     * - search_* - parameter from some form. Might be used for search or for update
     * - delete: int - if exists, will delete entry with this id
     * - update: int - if exists and 'save' is set, values from search_* will be used to update row with this id. If exists, but 'save' is not set then an edit form is sent back
     * - save - see update
     *
     * Yeah this does too mutch in itself
     */
    public static function table(DevModel $model)
    {
        preg_match('/^\/dev\/table\/([A-Za-z0-9_]+)\/?/i', $_SERVER['REQUEST_URI'], $matches);

        $table = $matches[1] ?? null;
        if (is_null($table)) {
            redirect('/dev/table/');
        }
        $page = intval($_GET['page'] ?? 0);

        /**
         * Deletion logic
         */
        $delete_id = intval($_GET['delete'] ?? -1);
        if ($delete_id > 0) {
            if ($error = $model->delete($table, $delete_id)) {
                redirect(updateGET(['delete' => null]), $error);
            } else {
                redirect(updateGET(['delete' => null]), null, 'Delete successfull!');
            }
        }

        /**
         * Extract search_* but remove key suffix
         */
        // stupid array_map can't do that
        $searchParams = [];
        foreach ($_GET as $k => $v) {
            if (str_starts_with($k, 'search_')) {
                $searchParams[substr($k, 7)] = $v;
            }
        }

        /**
         * Update logic
         */
        $update_id = intval($_GET['edit'] ?? -1);
        if ($update_id > 0) {
            $schema = $model->extract_spec_from_table($table, true);

            /**
             * Do update
             */
            if (array_key_exists('save', $_GET)) {
                if ($error = $model->updateRow($table, $update_id, $schema, $searchParams)) {
                    redirect("/dev/table/$table", $error);
                } else {
                    redirect("/dev/table/$table", null, 'Updated row!');
                }
            }

            /**
             * Send form
             */
            $schema = $model->fill_schema_with_data($table, $schema, $update_id);
            $components = \component\FormComponents::render($schema);
            $backUrl = "/dev/table/$table";
            $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            return <<<END
            <form class="mt-3 card" action="$url" method="GET" id="inputForm">
                <div class="card-header">
                    <h5 class="card-title">Edit #$update_id</h5>
                </div>
                <div class="card-body">
                    <h4>Edit DB row</h4>
                    $components
                </div>
                <div class="card-footer">
                    <a href="$backUrl" class="btn btn-secondary">Close</a>
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="edit" value="$update_id">
                    <button type="submit" class="btn btn-primary">Save edits</button>
                </div>
            </form>
            END;
        }


        /**
         * Do a normal fuzzy search
         */
        $schema = $model->extract_spec_from_table($table);

        $results = $model->searchTable($table, $page, $searchParams);

        [
                'pages' => $pages,
                'res' => $result,
            ] = $results;

        return \component\DevTableView::render($result, $page, $pages, $schema, parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "Search $table");
    }

    /**
     * Get list of tables for dev main page
     */
    public static function listTables(DevModel $model)
    {
        $rows = implode('', array_map(fn ($table) => "<tr><td><a href=\"/dev/table/$table\">$table</td></tr>", $model->listTables()));
        return <<<END
        <h1>Tables</h1>
        The following tables are alaviable to edit:
        <table border=2>
            <tr>
                <th>Table name</th>
            </tr>
            $rows
        </table>
        You can also <a class="btn btn-secondary btn-sm" href="/dev/seed">seed the DB</a>
        END;
    }
}
