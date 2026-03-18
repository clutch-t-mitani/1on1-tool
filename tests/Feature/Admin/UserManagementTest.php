<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company     = Company::factory()->create();
        $this->admin       = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => true]);
        $this->regularUser = User::factory()->create(['company_id' => $this->company->id, 'is_admin' => false]);
    }

    public function test_管理者はユーザー一覧を取得できる(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'is_admin', 'company']]]);
    }

    public function test_一般ユーザーはユーザー一覧を取得できない(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/admin/users');

        $response->assertForbidden();
    }

    public function test_管理者はユーザーを作成できる(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/admin/users', [
            'company_id' => $this->company->id,
            'name'       => '新規ユーザー',
            'email'      => 'new@example.com',
            'password'   => 'password123',
            'is_admin'   => false,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_管理者はユーザーを削除できる(): void
    {
        $target = User::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$target->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_管理者は会社一覧を取得できる(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/companies');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    public function test_管理者は会社を作成できる(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/admin/companies', [
            'name' => '新規会社',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id']);
    }
}
