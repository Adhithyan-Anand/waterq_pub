<?php
/**
 * Water Quality System - View All Data
 * 
 * Displays all recorded water quality readings with filtering and sorting options
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MySQL database connection details
$db_host = "";    // MySQL host
$db_user = "";           // MySQL username
$db_pass = "";     // MySQL password
$db_name = "";      // MySQL database name

// Default pagination and sorting
$recordsPerPage = 20;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;
$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'reading_time';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Initialize filter variables
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$minTdsIn = isset($_GET['min_tds_in']) ? $_GET['min_tds_in'] : '';
$maxTdsIn = isset($_GET['max_tds_in']) ? $_GET['max_tds_in'] : '';
$minTdsOut = isset($_GET['min_tds_out']) ? $_GET['min_tds_out'] : '';
$maxTdsOut = isset($_GET['max_tds_out']) ? $_GET['max_tds_out'] : '';

// Build the query
$whereClause = [];
$params = [];
$paramTypes = '';

if (!empty($dateFilter)) {
    $whereClause[] = "DATE(reading_time) = ?";
    $params[] = $dateFilter;
    $paramTypes .= 's';
}

if (!empty($minTdsIn)) {
    $whereClause[] = "tds_in >= ?";
    $params[] = $minTdsIn;
    $paramTypes .= 'd';
}

if (!empty($maxTdsIn)) {
    $whereClause[] = "tds_in <= ?";
    $params[] = $maxTdsIn;
    $paramTypes .= 'd';
}

if (!empty($minTdsOut)) {
    $whereClause[] = "tds_out >= ?";
    $params[] = $minTdsOut;
    $paramTypes .= 'd';
}

if (!empty($maxTdsOut)) {
    $whereClause[] = "tds_out <= ?";
    $params[] = $maxTdsOut;
    $paramTypes .= 'd';
}

// Generate the WHERE clause
$whereSQL = '';
if (count($whereClause) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClause);
}

// Create the complete SQL query
$countSQL = "SELECT COUNT(*) as total FROM water_readings $whereSQL";
$dataSQL = "SELECT * FROM water_readings $whereSQL ORDER BY $sortField $sortOrder LIMIT $offset, $recordsPerPage";

// Function to format field labels
function formatFieldLabel($field) {
    $labels = [
        'id' => 'ID',
        'tds_in' => 'TDS IN (PPM)',
        'tds_out' => 'TDS OUT (PPM)',
        'flow_in' => 'FLOW IN (L/min)',
        'waste_flow' => 'WASTE FLOW (L/min)',
        'flow_out' => 'FLOW OUT (L/min)',
        'reading_time' => 'TIMESTAMP'
    ];
    
    return isset($labels[$field]) ? $labels[$field] : ucfirst(str_replace('_', ' ', $field));
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Water Quality System - View All Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            cursor: pointer;
        }
        th:hover {
            background-color: #e0e0e0;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            text-decoration: none;
            color: #0066cc;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a:hover {
            background-color: #f2f2f2;
        }
        .pagination .active {
            background-color: #0066cc;
            color: white;
            border: 1px solid #0066cc;
        }
        .nav {
            margin: 20px 0;
        }
        .nav a {
            margin-right: 15px;
            color: #0066cc;
            text-decoration: none;
        }
        .nav a:hover {
            text-decoration: underline;
        }
        .filter-form {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filter-form .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        .filter-form .form-group {
            margin-bottom: 10px;
        }
        .filter-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .filter-form input {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form button {
            padding: 8px 16px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .filter-form button:hover {
            background-color: #0052a3;
        }
        .sort-icon::after {
            content: "";
            display: inline-block;
            width: 0;
            height: 0;
            margin-left: 5px;
            vertical-align: middle;
        }
        .sort-asc::after {
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-bottom: 5px solid black;
        }
        .sort-desc::after {
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid black;
        }
        .no-data {
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Water Quality Monitoring System - All Data</h1>
    
    <div class="nav">
        <a href="/">Home</a>
        <a href="test_post.php">Test Data Posting</a>
        <a href="test_data_submission.php">Submit Test Data</a>
        <a href="test_db_connection.php">Test Database Connection</a>
    </div>
    
    <div class="filter-form">
        <h2>Filter Data</h2>
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                </div>
                
                <div class="form-group">
                    <label for="min_tds_in">Min TDS IN:</label>
                    <input type="number" id="min_tds_in" name="min_tds_in" step="0.01" value="<?php echo htmlspecialchars($minTdsIn); ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_tds_in">Max TDS IN:</label>
                    <input type="number" id="max_tds_in" name="max_tds_in" step="0.01" value="<?php echo htmlspecialchars($maxTdsIn); ?>">
                </div>
                
                <div class="form-group">
                    <label for="min_tds_out">Min TDS OUT:</label>
                    <input type="number" id="min_tds_out" name="min_tds_out" step="0.01" value="<?php echo htmlspecialchars($minTdsOut); ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_tds_out">Max TDS OUT:</label>
                    <input type="number" id="max_tds_out" name="max_tds_out" step="0.01" value="<?php echo htmlspecialchars($maxTdsOut); ?>">
                </div>
            </div>
            
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortField); ?>">
            <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
            
            <button type="submit">Apply Filters</button>
            <button type="button" onclick="window.location.href='test_view_data.php'">Clear Filters</button>
        </form>
    </div>
    
    <?php
    try {
        // Create database connection
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Prepare and execute count query
        $totalRecords = 0;
        if (count($params) > 0) {
            $countStmt = $conn->prepare($countSQL);
            $countStmt->bind_param($paramTypes, ...$params);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $totalRecords = $countRow['total'];
            $countStmt->close();
        } else {
            $countResult = $conn->query($countSQL);
            $countRow = $countResult->fetch_assoc();
            $totalRecords = $countRow['total'];
        }
        
        // Calculate pagination info
        $totalPages = ceil($totalRecords / $recordsPerPage);
        
        // Display the total records
        echo "<p><strong>Total Records:</strong> " . $totalRecords . "</p>";
        
        // Prepare and execute data query
        if (count($params) > 0) {
            $dataStmt = $conn->prepare($dataSQL);
            $dataStmt->bind_param($paramTypes, ...$params);
            $dataStmt->execute();
            $result = $dataStmt->get_result();
        } else {
            $result = $conn->query($dataSQL);
        }
        
        if ($result && $result->num_rows > 0) {
            // Display data in a table
            echo "<table>";
            
            // Table headers with sorting links
            echo "<tr>";
            $fields = ['id', 'tds_in', 'tds_out', 'flow_in', 'waste_flow', 'flow_out', 'reading_time'];
            
            foreach ($fields as $field) {
                $newOrder = ($field == $sortField && $sortOrder == 'DESC') ? 'asc' : 'desc';
                $sortClass = ($field == $sortField) ? ($sortOrder == 'DESC' ? 'sort-desc' : 'sort-asc') : '';
                
                echo "<th class='sort-icon $sortClass'>";
                
                // Build the sort URL preserving filters
                $sortUrl = "?sort=" . $field . "&order=" . $newOrder;
                if (!empty($dateFilter)) $sortUrl .= "&date=" . urlencode($dateFilter);
                if (!empty($minTdsIn)) $sortUrl .= "&min_tds_in=" . urlencode($minTdsIn);
                if (!empty($maxTdsIn)) $sortUrl .= "&max_tds_in=" . urlencode($maxTdsIn);
                if (!empty($minTdsOut)) $sortUrl .= "&min_tds_out=" . urlencode($minTdsOut);
                if (!empty($maxTdsOut)) $sortUrl .= "&max_tds_out=" . urlencode($maxTdsOut);
                if ($currentPage > 1) $sortUrl .= "&page=" . $currentPage;
                
                echo "<a href='$sortUrl'>" . formatFieldLabel($field) . "</a>";
                echo "</th>";
            }
            echo "</tr>";
            
            // Table data
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($fields as $field) {
                    if ($field == 'reading_time') {
                        // Format the timestamp
                        echo "<td>" . date('Y-m-d H:i:s', strtotime($row[$field])) . "</td>";
                    } else {
                        $value = $row[$field];
                        // Format numeric values
                        if (is_numeric($value) && $field != 'id') {
                            $value = number_format((float)$value, 2);
                        }
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                }
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Pagination
            if ($totalPages > 1) {
                echo "<div class='pagination'>";
                
                // Build the base pagination URL preserving filters and sorting
                $paginationUrl = "?sort=" . urlencode($sortField) . "&order=" . urlencode($sortOrder);
                if (!empty($dateFilter)) $paginationUrl .= "&date=" . urlencode($dateFilter);
                if (!empty($minTdsIn)) $paginationUrl .= "&min_tds_in=" . urlencode($minTdsIn);
                if (!empty($maxTdsIn)) $paginationUrl .= "&max_tds_in=" . urlencode($maxTdsIn);
                if (!empty($minTdsOut)) $paginationUrl .= "&min_tds_out=" . urlencode($minTdsOut);
                if (!empty($maxTdsOut)) $paginationUrl .= "&max_tds_out=" . urlencode($maxTdsOut);
                
                // Previous page link
                if ($currentPage > 1) {
                    echo "<a href='" . $paginationUrl . "&page=" . ($currentPage - 1) . "'>&laquo; Previous</a>";
                }
                
                // Page links
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if ($startPage > 1) {
                    echo "<a href='" . $paginationUrl . "&page=1'>1</a>";
                    if ($startPage > 2) {
                        echo "<span>...</span>";
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i == $currentPage) {
                        echo "<span class='active'>$i</span>";
                    } else {
                        echo "<a href='" . $paginationUrl . "&page=$i'>$i</a>";
                    }
                }
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo "<span>...</span>";
                    }
                    echo "<a href='" . $paginationUrl . "&page=$totalPages'>$totalPages</a>";
                }
                
                // Next page link
                if ($currentPage < $totalPages) {
                    echo "<a href='" . $paginationUrl . "&page=" . ($currentPage + 1) . "'>Next &raquo;</a>";
                }
                
                echo "</div>";
            }
            
        } else {
            // No records found
            echo "<div class='no-data'>";
            echo "<p>No records found matching your criteria.</p>";
            echo "</div>";
        }
        
        // Close the database connection
        $conn->close();
        
    } catch(Exception $e) {
        echo "<div class='no-data'>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>
    
    <div class="nav">
        <a href="/">Return to Home</a>
    </div>
    
    <script>
        // Set minimum date input to the current date
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            if (dateInput && !dateInput.value) {
                const today = new Date();
                const year = today.getFullYear();
                let month = today.getMonth() + 1;
                let day = today.getDate();
                
                // Add leading zeros if needed
                month = month < 10 ? '0' + month : month;
                day = day < 10 ? '0' + day : day;
                
                // Set the max date to today
                dateInput.max = `${year}-${month}-${day}`;
            }
        });
    </script>
</body>
</html>