// =====================================================
// ESP8266 RFID + LCD + WiFi + API + AP CONFIG + OTA
// dengan Watchdog & Cooldown (TANPA OFFLINE QUEUE)
// =====================================================

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ESP8266WebServer.h>
#include <ESP8266mDNS.h>
#include <ArduinoOTA.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <EEPROM.h>
#include <Ticker.h>

// =====================================================
// KONFIGURASI UMUM
// =====================================================

// AP Config
const char* AP_SSID = "ABSEN-RFID";
const char* AP_PASS = "12345678";

// OTA Config
const char* OTA_HOSTNAME = "ABSEN-RFID";
const char* OTA_PASSWORD = "04112000";  // Password untuk OTA update

// API
// URL server Laravel (tanpa trailing slash)
// Contoh XAMPP: "http://192.168.1.10/absen/public"
// Contoh artisan serve: "http://192.168.1.10:8000"
const char* API_HOST = "http://192.168.1.10/absen/public";

// EEPROM
#define EEPROM_SIZE   512
#define CONFIG_MAGIC  0xA5

// GPIO
#define SS_PIN     16   // D0
#define RST_PIN     0   // D3 (BOOT PIN)
#define BUZZER_PIN  2   // D4

// I2C
#define I2C_SDA     4   // D2
#define I2C_SCL     5   // D1

// LCD Display Timing
#define RESPONSE_DISPLAY_TIME 3000  // 3 detik tampil response
#define STANDBY_SCROLL_INTERVAL 500 // 500ms untuk scroll text
#define BACKLIGHT_TIMEOUT 10000     // 10 detik timeout backlight

// Cooldown Settings
#define CARD_COOLDOWN_TIME 2000     // 3 detik cooldown untuk kartu yang sama

// Watchdog Settings
#define WATCHDOG_TIMEOUT_SECONDS 30 // 30 detik watchdog timeout

// =====================================================
// STRUCT & OBJECT
// =====================================================

struct Config {
  char ssid[32];
  char pass[32];
  char apiKey[65];
  uint8_t magic;
};

Config cfg;

bool bootWifiOK = false;
bool isProcessing = false;
unsigned long lastActivity = 0;
bool backlightOn = true;
bool otaInProgress = false;

// Cooldown tracking
String lastScannedUID = "";
unsigned long lastScanTime = 0;

// Watchdog
Ticker watchdogTicker;
volatile bool watchdogFlag = false;

// LCD State Management
enum LcdState {
  LCD_BOOT,
  LCD_AP_MODE,
  LCD_STANDBY,
  LCD_PROCESSING,
  LCD_SUCCESS,
  LCD_ERROR,
  LCD_OTA_UPDATE
};

LcdState currentLcdState = LCD_BOOT;
unsigned long lcdStateChangeTime = 0;
unsigned long lastStandbyUpdate = 0;
int standbyScrollPos = 0;

ESP8266WebServer server(80);
LiquidCrystal_I2C lcd(0x27, 16, 2);
MFRC522 rfid(SS_PIN, RST_PIN);

// =====================================================
// WATCHDOG FUNCTIONS
// =====================================================

void IRAM_ATTR watchdogISR() {
  watchdogFlag = true;
}

void feedWatchdog() {
  watchdogFlag = false;
}

void setupWatchdog() {
  watchdogTicker.attach(WATCHDOG_TIMEOUT_SECONDS, watchdogISR);
  Serial.println("[WD] Watchdog initialized (" + String(WATCHDOG_TIMEOUT_SECONDS) + "s timeout)");
}

void checkWatchdog() {
  // Jangan restart saat OTA
  if (otaInProgress) {
    feedWatchdog();
    return;
  }
  
  if (watchdogFlag) {
    Serial.println("[WD] !!! WATCHDOG TRIGGERED - System Hung !!!");
    Serial.println("[WD] Restarting in 3 seconds...");
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("SYSTEM RESTART");
    lcd.setCursor(0, 1);
    lcd.print("Watchdog Trigger");
    
    delay(3000);
    ESP.restart();
  }
}

// =====================================================
// OTA FUNCTIONS
// =====================================================

void setupOTA() {
  ArduinoOTA.setHostname(OTA_HOSTNAME);
  ArduinoOTA.setPassword(OTA_PASSWORD);
  
  ArduinoOTA.onStart([]() {
    otaInProgress = true;
    String type;
    if (ArduinoOTA.getCommand() == U_FLASH) {
      type = "sketch";
    } else {
      type = "filesystem";
    }
    
    Serial.println("\n[OTA] Start updating " + type);
    
    setLcdState(LCD_OTA_UPDATE);
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("OTA UPDATE");
    lcd.setCursor(0, 1);
    lcd.print("Starting...");
  });
  
  ArduinoOTA.onEnd([]() {
    Serial.println("\n[OTA] Update Complete!");
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("OTA COMPLETE");
    lcd.setCursor(0, 1);
    lcd.print("Restarting...");
    
    // Buzzer beep success
    for (int i = 0; i < 3; i++) {
      digitalWrite(BUZZER_PIN, HIGH);
      delay(100);
      digitalWrite(BUZZER_PIN, LOW);
      delay(100);
    }
  });
  
  ArduinoOTA.onProgress([](unsigned int progress, unsigned int total) {
    unsigned int percent = (progress / (total / 100));
    Serial.printf("[OTA] Progress: %u%%\r", percent);
    
    lcd.setCursor(0, 1);
    lcd.print("Progress: ");
    lcd.print(percent);
    lcd.print("%   ");
    
    // Feed watchdog selama OTA
    feedWatchdog();
  });
  
  ArduinoOTA.onError([](ota_error_t error) {
    otaInProgress = false;
    Serial.printf("\n[OTA] Error[%u]: ", error);
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("OTA ERROR!");
    
    if (error == OTA_AUTH_ERROR) {
      Serial.println("Auth Failed");
      lcd.setCursor(0, 1);
      lcd.print("Auth Failed");
    } else if (error == OTA_BEGIN_ERROR) {
      Serial.println("Begin Failed");
      lcd.setCursor(0, 1);
      lcd.print("Begin Failed");
    } else if (error == OTA_CONNECT_ERROR) {
      Serial.println("Connect Failed");
      lcd.setCursor(0, 1);
      lcd.print("Connect Failed");
    } else if (error == OTA_RECEIVE_ERROR) {
      Serial.println("Receive Failed");
      lcd.setCursor(0, 1);
      lcd.print("Receive Failed");
    } else if (error == OTA_END_ERROR) {
      Serial.println("End Failed");
      lcd.setCursor(0, 1);
      lcd.print("End Failed");
    }
    
    // Buzzer beep error
    for (int i = 0; i < 5; i++) {
      digitalWrite(BUZZER_PIN, HIGH);
      delay(200);
      digitalWrite(BUZZER_PIN, LOW);
      delay(200);
    }
    
    delay(3000);
    showStandbyDisplay();
  });
  
  ArduinoOTA.begin();
  Serial.println("[OTA] Ready");
  Serial.println("[OTA] Hostname: " + String(OTA_HOSTNAME));
  Serial.println("[OTA] IP: " + WiFi.localIP().toString());
}

// =====================================================
// COOLDOWN FUNCTIONS
// =====================================================

bool checkCooldown(String uid) {
  unsigned long currentTime = millis();
  
  // Jika kartu berbeda, allow
  if (lastScannedUID != uid) {
    lastScannedUID = uid;
    lastScanTime = currentTime;
    return true;
  }
  
  // Kartu sama, cek cooldown
  if (currentTime - lastScanTime < CARD_COOLDOWN_TIME) {
    unsigned long remainingTime = (CARD_COOLDOWN_TIME - (currentTime - lastScanTime)) / 1000;
    Serial.println("[COOLDOWN] Card blocked for " + String(remainingTime) + " more seconds");
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("TUNGGU");
    lcd.setCursor(0, 1);
    lcd.print("Cooldown: " + String(remainingTime) + "s");
    
    // Buzzer beep pendek
    digitalWrite(BUZZER_PIN, HIGH);
    delay(100);
    digitalWrite(BUZZER_PIN, LOW);
    
    delay(1000);
    return false;
  }
  
  // Cooldown selesai
  lastScanTime = currentTime;
  return true;
}

// =====================================================
// EEPROM
// =====================================================

void loadConfig() {
  EEPROM.get(0, cfg);
  if (cfg.magic != CONFIG_MAGIC) {
    memset(&cfg, 0, sizeof(cfg));
  }
}

void saveConfig() {
  cfg.magic = CONFIG_MAGIC;
  EEPROM.put(0, cfg);
  EEPROM.commit();
}

// =====================================================
// LCD DISPLAY FUNCTIONS
// =====================================================

void resetActivityTimer() {
  lastActivity = millis();
  if (!backlightOn) {
    lcd.backlight();
    backlightOn = true;
    Serial.println("[LCD] Backlight ON - Activity detected");
  }
}

void checkBacklightTimeout() {
  if (!bootWifiOK || currentLcdState == LCD_PROCESSING || 
      currentLcdState == LCD_SUCCESS || currentLcdState == LCD_ERROR ||
      currentLcdState == LCD_OTA_UPDATE) {
    return;
  }
  
  if (backlightOn && (millis() - lastActivity >= BACKLIGHT_TIMEOUT)) {
    lcd.noBacklight();
    backlightOn = false;
    Serial.println("[LCD] Backlight OFF - Timeout (10s idle)");
  }
}

void updateLcdDisplay() {
  checkBacklightTimeout();
  
  if ((currentLcdState == LCD_SUCCESS || currentLcdState == LCD_ERROR) &&
      millis() - lcdStateChangeTime >= RESPONSE_DISPLAY_TIME) {
    setLcdState(LCD_STANDBY);
  }
  
  if (currentLcdState == LCD_STANDBY) {
    updateStandbyDisplay();
  }
}

void setLcdState(LcdState newState) {
  if (currentLcdState != newState) {
    currentLcdState = newState;
    lcdStateChangeTime = millis();
    standbyScrollPos = 0;
    lastStandbyUpdate = 0;
  }
}

void updateStandbyDisplay() {
  if (millis() - lastStandbyUpdate < STANDBY_SCROLL_INTERVAL) {
    return;
  }
  
  lastStandbyUpdate = millis();
  lcd.clear();
  
  // Baris 1: Teks standar
  lcd.setCursor(0, 0);
  lcd.print("SISTEM ABSENSI");
  
  // Baris 2: Scrolling text
  lcd.setCursor(0, 1);
  String scrollText = "Tempel Kartu Anda   ";
  int textLen = scrollText.length();
  
  String displayText = "";
  for (int i = 0; i < 16; i++) {
    displayText += scrollText[(standbyScrollPos + i) % textLen];
  }
  
  lcd.print(displayText);
  
  standbyScrollPos++;
  if (standbyScrollPos >= textLen) {
    standbyScrollPos = 0;
  }
}

void showStandbyDisplay() {
  setLcdState(LCD_STANDBY);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("SISTEM ABSENSI");
  lcd.setCursor(0, 1);
  lcd.print("Tempel Kartu Anda   ");
}

void showProcessingDisplay() {
  setLcdState(LCD_PROCESSING);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("MEMPROSES DATA");
  lcd.setCursor(0, 1);
  lcd.print("Tunggu sebentar");
}

void showSuccessDisplay(const char* nama, const char* type = "") {
  setLcdState(LCD_SUCCESS);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  
  // Tampilkan tipe absen di baris pertama
  String typeStr = String(type);
  typeStr.toLowerCase();
  if (typeStr == "absen_masuk") {
    lcd.print("ABSEN MASUK");
  } else if (typeStr == "absen_pulang") {
    lcd.print("ABSEN PULANG");
  } else if (typeStr == "sudah_absen_masuk") {
    lcd.print("ABSEN MASUK");
  } else if (typeStr == "sudah_lengkap") {
    lcd.print("ABSEN LENGKAP");
  } else if (typeStr == "enroll_rfid") {
    lcd.print("ENROLL SUKSES");
  } else {
    lcd.print("ABSEN BERHASIL");
  }
  
  lcd.setCursor(0, 1);
  
  String namaStr = String(nama);
  if (namaStr.length() > 16) {
    namaStr = namaStr.substring(0, 13) + "...";
  }
  lcd.print(namaStr);
  
  // Buzzer berdasarkan field 'sound' atau 'type' dari backend
  // sound: "ok" = sukses, "warning" = peringatan, "gagal/error" = gagal
  if (typeStr == "absen_masuk" || typeStr == "absen_masuk_guru") {
    // 2 beep pendek — masuk
    digitalWrite(BUZZER_PIN, HIGH); delay(100); digitalWrite(BUZZER_PIN, LOW);
    delay(50);
    digitalWrite(BUZZER_PIN, HIGH); delay(100); digitalWrite(BUZZER_PIN, LOW);
  } else if (typeStr == "absen_pulang" || typeStr == "absen_pulang_guru") {
    // 1 beep panjang — pulang
    digitalWrite(BUZZER_PIN, HIGH); delay(400); digitalWrite(BUZZER_PIN, LOW);
  } else if (typeStr == "gate_opened") {
    // 3 beep cepat — gerbang dibuka
    for (int i = 0; i < 3; i++) {
      digitalWrite(BUZZER_PIN, HIGH); delay(80); digitalWrite(BUZZER_PIN, LOW); delay(80);
    }
  } else {
    // Default beep
    digitalWrite(BUZZER_PIN, HIGH); delay(100); digitalWrite(BUZZER_PIN, LOW);
    delay(50);
    digitalWrite(BUZZER_PIN, HIGH); delay(100); digitalWrite(BUZZER_PIN, LOW);
  }
}
void showErrorDisplay(const char* message) {
  setLcdState(LCD_ERROR);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ABSEN GAGAL!");
  lcd.setCursor(0, 1);
  
  String msgStr = String(message);
  if (msgStr.length() > 16) {
    msgStr = msgStr.substring(0, 13) + "...";
  }
  lcd.print(msgStr);
  
  // Buzzer beep - error
  digitalWrite(BUZZER_PIN, HIGH);
  delay(500);
  digitalWrite(BUZZER_PIN, LOW);
}

void showNetworkErrorDisplay(const char* errorType) {
  setLcdState(LCD_ERROR);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ERROR JARINGAN");
  lcd.setCursor(0, 1);
  lcd.print(errorType);
  
  for (int i = 0; i < 3; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(150);
    digitalWrite(BUZZER_PIN, LOW);
    delay(150);
  }
}

// =====================================================
// WEB CONFIG (AP MODE)
// =====================================================

void setupWebConfig() {
  server.on("/", []() {
    String html = "<!DOCTYPE html><html><head>";
    html += "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    html += "<title>Setup Absen RFID</title>";
    html += "<style>";
    html += "body{font-family:Arial;margin:20px;background:#f0f0f0;}";
    html += "form{background:white;padding:20px;border-radius:8px;max-width:400px;margin-bottom:20px;}";
    html += "input{width:100%;padding:8px;margin:5px 0 15px;box-sizing:border-box;}";
    html += "button{background:#4CAF50;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;width:100%;}";
    html += "button:hover{background:#45a049;}";
    html += ".info{background:#e7f3fe;padding:10px;border-left:4px solid #2196F3;margin:10px 0;}";
    html += ".ota{background:#fff3cd;padding:10px;border-left:4px solid #ffc107;margin:10px 0;}";
    html += "</style>";
    html += "</head><body>";

    html += "<h2>🔧 Setup Absen RFID</h2>";
    
    html += "<div class='info'>";
    if (bootWifiOK) {
      html += "<strong>Status:</strong> WiFi Connected<br>";
      html += "<strong>SSID:</strong> " + String(cfg.ssid) + "<br>";
      html += "<strong>IP:</strong> " + WiFi.localIP().toString() + "<br>";
    } else {
      html += "<strong>Status:</strong> AP Mode<br>";
      html += "<strong>AP IP:</strong> " + WiFi.softAPIP().toString() + "<br>";
    }
    html += "</div>";
    
    if (bootWifiOK) {
      html += "<div class='ota'>";
      html += "🔄 <strong>OTA Update tersedia!</strong><br>";
      html += "Hostname: <strong>" + String(OTA_HOSTNAME) + "</strong><br>";
      html += "Gunakan Arduino IDE atau platformio untuk upload firmware.<br>";
      html += "Password OTA: <strong>" + String(OTA_PASSWORD) + "</strong>";
      html += "</div>";
    }
    
    html += "<form method='POST' action='/save'>";
    html += "SSID WiFi:<br>";
    html += "<input name='ssid' value='" + String(cfg.ssid) + "' required><br>";
    html += "Password WiFi:<br>";
    html += "<input name='pass' type='password' value='" + String(cfg.pass) + "'><br>";
    html += "API Key:<br>";
    html += "<input name='apikey' value='" + String(cfg.apiKey) + "' required><br>";
    html += "<button type='submit'>💾 Simpan & Restart</button>";
    html += "</form>";

    html += "</body></html>";
    server.send(200, "text/html", html);
  });

  server.on("/save", []() {
    strncpy(cfg.ssid, server.arg("ssid").c_str(), sizeof(cfg.ssid) - 1);
    cfg.ssid[sizeof(cfg.ssid) - 1] = '\0';
    strncpy(cfg.pass, server.arg("pass").c_str(), sizeof(cfg.pass) - 1);
    cfg.pass[sizeof(cfg.pass) - 1] = '\0';
    strncpy(cfg.apiKey, server.arg("apikey").c_str(), sizeof(cfg.apiKey) - 1);
    cfg.apiKey[sizeof(cfg.apiKey) - 1] = '\0';
    saveConfig();

    String html = "<!DOCTYPE html><html><head>";
    html += "<meta http-equiv='refresh' content='3;url=/'>";
    html += "</head><body>";
    html += "<h3 style='color:green;'>✓ Berhasil disimpan!</h3>";
    html += "<p>ESP akan restart dalam 3 detik...</p>";
    html += "</body></html>";
    server.send(200, "text/html", html);

    delay(1500);
    ESP.restart();
  });

  server.begin();
}

// =====================================================
// SETUP
// =====================================================

void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.println("\n\n=================================");
  Serial.println("  ESP8266 RFID ABSENSI v2.1");
  Serial.println("  Watchdog + Cooldown + OTA");
  Serial.println("=================================\n");

  EEPROM.begin(EEPROM_SIZE);
  loadConfig();

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  pinMode(RST_PIN, OUTPUT);
  digitalWrite(RST_PIN, HIGH);

  Wire.begin(I2C_SDA, I2C_SCL);
  lcd.init();
  lcd.backlight();
  backlightOn = true;
  lastActivity = millis();

  // Initialize Watchdog
  setupWatchdog();

  setLcdState(LCD_BOOT);
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("SISTEM ABSENSI");
  lcd.setCursor(0, 1);
  lcd.print("Booting v2.1...");
  delay(1500);

  SPI.begin();
  rfid.PCD_Init();
  Serial.println("[RFID] Initialized");

  // ===== WIFI BOOT =====
  WiFi.mode(WIFI_STA);

  if (strlen(cfg.ssid) > 0) {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Connecting WiFi");
    lcd.setCursor(0, 1);
    lcd.print(cfg.ssid);
    
    WiFi.begin(cfg.ssid, cfg.pass);
  }

  unsigned long start = millis();
  int dots = 0;
  while (WiFi.status() != WL_CONNECTED && millis() - start < 15000) {
    feedWatchdog();
    delay(500);
    lcd.setCursor(15, 1);
    lcd.print(".");
    dots++;
    if (dots > 3) {
      lcd.setCursor(13, 1);
      lcd.print("   ");
      dots = 0;
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    bootWifiOK = true;

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Connected");
    lcd.setCursor(0, 1);
    lcd.print(WiFi.localIP());
    
    Serial.println("\n[WiFi] Connected");
    Serial.print("[WiFi] IP: ");
    Serial.println(WiFi.localIP());
    Serial.print("[WiFi] SSID: ");
    Serial.println(cfg.ssid);
    
    // Setup mDNS
    if (MDNS.begin(OTA_HOSTNAME)) {
      Serial.println("[mDNS] Responder started");
      Serial.println("[mDNS] Hostname: " + String(OTA_HOSTNAME) + ".local");
    }
    
    // Setup OTA
    setupOTA();
    
    delay(2000);
    showStandbyDisplay();

  } else {
    bootWifiOK = false;

    WiFi.disconnect(true);
    WiFi.mode(WIFI_AP);
    WiFi.softAP(AP_SSID, AP_PASS);

    setLcdState(LCD_AP_MODE);
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("MODE CONFIG");
    lcd.setCursor(0, 1);
    lcd.print(WiFi.softAPIP());
    
    Serial.println("\n[WiFi] AP Mode");
    Serial.print("[WiFi] SSID: ");
    Serial.println(AP_SSID);
    Serial.print("[WiFi] IP: ");
    Serial.println(WiFi.softAPIP());
  }
  
  setupWebConfig();
  
  Serial.println("\n[SYSTEM] Ready!\n");
}

// =====================================================
// LOOP
// =====================================================

void loop() {
  // Feed watchdog di awal loop
  feedWatchdog();
  
  // Check watchdog status
  checkWatchdog();

  // Handle OTA
  if (bootWifiOK) {
    ArduinoOTA.handle();
    MDNS.update();
  }

  server.handleClient();
  updateLcdDisplay();

  // Jika OTA sedang berjalan, skip RFID processing
  if (otaInProgress) {
    return;
  }

  if (!bootWifiOK) {
    delay(100);
    return;
  }

  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    static unsigned long lastReconnect = 0;
    if (millis() - lastReconnect > 30000) {
      Serial.println("[WiFi] Disconnected, reconnecting...");
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("WiFi Terputus");
      lcd.setCursor(0, 1);
      lcd.print("Reconnecting...");
      WiFi.reconnect();
      lastReconnect = millis();
    }
    delay(500);
    return;
  }


  
  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  resetActivityTimer();

  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  Serial.println("\n[RFID] Card Detected: " + uid);

  // Check cooldown
  if (!checkCooldown(uid)) {
    Serial.println("[RFID] Blocked by cooldown");
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    return;
  }

  showProcessingDisplay();
  sendToServer(uid);

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}

// =====================================================
// HTTP API
// =====================================================

void sendToServer(String uid) {
  feedWatchdog(); // Feed sebelum HTTP request

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[HTTP] WiFi not connected");
    showNetworkErrorDisplay("No WiFi");
    return;
  }

  WiFiClient client;
  HTTPClient http;

  // Susun URL endpoint
  String apiUrl = String(API_HOST) + "/api/rfid";

  // Body JSON
  StaticJsonDocument<200> reqDoc;
  reqDoc["api_key"] = cfg.apiKey;
  reqDoc["uid"]     = uid;
  String postData;
  serializeJson(reqDoc, postData);

  Serial.println("[HTTP] Sending to: " + apiUrl);

  http.setTimeout(8000);
  http.begin(client, apiUrl);
  http.addHeader("Content-Type", "application/json");

  int httpCode = http.POST(postData);
  feedWatchdog(); // Feed setelah HTTP response

  Serial.println("[HTTP] Response code: " + String(httpCode));

  if (httpCode == 200) {
    String payload = http.getString();
    
    Serial.println("\n----- RAW SERVER RESPONSE -----");
    Serial.println(payload);
    Serial.println("----- END RAW RESPONSE --------\n");
    
    StaticJsonDocument<512> doc;
    DeserializationError error = deserializeJson(doc, payload);
    
    if (error == DeserializationError::Ok) {
      bool   ok      = doc["ok"].as<bool>();
      const char* status  = doc["status"]  | "";
      const char* message = doc["message"] | "";
      const char* nama    = doc["nama"]    | "";
      const char* type    = doc["type"]    | "";
      const char* sound   = doc["sound"]   | "ok";

      Serial.println("[HTTP] ok=" + String(ok) + " status=" + String(status));
      Serial.println("[HTTP] message=" + String(message));
      Serial.println("[HTTP] nama=" + String(nama) + " type=" + String(type));

      if (ok) {
        showSuccessDisplay(nama, type);
      } else {
        showErrorDisplay(message);
      }
    } else {
      Serial.println("[HTTP] JSON Parse Error: " + String(error.c_str()));
      showErrorDisplay("Format Salah");
    }
  } else if (httpCode > 0) {
    Serial.println("[HTTP] Server error code: " + String(httpCode));
    showNetworkErrorDisplay("Server Error");
  } else {
    Serial.println("[HTTP] Connection failed");
    Serial.println("[HTTP] Error: " + http.errorToString(httpCode));
    showNetworkErrorDisplay("Conn Failed");
  }
  
  http.end();
}