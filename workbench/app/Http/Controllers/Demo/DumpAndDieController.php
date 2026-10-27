<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use stdClass;
use Workbench\App\Models\User;

class DumpAndDieController
{
    public function __invoke(Request $request): never
    {
        $index = $request->hasHeader('x-index') ? (int) $request->header('x-index') : null;

        $dumps = [
            // Array with users, pagination, null, and closure
            function () {
                $user1 = new stdClass;
                $user1->id = 1;
                $user1->name = 'John Doe';
                $user1->email = 'john@example.com';
                $user1->password = 'hashed_secret';
                $user1->rememberToken = 'abc123xyz';
                $user1->isActive = true;

                $metadata = new stdClass;
                $metadata->lastLogin = '2025-12-28T10:00:00Z';
                $metadata->loginCount = 42;
                $metadata->preferences = [
                    'theme' => 'dark',
                    'notifications' => true,
                    'language' => 'en',
                ];
                $user1->metadata = $metadata;

                $user2 = new stdClass;
                $user2->id = 2;
                $user2->name = 'Jane Smith';
                $user2->email = 'jane@example.com';

                $role = new stdClass;
                $role->id = 1;
                $role->name = 'admin';
                $role->permissions = ['read', 'write', 'delete'];
                $user2->role = $role;

                $pagination = new stdClass;
                $pagination->currentPage = 1;
                $pagination->total = 150;
                $pagination->perPage = 15;
                $pagination->hasMorePages = true;

                return [
                    [
                        'users' => [$user1, $user2],
                        'pagination' => $pagination,
                        'nullValue' => null,
                        'callback' => fn () => null,
                    ],
                ];
            },

            // Request Object
            function () use ($request) {
                return [
                    $request,
                ];
            },

            // App Object
            app()->runningUnitTests() ? fn () => [app()] : null,

            // Product run-time object
            function () {
                $product = new stdClass;
                $product->id = 42;
                $product->name = 'Laravel Framework Book';
                $product->price = 49.99;
                $product->inStock = true;
                $product->tags = ['php', 'laravel', 'framework', 'web-development'];

                $review = new stdClass;
                $review->id = 1;
                $review->rating = 5;
                $review->comment = 'Excellent book!';

                $author = new stdClass;
                $author->id = 10;
                $author->name = 'Alice Johnson';
                $review->author = $author;

                $product->reviews = [$review];

                return [
                    $product,
                ];
            },

            // Eloquent Model
            function () {
                return [
                    User::factory()->make([
                        'name' => 'Dr. Major Willms Sr.',
                        'email' => 'farrell.ryley@example.com',
                        'password' => '$2y$12$oW6OxRs//G14H8mPrL.2/eyeDNAOOLSp4vQ7bJ.LkA83Zw.MuuCVq',
                        'remember_token' => 'Euycju51eF',
                        'two_factor_secret' => 'zTSvlt8tdp',
                        'two_factor_recovery_codes' => 'KqosMgNT1s',
                        'two_factor_confirmed_at' => now(),
                        'email_verified_at' => '2026-01-03 22:54:39',
                        'emptyObject' => (object) [],
                    ]),
                ];
            },

            // Primitives and multi logs.
            function () {
                return [
                    Str::random(),
                    rand(),
                    null,
                    true,
                    false,
                ];
            },
        ];

        $dumps = array_values(array_filter($dumps));

        if ($index !== null && $index >= 0 && $index <= 5) {
            dd(...$dumps[$index]->call($this));
        }

        $randomDump = Arr::random($dumps)->call($this);

        dd(...$randomDump);
    }
}
