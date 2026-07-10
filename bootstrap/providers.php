<?php

use App\Providers\AppServiceProvider;
use App\Providers\DomainServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
    FortifyServiceProvider::class,
];
