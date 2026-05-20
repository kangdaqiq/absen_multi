<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomDomainLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that visiting login page via custom domain shows the custom branding.
     */
    public function test_login_page_displays_school_branding_for_registered_custom_domain(): void
    {
        // 1. Create a school with a custom domain
        $school = School::create([
            'name' => 'SMP Negeri 1 Jakarta',
            'type' => 'school',
            'code' => 'SCH-SMPN1',
            'domain' => 'smpn1.sch.id',
            'is_active' => true,
        ]);

        // 2. Make a request to the login route using the custom domain
        $response = $this->withHeaders([
            'Host' => 'smpn1.sch.id',
        ])->get('/login');

        // 3. Assert status is successful
        $response->assertStatus(200);

        // 4. Assert that the school's name and branding are visible on the page
        $response->assertSee('SMP Negeri 1 Jakarta');
        $response->assertSee('Silakan masuk menggunakan akun Anda');
    }

    /**
     * Test that www. prefix is normalized correctly.
     */
    public function test_login_page_normalizes_www_prefix_correctly(): void
    {
        // 1. Create a school with a custom domain
        $school = School::create([
            'name' => 'SMA Negeri 2 Bandung',
            'type' => 'school',
            'code' => 'SCH-SMAN2',
            'domain' => 'sman2.sch.id',
            'is_active' => true,
        ]);

        // 2. Make a request to the login route with www. prefix
        $response = $this->withHeaders([
            'Host' => 'www.sman2.sch.id',
        ])->get('/login');

        // 3. Assert status is successful and the school name is displayed
        $response->assertStatus(200);
        $response->assertSee('SMA Negeri 2 Bandung');
    }

    /**
     * Test that default branding is shown if domain is not registered.
     */
    public function test_login_page_displays_default_branding_for_unregistered_domain(): void
    {
        // 1. Make a request using an unregistered domain
        $response = $this->withHeaders([
            'Host' => 'another-domain.com',
        ])->get('/login');

        // 2. Assert status is successful
        $response->assertStatus(200);

        // 3. Assert default texts are visible
        $response->assertSee('Selamat Datang 👋');
        $response->assertSee('Masukkan kredensial Anda untuk mengakses dashboard');
        $response->assertDontSee('Silakan masuk menggunakan akun Anda');
    }

    /**
     * Test that default branding is shown if the school is inactive.
     */
    public function test_login_page_displays_default_branding_if_school_is_inactive(): void
    {
        // 1. Create an inactive school with a domain
        $school = School::create([
            'name' => 'SMP Pasundan 1',
            'type' => 'school',
            'code' => 'SCH-PAS1',
            'domain' => 'pasundan1.sch.id',
            'is_active' => false,
        ]);

        // 2. Make a request to the login route with that domain
        $response = $this->withHeaders([
            'Host' => 'pasundan1.sch.id',
        ])->get('/login');

        // 3. Assert status is successful but default branding is shown (since school is inactive)
        $response->assertStatus(200);
        $response->assertSee('Selamat Datang 👋');
        $response->assertDontSee('SMP Pasundan 1');
    }
}
