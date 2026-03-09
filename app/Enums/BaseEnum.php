<?php

namespace App\Enums;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use JsonSerializable;
use ReflectionClass;
use BadMethodCallException;

abstract class BaseEnum implements CastsAttributes, JsonSerializable
{
    protected string|int|null $value = null;

    final public function __construct()
    {
    }

    /* ================= ELOQUENT CAST ================= */

    public function get($model, string $key, $value, array $attributes): static
    {
        return (new static())->make($value);
    }

    public function set($model, string $key, $value, array $attributes): string|int|null
    {
        if ($value instanceof static) {
            return $value->value;
        }

        return $value;
    }

    /* ================= CORE ================= */

    public function make(string|int|null $value): static
    {
        if ($value !== null && ! static::isValid($value)) {
            $this->value = '';
        }

        $this->value = $value;

        return $this;
    }

    public function value(): string|int|null
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function equals(?BaseEnum $enum): bool
    {
        return $enum !== null
            && $this->value === $enum->value
            && static::class === $enum::class;
    }

    /* ================= ENUM META ================= */

    public static function toArray(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    public static function values(): array
    {
        return array_values(static::toArray());
    }

    public static function keys(): array
    {
        return array_keys(static::toArray());
    }

    public static function isValid(string|int|null $value): bool
    {
        return in_array($value, static::values(), true);
    }

    public static function __callStatic(string $name, array $arguments): static
    {
        $constants = static::toArray();

        if (! array_key_exists($name, $constants)) {
            throw new BadMethodCallException(
                "Enum constant {$name} does not exist in " . static::class
            );
        }

        return (new static())->make($constants[$name]);
    }


    public static function options(): array
    {
        return array_map(
            fn ($value) => [
                'value' => $value,
                'label' => static::labelFor($value),
            ],
            static::values()
        );
    }

    public static function collect(): Collection
    {
        return collect(static::options());
    }

    abstract public static function labelFor(string|int $value): string;

    public function label(): string
    {
        return static::labelFor($this->value);
    }

    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }
}
