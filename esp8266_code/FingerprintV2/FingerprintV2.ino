// =====================================================
// ESP8266 Fingerprint + LCD + RTC + Offline Queue + OTA
// Buzzer Only | LittleFS Queue | DS3231 RTC
// Library: RTClib, LittleFS, ArduinoJson, Adafruit_Fingerprint,
//          LiquidCrystal_I2C, ESP8266WiFi, ArduinoOTA
// =====================================================

#include <Adafruit_Fingerprint.h>
#include <ArduinoJson.h>
#include <ArduinoOTA.h>
#include <EEPROM.h>
#include <ESP8266HTTPClient.h>
#include <ESP8266WebServer.h>
#include <ESP8266WiFi.h>
#include <ESP8266httpUpdate.h>
#include <ESP8266mDNS.h>
#include <LiquidCrystal_I2C.h>
#include <LittleFS.h>
#include <RTClib.h>
#include <SPI.h>
#include <SoftwareSerial.h>
#include <Ticker.h>
#include <WiFiClientSecure.h>
#include <Wire.h>
#include <time.h>

// =====================================================
// KONFIGURASI
// =====================================================
const char *AP_SSID = "ABSEN-FINGER";
const char *AP_PASS = "12345678";
const char *OTA_HOSTNAME = "ABSEN-FINGER";
const char *OTA_PASSWORD = "jagattech";
const char *CURRENT_VERSION = "v2.0-Finger";

#define EEPROM_SIZE 512
#define CONFIG_MAGIC 0xFA
#define BUZZER_PIN 15 // D8
#define I2C_SDA 4     // D2
#define I2C_SCL 5     // D1

// PIN Fingerprint UART
#define FINGER_RX 14 // D5 (Hubungkan ke TX Sensor)
#define FINGER_TX 12 // D6 (Hubungkan ke RX Sensor)

#define RESPONSE_DISPLAY_TIME 3000
#define STANDBY_SCROLL_INTERVAL 500
#define BACKLIGHT_TIMEOUT 10000
#define FINGER_COOLDOWN_TIME 2000
#define WATCHDOG_TIMEOUT_SECONDS 30
#define OFFLINE_QUEUE_FILE "/queue.ndjson"
#define OFFLINE_QUEUE_TMP "/queue_tmp.ndjson"
#define OFFLINE_QUEUE_MAX 2000
#define OFFLINE_BATCH_TIMEOUT (30UL * 60 * 1000)

// =====================================================
// STRUCT
// =====================================================
struct WifiConfig {
  char ssid[32];
  char pass[32];
  char apiKey[65];
  char apiUrl[128];
  char schoolName[32];
  char otaUrl[128];
  uint8_t magic;
};
WifiConfig cfg;

// =====================================================
// GLOBALS
// =====================================================
RTC_DS1307 rtc;
bool rtcReady = false;
bool bootWifiOK = false;
bool otaInProgress = false;
bool backlightOn = true;
unsigned long lastActivity = 0;
int lastScannedFinger = -1;
unsigned long lastScanTime = 0;
unsigned long lastSyncAttempt = 0;
const unsigned long SYNC_INTERVAL = 30000;
bool serverOnline = false;

// Polling enroll
const unsigned long ENROLL_POLL_INTERVAL = 3000;
unsigned long lastEnrollCheck = 0;
bool pendingEnroll = false;
int pendingEnrollId = 0;
String pendingEnrollType = "";

String offlineBatchTime = "";
unsigned long lastOfflineScanMs = 0;

Ticker watchdogTicker;
volatile bool watchdogFlag = false;
bool otaTriggered = false;
String otaTriggerUrl = "";

enum LcdState {
  LCD_BOOT,
  LCD_AP_MODE,
  LCD_STANDBY,
  LCD_PROCESSING,
  LCD_SUCCESS,
  LCD_ERROR,
  LCD_OTA_UPDATE,
  LCD_OFFLINE_SAVED,
  LCD_ENROLLING
};
LcdState currentLcdState = LCD_BOOT;
unsigned long lcdStateChangeTime = 0;
unsigned long lastStandbyUpdate = 0;
int standbyScrollPos = 0;

ESP8266WebServer server(80);
LiquidCrystal_I2C lcd(0x27, 16, 2);

SoftwareSerial mySerial(FINGER_RX, FINGER_TX);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

bool sendToServer(int fingerId, String scannedAt = "");
void sendEnrollSuccess(int id);
void sendEnrollError(int id, String reason);
void showStandbyDisplay();

// =====================================================
// WATCHDOG & BUZZER
// =====================================================
void IRAM_ATTR watchdogISR() { watchdogFlag = true; }
void feedWatchdog() { watchdogFlag = false; }
void setupWatchdog() {
  watchdogTicker.attach(WATCHDOG_TIMEOUT_SECONDS, watchdogISR);
}
void checkWatchdog() {
  if (otaInProgress) {
    feedWatchdog();
    return;
  }
  if (watchdogFlag) {
    ESP.restart();
  }
}

void beepOK() {
  digitalWrite(BUZZER_PIN, HIGH);
  delay(100);
  digitalWrite(BUZZER_PIN, LOW);
  delay(60);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(100);
  digitalWrite(BUZZER_PIN, LOW);
}
void beepShort() {
  digitalWrite(BUZZER_PIN, HIGH);
  delay(120);
  digitalWrite(BUZZER_PIN, LOW);
}
void beepError() {
  digitalWrite(BUZZER_PIN, HIGH);
  delay(600);
  digitalWrite(BUZZER_PIN, LOW);
}
void beepTriple() {
  for (int i = 0; i < 3; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(80);
    digitalWrite(BUZZER_PIN, LOW);
    delay(80);
  }
}
void beepNetwork() {
  for (int i = 0; i < 3; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(150);
    digitalWrite(BUZZER_PIN, LOW);
    delay(150);
  }
}

// =====================================================
// RTC & BATTERY
// =====================================================
String getRtcDatetime() {
  if (!rtcReady)
    return "1970-01-01 00:00:00";
  DateTime now = rtc.now();
  char buf[20];
  snprintf(buf, sizeof(buf), "%04d-%02d-%02d %02d:%02d:%02d", now.year(),
           now.month(), now.day(), now.hour(), now.minute(), now.second());
  return String(buf);
}

void syncNTPtoRTC() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Sync NTP Time...");
  configTime(25200, 0, "pool.ntp.org", "time.nist.gov");
  time_t now = time(nullptr);
  int retries = 0;
  while (now < 8 * 3600 * 2 && retries < 15) {
    delay(500);
    now = time(nullptr);
    retries++;
    feedWatchdog();
  }
  if (now > 8 * 3600 * 2) {
    struct tm timeinfo;
    gmtime_r(&now, &timeinfo);
    if (rtcReady) {
      rtc.adjust(DateTime(timeinfo.tm_year + 1900, timeinfo.tm_mon + 1,
                          timeinfo.tm_mday, timeinfo.tm_hour, timeinfo.tm_min,
                          timeinfo.tm_sec));
      lcd.setCursor(0, 1);
      lcd.print("NTP Sync OK   ");
    }
  } else {
    lcd.setCursor(0, 1);
    lcd.print("NTP Failed    ");
  }
  delay(1000);
}

int getBatteryPercentage() {
  // Simplified battery reading from raw ADC
  int raw = analogRead(A0);
  float vAdc = (raw / 1023.0f) * 3.3f;
  float vBattery = (vAdc * 2.0f) + 0.253f;
  if (vBattery >= 4.00f)
    return 100;
  if (vBattery <= 3.00f)
    return 0;
  return (int)((vBattery - 3.00f) / 1.0f * 100.0f);
}

bool initRTC() {
  if (!rtc.begin())
    return false;
  if (!rtc.isrunning())
    rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  return true;
}

// =====================================================
// OFFLINE QUEUE
// =====================================================
int getQueueCount() {
  if (!LittleFS.exists(OFFLINE_QUEUE_FILE))
    return 0;
  File f = LittleFS.open(OFFLINE_QUEUE_FILE, "r");
  if (!f)
    return 0;
  int count = 0;
  while (f.available()) {
    String line = f.readStringUntil('\n');
    line.trim();
    if (line.length() > 0)
      count++;
  }
  f.close();
  return count;
}

bool saveOfflineRecord(int fingerId) {
  Serial.println("[QUEUE] Simpan offline ID: " + String(fingerId));
  unsigned long nowMs = millis();
  bool newBatch = (offlineBatchTime.length() == 0) ||
                  (lastOfflineScanMs > 0 &&
                   nowMs - lastOfflineScanMs > OFFLINE_BATCH_TIMEOUT);
  if (newBatch)
    offlineBatchTime = getRtcDatetime();
  lastOfflineScanMs = nowMs;

  if (getQueueCount() >= OFFLINE_QUEUE_MAX)
    return false;

  File f = LittleFS.open(OFFLINE_QUEUE_FILE, "a");
  if (!f)
    return false;
  StaticJsonDocument<128> doc;
  doc["finger_id"] = fingerId;
  doc["ts"] = offlineBatchTime;
  serializeJson(doc, f);
  f.println();
  f.close();
  return true;
}

void syncOfflineQueue() {
  if (!LittleFS.exists(OFFLINE_QUEUE_FILE))
    return;
  if (WiFi.status() != WL_CONNECTED)
    return;
  int total = getQueueCount();
  if (total == 0) {
    LittleFS.remove(OFFLINE_QUEUE_FILE);
    return;
  }

  Serial.println("[SYNC] Memulai sync " + String(total) + " entri...");
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("SINKRONISASI");
  String apiUrl = String(cfg.apiUrl) + "/api/fingerprint";
  File src = LittleFS.open(OFFLINE_QUEUE_FILE, "r");
  if (!src)
    return;
  File tmp = LittleFS.open(OFFLINE_QUEUE_TMP, "w");
  int synced = 0, failed = 0, idx = 0;

  while (src.available()) {
    feedWatchdog();
    String line = src.readStringUntil('\n');
    line.trim();
    if (line.length() == 0)
      continue;

    StaticJsonDocument<128> entry;
    if (deserializeJson(entry, line))
      continue;

    int fingerId = entry["finger_id"] | -1;
    const char *ts = entry["ts"] | "";
    if (fingerId == -1)
      continue;

    idx++;
    WiFiClient *client = nullptr;
    if (apiUrl.startsWith("https")) {
      WiFiClientSecure *secClient = new WiFiClientSecure();
      secClient->setInsecure();
      client = secClient;
    } else {
      client = new WiFiClient();
    }
    HTTPClient http;
    http.begin(*client, apiUrl);
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(6000);

    StaticJsonDocument<200> req;
    req["api_key"] = cfg.apiKey;
    req["finger_id"] = fingerId;
    if (strlen(ts) > 0)
      req["scanned_at"] = ts;
    String body;
    serializeJson(req, body);

    int code = http.POST(body);
    if (code == 200) {
      synced++;
      Serial.printf("[SYNC] OK %d/%d: ID %d\n", idx, total, fingerId);
    } else {
      failed++;
      if (tmp)
        tmp.println(line);
      Serial.printf("[SYNC] FAIL %d/%d: ID %d (HTTP %d)\n", idx, total, fingerId, code);
      if (code > 0) Serial.println("[SYNC] Response: " + http.getString());
      else Serial.println("[SYNC] HTTP Error: " + http.errorToString(code));
    }
    http.end();
    delete client;
    delay(150);
    lcd.setCursor(0, 1);
    lcd.print(String(idx) + "/" + String(total) + "      ");
  }
  src.close();
  if (tmp)
    tmp.close();
  LittleFS.remove(OFFLINE_QUEUE_FILE);
  if (failed > 0) {
    LittleFS.rename(OFFLINE_QUEUE_TMP, OFFLINE_QUEUE_FILE);
  } else {
    LittleFS.remove(OFFLINE_QUEUE_TMP);
    offlineBatchTime = "";
    lastOfflineScanMs = 0;
  }

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("SYNC SELESAI");
  lcd.setCursor(0, 1);
  lcd.print(String(synced) + " OK " + String(failed) + " gagal");
  Serial.printf("[SYNC] Selesai! %d Berhasil, %d Gagal\n", synced, failed);
  delay(2000);
  showStandbyDisplay();
}

bool pingServer() {
  if (WiFi.status() != WL_CONNECTED)
    return false;
  String apiUrl = String(cfg.apiUrl) + "/api/fingerprint";
  WiFiClient *client = nullptr;
  if (apiUrl.startsWith("https")) {
    WiFiClientSecure *secClient = new WiFiClientSecure();
    secClient->setInsecure();
    client = secClient;
  } else {
    client = new WiFiClient();
  }
  HTTPClient http;
  http.begin(*client, apiUrl);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(4000);
  StaticJsonDocument<128> doc;
  doc["api_key"] = cfg.apiKey;
  doc["ping"] = true;
  String body;
  serializeJson(doc, body);
  int code = http.POST(body);
  http.end();
  delete client;
  return (code > 0);
}

// =====================================================
// LCD STATE
// =====================================================
void setLcdState(LcdState s) {
  if (currentLcdState != s) {
    currentLcdState = s;
    lcdStateChangeTime = millis();
    standbyScrollPos = 0;
    lastStandbyUpdate = 0;
  }
}
void resetActivityTimer() {
  lastActivity = millis();
  if (!backlightOn) {
    lcd.backlight();
    backlightOn = true;
  }
}
void checkBacklightTimeout() {
  if (!bootWifiOK || currentLcdState == LCD_PROCESSING ||
      currentLcdState == LCD_SUCCESS || currentLcdState == LCD_ERROR ||
      currentLcdState == LCD_OTA_UPDATE || currentLcdState == LCD_ENROLLING)
    return;
  if (backlightOn && millis() - lastActivity >= BACKLIGHT_TIMEOUT) {
    lcd.noBacklight();
    backlightOn = false;
  }
}
void updateStandbyDisplay() {
  if (millis() - lastStandbyUpdate < STANDBY_SCROLL_INTERVAL)
    return;
  lastStandbyUpdate = millis();
  lcd.setCursor(0, 0);
  char buf[17];
  snprintf(buf, sizeof(buf), "ABSENSI     %3d%%", getBatteryPercentage());
  lcd.print(buf);
  lcd.setCursor(0, 1);
  String school = String(cfg.schoolName);
  if (school.length() == 0)
    school = "ABSEN FINGERPRINT";
  String s = school + "       ";
  String d = "";
  int len = s.length();
  for (int i = 0; i < 16; i++)
    d += s[(standbyScrollPos + i) % len];
  lcd.print(d);
  if (++standbyScrollPos >= len)
    standbyScrollPos = 0;
}
void updateLcdDisplay() {
  checkBacklightTimeout();
  if ((currentLcdState == LCD_SUCCESS || currentLcdState == LCD_ERROR ||
       currentLcdState == LCD_OFFLINE_SAVED) &&
      millis() - lcdStateChangeTime >= RESPONSE_DISPLAY_TIME)
    setLcdState(LCD_STANDBY);
  if (currentLcdState == LCD_STANDBY)
    updateStandbyDisplay();
}
void showStandbyDisplay() {
  setLcdState(LCD_STANDBY);
  resetActivityTimer();
  lcd.clear();
  updateStandbyDisplay();
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
void showOfflineSavedDisplay(int id) {
  setLcdState(LCD_OFFLINE_SAVED);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("OFFLINE - SIMPAN");
  lcd.setCursor(0, 1);
  lcd.print("ID: " + String(id));
  beepOK();
}
void showSuccessDisplay(const char *nama, const char *type) {
  setLcdState(LCD_SUCCESS);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  String t = String(type);
  t.toLowerCase();
  if (t == "absen_masuk" || t == "absen_masuk_guru") {
    lcd.print("ABSEN MASUK");
    beepOK();
  } else if (t == "absen_pulang" || t == "absen_pulang_guru") {
    lcd.print("ABSEN PULANG");
    beepOK();
  } else if (t == "sudah_absen_masuk") {
    lcd.print("SUDAH ABSEN");
    beepShort();
  } else if (t == "sudah_lengkap") {
    lcd.print("ABSEN LENGKAP");
    beepShort();
  } else {
    lcd.print("SUKSES");
    beepOK();
  }
  lcd.setCursor(0, 1);
  String n = String(nama);
  if (n.length() > 16)
    n = n.substring(0, 13) + "...";
  lcd.print(n);
}
void showErrorDisplay(const char *message) {
  setLcdState(LCD_ERROR);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ABSEN GAGAL!");
  lcd.setCursor(0, 1);
  String m = String(message);
  if (m.length() > 16)
    m = m.substring(0, 13) + "...";
  lcd.print(m);
  beepError();
}

// =====================================================
// FINGERPRINT LOGIC
// =====================================================
int16_t identifyFingerprint(uint16_t &outId, uint16_t &outConfidence) {
  int p = finger.getImage();
  if (p != FINGERPRINT_OK)
    return p;
  p = finger.image2Tz();
  if (p != FINGERPRINT_OK)
    return p;
  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK)
    return p;
  outId = finger.fingerID;
  outConfidence = finger.confidence;
  return FINGERPRINT_OK;
}

void checkEnrollRequest() {
  if (WiFi.status() != WL_CONNECTED)
    return;
  String url = String(cfg.apiUrl) +
               "/api/fingerprint/check-enroll?api_key=" + String(cfg.apiKey);
  WiFiClient *client = nullptr;
  if (url.startsWith("https")) {
    WiFiClientSecure *secClient = new WiFiClientSecure();
    secClient->setInsecure();
    client = secClient;
  } else {
    client = new WiFiClient();
  }
  HTTPClient http;
  http.begin(*client, url);
  http.setTimeout(3000);
  int httpCode = http.GET();
  if (httpCode == 200) {
    String payload = http.getString();
    StaticJsonDocument<256> doc;
    if (!deserializeJson(doc, payload)) {
      String status = doc["status"].as<String>();
      if (status == "enroll_mode") {
        pendingEnrollId = doc["enroll_id"].as<int>();
        pendingEnrollType = doc["type"].as<String>();
        pendingEnroll = true;
      } else if (status == "delete_mode") {
        int idToDelete = doc["enroll_id"].as<int>();
        Serial.printf("[DELETE] Perintah hapus ID: %d dari server\n", idToDelete);
        if (finger.deleteModel(idToDelete) == FINGERPRINT_OK) {
          Serial.println("[DELETE] Sukses terhapus dari sensor.");
        } else {
          Serial.println("[DELETE] Gagal terhapus dari sensor (mungkin sudah kosong).");
        }
      }
    }
  }
  http.end();
  delete client;
}

void handleEnrollment(int id, String type) {
  Serial.println("\n[ENROLL] Proses enroll ID: " + String(id) + " (" + type + ")");
  setLcdState(LCD_ENROLLING);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ENROLL ID: " + String(id));
  lcd.setCursor(0, 1);
  lcd.print("Tempelkan Jari..");

  uint8_t p = -1;
  Serial.println("[ENROLL] -> Tempelkan jari (Gambar 1)...");
  while (p != FINGERPRINT_OK) {
    feedWatchdog();
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER)
      delay(100);
  }
  Serial.println("[ENROLL] -> Gambar 1 OK!");

  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) {
    sendEnrollError(id, "Gagal baca gambar 1");
    showStandbyDisplay();
    return;
  }

  lcd.setCursor(0, 1);
  lcd.print("Angkat Jari...  ");
  Serial.println("[ENROLL] -> Silakan Angkat Jari...");
  beepShort();
  delay(1500);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    feedWatchdog();
    p = finger.getImage();
    delay(100);
  }
  Serial.println("[ENROLL] -> Jari sudah diangkat.");

  lcd.setCursor(0, 1);
  lcd.print("Tempel Lagi...  ");
  Serial.println("[ENROLL] -> Tempelkan Jari SEKALI LAGI (Gambar 2)...");
  p = -1;
  while (p != FINGERPRINT_OK) {
    feedWatchdog();
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER)
      delay(100);
  }
  Serial.println("[ENROLL] -> Gambar 2 OK!");

  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) {
    sendEnrollError(id, "Gagal baca gambar 2");
    showStandbyDisplay();
    return;
  }

  lcd.setCursor(0, 1);
  lcd.print("Memproses...    ");
  Serial.println("[ENROLL] -> Memproses pencocokan 2 gambar...");
  p = finger.createModel();
  if (p != FINGERPRINT_OK) {
    sendEnrollError(id, "Sidik jari tak cocok");
    showErrorDisplay("Jari tak cocok");
    delay(2000);
    showStandbyDisplay();
    return;
  }

  p = finger.storeModel(id);
  if (p == FINGERPRINT_OK) {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("ENROLL SUKSES");
    lcd.setCursor(0, 1);
    lcd.print("Tersimpan!");
    beepTriple();
    sendEnrollSuccess(id);
  } else {
    sendEnrollError(id, "Gagal simpan ke sensor");
    showErrorDisplay("Gagal simpan");
  }
  delay(2000);
  showStandbyDisplay();
}

void sendEnrollSuccess(int id) {
  Serial.println("[ENROLL] Mengirim status sukses ID: " + String(id));
  if (WiFi.status() != WL_CONNECTED)
    return;
  WiFiClient client;
  HTTPClient http;
  String url = String(cfg.apiUrl) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  StaticJsonDocument<200> doc;
  doc["api_key"] = cfg.apiKey;
  doc["finger_id"] = id;
  doc["enroll_success"] = true;
  String body;
  serializeJson(doc, body);
  http.POST(body);
  http.end();
}

void sendEnrollError(int id, String reason) {
  Serial.println("[ENROLL] Error ID: " + String(id) + " - " + reason);
  if (WiFi.status() != WL_CONNECTED)
    return;
  WiFiClient client;
  HTTPClient http;
  String url = String(cfg.apiUrl) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  StaticJsonDocument<256> doc;
  doc["api_key"] = cfg.apiKey;
  doc["finger_id"] = id;
  doc["enroll_error"] = true;
  doc["message"] = reason;
  String body;
  serializeJson(doc, body);
  http.POST(body);
  http.end();
}

// =====================================================
// SETUP & LOOP
// =====================================================
void loadConfig() {
  EEPROM.get(0, cfg);
  if (cfg.magic != CONFIG_MAGIC) {
    memset(&cfg, 0, sizeof(cfg));
    strncpy(cfg.schoolName, "ABSEN FINGERPRINT", sizeof(cfg.schoolName) - 1);
    strncpy(cfg.otaUrl, "http://yourserver.com/ota/FingerprintV2.bin",
            sizeof(cfg.otaUrl) - 1);
  }
}
void saveConfig() {
  cfg.magic = CONFIG_MAGIC;
  EEPROM.put(0, cfg);
  EEPROM.commit();
}

void setupOTA() {
  ArduinoOTA.setHostname(OTA_HOSTNAME);
  ArduinoOTA.setPassword(OTA_PASSWORD);
  ArduinoOTA.onStart([]() {
    otaInProgress = true;
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("OTA UPDATE");
  });
  ArduinoOTA.onEnd([]() {
    lcd.clear();
    lcd.print("OTA COMPLETE");
    beepTriple();
  });
  ArduinoOTA.onProgress([](unsigned int p, unsigned int t) {
    lcd.setCursor(0, 1);
    lcd.print("Prog: ");
    lcd.print(p / (t / 100));
    lcd.print("%  ");
    feedWatchdog();
  });
  ArduinoOTA.begin();
}

void setupWebConfig() {
  server.on("/", []() {
    String html = "<h2>Setup Fingerprint V2</h2>";
    html += "<form method='POST' action='/save'>";
    html += "SSID: <input name='ssid' value='" + String(cfg.ssid) + "'><br>";
    html += "PASS: <input name='pass' type='password'><br>";
    html += "School: <input name='school' value='" + String(cfg.schoolName) +
            "'><br>";
    html +=
        "API Key: <input name='apikey' value='" + String(cfg.apiKey) + "'><br>";
    html +=
        "API URL: <input name='apiurl' value='" + String(cfg.apiUrl) + "'><br>";
    html += "<button type='submit'>Simpan</button></form>";
    server.send(200, "text/html", html);
  });
  server.on("/save", []() {
    strncpy(cfg.ssid, server.arg("ssid").c_str(), sizeof(cfg.ssid) - 1);
    if (server.arg("pass").length() > 0)
      strncpy(cfg.pass, server.arg("pass").c_str(), sizeof(cfg.pass) - 1);
    strncpy(cfg.schoolName, server.arg("school").c_str(),
            sizeof(cfg.schoolName) - 1);
    strncpy(cfg.apiKey, server.arg("apikey").c_str(), sizeof(cfg.apiKey) - 1);
    strncpy(cfg.apiUrl, server.arg("apiurl").c_str(), sizeof(cfg.apiUrl) - 1);
    saveConfig();
    server.send(200, "text/html", "Tersimpan. Restart...");
    delay(1000);
    ESP.restart();
  });
  server.on("/delete-finger", []() {
    if (!server.hasArg("id")) {
      server.send(400, "application/json", "{\"status\":\"error\"}");
      return;
    }
    int id = server.arg("id").toInt();
    if (finger.deleteModel(id) == FINGERPRINT_OK) {
      server.send(200, "application/json", "{\"status\":\"ok\"}");
    } else {
      server.send(500, "application/json", "{\"status\":\"error\"}");
    }
  });
  server.begin();
}

void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.println("\n\n=== FINGERPRINT V2 BOOTING ===");
  EEPROM.begin(EEPROM_SIZE);
  loadConfig();
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  Wire.begin(I2C_SDA, I2C_SCL);
  lcd.init();
  lcd.backlight();
  if (!LittleFS.begin()) {
    LittleFS.format();
    LittleFS.begin();
  }
  setupWatchdog();

  lcd.clear();
  lcd.print("FINGERPRINT V2");
  delay(1000);
  rtcReady = initRTC();

  finger.begin(57600);
  if (finger.verifyPassword()) {
    lcd.setCursor(0, 1);
    lcd.print("Sensor OK!");
  } else {
    lcd.setCursor(0, 1);
    lcd.print("Sensor Error!");
    while (1) {
      delay(1);
    }
  }
  delay(1000);

  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP(AP_SSID, AP_PASS);
  if (strlen(cfg.ssid) > 0) {
    lcd.clear();
    lcd.print("Conn WiFi...");
    lcd.setCursor(0, 1);
    lcd.print(cfg.ssid);
    WiFi.begin(cfg.ssid, cfg.pass);
  }
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 10000) {
    feedWatchdog();
    delay(500);
  }
  if (WiFi.status() == WL_CONNECTED) {
    bootWifiOK = true;
    WiFi.softAPdisconnect(true);
    WiFi.mode(WIFI_STA);
    lcd.clear();
    lcd.print("WiFi Connected");
    lcd.setCursor(0, 1);
    lcd.print(WiFi.localIP());
    setupOTA();
    delay(1000);
    syncNTPtoRTC();
    serverOnline = pingServer();
    if (serverOnline)
      syncOfflineQueue();
  } else {
    lcd.clear();
    lcd.print("WIFI FAILED");
    lcd.setCursor(0, 1);
    lcd.print("OFFLINE MODE");
    delay(2000);
  }
  setupWebConfig();
  showStandbyDisplay();
}

void loop() {
  feedWatchdog();
  checkWatchdog();
  if (bootWifiOK) {
    ArduinoOTA.handle();
    MDNS.update();
  }
  server.handleClient();
  updateLcdDisplay();

  if (bootWifiOK && WiFi.status() == WL_CONNECTED &&
      millis() - lastSyncAttempt > SYNC_INTERVAL) {
    lastSyncAttempt = millis();
    serverOnline = pingServer();
    if (serverOnline && getQueueCount() > 0)
      syncOfflineQueue();
  }

  // Polling Enroll
  if (bootWifiOK && millis() - lastEnrollCheck >= ENROLL_POLL_INTERVAL) {
    lastEnrollCheck = millis();
    checkEnrollRequest();
  }

  if (pendingEnroll) {
    pendingEnroll = false;
    handleEnrollment(pendingEnrollId, pendingEnrollType);
  }

  // Scan Jari
  if (currentLcdState != LCD_ENROLLING) {
    uint16_t id, conf;
    if (identifyFingerprint(id, conf) == FINGERPRINT_OK) {
      if (millis() - lastScanTime < FINGER_COOLDOWN_TIME &&
          lastScannedFinger == id) {
        // Cooldown
      } else {
        lastScannedFinger = id;
        lastScanTime = millis();
        Serial.println("[SCAN] Jari ID: " + String(id) + " Conf: " + String(conf));
        beepShort();
        if (WiFi.status() == WL_CONNECTED && serverOnline) {
          showProcessingDisplay();
          if (!sendToServer(id)) {
            serverOnline = false;
            saveOfflineRecord(id);
            showOfflineSavedDisplay(id);
          }
        } else {
          saveOfflineRecord(id);
          showOfflineSavedDisplay(id);
        }
      }
      // Tunggu sampai jari diangkat
      while (finger.getImage() != FINGERPRINT_NOFINGER) {
        feedWatchdog();
        delay(50);
      }
    }
  }
}

bool sendToServer(int fingerId, String scannedAt) {
  feedWatchdog();
  Serial.println("[HTTP] Mengirim ID: " + String(fingerId));
  String apiUrl = String(cfg.apiUrl) + "/api/fingerprint";
  WiFiClient *client = nullptr;
  if (apiUrl.startsWith("https")) {
    WiFiClientSecure *secClient = new WiFiClientSecure();
    secClient->setInsecure();
    client = secClient;
  } else {
    client = new WiFiClient();
  }
  HTTPClient http;
  http.setTimeout(8000);
  http.begin(*client, apiUrl);
  http.addHeader("Content-Type", "application/json");
  StaticJsonDocument<256> req;
  req["api_key"] = cfg.apiKey;
  req["finger_id"] = fingerId;
  if (scannedAt.length() > 0)
    req["scanned_at"] = scannedAt;
  String body;
  serializeJson(req, body);
  int code = http.POST(body);
  feedWatchdog();
  Serial.printf("[HTTP] Code: %d\n", code);
  bool success = false;
  if (code == 200) {
    String payload = http.getString();
    Serial.println("[HTTP] Response: " + payload);
    StaticJsonDocument<512> doc;
    if (deserializeJson(doc, payload) == DeserializationError::Ok) {
      bool ok = doc["ok"].as<bool>();
      const char *message = doc["message"] | "Error";
      const char *nama = doc["nama"] | "";
      const char *type = doc["type"] | "";
      if (ok)
        showSuccessDisplay(nama, type);
      else
        showErrorDisplay(message);
      success = true;
    } else {
      showErrorDisplay("Format Salah");
      success = true;
    }
  } else if (code > 0) {
    success = false;
  } else {
    Serial.println("[HTTP] Error: " + http.errorToString(code));
    success = false;
  }
  http.end();
  delete client;
  return success;
}
