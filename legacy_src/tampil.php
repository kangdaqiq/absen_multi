<?php
// tampil.php (perbaikan lengkap)
// aktifkan error display sementara untuk debugging (hapus di produksi jika perlu)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// pastikan path db.php benar:
// Jika db.php ada di folder yang sama gunakan:
// require_once __DIR__ . '/db.php';

// Jika db.php ada di folder includes gunakan:
// require_once __DIR__ . '/includes/db.php';

// Sesuaikan baris berikut dengan lokasi db.php Anda:
require_once __DIR__ . '/includes/db.php';

// AJAX data provider (mengembalikan JSON, dengan handling error)
if (isset($_GET['ajax'])) {
    header("Content-Type: application/json; charset=utf-8");

    try {
        $sql = "SELECT 
                    a.id,
                    s.nis,
                    s.nama,
                    s.kelas,
                    a.tanggal,
                    a.jam_masuk,
                    a.jam_pulang
                FROM attendance a
                LEFT JOIN siswa s ON s.id = a.student_id
                WHERE DATE(a.tanggal) = CURDATE()
                ORDER BY a.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // pastikan semua id ada dan numeric
        foreach ($rows as &$r) {
            if (isset($r['id'])) $r['id'] = (int)$r['id'];
        }
        unset($r);

        echo json_encode($rows);
        exit;
    } catch (Exception $e) {
        // log error dan kembalikan JSON agar fetch .json() tidak pecah
        error_log("tampil.php ajax error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sistem Absensi - SMK ASSUNNIYYAH TUMIJAJAR</title>

<!-- BOOTSTRAP 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- DATA TABLES -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* (CSS sama seperti sebelumnya — tetap) */
body { background: #eef2f7; font-family: 'Segoe UI', Arial, sans-serif; }
.header-box { background: linear-gradient(135deg, #1e6ed8, #2d89ef); padding: 35px 20px; color: white; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.2); margin-bottom: 25px; }
.logo-sekolah { width: 95px; height: 95px; object-fit: contain; display:block; margin: 0 auto 8px auto; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.3)); }
.header-title { font-size: 34px; font-weight: 700; letter-spacing: 1px; }
.header-school { font-size: 20px; opacity:0.95; }
.date-box { margin-top:10px; font-size:16px; font-weight:500; }
#jam { font-size:24px; font-weight:700; }
.table-box { background:white; padding:20px; border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.1); }
#notif { position: fixed; right: 20px; bottom: 20px; background: linear-gradient(90deg,#16a34a,#10b981); color:white; padding:14px 18px; border-radius:10px; display:none; box-shadow:0 10px 30px rgba(16,185,129,0.25); font-size:14px; z-index:9999; max-width:320px; transition: opacity .25s; }
#notif .title { font-weight:700; margin-bottom:6px; }
#notif .count-pill { background: rgba(255,255,255,0.14); padding:4px 8px; border-radius:999px; margin-left:8px; font-weight:600; }
#notif .list { font-size:13px; opacity:.95; line-height:1.2; }
.flash-new { animation: flashNeon 2.2s ease forwards; position:relative; z-index:5; }
@keyframes flashNeon { 0% { background-color:#eaffef; box-shadow:0 0 0 rgba(34,197,94,0.0);} 25% { background-color:#dff7e6; box-shadow:0 0 10px rgba(34,197,94,0.35);} 50% { background-color:#c8f3d0; box-shadow:0 0 22px rgba(34,197,94,0.55);} 80% { background-color:#eaf9ef; box-shadow:0 0 12px rgba(34,197,94,0.30);} 100% { background-color:transparent; box-shadow:none;} }
</style>
</head>
<body>

<!-- HEADER -->
<div class="header-box">
    <?php if (file_exists(__DIR__.'/logo.png')): ?>
        <img src="logo.png" class="logo-sekolah" alt="Logo Sekolah">
    <?php endif; ?>

    <div class="header-title"><i class="fa-solid fa-fingerprint"></i> Sistem Absensi</div>
    <div class="header-school">SMK ASSUNNIYYAH TUMIJAJAR</div>

    <div class="date-box">
        <div id="tanggal"></div>
        <div id="jam"></div>
    </div>
</div>

<div class="container mb-5">
    <div class="table-box">
        <h5><i class="fa-solid fa-list-check"></i> Data Absensi</h5>

        <table id="absensiTable" class="table table-striped table-bordered nowrap" width="100%">
            <thead>
                <tr>
                    <th style="display:none;">ID</th>
                    <th>No</th>
                    <th>NISN</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- NOTIFICATION -->
<div id="notif">
    <div class="title">Absensi Baru <span class="count-pill" id="notif-count">0</span></div>
    <div class="list" id="notif-list"></div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
// tanggal & jam
function formatTanggalIndonesia(date){
    const bulan=["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    return `${date.getDate()} ${bulan[date.getMonth()]} ${date.getFullYear()}`;
}
function updateDateTime(){ const now=new Date(); document.getElementById("tanggal").textContent = formatTanggalIndonesia(now); document.getElementById("jam").textContent = now.toLocaleTimeString("id-ID",{hour:"2-digit",minute:"2-digit"}) + " WIB"; }
setInterval(updateDateTime,1000); updateDateTime();

// beep
function playBeep(){ try {
    const ctx = new (window.AudioContext||window.webkitAudioContext)();
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type='sine'; o.frequency.value=900;
    g.gain.value=0.001;
    o.connect(g); g.connect(ctx.destination);
    o.start();
    g.gain.linearRampToValueAtTime(0.08, ctx.currentTime+0.01);
    g.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime+0.2);
    o.stop(ctx.currentTime+0.22);
} catch(e){ console.warn('beep err', e); } }

// globals
let table=null, prevMaxId=0, initialLoad=true, lastHighlightIds=[];

function timeOnly(dt){ if(!dt) return "-"; const p=dt.split(" "); return p.length===2? p[1]: dt; }

function buildRows(data){
    const rows=[]; let no=1;
    data.forEach(r=>{
        const id = Number(r.id);
        const noCell = `<span class="row-id" data-id="${id}">${no++}</span>`;
        rows.push([
            id,
            noCell,
            r.nis ?? "-",
            r.nama ?? "-",
            r.kelas ?? "-",
            r.tanggal ?? "-",
            r.jam_masuk ? timeOnly(r.jam_masuk) : "-",
            r.jam_pulang ? timeOnly(r.jam_pulang) : "-"
        ]);
    });
    return rows;
}

const notifEl = document.getElementById("notif");
const notifListEl = document.getElementById("notif-list");
const notifCountEl = document.getElementById("notif-count");

function showEnhancedNotif(entries){
    if(!entries || entries.length===0) return;
    const maxShow=4;
    const shown = entries.slice(0,maxShow).map(e=> `${e.nama} • ${e.kelas}`).join("<br>");
    notifListEl.innerHTML = shown;
    notifCountEl.textContent = entries.length;
    notifEl.style.display="block"; notifEl.style.opacity="1"; playBeep();
    setTimeout(()=>{ notifEl.style.opacity="0"; setTimeout(()=>notifEl.style.display="none",300); }, 4000);
}

function highlightRowIfNeeded(tr){
    const idSpan = tr.querySelector(".row-id");
    if(!idSpan) return;
    const id = Number(idSpan.dataset.id);
    if(lastHighlightIds.includes(id)){ tr.classList.add("flash-new"); setTimeout(()=>tr.classList.remove("flash-new"),2200); }
}

function loadData(){
    fetch("tampil.php?ajax=1",{cache:"no-store"})
    .then(r=>{
        if(!r.ok) throw new Error("HTTP "+r.status);
        return r.json();
    })
    .then(data=>{
        if(!Array.isArray(data)) {
            // jika server mengembalikan object error {error:...}
            if(data && data.error) console.error("Server JSON error:", data.error);
            return;
        }

        const currentMaxId = data.length>0 ? Number(data[0].id) : 0;
        let added=[];
        if(!initialLoad && currentMaxId > prevMaxId){
            for(const r of data){
                const id = Number(r.id);
                if(id > prevMaxId) added.push({id:id, nama: r.nama ?? "-", kelas: r.kelas ?? "-"});
                else break;
            }
        }
        lastHighlightIds = added.map(a=>a.id);

        const rows = buildRows(data);

        if(!table){
            table = $('#absensiTable').DataTable({
                data: rows,
                responsive:true,
                order:[[0,"desc"]], // sort by hidden ID desc -> newest top
                pageLength:10,
                language:{url:"https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"},
                columnDefs:[
                    { targets: 0, visible: false, searchable: false },
                    { orderable: false, targets: 1 }
                ],
                rowCallback:function(row){ highlightRowIfNeeded(row); }
            });
            initialLoad=false;
            prevMaxId=currentMaxId;
        } else {
            table.clear().rows.add(rows).draw(false);

            if(added.length>0){
                showEnhancedNotif(added);
                setTimeout(()=>{
                    const first = document.querySelector(`.row-id[data-id="${added[0].id}"]`);
                    if(first){ const tr = first.closest("tr"); if(tr) tr.scrollIntoView({behavior:"smooth", block:"center"}); }
                },80);
            }
            prevMaxId = Math.max(prevMaxId, currentMaxId);
        }
    })
    .catch(err=>{
        console.error("loadData error:", err);
    });
}

loadData();
setInterval(loadData,2000);
</script>

</body>
</html>
