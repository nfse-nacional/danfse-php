<?php

declare(strict_types=1);

namespace Danfse\Danfse\Mappers;

use A2Insights\JxrmlTemplateEngine\Contracts\TemplateDataMapper;
use ArrayAccess;
use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Converte a NFS-e (payload nacional ou NfseData do nfse-php) nos parâmetros do
 * DANFSe v2.0 da NT 008/2026. Todo valor sai pronto para impressão: moeda com
 * "R$", percentual com "%", códigos traduzidos e "-" no lugar de campo vazio.
 */
class NfseTemplateMapper implements TemplateDataMapper
{
    /** Campo vazio no DANFSe oficial aparece como traço. */
    public const EMPTY = '-';

    /** Endereço exigido pela NT 008 (item 2.4.3) para o QR Code. */
    public const PUBLIC_CONSULTATION_URL = 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=';

    private const UF_BY_IBGE = [
        '11' => 'RO', '12' => 'AC', '13' => 'AM', '14' => 'RR', '15' => 'PA', '16' => 'AP', '17' => 'TO',
        '21' => 'MA', '22' => 'PI', '23' => 'CE', '24' => 'RN', '25' => 'PB', '26' => 'PE', '27' => 'AL',
        '28' => 'SE', '29' => 'BA', '31' => 'MG', '32' => 'ES', '33' => 'RJ', '35' => 'SP', '41' => 'PR',
        '42' => 'SC', '43' => 'RS', '50' => 'MS', '51' => 'MT', '52' => 'GO', '53' => 'DF',
    ];

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function parameters(mixed $source, array $config): array
    {
        $payload = $this->array($this->value($source, 'payload_nfse', $source));
        $infNfse = $this->array($payload['infNfse'] ?? $payload);
        $dps = $this->array($this->value($infNfse, 'dps.infDps', []));
        $emitente = $this->array($infNfse['emitente'] ?? []);
        $prestador = $this->array($dps['prestador'] ?? []);
        $tomador = $this->array($dps['tomador'] ?? []);
        $servico = $this->array($dps['servico'] ?? []);
        $codigoServico = $this->array($servico['codigoServico'] ?? []);
        $regime = $this->array($prestador['regimeTributario'] ?? []);
        $valoresDps = $this->array($dps['valores'] ?? []);
        $servicoPrestado = $this->array($valoresDps['valorServicoPrestado'] ?? []);
        $desconto = $this->array($valoresDps['desconto'] ?? []);
        $tributacao = $this->array($valoresDps['tributacao'] ?? []);
        $valores = $this->array($infNfse['valores'] ?? []);
        $ibsCbsDps = $this->array($dps['ibscbs'] ?? []);
        $ibsCbs = $this->array($infNfse['ibscbs'] ?? []);
        $emitenteEndereco = $this->array($emitente['endereco'] ?? $prestador['endereco'] ?? []);
        $tomadorEndereco = $this->array($tomador['endereco'] ?? []);
        $municipioCodigo = $this->digits($this->value(
            $source,
            'municipio_codigo',
            $this->value(
                $source,
                'municipioCodigo',
                $dps['codigoLocalEmissao'] ?? $emitenteEndereco['codigoMunicipio'] ?? '',
            ),
        ));
        $chave = $this->accessKey($source, $payload, $infNfse);
        $tipoAmbiente = $this->string($dps['tipoAmbiente'] ?? $this->value($source, 'tipo_ambiente', ''));
        $opSimpNac = $this->string($regime['opcaoSimplesNacional'] ?? '');

        $vLiq = $this->float($valores['valorLiquido'] ?? null);
        $vIbs = $this->float($ibsCbs['valorTotalIbs'] ?? null);
        $vCbs = $this->float($ibsCbs['valorCbs'] ?? null);
        $vIbsCbs = ($vIbs ?? 0.0) + ($vCbs ?? 0.0);
        $vTotalNota = $this->float($ibsCbs['valorTotalNota'] ?? null)
            ?? ($vLiq === null ? null : $vLiq + $vIbsCbs);

        $parameters = [
            // Cabeçalho (2.1.1)
            'imgNfse' => $this->string($this->value($source, 'imagem_nfse', $this->templateAsset('nfse-nacional.png'))),
            'municipioCodigo' => $municipioCodigo,
            'municipioEmissao' => $this->issuingMunicipality($source, $infNfse, $codigoServico, $emitenteEndereco),
            'ambGer' => 'Ambiente Gerador: '.$this->label(['1' => 'Prefeitura', '2' => 'Sistema Nacional NFS-e'], $infNfse['ambienteGerador'] ?? ''),
            'tpAmb' => 'Tipo de Ambiente: '.$this->label(['1' => 'Produção', '2' => 'Homologação'], $tipoAmbiente),
            'homologacao' => $tipoAmbiente === '2' ? 'true' : 'false',

            // Identificação da NFS-e (2.1.2)
            'cdChave' => $this->orEmpty($chave),
            'linkPublico' => $this->publicConsultationUrl($source, $chave),
            'nNFSe' => $this->orEmpty($infNfse['numeroNfse'] ?? $this->value($source, 'numero_nfse', '')),
            'dCompet' => $this->date($dps['dataCompetencia'] ?? null),
            'dhProc' => $this->dateTime($infNfse['dataProcessamento'] ?? $dps['dataEmissao'] ?? null),
            'nDPS' => $this->orEmpty($dps['numeroDps'] ?? ''),
            'serie' => $this->orEmpty($dps['serie'] ?? ''),
            'dhEmi' => $this->dateTime($dps['dataEmissao'] ?? null),
            'tpEmit' => $this->label(['1' => 'Prestador', '2' => 'Tomador', '3' => 'Intermediário'], $dps['tipoEmitente'] ?? '1'),
            'situacao' => $this->label([
                '100' => 'NFS-e Gerada',
                '101' => 'NFS-e de Substituição Gerada',
                '102' => 'NFS-e de Decisão Judicial',
                '103' => 'NFS-e Avulsa',
                '107' => 'NFS-e MEI',
            ], $infNfse['codigoStatus'] ?? $this->value($source, 'situacao', '')),
            'finNFSe' => $this->label(['0' => 'NFS-e Regular'], $dps['finalidadeNfse'] ?? $ibsCbsDps['finalidadeNfse'] ?? ''),

            // Prestador / Fornecedor (2.1.3)
            'emitCNPJ' => $this->orEmpty($this->cpfCnpj($emitente['cnpj'] ?? $emitente['cpf'] ?? $prestador['cnpj'] ?? $prestador['cpf'] ?? $prestador['nif'] ?? '')),
            'emitIM' => $this->orEmpty($emitente['inscricaoMunicipal'] ?? $prestador['inscricaoMunicipal'] ?? ''),
            'emitfone' => $this->orEmpty($this->phone($emitente['telefone'] ?? $prestador['telefone'] ?? '')),
            'emitxNome' => $this->orEmpty($emitente['nome'] ?? $prestador['nome'] ?? ''),
            'emitxMun' => $this->orEmpty($this->cityWithState(
                $this->value($source, 'emitente_municipio', $emitenteEndereco['municipio'] ?? $infNfse['localEmissao'] ?? ''),
                $emitenteEndereco['uf'] ?? '',
                $emitenteEndereco['codigoMunicipio'] ?? $municipioCodigo,
            )),
            'emitIbgeCep' => $this->ibgeCep($emitenteEndereco['codigoMunicipio'] ?? $municipioCodigo, $emitenteEndereco['cep'] ?? ''),
            'emitxLgr' => $this->orEmpty($this->address($emitenteEndereco)),
            'emitemail' => $this->orEmpty($emitente['email'] ?? $prestador['email'] ?? ''),
            'opSimpNac' => $this->label([
                '1' => 'Não Optante',
                '2' => 'Optante - Microempreendedor Individual (MEI)',
                '3' => 'Optante - Microempresa ou Empresa de Pequeno Porte (ME/EPP)',
            ], $opSimpNac),
            'regApTribSN' => $opSimpNac === '3' ? $this->label([
                '1' => 'Regime de apuração dos tributos federais e municipal pelo Simples Nacional',
                '2' => 'Regime de apuração dos tributos federais pelo Simples Nacional e ISSQN por fora, conforme a legislação municipal',
                '3' => 'Regime de apuração dos tributos federais e municipal por fora do Simples Nacional',
            ], $regime['regimeApuracaoTributosSn'] ?? '') : self::EMPTY,

            // Tomador / Adquirente (2.1.4)
            'tomaCNPJ' => $this->orEmpty($this->cpfCnpj($tomador['cnpj'] ?? $tomador['cpf'] ?? $tomador['nif'] ?? '')),
            'tomaIM' => $this->orEmpty($tomador['inscricaoMunicipal'] ?? ''),
            'tomafone' => $this->orEmpty($this->phone($tomador['telefone'] ?? '')),
            'tomaxNome' => $this->orEmpty($tomador['nome'] ?? ''),
            'tomaxMun' => $this->orEmpty($this->cityWithState(
                $this->value($source, 'tomador_municipio', $tomadorEndereco['municipio'] ?? ''),
                $tomadorEndereco['uf'] ?? '',
                $tomadorEndereco['codigoMunicipio'] ?? '',
            )),
            'tomaIbgeCep' => $this->ibgeCep($tomadorEndereco['codigoMunicipio'] ?? '', $tomadorEndereco['cep'] ?? ''),
            'tomaxLgr' => $this->orEmpty($this->address($tomadorEndereco)),
            'tomaemail' => $this->orEmpty($tomador['email'] ?? ''),

            // Serviço prestado (2.1.7)
            'cTrib' => $this->serviceCode($codigoServico['codigoTributacaoNacional'] ?? '').' / '.$this->orEmpty($codigoServico['codigoTributacaoMunicipal'] ?? ''),
            'xNBS' => $this->orEmpty($infNfse['descricaoNbs'] ?? $codigoServico['codigoNbs'] ?? ''),
            'xLocPrestacao' => $this->orEmpty($this->value($source, 'local_prestacao', $infNfse['localPrestacao'] ?? '')),
            'xTribNac' => $this->string($infNfse['descricaoTributacaoNacional'] ?? ''),
            'xDescServ' => $this->string($codigoServico['descricaoServico'] ?? ''),

            // Tributação municipal - ISSQN (2.1.8)
            'tribISSQN' => $this->label([
                '1' => 'Operação Tributável',
                '2' => 'Imunidade',
                '3' => 'Exportação de Serviço',
                '4' => 'Não Incidência',
            ], $tributacao['tributacaoIssqn'] ?? ''),
            'xLocIncid' => $this->orEmpty($this->value($source, 'local_incidencia', $infNfse['nomeLocalIncidencia'] ?? '')),
            'vBC' => $this->money($valores['baseCalculo'] ?? null),
            'pAliq' => $this->percent($valores['aliquotaAplicada'] ?? $tributacao['aliquota'] ?? null),
            'tpRetISSQN' => $this->label([
                '1' => 'Não Retido',
                '2' => 'Retido pelo Tomador',
                '3' => 'Retido pelo Intermediário',
            ], $tributacao['tipoRetencaoIssqn'] ?? ''),
            'vISSQN' => $this->money($valores['valorIssqn'] ?? null),

            // Tributação federal, exceto CBS (2.1.9)
            'vRetIRRF' => $this->money($tributacao['valorRetidoIrrf'] ?? null),
            'vRetCP' => $this->money($tributacao['valorRetidoContribuicaoPrevidenciaria'] ?? null),
            'vRetCSLL' => $this->money($tributacao['valorRetidoCsll'] ?? null),
            'vPis' => $this->money($tributacao['valorPis'] ?? null),
            'vCofins' => $this->money($tributacao['valorCofins'] ?? null),
            'tpRetPisCofins' => $this->label([
                '0' => 'PIS/COFINS/CSLL Não Retidos',
                '1' => 'PIS/COFINS Retido',
                '2' => 'PIS/COFINS Não Retido',
                '3' => 'PIS/COFINS/CSLL Retidos',
                '4' => 'PIS/COFINS Retidos, CSLL Não Retido',
                '5' => 'PIS Retido, COFINS/CSLL Não Retido',
                '6' => 'COFINS Retido, PIS/CSLL Não Retido',
                '7' => 'PIS Não Retido, COFINS/CSLL Retidos',
                '8' => 'PIS/COFINS Não Retidos, CSLL Retido',
                '9' => 'COFINS Não Retido, PIS/CSLL Retidos',
            ], $tributacao['tipoRetencaoPisCofins'] ?? ''),

            // Tributação IBS/CBS (2.1.10)
            'ibsCbsCst' => $this->orEmpty($ibsCbsDps['cst'] ?? '').' / '.$this->orEmpty($ibsCbsDps['codigoClassificacaoTributaria'] ?? ''),
            'ibsCbsIncid' => implode(' / ', [
                $this->orEmpty($ibsCbsDps['codigoIndicadorOperacao'] ?? ''),
                $this->orEmpty($this->ibge($ibsCbs['codigoLocalidadeIncidencia'] ?? '')),
                $this->orEmpty($ibsCbs['nomeLocalidadeIncidencia'] ?? ''),
                $this->orEmpty($this->ufFromIbge($ibsCbs['codigoLocalidadeIncidencia'] ?? '')),
            ]),
            'ibsCbsExclRed' => $this->money($ibsCbs['valorCalculadoReembolso'] ?? 0),
            'ibsCbsBC' => $this->money($ibsCbs['baseCalculo'] ?? null),
            'ibsCbsRedAliq' => $this->percent($ibsCbs['percentualReducaoAliquotaUf'] ?? null).' / '.$this->percent($ibsCbs['percentualReducaoAliquotaCbs'] ?? null),
            'ibsAliq' => $this->percent($ibsCbs['aliquotaIbsUf'] ?? null).' / '.$this->percent($ibsCbs['aliquotaIbsMunicipal'] ?? null),
            'ibsMunAliqEfet' => $this->percent($ibsCbs['aliquotaEfetivaMunicipal'] ?? null),
            'vIBSMun' => $this->money($ibsCbs['valorIbsMunicipal'] ?? null),
            'ibsUfAliqEfet' => $this->percent($ibsCbs['aliquotaEfetivaUf'] ?? null),
            'vIBSUF' => $this->money($ibsCbs['valorIbsUf'] ?? null),
            'vIBSTot' => $this->money($vIbs),
            'cbsAliq' => $this->percent($ibsCbs['aliquotaCbs'] ?? null),
            'cbsAliqEfet' => $this->percent($ibsCbs['aliquotaEfetivaCbs'] ?? null),
            'vCBS' => $this->money($vCbs),

            // Valor total da NFS-e (2.1.11)
            'vServ' => $this->money($servicoPrestado['valorServico'] ?? null),
            'vDescIncond' => $this->money($desconto['valorDescontoIncondicionado'] ?? $this->value($source, 'valor_desconto_incondicionado')),
            'vDescCond' => $this->money($desconto['valorDescontoCondicionado'] ?? $this->value($source, 'valor_desconto_condicionado')),
            'vTotalRet' => $this->money($valores['valorTotalRetido'] ?? null),
            'vLiq' => $this->money($vLiq),
            'vTotIbsCbs' => $this->money($vIbsCbs),
            'vTotNF' => $this->money($vTotalNota),

            // Informações complementares (2.1.12)
            'infCompl' => $this->complementaryInformation($tributacao, $this->string(
                $infNfse['outrasInformacoes'] ?? $this->value($servico, 'informacaoComplemento.informacoesComplementares', ''),
            )),

            // Canhoto (2.1.13)
            'nNFSeChave' => $this->orEmpty($infNfse['numeroNfse'] ?? '').' / '.$this->orEmpty($chave),
        ];

        foreach ($parameters as $name => $value) {
            $parameters[$name] = $this->mapParameter($name, $value, $source, $config);
        }

        return $parameters;
    }

    public function rows(mixed $source, array $config): iterable
    {
        return [['id' => 1]];
    }

    /**
     * Override to customize one variable without replacing the complete NFSe mapping.
     *
     * @param  array<string, mixed>  $config
     */
    protected function mapParameter(string $name, mixed $value, mixed $source, array $config): mixed
    {
        return $value;
    }

    protected function value(mixed $source, string $path, mixed $default = null): mixed
    {
        $value = $source;

        foreach (explode('.', $path) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } elseif ($value instanceof ArrayAccess && $value->offsetExists($segment)) {
                $value = $value[$segment];
            } elseif (is_object($value) && isset($value->{$segment})) {
                $value = $value->{$segment};
            } elseif (is_object($value) && method_exists($value, $segment)) {
                $value = $value->{$segment}();
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * "Totais aproximados dos tributos" (Lei 12.741/2012) no texto da nota 10 do
     * item 2.4.5 da NT 008, seguido das outras informações da NFS-e.
     *
     * @param  array<string, mixed>  $tributacao
     */
    private function complementaryInformation(array $tributacao, string $outrasInformacoes): string
    {
        $base = 'Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: ';
        $fed = $tributacao['valorTotalTributosFederais'] ?? null;
        $est = $tributacao['valorTotalTributosEstaduais'] ?? null;
        $mun = $tributacao['valorTotalTributosMunicipais'] ?? null;
        $pFed = $tributacao['percentualTotalTributosFederais'] ?? null;
        $pEst = $tributacao['percentualTotalTributosEstaduais'] ?? null;
        $pMun = $tributacao['percentualTotalTributosMunicipais'] ?? null;
        $sn = $tributacao['percentualTotalTributosSN'] ?? null;

        $text = match (true) {
            $fed !== null || $est !== null || $mun !== null => $base.'Federais: '.$this->money($fed).'; Estaduais: '.$this->money($est).'; Municipais: '.$this->money($mun).';',
            $pFed !== null || $pEst !== null || $pMun !== null => $base.'Federais: '.$this->percent($pFed).'; Estaduais: '.$this->percent($pEst).'; Municipais: '.$this->percent($pMun).';',
            // O percentual do Simples não se divide entre as esferas: sai com traços e a alíquota logo depois.
            $sn !== null => $base.'Federais: -; Estaduais: -; Municipais: -; Alíquota do Simples Nacional: '.$this->percent($sn).'.',
            default => $base.'Federais: -; Estaduais: -; Municipais: -;',
        };

        return $outrasInformacoes === '' ? $text : $text."\n".$outrasInformacoes;
    }

    /**
     * @return array<string, mixed>
     */
    private function array(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return is_object($value) ? get_object_vars($value) : [];
    }

    private function string(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function orEmpty(mixed $value): string
    {
        $value = $this->string($value);

        return $value === '' ? self::EMPTY : $value;
    }

    /**
     * @param  array<int|string, string>  $labels
     */
    private function label(array $labels, mixed $code): string
    {
        $code = $this->string($code);

        return $labels[$code] ?? $this->orEmpty($code);
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', $this->string($value)) ?? '';
    }

    private function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function money(mixed $value): string
    {
        $value = $this->float($value);

        return $value === null ? self::EMPTY : 'R$ '.number_format($value, 2, ',', '.');
    }

    private function percent(mixed $value): string
    {
        $value = $this->float($value);

        return $value === null ? self::EMPTY : number_format($value, 2, ',', '.').'%';
    }

    private function cpfCnpj(mixed $value): string
    {
        $digits = $this->digits($value);

        return match (strlen($digits)) {
            11 => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits) ?? $digits,
            14 => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits) ?? $digits,
            default => $this->string($value),
        };
    }

    private function phone(mixed $value): string
    {
        $digits = $this->digits($value);

        return match (strlen($digits)) {
            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits) ?? $digits,
            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits) ?? $digits,
            default => $this->string($value),
        };
    }

    /** O código de tributação nacional é impresso pontuado: 010801 vira 01.08.01. */
    private function serviceCode(mixed $value): string
    {
        $digits = $this->digits($value);

        if (strlen($digits) !== 6) {
            return $this->orEmpty($value);
        }

        return substr($digits, 0, 2).'.'.substr($digits, 2, 2).'.'.substr($digits, 4, 2);
    }

    /** O DANFSe imprime o IBGE pontuado: 3550308 vira 35.50308. */
    private function ibge(mixed $value): string
    {
        $digits = $this->digits($value);

        return strlen($digits) === 7 ? substr($digits, 0, 2).'.'.substr($digits, 2) : $digits;
    }

    private function ufFromIbge(mixed $value): string
    {
        return self::UF_BY_IBGE[substr($this->digits($value), 0, 2)] ?? '';
    }

    private function ibgeCep(mixed $ibge, mixed $cep): string
    {
        $cep = $this->digits($cep);
        $cep = strlen($cep) === 8 ? substr($cep, 0, 2).'.'.substr($cep, 2, 3).'-'.substr($cep, 5) : $cep;

        return $this->orEmpty(trim($this->ibge($ibge).' / '.$cep, ' /'));
    }

    private function cityWithState(mixed $city, mixed $state, mixed $ibge): string
    {
        $city = $this->string($city);
        $state = $this->string($state) ?: $this->ufFromIbge($ibge);

        if ($city === '' || $state === '' || preg_match('/\b'.preg_quote($state, '/').'\s*$/i', $city)) {
            return $city;
        }

        return "{$city} / {$state}";
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function address(array $address): string
    {
        $street = trim($this->string($address['logradouro'] ?? '').', '.$this->string($address['numero'] ?? ''), ' ,');

        return implode(', ', array_filter([
            $street,
            $this->string($address['complemento'] ?? ''),
            $this->string($address['bairro'] ?? ''),
        ], static fn (string $part): bool => $part !== ''));
    }

    private function date(mixed $value): string
    {
        return $this->formattedDate($value, 'd/m/Y');
    }

    private function dateTime(mixed $value): string
    {
        return $this->formattedDate($value, 'd/m/Y H:i:s');
    }

    private function formattedDate(mixed $value, string $format): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($format);
        }

        if (! is_string($value) || $value === '') {
            return self::EMPTY;
        }

        try {
            return (new DateTimeImmutable($value))->format($format);
        } catch (Throwable) {
            return $value;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $infNfse
     */
    private function accessKey(mixed $source, array $payload, array $infNfse): string
    {
        $key = $this->value(
            $source,
            'chave',
            $payload['chaveAcesso'] ?? $infNfse['chaveAcesso'] ?? $infNfse['id'] ?? '',
        );

        return $this->digits($key);
    }

    private function publicConsultationUrl(mixed $source, string $accessKey): string
    {
        $url = $this->string($this->value(
            $source,
            'linkPublico',
            $this->value(
                $source,
                'consulta_publica_url',
                $this->value($source, 'consultaPublicaUrl', ''),
            ),
        ));

        if ($url !== '' || $accessKey === '') {
            return $url;
        }

        return self::PUBLIC_CONSULTATION_URL.rawurlencode($accessKey);
    }

    /**
     * "Município: CCCC / UF" no canto direito do cabeçalho (item 2.4.3). Fica em
     * branco para códigos de tributação 99, que não pertencem a um município.
     *
     * @param  array<string, mixed>  $infNfse
     * @param  array<string, mixed>  $codigoServico
     * @param  array<string, mixed>  $emitenteEndereco
     */
    private function issuingMunicipality(
        mixed $source,
        array $infNfse,
        array $codigoServico,
        array $emitenteEndereco,
    ): string {
        $override = $this->value(
            $source,
            'municipio_emissao',
            $this->value($source, 'municipioEmissao'),
        );

        if ($override !== null) {
            return $this->string($override);
        }

        if (str_starts_with($this->digits($codigoServico['codigoTributacaoNacional'] ?? ''), '99')) {
            return '';
        }

        $municipio = $this->cityWithState(
            $infNfse['localEmissao'] ?? $emitenteEndereco['municipio'] ?? $this->value($source, 'emitente_municipio', ''),
            $emitenteEndereco['uf'] ?? '',
            $emitenteEndereco['codigoMunicipio'] ?? '',
        );

        return $municipio === '' ? '' : 'Município: '.$municipio;
    }

    private function templateAsset(string $filename): string
    {
        return dirname(__DIR__).'/Templates/'.$filename;
    }
}
