<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $u = User::factory()->create();
        $this->actingAs($u);

        return $u;
    }

    private function sale(array $extra = []): Sale
    {
        return Sale::create(array_merge(['reference' => 'ORD-'.uniqid(), 'customer' => 'Ada Example', 'product' => 'Keyboard', 'channel' => 'Online store', 'status' => 'Paid', 'ordered_at' => '2026-10-15', 'amount_cents' => 10000], $extra));
    }

    public function test_guests_cannot_access_dashboard_or_csv(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/sales/export')->assertRedirect('/login');
    }

    public function test_login_and_logout(): void
    {
        $u = User::factory()->create(['email' => 'tester@example.com', 'password' => Hash::make('test-password')]);
        $this->post('/login', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $u->email, 'password' => 'test-password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $r = $this->post('/login', ['email' => 'rate@example.com', 'password' => 'wrong']);
        } $r->assertSessionHasErrors(['email' => 'Too many attempts. Please try again in one minute.']);
    }

    public function test_totals_only_collect_paid_revenue_and_include_date_boundaries(): void
    {
        $this->loginUser();
        $this->sale(['ordered_at' => '2026-10-01']);
        $this->sale(['ordered_at' => '2026-10-31', 'amount_cents' => 25000]);
        $this->sale(['status' => 'Pending', 'amount_cents' => 8000]);
        $this->sale(['status' => 'Refunded']);
        $this->sale(['ordered_at' => '2026-09-30']);
        $this->get('/dashboard?from=2026-10-01&to=2026-10-31')->assertOk()->assertViewHas('revenue', 35000)->assertViewHas('count', 4)->assertViewHas('pending', 8000)->assertViewHas('paidCount', 2)->assertViewHas('chart', fn ($p) => count($p) === 31 && $p[0]['value'] === 10000 && $p[30]['value'] === 25000);
    }

    public function test_filters_apply_to_all_metrics_and_csv(): void
    {
        $this->loginUser();
        $this->sale(['customer' => 'Target Person', 'status' => 'Pending', 'reference' => 'TARGET']);
        $this->sale(['customer' => 'Other Person']);
        $params = 'from=2026-10-01&to=2026-10-31&status=Pending&q=Target';
        $this->get('/dashboard?'.$params)->assertOk()->assertViewHas('count', 1)->assertViewHas('revenue', 0)->assertViewHas('pending', 10000)->assertSee('TARGET')->assertDontSee('Other Person');
        $r = $this->get('/sales/export?'.$params)->assertOk()->assertDownload('sales-report.csv');
        $s = $r->streamedContent();
        $this->assertStringContainsString('TARGET', $s);
        $this->assertStringNotContainsString('Other Person', $s);
    }

    public function test_invalid_dates_status_and_oversized_ranges_are_rejected(): void
    {
        $this->loginUser();
        foreach (['from=2026-10-31&to=2026-10-01', 'from=2020-01-01&to=2026-10-01', 'from=not-a-date', 'status=Invalid'] as $q) {
            $this->get('/dashboard?'.$q)->assertSessionHasErrors();
        }
    }

    public function test_pagination_preserves_filters_and_export_includes_all_pages(): void
    {
        $this->loginUser();
        for ($i = 0; $i < 23; $i++) {
            $this->sale(['reference' => sprintf('PAGE-%02d', $i)]);
        }
        $this->get('/dashboard?from=2026-10-01&to=2026-10-31&page=2')->assertOk()->assertViewHas('sales', fn ($p) => $p->count() === 10 && $p->total() === 23 && str_contains($p->nextPageUrl(), 'from=2026-10-01'));
        $csv = $this->get('/sales/export?from=2026-10-01&to=2026-10-31&page=2')->streamedContent();
        $this->assertCount(24, explode("\n", trim($csv)));
    }

    public function test_empty_results_have_zero_metrics(): void
    {
        $this->loginUser();
        $this->get('/dashboard')->assertOk()->assertViewHas('revenue', 0)->assertViewHas('count', 0)->assertSee('No orders found');
    }

    public function test_csv_neutralizes_formulas_and_html_is_escaped(): void
    {
        $this->loginUser();
        $this->sale(['customer' => '=HYPERLINK("bad")', 'product' => '<script>alert(1)</script>']);
        $this->get('/dashboard?from=2026-10-01&to=2026-10-31')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        $csv = $this->get('/sales/export?from=2026-10-01&to=2026-10-31')->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->loginUser();
        $this->sale();
        $this->get('/dashboard?from=2026-10-01&to=2026-10-31&q=%25')->assertViewHas('count', 0);
    }

    public function test_seeder_is_repeatable(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('sales',180);
        $this->assertDatabaseCount('users',1);
    }
}
