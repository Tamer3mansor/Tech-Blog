<?php
class orm
{

    protected $db_config = [];
    protected $connection = null;
    protected $result = null;
    // construct
    public function __construct(array $config)
    {
        if (count($config) < 4) {
            throw new Exception("Enter all configs");
        } else {
            $this->db_config = $config;
        }
    }
    // create connection
    public function create_connection()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }
        list($db_host, $db_user, $db_password, $db_name) = $this->db_config;
        $this->connection = mysqli_connect($db_host, $db_user, $db_password, $db_name);
        if (!$this->connection) {
            die("Error while connect to Database" . mysqli_connect_error());
        } else
            return $this->connection;


    }
    // execute query (not used)
    public function get($query)
    {
        // echo $query;
        $this->result = mysqli_query($this->connection, $query);
        if ($this->result)
            return mysqli_fetch_all($this->result, MYSQLI_ASSOC);
        else
            throw new Exception("Error Processing Request", 1);
    }
    // insert ::  data passed as assoc arr  
    public function insert($table = "", array $data)
    {
        $keys = implode(', ', $this->input_validate(array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $types = implode('', $this->get_types(array_values($data)));
        $table = $this->input_validate($table);

        $query = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            error_log("errorlog" . $this->connection->error);
            return false;
        }

        $values = array_values($data);
        if (!$stmt->bind_param($types, ...$values)) {
            error_log("errorlog" . $stmt->error);
            return false;
        }

        if (!$stmt->execute()) {
            if (str_contains($stmt->error, 'Duplicate entry')) {
                return 'Duplicate email';
            }
            error_log("errorlog" . $stmt->error);
            return false;
        }

        return $this->connection->insert_id;
    }


    public function select($fields = "")
    {

        $fields = $this->input_validate($fields);
        $query = 'SELECT ' . $fields;
        return $query;
    }
    public function from($tables = "*")
    {
        $tables = $this->input_validate($tables);
        $query = '  FROM ' . $tables;
        return $query;
    }
    public function join($tables = "", $alisa = "", $join_type = "", $conditions = "")
    {
        $tables = explode(',', $tables);
        $alisa = explode(',', $alisa);
        $tables = $this->input_validate($tables);
        $alisa = $this->input_validate($alisa);
        $join_type = $this->input_validate($join_type);
        list($alisa1, $alisa2) = $alisa;
        list($table1, $table2) = $tables;
        $query = " FROM $table1 as $alisa1 $join_type $table2 as $alisa2 ON  $conditions";

        return $query;
    }
    public function Where($where = "", $like = "")
    {
        // echo $where;
        $query = "";
        $where = explode(',', $where);
        $like = explode(',', $like);
        $where_query = $this->only_where($where);
        $like_query = $this->only_like($like);

        if (!empty($where_query) && !empty($like_query)) {
            $query .= " WHERE $where_query AND $like_query";
        } elseif (!empty($where_query)) {
            $query .= " WHERE $where_query";
        } elseif (!empty($like_query)) {
            $query .= " WHERE $like_query";
        }
        return $query;
    }
    public function only_Like($like = [])
    {

        $conditions = implode(" AND ", $like);
        return $conditions;
    }
    public function only_where($where = [])
    {
        // echo "where caluser    ";
        // print_r($where);
        $conditions = implode(' AND ', $where);
        return $conditions;
    }

    public function groupby($conditions = "")
    {
        $conditions = explode(',', $conditions);
        $conditions = $this->input_validate($conditions);
        $query = "  GROUP BY ";
        if (!empty($conditions)) {
            $groups = implode(',', $conditions);
        }
        $query .= $groups;
        return $query;
    }
    public function orderby($conditions = "")
    {
        $query = " ORDER BY " . $conditions;
        return $query;

    }
    public function limit(int $limit)
    {
        return " limit $limit ";
    }
    public function delete($table = "", $where = [])
    {
        $table = $this->input_validate($table);
        $query = "DELETE  from $table ";
        if (!empty($where)) {
            $where_keys = [];
            $types = "";

            foreach ($where as $key => $value) {
                $where_keys[] = "$key = ?";
                $where_values[] = $value;
                $types .= $this->get_type($value);
            }

            $query .= " WHERE " . implode(" AND ", $where_keys);
        }
        $stmt = $this->connection->prepare($query);
        if (!empty($where)) {
            $stmt->bind_param($types, ...$where_values);
        }
        $this->result = $stmt->execute();
        if ($this->result) {

            return $this->result;
        } else {
            return mysqli_error_list($this->connection);

        }

    }
    public function update($table = "", $data = [], $where = [])
    {
        $table = $this->input_validate($table);
        foreach ($data as $key => $value) {
            $keys[] = "$key = ?";
            $values[] = $value;
            $types .= $this->get_type($value);
        }

        $query = "UPDATE $table set " . implode(" ,", $keys);
        $where_values = [];
        if (!empty($where)) {
            $where_keys = [];
            $type = "";

            foreach ($where as $key => $value) {
                $where_keys[] = "$key = ?";
                $where_values[] = $value;
                $types .= $this->get_type($value);
            }

            $query .= " WHERE " . implode(" AND ", $where_keys);
        }
        $params = array_merge($values, $where_values);
        $stmt = $this->connection->prepare($query);
        echo $types;
        print_r($params);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }






    // helper functions 
    //validate inputs from user :: passed array 
    private function input_validate($data)
    {
        if (is_string($data)) {
            return mysqli_real_escape_string(
                $this->connection,
                htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8')
            );
        } elseif (is_array($data)) {
            return $this->inputs_validate($data);
        }

    }
    private function inputs_validate($data)
    {
        return array_map([$this, 'input_validate'], $data);

    }
    private function get_type($item)
    {
        if (is_int($item))
            return 'i';
        if (is_float($item))
            return 'd';
        if (is_string($item))
            return 's';
        return 'b'; //blob
    }
    private function get_types($items)
    {
        return array_map([$this, 'get_type'], $items);
    }
    public function __destruct()
    {
        if ($this->result)
            mysqli_free_result($this->result);
    }
}
