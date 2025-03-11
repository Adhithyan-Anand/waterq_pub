<?php
/**
 * Test script to simulate ESP8266 data submission
 * 
 * This script simulates HTTP POST requests that would
 * normally come from your ESP8266 device.
 */

// Sample data (simulating sensor readings)
$tds_in = 432.5;      // TDS input value in PPM
$tds_out = 125.8;     // TDS output value in PPM
$flow_in = 4.75;      // Flow input in L/min
$waste_flow = 1.25;   // Waste flow in L/min
$flow_out = 3.50;     // Flow output in L/min (calculated as flow_in - waste_flow)

// Target URL (your water_data.php script)
// When running on localhost, use this URL
$url = 'http://' . $_SERVER['HTTP_HOST'] . '/water_data.php';

// Initialize cURL session
$ch = curl_init($url);

// Prepare POST data
$postData = [
    'tds1' => $tds_in,
    'tds2' => $tds_out,
    'flow1' => $flow_in,
    'flow2' => $waste_flow,
    'flow_out' => $flow_out
];

// Set cURL options
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL request
$response = curl_exec($ch);

// Check for errors
if(curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    echo 'Server Response:<br>';
    echo $response;
    
    echo '<br><br>Data sent to server:<br>';
    echo 'TDS IN: ' . $tds_in . ' PPM<br>';
    echo 'TDS OUT: ' . $tds_out . ' PPM<br>';
    echo 'FLOW IN: ' . $flow_in . ' L/min<br>';
    echo 'WASTE FLOW: ' . $waste_flow . ' L/min<br>';
    echo 'FLOW OUT: ' . $flow_out . ' L/min<br>';
}

// Close cURL session
curl_close($ch);
?>

<p>
<a href="test_view_data.php">View Stored Data</a>
</p>