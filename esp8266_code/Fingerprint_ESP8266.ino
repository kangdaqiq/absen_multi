/*
 * ============================================================
 *  Absensi Fingerprint ESP8266 - Jagat Tech
 *  Backend: Laravel Multi-School Absensi System
 *
 *  API Endpoints yang digunakan:
 *    POST /api/fingerprint              → scan / ping / konfirmasi enroll
 *    GET  /api/fingerprint/check-enroll → polling cek request enroll pending
 *    GET  /delete-finger?id=X           → hapus slot di sensor (lokal, dari dashboard)
 * ============================================================
 */

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <ESP8266WebServer.h>
#include <ArduinoJson.h>
#include <SoftwareSerial.h>
#include <Adafruit_Fingerprint.h>

// ==========================================
// KONFIGURASI — Sesuaikan sebelum upload
// ==========================================
const char* ssid     = "NamaWiFi";
const char* password = "PasswordWiFi";

// URL server Laravel (tanpa trailing slash)
// Contoh XAMPP: "http://192.168.1.10/absen/public"
// Contoh artisan serve: "http://192.168.1.10:8000"
const char* api_host = "http://192.168.1.10/absen/public";

// API Key dari halaman Manajemen Device di dashboard admin
const char* api_key  = "isi-api-key-dari-dashboard";

// ==========================================
// PIN KONFIGURASI
// Sensor TX (hijau) → D1 = GPIO5 (RX ESP)
// Sensor RX (putih) → D2 = GPIO4 (TX ESP)
// ==========================================
SoftwareSerial mySerial(5, 4); // RX=GPIO5, TX=GPIO4
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

// ==========================================
// GLOBALS
// ==========================================
ESP8266WebServer server(80);

// Polling enroll ke backend setiap 3 detik
const unsigned long ENROLL_POLL_INTERVAL = 3000;
unsigned long lastEnrollCheck = 0;

// State enroll dari hasil polling
bool   pendingEnroll     = false;
int    pendingEnrollId   = 0;
String pendingEnrollType = ""; // "guru", "siswa", "gate_card"

// ==========================================
// SETUP
// ==========================================
void setup() {
  Serial.begin(115200);
  delay(100);
  Serial.println("\n\n=== Absensi Fingerprint ESP8266 - Jagat Tech ===");

  // 1. Init Sensor Fingerprint
  finger.begin(57600);
  delay(50);
  if (finger.verifyPassword()) {
    Serial.println("[OK] Fingerprint sensor ditemukan.");
  } else {
    Serial.println("[ERROR] Fingerprint sensor TIDAK ditemukan! Cek kabel.");
    while (1) { delay(1); }
  }

  // 2. Koneksi WiFi
  WiFi.begin(ssid, password);
  Serial.print("Menghubungkan ke WiFi");
  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 40) {
    delay(500);
    Serial.print(".");
    retry++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[OK] WiFi terhubung. IP: " + WiFi.localIP().toString());
  } else {
    Serial.println("\n[WARN] WiFi gagal. Mode offline.");
  }

  // 3. Web Server Lokal (untuk hapus sidik jari dari dashboard)
  server.on("/delete-finger", handleDeleteFinger);
  server.begin();
  Serial.println("[OK] Web server lokal aktif (port 80).");

  // 4. Ping ke backend (registrasi IP device)
  sendPing();
}

// ==========================================
// LOOP
// ==========================================
void loop() {
  // Handle request lokal (delete-finger dari dashboard)
  server.handleClient();

  // Polling ke backend: cek apakah ada request enroll pending
  if (millis() - lastEnrollCheck >= ENROLL_POLL_INTERVAL) {
    lastEnrollCheck = millis();
    checkEnrollRequest();
  }

  // Proses enroll jika ada dari hasil polling
  if (pendingEnroll) {
    pendingEnroll = false;
    handleEnrollment(pendingEnrollId, pendingEnrollType);
  }

  // Scan sidik jari untuk absensi
  int fingerId = getFingerprintID();
  if (fingerId != -1) {
    Serial.print("[SCAN] Terdeteksi ID #"); Serial.println(fingerId);
    sendScanRequest(fingerId);
    delay(2000); // Jeda agar tidak scan ganda
  }

  delay(50);
}

// ==========================================
// FINGERPRINT: Ambil & Cocokkan
// ==========================================
int getFingerprintID() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) return -1;

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) return -1;

  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) return -1;

  return finger.fingerID;
}

// ==========================================
// POLLING: Cek Enroll Request ke Backend
// GET /api/fingerprint/check-enroll?api_key=xxx
// Response: { ok, status:"enroll_mode", enroll_id, type }
// ==========================================
void checkEnrollRequest() {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClient client;
  HTTPClient http;

  String url = String(api_host) + "/api/fingerprint/check-enroll?api_key=" + String(api_key);
  http.begin(client, url);
  http.setTimeout(3000);

  int httpCode = http.GET();
  if (httpCode == 200) {
    String payload = http.getString();
    StaticJsonDocument<256> doc;
    if (!deserializeJson(doc, payload)) {
      String status = doc["status"].as<String>();
      if (status == "enroll_mode") {
        pendingEnrollId   = doc["enroll_id"].as<int>();
        pendingEnrollType = doc["type"].as<String>(); // guru / siswa / gate_card
        pendingEnroll     = true;
        Serial.println("[ENROLL] Request dari backend: " + pendingEnrollType + " ID=" + String(pendingEnrollId));
      }
    }
  }
  http.end();
}

// ==========================================
// ENROLL: Proses Pendaftaran Sidik Jari
// ==========================================
void handleEnrollment(int id, String type) {
  Serial.println("\n[ENROLL] Mulai untuk " + type + " ID=" + String(id));
  Serial.println(">> Tempelkan jari...");

  uint8_t p = -1;

  // --- Cek Duplikat ---
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) delay(100);
    else if (p != FINGERPRINT_OK)  delay(100);
  }

  p = finger.image2Tz();
  if (p == FINGERPRINT_OK) {
    p = finger.fingerFastSearch();
    if (p == FINGERPRINT_OK) {
      Serial.println("[ENROLL] Duplikat! Reuse slot #" + String(finger.fingerID));
      sendEnrollSuccess(finger.fingerID);
      delay(2000);
      return;
    }
  }

  Serial.println(">> Tidak ada duplikat. Angkat jari...");
  delay(1000);

  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
    delay(10);
  }

  // --- Gambar 1 ---
  Serial.println(">> Tempelkan jari lagi (Gambar 1)...");
  p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) Serial.print(".");
    delay(100);
  }
  Serial.println("\n[OK] Gambar 1 diambil.");

  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) {
    Serial.println("[ERROR] image2Tz(1) gagal.");
    sendEnrollError(id, "Image 1 gagal");
    return;
  }

  // --- Angkat jari ---
  Serial.println(">> Angkat jari...");
  delay(2000);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
    delay(100);
  }

  // --- Gambar 2 ---
  Serial.println(">> Tempelkan jari yang sama lagi (Gambar 2)...");
  p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) Serial.print(".");
    delay(100);
  }
  Serial.println("\n[OK] Gambar 2 diambil.");

  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) {
    Serial.println("[ERROR] image2Tz(2) gagal.");
    sendEnrollError(id, "Image 2 gagal");
    return;
  }

  // --- Buat Model ---
  p = finger.createModel();
  if (p != FINGERPRINT_OK) {
    Serial.println("[ERROR] Sidik jari tidak cocok. Coba ulangi.");
    sendEnrollError(id, "Sidik jari tidak cocok");
    return;
  }

  // --- Simpan ke Sensor ---
  p = finger.storeModel(id);
  if (p == FINGERPRINT_OK) {
    Serial.println("[OK] Tersimpan di slot #" + String(id));
    sendEnrollSuccess(id);
  } else {
    Serial.println("[ERROR] Gagal simpan ke sensor.");
    sendEnrollError(id, "Gagal menyimpan ke sensor");
  }
}

// ==========================================
// API: Konfirmasi Enroll Berhasil
// POST /api/fingerprint
// Body: { api_key, finger_id, enroll_success: true }
// ==========================================
void sendEnrollSuccess(int id) {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClient client;
  HTTPClient http;

  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<200> doc;
  doc["api_key"]        = api_key;
  doc["finger_id"]      = id;
  doc["enroll_success"] = true;

  String body;
  serializeJson(doc, body);

  int httpCode = http.POST(body);
  if (httpCode > 0) {
    Serial.println("[ENROLL] Konfirmasi OK. HTTP=" + String(httpCode) + " " + http.getString());
  } else {
    Serial.println("[ENROLL] Gagal kirim konfirmasi: " + http.errorToString(httpCode));
  }
  http.end();
}

// ==========================================
// API: Laporan Error Enroll
// POST /api/fingerprint
// Body: { api_key, finger_id, enroll_error: true, message }
// ==========================================
void sendEnrollError(int id, String reason) {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClient client;
  HTTPClient http;

  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> doc;
  doc["api_key"]      = api_key;
  doc["finger_id"]    = id;
  doc["enroll_error"] = true;
  doc["message"]      = reason;

  String body;
  serializeJson(doc, body);

  int httpCode = http.POST(body);
  Serial.println("[ENROLL] Error dikirim. HTTP=" + String(httpCode));
  http.end();
}

// ==========================================
// API: Scan Absensi
// POST /api/fingerprint
// Body: { api_key, finger_id }
// Response: { ok, status, message, sound, type, nama }
// ==========================================
void sendScanRequest(int id) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[SCAN] Offline, scan dilewati.");
    return;
  }

  WiFiClient client;
  HTTPClient http;

  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(5000);

  StaticJsonDocument<200> doc;
  doc["api_key"]   = api_key;
  doc["finger_id"] = id;

  String body;
  serializeJson(doc, body);

  int httpCode = http.POST(body);
  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("[SCAN] HTTP=" + String(httpCode) + " " + payload);

    // Parse respons (untuk feedback LED/buzzer/LCD jika ada)
    StaticJsonDocument<256> resp;
    if (!deserializeJson(resp, payload)) {
      bool ok       = resp["ok"].as<bool>();
      String type   = resp["type"].as<String>();
      String nama   = resp["nama"].as<String>();
      String sound  = resp["sound"].as<String>(); // "ok", "warning", "gagal"
      Serial.println("[INFO] ok=" + String(ok) + " type=" + type + " nama=" + nama + " sound=" + sound);
      // TODO: tambahkan kontrol buzzer/LED berdasarkan 'sound'
    }
  } else {
    Serial.println("[SCAN] Error: " + http.errorToString(httpCode));
  }
  http.end();
}

// ==========================================
// API: Ping / Boot Notification
// POST /api/fingerprint
// Body: { api_key, ping: true }
// ==========================================
void sendPing() {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClient client;
  HTTPClient http;

  String url = String(api_host) + "/api/fingerprint";
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<128> doc;
  doc["api_key"] = api_key;
  doc["ping"]    = true;

  String body;
  serializeJson(doc, body);

  int httpCode = http.POST(body);
  if (httpCode > 0) {
    Serial.println("[PING] OK. HTTP=" + String(httpCode) + " " + http.getString());
  } else {
    Serial.println("[PING] Gagal: " + http.errorToString(httpCode));
  }
  http.end();
}

// ==========================================
// LOCAL SERVER: Hapus Sidik Jari dari Sensor
// GET /delete-finger?id=X
// Dipanggil oleh dashboard Laravel saat admin delete enrollment
// ==========================================
void handleDeleteFinger() {
  if (!server.hasArg("id")) {
    server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Missing id\"}");
    return;
  }

  int idToDelete = server.arg("id").toInt();
  uint8_t p = finger.deleteModel(idToDelete);

  if (p == FINGERPRINT_OK) {
    Serial.println("[DELETE] Slot #" + String(idToDelete) + " berhasil dihapus.");
    server.send(200, "application/json",
      "{\"status\":\"ok\",\"message\":\"Deleted ID " + String(idToDelete) + "\"}");
  } else {
    Serial.println("[DELETE] Gagal hapus slot #" + String(idToDelete) + ".");
    server.send(500, "application/json",
      "{\"status\":\"error\",\"message\":\"Failed to delete ID " + String(idToDelete) + "\"}");
  }
}
