<?php
// ============================================================
// SmartServe – Supabase REST API Helper
// File: db_connect.php
// ============================================================
// Uses Supabase REST API (HTTP) instead of direct PostgreSQL
// connection — avoids all port/SSL/pooler issues.
// ============================================================

define('SUPABASE_URL',  'https://phheuvsnkllqxjkgoodh.supabase.co');
define('SUPABASE_KEY',  'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBoaGV1dnNua2xscXhqa2dvb2RoIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODMyMjgwOTcsImV4cCI6MjA5ODgwNDA5N30.xTG6XBv32ln3Ks-HGE7NDE1wimO9ul4aKcSySH4wb-A');
define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBoaGV1dnNua2xscXhqa2dvb2RoIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4MzIyODA5NywiZXhwIjoyMDk4ODA0MDk3fQ.placeholder');

/**
 * Make a Supabase REST API request
 * @param string $table       Table name
 * @param string $method      HTTP method: GET, POST, PATCH, DELETE
 * @param array  $data        Data to send (for POST/PATCH)
 * @param string $query       Query string filters (e.g. "id=eq.5")
 * @param bool   $single      Return single object
 * @return array|null
 */
function supabase_request(string $table, string $method = 'GET', array $data = [], string $query = '', bool $single = false): ?array {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    if ($query) $url .= '?' . $query;

    $headers = [
        'apikey: '       . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation',
    ];
    if ($single) {
        $headers[] = 'Accept: application/vnd.pgrst.object+json';
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        return null;
    }
    return json_decode($response, true);
}

/**
 * Get rows from a table with optional filters
 * @param string $table
 * @param string $filters  e.g. "status=eq.pending&order=order_date.asc"
 * @return array
 */
function db_select(string $table, string $filters = ''): array {
    $result = supabase_request($table, 'GET', [], $filters . ($filters ? '&' : '') . 'select=*');
    return $result ?? [];
}

/**
 * Get a single row
 */
function db_select_one(string $table, string $filters = ''): ?array {
    $result = supabase_request($table, 'GET', [], $filters . ($filters ? '&' : '') . 'select=*', true);
    return $result;
}

/**
 * Insert a row
 */
function db_insert(string $table, array $data): ?array {
    return supabase_request($table, 'POST', $data);
}

/**
 * Update rows matching filters
 */
function db_update(string $table, array $data, string $filters): ?array {
    return supabase_request($table, 'PATCH', $data, $filters);
}

/**
 * Delete rows matching filters
 */
function db_delete(string $table, string $filters): ?array {
    return supabase_request($table, 'DELETE', [], $filters);
}

// -----------------------------------------------------------
// Legacy $pdo compatibility shim
// This lets existing PHP files that use $pdo->prepare() still work
// by routing through a simple PDO-like wrapper.
// -----------------------------------------------------------
class SupabasePDO {
    private string $lastInsertId = '0';

    public function setLastInsertId(string $id): void {
        $this->lastInsertId = $id;
    }

    public function prepare(string $sql): SupabaseStatement {
        return new SupabaseStatement($sql, $this);
    }

    public function query(string $sql): SupabaseStatement {
        $stmt = new SupabaseStatement($sql, $this);
        $stmt->execute([]);
        return $stmt;
    }

    public function lastInsertId(): string {
        return $this->lastInsertId;
    }

    public function inTransaction(): bool {
        return false;
    }

    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function rollBack(): bool { return true; }
}

class SupabaseStatement {
    private string $sql;
    private array  $params = [];
    private array  $rows   = [];
    private int    $pos    = 0;
    private ?SupabasePDO $pdoRef = null;

    public function __construct(string $sql, ?SupabasePDO $pdoRef = null) {
        $this->sql = $sql;
        $this->pdoRef = $pdoRef;
    }

    public function bindParam(string $key, mixed &$val): void {
        $this->params[$key] = &$val;
    }

    public function execute(array $params = []): bool {
        if (!empty($params)) {
            $this->params = array_merge($this->params, $params);
        }
        $this->rows = $this->runQuery();
        $this->pos  = 0;

        // If it was an INSERT and we have returned rows
        if (stripos(trim($this->sql), 'INSERT') === 0 && !empty($this->rows) && $this->pdoRef !== null) {
            $firstRow = $this->rows[0];
            $idVal = '0';
            foreach ($firstRow as $col => $val) {
                if (strtolower($col) === 'id' || preg_match('/_id$/i', $col)) {
                    $idVal = strval($val);
                    break;
                }
            }
            if ($idVal === '0' && !empty($firstRow)) {
                $idVal = strval(current($firstRow));
            }
            $this->pdoRef->setLastInsertId($idVal);
        }
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_ASSOC): mixed {
        if ($this->pos < count($this->rows)) {
            return $this->rows[$this->pos++];
        }
        return false;
    }

    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array {
        return $this->rows;
    }

    public function fetchColumn(int $col = 0): mixed {
        if (!empty($this->rows)) {
            $row = array_values($this->rows[0]);
            return $row[$col] ?? null;
        }
        return null;
    }

    public function rowCount(): int {
        return count($this->rows);
    }

    private function runQuery(): array {
        $sql    = trim($this->sql);
        $params = $this->params;

        // Helper to parse WHERE clause
        $parseWhere = function(string $where, array $params, array $vals, &$valIndex) {
            $filters = [];
            $conditions = preg_split('/\s+AND\s+/i', $where);
            foreach ($conditions as $cond) {
                $cond = trim($cond);
                if (empty($cond)) continue;

                // col = ? or col = :param
                if (preg_match('/^["`]?(\w+)["`]?\s*=\s*(\?|:\w+)$/', $cond, $cm)) {
                    $col = $cm[1];
                    $v = isset($params[$cm[2]]) ? $params[$cm[2]] : ($vals[$valIndex] ?? null);
                    $filters[] = $col . '=eq.' . urlencode($v ?? '');
                    $valIndex++;
                }
                // col = 'literal'
                else if (preg_match('/^["`]?(\w+)["`]?\s*=\s*\'([^\']+)\'$/', $cond, $cm)) {
                    $filters[] = $cm[1] . '=eq.' . urlencode($cm[2]);
                }
                // col != 'literal' or col <> 'literal'
                else if (preg_match('/^["`]?(\w+)["`]?\s*(?:!=|<>)\s*\'([^\']+)\'$/', $cond, $cm)) {
                    $filters[] = $cm[1] . '=neq.' . urlencode($cm[2]);
                }
                // col NOT IN ('val1', 'val2')
                else if (preg_match('/^["`]?(\w+)["`]?\s+NOT\s+IN\s*\(([^)]+)\)$/i', $cond, $cm)) {
                    $col = $cm[1];
                    $valList = str_replace(["'", " "], "", $cm[2]);
                    $filters[] = $col . '=not.in.(' . $valList . ')';
                }
                // col IN ('val1', 'val2')
                else if (preg_match('/^["`]?(\w+)["`]?\s+IN\s*\(([^)]+)\)$/i', $cond, $cm)) {
                    $col = $cm[1];
                    $valList = str_replace(["'", " "], "", $cm[2]);
                    $filters[] = $col . '=in.(' . $valList . ')';
                }
            }
            return $filters;
        };

        // ------- SELECT -------
        if (stripos($sql, 'SELECT') === 0) {
            preg_match('/FROM\s+["`]?(\w+)["`]?/i', $sql, $tm);
            $table = $tm[1] ?? '';

            $filters = [];
            $valIndex = 0;
            $vals = array_values($params);

            // WHERE clause parsing
            if (preg_match('/WHERE\s+(.+?)(?:\s+(?:ORDER|LIMIT|GROUP|HAVING|$))/is', $sql, $wm) ||
                preg_match('/WHERE\s+(.+)$/is', $sql, $wm)) {
                $where = $wm[1];
                $filters = $parseWhere($where, $params, $vals, $valIndex);
            }

            // ORDER BY
            if (preg_match('/ORDER\s+BY\s+(\w+)\s*(ASC|DESC)?/i', $sql, $om)) {
                $dir = strtolower($om[2] ?? 'asc');
                $filters[] = 'order=' . $om[1] . '.' . $dir;
            }

            // LIMIT
            if (preg_match('/LIMIT\s+(\d+)/i', $sql, $lm)) {
                $filters[] = 'limit=' . $lm[1];
            }

            $q = implode('&', $filters);
            return db_select($table, $q);
        }

        // ------- INSERT -------
        if (stripos($sql, 'INSERT') === 0) {
            preg_match('/INTO\s+["`]?(\w+)["`]?\s*\(([^)]+)\)/i', $sql, $m);
            $table   = $m[1] ?? '';
            $columns = array_map('trim', explode(',', $m[2] ?? ''));
            $data    = [];
            $vals    = array_values($params);
            foreach ($columns as $i => $col) {
                $col = trim($col, '"` ');
                $data[$col] = $vals[$i] ?? null;
            }
            $r = db_insert($table, $data);
            return $r ?? [];
        }

        // ------- UPDATE -------
        if (stripos($sql, 'UPDATE') === 0) {
            preg_match('/UPDATE\s+["`]?(\w+)["`]?\s+SET\s+(.+?)\s+WHERE\s+(.+)/is', $sql, $m);
            $table  = $m[1] ?? '';
            $setPart   = $m[2] ?? '';
            $wherePart = $m[3] ?? '';

            $data    = [];
            $vals    = array_values($params);
            $valIndex = 0;

            // Parse SET items
            $setItems = explode(',', $setPart);
            foreach ($setItems as $item) {
                if (preg_match('/["`]?(\w+)["`]?\s*=\s*(.+)/', trim($item), $sm)) {
                    $col = trim($sm[1]);
                    $valStr = trim($sm[2]);
                    
                    if ($valStr === '?' || strpos($valStr, ':') === 0) {
                        $v = isset($params[$valStr]) ? $params[$valStr] : ($vals[$valIndex] ?? null);
                        $data[$col] = $v;
                        $valIndex++;
                    } else if (preg_match("/^'(.+)'$/", $valStr, $qm)) {
                        $data[$col] = $qm[1];
                    } else if (is_numeric($valStr)) {
                        $data[$col] = $valStr + 0;
                    } else if (strtoupper($valStr) === 'NOW()' || strtoupper($valStr) === 'CURRENT_TIMESTAMP') {
                        $data[$col] = date('Y-m-d H:i:s');
                    } else {
                        $data[$col] = $valStr;
                    }
                }
            }

            // Parse WHERE
            $filters = $parseWhere($wherePart, $params, $vals, $valIndex);

            $q = implode('&', $filters);
            $r = db_update($table, $data, $q);
            return $r ?? [];
        }

        // ------- DELETE -------
        if (stripos($sql, 'DELETE') === 0) {
            preg_match('/FROM\s+["`]?(\w+)["`]?\s+WHERE\s+(.+)/is', $sql, $m);
            $table  = $m[1] ?? '';
            $wherePart = $m[2] ?? '';

            $vals = array_values($params);
            $valIndex = 0;
            $filters = $parseWhere($wherePart, $params, $vals, $valIndex);

            $q = implode('&', $filters);
            $r = db_delete($table, $q);
            return $r ?? [];
        }

        return [];
    }
}

// Create global $pdo object (drop-in replacement)
$pdo = new SupabasePDO();
