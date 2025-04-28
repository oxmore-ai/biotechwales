<?php
// config.php - Database connection setup

// Production
$db_host = 'mysql-200-139.mysql.prositehosting.net'; // Use IP instead of 'localhost'
$db_user = 'biotechadmin'; // Default MAMP username
$db_password = 'bfy4VAGUDZ2Gx89vxfbp'; // Default MAMP password
$db_db = 'directory_db'; // Change to your actual local database name

// Create mysqli connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_db, $db_port);

// Check connection
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// For backward compatibility with existing PDO code
// This class creates a PDO-like interface that uses mysqli
class MysqliDb {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function query($sql) {
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new Exception("Query failed: " . $this->mysqli->error);
        }
        return new MysqliStatement($result);
    }
    
    public function prepare($sql) {
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysqli->error);
        }
        return new MysqliStatement($stmt, $sql);
    }
    
    public function lastInsertId() {
        return $this->mysqli->insert_id;
    }
    
    public function setAttribute($attribute, $value) {
        // Just a stub for PDO compatibility
        return true;
    }
}

class MysqliStatement {
    private $result;
    private $stmt;
    private $sql;
    
    public function __construct($result, $sql = null) {
        if ($result instanceof mysqli_stmt) {
            $this->stmt = $result;
            $this->sql = $sql;
        } else {
            $this->result = $result;
        }
    }
    
    public function execute($params = []) {
        if ($this->stmt) {
            if (!empty($params)) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                }
                
                $bind_params = array($types);
                foreach ($params as $key => $value) {
                    $bind_params[] = &$params[$key];
                }
                
                call_user_func_array(array($this->stmt, 'bind_param'), $bind_params);
            }
            
            if (!$this->stmt->execute()) {
                throw new Exception("Execute failed: " . $this->stmt->error);
            }
            
            $this->result = $this->stmt->get_result();
            return true;
        }
        return false;
    }
    
    public function fetchAll($fetch_style = null) {
        $rows = [];
        if ($this->result) {
            while ($row = $this->result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
    
    public function fetch($fetch_style = null) {
        if ($this->result) {
            return $this->result->fetch_assoc();
        }
        return false;
    }
    
    public function fetchColumn() {
        if ($this->result) {
            $row = $this->result->fetch_array(MYSQLI_NUM);
            return $row ? $row[0] : false;
        }
        return false;
    }
}

// Create PDO-compatible wrapper
$pdo = new MysqliDb($mysqli);
?>