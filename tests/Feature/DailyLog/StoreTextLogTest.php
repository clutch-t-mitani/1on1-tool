<?php

namespace Tests\Feature\DailyLog;

use App\Models\Company;
use App\Models\DailyLog;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StoreTextLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $company        = Company::factory()->create();
        $this->user     = User::factory()->create(['company_id' => $company->id]);
        $this->question = Question::create(['content' => 'テスト質問', 'is_active' => true]);
    }

    public function test_テキストログを保存できる(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/daily-logs/text', [
            'question_id' => $this->question->id,
            'answer_text' => '今日は良い一日でした。',
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('daily_logs', 1);

        $log = DailyLog::first();
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertSame($this->question->id, $log->question_id);
        $this->assertNotSame('今日は良い一日でした。', $log->answer_text); // 暗号化されている
    }

    public function test_同日同質問に2回保存すると422(): void
    {
        $this->actingAs($this->user)->postJson('/api/daily-logs/text', [
            'question_id' => $this->question->id,
            'answer_text' => '1回目',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/daily-logs/text', [
            'question_id' => $this->question->id,
            'answer_text' => '2回目',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id']);

        $this->assertDatabaseCount('daily_logs', 1);
    }

    public function test_未ログイン状態では401(): void
    {
        $response = $this->postJson('/api/daily-logs/text', [
            'question_id' => $this->question->id,
            'answer_text' => 'テスト',
        ]);

        $response->assertUnauthorized();
    }

    public function test_answer_textが空の場合は422(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/daily-logs/text', [
            'question_id' => $this->question->id,
            'answer_text' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['answer_text']);
    }

    public function test_入力状況を取得できる(): void
    {
        $now = now();
        $targetDate = $now->hour < 12
            ? $now->toDateString()
            : $now->copy()->addDay()->toDateString();

        DailyLog::create([
            'user_id'     => $this->user->id,
            'question_id' => $this->question->id,
            'answer_text' => 'encrypted',
            'target_date' => $targetDate,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/daily-logs/status');

        $response->assertOk()
            ->assertJsonStructure(['answered_question_ids', 'total_log_days', 'target_date']);

        $this->assertContains($this->question->id, $response->json('answered_question_ids'));
        $this->assertSame(1, $response->json('total_log_days'));
    }
}
