/*
 * Water Quality Monitoring System
 * 
 * This sketch reads data from 2 TDS sensors and 2 flow sensors,
 * displays the readings on a 16x2 LCD display,
 * and sends the data to an ESP8266 for database storage.
 * 
 * Hardware:
 * - Arduino UNO
 * - 16x2 LCD Display
 * - 2x TDS Sensors
 * - 2x Flow Sensors
 */

#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <SoftwareSerial.h>

// LCD Configuration for I2C - typical address is 0x27 or 0x3F
LiquidCrystal_I2C lcd(0x27, 16, 2); // Set the LCD address to 0x27 for a 16 chars and 2 line display

// Software Serial for ESP8266 communication
SoftwareSerial espSerial(8, 9); // RX, TX

// TDS Sensor Pins
#define TDS_SENSOR_1 A0
#define TDS_SENSOR_2 A1

// Flow Sensor Pins
#define FLOW_SENSOR_1 2  // FLOW IN sensor (uses interrupt 0)
#define FLOW_SENSOR_2 3  // WASTE FLOW sensor (uses interrupt 1)

// Sensor calibration values
#define TDS_VREF 5.0      // Analog reference voltage
#define TDS_TEMPERATURE 25 // Default temperature for calibration

// Flow sensor constants
volatile int flow1_pulse_count = 0;
volatile int flow2_pulse_count = 0;
unsigned long current_time = 0;
unsigned long previous_time = 0;
const unsigned long SENSOR_READ_INTERVAL = 1000; // Read sensors every 1 second

// Display variables
unsigned long display_previous_time = 0;
const unsigned long DISPLAY_INTERVAL = 2000; // Change display every 2 seconds
int display_state = 0;

// Calculated values
float tds1_value = 0.0;
float tds2_value = 0.0;
float flow1_rate = 0.0; // FLOW IN (L/min)
float flow2_rate = 0.0; // WASTE FLOW (L/min)
float flow_out = 0.0;   // FLOW OUT (FLOW IN - WASTE FLOW)

// Sensor read flag
bool new_reading = false;

void setup() {
  // Initialize I2C
  Wire.begin();
  
  // Initialize LCD
  lcd.init();                      // Initialize the LCD
  lcd.backlight();                 // Turn on the backlight
  lcd.print("Water Quality");
  lcd.setCursor(0, 1);
  lcd.print("Monitor System");
  
  // Initialize serial communications
  Serial.begin(9600);
  espSerial.begin(9600);
  
  // Initialize flow sensor pins
  pinMode(FLOW_SENSOR_1, INPUT);
  pinMode(FLOW_SENSOR_2, INPUT);
  digitalWrite(FLOW_SENSOR_1, HIGH); // Enable internal pull-up
  digitalWrite(FLOW_SENSOR_2, HIGH); // Enable internal pull-up
  
  // Attach interrupts for flow sensors
  attachInterrupt(0, flow1_pulse_counter, FALLING);
  attachInterrupt(1, flow2_pulse_counter, FALLING);
  
  delay(2000); // Show intro message for 2 seconds
  lcd.clear();
}

void loop() {
  current_time = millis();
  
  // Read sensors every second
  if (current_time - previous_time >= SENSOR_READ_INTERVAL) {
    readSensors();
    previous_time = current_time;
    new_reading = true;
  }
  
  // Update display every 2 seconds
  if (current_time - display_previous_time >= DISPLAY_INTERVAL) {
    updateDisplay();
    display_previous_time = current_time;
  }
  
  // Send data to ESP8266 if there's a new reading
  if (new_reading) {
    sendDataToESP();
    new_reading = false;
  }
}

void readSensors() {
  // Read TDS sensors
  int tds1_raw = analogRead(TDS_SENSOR_1);
  int tds2_raw = analogRead(TDS_SENSOR_2);
  
  // Convert to voltage
  float tds1_voltage = tds1_raw * (TDS_VREF / 1024.0);
  float tds2_voltage = tds2_raw * (TDS_VREF / 1024.0);
  
  // Temperature compensation formula: fFinalResult(25^C) = fFinalResult(current)/(1.0+0.02*(fTP-25.0));
  float compensation_coefficient = 1.0 + 0.02 * (TDS_TEMPERATURE - 25.0);
  
  // Compensate voltage
  float tds1_voltage_comp = tds1_voltage / compensation_coefficient;
  float tds2_voltage_comp = tds2_voltage / compensation_coefficient;
  
  // Convert to TDS value
  // TDS = (133.42 * voltage³ - 255.86 * voltage² + 857.39 * voltage) * 0.5
  tds1_value = (133.42 * pow(tds1_voltage_comp, 3) - 255.86 * pow(tds1_voltage_comp, 2) + 857.39 * tds1_voltage_comp) * 0.5;
  tds2_value = (133.42 * pow(tds2_voltage_comp, 3) - 255.86 * pow(tds2_voltage_comp, 2) + 857.39 * tds2_voltage_comp) * 0.5;
  
  // Calculate flow rates
  // Each sensor has a different calibration factor (pulses per liter)
  // Typical YF-S201 sensor: 7.5 pulses per second = 1 L/min
  flow1_rate = (flow1_pulse_count / 7.5);
  flow2_rate = (flow2_pulse_count / 7.5);
  
  // Calculate FLOW OUT
  flow_out = flow1_rate - flow2_rate;
  if (flow_out < 0) flow_out = 0; // Ensure flow_out doesn't go negative
  
  // Reset pulse counters
  flow1_pulse_count = 0;
  flow2_pulse_count = 0;
}

void updateDisplay() {
  lcd.clear();
  
  switch(display_state) {
    case 0: // TDS IN
      lcd.print("TDS IN:");
      lcd.setCursor(0, 1);
      lcd.print(tds1_value, 1);
      lcd.print(" PPM");
      break;
      
    case 1: // TDS OUT
      lcd.print("TDS OUT:");
      lcd.setCursor(0, 1);
      lcd.print(tds2_value, 1);
      lcd.print(" PPM");
      break;
      
    case 2: // FLOW IN
      lcd.print("FLOW IN:");
      lcd.setCursor(0, 1);
      lcd.print(flow1_rate, 2);
      lcd.print(" L/min");
      break;
      
    case 3: // WASTE FLOW
      lcd.print("WASTE FLOW:");
      lcd.setCursor(0, 1);
      lcd.print(flow2_rate, 2);
      lcd.print(" L/min");
      break;
      
    case 4: // FLOW OUT
      lcd.print("FLOW OUT:");
      lcd.setCursor(0, 1);
      lcd.print(flow_out, 2);
      lcd.print(" L/min");
      break;
  }
  
  // Increment display state
  display_state = (display_state + 1) % 5;
}

void sendDataToESP() {
  // Format: "TDS1,TDS2,FLOW1,FLOW2,FLOW_OUT"
  espSerial.print(tds1_value);
  espSerial.print(",");
  espSerial.print(tds2_value);
  espSerial.print(",");
  espSerial.print(flow1_rate);
  espSerial.print(",");
  espSerial.print(flow2_rate);
  espSerial.print(",");
  espSerial.println(flow_out);
  
  // Debug to serial monitor
  Serial.print("Sent to ESP: ");
  Serial.print(tds1_value);
  Serial.print(",");
  Serial.print(tds2_value);
  Serial.print(",");
  Serial.print(flow1_rate);
  Serial.print(",");
  Serial.print(flow2_rate);
  Serial.print(",");
  Serial.println(flow_out);
}

// Interrupt Service Routine for Flow Sensor 1
void flow1_pulse_counter() {
  flow1_pulse_count++;
}

// Interrupt Service Routine for Flow Sensor 2
void flow2_pulse_counter() {
  flow2_pulse_count++;
}
