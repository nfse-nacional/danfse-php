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
            'tomaCNPJ' => '123.456.789-01',
            'tomaxMun' => '-',
            'tomaIbgeCep' => '15.01402',
            'cTrib' => '01.01.01 / -',
            'xDescServ' => 'Consultoria',
            'xLocPrestacao' => 'Belém',
            'xLocIncid' => 'São Paulo',
            'vServ' => 'R$ 1.234,50',
            'vLiq' => 'R$ 1.200,00',
            'vTotIbsCbs' => 'R$ 0,00',
            'vTotNF' => 'R$ 1.200,00',
            'tpEmit' => 'Prestador',
            'homologacao' => 'false',
            'municipioCodigo' => '3550308',
            'municipioEmissao' => 'Município: São Paulo / SP',
        ]);
});

it('maps the IBS/CBS groups of the DPS and of the NFS-e', function () {
    $parameters = (new NfseTemplateMapper)->parameters([
        'infNfse' => [
            'valores' => ['valorLiquido' => 1500],
            'ibscbs' => [
                'codigoLocalidadeIncidencia' => '3550308',
                'nomeLocalidadeIncidencia' => 'São Paulo',
                'baseCalculo' => 1500,
                'aliquotaIbsUf' => 0.1,
                'aliquotaIbsMunicipal' => 0,
                'aliquotaEfetivaUf' => 0.1,
                'aliquotaCbs' => 0.9,
                'aliquotaEfetivaCbs' => 0.9,
                'valorIbsUf' => 1.5,
                'valorTotalIbs' => 1.5,
                'valorCbs' => 13.5,
            ],
            'dps' => [
                'infDps' => [
                    'ibscbs' => [
                        'codigoIndicadorOperacao' => '100301',
                        'cst' => '000',
                        'codigoClassificacaoTributaria' => '000001',
                    ],
                ],
            ],
        ],
    ], []);

    expect($parameters)->toMatchArray([
        'ibsCbsCst' => '000 / 000001',
        'ibsCbsIncid' => '100301 / 35.50308 / São Paulo / SP',
        'ibsCbsBC' => 'R$ 1.500,00',
        'ibsAliq' => '0,10% / 0,00%',
        'ibsUfAliqEfet' => '0,10%',
        'vIBSUF' => 'R$ 1,50',
        'vIBSTot' => 'R$ 1,50',
        'cbsAliq' => '0,90%',
        'vCBS' => 'R$ 13,50',
        'vTotIbsCbs' => 'R$ 15,00',
        'vTotNF' => 'R$ 1.515,00',
    ]);
});

it('prints dashes when the nfse has no IBS/CBS group', function () {
    $parameters = (new NfseTemplateMapper)->parameters(['infNfse' => []], []);

    expect($parameters)->toMatchArray([
        'ibsCbsCst' => '- / -',
        'ibsCbsIncid' => '- / - / - / -',
        'ibsCbsBC' => '-',
        'vIBSTot' => '-',
        'vCBS' => '-',
        'vTotIbsCbs' => 'R$ 0,00',
        'vTotNF' => '-',
    ]);
});

it('flags the homologation environment', function () {
    $parameters = (new NfseTemplateMapper)->parameters([
        'infNfse' => ['dps' => ['infDps' => ['tipoAmbiente' => '2']]],
    ], []);

    expect($parameters)->toMatchArray([
        'homologacao' => 'true',
        'tpAmb' => 'Tipo de Ambiente: Homologação',
    ]);
});

it('writes the approximate taxes text required by the NT 008', function () {
    $parameters = (new NfseTemplateMapper)->parameters([
        'infNfse' => [
            'outrasInformacoes' => 'Pedido 123',
            'dps' => ['infDps' => ['valores' => ['tributacao' => ['percentualTotalTributosSN' => 6]]]],
        ],
    ], []);

    expect($parameters['infCompl'])->toBe(
        "Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: -; Estaduais: -; Municipais: -; Alíquota do Simples Nacional: 6,00%.\nPedido 123"
    );
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
