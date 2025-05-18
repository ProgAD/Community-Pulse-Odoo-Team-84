<?php
/**
 * Database Helper Functions
 * 
 * This file contains common functions for interacting with the database.
 */

require_once 'config.php';

/**
 * Sanitize input data to prevent SQL injection
 * 
 * @param mysqli $conn Database connection
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Execute a database query
 * 
 * @param string $query SQL query to execute
 * @param array $params Parameters for prepared statement
 * @param string $types Types of parameters (s = string, i = integer, d = double, b = blob)
 * @return mixed Query result or false on failure
 */
function executeQuery($query, $params = [], $types = '') {
    $conn = connectDB();
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Query preparation failed: " . $conn->error);
        $conn->close();
        return false;
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            // Automatically determine parameter types
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                elseif (is_string($param)) $types .= 's';
                else $types .= 'b';
            }
        }
        
        // Bind parameters
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result === false) {
        // For INSERT, UPDATE, DELETE queries
        $affected_rows = $stmt->affected_rows;
        $insert_id = $conn->insert_id;
        $conn->close();
        
        return [
            'affected_rows' => $affected_rows,
            'insert_id' => $insert_id
        ];
    }
    
    // For SELECT queries
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $conn->close();
    return $data;
}

/**
 * Get a single record from the database
 * 
 * @param string $query SQL query to execute
 * @param array $params Parameters for prepared statement
 * @param string $types Types of parameters
 * @return array|null Single record or null if not found
 */
function getRecord($query, $params = [], $types = '') {
    $result = executeQuery($query, $params, $types);
    
    if ($result && is_array($result) && count($result) > 0) {
        return $result[0];
    }
    
    return null;
}

/**
 * Insert a record into the database
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value pairs
 * @return int|bool Last insert ID or false on failure
 */
function insertRecord($table, $data) {
    $conn = connectDB();
    
    $columns = [];
    $placeholders = [];
    $values = [];
    $types = '';
    
    foreach ($data as $column => $value) {
        $columns[] = "`$column`";
        $placeholders[] = "?";
        $values[] = $value;
        
        if (is_int($value)) $types .= 'i';
        elseif (is_float($value)) $types .= 'd';
        elseif (is_string($value)) $types .= 's';
        else $types .= 'b';
    }
    
    $columns_str = implode(", ", $columns);
    $placeholders_str = implode(", ", $placeholders);
    
    $query = "INSERT INTO `$table` ($columns_str) VALUES ($placeholders_str)";
    
    $result = executeQuery($query, $values, $types);
    
    if ($result && isset($result['insert_id'])) {
        return $result['insert_id'];
    }
    
    return false;
}

/**
 * Update a record in the database
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value pairs to update
 * @param array $condition Associative array for WHERE clause (column => value)
 * @return bool True on success, false on failure
 */
function updateRecord($table, $data, $condition) {
    $conn = connectDB();
    
    $set_clause = [];
    $where_clause = [];
    $values = [];
    $types = '';
    
    foreach ($data as $column => $value) {
        $set_clause[] = "`$column` = ?";
        $values[] = $value;
        
        if (is_int($value)) $types .= 'i';
        elseif (is_float($value)) $types .= 'd';
        elseif (is_string($value)) $types .= 's';
        else $types .= 'b';
    }
    
    foreach ($condition as $column => $value) {
        $where_clause[] = "`$column` = ?";
        $values[] = $value;
        
        if (is_int($value)) $types .= 'i';
        elseif (is_float($value)) $types .= 'd';
        elseif (is_string($value)) $types .= 's';
        else $types .= 'b';
    }
    
    $set_str = implode(", ", $set_clause);
    $where_str = implode(" AND ", $where_clause);
    
    $query = "UPDATE `$table` SET $set_str WHERE $where_str";
    
    $result = executeQuery($query, $values, $types);
    
    if ($result && isset($result['affected_rows'])) {
        return $result['affected_rows'] > 0;
    }
    
    return false;
}

/**
 * Delete a record from the database
 * 
 * @param string $table Table name
 * @param array $condition Associative array for WHERE clause (column => value)
 * @return bool True on success, false on failure
 */
function deleteRecord($table, $condition) {
    $conn = connectDB();
    
    $where_clause = [];
    $values = [];
    $types = '';
    
    foreach ($condition as $column => $value) {
        $where_clause[] = "`$column` = ?";
        $values[] = $value;
        
        if (is_int($value)) $types .= 'i';
        elseif (is_float($value)) $types .= 'd';
        elseif (is_string($value)) $types .= 's';
        else $types .= 'b';
    }
    
    $where_str = implode(" AND ", $where_clause);
    
    $query = "DELETE FROM `$table` WHERE $where_str";
    
    $result = executeQuery($query, $values, $types);
    
    if ($result && isset($result['affected_rows'])) {
        return $result['affected_rows'] > 0;
    }
    
    return false;
}

/**
 * Check if a record exists in the database
 * 
 * @param string $table Table name
 * @param array $condition Associative array for WHERE clause (column => value)
 * @return bool True if record exists, false otherwise
 */
function recordExists($table, $condition) {
    $conn = connectDB();
    
    $where_clause = [];
    $values = [];
    $types = '';
    
    foreach ($condition as $column => $value) {
        $where_clause[] = "`$column` = ?";
        $values[] = $value;
        
        if (is_int($value)) $types .= 'i';
        elseif (is_float($value)) $types .= 'd';
        elseif (is_string($value)) $types .= 's';
        else $types .= 'b';
    }
    
    $where_str = implode(" AND ", $where_clause);
    
    $query = "SELECT 1 FROM `$table` WHERE $where_str LIMIT 1";
    
    $result = executeQuery($query, $values, $types);
    
    return !empty($result);
}

/**
 * Count records in a table with optional conditions
 * 
 * @param string $table Table name
 * @param array $condition Optional associative array for WHERE clause
 * @return int Number of records
 */
function countRecords($table, $condition = []) {
    $conn = connectDB();
    
    $where_clause = [];
    $values = [];
    $types = '';
    
    foreach ($condition as $column => $value) {
        $where_clause[] = "`$column` = ?";
        $values[] = $value;
        
        if (is_int($value)) $types .= 'i';
        elseif (is_float($value)) $types .= 'd';
        elseif (is_string($value)) $types .= 's';
        else $types .= 'b';
    }
    
    $where_str = !empty($where_clause) ? "WHERE " . implode(" AND ", $where_clause) : "";
    
    $query = "SELECT COUNT(*) as count FROM `$table` $where_str";
    
    $result = executeQuery($query, $values, $types);
    
    if ($result && isset($result[0]['count'])) {
        return (int)$result[0]['count'];
    }
    
    return 0;
}
