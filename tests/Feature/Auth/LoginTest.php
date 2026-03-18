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
            ->assertJsonValidationErrors(['auth'])
            ->assertJsonPath('errors.auth.0', 'メールアドレスまたはパスワードが正しくありません。');

        $this->assertGuest();
    }

    public function test_メールアドレス未入力時はバリデーションエラーを返す(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => '',
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'メールアドレスを入力してください。');
    }

    public function test_パスワード未入力時はバリデーションエラーを返す(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'パスワードを入力してください。');
    }

    public function test_メールアドレス形式が不正な場合はバリデーションエラーを返す(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'メールアドレスの形式が正しくありません。');
    }

    public function test_存在しないメールアドレスでは認証エラーを返す(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['auth']);

        $this->assertGuest();
    }

    public function test_ログアウトできる(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user, 'web')->postJson('/api/logout');

        $response->assertNoContent();

        $this->assertGuest('web');
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
