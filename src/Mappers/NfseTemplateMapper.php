<?php

declare(strict_types=1);

namespace Danfse\Danfse\Mappers;

use A2Insights\JxrmlTemplateEngine\Contracts\TemplateDataMapper;
use ArrayAccess;
use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

class NfseTemplateMapper implements TemplateDataMapper
{
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
        $localPrestacao = $this->array($servico['localPrestacao'] ?? []);
        $comercioExterior = $this->array($servico['comercioExterior'] ?? []);
        $regime = $this->array($prestador['regimeTributario'] ?? []);
        $valoresDps = $this->array($dps['valores'] ?? []);
        $servicoPrestado = $this->array($valoresDps['valorServicoPrestado'] ?? []);
        $deducaoReducao = $this->array($valoresDps['deducaoReducao'] ?? []);
        $desconto = $this->array($valoresDps['desconto'] ?? []);
        $tributacao = $this->array($valoresDps['tributacao'] ?? []);
        $valores = $this->array($infNfse['valores'] ?? []);

        $parameters = [
            'PREFEITURA_NOME' => $this->string($this->value($source, 'prefeitura.nome', '')),
            'PREFEITURA_ESTADO' => $this->string($this->value($source, 'prefeitura.estado', '')),
            'PREFEITURA_TELEFONE' => $this->string($this->value($source, 'prefeitura.telefone', '')),
            'PREFEITURA_EMAIL' => $this->string($this->value($source, 'prefeitura.email', '')),
            'cdChave' => $this->digits($this->value($source, 'chave', $infNfse['chaveAcesso'] ?? '')),
            'LINK_CONSULTA_PUBLICA' => $this->string($this->value(
                $source,
                'consulta_publica_url',
                $this->value($source, 'consultaPublicaUrl', ''),
            )),
            'nNFSe' => $this->string($infNfse['numeroNfse'] ?? $this->value($source, 'numero_nfse', '')),
            'dCompet' => $this->date($dps['dataCompetencia'] ?? null),
            'dhEmi' => $this->dateTime($dps['dataEmissao'] ?? $infNfse['dataProcessamento'] ?? null),
            'nDPS' => $this->string($dps['numeroDps'] ?? ''),
            'serie' => $this->string($dps['serie'] ?? ''),
            'emitCNPJ' => $this->cpfCnpj($emitente['cnpj'] ?? $emitente['cpf'] ?? ''),
            'emitxNome' => $this->string($emitente['nome'] ?? $prestador['nome'] ?? ''),
            'emitIM' => $this->string($emitente['inscricaoMunicipal'] ?? $prestador['inscricaoMunicipal'] ?? ''),
            'emitfone' => $this->string($emitente['telefone'] ?? $prestador['telefone'] ?? ''),
            'emitemail' => $this->string($emitente['email'] ?? $prestador['email'] ?? ''),
            'emitxLgr' => $this->address($this->array($emitente['endereco'] ?? $prestador['endereco'] ?? [])),
            'emitxMun' => $this->string($this->value($source, 'emitente_municipio', $infNfse['localEmissao'] ?? '')),
            'emitCEP' => $this->string($this->value($emitente, 'endereco.cep', $this->value($prestador, 'endereco.cep', ''))),
            'tomaCNPJ' => $this->cpfCnpj($tomador['cnpj'] ?? $tomador['cpf'] ?? ''),
            'tomaCPF' => $this->cpfCnpj($tomador['cpf'] ?? ''),
            'tomaxNome' => $this->string($tomador['nome'] ?? ''),
            'tomaIM' => $this->string($tomador['inscricaoMunicipal'] ?? ''),
            'tomafone' => $this->string($tomador['telefone'] ?? ''),
            'tomaemail' => $this->string($tomador['email'] ?? ''),
            'tomaxLgr' => $this->address($this->array($tomador['endereco'] ?? [])),
            'tomaxMun' => $this->string($this->value($source, 'tomador_municipio', '')),
            'tomaCEP' => $this->string($this->value($tomador, 'endereco.cep', '')),
            'xDescServ' => $this->string($codigoServico['descricaoServico'] ?? ''),
            'cTribNac' => $this->string($codigoServico['codigoTributacaoNacional'] ?? ''),
            'cTribMun' => $this->string($codigoServico['codigoTributacaoMunicipal'] ?? ''),
            'cPaisPrestacao' => $this->string($localPrestacao['codigoPaisPrestacao'] ?? ''),
            'xLocPrestacao' => $this->string($this->value($source, 'local_prestacao', $infNfse['localPrestacao'] ?? '')),
            'xLocIncid' => $this->string($this->value($source, 'local_incidencia', $infNfse['nomeLocalIncidencia'] ?? '')),
            'xNBS' => $this->string($infNfse['descricaoNbs'] ?? $codigoServico['codigoNbs'] ?? ''),
            'vServ' => $this->money($servicoPrestado['valorServico'] ?? null),
            'vLiq' => $this->money($valores['valorLiquido'] ?? null),
            'vBC' => $this->money($valores['baseCalculo'] ?? null),
            'pAliq' => $this->money($valores['aliquotaAplicada'] ?? $tributacao['aliquota'] ?? null),
            'vISSQN' => $this->money($valores['valorIssqn'] ?? null),
            'vTotTribFed' => $this->money($tributacao['valorTotalTributosFederais'] ?? $this->value($source, 'valor_tributos_federais')),
            'vTotTribEst' => $this->money($tributacao['valorTotalTributosEstaduais'] ?? $this->value($source, 'valor_tributos_estaduais')),
            'vTotTribMun' => $this->money($tributacao['valorTotalTributosMunicipais'] ?? $this->value($source, 'valor_tributos_municipais')),
            'vCalcDR' => $this->money($valores['valorCalculadoDeducaoReducao'] ?? $deducaoReducao['valorDeducaoReducao'] ?? null),
            'vCalcBM' => $this->money($valores['valorCalculadoBeneficioMunicipal'] ?? null),
            'vDescIncond' => $this->money($desconto['valorDescontoIncondicionado'] ?? $this->value($source, 'valor_desconto_incondicionado')),
            'vDescCond' => $this->money($desconto['valorDescontoCondicionado'] ?? $this->value($source, 'valor_desconto_condicionado')),
            'vTotalRet' => $this->money($valores['valorTotalRetido'] ?? null),
            'vPis' => $this->money($tributacao['valorPis'] ?? null),
            'vCofins' => $this->money($tributacao['valorCofins'] ?? null),
            'vRetCP' => $this->money($tributacao['valorRetidoContribuicaoPrevidenciaria'] ?? 0),
            'vRetCSLL' => $this->money($tributacao['valorRetidoCsll'] ?? null),
            'vRetIRRF' => $this->money($tributacao['valorRetidoIrrf'] ?? null),
            'vRetPisCofins' => $this->money($tributacao['valorRetidoPisCofins'] ?? null),
            'opSimpNac' => $this->string($regime['opcaoSimplesNacional'] ?? ''),
            'regApTribSN' => $this->string($regime['regimeApuracaoTributosSn'] ?? ''),
            'regEspTrib' => $this->string($regime['regimeEspecialTributacao'] ?? ''),
            'tribISSQN' => $this->string($tributacao['tributacaoIssqn'] ?? ''),
            'tpImunidade' => $this->string($tributacao['tipoImunidade'] ?? ''),
            'tpSusp' => $this->string($tributacao['tipoSuspensao'] ?? ''),
            'tpRetISSQN' => $this->string($tributacao['tipoRetencaoIssqn'] ?? ''),
            'tpRetPisCofins' => $this->string($tributacao['tipoRetencaoPisCofins'] ?? ''),
            'tpBM' => $this->string($valores['tipoBeneficioMunicipal'] ?? ''),
            'nProcesso' => $this->string($tributacao['numeroProcessoSuspensao'] ?? ''),
            'cPaisResult' => $this->string($comercioExterior['codigoPaisResultado'] ?? ''),
            'xRetCP' => '',
            'infCompl' => $this->string($infNfse['outrasInformacoes'] ?? $this->value($servico, 'informacaoComplemento.informacoesComplementares', '')),
            'imgNfse' => $this->string($this->value($source, 'imagem_nfse', $this->templateAsset('nfse-nacional.png'))),
            'imgPrefeitura' => $this->string($this->value($source, 'prefeitura.logo', $this->templateAsset('prefeitura.png'))),
            'prefeituraFone' => $this->string($this->value($source, 'prefeitura.telefone', '')),
            'prefeituraEmail' => $this->string($this->value($source, 'prefeitura.email', '')),
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

        return is_scalar($value) ? (string) $value : '';
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', $this->string($value)) ?? '';
    }

    private function money(mixed $value): string
    {
        return $value === null || $value === '' ? '' : number_format((float) $value, 2, ',', '.');
    }

    private function cpfCnpj(mixed $value): string
    {
        $digits = $this->digits($value);

        return match (strlen($digits)) {
            11 => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits) ?? $digits,
            14 => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits) ?? $digits,
            default => $digits,
        };
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function address(array $address): string
    {
        return implode(', ', array_filter([
            $this->string($address['logradouro'] ?? ''),
            $this->string($address['numero'] ?? ''),
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
            return '';
        }

        try {
            return (new DateTimeImmutable($value))->format($format);
        } catch (Throwable) {
            return $value;
        }
    }

    private function templateAsset(string $filename): string
    {
        return dirname(__DIR__).'/Templates/'.$filename;
    }
}
