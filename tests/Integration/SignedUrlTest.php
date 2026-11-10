<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Magic\SignedUrlController;
use Sunchayn\Nimbus\Http\Api\Magic\SignedUrlRequest;
use Sunchayn\Nimbus\Modules\Magic\Actions\SignRouteUrlAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SignedUrlController::class)]
#[CoversClass(SignedUrlRequest::class)]
#[CoversClass(SignRouteUrlAction::class)]
class SignedUrlTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->prefix('demo')->group(__DIR__.'/../../workbench/routes/demo.php');

        Route::get('/plain', fn () => 'ok')->name('plain');
    }

    public function test_it_signs_a_route_that_uses_the_signed_middleware(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.magic.signed-url'), [
            'method' => 'GET',
            'url' => url('demo/signed/unsubscribe/42').'?ref=mail',
        ]);

        // Assert

        $response->assertOk();

        $signed = $response->json('url');

        $this->assertStringContainsString('ref=mail', $signed);
        $this->assertStringContainsString('signature=', $signed);
        $this->get($signed)->assertOk();
    }

    public function test_it_signs_for_the_host_of_the_given_url(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.magic.signed-url'), [
            'method' => 'GET',
            'url' => 'http://127.0.0.1:8001/demo/signed/unsubscribe/42?ref=mail&tag=a%20b',
        ]);

        // Assert

        $response->assertOk();

        $signed = $response->json('url');

        $this->assertStringStartsWith('http://127.0.0.1:8001/demo/signed/unsubscribe/42', $signed);
        $this->assertTrue(Request::create($signed)->hasValidSignature());
        $this->assertStringContainsString('tag=a%20b', $signed);
    }

    public function test_it_replaces_an_old_signature(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.magic.signed-url'), [
            'method' => 'GET',
            'url' => url('demo/signed/unsubscribe/42').'?signature=old',
        ]);

        // Assert

        $this->assertStringNotContainsString('signature=old', $response->json('url'));
        $this->get($response->json('url'))->assertOk();
    }

    public function test_it_refuses_a_route_without_the_signed_middleware(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.magic.signed-url'), [
            'method' => 'GET',
            'url' => url('plain'),
        ]);

        // Assert

        $response->assertStatus(422);
    }

    public function test_it_refuses_a_url_that_matches_no_route(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.magic.signed-url'), [
            'method' => 'GET',
            'url' => url('does/not/exist'),
        ]);

        // Assert

        $response->assertStatus(422);
    }
}
