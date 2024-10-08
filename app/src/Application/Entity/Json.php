<?php

declare(strict_types=1);

namespace App\Application\Entity;

final readonly class Json implements \JsonSerializable, \Stringable
{
    public static function fromString(string $value): self
    {
        return new self(\json_decode(json: $value, associative: true, flags: \JSON_THROW_ON_ERROR));
    }

    public function __construct(
        public array|\JsonSerializable $data = [],
    ) {}

    /**
     * Create from data storage raw value
     */
    final public static function typecast(mixed $value): self
    {
        if (empty($value)) {
            return new self();
        }

        try {
            return self::fromString((string) $value);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function jsonSerialize(): array
    {
        return $this->data instanceof \JsonSerializable
            ? $this->data->jsonSerialize()
            : $this->data;
    }

    public function __toString(): string
    {
        return \json_encode($this);
    }
}
