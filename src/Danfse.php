<?php

declare(strict_types=1);

namespace Danfse\Danfse;

use A2Insights\JxrmlTemplateEngine\Contracts\TemplateDataMapper;
use A2Insights\JxrmlTemplateEngine\JxrmlRenderer;
use A2Insights\JxrmlTemplateEngine\JxrmlTemplateEngine;
use Danfse\Danfse\Mappers\NfseTemplateMapper;
use JasperPHP\core\TJasper;
use ReflectionClass;

final class Danfse
{
    private readonly JxrmlTemplateEngine $engine;

    private readonly TemplateDataMapper $mapper;

    public function __construct(
        ?JxrmlTemplateEngine $engine = null,
        ?TemplateDataMapper $mapper = null,
        private readonly ?string $template = null,
    ) {
        $this->engine = $engine ?? new JxrmlTemplateEngine(new JxrmlRenderer(
            fontDirectory: $this->jasperFontDirectory(),
        ));
        $this->mapper = $mapper ?? new NfseTemplateMapper;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function render(
        mixed $nfse,
        array $parameters = [],
        ?TemplateDataMapper $mapper = null,
        ?string $template = null,
    ): string {
        $previousHandler = null;
        $previousHandler = set_error_handler(
            static function (int $severity, string $message, string $file, int $line) use (&$previousHandler): bool {
                if (str_contains(str_replace('\\', '/', $file), '/quilhasoft/jasperphp/')) {
                    return true;
                }

                if ($previousHandler !== null) {
                    return (bool) $previousHandler($severity, $message, $file, $line);
                }

                return false;
            },
            E_DEPRECATED,
        );

        try {
            return $this->engine->render(
                template: $template ?? $this->templatePath(),
                source: $nfse,
                mapper: $mapper ?? $this->mapper,
                parameters: $parameters,
            );
        } finally {
            restore_error_handler();
        }
    }

    public function templatePath(): string
    {
        return $this->template ?? __DIR__.'/Templates/nfse-nacional.jrxml';
    }

    private function jasperFontDirectory(): string
    {
        $jasperFile = (new ReflectionClass(TJasper::class))->getFileName();

        return dirname((string) $jasperFile, 2).'/fonts';
    }
}
