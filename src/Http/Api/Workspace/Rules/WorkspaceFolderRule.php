<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * Validates a folder path or a single folder name from the interface.
 */
class WorkspaceFolderRule implements ValidationRule
{
    /**
     * @param  bool  $isNested  Whether the value can hold several names separated by slashes.
     */
    public function __construct(
        private readonly bool $isNested = true,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $problem = $this->findProblem($value);

        if ($problem === null) {
            return;
        }

        $fail($problem);
    }

    private function findProblem(mixed $value): ?string
    {
        if (! is_string($value)) {
            return 'The :attribute must be a string.';
        }

        $names = $this->namesIn($value);
        $maxDepth = SavedRequest::MAX_FOLDER_DEPTH;

        return match (true) {
            $this->hasSlashesButMustBeFlat($value) => 'The :attribute may not contain slashes.',
            $names === [] => 'The :attribute must have a folder name.',
            count($names) > $maxDepth => "The :attribute may not nest folders more than {$maxDepth} levels deep.",
            default => $this->findNameProblem($names),
        };
    }

    /**
     * @param  list<string>  $names
     */
    private function findNameProblem(array $names): ?string
    {
        $maxLength = FolderPath::MAX_SEGMENT_LENGTH;

        foreach ($names as $name) {
            if ($this->isTooLong($name)) {
                return "Each folder name in the :attribute may not be longer than {$maxLength} characters.";
            }

            // Names are turned into slugs later, so a name without a letter or number would vanish.
            if ($this->hasNoLetterOrNumber($name)) {
                return 'Each folder name in the :attribute needs at least one letter or number.';
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function namesIn(string $value): array
    {
        $names = [];

        foreach (explode('/', $this->withForwardSlashes($value)) as $segment) {
            $name = trim($segment);

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function hasSlashesButMustBeFlat(string $value): bool
    {
        if ($this->isNested) {
            return false;
        }

        return str_contains($this->withForwardSlashes($value), '/');
    }

    private function withForwardSlashes(string $value): string
    {
        return str_replace('\\', '/', $value);
    }

    private function isTooLong(string $name): bool
    {
        return mb_strlen($name) > FolderPath::MAX_SEGMENT_LENGTH;
    }

    private function hasNoLetterOrNumber(string $name): bool
    {
        return Str::slug($name) === '';
    }
}
