# Exemplo de geração de PDF

O arquivo [`generate-pdf.php`](generate-pdf.php) contém um payload completo de exemplo e gera uma DANFSe usando o template incluído no pacote.

Na raiz do projeto, instale as dependências:

```bash
composer install
```

Em seguida, execute:

```bash
php examples/generate-pdf.php
```

O arquivo será criado em:

```text
examples/output/danfse-exemplo.pdf
```

Também é possível informar outro destino:

```bash
php examples/generate-pdf.php /tmp/minha-danfse.pdf
```

O script pode ser usado como referência para:

- estruturar os dados da NFS-e Nacional;
- informar os dados da prefeitura, prestador e tomador;
- renderizar o documento com `Danfse::render()`;
- salvar o conteúdo binário retornado em um arquivo PDF.

Voltar para a [documentação principal](../README.md).
