<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Collection;
use JsonSerializable;

final readonly class LimitOffsetPaginator implements JsonSerializable
{
    public function __construct(
        public Collection $data,
        public int $total,
        public int $limit,
        public int $offset,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'data' => $this->data,
            'total' => $this->total,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }
}
