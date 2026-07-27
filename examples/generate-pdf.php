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
    'chave' => '150600822123456780001990000000000042260700000001',
    'numero_nfse' => '42',
    'consulta_publica_url' => 'https://www.nfse.gov.br/EmissorNacional/Nota/150600822123456780001990000000000042260700000001',
    'emitente_municipio' => 'Primavera',
    'tomador_municipio' => 'Belém',
    'local_prestacao' => 'Primavera',
    'local_incidencia' => 'Primavera',
    'prefeitura' => [
        'nome' => 'Primavera',
        'estado' => 'Pará',
        'telefone' => '(91) 3481-1228',
        'email' => 'tributos@primavera.pa.gov.br',
    ],
    'payload_nfse' => [
        'infNfse' => [
            'numeroNfse' => '42',
            'dataProcessamento' => '2026-07-26T14:35:00-03:00',
            'localEmissao' => 'Primavera',
            'localPrestacao' => 'Primavera',
            'nomeLocalIncidencia' => 'Primavera',
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
                    'cep' => '68707000',
                ],
            ],
            'dps' => [
                'infDps' => [
                    'dataCompetencia' => '2026-07-01',
                    'dataEmissao' => '2026-07-26T14:30:00-03:00',
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
                        ],
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
        ],
    ],
];

$pdf = (new Danfse)->render($nfse);

if (file_put_contents($output, $pdf) === false) {
    throw new RuntimeException("Não foi possível gravar o PDF: {$output}");
}

fwrite(STDOUT, "PDF gerado em: {$output}".PHP_EOL);
