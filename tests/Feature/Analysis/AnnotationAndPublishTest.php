<?php

namespace Tests\Feature\Analysis;

use App\Models\Analysis;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AnnotationAndPublishTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $owner;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->owner   = User::factory()->create(['company_id' => $this->company->id]);
        $this->viewer  = User::factory()->create(['company_id' => $this->company->id]);
    }

    private function createAnalysis(?string $summaryContent = '{"points":[]}'): Analysis
    {
        return Analysis::create([
            'user_id'         => $this->owner->id,
            'summary_content' => $summaryContent,
        ]);
    }

    // ── 注釈保存 ──

    public function test_注釈を保存できる(): void
    {
        $analysis = $this->createAnalysis();

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/analyses/{$analysis->id}/annotation", [
                'annotation_text' => 'テスト注釈',
            ]);

        $response->assertOk();
        $this->assertSame('テスト注釈', $analysis->fresh()->annotation_text);
    }

    public function test_注釈はnullで保存できる(): void
    {
        $analysis = $this->createAnalysis();

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/analyses/{$analysis->id}/annotation", [
                'annotation_text' => null,
            ]);

        $response->assertOk();
    }

    public function test_公開済みの要約に注釈を保存すると422(): void
    {
        $analysis = $this->createAnalysis();
        $analysis->update(['published_at' => now(), 'viewer_id' => $this->viewer->id]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/analyses/{$analysis->id}/annotation", [
                'annotation_text' => '変更できない',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['base']);
    }

    public function test_他人の要約に注釈を保存すると404(): void
    {
        $other    = User::factory()->create(['company_id' => $this->company->id]);
        $analysis = Analysis::create(['user_id' => $other->id, 'summary_content' => '{}']);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/analyses/{$analysis->id}/annotation", [
                'annotation_text' => 'テスト',
            ]);

        $response->assertNotFound();
    }

    // ── 公開 ──

    public function test_上司に公開できる(): void
    {
        $analysis = $this->createAnalysis();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/analyses/{$analysis->id}/publish", [
                'viewer_id' => $this->viewer->id,
            ]);

        $response->assertOk();

        $fresh = $analysis->fresh();
        $this->assertSame($this->viewer->id, $fresh->viewer_id);
        $this->assertNotNull($fresh->published_at);
    }

    public function test_別会社のユーザーへの公開は422(): void
    {
        $otherCompany = Company::factory()->create();
        $outsider     = User::factory()->create(['company_id' => $otherCompany->id]);
        $analysis     = $this->createAnalysis();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/analyses/{$analysis->id}/publish", [
                'viewer_id' => $outsider->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['viewer_id']);
    }

    public function test_公開済みの要約を再公開すると422(): void
    {
        $analysis = $this->createAnalysis();
        $analysis->update(['published_at' => now(), 'viewer_id' => $this->viewer->id]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/analyses/{$analysis->id}/publish", [
                'viewer_id' => $this->viewer->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['base']);
    }
}
