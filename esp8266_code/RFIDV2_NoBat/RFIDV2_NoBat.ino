// =====================================================
// ESP8266 RFID + LCD + RTC + Offline Queue + OTA
// Buzzer Only | LittleFS Queue | DS3231 RTC
// Library: RTClib, LittleFS, ArduinoJson, MFRC522,
//          LiquidCrystal_I2C, ESP8266WiFi, ArduinoOTA
// =====================================================

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
#include <MFRC522.h>
#include <RTClib.h>
#include <SPI.h>
#include <Ticker.h>
#include <WiFiClientSecure.h>
#include <Wire.h>
#include <time.h>

// =====================================================
// KONFIGURASI
// =====================================================
const char *AP_SSID = "ABSEN-RFID";
const char *AP_PASS = "12345678";
const char *OTA_HOSTNAME = "ABSEN-RFID";
const char *OTA_PASSWORD = "04112000";
const char *CURRENT_VERSION = "4.0.1";

#define EEPROM_SIZE 512
#define CONFIG_MAGIC 0xA9
#define SS_PIN 16     // D0
#define RST_PIN 0     // D3
#define BUZZER_PIN 15 // D8
#define I2C_SDA 4     // D2
#define I2C_SCL 5     // D1
#define RESPONSE_DISPLAY_TIME 3000
#define STANDBY_SCROLL_INTERVAL 500
#define BACKLIGHT_TIMEOUT 10000
#define CARD_COOLDOWN_TIME 2000
#define WATCHDOG_TIMEOUT_SECONDS 30
#define OFFLINE_QUEUE_FILE "/queue.ndjson"
#define OFFLINE_QUEUE_TMP "/queue_tmp.ndjson"
#define OFFLINE_QUEUE_MAX 2000 // Batas entri (file ~100KB di flash)
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
String lastScannedUID = "";
unsigned long lastScanTime = 0;
unsigned long lastSyncAttempt = 0;
const unsigned long SYNC_INTERVAL = 30000;
bool serverOnline = false; // Status koneksi ke server
// Offline batch tracking
String offlineBatchTime = "";        // Timestamp batch aktif
unsigned long lastOfflineScanMs = 0; // Millis scan offline terakhir

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
  LCD_OFFLINE_SAVED
};
LcdState currentLcdState = LCD_BOOT;
unsigned long lcdStateChangeTime = 0;
unsigned long lastStandbyUpdate = 0;
int standbyScrollPos = 0;

ESP8266WebServer server(80);
LiquidCrystal_I2C lcd(0x27, 16, 2);
MFRC522 rfid(SS_PIN, RST_PIN);

// Deklarasi fungsi di awal agar tidak error scope
bool sendToServer(String uid, String scannedAt = "");

// =====================================================
// RTC
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

bool initRTC() {
  Serial.println("[I2C] Scanning bus...");
  int devices = 0;
  for(byte address = 1; address < 127; address++ ) {
    Wire.beginTransmission(address);
    if (Wire.endTransmission() == 0) {
      Serial.print("[I2C] Found device at 0x");
      Serial.println(address, HEX);
      devices++;
    }
  }
  if (devices == 0) Serial.println("[I2C] No devices found!");

  if (!rtc.begin()) {
    Serial.println("[RTC] Not found! Pastikan alamat 0x68 muncul di atas.");
    return false;
  }

  if (!rtc.isrunning()) {
    Serial.println("[RTC] Not running, setting time...");
    rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
  }
  Serial.println("[RTC] Ready: " + getRtcDatetime());
  return true;
}

void syncNTPtoRTC() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Sync NTP Time...");
  Serial.print("[NTP] Syncing time...");

  // Waktu WIB = GMT+7 (7 * 3600 = 25200)
  configTime(25200, 0, "pool.ntp.org", "time.nist.gov");

  time_t now = time(nullptr);
  int retries = 0;
  // Tunggu hingga tahun valid (bukan 1970)
  while (now < 8 * 3600 * 2 && retries < 15) {
    delay(500);
    Serial.print(".");
    now = time(nullptr);
    retries++;
    feedWatchdog();
  }
  Serial.println();

  if (now > 8 * 3600 * 2) {
    struct tm timeinfo;
    localtime_r(&now, &timeinfo);
    if (rtcReady) {
      rtc.adjust(DateTime(timeinfo.tm_year + 1900, timeinfo.tm_mon + 1, timeinfo.tm_mday, timeinfo.tm_hour, timeinfo.tm_min, timeinfo.tm_sec));
      Serial.println("[NTP] RTC Updated: " + getRtcDatetime());
      lcd.setCursor(0, 1);
      lcd.print("NTP Sync OK   ");
    }
  } else {
    Serial.println("[NTP] Sync Failed (Timeout)");
    lcd.setCursor(0, 1);
    lcd.print("NTP Failed    ");
  }
  delay(1000);
}

// =====================================================
// OFFLINE QUEUE (LittleFS — NDJSON, 1 baris per entri)
// Format per baris: {"uid":"AABB","ts":"2024-04-29 07:05:00"}
// RAM dibutuhkan: hanya 1 StaticJsonDoc<128> per baris
// Kapasitas: ~2000 entri (~100KB file), flash 1MB tidak jadi masalah
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

// Simpan 1 baris NDJSON ke file (append).
// Logika batch 30 menit: jika jeda > 30 menit, catat timestamp baru.
bool saveOfflineRecord(const String &uid) {
  unsigned long nowMs = millis();

  bool newBatch = (offlineBatchTime.length() == 0) ||
                  (lastOfflineScanMs > 0 &&
                   nowMs - lastOfflineScanMs > OFFLINE_BATCH_TIMEOUT);
  if (newBatch) {
    offlineBatchTime = getRtcDatetime();
    Serial.println("[QUEUE] Batch baru: " + offlineBatchTime);
  }
  lastOfflineScanMs = nowMs;

  // Cek batas
  if (getQueueCount() >= OFFLINE_QUEUE_MAX) {
    Serial.println("[QUEUE] Full! Entry baru diabaikan.");
    return false;
  }

  // Cek duplikat di batch yang sama
  bool isDuplicate = false;
  File r = LittleFS.open(OFFLINE_QUEUE_FILE, "r");
  if (r) {
    String searchUid = "\"uid\":\"" + uid + "\"";
    String searchTs = "\"ts\":\"" + offlineBatchTime + "\"";
    while (r.available()) {
      String line = r.readStringUntil('\n');
      if (line.indexOf(searchUid) >= 0 && line.indexOf(searchTs) >= 0) {
        isDuplicate = true;
        break;
      }
    }
    r.close();
  }

  if (isDuplicate) {
    Serial.println("[QUEUE] Skip duplikat: " + uid);
    return true; // Anggap sukses agar proses berlanjut, tapi tidak disimpan
  }

  // Append 1 baris JSON ke file
  File f = LittleFS.open(OFFLINE_QUEUE_FILE, "a"); // mode append!
  if (!f)
    return false;
  StaticJsonDocument<128> doc;
  doc["uid"] = uid;
  doc["ts"] = offlineBatchTime;
  serializeJson(doc, f);
  f.println(); // newline pemisah antar entri
  f.close();

  Serial.println("[QUEUE] Saved: " + uid + " @ " + offlineBatchTime);
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

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("SINKRONISASI");
  lcd.setCursor(0, 1);
  lcd.print("0/" + String(total));
  Serial.println("[SYNC] Start: " + String(total) + " entri");

  String apiUrl = String(cfg.apiUrl) + "/api/rfid";
  File src = LittleFS.open(OFFLINE_QUEUE_FILE, "r");
  if (!src)
    return;

  // File sementara untuk menyimpan entri yang gagal
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
      continue; // skip baris rusak

    const char *uid = entry["uid"] | "";
    const char *ts = entry["ts"] | "";
    if (strlen(uid) == 0)
      continue;

    idx++;
    WiFiClient client;
    HTTPClient http;
    http.begin(client, apiUrl);
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(6000);

    StaticJsonDocument<200> req;
    req["api_key"] = cfg.apiKey;
    req["uid"] = uid;
    if (strlen(ts) > 0)
      req["scanned_at"] = ts;
    String body;
    serializeJson(req, body);

    int code = http.POST(body);
    if (code == 200) {
      synced++;
      Serial.println("[SYNC] OK " + String(idx) + "/" + String(total) + ": " +
                     String(uid));
    } else {
      failed++;
      // Tulis ulang ke tmp file
      if (tmp) {
        tmp.println(line);
      }
      Serial.println("[SYNC] FAIL " + String(code) + ": " + String(uid));
    }
    http.end();
    delay(150);
    lcd.setCursor(0, 1);
    lcd.print(String(idx) + "/" + String(total) + "      ");
  }

  src.close();
  if (tmp)
    tmp.close();

  // Ganti file queue dengan yang tersisa (gagal)
  LittleFS.remove(OFFLINE_QUEUE_FILE);
  if (failed > 0) {
    LittleFS.rename(OFFLINE_QUEUE_TMP, OFFLINE_QUEUE_FILE);
  } else {
    LittleFS.remove(OFFLINE_QUEUE_TMP);
    offlineBatchTime = "";
    lastOfflineScanMs = 0;
  }

  Serial.println("[SYNC] Done: " + String(synced) + " OK, " + String(failed) +
                 " gagal");
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("SYNC SELESAI");
  lcd.setCursor(0, 1);
  lcd.print(String(synced) + " OK " + String(failed) + " gagal");
  delay(2000);
  showStandbyDisplay();
}

bool pingServer() {
  if (WiFi.status() != WL_CONNECTED)
    return false;
  WiFiClient client;
  HTTPClient http;
  String apiUrl = String(cfg.apiUrl) + "/api/rfid";
  http.begin(client, apiUrl);
  http.setTimeout(3000);
  int code = http.GET(); // Hanya butuh respons HTTP apapun
  http.end();
  return (code > 0); // Jika HTTP code > 0 berarti server hidup
}

// =====================================================
// WATCHDOG
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
// BUZZER
// =====================================================
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
// OTA
// =====================================================
void setupOTA() {
  ArduinoOTA.setHostname(OTA_HOSTNAME);
  ArduinoOTA.setPassword(OTA_PASSWORD);
  ArduinoOTA.onStart([]() {
    otaInProgress = true;
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("OTA UPDATE");
    lcd.setCursor(0, 1);
    lcd.print("Starting...");
  });
  ArduinoOTA.onEnd([]() {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("OTA COMPLETE");
    lcd.setCursor(0, 1);
    lcd.print("Restarting...");
    beepTriple();
  });
  ArduinoOTA.onProgress([](unsigned int p, unsigned int t) {
    lcd.setCursor(0, 1);
    lcd.print("Prog: ");
    lcd.print(p / (t / 100));
    lcd.print("%   ");
    feedWatchdog();
  });
  ArduinoOTA.onError([](ota_error_t e) {
    otaInProgress = false;
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("OTA ERROR!");
    beepError();
    delay(3000);
  });
  ArduinoOTA.begin();
}

// =====================================================
// ONLINE OTA UPDATE
// =====================================================
void handleOnlineUpdate(String url = "") {
  if (WiFi.status() != WL_CONNECTED) {
    server.send(500, "text/plain", "WiFi Tidak Terkoneksi");
    return;
  }

  String updateUrl = (url.length() > 0) ? url : String(cfg.otaUrl);
  updateUrl.trim();

  if (updateUrl.length() == 0) {
    server.send(500, "text/plain", "URL OTA Kosong");
    return;
  }

  // Cache Buster
  if (updateUrl.indexOf('?') >= 0)
    updateUrl += "&t=";
  else
    updateUrl += "?t=";
  updateUrl += String(millis());

  Serial.println("[OTA] Target: " + updateUrl);

  server.send(
      200, "text/html",
      "<h3>Memulai Update Online...</h3><p>Jangan matikan perangkat. Cek LCD "
      "atau Serial "
      "Monitor.</p><script>setTimeout(function(){window.location.href='/';}, "
      "10000);</script>");

  delay(1000);
  server.stop(); // Stop server untuk bebaskan RAM

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ONLINE UPDATE");
  lcd.setCursor(0, 1);
  lcd.print("Connecting...");

  ESPhttpUpdate.onProgress([](int cur, int total) {
    static int lastPct = -1;
    if (total > 0) {
      int pct = (cur * 100) / total;
      if (pct != lastPct) {
        lastPct = pct;
        lcd.setCursor(0, 1);
        lcd.print("Prog: ");
        lcd.print(pct);
        lcd.print("%   ");
        Serial.printf("[OTA] Progress: %d%%\n", pct);
      }
    } else {
      lcd.setCursor(0, 1);
      lcd.print("Downloading...  ");
    }
    feedWatchdog();
  });

  ESPhttpUpdate.rebootOnUpdate(true);
  ESPhttpUpdate.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
  Serial.println("[OTA] Starting download...");
  Serial.printf("[OTA] Free heap: %d\n", ESP.getFreeHeap());
  Serial.printf("[OTA] Free sketch space: %d\n", ESP.getFreeSketchSpace());

  t_httpUpdate_return ret;
  if (updateUrl.startsWith("https")) {
    WiFiClientSecure sclient;
    sclient.setInsecure();
    // GitHub butuh buffer sedikit lebih besar, coba 1024
    sclient.setBufferSizes(1024, 1024);
    ret = ESPhttpUpdate.update(sclient, updateUrl);
  } else {
    WiFiClient client;
    client.setTimeout(15000);
    ret = ESPhttpUpdate.update(client, updateUrl);
  }

  switch (ret) {
  case HTTP_UPDATE_FAILED:
    Serial.printf("HTTP_UPDATE_FAILED Error (%d): %s\n",
                  ESPhttpUpdate.getLastError(),
                  ESPhttpUpdate.getLastErrorString().c_str());
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("UPDATE GAGAL");
    lcd.setCursor(0, 1);
    lcd.print("Err: " + String(ESPhttpUpdate.getLastError()));
    delay(5000);
    showStandbyDisplay();
    break;
  case HTTP_UPDATE_NO_UPDATES:
    Serial.println("HTTP_UPDATE_NO_UPDATES");
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("TIDAK ADA UPDATE");
    delay(3000);
    showStandbyDisplay();
    break;
  case HTTP_UPDATE_OK:
    Serial.println("HTTP_UPDATE_OK");
    // Restart otomatis
    break;
  }
}

// =====================================================
// COOLDOWN
// =====================================================
bool checkCooldown(String uid) {
  unsigned long now = millis();
  if (lastScannedUID != uid) {
    lastScannedUID = uid;
    lastScanTime = now;
    return true;
  }
  if (now - lastScanTime < CARD_COOLDOWN_TIME) {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("TUNGGU");
    lcd.setCursor(0, 1);
    lcd.print("Cooldown: " +
              String((CARD_COOLDOWN_TIME - (now - lastScanTime)) / 1000) + "s");
    beepShort();
    delay(1000);
    return false;
  }
  lastScanTime = now;
  return true;
}

// =====================================================
// EEPROM
// =====================================================
void loadConfig() {
  EEPROM.get(0, cfg);
  if (cfg.magic != CONFIG_MAGIC) {
    memset(&cfg, 0, sizeof(cfg));
    strncpy(cfg.schoolName, "RFID ABSENSI", sizeof(cfg.schoolName) - 1);
    strncpy(cfg.otaUrl, "http://yourserver.com/ota/RFIDV2.bin",
            sizeof(cfg.otaUrl) - 1);
  }
}

void saveConfig() {
  cfg.magic = CONFIG_MAGIC;
  EEPROM.put(0, cfg);
  EEPROM.commit();
}

// =====================================================
// LCD
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
      currentLcdState == LCD_OTA_UPDATE)
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

  // Baris 1: ABSENSI HH:MM
  lcd.setCursor(0, 0);
  char buf[17];
  if (rtcReady) {
    DateTime now = rtc.now();
    snprintf(buf, sizeof(buf), "ABSENSI    %02d:%02d", now.hour(),
             now.minute());
  } else {
    snprintf(buf, sizeof(buf), "ABSENSI  NO RTC ");
  }
  lcd.print(buf);

  // Baris 2: Running Text (School Name only)
  lcd.setCursor(0, 1);
  String school = String(cfg.schoolName);
  if (school.length() == 0)
    school = "RFID ABSENSI";
  String s = school + "       "; // Padding spasi

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
  updateStandbyDisplay(); // Langsung render frame pertama
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
void showOfflineSavedDisplay(const String &uid) {
  setLcdState(LCD_OFFLINE_SAVED);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("OFFLINE - SIMPAN");
  lcd.setCursor(0, 1);
  String u = uid;
  if (u.length() > 16)
    u = u.substring(0, 16);
  lcd.print(u);
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
  } else if (t == "enroll_rfid") {
    lcd.print("ENROLL SUKSES");
    beepTriple();
  } else if (t == "gate_opened") {
    lcd.print("PULANG DIBUKA");
    beepTriple();
  } else if (t == "gate_closed") {
    lcd.print("PULANG DITUTUP");
    beepTriple();
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
void showErrorDisplay(const char *message, const char *type = "") {
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
void showNetworkErrorDisplay(const char *errorType) {
  setLcdState(LCD_ERROR);
  resetActivityTimer();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("ERROR JARINGAN");
  lcd.setCursor(0, 1);
  lcd.print(errorType);
  beepNetwork();
}

// =====================================================
// WEB CONFIG
// =====================================================
void setupWebConfig() {
  server.on("/", []() {
    int qCount = getQueueCount();
    String html = "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>";
    html +=
        "<meta name='viewport' content='width=device-width,initial-scale=1'>";
    html += "<title>RFIDv2 Config</title>";
    html += "<link "
            "href='https://fonts.googleapis.com/"
            "css2?family=Inter:wght@400;600&display=swap' rel='stylesheet'>";
    html += "<style>";
    html += "body{font-family:'Inter',sans-serif;background:#f8fafc;color:#"
            "1e293b;margin:0;padding:20px;display:flex;justify-content:center;"
            "line-height:1.5}";
    html +=
        ".card{background:#fff;padding:32px;border-radius:20px;box-shadow:0 "
        "20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px "
        "rgba(0,0,0,0.04);width:100%;max-width:400px;border:1px solid #e2e8f0}";
    html += "h2{margin:0 0 "
            "24px;font-size:24px;font-weight:600;color:#0f172a;text-align:"
            "center;letter-spacing:-0.025em}";
    html += ".status-box{background:#f1f5f9;border-radius:12px;padding:16px;"
            "margin-bottom:24px;font-size:13px;border:1px solid #e2e8f0}";
    html += ".status-item{display:flex;justify-content:space-between;margin-"
            "bottom:6px}";
    html += ".label{font-weight:600;color:#64748b;font-size:12px;margin-bottom:"
            "6px;display:block;text-transform:uppercase;letter-spacing:0.05em}";
    html += "input{width:100%;padding:12px;margin-bottom:18px;border:1px solid "
            "#cbd5e1;border-radius:10px;box-sizing:border-box;font-size:14px;"
            "transition:all 0.2s;background:#fff}";
    html += "input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 "
            "4px rgba(99,102,241,0.1)}";
    html +=
        "button{width:100%;padding:14px;background:#6366f1;color:#fff;border:"
        "none;border-radius:10px;font-weight:600;cursor:pointer;transition:all "
        "0.2s;font-size:14px;box-shadow:0 4px 6px -1px rgba(99,102,241,0.2)}";
    html += "button:hover{background:#4f46e5;transform:translateY(-1px);box-"
            "shadow:0 10px 15px -3px rgba(99,102,241,0.3)}";
    html += ".btn-ota{background:#f59e0b;margin-bottom:24px;box-shadow:0 4px "
            "6px -1px rgba(245,158,11,0.2)}";
    html += ".btn-ota:hover{background:#d97706;box-shadow:0 10px 15px -3px "
            "rgba(245,158,11,0.3)}";
    html += ".footer{text-align:center;font-size:12px;color:#94a3b8;margin-top:"
            "24px}";
    html += "a{color:#6366f1;text-decoration:none;font-weight:600}";
    html += "</style></head><body>";
    html += "<div class='card'><h2>Setup RFIDv2</h2>";

    html += "<div class='status-box'>";
    html += "<div class='status-item'><span>Status WiFi</span><strong>" +
            String(bootWifiOK ? "Connected ✅" : "AP Mode 📡") +
            "</strong></div>";
    html +=
        "<div class='status-item'><span>IP Address</span><strong>" +
        (bootWifiOK ? WiFi.localIP().toString() : WiFi.softAPIP().toString()) +
        "</strong></div>";
    html += "<div class='status-item'><span>Versi Firmware</span><strong>" +
            String(CURRENT_VERSION) + "</strong></div>";
    html += "<div class='status-item'><span>Antrean Offline</span><strong>" +
            String(qCount) + " entri</strong></div>";
    if (qCount > 0)
      html += "<div style='text-align:center;margin-top:8px'><a "
              "href='/sync'>🔄 Sinkronisasi Sekarang</a></div>";
    html += "</div>";

    html += "<form method='POST' action='/save'>";
    html += "<span class='label'>SSID WiFi</span><input name='ssid' value='" +
            String(cfg.ssid) + "' placeholder='Nama WiFi'>";
    html += "<span class='label'>Password WiFi</span><input name='pass' "
            "type='password' placeholder='Kosongkan jika tidak diubah'>";
    html +=
        "<span class='label'>Nama Sekolah</span><input name='school' value='" +
        String(cfg.schoolName) + "'>";

    html += "<span class='label'>OTA Update URL</span>";
    html += "<input name='ota' id='ota_url' value='" + String(cfg.otaUrl) +
            "' placeholder='http://server.com/firmware.bin'>";
    html += "<button type='button' class='btn-ota' onclick='doUpdate()'>🚀 "
            "Update Sekarang</button>";

    html += "<span class='label'>API Key</span><input name='apikey' value='" +
            String(cfg.apiKey) + "'>";
    html += "<span class='label'>API Server URL</span><input name='apiurl' "
            "value='" +
            String(cfg.apiUrl) + "' placeholder='http://domain.com'>";

    html += "<button type='submit'>💾 Simpan Konfigurasi</button></form>";
    html += "<div class='footer'>RFIDv2 Smart Attendance System</div>";

    html += "<script>";
    html += "function doUpdate(){";
    html += "  var u=document.getElementById('ota_url').value;";
    html += "  if(!u){alert('URL Kosong!');return;}";
    html += "  if(confirm('Mulai update online sekarang? Perangkat akan "
            "mendownload dan "
            "restart.')){window.location.href='/"
            "ota-trigger?url='+encodeURIComponent(u);}";
    html += "}";
    html += "</script></div></body></html>";
    server.send(200, "text/html", html);
  });

  server.on("/save", []() {
    strncpy(cfg.ssid, server.arg("ssid").c_str(), sizeof(cfg.ssid) - 1);
    if (server.arg("pass").length() > 0)
      strncpy(cfg.pass, server.arg("pass").c_str(), sizeof(cfg.pass) - 1);
    strncpy(cfg.schoolName, server.arg("school").c_str(),
            sizeof(cfg.schoolName) - 1);
    strncpy(cfg.otaUrl, server.arg("ota").c_str(), sizeof(cfg.otaUrl) - 1);
    strncpy(cfg.apiKey, server.arg("apikey").c_str(), sizeof(cfg.apiKey) - 1);
    String u = server.arg("apiurl");
    u.trim();
    strncpy(cfg.apiUrl, u.c_str(), sizeof(cfg.apiUrl) - 1);
    saveConfig();
    server.send(200, "text/html", "<h3>Tersimpan! Restart 3 detik...</h3>");
    delay(1500);
    ESP.restart();
  });

  server.on("/sync", []() {
    server.send(200, "text/html", "<h3>Sinkronisasi dimulai...</h3>");
    syncOfflineQueue();
  });

  server.on("/update-online", []() {
    otaTriggered = true;
    server.send(200, "text/html", "<h3>Memicu Update...</h3>");
  });

  server.on("/ota-trigger", []() {
    String url = server.arg("url");
    if (url.length() > 0) {
      otaTriggerUrl = url;
      otaTriggered = true;
      server.send(
          200, "text/html",
          "<h3>Memicu Update Kustom...</h3><p>Cek layar perangkat.</p>");
    } else {
      server.send(400, "text/plain", "URL Kosong");
    }
  });

  server.begin();
}

// =====================================================
// SETUP
// =====================================================
void setup() {
  Serial.begin(115200);
  delay(300);
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

  if (!LittleFS.begin()) {
    Serial.println("[FS] Format LittleFS...");
    LittleFS.format();
    LittleFS.begin();
  }

  setupWatchdog();

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("SISTEM ABSENSI");
  lcd.setCursor(0, 1);
  lcd.print("Booting v4.0...");
  delay(1000);

  rtcReady = initRTC();

  SPI.begin();
  rfid.PCD_Init();
  Serial.println("[RFID] Initialized");

  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP(AP_SSID, AP_PASS);
  Serial.println("[WiFi] SoftAP started: " + WiFi.softAPIP().toString());

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
  while (WiFi.status() != WL_CONNECTED &&
         millis() - start <
             10000) { // Kurangi timeout ke 10 detik agar tidak lama menunggu
    feedWatchdog();
    delay(500);
    lcd.setCursor(15, 1);
    lcd.print(".");
    if (++dots > 3) {
      lcd.setCursor(13, 1);
      lcd.print("   ");
      dots = 0;
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    bootWifiOK = true;
    WiFi.softAPdisconnect(true);
    WiFi.mode(WIFI_STA);
    Serial.println("[WiFi] Connected, SoftAP turned off.");

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Connected");
    lcd.setCursor(0, 1);
    lcd.print(WiFi.localIP());
    if (MDNS.begin(OTA_HOSTNAME))
      Serial.println("[mDNS] Ready");
    setupOTA();
    delay(1000);

    // Sync NTP Time
    syncNTPtoRTC();

    // Cek server
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Cek Server...");
    serverOnline = pingServer();
    lcd.setCursor(0, 1);
    if (serverOnline) {
      lcd.print("Server ONLINE");
      syncOfflineQueue();
    } else {
      lcd.print("Server OFFLINE");
    }
    delay(1500);
  } else {
    bootWifiOK = false;
    Serial.println("[WiFi] Connection failed. Entering Offline Mode.");
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WIFI FAILED");
    lcd.setCursor(0, 1);
    lcd.print("OFFLINE MODE");
    delay(2000);
  }

  setupWebConfig();
  showStandbyDisplay();
  Serial.println("[SYSTEM] Ready! Queue: " + String(getQueueCount()));
}

// =====================================================
// LOOP
// =====================================================
void loop() {
  feedWatchdog();
  checkWatchdog();
  if (bootWifiOK) {
    ArduinoOTA.handle();
    MDNS.update();
  }
  server.handleClient();
  updateLcdDisplay();

  // Auto-sync periodik & Ping
  if (bootWifiOK && WiFi.status() == WL_CONNECTED &&
      millis() - lastSyncAttempt > SYNC_INTERVAL) {
    lastSyncAttempt = millis();
    serverOnline = pingServer(); // Ping tiap interval
    if (serverOnline && getQueueCount() > 0) {
      syncOfflineQueue();
    }
  }

  if (otaTriggered) {
    otaTriggered = false;
    handleOnlineUpdate(otaTriggerUrl);
  }

  if (otaInProgress) {
    delay(100);
    return;
  }

  // Reconnect jika terputus
  if (WiFi.status() != WL_CONNECTED) {
    static unsigned long lastRecon = 0;
    if (millis() - lastRecon > 30000) {
      // Hidupkan AP jika terputus agar tetap bisa dikonfigurasi
      if (WiFi.getMode() != WIFI_AP_STA) {
        WiFi.mode(WIFI_AP_STA);
        WiFi.softAP(AP_SSID, AP_PASS);
        Serial.println("[WiFi] Lost connection, SoftAP turned back on.");
      }

      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("WiFi Terputus");
      lcd.setCursor(0, 1);
      lcd.print("Reconnecting...");
      WiFi.reconnect();
      lastRecon = millis();
    }
  }

  if (!rfid.PICC_IsNewCardPresent())
    return;
  if (!rfid.PICC_ReadCardSerial())
    return;

  resetActivityTimer();
  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10)
      uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  Serial.println("[RFID] Card: " + uid);

  if (!checkCooldown(uid)) {
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    return;
  }

  if (WiFi.status() == WL_CONNECTED && serverOnline) {
    showProcessingDisplay();
    bool success = sendToServer(uid);
    if (!success) {
      // Fallback ke offline jika sendToServer gagal
      serverOnline = false;
      saveOfflineRecord(uid);
      showOfflineSavedDisplay(uid);
      Serial.println("[OFFLINE FALLBACK] Saved: " + uid);
    }
  } else {
    // Mode OFFLINE — simpan UID ke queue
    saveOfflineRecord(uid);
    showOfflineSavedDisplay(uid);
    Serial.println("[OFFLINE] Saved: " + uid);
  }

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}

// =====================================================
// HTTP API
// POST /api/rfid — JSON: { api_key, uid, scanned_at? }
// =====================================================
bool sendToServer(String uid, String scannedAt) {
  feedWatchdog();
  String apiUrl = String(cfg.apiUrl) + "/api/rfid";
  WiFiClient client;
  HTTPClient http;
  http.setTimeout(8000);
  http.begin(client, apiUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> req;
  req["api_key"] = cfg.apiKey;
  req["uid"] = uid;
  if (scannedAt.length() > 0)
    req["scanned_at"] = scannedAt;
  String body;
  serializeJson(req, body);

  Serial.println("[HTTP] POST " + apiUrl + " | " + body);
  int code = http.POST(body);
  feedWatchdog();
  Serial.println("[HTTP] Code: " + String(code));

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
        showErrorDisplay(message, type);
      success = true;
    } else {
      showErrorDisplay("Format Salah");
      success =
          true; // Request terkirim tapi format salah (bukan masalah offline)
    }
  } else if (code > 0) {
    showNetworkErrorDisplay(("Err:" + String(code)).c_str());
    success = false; // Code misal 404/500, fallback ke offline
  } else {
    showNetworkErrorDisplay("Conn Failed");
    success = false; // Timeout/Network Error, fallback ke offline
  }
  http.end();
  return success;
}