<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect('/login');
    }

    public function test_non_admin_user_can_not_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_public_registration_routes_are_disabled(): void
    {
        $this->get('/register')
            ->assertNotFound();

        $this->post('/register', [
            'name' => 'Public User',
            'email' => 'public@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_public_frontend_homepage_remains_accessible(): void
    {
        $this->get('/')
            ->assertOk();
    }

    public function test_image_upload_validation_rejects_image_content_with_unsafe_extension(): void
    {
        $validator = Validator::make([
            'image' => UploadedFile::fake()->image('payload.php', 20, 20),
        ], [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('image', $validator->errors()->messages());
    }

    public function test_env_example_defaults_debug_to_false(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_DEBUG=false', $envExample);
    }
}
