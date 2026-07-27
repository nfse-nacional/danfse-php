<?php

declare(strict_types=1);

use Danfse\Danfse\Mappers\NfseTemplateMapper;

it('maps a national nfse payload to the bundled template parameters', function () {
    $source = [
        'chave' => 'NFSe-123.456',
        'numero_nfse' => '42',
        'consulta_publica_url' => 'https://example.test/nfse/42',
        'emitente_municipio' => 'São Paulo',
        'tomador_municipio' => 'São Paulo',
        'local_prestacao' => 'Belém',
        'prefeitura' => [
            'nome' => 'São Paulo',
            'estado' => 'SP',
            'telefone' => '(91) 3000-0000',
            'email' => 'nfse@example.test',
        ],
        'payload_nfse' => [
            'infNfse' => [
                'numeroNfse' => '42',
                'dps' => [
                    'infDps' => [
                        'dataCompetencia' => '2026-07-26',
                        'dataEmissao' => '2026-07-26T14:30:00-03:00',
                        'prestador' => [],
                        'tomador' => [
                            'cpf' => '12345678901',
                            'nome' => 'Cliente',
                        ],
                        'servico' => [
                            'codigoServico' => [
                                'descricaoServico' => 'Consultoria',
                                'codigoTributacaoNacional' => '010101',
                            ],
                        ],
                        'valores' => [
                            'valorServicoPrestado' => ['valorServico' => 1234.5],
                        ],
                    ],
                ],
                'emitente' => [
                    'cnpj' => '12345678000199',
                    'nome' => 'Prestador',
                ],
                'valores' => ['valorLiquido' => 1200],
            ],
        ],
    ];

    $parameters = (new NfseTemplateMapper)->parameters($source, []);

    expect($parameters)
        ->toMatchArray([
            'PREFEITURA_NOME' => 'São Paulo',
            'cdChave' => '123456',
            'LINK_CONSULTA_PUBLICA' => 'https://example.test/nfse/42',
            'nNFSe' => '42',
            'dCompet' => '26/07/2026',
            'dhEmi' => '26/07/2026 14:30:00',
            'emitCNPJ' => '12.345.678/0001-99',
            'tomaCPF' => '123.456.789-01',
            'xDescServ' => 'Consultoria',
            'vServ' => '1.234,50',
            'vLiq' => '1.200,00',
        ]);
});

it('allows customization of one template variable', function () {
    $mapper = new class extends NfseTemplateMapper
    {
        protected function mapParameter(string $name, mixed $value, mixed $source, array $config): mixed
        {
            return $name === 'nNFSe' ? 'CUSTOM-'.$value : $value;
        }
    };

    expect($mapper->parameters(['numero_nfse' => '10'], [])['nNFSe'])
        ->toBe('CUSTOM-10');
});
