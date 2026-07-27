# DANFSe PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nfse-nacional/danfse-php.svg?style=flat-square)](https://packagist.org/packages/nfse-nacional/danfse-php)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nfse-nacional/danfse-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nfse-nacional/danfse-php/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nfse-nacional/danfse-php/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nfse-nacional/danfse-php/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nfse-nacional/danfse-php.svg?style=flat-square)](https://packagist.org/packages/nfse-nacional/danfse-php)

Gere o PDF do Documento Auxiliar da Nota Fiscal de Serviço eletrônica (DANFSe) a partir dos dados da NFS-e Nacional. O pacote inclui o template JRXML, o mapeamento dos dados e os recursos visuais necessários para produzir o documento.

## Requisitos

- PHP 8.4 ou superior
- Extensões exigidas pelo Composer

## Instalação

```bash
composer require nfse-nacional/danfse-php
```

## Uso

O método `render()` recebe um array ou objeto com os dados da NFS-e e retorna o conteúdo binário do PDF:

```php
<?php

use Danfse\Danfse\Danfse;

require 'vendor/autoload.php';

$nfse = [
    'chave' => '150600822123456780001990000000000042260700000001',
    'numero_nfse' => '42',
    'consulta_publica_url' => 'https://www.nfse.gov.br/EmissorNacional/Nota/...',
    'prefeitura' => [
        'nome' => 'São Paulo',
        'estado' => 'São Paulo',
        'telefone' => '(91) 3481-1228',
        'email' => 'tributos@example.com',
    ],
    'payload_nfse' => [
        'infNfse' => [
            'numeroNfse' => '42',
            'dps' => [
                'infDps' => [
                    'dataCompetencia' => '2026-07-01',
                    'dataEmissao' => '2026-07-26T14:30:00-03:00',
                ],
            ],
        ],
    ],
];

$pdf = (new Danfse)->render($nfse);

file_put_contents('danfse.pdf', $pdf);
```

![Outout](danfse-exemplo-1.png)

Para um payload mais completo, consulte o [exemplo de geração de PDF](examples/generate-pdf.php). As instruções para executá-lo estão na [documentação dos exemplos](examples/README.md).

## Fontes

O template padrão utiliza a família `Arial` nos estilos regular, negrito,
itálico e negrito-itálico. Não é necessário instalar a fonte manualmente para
usar o template incluído no pacote.

Ao criar a instância de `Danfse`, o pacote localiza automaticamente o diretório
de fontes distribuído pelo `quilhasoft/jasperphp` e o informa ao
`JxrmlTemplateEngine`. As definições utilizadas pelo TCPDF são:

| Estilo do JRXML | Definição TCPDF |
| --- | --- |
| `Arial` regular | `arial.php` e `arial.z` |
| `Arial` negrito | `arialb.php` e `arialb.z` |
| `Arial` itálico | `ariali.php` e `ariali.z` |
| `Arial` negrito-itálico | `arialbi.php` e `arialbi.z` |

Esses arquivos são instalados transitivamente em:

```text
vendor/quilhasoft/jasperphp/src/fonts
```

Uma fonte `.ttf` isolada não é suficiente para o TCPDF. Ao usar outra família
em um template personalizado, o diretório precisa conter as definições TCPDF
geradas para todos os estilos utilizados, normalmente arquivos `.php`, `.z` e
`.ctg.z`.

Para informar um diretório próprio de fontes, injete uma engine configurada:

```php
use A2Insights\JxrmlTemplateEngine\JxrmlRenderer;
use A2Insights\JxrmlTemplateEngine\JxrmlTemplateEngine;
use Danfse\Danfse\Danfse;

$renderer = new JxrmlRenderer(
    fontDirectory: __DIR__.'/fonts',
);

$engine = new JxrmlTemplateEngine($renderer);
$danfse = new Danfse(engine: $engine);

$pdf = $danfse->render($nfse);
```

O valor de `fontName` no JRXML deve corresponder ao nome registrado na
definição TCPDF. Em sistemas Linux, mantenha também o nome dos arquivos em
minúsculas para evitar diferenças entre ambientes.

## Estrutura dos dados

O mapeador aceita o payload nacional dentro de `payload_nfse.infNfse`. Alguns dados complementares do documento são informados no nível principal:

| Campo | Descrição |
| --- | --- |
| `chave` | Chave de acesso da NFS-e |
| `numero_nfse` | Número da NFS-e, usado como alternativa ao valor do payload |
| `consulta_publica_url` | Endereço da consulta pública |
| `prefeitura` | Nome, estado, telefone, e-mail e logo da prefeitura |
| `emitente_municipio` | Município do prestador |
| `tomador_municipio` | Município do tomador |
| `local_prestacao` | Local da prestação do serviço |
| `local_incidencia` | Local de incidência do ISSQN |
| `payload_nfse` | Conteúdo da NFS-e Nacional |

Arrays, objetos, propriedades públicas e objetos que exponham os dados por métodos são aceitos pelo mapeador.

## Personalização

É possível fornecer outro template JRXML no construtor ou apenas para uma renderização:

```php
$danfse = new Danfse(template: '/caminho/template.jrxml');

$pdf = $danfse->render($nfse);

// Ou somente nesta chamada:
$pdf = $danfse->render($nfse, template: '/caminho/outro-template.jrxml');
```

Para alterar o mapeamento, estenda `NfseTemplateMapper` e sobrescreva `mapParameter()`:

```php
use Danfse\Danfse\Mappers\NfseTemplateMapper;

$mapper = new class extends NfseTemplateMapper
{
    protected function mapParameter(string $name, mixed $value, mixed $source, array $config): mixed
    {
        return $name === 'nNFSe' ? 'NFSe '.$value : $value;
    }
};

$pdf = (new Danfse)->render($nfse, mapper: $mapper);
```

## Desenvolvimento

```bash
composer test
composer analyse
composer format
```

## Changelog

Consulte o [CHANGELOG](CHANGELOG.md) para acompanhar as alterações.

## Créditos

- [a21ns1g4ts](https://github.com/a21ns1g4ts)
- [Todos os contribuidores](../../contributors)

## Licença

Distribuído sob a licença MIT. Consulte o arquivo [LICENSE](LICENSE.md).
