<?php

namespace Tests\Feature\Analysis;

use App\Jobs\GenerateAnalysisJob;
use App\Models\Analysis;
use App\Models\Company;
use App\Models\DailyLog;
use App\Models\Question;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class GenerateAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $company    = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $company->id]);
    }

    /**
     * 5日分の未使用ログを作成するヘルパー。
     */
    private function createLogsForDays(int $days): void
    {
        $encryption = new EncryptionService();
        $question   = Question::create(['content' => 'テスト質問', 'is_active' => true]);

        for ($i = 0; $i < $days; $i++) {
            DailyLog::create([
                'user_id'     => $this->user->id,
                'question_id' => $question->id,
                'answer_text' => $encryption->encrypt('テスト回答'),
                'target_date' => now()->subDays($i)->toDateString(),
            ]);
        }
    }

    public function test_5日分以上のログがあれば要約Jobが投入される(): void
    {
        Queue::fake();
        $this->createLogsForDays(5);

        $response = $this->actingAs($this->user)->postJson('/api/analyses');

        $response->assertStatus(202)
            ->assertJsonStructure(['analysis_id']);

        Queue::assertPushed(GenerateAnalysisJob::class);

        $this->assertDatabaseCount('analyses', 1);
        $this->assertNull(Analysis::first()->summary_content);
    }

    public function test_5日分未満では422(): void
    {
        Queue::fake();
        $this->createLogsForDays(4);

        $response = $this->actingAs($this->user)->postJson('/api/analyses');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['base']);

        Queue::assertNothingPushed();
    }

    public function test_処理中のJobがあれば422(): void
    {
        Queue::fake();
        $this->createLogsForDays(5);

        // 1回目は成功
        $this->actingAs($this->user)->postJson('/api/analyses');

        // 2回目は処理中扱いで422
        $response = $this->actingAs($this->user)->postJson('/api/analyses');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['base']);
    }

    public function test_要約詳細を取得できる(): void
    {
        $analysis = Analysis::create([
            'user_id'         => $this->user->id,
            'summary_content' => '{"points":[]}',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/analyses/{$analysis->id}");

        $response->assertOk()
            ->assertJsonStructure(['id', 'summary_content', 'annotation_text', 'published_at', 'viewer_id']);
    }

    public function test_他人の要約は取得できない(): void
    {
        $company2 = Company::factory()->create();
        $other    = User::factory()->create(['company_id' => $company2->id]);

        $analysis = Analysis::create([
            'user_id'         => $other->id,
            'summary_content' => '{"points":[]}',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/analyses/{$analysis->id}");

        $response->assertNotFound();
    }
}
