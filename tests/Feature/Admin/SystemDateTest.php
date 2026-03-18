<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemDateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $company           = Company::factory()->create();
        $this->admin       = User::factory()->create(['company_id' => $company->id, 'is_admin' => true]);
        $this->regularUser = User::factory()->create(['company_id' => $company->id, 'is_admin' => false]);
    }

    public function test_管理者はシステム日付を取得できる(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/system-date');

        $response->assertOk()
            ->assertJsonStructure(['date', 'is_overridden']);

        $this->assertFalse($response->json('is_overridden'));
    }

    public function test_管理者はシステム日付を変更できる(): void
    {
        $response = $this->actingAs($this->admin)->putJson('/api/admin/system-date', [
            'date' => '2026-04-01',
        ]);

        $response->assertOk()
            ->assertJson(['date' => '2026-04-01']);

        $this->assertSame('2026-04-01', SystemSetting::getMasterDate()->toDateString());
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_管理者はシステム日付の上書きを解除できる(): void
    {
        SystemSetting::setMasterDate(\Illuminate\Support\Carbon::parse('2026-04-01'));

        $response = $this->actingAs($this->admin)->deleteJson('/api/admin/system-date');

        $response->assertNoContent();
        $this->assertDatabaseMissing('system_settings', ['key' => 'master_date_override']);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_一般ユーザーはシステム日付を変更できない(): void
    {
        $response = $this->actingAs($this->regularUser)->putJson('/api/admin/system-date', [
            'date' => '2026-04-01',
        ]);

        $response->assertForbidden();
    }

    public function test_不正な日付形式は422(): void
    {
        $response = $this->actingAs($this->admin)->putJson('/api/admin/system-date', [
            'date' => 'not-a-date',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }
}
