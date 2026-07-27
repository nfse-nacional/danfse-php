<?php

declare(strict_types=1);

use Danfse\Danfse\Danfse;

it('bundles the national nfse JRXML template inside src', function () {
    $danfse = new Danfse;

    expect($danfse->templatePath())
        ->toBeFile()
        ->and(file_get_contents($danfse->templatePath()))
        ->toContain('name="nfse-nacional-padrao"');
});

it('renders the bundled national nfse template to PDF', function () {
    $pdf = (new Danfse)->render([
        'chave' => '123456789',
        'numero_nfse' => '42',
        'consulta_publica_url' => 'https://example.test/nfse/42',
        'prefeitura' => [
            'nome' => 'São Paulo',
            'estado' => 'Pará',
            'telefone' => '',
            'email' => '',
        ],
        'payload_nfse' => [
            'infNfse' => [
                'numeroNfse' => '42',
                'dps' => ['infDps' => []],
            ],
        ],
    ]);

    expect($pdf)->toStartWith('%PDF-');
});
