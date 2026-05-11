<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromoCodeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_non_admin_cannot_access_promo_codes_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.promo-codes.index'))
            ->assertStatus(403);
    }

    public function test_admin_sees_create_form_with_type_options(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.promo-codes.create'))
            ->assertOk()
            ->assertSee('code', false);
    }

    public function test_admin_can_create_a_fixed_promo_code(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.promo-codes.store'), [
                'code' => 'welcome10',
                'label' => 'Welcome bonus',
                'type' => PromoCode::TYPE_FIXED,
                'amount' => '10',
                'min_subtotal' => '',
                'starts_at' => '',
                'ends_at' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.promo-codes.index'));

        $this->assertDatabaseHas('promo_codes', [
            'code' => 'WELCOME10',
            'type' => PromoCode::TYPE_FIXED,
            'is_active' => true,
        ]);
    }

    public function test_store_rejects_duplicate_codes_case_insensitively(): void
    {
        $admin = User::factory()->admin()->create();
        PromoCode::query()->create([
            'code' => 'SAVE20',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.promo-codes.create'))
            ->post(route('admin.promo-codes.store'), [
                'code' => 'save20',
                'type' => PromoCode::TYPE_FIXED,
                'amount' => 5,
            ])
            ->assertRedirect(route('admin.promo-codes.create'))
            ->assertSessionHasErrors(['code']);
    }

    public function test_store_rejects_percent_above_100(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.promo-codes.create'))
            ->post(route('admin.promo-codes.store'), [
                'code' => 'BIG',
                'type' => PromoCode::TYPE_PERCENT,
                'amount' => '120',
            ])
            ->assertRedirect(route('admin.promo-codes.create'))
            ->assertSessionHasErrors(['amount']);

        $this->assertDatabaseCount('promo_codes', 0);
    }

    public function test_store_rejects_invalid_code_characters_and_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.promo-codes.create'))
            ->post(route('admin.promo-codes.store'), [
                'code' => 'bad code!',
                'type' => 'unknown',
                'amount' => '',
            ])
            ->assertRedirect(route('admin.promo-codes.create'))
            ->assertSessionHasErrors(['code', 'type', 'amount']);
    }

    public function test_store_rejects_ends_at_before_starts_at(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.promo-codes.create'))
            ->post(route('admin.promo-codes.store'), [
                'code' => 'WINDOW',
                'type' => PromoCode::TYPE_FIXED,
                'amount' => '5',
                'starts_at' => '2026-06-10 00:00:00',
                'ends_at' => '2026-06-01 00:00:00',
            ])
            ->assertRedirect(route('admin.promo-codes.create'))
            ->assertSessionHasErrors(['ends_at']);
    }

    public function test_admin_can_update_existing_promo_code(): void
    {
        $admin = User::factory()->admin()->create();
        $promo = PromoCode::query()->create([
            'code' => 'WELCOME10',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.promo-codes.update', ['promoCode' => $promo]), [
                'code' => 'WELCOME10',
                'label' => 'Welcome v2',
                'type' => PromoCode::TYPE_PERCENT,
                'amount' => '15',
                'min_subtotal' => '50',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.promo-codes.index'));

        $promo->refresh();
        $this->assertSame('Welcome v2', $promo->label);
        $this->assertSame(PromoCode::TYPE_PERCENT, $promo->type);
        $this->assertEquals(15, (float) $promo->amount);
        $this->assertEquals(50, (float) $promo->min_subtotal);
        $this->assertFalse((bool) $promo->is_active);
    }

    public function test_admin_can_delete_a_promo_code(): void
    {
        $admin = User::factory()->admin()->create();
        $promo = PromoCode::query()->create([
            'code' => 'TEMP',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.promo-codes.destroy', ['promoCode' => $promo]))
            ->assertRedirect(route('admin.promo-codes.index'));

        $this->assertDatabaseMissing('promo_codes', ['id' => $promo->id]);
    }

    public function test_index_filters_by_active_status_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        PromoCode::query()->create([
            'code' => 'ACTIVECODE',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 5,
            'is_active' => true,
        ]);
        PromoCode::query()->create([
            'code' => 'INACTIVECODE',
            'type' => PromoCode::TYPE_FIXED,
            'amount' => 5,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.promo-codes.index', ['status' => 'active']))
            ->assertOk();

        $response->assertSee('ACTIVECODE', false);
        $response->assertDontSee('INACTIVECODE', false);
    }
}
