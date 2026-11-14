<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\DataTransferObjects\Concerns;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Stringable;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\Concerns\JsonStringable;

#[CoversTrait(JsonStringable::class)]
class JsonStringableUnitTest extends TestCase
{
    public function test_it_indents_with_two_spaces_and_ends_with_a_new_line(): void
    {
        // Arrange

        $subject = $this->subject(['list' => ['one', 'two'], 'nested' => ['key' => 'value']]);

        $expected = <<<'JSON'
{
  "list": [
    "one",
    "two"
  ],
  "nested": {
    "key": "value"
  }
}

JSON;

        // Act

        $json = $subject->toJson();

        // Assert

        $this->assertSame(
            $expected,
            $json,
        );
    }

    public function test_it_keeps_slashes_and_unicode_readable(): void
    {
        // Arrange

        $subject = $this->subject(['path' => 'users/admin', 'name' => 'Création']);

        // Act

        $json = $subject->toJson();

        // Assert

        $this->assertStringContainsString(
            '"path": "users/admin"',
            $json,
        );

        $this->assertStringContainsString(
            '"name": "Création"',
            $json,
        );
    }

    public function test_it_keeps_the_leading_spaces_of_a_value(): void
    {
        // Arrange

        $subject = $this->subject(['text' => "first\n    second"]);

        // Act

        $json = $subject->toJson();

        // Assert

        $this->assertSame(
            "{\n  \"text\": \"first\\n    second\"\n}\n",
            $json,
        );
    }

    public function test_it_turns_into_the_same_text_as_a_string(): void
    {
        // Arrange

        $subject = $this->subject(['key' => 'value']);

        // Act

        $text = (string) $subject;

        // Assert

        $this->assertSame(
            $subject->toJson(),
            $text,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return object{toJson: callable}&Stringable
     */
    private function subject(array $data): object
    {
        return new class($data) implements Stringable
        {
            use JsonStringable;

            /**
             * @param  array<string, mixed>  $data
             */
            public function __construct(private readonly array $data) {}

            public function toArray(): array
            {
                return $this->data;
            }
        };
    }
}
