<?php
/**
 * Water Quality Monitoring System - Dashboard
 * 
 * Main dashboard for the water quality monitoring system.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Quality Monitoring System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f5f8fa;
        }
        header {
            background-color: #0066cc;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #0066cc;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .dashboard-item {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        .dashboard-value {
            font-size: 28px;
            font-weight: bold;
            margin: 15px 0;
        }
        .dashboard-label {
            color: #666;
            font-size: 16px;
        }
        .actions {
            margin: 30px 0;
            text-align: center;
        }
        .button {
            background-color: #0066cc;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px;
            display: inline-block;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0052a3;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 14px;
            color: #666;
            padding: 20px;
            border-top: 1px solid #ddd;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Water Quality Monitoring System</h1>
            <p>Real-time monitoring of water quality parameters</p>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2>System Overview</h2>
            <p>This system monitors water quality parameters including TDS levels and flow rates. Data is collected from Arduino sensors, transmitted via ESP8266, and stored in a MySQL database.</p>
        </div>
        
        <h2>Latest Readings</h2>
        <div class="dashboard-grid">
            <?php
            // MySQL database connection details
            $db_host = "";      // Your MySQL host
            $db_user = "";      // Your MySQL username
            $db_pass = ""; // Your MySQL password
            $db_name = "";  // Your MySQL database name
            
            try {
                // Create database connection
                $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                
                // Check connection
                if ($conn->connect_error) {
                    throw new Exception("Connection failed: " . $conn->connect_error);
                }
                
                // Get the latest reading
                $sql = "SELECT * FROM water_readings ORDER BY reading_time DESC LIMIT 1";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $timestamp = date('Y-m-d H:i:s', strtotime($row["reading_time"]));
                    
                    // TDS IN
                    echo "<div class='dashboard-item'>";
                    echo "<div class='dashboard-label'>TDS IN</div>";
                    echo "<div class='dashboard-value'>" . number_format($row["tds_in"], 1) . " PPM</div>";
                    echo "</div>";
                    
                    // TDS OUT
                    echo "<div class='dashboard-item'>";
                    echo "<div class='dashboard-label'>TDS OUT</div>";
                    echo "<div class='dashboard-value'>" . number_format($row["tds_out"], 1) . " PPM</div>";
                    echo "</div>";
                    
                    // FLOW IN
                    echo "<div class='dashboard-item'>";
                    echo "<div class='dashboard-label'>FLOW IN</div>";
                    echo "<div class='dashboard-value'>" . number_format($row["flow_in"], 2) . " L/min</div>";
                    echo "</div>";
                    
                    // WASTE FLOW
                    echo "<div class='dashboard-item'>";
                    echo "<div class='dashboard-label'>WASTE FLOW</div>";
                    echo "<div class='dashboard-value'>" . number_format($row["waste_flow"], 2) . " L/min</div>";
                    echo "</div>";
                    
                    // FLOW OUT
                    echo "<div class='dashboard-item'>";
                    echo "<div class='dashboard-label'>FLOW OUT</div>";
                    echo "<div class='dashboard-value'>" . number_format($row["flow_out"], 2) . " L/min</div>";
                    echo "</div>";
                    
                    // Last updated
                    echo "<div class='dashboard-item'>";
                    echo "<div class='dashboard-label'>LAST UPDATED</div>";
                    echo "<div class='dashboard-value' style='font-size: 18px;'>" . $timestamp . "</div>";
                    echo "</div>";
                    
                } else {
                    echo "<div class='dashboard-item' style='grid-column: 1 / -1;'>";
                    echo "<p>No readings available yet. Please make sure your Arduino and ESP8266 are sending data.</p>";
                    echo "</div>";
                }
                
                // Close the MySQL connection
                $conn->close();
            } catch(Exception $e) {
                echo "<div class='dashboard-item' style='grid-column: 1 / -1;'>";
                echo "<p>Error: " . $e->getMessage() . "</p>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="actions">
            <a href="test_data_submission.php" class="button">Submit Test Data</a>
            <a href="test_view_data.php" class="button">View All Data</a>
            <a href="test_db_connection.php" class="button">Test Database</a>
            <a href="test_post.php" class="button">Test Data Posting</a>
        </div>
        
        <div class="card">
            <h2>System Setup</h2>
            <p>The complete water quality monitoring system includes:</p>
            <ul>
                <li>Arduino UNO with TDS sensors and flow sensors</li>
                <li>16x2 I2C LCD display for real-time readings</li>
                <li>ESP8266 for wireless data transmission</li>
                <li>MySQL database for data storage and retrieval</li>
                <li>Web interface for monitoring the data</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Water Quality Monitoring System &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
</body>
</html>