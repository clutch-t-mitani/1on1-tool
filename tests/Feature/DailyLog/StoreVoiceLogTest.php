<?php

namespace Tests\Feature\DailyLog;

use App\Jobs\ProcessVoiceLogJob;
use App\Models\Company;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class StoreVoiceLogTest extends TestCase
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

    public function test_音声ファイルをアップロードするとJobがキューに投入される(): void
    {
        Storage::fake('s3');
        Queue::fake();

        $file = UploadedFile::fake()->create('audio.webm', 100, 'audio/webm');

        $response = $this->actingAs($this->user)->postJson('/api/daily-logs/voice', [
            'question_id' => $this->question->id,
            'audio'       => $file,
        ]);

        $response->assertStatus(202);

        Queue::assertPushed(ProcessVoiceLogJob::class, function (ProcessVoiceLogJob $job): bool {
            return $job->userId === $this->user->id
                && $job->questionId === $this->question->id;
        });

        Storage::disk('s3')->assertExists(
            collect(Storage::disk('s3')->allFiles())->first()
        );
    }

    public function test_未ログイン状態では401(): void
    {
        $response = $this->postJson('/api/daily-logs/voice', [
            'question_id' => $this->question->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_音声ファイルなしでは422(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/daily-logs/voice', [
            'question_id' => $this->question->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['audio']);
    }
}
