<?php

namespace Laravie\QueryFilter;

use Illuminate\Support\Str;
use Laravie\QueryFilter\Concerns\SearchingWildcard;
use Laravie\QueryFilter\Contracts\Keyword as KeywordContract;
use Laravie\QueryFilter\Concerns\ConditionallySearchingWildcard;

class Keyword implements KeywordContract
{
    use ConditionallySearchingWildcard,
        SearchingWildcard;

    /**
     * Keyword value.
     *
     * @var string
     */
    protected $value;

    /**
     * List default search variations.
     *
     * @var string[]
     */
    public static $defaultSearchVariations = ['{keyword}', '{keyword}%', '%{keyword}', '%{keyword}%'];

    /**
     * Construct a new Keyword value object.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Get keyword value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Validate keyword value.
     */
    public function validate(): bool
    {
        return ! empty($this->value);
    }

    /**
     * Get searchable strings.
     */
    public function all(): array
    {
        return static::searchable(
            $this->value,
            $this->wildcardCharacter,
            $this->wildcardReplacement,
            $this->wildcardSearching ?? true
        );
    }

    /**
     * Get searchable strings as lowercase.
     */
    public function allLowerCase(): array
    {
        return static::searchable(
            Str::lower($this->value),
            $this->wildcardCharacter,
            $this->wildcardReplacement,
            $this->wildcardSearching ?? true
        );
    }

    /**
     * Handle resolving keyword for filter.
     */
    public function handle(Contracts\SearchFilter $filter): array
    {
        if ($filter instanceof Contracts\Keyword\AsExactValue) {
            return [$this->getValue()];
        } elseif ($filter instanceof Contracts\Keyword\AsLowerCase) {
            return $this->allLowerCase();
        }

        return $this->all();
    }

    /**
     * Return value as string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Convert basic string to searchable result.
     */
    public static function searchable(string $text, ?string $wildcard = '*', ?string $replacement = '%', bool $wildcardSearching = true): array
    {
        $text = static::sanitize($text);

        if (empty($text)) {
            return [];
        } elseif (\is_null($replacement)) {
            return [$text];
        } elseif (! Str::contains($text, \array_filter([$wildcard, $replacement])) && $wildcardSearching === true) {
            return \collect(static::$defaultSearchVariations)
                ->map(function ($string) use ($text) {
                    return Str::replaceFirst('{keyword}', $text, $string);
                })->all();
        }

        return [
            \str_replace($wildcard, $replacement, $text),
        ];
    }

    /**
     * Sanitize keywords.
     */
    public static function sanitize(string $keyword): string
    {
        $words = \preg_replace('/[^\w\*\s]/iu', '', $keyword);

        if (empty(\trim($words))) {
            return '';
        } elseif (\strlen($words) > 3 && \strlen($words) < (\strlen($keyword) * 0.5)) {
            return $words;
        }

        return $keyword;
    }
}