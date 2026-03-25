<?php

namespace Danfse\Danfse\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Danfse\Danfse\Danfse
 */
class Danfse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Danfse\Danfse\Danfse::class;
    }
}
