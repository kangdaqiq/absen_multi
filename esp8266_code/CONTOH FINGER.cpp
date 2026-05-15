#include <Adafruit_Fingerprint.h>
#include <SoftwareSerial.h>


/* =========================
   Fingerprint UART
   =========================
   D1 = RX <- TX sensor
   D2 = TX -> RX sensor
*/
SoftwareSerial FPSerial(5, 4);

Adafruit_Fingerprint finger = Adafruit_Fingerprint(&FPSerial);

/* =========================
   Mapping ID -> Nama Jari
   ========================= */
String fingerName(uint16_t id) {
  switch (id) {
  case 1:
    return "Jempol Kanan";
  case 2:
    return "Telunjuk Kanan";
  case 3:
    return "Tengah Kanan";
  case 4:
    return "Manis Kanan";
  case 5:
    return "Kelingking Kanan";
  case 6:
    return "Jempol Kiri";
  case 7:
    return "Telunjuk Kiri";
  case 8:
    return "Tengah Kiri";
  case 9:
    return "Manis Kiri";
  case 10:
    return "Kelingking Kiri";
  default:
    return "Tidak dipetakan";
  }
}

/* =========================
   Enroll Fingerprint
   ========================= */
uint8_t enrollFingerprint(uint16_t id) {
  int p = -1;

  Serial.println("\n=== ENROLL MODE ===");
  Serial.println("ID: " + String(id));
  Serial.println("Tempelkan jari...");

  while (p != FINGERPRINT_OK) {
    p = finger.getImage();

    if (p == FINGERPRINT_NOFINGER) {
      delay(50);
    } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
      Serial.println("Error komunikasi.");
    } else if (p == FINGERPRINT_IMAGEFAIL) {
      Serial.println("Gagal ambil gambar.");
    }
  }

  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) {
    Serial.println("image2Tz(1) gagal.");
    return p;
  }

  Serial.println("Angkat jari...");
  delay(1500);

  while (finger.getImage() != FINGERPRINT_NOFINGER) {
    delay(50);
  }

  Serial.println("Tempelkan lagi jari yang sama...");

  p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();

    if (p == FINGERPRINT_NOFINGER) {
      delay(50);
    }
  }

  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) {
    Serial.println("image2Tz(2) gagal.");
    return p;
  }

  p = finger.createModel();
  if (p != FINGERPRINT_OK) {
    Serial.println("createModel gagal.");
    return p;
  }

  p = finger.storeModel(id);

  if (p == FINGERPRINT_OK) {
    Serial.println("Enroll sukses!");
  } else {
    Serial.println("storeModel gagal.");
  }

  return p;
}

/* =========================
   Scan Fingerprint
   ========================= */
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

/* =========================
   Read Serial Command
   ketik:
   e1
   e2
   e10
   ========================= */
bool readEnrollCommand(uint16_t &id) {

  if (!Serial.available())
    return false;

  String cmd = Serial.readStringUntil('\n');
  cmd.trim();
  cmd.toLowerCase();

  if (cmd.startsWith("e")) {

    int val = cmd.substring(1).toInt();

    if (val > 0 && val <= 200) {
      id = val;
      return true;
    }
  }

  return false;
}

void setup() {

  Serial.begin(115200);
  delay(200);

  Serial.println("\nBOOTING...");

  FPSerial.begin(57600);

  finger.begin(57600);

  if (finger.verifyPassword()) {

    Serial.println("Fingerprint sensor OK");

  } else {

    Serial.println("Fingerprint sensor ERROR");
    while (1)
      delay(1);
  }

  finger.getTemplateCount();

  Serial.println("Template tersimpan: " + String(finger.templateCount));

  Serial.println("\nREADY");
  Serial.println("Ketik e1 / e2 / e10 untuk enroll");
}

void loop() {

  // ENROLL
  uint16_t enrollId;

  if (readEnrollCommand(enrollId)) {

    enrollFingerprint(enrollId);

    finger.getTemplateCount();

    Serial.println("Total template: " + String(finger.templateCount));

    Serial.println("\nREADY");
  }

  // SCAN
  uint16_t id, conf;

  int16_t res = identifyFingerprint(id, conf);

  if (res == FINGERPRINT_OK) {

    Serial.println("\n=== MATCH ===");
    Serial.println("ID   : " + String(id));
    Serial.println("Nama : " + fingerName(id));
    Serial.println("Conf : " + String(conf));

    while (finger.getImage() != FINGERPRINT_NOFINGER) {
      delay(50);
    }

    delay(300);
  }

  else if (res == FINGERPRINT_NOTFOUND) {

    Serial.println("Fingerprint tidak dikenal");

    while (finger.getImage() != FINGERPRINT_NOFINGER) {
      delay(50);
    }

    delay(300);
  }

  delay(50);
}