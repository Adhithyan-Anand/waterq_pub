<?php
/**
 * Water Quality Monitoring - Data Handler
 * 
 * This script receives water quality data from ESP8266
 * and stores it in a MySQL database.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MySQL database connection details
$db_host = "";      // Your MySQL host
$db_user = "";      // Your MySQL username
$db_pass = ""; // Your MySQL password
$db_name = "";  // Your MySQL database name

// Function to validate numeric values
function validateNumeric($value, $min, $max) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $value = floatval($value);
    return ($value >= $min && $value <= $max);
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Retrieve POST data
    $tds1 = isset($_POST['tds1']) ? $_POST['tds1'] : null;
    $tds2 = isset($_POST['tds2']) ? $_POST['tds2'] : null;
    $flow1 = isset($_POST['flow1']) ? $_POST['flow1'] : null;
    $flow2 = isset($_POST['flow2']) ? $_POST['flow2'] : null;
    $flow_out = isset($_POST['flow_out']) ? $_POST['flow_out'] : null;
    
    // Validate data
    $tds1_valid = validateNumeric($tds1, 0, 5000);    // TDS normally 0-5000 ppm
    $tds2_valid = validateNumeric($tds2, 0, 5000);
    $flow1_valid = validateNumeric($flow1, 0, 100);   // Flow rates 0-100 L/min
    $flow2_valid = validateNumeric($flow2, 0, 100);
    $flow_out_valid = validateNumeric($flow_out, 0, 100);
    
    if ($tds1_valid && $tds2_valid && $flow1_valid && $flow2_valid && $flow_out_valid) {
        // All data valid, proceed with database storage
        
        try {
            // Create database connection with expanded error reporting
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            
            // Check connection
            if ($conn->connect_error) {
                $error_msg = "Connection failed: " . $conn->connect_error;
                // Log connection error
                error_log($error_msg);
                file_put_contents('db_error_log.txt', date('Y-m-d H:i:s') . " - " . $error_msg . "\n", FILE_APPEND);
                throw new Exception($error_msg);
            }
            
            // Log connection success
            error_log("Database connection established successfully");
            
            // Check if table exists, create if not
            $table_check = $conn->query("SHOW TABLES LIKE 'water_readings'");
            if ($table_check->num_rows == 0) {
                // Table doesn't exist, create it
                $create_table_sql = "CREATE TABLE water_readings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tds_in FLOAT NOT NULL,
                    tds_out FLOAT NOT NULL,
                    flow_in FLOAT NOT NULL,
                    waste_flow FLOAT NOT NULL,
                    flow_out FLOAT NOT NULL,
                    reading_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )";
                
                if (!$conn->query($create_table_sql)) {
                    throw new Exception("Failed to create table: " . $conn->error);
                }
                
                // Create index for faster queries
                $conn->query("CREATE INDEX idx_reading_time ON water_readings(reading_time)");
                error_log("Created water_readings table successfully");
            }
            
            // Prepare and execute statement
            $stmt = $conn->prepare("INSERT INTO water_readings (tds_in, tds_out, flow_in, waste_flow, flow_out) 
                                   VALUES (?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                $error_msg = "Prepare failed: " . $conn->error;
                error_log($error_msg);
                file_put_contents('db_error_log.txt', date('Y-m-d H:i:s') . " - " . $error_msg . "\n", FILE_APPEND);
                throw new Exception($error_msg);
            }
            
            $stmt->bind_param("ddddd", $tds1, $tds2, $flow1, $flow2, $flow_out);
            
            if ($stmt->execute()) {
                echo "Data stored successfully! ID: " . $conn->insert_id;
                
                // Log to file for debugging
                $log_message = date('Y-m-d H:i:s') . " - Data stored (ID: " . $conn->insert_id . "): TDS IN: $tds1, TDS OUT: $tds2, FLOW IN: $flow1, WASTE FLOW: $flow2, FLOW OUT: $flow_out\n";
                file_put_contents('water_log.txt', $log_message, FILE_APPEND);
                error_log("Data inserted successfully with ID: " . $conn->insert_id);
            } else {
                $error_msg = "Error executing statement: " . $stmt->error;
                error_log($error_msg);
                file_put_contents('db_error_log.txt', date('Y-m-d H:i:s') . " - " . $error_msg . "\n", FILE_APPEND);
                throw new Exception($error_msg);
            }
            
            // Close statement and connection
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
            echo $error_message;
            
            // Log the error for debugging
            error_log($error_message);
            file_put_contents('db_error_log.txt', date('Y-m-d H:i:s') . " - " . $error_message . "\n", FILE_APPEND);
            
            // Return HTTP 500 status code to indicate server error
            // This helps the ESP8266 client detect connection problems
            http_response_code(500);
        }
        
    } else {
        // Invalid data
        echo "Error: Invalid data received. Data must be numerical and within acceptable ranges.";
        
        // Output validation details for debugging
        echo "\nValidation results: ";
        echo "TDS IN: " . ($tds1_valid ? "Valid" : "Invalid") . " ($tds1), ";
        echo "TDS OUT: " . ($tds2_valid ? "Valid" : "Invalid") . " ($tds2), ";
        echo "FLOW IN: " . ($flow1_valid ? "Valid" : "Invalid") . " ($flow1), ";
        echo "WASTE FLOW: " . ($flow2_valid ? "Valid" : "Invalid") . " ($flow2), ";
        echo "FLOW OUT: " . ($flow_out_valid ? "Valid" : "Invalid") . " ($flow_out)";
    }
    
} else {
    // Not a POST request
    echo "Error: Only POST requests are accepted.";
}
?>
