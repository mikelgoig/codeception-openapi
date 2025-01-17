<?php

declare(strict_types=1);

use MikelGoig\EasyCodingStandard\SetList as CodingStandard;
use PhpCsFixer\Fixer\ClassNotation\FinalClassFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src'])

    ->withRootFiles()

    ->withSets([CodingStandard::DEFAULT, CodingStandard::RISKY])

    ->withSkip([
        FinalClassFixer::class => [__DIR__ . '/src/OpenApi.php'],
    ])
;
