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
    public function execute($query)
    {
        $this->result = mysqli_query($this->connection, $query);
        if ($this->result)
            return $this->result;
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


    // select all // o/ne
    public function select(

        $table = "",
        $alias_table1 = "",
         $fields = ["*"],
        $where = [],
        $like = [],
        $limit = "",
        $join = "",
        $second_table = "",
        $alias_table2 = "",
        $join_conditions = "",
        $groupby = [],
        $orderby = []
    ) {
        // Validate and prepare fields
        $fields = implode(',', $this->inputs_validate($fields));
        $table = $this->input_validate($table);

        // Start building the base query
        $query = "SELECT $fields FROM $table";

        // Add table alias for the main table if provided
        if (!empty($alias_table1)) {
            $query .= " AS $alias_table1";
        }

        // Add JOINs if provided
        if (!empty($join) && !empty($second_table) && !empty($join_conditions)) {
            $second_table = $this->input_validate($second_table);
            $join_conditions = $this->input_validate($join_conditions);
            $query .= " $join $second_table";

            // Add alias for the second table if provided
            if (!empty($alias_table2)) {
                $query .= " AS $alias_table2";
            }

            $query .= " ON $join_conditions";
        }

        // Add WHERE conditions
        $where_values = [];
        $like_values = [];
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
        if (!empty($like)) {
            foreach ($like as $key => $value) {
                $like_keys[] = " $key LIKE ?";
                $like_values[] = $value;
                $types .= $this->get_type($value);
            }
            if (!empty($where)) {
                $query .= " And ";
                $query .= implode(" AND ", $like_keys);
            } else {

                $query .= "where ";
                $query .= implode(" AND ", $like_keys);
            }


        }

        // Add GROUP BY clause
        if (!empty($groupby)) {
            $groupby = implode(", ", $this->inputs_validate($groupby));
            $query .= " GROUP BY $groupby";
        }

        // Add ORDER BY clause
        if (!empty($orderby)) {
            $orderby_clause = [];
            foreach ($orderby as $field => $direction) {
                $direction = strtoupper($direction) === "DESC" ? "DESC" : "ASC";
                $orderby_clause[] = "$field $direction";
            }
            $query .= " ORDER BY " . implode(", ", $orderby_clause);
        }

        // Add LIMIT clause
        if (!empty($limit)) {
            $query .= " LIMIT $limit";
        }


        // Prepare the statement
        $stmt = $this->connection->prepare($query);

        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $this->connection->error);
        }
        $values = array_merge($where_values, $like_values);
        // Bind parameters if necessary
        // echo $query;
        // print_r($values);
        if (!empty($where_values) || !empty($like_values)) {
            $stmt->bind_param($types, ...$values);
        }

        // Execute the query
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        // Get the result
        $this->result = $stmt->get_result();
        // Fetch data

        if (!empty($limit) && $limit == 1) {
            return $this->result->fetch_assoc(); // Return a single row
        } else {
            return $this->result->fetch_all(MYSQLI_ASSOC); // Return all rows as an array
        }
    }
// select...from...join...where...groupby...orderby 
    // input => array of data  return query

   

    public function groupby($conditions = [])
    {
        $conditions = $this->input_validate($conditions);
        $query = "GROUP BY ";
        if (!empty($conditions)) {
            $groups = implode(',', $conditions);
        }
        $query .= $groups;
        return $query;
    }
    public function orderby($conditions = [])
    {
        $conditions = $this->input_validate($conditions);
        $query = "ORDER BY";
        $orderby_clause = [];
        if (!empty($conditions)) {
            foreach ($conditions as $field => $direction) {
                $direction = strtoupper($direction) === "DESC" ? "DESC" : "ASC";
                $orderby_clause[] = "$field $direction";
            }

        }
        $query .= implode(",", $orderby_clause);
        return $query;

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
        // echo $types;
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
            return inputs_validate($data);
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
