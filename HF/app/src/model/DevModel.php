<?php

namespace DEV;

require_once 'lib.php';

/**
 * Ugliest model of all, hacked together in the last minute
 * DEV related direct DB manipulation
 */
class DevModel extends \ModelBase
{
    /**
     * Get list of column names for a table
     */
    protected function get_columns(string $table): array
    {
        $q = $this->db->query("SHOW COLUMNS FROM $table;");
        return $q->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Generate TableView-style spec of a table's columns. If $edit is set then we don't allow wildcard-like options.
     */
    public function extract_spec_from_table(string $table, bool $edit = false): array
    {
        $del = [['ACTIONS',
            'search' => 'no',
            'db' => 'id',
            'transform' => function ($id) {
                $url = updateGET(['delete' => $id]);
                $editUrl = updateGET(['edit' => $id]);
                return <<<END
                <a href="$url" class="text-decoration-none">🗑</a>
                <a href="$editUrl" class="text-decoration-none">✎</a>
                END;
            },
        ]];

        /**
         * Yeah more functional spaghetti
         */
        return array_merge($del, array_map(function ($row) use ($edit) {
            $schema = [$row['Field']];

            if (str_starts_with($row['Type'], 'enum')) {
                $schema['options'] = array_merge($edit ? [] : [['', 'Don\'t care']], array_map(fn ($x) => [substr($x, 1, -1), substr($x, 1, -1)], explode(',', substr($row['Type'], 5, -1))));
            }
            if (str_starts_with($row['Type'], 'tinyint')) {
                $schema['options'] = array_merge($edit ? [] : [['', 'Don\'t care']], [['1', '1'], ['0', '0']]);
            }
            if ($row['Type']==='int') {
                $schema['inputType'] = 'number';
            }
            $schema['param'] = 'search_' . $row['Field'];
            return $schema;
        }, $this->get_columns($table)));
    }

    /**
     * Fill a TableView-like schema with 'value' keys from a DB row. Used for single row editing.
     */
    public function fill_schema_with_data(string $table, array $schema, int $id)
    {
        $data = $this->db->query("SELECT * FROM $table WHERE id = $id")->fetch_assoc();

        $schema = array_map(function ($row) use ($data) {
            if (array_key_exists($row[0], $data)) {
                $row['value'] = $data[$row[0]];
            }
            return $row;
        }, $schema);
        return $schema;
    }

    /**
     * Update row with id $update_id in db $table, using $schema as help and $searchParams as source of values
     */
    public function updateRow(string $table, int $update_id, array $schema, array $searchParams): ?string
    {
        $vals = implode(', ', array_filter(array_map(function ($column) use ($searchParams) {
            if (!array_key_exists($column['Field'], $searchParams)) {
                return '';
            }
            $field = $column['Field'];
            $param = $searchParams[$field];
            if (!$param) {
                return '';
            }

            if (str_contains($column['Extra'], 'GENERATED')) {
                return '';
            }

            if (str_starts_with($column['Type'], 'varchar') || str_starts_with($column['Type'], 'enum') || str_starts_with($column['Type'], 'date')) {
                return "$field = '$param' ";
            }
            return "$field = $param ";
        }, $this->get_columns($table))));
        try {
            $this->db->query("UPDATE $table SET $vals WHERE id = $update_id;");
            return null;
        } catch (\mysqli_sql_exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * Delete a row with give id $id from table $table
     * Return error message if any
     */
    public function delete(string $table, int $id): ?string
    {
        try {
            $this->db->query("DELETE FROM $table WHERE ID = $id");
            return null;
        } catch (\mysqli_sql_exception $ex) {
            return $ex->getMessage();
        }
    }

    public const PAGESIZE = 20;

    /**
     * Paginated fuzzy-search on db schema
     */
    public function searchTable(string $table, int $page, array $searchParams)
    {
        $WHERE = implode('AND ', array_filter(array_map(function ($column) use ($searchParams) {
            if (!array_key_exists($column['Field'], $searchParams)) {
                return '';
            }
            $field = $column['Field'];
            $param = $searchParams[$field];
            if (!$param) {
                return '';
            }

            if (str_starts_with($column['Type'], 'varchar') || str_starts_with($column['Type'], 'enum') || str_starts_with($column['Type'], 'date')) {
                return "$field LIKE '%$param%' ";
            }
            return "$field = $param ";
        }, $this->get_columns($table))));

        $WHERE = $WHERE ? "WHERE $WHERE" : '';

        $q = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM $table $WHERE ORDER BY id LIMIT ? OFFSET ?;");

        $offset = $page * self::PAGESIZE;
        $limit = self::PAGESIZE;

        $q->bind_param('ii', $limit, $offset);
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
     * Get list of all tables in db
     */
    public function listTables()
    {
        return array_map(fn ($r) => $r[0], $this->db->query('show tables;')->fetch_all());
    }
}
