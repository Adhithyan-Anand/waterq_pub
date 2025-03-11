/*
 * ESP8266 WiFi Client for Water Quality Monitoring
 * 
 * This sketch connects to WiFi, receives data from Arduino,
 * and sends it to a MySQL database via HTTP requests.
 * 
 * Hardware:
 * - ESP8266 (NodeMCU or similar)
 * 
 * Library Requirements:
 * - ESP8266WiFi - Part of ESP8266 Core for Arduino
 * - ESP8266HTTPClient - Part of ESP8266 Core for Arduino
 * - WiFiClientSecure - Part of ESP8266 Core for Arduino (optional for HTTPS)
 * 
 * Installation Instructions:
 * 1. In Arduino IDE: Tools > Board > Boards Manager
 * 2. Search for "esp8266" and install "ESP8266 by ESP8266 Community" (version 3.0.2 or newer)
 */

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>

// WiFi credentials
const char* ssid = "MSI";           // Your actual WiFi network name
const char* password = "MSI@2023";  // Your actual WiFi password

// Server details - Using MySQL server at adhithyadb.shop
const char* serverPath = "http://serverip/water_data.php";

// LED Pins - Change these pins based on your ESP8266 board and wiring
const int WIFI_LED_PIN = 5;     // D1 - Blue LED for WiFi status (ON when connected)
const int RECEIVE_LED_PIN = 4;  // D2 - Green LED for data received from Arduino
const int SEND_LED_PIN = 0;     // D3 - Yellow LED for data sent to server

// Variables to store sensor data
float tds1_value = 0.0;
float tds2_value = 0.0;
float flow1_rate = 0.0;
float flow2_rate = 0.0;
float flow_out = 0.0;

// Connection status variables
unsigned long lastConnectionAttempt = 0;
const unsigned long connectionAttemptInterval = 30000; // 30 seconds
bool isWiFiConnected = false;

// Data sending variables
unsigned long lastDataSent = 0;
const unsigned long dataSendInterval = 5000; // 5 seconds delay between data sends
bool newDataReceived = false;

// Reconnection counter for error tracking
int reconnectCount = 0;
const int maxReconnectAttempts = 5;

void setup() {
  // Initialize serial communication for Arduino connection
  Serial.begin(9600);
  Serial.setTimeout(1000); // Set timeout for serial reading
  
  // Wait for serial connection to stabilize
  delay(100);
  
  // Initialize LED pins
  pinMode(WIFI_LED_PIN, OUTPUT);
  pinMode(RECEIVE_LED_PIN, OUTPUT);
  pinMode(SEND_LED_PIN, OUTPUT);
  
  // Turn all LEDs off initially
  digitalWrite(WIFI_LED_PIN, LOW);
  digitalWrite(RECEIVE_LED_PIN, LOW);
  digitalWrite(SEND_LED_PIN, LOW);
  
  // Configure ESP8266 for WiFi
  WiFi.mode(WIFI_STA);   // Set as station mode (client)
  WiFi.setSleepMode(WIFI_NONE_SLEEP); // Disable sleep mode for better stability
  WiFi.setAutoReconnect(true);        // Enable auto-reconnect
  
  // Connect to WiFi
  connectToWiFi();
  
  // Flash all LEDs to indicate boot completed
  for (int i = 0; i < 6; i++) {
    digitalWrite(WIFI_LED_PIN, HIGH);
    digitalWrite(RECEIVE_LED_PIN, HIGH);
    digitalWrite(SEND_LED_PIN, HIGH);
    delay(100);
    digitalWrite(WIFI_LED_PIN, LOW);
    digitalWrite(RECEIVE_LED_PIN, LOW);
    digitalWrite(SEND_LED_PIN, LOW);
    delay(100);
  }
}

void loop() {
  // Check WiFi connection with improved handling
  checkWiFiConnection();
  
  // Receive data from Arduino (with timeout handling)
  if (isWiFiConnected) {
    receiveDataFromArduino();
  }
  
  // Send data to server immediately when new data is received,
  // but not more frequently than dataSendInterval
  if (isWiFiConnected && newDataReceived) {
    unsigned long currentMillis = millis();
    if (currentMillis - lastDataSent >= dataSendInterval) {
      Serial.println("Sending new data to server now...");
      bool sendSuccess = sendDataToServer();
      
      if (sendSuccess) {
        newDataReceived = false;
        lastDataSent = currentMillis;
        Serial.println("Data successfully sent and updated in database.");
      } else {
        // Try again sooner on failure, but not immediately to avoid flooding
        lastDataSent = currentMillis - (dataSendInterval - 1000);
        Serial.println("Data send failed, will retry soon.");
      }
    } else {
      // If we can't send yet due to the interval, show how long until next send
      Serial.print("Waiting to send data: ");
      Serial.print((dataSendInterval - (currentMillis - lastDataSent))/1000);
      Serial.println(" seconds remaining");
    }
  }
  
  // Non-blocking delay
  yield(); // Allow ESP8266 to handle background tasks
}

void checkWiFiConnection() {
  if (WiFi.status() != WL_CONNECTED) {
    if (isWiFiConnected) {
      Serial.println("WiFi connection lost!");
      isWiFiConnected = false;
      
      // Turn off WiFi LED to indicate disconnection
      digitalWrite(WIFI_LED_PIN, LOW);
    }
    
    // Attempt reconnection at intervals
    unsigned long currentMillis = millis();
    if (currentMillis - lastConnectionAttempt >= connectionAttemptInterval) {
      reconnectCount++;
      if (reconnectCount > maxReconnectAttempts) {
        Serial.println("Too many failed reconnect attempts. Restarting ESP...");
        ESP.restart(); // Restart ESP8266 after too many failed attempts
      }
      
      connectToWiFi();
      lastConnectionAttempt = currentMillis;
    }
  } else if (!isWiFiConnected) {
    isWiFiConnected = true;
    reconnectCount = 0; // Reset counter on successful connection
    Serial.println("WiFi Connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
    
    // Turn on WiFi LED to indicate successful connection
    digitalWrite(WIFI_LED_PIN, HIGH);
  }
}

void connectToWiFi() {
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);
  
  // Start connection
  WiFi.begin(ssid, password);
  
  // Wait for connection with timeout and visual feedback
  unsigned long connectionStartTime = millis();
  int dots = 0;
  
  while (WiFi.status() != WL_CONNECTED && millis() - connectionStartTime < 20000) {
    delay(500);
    Serial.print(".");
    dots++;
    
    // Flash LED while connecting
    if (dots % 2 == 0) {
      flashLED(1);
    }
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("");
    Serial.println("WiFi connected");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    Serial.print("Signal strength (RSSI): ");
    Serial.print(WiFi.RSSI());
    Serial.println(" dBm");
    isWiFiConnected = true;
    
    // Show successful connection
    flashLED(2);
  } else {
    Serial.println("");
    Serial.println("WiFi connection failed");
    isWiFiConnected = false;
    
    // Show failed connection
    flashLED(5);
  }
}

void receiveDataFromArduino() {
  if (Serial.available() > 0) {
    String data = Serial.readStringUntil('\n');
    data.trim(); // Remove any whitespace or newline characters
    
    // Ignore empty strings
    if (data.length() == 0) {
      return;
    }
    
    // Debug: Show raw received data
    Serial.print("Raw data received: ");
    Serial.println(data);
    
    // Parse the comma-separated values with improved error handling
    int index1 = data.indexOf(',');
    int index2 = data.indexOf(',', index1 + 1);
    int index3 = data.indexOf(',', index2 + 1);
    int index4 = data.indexOf(',', index3 + 1);
    
    if (index1 > 0 && index2 > index1 && index3 > index2 && index4 > index3) {
      // Extract values with range validation
      String val1 = data.substring(0, index1);
      String val2 = data.substring(index1 + 1, index2);
      String val3 = data.substring(index2 + 1, index3);
      String val4 = data.substring(index3 + 1, index4);
      String val5 = data.substring(index4 + 1);
      
      // Convert to float with validation
      if (isValidNumber(val1) && isValidNumber(val2) && 
          isValidNumber(val3) && isValidNumber(val4) && isValidNumber(val5)) {
        
        tds1_value = val1.toFloat();
        tds2_value = val2.toFloat();
        flow1_rate = val3.toFloat();
        flow2_rate = val4.toFloat();
        flow_out = val5.toFloat();
        
        // Apply basic range validation
        if (tds1_value >= 0 && tds1_value <= 5000 &&
            tds2_value >= 0 && tds2_value <= 5000 &&
            flow1_rate >= 0 && flow1_rate <= 100 &&
            flow2_rate >= 0 && flow2_rate <= 100 &&
            flow_out >= 0 && flow_out <= 100) {
              
          Serial.println("Data validated and processed from Arduino:");
          Serial.print("TDS IN: ");
          Serial.print(tds1_value);
          Serial.print(" PPM, TDS OUT: ");
          Serial.print(tds2_value);
          Serial.print(" PPM, FLOW IN: ");
          Serial.print(flow1_rate);
          Serial.print(" L/min, WASTE FLOW: ");
          Serial.print(flow2_rate);
          Serial.print(" L/min, FLOW OUT: ");
          Serial.print(flow_out);
          Serial.println(" L/min");
          
          // Turn on RECEIVE LED briefly to indicate data received
          digitalWrite(RECEIVE_LED_PIN, HIGH);
          delay(100);
          digitalWrite(RECEIVE_LED_PIN, LOW);
          
          newDataReceived = true;
        } else {
          Serial.println("Data out of valid range, ignoring");
        }
      } else {
        Serial.println("Invalid numeric data received");
      }
    } else {
      Serial.println("Invalid data format received, expected 5 comma-separated values");
    }
  }
}

bool sendDataToServer() {
  // Check WiFi connection status
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;
    
    Serial.println("Sending data to server...");
    
    // Domain name with URL path or IP address with path
    http.begin(client, serverPath);
    
    // Add custom headers
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    http.addHeader("Connection", "close"); // Close connection after response
    
    // Prepare HTTP POST data - use the correct parameter names matching water_data.php
    String httpRequestData = "tds1=" + String(tds1_value, 2) + 
                            "&tds2=" + String(tds2_value, 2) + 
                            "&flow1=" + String(flow1_rate, 2) + 
                            "&flow2=" + String(flow2_rate, 2) + 
                            "&flow_out=" + String(flow_out, 2);
    
    // Set timeout for request
    http.setTimeout(10000); // 10 second timeout
    
    // Turn on SEND LED to indicate data sending in progress
    digitalWrite(SEND_LED_PIN, HIGH);
    
    // Send HTTP POST request
    int httpResponseCode = http.POST(httpRequestData);
    
    // Handle response
    if (httpResponseCode > 0) {
      Serial.print("HTTP Response code: ");
      Serial.println(httpResponseCode);
      
      String response = http.getString();
      Serial.println("Server response: " + response);
      
      // Free resources
      http.end();
      
      // Blink SEND LED to indicate successful send
      for (int i = 0; i < 3; i++) {
        digitalWrite(SEND_LED_PIN, LOW);
        delay(100);
        digitalWrite(SEND_LED_PIN, HIGH);
        delay(100);
      }
      digitalWrite(SEND_LED_PIN, LOW); // Turn off LED after blinking
      
      return true;
    } else {
      Serial.print("HTTP Error code: ");
      Serial.println(httpResponseCode);
      
      // Free resources
      http.end();
      
      // Rapid blink SEND LED to indicate error
      for (int i = 0; i < 5; i++) {
        digitalWrite(SEND_LED_PIN, LOW);
        delay(50);
        digitalWrite(SEND_LED_PIN, HIGH);
        delay(50);
      }
      digitalWrite(SEND_LED_PIN, LOW); // Turn off LED after blinking
      
      return false;
    }
  } else {
    Serial.println("WiFi not connected, cannot send data");
    return false;
  }
}

// Utility function to check if a string contains a valid number
bool isValidNumber(String str) {
  if (str.length() == 0) return false;
  
  bool decimalPointFound = false;
  
  for (unsigned int i = 0; i < str.length(); i++) {
    char c = str.charAt(i);
    
    // First character can be a minus sign for negative numbers
    if (i == 0 && c == '-') {
      continue;
    }
    
    // Check for decimal point (allow only one)
    if (c == '.') {
      if (decimalPointFound) return false;
      decimalPointFound = true;
      continue;
    }
    
    // All other characters must be digits
    if (!isdigit(c)) {
      return false;
    }
  }
  
  return true;
}

// Visual feedback function using built-in LED (GPIO 2 on many ESP8266 boards)
// Adjust pin if your board uses a different pin for the built-in LED
void flashLED(int times) {
  const int ledPin = 2; // Built-in LED on most ESP8266 modules (LOW = ON)
  pinMode(ledPin, OUTPUT);
  
  for (int i = 0; i < times; i++) {
    digitalWrite(ledPin, LOW);  // LED on
    delay(100);
    digitalWrite(ledPin, HIGH); // LED off
    delay(100);
  }
}
