<?php

namespace Tests\Feature\Question;

use App\Models\Company;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_ログイン済みユーザーが自社の質問一覧を取得できる(): void
    {
        $q1 = Question::create(['company_id' => $this->company->id, 'content' => '質問1', 'is_active' => true, 'display_order' => 1]);
        $q2 = Question::create(['company_id' => $this->company->id, 'content' => '質問2', 'is_active' => true, 'display_order' => 2]);

        $response = $this->actingAs($this->user)->getJson('/api/questions');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $q1->id);
        $response->assertJsonPath('data.0.text', '質問1');
        $response->assertJsonPath('data.1.id', $q2->id);
        $response->assertJsonPath('data.1.text', '質問2');
    }

    public function test_他社の質問は取得されない(): void
    {
        $otherCompany = Company::factory()->create();
        Question::create(['company_id' => $otherCompany->id, 'content' => '他社の質問', 'is_active' => true, 'display_order' => 1]);
        Question::create(['company_id' => $this->company->id, 'content' => '自社の質問', 'is_active' => true, 'display_order' => 1]);

        $response = $this->actingAs($this->user)->getJson('/api/questions');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.text', '自社の質問');
    }

    public function test_無効な質問は取得されない(): void
    {
        Question::create(['company_id' => $this->company->id, 'content' => '有効', 'is_active' => true, 'display_order' => 1]);
        Question::create(['company_id' => $this->company->id, 'content' => '無効', 'is_active' => false, 'display_order' => 2]);

        $response = $this->actingAs($this->user)->getJson('/api/questions');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.text', '有効');
    }

    public function test_display_orderカラムで昇順にソートされる(): void
    {
        Question::create(['company_id' => $this->company->id, 'content' => '後', 'is_active' => true, 'display_order' => 2]);
        $first = Question::create(['company_id' => $this->company->id, 'content' => '先', 'is_active' => true, 'display_order' => 1]);

        $response = $this->actingAs($this->user)->getJson('/api/questions');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $first->id);
        $response->assertJsonPath('data.0.text', '先');
    }

    public function test_display_orderが同じ場合はid昇順で安定してソートされる(): void
    {
        $first = Question::create(['company_id' => $this->company->id, 'content' => '先', 'is_active' => true, 'display_order' => 1]);
        $second = Question::create(['company_id' => $this->company->id, 'content' => '後', 'is_active' => true, 'display_order' => 1]);

        $response = $this->actingAs($this->user)->getJson('/api/questions');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $first->id);
        $response->assertJsonPath('data.1.id', $second->id);
    }

    public function test_未認証ではアクセスできない(): void
    {
        $response = $this->getJson('/api/questions');

        $response->assertUnauthorized();
    }

    public function test_質問が0件の場合は空配列を返す(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/questions');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }
}
