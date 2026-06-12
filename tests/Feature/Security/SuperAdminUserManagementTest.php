<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_not_access_admin_users(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect('/login');
    }

    public function test_regular_admin_can_not_access_admin_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_admin_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Admin Users')
            ->assertSee($superAdmin->email);
    }

    public function test_super_admin_can_create_admin_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $password = 'SecurePass123!';

        $this->actingAs($superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Content Admin',
                'email' => 'content-admin@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'role' => User::ROLE_ADMIN,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $createdUser = User::where('email', 'content-admin@example.com')->firstOrFail();

        $this->assertTrue($createdUser->is_admin);
        $this->assertSame(User::ROLE_ADMIN, $createdUser->role);
        $this->assertTrue($createdUser->is_active);
        $this->assertSame($superAdmin->id, $createdUser->created_by);
        $this->assertTrue(Hash::check($password, $createdUser->password));
        $this->assertNotSame($password, $createdUser->password);
    }

    public function test_super_admin_can_create_super_admin_when_role_is_explicit(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $password = 'SecurePass123!';

        $this->actingAs($superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Second Super Admin',
                'email' => 'second-super@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'second-super@example.com',
            'is_admin' => true,
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_not_deactivate_self(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.deactivate', $superAdmin))
            ->assertSessionHas('error');

        $this->assertTrue($superAdmin->fresh()->is_active);
    }

    public function test_super_admin_can_not_downgrade_only_active_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $superAdmin), [
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'role' => User::ROLE_ADMIN,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_SUPER_ADMIN, $superAdmin->fresh()->role);
    }

    public function test_inactive_admin_can_not_access_admin_dashboard(): void
    {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $this->actingAs($inactiveAdmin)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_inactive_admin_can_not_login(): void
    {
        $inactiveAdmin = User::factory()->admin()->inactive()->create([
            'email' => 'inactive-admin@example.com',
        ]);

        $this->post('/login', [
            'email' => $inactiveAdmin->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_public_registration_and_frontend_remain_safe(): void
    {
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
