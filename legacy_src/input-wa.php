<?php
require_once __DIR__ . '/includes/db.php';

/* ================= CONFIG ================= */
define('WA_API_URL', 'http://localhost:3000/send/message');
define('WA_BASIC_AUTH', 'admin:04112000');

/* ================= AJAX ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if ($_POST['ajax'] === 'get_siswa') {
        $stmt = $pdo->prepare("SELECT id, nama FROM siswa WHERE kelas_id=? ORDER BY nama");
        $stmt->execute([(int)$_POST['kelas_id']]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($_POST['ajax'] === 'simpan_wa') {
        $id = (int)$_POST['siswa_id'];
        $wa = trim($_POST['no_wa']);

        if (!$id || !preg_match('/^08\d{8,12}$/', $wa)) {
            echo json_encode(['ok'=>false,'message'=>'Nomor WA tidak valid']);
            exit;
        }

        $wa = preg_replace('/^08/', '628', $wa);

        $stmt = $pdo->prepare("SELECT nama FROM siswa WHERE id=?");
        $stmt->execute([$id]);
        $s = $stmt->fetch();

        if (!$s) {
            echo json_encode(['ok'=>false,'message'=>'Siswa tidak ditemukan']);
            exit;
        }

        $pdo->prepare("UPDATE siswa SET no_wa=? WHERE id=?")
            ->execute([$wa, $id]);

        $msg = "Assalamu’alaikum {$s['nama']},\n\nNomor WhatsApp ini telah terdaftar untuk informasi sekolah.\n\nTerima kasih 🙏";

        $ch = curl_init(WA_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['phone'=>$wa,'message'=>$msg]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(WA_BASIC_AUTH)
            ]
        ]);
        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        echo json_encode($err
            ? ['ok'=>false,'message'=>'Gagal kirim WA']
            : ['ok'=>true]
        );
        exit;
    }
}

$kelas = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">


<title>Input WhatsApp Siswa</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root {
  --primary:#4f46e5;
  --bg:#f3f4f6;
  --card:#ffffff;
  --success:#16a34a;
  --error:#dc2626;
  --warning:#f59e0b;
}

* { box-sizing:border-box; font-family:system-ui,-apple-system; }

body {
  background:var(--bg);
  padding:30px;
}

.card {
  max-width:480px;
  margin:auto;
  background:var(--card);
  border-radius:14px;
  padding:24px;
  box-shadow:0 10px 25px rgba(0,0,0,.08);
}

h2 {
  margin:0 0 5px;
  color:#111827;
}

.subtitle {
  color:#6b7280;
  margin-bottom:20px;
  font-size:14px;
}

label {
  font-weight:600;
  margin-top:15px;
  display:block;
}

select, input {
  width:100%;
  padding:12px;
  margin-top:6px;
  border-radius:10px;
  border:1px solid #d1d5db;
  font-size:15px;
}

select:focus, input:focus {
  outline:none;
  border-color:var(--primary);
}

button {
  width:100%;
  margin-top:20px;
  padding:12px;
  border:none;
  border-radius:12px;
  background:var(--primary);
  color:#fff;
  font-size:16px;
  cursor:pointer;
}

button:hover { opacity:.9 }

.badge {
  margin-top:15px;
  padding:10px;
  border-radius:10px;
  font-weight:600;
  text-align:center;
}

.success { background:#dcfce7; color:var(--success); }
.error   { background:#fee2e2; color:var(--error); }
.loading { background:#fef3c7; color:var(--warning); }

.hidden { display:none }
.school-title {
  text-align:center;
  font-weight:800;
  letter-spacing:1.5px;
  color:#1f2937;
  font-size:18px;
  margin-bottom:10px;
}

</style>
</head>
<body>

<div class="card">
<div class="school-title">SMK ASSUNIYAH</div>
  <h2>📱 Input WhatsApp Siswa</h2>
  <div class="subtitle">Pendaftaran nomor WA & notifikasi otomatis</div>

  <label>Kelas</label>
  <select id="kelas">
    <option value="">Pilih kelas</option>
    <?php foreach ($kelas as $k): ?>
      <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
    <?php endforeach ?>
  </select>

  <div id="wrap-siswa" class="hidden">
    <label>Siswa</label>
    <select id="siswa">
      <option value="">Pilih siswa</option>
    </select>
  </div>

  <div id="wrap-form" class="hidden">
    <label>Nomor WhatsApp</label>
    <input type="text" id="no_wa" placeholder="08xxxxxxxxxx">
    <button onclick="simpan()">Simpan & Kirim WA</button>
  </div>

  <div id="status"></div>
</div>

<script>
const kelas = document.getElementById('kelas');
const siswa = document.getElementById('siswa');
const wrapSiswa = document.getElementById('wrap-siswa');
const wrapForm = document.getElementById('wrap-form');
const statusBox = document.getElementById('status');

kelas.onchange = () => {
  wrapForm.classList.add('hidden');
  wrapSiswa.classList.add('hidden');
  statusBox.innerHTML = '';

  if (!kelas.value) return;

  fetch('', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'ajax=get_siswa&kelas_id='+kelas.value
  })
  .then(r=>r.json())
  .then(data=>{
    siswa.innerHTML='<option value="">Pilih siswa</option>';
    data.forEach(s=> siswa.innerHTML+=`<option value="${s.id}">${s.nama}</option>`);
    wrapSiswa.classList.remove('hidden');
  });
};

siswa.onchange = () => {
  wrapForm.classList.toggle('hidden', !siswa.value);
  statusBox.innerHTML='';
};

function simpan() {
  const wa = no_wa.value.trim();
  if (!/^08\d{8,12}$/.test(wa)) {
    statusBox.innerHTML='<div class="badge error">Nomor WA tidak valid</div>';
    return;
  }

  statusBox.innerHTML='<div class="badge loading">Mengirim WhatsApp…</div>';

  fetch('', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'ajax=simpan_wa&siswa_id='+siswa.value+'&no_wa='+wa
  })
  .then(r=>r.json())
  .then(res=>{
    statusBox.innerHTML = res.ok
      ? '<div class="badge success">WhatsApp berhasil dikirim</div>'
      : '<div class="badge error">'+res.message+'</div>';
  });
}
</script>

</body>
</html>
