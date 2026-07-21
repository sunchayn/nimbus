<?php

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\NotIn;
use Illuminate\Validation\Rules\RequiredIf;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Ast\Stubs\FormRequestStub;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs\StatusEnumStub;

class TestController
{
    public function simple_call(Request $request): void
    {
        $validated = $request->validate([
            'foobar' => 'required|string',
            'foobaz' => ['required', 'integer'],
        ]);
    }

    public function simple_call_without_assignment(FormRequestStub $request)
    {
        $request->validate([
            'foobar' => 'required|string',
            'foobaz' => ['required', 'integer'],
        ]);
    }

    public function getting_rules_from_sub_method(Request $request): void
    {
        $validated = $request->validate($this->craftRules());
    }

    public function call_from_a_variable(Request $request)
    {
        $rules = [
            'foobar' => 'required|string',
            'foobaz' => ['required', 'integer'],
        ];

        $validated = $request->validate($rules);
    }

    public function call_from_nested_variables(Request $request)
    {
        $rule = ['required', 'string', 'email'];

        $rules = [
            'foobar' => 'required|string',
            'foobaz' => $rule,
        ];

        $validated = $request->validate($rules);
    }

    public function call_with_rules_instances(Request $request)
    {
        $rules = [
            'foobar' => [
                'required',
                Rule::in(1, 2, 3, 4),
                new NotIn(3, 4),
                new RequiredIf(fn () => '::value::'),
                Rule::enum(StatusEnumStub::class),
            ],
            'foobaz' => ['required', 'string', new In(1, 2)],
        ];

        $validated = $request->validate($rules);
    }

    public function call_validateWithBag(Request $request)
    {
        $rules = [
            'foobar' => [
                'required',
                'in:1, 2, 3, 4',
            ],
            'foobaz' => ['required', 'string', 'email'],
        ];

        $validated = $request->validateWithBag('foobaz', $rules);
    }

    public function no_validate_call(Request $request)
    {
        return response()->json('Hi');
    }

    public function call_validate_on_different_class(Container $container)
    {
        $container->validate([
            'foobar' => [
                'required',
                'in:1, 2, 3, 4',
            ],
            'foobaz' => ['required', 'string', 'email'],
        ]);
    }

    public function nested_methods_calls(Request $request)
    {
        $this->validateFormData($request);

        return response()->json('Hi');
    }

    public function call_with_static_side_effect_assignment(Request $request): void
    {
        // Must not execute during extraction — previously wrote to the DB / ran app code.
        $user = \Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Ast\Stubs\SideEffectStub::boom();

        $validated = $request->validate([
            'foobar' => 'required|string',
        ]);
    }

    public function call_with_new_side_effect_assignment(Request $request): void
    {
        $instance = new \Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Ast\Stubs\SideEffectStub;

        $validated = $request->validate([
            'foobar' => 'required|string',
        ]);
    }

    public function call_with_static_keyword_assignment(Request $request): void
    {
        // Relative keywords must not fatal ("Class 'static' not found") during extraction.
        $query = static::missingMethodThatWouldFatal();

        $validated = $request->validate([
            'foobar' => 'required|string',
        ]);
    }

    public function call_with_self_validation_rules(Request $request): void
    {
        $validated = $request->validate(self::getValidationRules());
    }

    public function call_with_static_validation_rules(Request $request): void
    {
        $validated = $request->validate(static::getValidationRules());
    }

    public function call_with_explicit_class_validation_rules(Request $request): void
    {
        // NameResolver keeps/rewrites this to the enclosing FQCN (not self/static keywords).
        $validated = $request->validate(TestController::getValidationRules());
    }

    public function call_with_unknown_self_validation_rules(Request $request): void
    {
        // Method is not defined on the class — nested lookup fails, concrete value is null.
        $validated = $request->validate(self::missingValidationRulesHelper());
    }

    public function call_with_variable_class_static_validation_rules(Request $request): void
    {
        $class = self::class;

        $validated = $request->validate($class::getValidationRules());
    }

    public function call_with_variable_method_nested_rules(Request $request): void
    {
        $method = 'craftRules';

        $validated = $request->validate($this->{$method}());
    }

    public function call_with_foreign_class_validation_rules(Request $request): void
    {
        // Local helper shares the method name, but the callee is a different class.
        $validated = $request->validate(\stdClass::getValidationRules());
    }

    private function validateFormData(Request $request)
    {
        return $request->validate([
            'foobaz' => ['required', 'string', 'email'],
        ]);
    }

    private function craftRules(): array
    {
        $rule = ['required', 'string', 'email'];

        $interpolation = 'required|string';

        $interpolation2 = '|max:200';

        $fieldname = 'foobaz';

        return [
            'foobar' => 'required_with:'.$fieldname,
            'foobarr' => 'present_with'.':'.'foobar',
            'fooobazz' => "{$interpolation}|email{$interpolation2}",
            'baz' => "{$interpolation}|present".'|'.'email',
            'foobaz' => $rule,
        ];
    }

    private static function getValidationRules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email',
        ];
    }
}
