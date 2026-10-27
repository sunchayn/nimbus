<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Resolvers\DataValidationRulesResolver;
use Spatie\LaravelData\Support\Validation\DataRules;
use Spatie\LaravelData\Support\Validation\ValidationPath;
use Workbench\App\Http\Controllers\Api\Enums\SomeFilterEnum;
use Workbench\App\Http\Controllers\Demo\SpatieData\SpatieDataExample;

class DemoController
{
    /**
     * Simple inline validation demonstration.
     */
    public function simpleValidation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'age' => 'required|integer|min:18|max:120',
        ]);

        return response()->json([
            'message' => 'Validation passed',
            'data' => $validated,
        ]);
    }

    /**
     * Complex inline validation demonstration.
     */
    public function complexValidation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user' => 'required|array',
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email',
            'user.profile' => 'nullable|array',
            'user.profile.bio' => 'nullable|string|max:1000',
            'user.profile.avatar' => 'nullable|image|max:2048',
            'preferences' => 'nullable|array',
            'preferences.notifications' => 'boolean',
            'preferences.theme' => ['nullable', Rule::in(['light', 'dark', 'auto'])],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|json',
        ]);

        return response()->json([
            'message' => 'Complex validation passed',
            'data' => $validated,
        ]);
    }

    /**
     * Conditional validation demonstration.
     */
    public function conditionalValidation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'company_name' => 'required_if:type,company|string|max:255',
            'tax_id' => 'required_if:type,company|string|max:50',
            'phone' => 'required_without:email|string|max:20',
            'address' => 'nullable|array',
            'address.street' => 'required|string|max:255',
            'address.city' => 'sometimes|string|max:100',
            'address.country' => 'required|string|size:2',
        ]);

        return response()->json([
            'message' => 'Conditional validation passed',
            'data' => $validated,
        ]);
    }

    /**
     * Enum validation demonstration.
     */
    public function enumValidation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(SomeFilterEnum::class)],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['required', Rule::in(['bug', 'feature', 'improvement', 'task'])],
            'assignee_id' => 'nullable|integer|exists:users,id',
            'due_date' => 'nullable|date|after:today',
        ]);

        return response()->json([
            'message' => 'Enum validation passed',
            'data' => $validated,
        ]);
    }

    /**
     * Validator facade and dynamic method call validation demonstration.
     */
    public function validatorMakeValidation(Request $request): JsonResponse
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => 'The provided password does not match your current password.',
        ])->validateWithBag('updatePassword');

        return response()->json([
            'message' => 'Password updated successfully',
            'data' => $validated,
        ]);
    }

    protected function passwordRules(): array
    {
        return ['required', 'string', 'min:8'];
    }

    /**
     * Different error response types demonstration.
     */
    public function errorResponses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:validation,not-found,unauthorized,forbidden,server-error,unknown',
        ]);

        $type = $validated['type'];

        return match ($type) {
            'validation' => response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['The email field is required.'],
                    'password' => ['The password must be at least 8 characters.'],
                ],
            ], 422),
            'not-found' => response()->json([
                'message' => 'Resource not found',
                'error_code' => 'RESOURCE_NOT_FOUND',
            ], 404),
            'unauthorized' => response()->json([
                'message' => 'Unauthorized access',
                'error_code' => 'UNAUTHORIZED',
            ], 401),
            'forbidden' => response()->json([
                'message' => 'Access forbidden',
                'error_code' => 'FORBIDDEN',
            ], 403),
            'server-error' => response()->json([
                'message' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR',
            ], 500),
            default => response()->json(['message' => 'Unknown error type'], 400)
        };
    }

    /**
     * Different success response types demonstration.
     */
    public function successResponses(Request $request): JsonResponse
    {
        ['type' => $type] = $request->validate([
            'type' => 'required|in:created,updated,deleted,paginated,unknown',
        ]);

        return match ($type) {
            'created' => response()->json([
                'message' => 'Resource created successfully',
                'data' => [
                    'id' => 123,
                    'name' => 'Test Resource',
                    'created_at' => now()->toISOString(),
                ],
            ], 201),
            'updated' => response()->json([
                'message' => 'Resource updated successfully',
                'data' => [
                    'id' => 123,
                    'name' => 'Updated Resource',
                    'updated_at' => now()->toISOString(),
                ],
            ], 200),
            'deleted' => response()->json([
                'message' => 'Resource deleted successfully',
            ], 200),
            'paginated' => response()->json([
                'data' => [
                    ['id' => 1, 'name' => 'Item 1'],
                    ['id' => 2, 'name' => 'Item 2'],
                    ['id' => 3, 'name' => 'Item 3'],
                ],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 3,
                    'last_page' => 1,
                ],
                'links' => [
                    'first' => 'http://example.com/api/demo/success-responses/paginated?page=1',
                    'last' => 'http://example.com/api/demo/success-responses/paginated?page=1',
                    'prev' => null,
                    'next' => null,
                ],
            ], 200),
            default => response()->json(['message' => 'Success'], 200)
        };
    }

    public function spatieData(SpatieDataExample $spatieDataExample): JsonResponse
    {
        return response()->json([
            'rules' => app(DataValidationRulesResolver::class)->execute(
                SpatieDataExample::class,
                [],
                ValidationPath::create(),
                DataRules::create()
            ),
            'message' => 'Validation passed',
            'data' => $spatieDataExample->toArray(),
        ]);
    }

    public function requestParameters(Request $request): JsonResponse
    {
        return response()->json([
            'query' => $request->query(),
            'input' => $request->input(),
        ]);
    }
}
