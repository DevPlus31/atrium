<?php

declare(strict_types=1);

use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

it('resolves the typescript transformer configuration', function (): void {
    $config = $this->app->make(TypeScriptTransformerConfig::class);

    expect($config)->toBeInstanceOf(TypeScriptTransformerConfig::class);
});
