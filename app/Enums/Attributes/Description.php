<?php

namespace App\Enums\Attributes;

use Attribute;
use ReflectionClassConstant;

#[Attribute]
class Description
{
    public function __construct(public string $description)
    {
    }

    public static function getDescription(string $className, string $constName): string
    {
        $refConst = new ReflectionClassConstant($className, $constName);
        $attributes = $refConst->getAttributes(self::class);

        return $attributes && isset($attributes[0]) ? $attributes[0]->newInstance()->description : '';
    }
}
