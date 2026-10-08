<?php

declare(strict_types=1);

use Pest\ArchPresets\Php;

// Arch presets arrived with Pest 3; Pest 2 (the PHP 8.1 + Laravel 10 lane) gets the explicit rules only.
if (class_exists(Php::class)) {
    arch()->preset()->php();

    arch()->preset()->security();
}

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('Laranex\LaralogClient')
    ->toUseStrictTypes();
