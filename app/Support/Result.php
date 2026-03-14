<?php

declare(strict_types=1);

namespace App\Support;

final readonly class Result
{
    private function __construct(
        private bool $ok,
        private mixed $val = null,
        private mixed $err = null,
    ) {}

    public static function ok(mixed $value = null): static
    {
        return new self(true, $value);
    }

    public static function err(mixed $error): static
    {
        return new self(false, null, $error);
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
