<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\School;
use App\Models\Siswa;
use App\Models\StudentLeave;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileStudentLeaveApiTest extends TestCase
{
    protected School $school;
    protected User $teacherUser;
    protected Guru $guru;
    protected Kelas $kelas;
    protected Kelas $otherKelas;
    protected User $studentUser;
    protected Siswa $student;
    protected User $otherStudentUser;
    protected Siswa $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        \Illuminate\Support\Facades\DB::purge('sqlite');

        $this->createMinimalSchema();
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendLeaveSubmittedNotification')->andReturnNull();
            $mock->shouldReceive('sendLeaveApprovedNotification')->andReturnNull();
            $mock->shouldReceive('sendLeaveRejectedNotification')->andReturnNull();
        });

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

        $this->otherKelas = Kelas::create([
            'school_id' => $this->school->id,
            'nama_kelas' => 'X IPA 2',
            'tingkat' => '10',
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
            'wa_ortu' => '081234567890',
        ]);

        $this->otherStudentUser = User::create([
            'full_name' => 'Citra Dewi',
            'username' => 'siswa_1002',
            'email' => 'citra@school.test',
            'password_hash' => Hash::make('password'),
            'role' => 'student',
            'school_id' => $this->school->id,
        ]);

        $this->otherStudent = Siswa::create([
            'school_id' => $this->school->id,
            'user_id' => $this->otherStudentUser->id,
            'kelas_id' => $this->otherKelas->id,
            'nama' => 'Citra Dewi',
            'nis' => '1002',
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
            $table->string('no_wa')->nullable();
            $table->string('wa_ortu')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });

        Schema::create('student_leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->string('code', 30)->unique();
            $table->string('jenis')->default('izin');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('keterangan');
            $table->string('bukti_foto')->nullable();
            $table->string('pengaju')->default('siswa');
            $table->string('nama_pengaju')->nullable();
            $table->string('no_wa_pengaju', 25)->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->date('tanggal');
            $table->string('status', 10)->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_auto_alpha')->default(false);
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
    }

    public function test_student_can_submit_list_and_cancel_pending_leave(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->studentUser);

        $file = UploadedFile::fake()->image('surat_dokter.jpg');

        $response = $this->post('/api/mobile/izin', [
            'jenis' => 'sakit',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-16',
            'keterangan' => 'Demam tinggi',
            'bukti_foto' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.jenis', 'sakit')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.durasi_hari', 2);

        $leaveId = $response->json('data.id');
        $buktiPath = $response->json('data.bukti_foto');
        Storage::disk('public')->assertExists($buktiPath);

        // Overlapping date should be rejected
        $overlapResp = $this->postJson('/api/mobile/izin', [
            'jenis' => 'izin',
            'tanggal_mulai' => '2026-11-16',
            'tanggal_selesai' => '2026-11-17',
            'keterangan' => 'Acara keluarga',
        ]);
        $overlapResp->assertStatus(422);

        // List my leaves
        $listResp = $this->getJson('/api/mobile/izin/my');
        $listResp->assertStatus(200)
            ->assertJsonPath('summary.pending', 1)
            ->assertJsonCount(1, 'data');

        // Detail
        $this->getJson("/api/mobile/izin/{$leaveId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $leaveId);

        // Cancel pending leave
        $this->deleteJson("/api/mobile/izin/{$leaveId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('student_leaves', ['id' => $leaveId]);
        Storage::disk('public')->assertMissing($buktiPath);
    }

    public function test_teacher_can_view_approve_and_reject_managed_class_leaves(): void
    {
        // Leave in managed class
        $leave1 = StudentLeave::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'code' => 'IZIN-TEST01',
            'jenis' => 'sakit',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-16',
            'keterangan' => 'Sakit flu',
            'pengaju' => 'siswa',
            'status' => 'pending',
        ]);

        // Leave in managed class to reject
        $leave2 = StudentLeave::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'code' => 'IZIN-TEST02',
            'jenis' => 'izin',
            'tanggal_mulai' => '2026-11-20',
            'tanggal_selesai' => '2026-11-20',
            'keterangan' => 'Jalan-jalan',
            'pengaju' => 'siswa',
            'status' => 'pending',
        ]);

        // Leave in unmanaged class
        $otherLeave = StudentLeave::create([
            'school_id' => $this->school->id,
            'student_id' => $this->otherStudent->id,
            'code' => 'IZIN-OTHER1',
            'jenis' => 'izin',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-15',
            'keterangan' => 'Urusan keluarga',
            'pengaju' => 'siswa',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->teacherUser);

        // Teacher only sees managed class leaves
        $indexResp = $this->getJson('/api/mobile/guru/izin?status=pending');
        $indexResp->assertStatus(200)
            ->assertJsonPath('can_manage', true)
            ->assertJsonPath('summary.total_pending', 2)
            ->assertJsonCount(2, 'data');

        // Teacher cannot approve unmanaged class leave
        $this->postJson("/api/mobile/guru/izin/{$otherLeave->id}/approve")
            ->assertStatus(403);

        // Teacher approves leave1 -> syncs 2 days to attendance
        $approveResp = $this->postJson("/api/mobile/guru/izin/{$leave1->id}/approve");
        $approveResp->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('attendance', [
            'student_id' => $this->student->id,
            'tanggal' => '2026-11-15',
            'status' => 'S',
        ]);
        $this->assertDatabaseHas('attendance', [
            'student_id' => $this->student->id,
            'tanggal' => '2026-11-16',
            'status' => 'S',
        ]);

        // Re-approving returns 422
        $this->postJson("/api/mobile/guru/izin/{$leave1->id}/approve")
            ->assertStatus(422);

        // Teacher rejects leave2
        $rejectResp = $this->postJson("/api/mobile/guru/izin/{$leave2->id}/reject", [
            'rejected_reason' => 'Alasan tidak valid',
        ]);
        $rejectResp->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejected_reason', 'Alasan tidak valid');
    }

    public function test_teacher_can_manually_store_leave_and_sync_attendance(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $resp = $this->postJson('/api/mobile/guru/izin', [
            'student_id' => $this->student->id,
            'jenis' => 'dispensasi',
            'tanggal_mulai' => '2026-11-18',
            'tanggal_selesai' => '2026-11-18',
            'keterangan' => 'Lomba Olimpiade Matematika',
        ]);

        $resp->assertStatus(201)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.pengaju', 'guru');

        $this->assertDatabaseHas('attendance', [
            'student_id' => $this->student->id,
            'tanggal' => '2026-11-18',
            'status' => 'I',
        ]);
    }

    public function test_student_cannot_access_teacher_endpoints(): void
    {
        Sanctum::actingAs($this->studentUser);

        $this->getJson('/api/mobile/guru/izin')->assertStatus(403);
        $this->postJson('/api/mobile/guru/izin', [])->assertStatus(403);
    }
}

