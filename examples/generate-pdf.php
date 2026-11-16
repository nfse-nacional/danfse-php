<?php

declare(strict_types=1);

use Danfse\Danfse\Danfse;

require dirname(__DIR__).'/vendor/autoload.php';

$output = $argv[1] ?? __DIR__.'/output/danfse-exemplo.pdf';
$outputDirectory = dirname($output);

if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0775, true) && ! is_dir($outputDirectory)) {
    throw new RuntimeException("Não foi possível criar o diretório: {$outputDirectory}");
}

$nfse = [
    'infNfse' => [
        'id' => 'NFS150600822123456780001990000000000042260700000001',
        'numeroNfse' => '42',
        'dataProcessamento' => '2026-07-26T14:35:00-03:00',
        'ambienteGerador' => '2',
        'codigoStatus' => 100,
        'descricaoTributacaoNacional' => 'Análise e desenvolvimento de sistemas.',
        'localEmissao' => 'São Paulo',
        'localPrestacao' => 'São Paulo',
        'nomeLocalIncidencia' => 'São Paulo',
        'descricaoNbs' => 'Serviços de consultoria',
        'outrasInformacoes' => 'Documento gerado pelo exemplo do pacote danfse-php.',
        'emitente' => [
            'cnpj' => '12345678000199',
            'nome' => 'EMPRESA PRESTADORA DE SERVIÇOS LTDA',
            'inscricaoMunicipal' => '123456',
            'telefone' => '(91) 99999-0000',
            'email' => 'prestador@example.com',
            'endereco' => [
                'logradouro' => 'Avenida Principal',
                'numero' => '100',
                'complemento' => 'Sala 1',
                'bairro' => 'Centro',
                'cep' => '01310100',
                'codigoMunicipio' => '3550308',
                'uf' => 'SP',
            ],
        ],
        'dps' => [
            'infDps' => [
                'tipoAmbiente' => '1',
                'tipoEmitente' => '1',
                'finalidadeNfse' => '0',
                'dataCompetencia' => '2026-07-01',
                'dataEmissao' => '2026-07-26T14:30:00-03:00',
                'codigoLocalEmissao' => '3550308',
                'numeroDps' => '100',
                'serie' => '900',
                'prestador' => [
                    'regimeTributario' => [
                        'opcaoSimplesNacional' => '1',
                        'regimeApuracaoTributosSn' => '1',
                        'regimeEspecialTributacao' => '0',
                    ],
                ],
                'tomador' => [
                    'cpf' => '12345678901',
                    'nome' => 'CLIENTE DE EXEMPLO',
                    'telefone' => '(91) 98888-0000',
                    'email' => 'cliente@example.com',
                    'endereco' => [
                        'logradouro' => 'Rua das Flores',
                        'numero' => '200',
                        'bairro' => 'Nazaré',
                        'cep' => '66000000',
                        'codigoMunicipio' => '1501402',
                        'municipio' => 'Belém',
                    ],
                ],
                'servico' => [
                    'codigoServico' => [
                        'descricaoServico' => 'Consultoria e assessoria em tecnologia da informação.',
                        'codigoTributacaoNacional' => '010101',
                        'codigoTributacaoMunicipal' => '1001',
                        'codigoNbs' => '115029000',
                    ],
                    'localPrestacao' => [
                        'codigoPaisPrestacao' => '1058',
                    ],
                ],
                'valores' => [
                    'valorServicoPrestado' => [
                        'valorServico' => 1500.00,
                    ],
                    'desconto' => [
                        'valorDescontoIncondicionado' => 0,
                        'valorDescontoCondicionado' => 0,
                    ],
                    'tributacao' => [
                        'aliquota' => 5,
                        'valorPis' => 9.75,
                        'valorCofins' => 45,
                        'valorTotalTributosFederais' => 54.75,
                        'valorTotalTributosEstaduais' => 0,
                        'valorTotalTributosMunicipais' => 75,
                        'tributacaoIssqn' => '1',
                        'tipoRetencaoIssqn' => '1',
                        'tipoRetencaoPisCofins' => '2',
                    ],
                ],
                // Grupo IBS/CBS da DPS (reforma tributária, LC 214/2025)
                'ibscbs' => [
                    'finalidadeNfse' => '0',
                    'codigoIndicadorOperacao' => '100301',
                    'cst' => '000',
                    'codigoClassificacaoTributaria' => '000001',
                ],
            ],
        ],
        'valores' => [
            'valorLiquido' => 1500,
            'baseCalculo' => 1500,
            'aliquotaAplicada' => 5,
            'valorIssqn' => 75,
            'valorTotalRetido' => 0,
        ],
        // Grupo IBS/CBS apurado na NFS-e (alíquotas de teste de 2026)
        'ibscbs' => [
            'codigoLocalidadeIncidencia' => '3550308',
            'nomeLocalidadeIncidencia' => 'São Paulo',
            'baseCalculo' => 1500.00,
            'valorCalculadoReembolso' => 0,
            'aliquotaIbsUf' => 0.10,
            'percentualReducaoAliquotaUf' => 0,
            'aliquotaEfetivaUf' => 0.10,
            'aliquotaIbsMunicipal' => 0,
            'aliquotaEfetivaMunicipal' => 0,
            'aliquotaCbs' => 0.90,
            'percentualReducaoAliquotaCbs' => 0,
            'aliquotaEfetivaCbs' => 0.90,
            'valorIbsUf' => 1.50,
            'valorIbsMunicipal' => 0,
            'valorTotalIbs' => 1.50,
            'valorCbs' => 13.50,
            'valorTotalNota' => 1515.00,
        ],
    ],
];

$pdf = (new Danfse)->render($nfse);

if (file_put_contents($output, $pdf) === false) {
    throw new RuntimeException("Não foi possível gravar o PDF: {$output}");
}

fwrite(STDOUT, "PDF gerado em: {$output}".PHP_EOL);
