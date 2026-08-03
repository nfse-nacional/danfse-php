<?php

declare(strict_types=1);

use Danfse\Danfse\Mappers\NfseTemplateMapper;

it('maps a national nfse payload to the bundled template parameters', function () {
    $source = [
        'infNfse' => [
            'id' => 'NFS123456',
            'numeroNfse' => '42',
            'localEmissao' => 'São Paulo',
            'localPrestacao' => 'Belém',
            'nomeLocalIncidencia' => 'São Paulo',
            'dps' => [
                'infDps' => [
                    'dataCompetencia' => '2026-07-26',
                    'dataEmissao' => '2026-07-26T14:30:00-03:00',
                    'codigoLocalEmissao' => '3550308',
                    'prestador' => [],
                    'tomador' => [
                        'cpf' => '12345678901',
                        'nome' => 'Cliente',
                        'endereco' => ['codigoMunicipio' => '1501402'],
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
                'endereco' => [
                    'municipio' => 'São Paulo',
                    'uf' => 'SP',
                ],
            ],
            'valores' => ['valorLiquido' => 1200],
        ],
    ];

    $parameters = (new NfseTemplateMapper)->parameters($source, []);

    expect($parameters)
        ->toMatchArray([
            'cdChave' => '123456',
            'linkPublico' => 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=123456',
            'nNFSe' => '42',
            'dCompet' => '26/07/2026',
            'dhEmi' => '26/07/2026 14:30:00',
            'emitCNPJ' => '12.345.678/0001-99',
            'tomaCPF' => '123.456.789-01',
            'tomaxMun' => '1501402',
            'xDescServ' => 'Consultoria',
            'xLocPrestacao' => 'Belém',
            'xLocIncid' => 'São Paulo',
            'vServ' => '1.234,50',
            'vLiq' => '1.200,00',
            'imgPrefeitura' => '',
            'municipioCodigo' => '3550308',
            'municipioEmissao' => 'Município: São Paulo / SP',
        ]);
});

it('resolves the issuing municipality from nested nfse-php DTO objects', function () {
    $source = (object) [
        'infNfse' => (object) [
            'localEmissao' => 'Fortaleza',
            'emitente' => (object) [
                'endereco' => (object) [
                    'uf' => 'CE',
                ],
            ],
            'dps' => (object) [
                'infDps' => (object) [
                    'codigoLocalEmissao' => '2304400',
                    'servico' => (object) [
                        'codigoServico' => (object) [
                            'codigoTributacaoNacional' => '010101',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $parameters = (new NfseTemplateMapper)->parameters($source, []);

    expect($parameters)
        ->toMatchArray([
            'municipioCodigo' => '2304400',
            'municipioEmissao' => 'Município: Fortaleza / CE',
        ]);
});

it('resolves the issuing municipality without requiring an IBGE code', function () {
    $parameters = (new NfseTemplateMapper)->parameters([
        'infNfse' => [
            'localEmissao' => 'Fortaleza',
            'emitente' => ['endereco' => ['uf' => 'CE']],
        ],
    ], []);

    expect($parameters['municipioEmissao'])->toBe('Município: Fortaleza / CE');
});

it('uses an explicit public link from the payload', function () {
    $parameters = (new NfseTemplateMapper)->parameters([
        'linkPublico' => 'https://example.test/nfse/42',
        'infNfse' => ['id' => 'NFS123456'],
    ], []);

    expect($parameters['linkPublico'])->toBe('https://example.test/nfse/42');
});

it('maps an optional city hall image URL', function () {
    $parameters = (new NfseTemplateMapper)->parameters([
        'imgPrefeitura' => 'https://example.test/images/brasao.png',
        'infNfse' => [],
    ], []);

    expect($parameters['imgPrefeitura'])
        ->toBe('https://example.test/images/brasao.png');
});

it('hides the issuing municipality for national taxation codes starting with 99', function () {
    $parameters = (new NfseTemplateMapper)->parameters([
        'infNfse' => [
            'localEmissao' => 'São Paulo',
            'emitente' => ['endereco' => ['uf' => 'SP']],
            'dps' => [
                'infDps' => [
                    'codigoLocalEmissao' => '3550308',
                    'servico' => [
                        'codigoServico' => ['codigoTributacaoNacional' => '990101'],
                    ],
                ],
            ],
        ],
    ], []);

    expect($parameters['municipioEmissao'])->toBe('');
});

it('allows customization of one template variable', function () {
    $mapper = new class extends NfseTemplateMapper
    {
        protected function mapParameter(string $name, mixed $value, mixed $source, array $config): mixed
        {
            return $name === 'nNFSe' ? 'CUSTOM-'.$value : $value;
        }
    };

    expect($mapper->parameters(['infNfse' => ['numeroNfse' => '10']], [])['nNFSe'])
        ->toBe('CUSTOM-10');
});

it('prints the homologation notice required by NT 008/2026 when tpAmb is 2', function () {
    $parameters = (new NfseTemplateMapper)->parameters(['tpAmb' => 2], []);

    expect($parameters['avisoHomologacao'])->toBe('NFS-e SEM VALIDADE JURÍDICA');
});

it('reads tpAmb from the dps when it is not at the payload root', function () {
    $source = ['infNfse' => ['dps' => ['infDps' => ['tpAmb' => 2]]]];

    $parameters = (new NfseTemplateMapper)->parameters($source, []);

    expect($parameters['avisoHomologacao'])->toBe('NFS-e SEM VALIDADE JURÍDICA');
});

it('leaves the homologation notice empty in production', function () {
    $parameters = (new NfseTemplateMapper)->parameters(['tpAmb' => 1], []);

    expect($parameters['avisoHomologacao'])->toBe('');
});

it('binds the homologation notice parameter in the bundled template', function () {
    $template = file_get_contents(__DIR__.'/../src/Templates/nfse-nacional.jrxml');

    expect($template)
        ->toContain('<parameter name="avisoHomologacao"')
        ->toContain('$P{avisoHomologacao}');
});
