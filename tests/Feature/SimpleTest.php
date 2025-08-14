<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_run_basic_tests()
    {
        // اختبار بسيط لإنشاء tenant
        $tenant = Tenant::factory()->create(['name' => 'شركة الاختبار']);
        
        $this->assertDatabaseHas('tenants', [
            'name' => 'شركة الاختبار',
        ]);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_access_home_page()
    {
        $response = $this->get('/');
        // نتوقع إما 200 أو redirect
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }
}
