<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisionFirstAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_first_admin_user(): void
    {
        $password = 'SecurePass123!';

        $this->artisan('admin:provision-first')
            ->expectsQuestion('Admin name', 'Bintan Admin')
            ->expectsQuestion('Admin email', 'admin@example.com')
            ->expectsQuestion('Admin password', $password)
            ->expectsQuestion('Confirm admin password', $password)
            ->doesntExpectOutputToContain($password)
            ->expectsOutputToContain('First admin user created successfully.')
            ->assertExitCode(0);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertSame(User::ROLE_SUPER_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertNotSame($password, $admin->password);
    }

    public function test_command_stops_when_admin_already_exists(): void
    {
        User::factory()->admin()->create();

        $this->artisan('admin:provision-first')
            ->expectsOutputToContain('An admin user already exists. First admin provisioning was skipped.')
            ->assertExitCode(1);

        $this->assertSame(1, User::where('is_admin', true)->count());
    }

    public function test_command_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->artisan('admin:provision-first')
            ->expectsQuestion('Admin name', 'Bintan Admin')
            ->expectsQuestion('Admin email', 'admin@example.com')
            ->expectsOutputToContain('The email has already been taken.')
            ->assertExitCode(1);

        $this->assertFalse(User::where('email', 'admin@example.com')->firstOrFail()->isAdmin());
    }

    public function test_command_rejects_password_confirmation_mismatch(): void
    {
        $this->artisan('admin:provision-first')
            ->expectsQuestion('Admin name', 'Bintan Admin')
            ->expectsQuestion('Admin email', 'admin@example.com')
            ->expectsQuestion('Admin password', 'SecurePass123!')
            ->expectsQuestion('Confirm admin password', 'DifferentPass123!')
            ->expectsOutputToContain('The password field confirmation does not match.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }

    public function test_provisioned_admin_can_login_and_access_dashboard(): void
    {
        $password = 'SecurePass123!';

        $this->artisan('admin:provision-first')
            ->expectsQuestion('Admin name', 'Bintan Admin')
            ->expectsQuestion('Admin email', 'admin@example.com')
            ->expectsQuestion('Admin password', $password)
            ->expectsQuestion('Confirm admin password', $password)
            ->assertExitCode(0);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => $password,
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_non_admin_registration_and_public_frontend_posture_remain_safe(): void
    {
        $nonAdmin = User::factory()->create();

        $this->actingAs($nonAdmin)
            ->get('/admin/dashboard')
            ->assertForbidden();

        Auth::logout();
        $this->flushSession();

        $this->get('/register')
            ->assertNotFound();

        $this->post('/register', [
            'name' => 'Public User',
            'email' => 'public@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->get('/')
            ->assertOk();
    }
}
