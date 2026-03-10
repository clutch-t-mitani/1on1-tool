<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_正しい認証情報でログインできる(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email'      => 'test@example.com',
            'password'   => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'is_admin']]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_誤ったパスワードでログインできない(): void
    {
        $company = Company::factory()->create();
        User::factory()->create([
            'company_id' => $company->id,
            'email'      => 'test@example.com',
            'password'   => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_ログアウトできる(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->postJson('/api/logout');

        $response->assertNoContent();

        $this->assertGuest();
    }

    public function test_未ログイン状態でログアウトすると401(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }

    public function test_管理者ルートに一般ユーザーはアクセスできない(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'is_admin'   => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/admin/users');

        $response->assertForbidden();
    }
}
