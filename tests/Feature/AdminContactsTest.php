<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_contacts_include_hotlines_guidelines_and_preview_actions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Category')
            ->assertSee('contactCategory')
            ->assertSee('data-category="medical"', false)
            ->assertSee('data-category="security"', false)
            ->assertSee('data-category="utilities"', false)
            ->assertSee('data-category="disaster"', false)
            ->assertSee('data-category="fire"', false)
            ->assertSee('data-copy-number="0933-990-9960"', false)
            ->assertSee('MGA Center')
            ->assertSee('PELCO II')
            ->assertSee('CDRRMO')
            ->assertSee('PNP')
            ->assertSee('BFP')
            ->assertSee('Medical Emergency Hotline')
            ->assertSee('Electric Cooperative')
            ->assertSee('Disaster Risk Reduction and Management')
            ->assertSee('Philippine National Police')
            ->assertSee('Bureau of Fire Protection')
            ->assertSee('Fire outbreak')
            ->assertSee('Power interruption or hazard')
            ->assertSee('Medical emergency')
            ->assertSee('Security threat')
            ->assertSee('Disaster or evacuation')
            ->assertSee('Report unresponsive hotline')
            ->assertSee('Add custom contact')
            ->assertSee('This contact will not be saved or added to the hotline list.')
            ->assertDontSee('data-lucide');
    }
}
