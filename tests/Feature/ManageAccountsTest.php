<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManageAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_manage_accounts_page(): void
    {
        $user = User::factory()->create([
            'account_type' => 'Admin',
        ]);

        $response = $this->actingAs($user)->get('/manage-accounts');

        $response->assertOk();
    }

    public function test_non_admin_cannot_view_manage_accounts_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/manage-accounts');

        $response->assertForbidden();
    }

    public function test_admin_can_update_account_type_from_manage_accounts_page(): void
    {
        $user = User::factory()->create([
            'account_type' => 'Admin',
        ]);
        $targetUser = User::factory()->create([
            'account_type' => 'End_User',
        ]);

        $response = $this->actingAs($user)->put("/manage-accounts/{$targetUser->id}", [
            'account_type' => 'Admin',
        ]);

        $response->assertRedirect();
        $this->assertSame('Admin', $targetUser->refresh()->account_type);
    }

    public function test_admin_can_change_a_technician_password(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'Admin',
        ]);
        $technician = User::factory()->create([
            'account_type' => 'Biomed_Technician',
        ]);

        $response = $this->actingAs($admin)->put("/manage-accounts/{$technician->id}/password", [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('new-password-123', $technician->refresh()->password));
    }

    public function test_admin_can_delete_a_technician(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'Admin',
        ]);
        $technician = User::factory()->create([
            'account_type' => 'Biomed_Technician',
        ]);

        $response = $this->actingAs($admin)->delete("/manage-accounts/{$technician->id}");

        $response->assertRedirect();
        $this->assertNull($technician->fresh());
    }

    public function test_admin_cannot_delete_a_non_technician(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'Admin',
        ]);
        $targetUser = User::factory()->create([
            'account_type' => 'End_User',
        ]);

        $response = $this->actingAs($admin)->delete("/manage-accounts/{$targetUser->id}");

        $response->assertForbidden();
        $this->assertNotNull($targetUser->fresh());
    }
}