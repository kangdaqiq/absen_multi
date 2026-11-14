<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\KategoriPelanggaran;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\PenangananPelanggaran;
use App\Models\School;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PelanggaranFeatureTest extends TestCase
{
    protected School $school;
    protected User $teacherUser;
    protected Guru $guru;
    protected Kelas $kelas;
    protected User $studentUser;
    protected Siswa $student;
    protected KategoriPelanggaran $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
        ]);
        \Illuminate\Support\Facades\DB::purge('sqlite');

        $this->createMinimalSchema();

        $this->school = School::create([
            'name' => 'SMA Negeri 1 Test',
            'code' => 'SMAN1',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'full_name' => 'Budi Santoso',
            'username' => 'guru_budi',
            'email' => 'budi@school.test',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'school_id' => $this->school->id,
        ]);

        $this->guru = Guru::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'nama' => 'Budi Santoso',
            'nip' => '19800101',
        ]);

        $this->kelas = Kelas::create([
            'school_id' => $this->school->id,
            'nama_kelas' => 'X IPA 1',
            'tingkat' => '10',
            'wali_kelas_id' => $this->guru->id,
            'is_active_attendance' => true,
        ]);

        $this->studentUser = User::create([
            'full_name' => 'Ani Wijaya',
            'username' => 'siswa_1001',
            'email' => 'ani@school.test',
            'password_hash' => Hash::make('password'),
            'role' => 'student',
            'school_id' => $this->school->id,
        ]);

        $this->student = Siswa::create([
            'school_id' => $this->school->id,
            'user_id' => $this->studentUser->id,
            'kelas_id' => $this->kelas->id,
            'nama' => 'Ani Wijaya',
            'nis' => '1001',
        ]);

        $this->kategori = KategoriPelanggaran::create([
            'school_id' => $this->school->id,
            'kode' => 'PLG-01',
            'nama' => 'Terlambat masuk kelas',
            'kategori' => 'ringan',
            'poin' => 10,
            'sanksi_default' => 'Teguran lisan',
            'is_active' => true,
        ]);
    }

    private function createMinimalSchema(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password_hash')->nullable();
            $table->string('plain_password')->nullable();
            $table->string('role')->default('student');
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });

        Schema::create('guru', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('nama_kelas');
            $table->string('tingkat')->nullable();
            $table->unsignedBigInteger('wali_kelas_id')->nullable();
            $table->unsignedBigInteger('wali_kelas_2_id')->nullable();
            $table->boolean('is_active_attendance')->default(true);
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('nama');
            $table->string('nis')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('wa_ortu')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });

        Schema::create('kategori_pelanggarans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('kode', 30)->nullable();
            $table->string('nama', 150);
            $table->string('kategori')->default('ringan');
            $table->unsignedInteger('poin')->default(5);
            $table->text('sanksi_default')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pelanggarans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('kategori_pelanggaran_id')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->string('code', 30)->unique();
            $table->date('tanggal');
            $table->string('waktu', 10)->nullable();
            $table->string('judul_pelanggaran', 200);
            $table->string('kategori')->default('ringan');
            $table->unsignedInteger('poin')->default(5);
            $table->text('keterangan')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->string('bukti_foto')->nullable();
            $table->string('status')->default('tercatat');
            $table->timestamps();
        });

        Schema::create('penanganan_pelanggarans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('pelanggaran_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->date('tanggal');
            $table->string('tindakan_sp')->default('pembinaan');
            $table->text('catatan_penanganan');
            $table->text('hasil_kesepakatan')->nullable();
            $table->string('link_gdrive', 1000)->nullable();
            $table->string('lampiran_file')->nullable();
            $table->string('status_penanganan')->default('dalam_proses');
            $table->timestamps();
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('api_key')->nullable();
            $table->string('action')->nullable();
            $table->string('uid')->nullable();
            $table->boolean('success')->default(true);
            $table->text('message')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });
    }

    public function test_teacher_can_record_update_and_delete_violation_via_mobile_api(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->teacherUser);

        // Fetch kategori & students
        $this->getJson('/api/mobile/guru/pelanggaran/kategori')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/mobile/guru/pelanggaran/students')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Record violation using master kategori + photo
        $file = UploadedFile::fake()->image('bukti.jpg');
        $storeResp = $this->post('/api/mobile/guru/pelanggaran', [
            'student_id' => $this->student->id,
            'kategori_pelanggaran_id' => $this->kategori->id,
            'tanggal' => '2026-11-12',
            'keterangan' => 'Datang terlambat 20 menit',
            'bukti_foto' => $file,
        ], ['Accept' => 'application/json']);

        $storeResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.judul_pelanggaran', 'Terlambat masuk kelas')
            ->assertJsonPath('data.poin', 10)
            ->assertJsonPath('data.status', 'tercatat');

        $id = $storeResp->json('data.id');
        $buktiPath = $storeResp->json('data.bukti_foto');
        Storage::disk('public')->assertExists($buktiPath);

        // Teacher index
        $this->getJson('/api/mobile/guru/pelanggaran')
            ->assertStatus(200)
            ->assertJsonPath('summary.total_kasus', 1)
            ->assertJsonPath('summary.total_poin', 10);

        // Teacher update status
        $this->patchJson("/api/mobile/guru/pelanggaran/{$id}", [
            'status' => 'selesai',
            'tindak_lanjut' => 'Sudah diberi pembinaan',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'selesai')
            ->assertJsonPath('data.tindak_lanjut', 'Sudah diberi pembinaan');

        // Student can view own violation
        Sanctum::actingAs($this->studentUser);
        $this->getJson('/api/mobile/pelanggaran/my')
            ->assertStatus(200)
            ->assertJsonPath('summary.total_kasus', 1)
            ->assertJsonPath('summary.total_poin', 10);

        $this->getJson("/api/mobile/pelanggaran/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id);

        // Student cannot record or delete
        $this->postJson('/api/mobile/guru/pelanggaran', [])->assertStatus(403);
        $this->deleteJson("/api/mobile/guru/pelanggaran/{$id}")->assertStatus(403);

        // Teacher deletes violation
        Sanctum::actingAs($this->teacherUser);
        $this->deleteJson("/api/mobile/guru/pelanggaran/{$id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('pelanggarans', ['id' => $id]);
        Storage::disk('public')->assertMissing($buktiPath);
    }

    public function test_web_controller_can_store_update_and_delete_violation_and_kategori(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->teacherUser);

        $this->post('/pelanggaran', [
            'student_id' => $this->student->id,
            'tanggal' => '2026-11-12',
            'judul_pelanggaran' => 'Tidak memakai dasi',
            'kategori' => 'ringan',
            'poin' => 5,
            'keterangan' => 'Saat upacara Senin',
        ])->assertRedirect();

        $pelanggaran = Pelanggaran::first();
        $this->assertNotNull($pelanggaran);
        $this->assertEquals('Tidak memakai dasi', $pelanggaran->judul_pelanggaran);

        $this->put("/pelanggaran/{$pelanggaran->id}", [
            'status' => 'diproses',
            'tindak_lanjut' => 'Dipanggil wali kelas',
        ])->assertRedirect();

        $this->assertEquals('diproses', $pelanggaran->fresh()->status);

        $this->delete("/pelanggaran/{$pelanggaran->id}")->assertRedirect();
        $this->assertDatabaseMissing('pelanggarans', ['id' => $pelanggaran->id]);

        // Store & delete kategori
        $this->post('/pelanggaran/kategori', [
            'nama' => 'Merokok di area sekolah',
            'kategori' => 'berat',
            'poin' => 50,
        ])->assertRedirect();

        $kat = KategoriPelanggaran::where('nama', 'Merokok di area sekolah')->first();
        $this->assertNotNull($kat);
        $this->delete("/pelanggaran/kategori/{$kat->id}")->assertRedirect();
        $this->assertDatabaseMissing('kategori_pelanggarans', ['id' => $kat->id]);
    }

    public function test_web_controller_can_view_rekap_and_export(): void
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->teacherUser);

        $p = Pelanggaran::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'code' => 'PLG-REKAP1',
            'tanggal' => '2026-11-12',
            'judul_pelanggaran' => 'Terlambat upacara',
            'kategori' => 'ringan',
            'poin' => 5,
            'status' => 'tercatat',
        ]);

        $this->get('/pelanggaran/rekap?start_date=2026-11-01&end_date=2026-11-30')
            ->assertStatus(200)
            ->assertSee('Rekapitulasi Pelanggaran Siswa')
            ->assertSee('Terlambat upacara');

        $this->get('/pelanggaran/export-excel')
            ->assertStatus(200);

        $this->get('/pelanggaran/export-pdf')
            ->assertStatus(200);
    }

    public function test_reset_password_siswa_to_birthdate(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->teacherUser);

        $this->student->update(['tgl_lahir' => '2008-05-15']);
        $this->studentUser->update(['password_hash' => Hash::make('CustomPass123')]);

        $resp = $this->postJson("/siswa/{$this->student->id}/reset-password");
        $resp->assertStatus(200)
            ->assertJsonPath('success', true);

        // Password hash now matches 15052008
        $this->studentUser->refresh();
        $this->assertTrue(Hash::check('15052008', $this->studentUser->password_hash));
    }

    public function test_bk_can_record_and_delete_penanganan_sp_and_link_gdrive(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->teacherUser);

        $pelanggaran = Pelanggaran::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'code' => 'PLG-BK01',
            'tanggal' => '2026-11-13',
            'judul_pelanggaran' => 'Merokok di area sekolah',
            'kategori' => 'berat',
            'poin' => 50,
            'status' => 'diproses',
        ]);

        $res = $this->from('/pelanggaran')->post("/pelanggaran/{$pelanggaran->id}/penanganan", [
            'tanggal' => '2026-11-13',
            'tindakan_sp' => 'sp_2',
            'catatan_penanganan' => 'Pemanggilan orang tua dan bimbingan konseling intensif.',
            'hasil_kesepakatan' => 'Siswa menandatangani surat peringatan kedua dan berjanji tidak mengulangi.',
            'link_gdrive' => 'https://drive.google.com/file/d/12345sample/view',
            'status_penanganan' => 'dalam_proses',
        ]);

        $res->assertSessionHas('success');
        $this->assertDatabaseHas('penanganan_pelanggarans', [
            'pelanggaran_id' => $pelanggaran->id,
            'tindakan_sp' => 'sp_2',
            'link_gdrive' => 'https://drive.google.com/file/d/12345sample/view',
        ]);

        $pelanggaran->refresh();
        $this->assertStringContainsString('SP 2', $pelanggaran->tindak_lanjut);

        $penanganan = $pelanggaran->penanganans()->first();
        $del = $this->delete("/pelanggaran/penanganan/{$penanganan->id}");
        $del->assertSessionHas('success');
        $this->assertDatabaseMissing('penanganan_pelanggarans', ['id' => $penanganan->id]);
    }

    public function test_dedicated_penanganan_page_export_and_student_dossier(): void
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->teacherUser);

        $pelanggaran = Pelanggaran::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'code' => 'PLG-HIST01',
            'tanggal' => '2026-11-13',
            'judul_pelanggaran' => 'Membolos jam pelajaran',
            'kategori' => 'sedang',
            'poin' => 20,
            'status' => 'diproses',
        ]);

        $penanganan = PenangananPelanggaran::create([
            'school_id' => $this->school->id,
            'pelanggaran_id' => $pelanggaran->id,
            'student_id' => $this->student->id,
            'handled_by' => $this->teacherUser->id,
            'tanggal' => '2026-11-13',
            'tindakan_sp' => 'sp_1',
            'catatan_penanganan' => 'Bimbingan konseling dan penandatanganan SP 1.',
            'hasil_kesepakatan' => 'Siswa berkomitmen tidak mengulangi perbuatannya.',
            'link_gdrive' => 'https://drive.google.com/file/d/testsp1/view',
            'status_penanganan' => 'dalam_proses',
        ]);

        // 1. Dedicated index page
        $this->get('/pelanggaran/penanganan')
            ->assertStatus(200)
            ->assertSee('Riwayat Penanganan &amp; Konseling BK', false)
            ->assertSee('Ani Wijaya')
            ->assertSee('https://drive.google.com/file/d/testsp1/view');

        // 2. Student history dossier JSON
        $dossier = $this->getJson("/pelanggaran/penanganan/student/{$this->student->id}");
        $dossier->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.nama', 'Ani Wijaya')
            ->assertJsonPath('student.total_poin', 20)
            ->assertJsonPath('penanganans.0.tindakan_sp', 'sp_1')
            ->assertJsonPath('penanganans.0.link_gdrive', 'https://drive.google.com/file/d/testsp1/view');

        // 3. Export Excel
        $this->get('/pelanggaran/penanganan/export-excel')
            ->assertStatus(200);
    }

    public function test_rekap_and_filter_and_direct_penanganan_flow(): void
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->teacherUser);

        // Siswa 2 (tanpa pelanggaran)
        $cleanStudent = Siswa::create([
            'school_id' => $this->school->id,
            'kelas_id'  => $this->kelas->id,
            'nama'      => 'Bambang Tertib',
            'nis'       => '1002',
        ]);

        // Buat 2 pelanggaran untuk student Ani
        $v1 = Pelanggaran::create([
            'school_id'         => $this->school->id,
            'student_id'        => $this->student->id,
            'code'              => 'PLG-FLOW-01',
            'tanggal'           => '2026-11-10',
            'judul_pelanggaran' => 'Terlambat upacara bendera',
            'kategori'          => 'ringan',
            'poin'              => 10,
            'status'            => 'tercatat',
        ]);

        $v2 = Pelanggaran::create([
            'school_id'         => $this->school->id,
            'student_id'        => $this->student->id,
            'code'              => 'PLG-FLOW-02',
            'tanggal'           => '2026-11-12',
            'judul_pelanggaran' => 'Atribut seragam tidak lengkap',
            'kategori'          => 'ringan',
            'poin'              => 5,
            'status'            => 'tercatat',
        ]);

        // 1. Rekap page: Menampilkan Ani (akumulasi 15 poin)
        $resRekap = $this->get('/pelanggaran/rekap');
        $resRekap->assertStatus(200)
            ->assertSee('Ani Wijaya')
            ->assertSee('15 Poin')
            ->assertSee('2 Kasus')
            ->assertSee('/pelanggaran?student_id=' . $this->student->id);

        // 2. Klik detail di rekap -> masuk ke /pelanggaran?student_id=XX
        $resIndexFilter = $this->get("/pelanggaran?student_id={$this->student->id}");
        $resIndexFilter->assertStatus(200)
            ->assertSee('Filter Siswa Aktif')
            ->assertSee('Ani Wijaya')
            ->assertSee('PLG-FLOW-01')
            ->assertSee('PLG-FLOW-02')
            ->assertSee('Reset Filter');

        // 3. Catat Penanganan BK langsung memilih Siswa (student_id)
        $resPenangananStore = $this->post('/pelanggaran/penanganan', [
            'student_id'         => $this->student->id,
            'tanggal'            => '2026-11-13',
            'tindakan_sp'        => 'sp_1',
            'catatan_penanganan' => 'Konseling penertiban atribut & upacara bendera.',
            'hasil_kesepakatan'  => 'Siswa berjanji mematuhi aturan sekolah.',
            'link_gdrive'        => 'https://drive.google.com/file/d/sp1ani/view',
            'status_penanganan'  => 'dalam_proses',
        ]);

        $resPenangananStore->assertSessionHas('success');

        $this->assertDatabaseHas('penanganan_pelanggarans', [
            'student_id'  => $this->student->id,
            'tindakan_sp' => 'sp_1',
            'link_gdrive' => 'https://drive.google.com/file/d/sp1ani/view',
        ]);

        // Pastikan status pelanggaran terbaru ter-update otomatis
        $v2->refresh();
        $this->assertEquals('diproses', $v2->status);
        $this->assertStringContainsString('SP 1', $v2->tindak_lanjut);
    }

    public function test_mobile_api_rekap_and_penanganan_endpoints(): void
    {
        $this->withoutExceptionHandling();
        Sanctum::actingAs($this->teacherUser);

        // Buat 1 pelanggaran untuk student Ani
        $v = Pelanggaran::create([
            'school_id'         => $this->school->id,
            'student_id'        => $this->student->id,
            'code'              => 'PLG-MBL-01',
            'tanggal'           => '2026-11-13',
            'judul_pelanggaran' => 'Merokok di area sekolah',
            'kategori'          => 'berat',
            'poin'              => 50,
            'status'            => 'tercatat',
        ]);

        // 1. Mobile Rekap Pelanggaran
        $rekapRes = $this->getJson('/api/mobile/guru/pelanggaran/rekap');
        $rekapRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_siswa_pelanggar', 1)
            ->assertJsonPath('data.0.id', $this->student->id)
            ->assertJsonPath('data.0.total_poin', 50)
            ->assertJsonPath('data.0.total_kasus', 1)
            ->assertJsonPath('data.0.display_text', "Ani Wijaya (50 Poin) - X IPA 1");

        // 2. Mobile Penanganan Students Dropdown (hanya yang berpoin)
        $studentsRes = $this->getJson('/api/mobile/guru/penanganan/students');
        $studentsRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $this->student->id)
            ->assertJsonPath('data.0.total_poin', 50)
            ->assertJsonPath('data.0.violations.0.code', 'PLG-MBL-01');

        // 3. Guru mencatat penanganan BK via mobile
        $postRes = $this->postJson('/api/mobile/guru/penanganan', [
            'student_id'         => $this->student->id,
            'tindakan_sp'        => 'sp_2',
            'catatan_penanganan' => 'Pemanggilan orang tua dan skorsing 3 hari.',
            'hasil_kesepakatan'  => 'Orang tua menandatangani surat SP 2.',
            'link_gdrive'        => 'https://drive.google.com/file/d/mblsp2/view',
            'status_penanganan'  => 'dalam_proses',
        ]);

        $postRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tindakan_sp', 'sp_2')
            ->assertJsonPath('data.link_gdrive', 'https://drive.google.com/file/d/mblsp2/view');

        $penangananId = $postRes->json('data.id');

        // 4. Guru melihat daftar penanganan BK
        $indexRes = $this->getJson('/api/mobile/guru/penanganan');
        $indexRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('data.0.id', $penangananId);

        // 5. Guru melihat dossier utuh siswa
        $dossierRes = $this->getJson("/api/mobile/guru/penanganan/student/{$this->student->id}");
        $dossierRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.nama', 'Ani Wijaya')
            ->assertJsonPath('violations.0.code', 'PLG-MBL-01')
            ->assertJsonPath('penanganans.0.tindakan_sp', 'sp_2');

        // 6. Akun Siswa melihat riwayat penanganan/SP milik sendiri
        Sanctum::actingAs($this->studentUser);
        $myPenRes = $this->getJson('/api/mobile/student/penanganan/my');
        $myPenRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_penanganan', 1)
            ->assertJsonPath('data.0.id', $penangananId)
            ->assertJsonPath('data.0.link_gdrive', 'https://drive.google.com/file/d/mblsp2/view');

        // 7. Guru menghapus penanganan
        Sanctum::actingAs($this->teacherUser);
        $delRes = $this->deleteJson("/api/mobile/guru/penanganan/{$penangananId}");
        $delRes->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('penanganan_pelanggarans', ['id' => $penangananId]);
    }
}
