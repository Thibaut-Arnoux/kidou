<?php

declare(strict_types=1);

namespace App\Support;

final class Result
{
    private function __construct(
        private readonly bool $ok,
        private readonly mixed $val = null,
        private readonly mixed $err = null,
    ) {}

    public static function ok(mixed $value = null): static
    {
        return new self(true, $value);
    }

    public static function err(mixed $error): static
    {
        return new static(false, null, $error);
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function isErr(): bool
    {
        return ! $this->ok;
    }

    public function value(): mixed
    {
        return $this->val;
    }

    public function error(): mixed
    {
        return $this->err;
    }
}
