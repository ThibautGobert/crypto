<?php

namespace App\Enums\Traits;

use App\Enums\Attributes\Description;
use ReflectionClass;

trait EnumTrait
{
    public static function getConstants(): array
    {
        $self = new ReflectionClass(static::class);

        return $self->getConstants();
    }

    public static function getDescription($value): string
    {
        foreach (static::getConstants() as $key => $val) {
            if ($val === $value) {
                return Description::getDescription(static::class, $key);
            }
        }

        return '';
    }

    public static function getList(): array
    {
        $res = [];

        foreach (static::getConstants() as $key => $val) {
            $res[$val->value] = static::getDescription($val);
        }

        return $res;
    }

    public static function getAll(): array
    {
        $res = [];
        foreach (static::getConstants() as $key => $val) {
            $res[] = [
                'id' => $val->value,
                'key' => $key,
                'label' => static::getDescription($val),
            ];
        }
        return $res;
    }
}
