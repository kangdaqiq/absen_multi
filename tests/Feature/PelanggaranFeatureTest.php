<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\KategoriPelanggaran;
use App\Models\Kelas;
use App\Models\Pelanggaran;
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
            'role' => 'guru',
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
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->teacherUser);

        Pelanggaran::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'code' => 'PLG-REKAP1',
            'tanggal' => now()->format('Y-m-d'),
            'judul_pelanggaran' => 'Terlambat upacara',
            'kategori' => 'ringan',
            'poin' => 5,
            'status' => 'tercatat',
        ]);

        $this->get('/pelanggaran/rekap')
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
}
