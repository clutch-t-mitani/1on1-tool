<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\DailyLog;
use App\Models\Question;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDailyLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private DailyLog $log;

    protected function setUp(): void
    {
        parent::setUp();

        $company           = Company::factory()->create();
        $this->admin       = User::factory()->create(['company_id' => $company->id, 'is_admin' => true]);
        $this->regularUser = User::factory()->create(['company_id' => $company->id, 'is_admin' => false]);

        $question  = Question::create(['content' => 'テスト質問', 'is_active' => true]);
        $this->log = DailyLog::create([
            'user_id'     => $this->regularUser->id,
            'question_id' => $question->id,
            'answer_text' => (new EncryptionService())->encrypt('テスト回答'),
            'target_date' => now()->toDateString(),
        ]);
    }

    public function test_管理者は生ログ一覧を復号して取得できる(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/daily-logs');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'user', 'question', 'answer_text', 'target_date']]]);

        $this->assertSame('テスト回答', $response->json('data.0.answer_text'));
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_一般ユーザーは生ログ一覧を取得できない(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/admin/daily-logs');

        $response->assertForbidden();
    }

    public function test_管理者は生ログを削除できる(): void
    {
        $response = $this->actingAs($this->admin)->deleteJson("/api/admin/daily-logs/{$this->log->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('daily_logs', ['id' => $this->log->id]);
        $this->assertDatabaseCount('audit_logs', 1);
    }
}
