<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_invalid_form_preserves_input_without_oversized_cookies(): void
    {
        $deployment = json_decode(file_get_contents(base_path('vercel.json')), true);
        config([
            'session.driver' => $deployment['env']['SESSION_DRIVER'],
            'session.encrypt' => filter_var($deployment['env']['SESSION_ENCRYPT'], FILTER_VALIDATE_BOOL),
        ]);
        $this->app['session']->forgetDrivers();
        $description = str_repeat('x', 10000);
        $response = $this->from('/tasks/create')->post('/tasks', [
            'title' => '', 'description' => $description, 'status' => 'pending',
        ])->assertRedirect('/tasks/create')->assertSessionHasErrors('title')
            ->assertSessionHasInput('description', $description);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertLessThanOrEqual(4096, strlen($cookie->getName().'='.$cookie->getValue()));
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        $this->assertNotEmpty($cookies);
        $this->assertSame(1, DB::table('sessions')->count());
        $payload = base64_decode(DB::table('sessions')->value('payload'));
        $this->assertStringNotContainsString($description, $payload);
        $this->assertStringContainsString($description, $this->app['encrypter']->decrypt($payload));

        $this->app['session']->forgetDrivers();
        $this->withUnencryptedCookies($cookies)->get('/tasks/create')
            ->assertOk()->assertSee($description)->assertSee('The title field is required.');
    }
}
