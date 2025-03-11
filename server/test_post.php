<?php
/**
 * Test POST Request for Water Quality System
 * 
 * This file provides a simple form to test database updates
 * by submitting data directly from a web browser.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Water Quality System - Test Data Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: inline-block;
            width: 100px;
            font-weight: bold;
        }
        input[type="number"] {
            width: 150px;
            padding: 5px;
        }
        button {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .success {
            border-color: #2ecc71;
            background-color: #eafaf1;
        }
        .error {
            border-color: #e74c3c;
            background-color: #fdedeb;
        }
    </style>
</head>
<body>
    <h1>Water Quality System - Test Data Submission</h1>
    <p>Use this form to test submitting data to the water_data.php script.</p>

    <form id="testForm" method="post" action="water_data.php">
        <div class="form-group">
            <label for="tds1">TDS IN:</label>
            <input type="number" id="tds1" name="tds1" min="0" max="5000" step="0.01" value="123.45" required>
            <span>PPM</span>
        </div>
        
        <div class="form-group">
            <label for="tds2">TDS OUT:</label>
            <input type="number" id="tds2" name="tds2" min="0" max="5000" step="0.01" value="78.90" required>
            <span>PPM</span>
        </div>
        
        <div class="form-group">
            <label for="flow1">FLOW IN:</label>
            <input type="number" id="flow1" name="flow1" min="0" max="100" step="0.01" value="5.5" required>
            <span>L/min</span>
        </div>
        
        <div class="form-group">
            <label for="flow2">WASTE FLOW:</label>
            <input type="number" id="flow2" name="flow2" min="0" max="100" step="0.01" value="2.2" required>
            <span>L/min</span>
        </div>
        
        <div class="form-group">
            <label for="flow_out">FLOW OUT:</label>
            <input type="number" id="flow_out" name="flow_out" min="0" max="100" step="0.01" value="3.3" required>
            <span>L/min</span>
        </div>
        
        <button type="submit">Submit Data</button>
        <button type="button" id="randomButton">Generate Random Values</button>
    </form>
    
    <div id="result" class="result" style="display: none;"></div>
    
    <h2>Raw HTTP POST Test</h2>
    <p>This tests exactly what the ESP8266 does - sending a raw HTTP POST request.</p>
    
    <div class="form-group">
        <label for="rawData">POST Data:</label>
        <input type="text" id="rawData" style="width: 400px;" value="tds1=123.45&tds2=78.90&flow1=5.5&flow2=2.2&flow_out=3.3">
    </div>
    <button type="button" id="sendRawButton">Send Raw Request</button>
    
    <div id="rawResult" class="result" style="display: none;"></div>
    
    <p><a href="/">Return to Home</a> | <a href="test_db_connection.php">Test Database Connection</a></p>
    
    <script>
        // Handle form submission via AJAX
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var resultDiv = document.getElementById('result');
            
            fetch('water_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                resultDiv.innerHTML = '<h3>Response:</h3><pre>' + data + '</pre>';
                resultDiv.style.display = 'block';
                
                if (data.includes('successfully')) {
                    resultDiv.className = 'result success';
                } else {
                    resultDiv.className = 'result error';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<h3>Error:</h3><pre>' + error + '</pre>';
                resultDiv.style.display = 'block';
                resultDiv.className = 'result error';
            });
        });
        
        // Generate random values
        document.getElementById('randomButton').addEventListener('click', function() {
            document.getElementById('tds1').value = (Math.random() * 500).toFixed(2);
            document.getElementById('tds2').value = (Math.random() * 500).toFixed(2);
            document.getElementById('flow1').value = (Math.random() * 10).toFixed(2);
            document.getElementById('flow2').value = (Math.random() * 5).toFixed(2);
            document.getElementById('flow_out').value = (Math.random() * 5).toFixed(2);
        });
        
        // Send raw HTTP request
        document.getElementById('sendRawButton').addEventListener('click', function() {
            var rawData = document.getElementById('rawData').value;
            var rawResultDiv = document.getElementById('rawResult');
            
            fetch('water_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: rawData
            })
            .then(response => response.text())
            .then(data => {
                rawResultDiv.innerHTML = '<h3>Raw Response:</h3><pre>' + data + '</pre>';
                rawResultDiv.style.display = 'block';
                
                if (data.includes('successfully')) {
                    rawResultDiv.className = 'result success';
                } else {
                    rawResultDiv.className = 'result error';
                }
            })
            .catch(error => {
                rawResultDiv.innerHTML = '<h3>Error:</h3><pre>' + error + '</pre>';
                rawResultDiv.style.display = 'block';
                rawResultDiv.className = 'result error';
            });
        });
    </script>
</body>
</html>