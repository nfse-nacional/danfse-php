<?php

declare(strict_types=1);

use Danfse\Danfse\Danfse;

it('bundles the national nfse JRXML template inside src', function () {
    $danfse = new Danfse;
    $template = file_get_contents($danfse->templatePath());

    expect($danfse->templatePath())
        ->toBeFile()
        ->and($template)
        ->toContain('name="nfse-nacional-padrao"')
        ->toContain('<![CDATA[$P{municipioEmissao}]]>')
        ->toContain('<![CDATA[$P{imgPrefeitura}]]>')
        ->toContain('<![CDATA[$P{linkPublico}]]>')
        ->not->toContain('LINK_CONSULTA_PUBLICA')
        ->not->toContain('<element kind=')
        ->not->toContain('String.valueOf(');
});

it('bundles the editable layout state for the national nfse template', function () {
    $layoutState = dirname((new Danfse)->templatePath()).'/nfse-nacional.json';

    expect($layoutState)
        ->toBeFile()
        ->and(json_decode((string) file_get_contents($layoutState), true, flags: JSON_THROW_ON_ERROR))
        ->toHaveKeys(['config', 'layout_state']);
});

it('renders the bundled national nfse template to PDF', function () {
    $pdf = (new Danfse)->render([
        'infNfse' => [
            'id' => 'NFS123456789',
            'numeroNfse' => '42',
            'dps' => ['infDps' => []],
        ],
    ]);

    expect($pdf)
        ->toStartWith('%PDF-')
        ->and(strlen($pdf))->toBeGreaterThan(50_000);
});
