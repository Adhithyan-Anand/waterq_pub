<?php
/**
 * Database Connection Test for Water Quality System
 * 
 * This file tests the connection to the MySQL database and displays
 * information about the database structure and recent records.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MySQL database connection details
$db_host = "";    // MySQL host
$db_user = "";           // MySQL username
$db_pass = "";     // MySQL password
$db_name = "";      // MySQL database name

// Function to get table information
function getTableInfo($conn, $tableName) {
    $columns = [];
    $result = $conn->query("DESCRIBE $tableName");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
    }
    
    return $columns;
}

// Function to count records
function countRecords($conn, $tableName) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $tableName");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'];
    }
    return 0;
}

// Function to get recent records
function getRecentRecords($conn, $tableName, $limit = 5) {
    $records = [];
    $result = $conn->query("SELECT * FROM $tableName ORDER BY reading_time DESC LIMIT $limit");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    return $records;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Water Quality System - Database Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .nav {
            margin: 20px 0;
        }
        .nav a {
            display: inline-block;
            margin-right: 15px;
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        .nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Water Quality Monitoring System - Database Test</h1>
    
    <div class="nav">
        <a href="/">Home</a>
        <a href="test_post.php">Test Data Posting</a>
        <a href="test_data_submission.php">Submit Test Data</a>
        <a href="test_view_data.php">View All Data</a>
    </div>
    
    <div class="section" id="connection">
        <h2>Database Connection Test</h2>
        
        <?php
        try {
            // Create database connection
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Connection successful
            echo '<div class="success">';
            echo '<h3>✅ Connection Successful</h3>';
            echo '<p><strong>Server:</strong> ' . $conn->host_info . '</p>';
            echo '<p><strong>Database:</strong> ' . $db_name . '</p>';
            echo '<p><strong>Server Version:</strong> ' . $conn->server_info . '</p>';
            echo '</div>';
        
        } catch(Exception $e) {
            // Connection failed
            echo '<div class="error">';
            echo '<h3>❌ Connection Failed</h3>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '<p>Please check the database credentials in the code.</p>';
            echo '</div>';
            
            // Stop execution
            echo '</div></body></html>';
            exit;
        }
        ?>
    </div>
    
    <div class="section" id="table-structure">
        <h2>Database Tables</h2>
        
        <?php
        // Check if water_readings table exists
        $tableCheckResult = $conn->query("SHOW TABLES LIKE 'water_readings'");
        
        if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
            echo '<div class="success">';
            echo '<h3>✅ Table "water_readings" exists</h3>';
            
            // Get table structure
            $columns = getTableInfo($conn, 'water_readings');
            
            if (count($columns) > 0) {
                echo '<h4>Table Structure:</h4>';
                echo '<table>';
                echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
                
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td>' . $column['Field'] . '</td>';
                    echo '<td>' . $column['Type'] . '</td>';
                    echo '<td>' . $column['Null'] . '</td>';
                    echo '<td>' . $column['Key'] . '</td>';
                    echo '<td>' . (isset($column['Default']) ? $column['Default'] : 'NULL') . '</td>';
                    echo '<td>' . $column['Extra'] . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            echo '</div>';
            
            // Get record count
            $recordCount = countRecords($conn, 'water_readings');
            
            echo '<h3>Record Statistics</h3>';
            echo '<p><strong>Total Records:</strong> ' . $recordCount . '</p>';
            
            if ($recordCount > 0) {
                // Get the most recent records
                $recentRecords = getRecentRecords($conn, 'water_readings');
                
                echo '<h4>Most Recent Records:</h4>';
                echo '<table>';
                echo '<tr><th>ID</th><th>TDS IN</th><th>TDS OUT</th><th>FLOW IN</th><th>WASTE FLOW</th><th>FLOW OUT</th><th>Timestamp</th></tr>';
                
                foreach ($recentRecords as $record) {
                    echo '<tr>';
                    echo '<td>' . $record['id'] . '</td>';
                    echo '<td>' . $record['tds_in'] . '</td>';
                    echo '<td>' . $record['tds_out'] . '</td>';
                    echo '<td>' . $record['flow_in'] . '</td>';
                    echo '<td>' . $record['waste_flow'] . '</td>';
                    echo '<td>' . $record['flow_out'] . '</td>';
                    echo '<td>' . $record['reading_time'] . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            } else {
                echo '<div class="error">';
                echo '<p>No records found in the database yet. Try posting some data using the ESP8266 or test tools.</p>';
                echo '</div>';
            }
            
        } else {
            // Table doesn't exist
            echo '<div class="error">';
            echo '<h3>❌ Table "water_readings" does not exist</h3>';
            echo '<p>The database exists but the required table was not found.</p>';
            
            // Display SQL to create the table
            echo '<h4>SQL to Create Table:</h4>';
            echo '<pre>';
            echo file_get_contents('database_schema.sql');
            echo '</pre>';
            
            echo '<p>You can run this SQL in your database management tool to create the table.</p>';
            echo '</div>';
        }
        ?>
    </div>
    
    <div class="section" id="connection-params">
        <h2>Connection Parameters for ESP8266</h2>
        <p>These are the parameters your ESP8266 should be using to connect to the database via PHP:</p>
        
        <pre>
// Server Connection Details
const char* serverName = "adhithyadb.shop/water_data.php";
</pre>

        <h3>ESP8266 POST Data Format</h3>
        <pre>
// Example POST request data format
String httpRequestData = "tds1=" + String(tds1) + "&tds2=" + String(tds2) + 
                         "&flow1=" + String(flow1) + "&flow2=" + String(flow2) +
                         "&flow_out=" + String(flow_out);
</pre>
    </div>
    
    <?php
    // Close the MySQL connection
    if (isset($conn)) {
        $conn->close();
    }
    ?>
    
    <div class="nav">
        <a href="/">Return to Home</a>
    </div>
</body>
</html>