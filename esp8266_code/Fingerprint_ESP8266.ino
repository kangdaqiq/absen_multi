#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <ESP8266WebServer.h>
#include <ArduinoJson.h>
#include <SoftwareSerial.h>
#include <Adafruit_Fingerprint.h>

// ==========================================
// CONFIGURATION
// ==========================================
const char* ssid = "Tarbiyyah Assunniyyah";
const char* password = "AnfiaCollection";

// API Configuration
// PENTING: Sesuaikan api_host dengan cara Anda menjalankan server Laravel
// Opsi 1: Jika pakai "php artisan serve --host 0.0.0.0"
// const char* api_host = "http://192.168.1.X:8000"; 
// Opsi 2: Jika pakai XAMPP (tanpa virtual host)
// const char* api_host = "http://192.168.1.X/absen/public";

// Ganti 192.168.1.X dengan IP Laptop Anda (cek pakai ipconfig)
const char* api_host = "http://192.168.1.227/absen/public"; // Sesuai URL yang bisa diakses user
String api_key = "xlSoXObt6EPXrsiBYOkllYZ83QeV2lp0M63qHiVYLT0mnHwBXgpskzNdNGNh"; // Samakan dengan api_keys di database

// Pin Configuration
// Fingerprint sensor: Green (TX) to D2 (RX), White (RX) to D3 (TX)
SoftwareSerial mySerial(4, 0); 
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

// Globals

ESP8266WebServer server(80);
bool pendingEnroll = false;
int pendingEnrollId = 0;

void setup() {
  Serial.begin(115200);
  delay(100);
  
  Serial.println("\n\n=== Absensi Fingerprint ESP8266 ===");

  // 1. Setup Fingerprint
  finger.begin(57600);
  delay(5);
  if (finger.verifyPassword()) {
    Serial.println("Found fingerprint sensor!");
  } else {
    Serial.println("Did not find fingerprint sensor :(");
    while (1) { delay(1); }
  }

  // 2. Setup WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.print("Connected! IP: ");
  Serial.println(WiFi.localIP());

  // 3. Setup Web Server for Push Notification
  server.on("/enroll-finger", handlePushEnroll);
  server.on("/delete-finger", handleDeleteFinger);
  server.begin();
  Serial.println("Web server started");

  // 4. Send Ping to Register IP
  sendPing();
}

void loop() {
  // Check for Enrollment Request Periodically
  server.handleClient(); // Handle incoming push requests
  
  if (pendingEnroll) {
      pendingEnroll = false;
      handleEnrollment(pendingEnrollId, "Teacher (Push)");
  }



  // Check for Scan
  int fingerId = getFingerprintID();
  if (fingerId != -1) {
    Serial.print("Found ID #"); Serial.println(fingerId);
    sendScanRequest(fingerId);
    delay(2000); // Prevent multiple scans
  }
  
  delay(50);
}

// ==========================================
// FINGERPRINT LOGIC
// ==========================================

int getFingerprintID() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) return -1;

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) return -1;

  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) return -1;

  // Found a match!
  return finger.fingerID;
}

// ==========================================
// API LOGIC
// ==========================================



void handlePushEnroll() {
  if (server.hasArg("id")) {
    String idStr = server.arg("id");
    pendingEnrollId = idStr.toInt();
    pendingEnroll = true;
    server.send(200, "application/json", "{\"status\":\"ok\", \"message\":\"Enrollment started\"}");
    Serial.println("Push Enroll Request Received for ID: " + idStr);
  } else {
    server.send(400, "application/json", "{\"status\":\"error\", \"message\":\"Missing id\"}");
  }
}

void handleEnrollment(int id, String name) {
  Serial.println("Place finger to enroll...");
  
  // --- Check for Duplicate First ---
  Serial.println("Checking for duplicate...");
  int p = -1;
  while (p != FINGERPRINT_OK) {
      p = finger.getImage();
      if (p == FINGERPRINT_NOFINGER) delay(100);
      else if (p != FINGERPRINT_OK) delay(100);
  }
  
  p = finger.image2Tz();
  if (p == FINGERPRINT_OK) {
      p = finger.fingerFastSearch();
      if (p == FINGERPRINT_OK) {
          Serial.println("Fingerprint already exists!");
          Serial.print("Found ID #"); Serial.println(finger.fingerID);
          
          // REVISION: Use this existing ID instead of erroring
          sendEnrollSuccess(finger.fingerID);
          
          delay(2000);
          return; // Stop enrollment (we reused existing ID)
      }
  }
  
  Serial.println("No duplicate found. Proceeding...");
  Serial.println("Remove finger");
  delay(1000);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
    delay(10);
  }
  // ---------------------------------
  
  Serial.println("Place finger again for Image 1...");
  
  // Step 1: Get First Image
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) Serial.print(".");
    else if (p == FINGERPRINT_PACKETRECIEVEERR) Serial.println("Communication error");
    else if (p == FINGERPRINT_IMAGEFAIL) Serial.println("Imaging error");
    delay(100);
  }
  Serial.println("Image taken");
  
  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) {
    Serial.println("Image2Tz 1 failed");
    return;
  }
  
  Serial.println("Remove finger");
  delay(2000);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
    delay(100);
  }
  
  Serial.println("Place same finger again");
  p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) Serial.print(".");
    delay(100);
  }
  Serial.println("Image 2 taken");

  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) {
    Serial.println("Image2Tz 2 failed");
    return;
  }

  // Create Model
  p = finger.createModel();
  if (p == FINGERPRINT_OK) {
    Serial.println("Prints matched!");
  } else {
    Serial.println("Communication error or did not match");
    return;
  }

  // Store Model
  p = finger.storeModel(id);
  if (p == FINGERPRINT_OK) {
    Serial.println("Stored!");
    sendEnrollSuccess(id);
  } else {
    Serial.println("Error storing model");
  }
}

void sendEnrollSuccess(int id) {
  if (WiFi.status() != WL_CONNECTED) return;
  
  WiFiClient client;
  HTTPClient http;
  
  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  
  StaticJsonDocument<200> doc;
  doc["api_key"] = api_key;
  doc["finger_id"] = id;
  doc["enroll_success"] = true;
  String requestBody;
  serializeJson(doc, requestBody);
  
  int httpCode = http.POST(requestBody);
  Serial.print("Enroll Success Sent: "); Serial.println(httpCode);
  http.end();
}

void sendScanRequest(int id) {
  if (WiFi.status() != WL_CONNECTED) return;
  
  WiFiClient client;
  HTTPClient http;
  
  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  
  StaticJsonDocument<200> doc;
  doc["api_key"] = api_key;
  doc["finger_id"] = id;
  String requestBody;
  serializeJson(doc, requestBody);
  
  int httpCode = http.POST(requestBody);
  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Scan Response: " + payload);
    // You can parse payload to show message on LCD if available
  } else {
    Serial.print("Scan Request Error: "); Serial.println(httpCode);
  }
  http.end();
}

void sendPing() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  WiFiClient client;
  HTTPClient http;
  
  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  
  StaticJsonDocument<200> doc;
  doc["api_key"] = api_key;
  doc["ping"] = true;
  String requestBody;
  serializeJson(doc, requestBody);
  
  int httpCode = http.POST(requestBody);
  Serial.print("Ping Sent: "); Serial.println(httpCode);
  if (httpCode > 0) {
      Serial.println(http.getString());
  }
  http.end();
}

void sendEnrollError(int id, String reason) {
  if (WiFi.status() != WL_CONNECTED) return;
  
  WiFiClient client;
  HTTPClient http;
  
  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  
  StaticJsonDocument<200> doc;
  doc["api_key"] = api_key;
  doc["finger_id"] = id;
  doc["enroll_error"] = true;
  doc["message"] = reason;
  String requestBody;
  serializeJson(doc, requestBody);
  
  int httpCode = http.POST(requestBody);
  Serial.print("Enroll Error Sent: "); Serial.println(httpCode);
  http.end();
}

void handleDeleteFinger() {
  if (server.hasArg("id")) {
    int idToDelete = server.arg("id").toInt();
    
    // Delete from Fingerprint Sensor
    int p = finger.deleteModel(idToDelete);
    
    if (p == FINGERPRINT_OK) {
        server.send(200, "application/json", "{\"status\":\"ok\", \"message\":\"Deleted ID " + String(idToDelete) + "\"}");
        Serial.println("Deleted ID " + String(idToDelete));
    } else {
        server.send(500, "application/json", "{\"status\":\"error\", \"message\":\"Failed to delete\"}");
        Serial.print("Failed to delete ID "); Serial.println(idToDelete);
    }
  } else {
    server.send(400, "application/json", "{\"status\":\"error\", \"message\":\"Missing id\"}");
  }
}
