<?php

namespace Tests\Feature\Analysis;

use App\Models\Analysis;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ViewerAnalysisTest extends TestCase
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

    private function publishedAnalysis(int $daysAgo = 1): Analysis
    {
        return Analysis::create([
            'user_id'         => $this->owner->id,
            'viewer_id'       => $this->viewer->id,
            'summary_content' => '{"points":[]}',
            'published_at'    => now()->subDays($daysAgo),
        ]);
    }

    public function test_公開された要約一覧を取得できる(): void
    {
        $this->publishedAnalysis();

        $response = $this->actingAs($this->viewer)->getJson('/api/viewer/analyses');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'user', 'summary_content', 'annotation_text', 'published_at']]]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_7日を超えた要約は一覧に含まれない(): void
    {
        $this->publishedAnalysis(daysAgo: 8);

        $response = $this->actingAs($this->viewer)->getJson('/api/viewer/analyses');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_自分宛て以外の要約は一覧に含まれない(): void
    {
        $other = User::factory()->create(['company_id' => $this->company->id]);

        Analysis::create([
            'user_id'         => $this->owner->id,
            'viewer_id'       => $other->id,
            'summary_content' => '{"points":[]}',
            'published_at'    => now(),
        ]);

        $response = $this->actingAs($this->viewer)->getJson('/api/viewer/analyses');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_要約詳細を取得できる(): void
    {
        $analysis = $this->publishedAnalysis();

        $response = $this->actingAs($this->viewer)->getJson("/api/viewer/analyses/{$analysis->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $analysis->id]);
    }

    public function test_7日超えの要約詳細は404(): void
    {
        $analysis = $this->publishedAnalysis(daysAgo: 8);

        $response = $this->actingAs($this->viewer)->getJson("/api/viewer/analyses/{$analysis->id}");

        $response->assertNotFound();
    }
}
