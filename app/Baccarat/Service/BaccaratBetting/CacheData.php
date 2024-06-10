<?php

namespace App\Baccarat\Service\BaccaratBetting;

use Carbon\Carbon;

class CacheData
{
    public function __construct(protected string $title,protected string $date,protected array $rules = [])
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public static function make(string $title,string $date,array $rules = []): CacheData
    {
        return new self($title,$date,$rules);
    }

    public function exists(string $rule,string $deckId): bool
    {
        return isset($this->rules[$rule][$deckId]);
    }

    public function add(string $rule,string $deckId):void
    {
        $this->rules[$rule][$deckId] ??= Carbon::now()->toDateTimeString();
    }
}