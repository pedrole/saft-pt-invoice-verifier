# Verificador de Assinaturas SAF-T PT

Aplicação **Laravel 11** para verificar as assinaturas digitais (Hash) dos documentos de faturação (Invoices) contidos em ficheiros SAF-T PT, de acordo com a **Portaria 302/2016** e o **Manual de Integração de Software da AT**.

---

## Índice

- [Descrição](#descrição)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Utilização](#utilização)
- [Algoritmo de Verificação](#algoritmo-de-verificação)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Limitações](#limitações)
- [Referências](#referências)

---

## Descrição

O ficheiro SAF-T PT (Standard Audit File for Tax — Portugal) contém, em cada documento de faturação, um campo `Hash` que é uma assinatura digital RSA sobre dados específicos do documento. Esta aplicação permite:

- **Fazer upload** de um ficheiro SAF-T PT (XML) de até 100 MB
- **Verificar a integridade da cadeia de hashes** entre documentos da mesma série
- **Verificar criptograficamente** as assinaturas RSA (SHA-256 e SHA-1) se a chave pública for fornecida
- **Apresentar resultados** detalhados por série documental, com a string de assinatura reconstruída

---

## Requisitos

- **PHP** 8.2 ou superior
- **Composer** 2.x
- Extensão PHP **OpenSSL** (para verificação RSA — normalmente incluída)
- Extensão PHP **SimpleXML** (para parsing XML — normalmente incluída)

---

## Instalação

```bash
# 1. Clonar o repositório
git clone https://github.com/pedrole/saft-pt-invoice-verifier.git
cd saft-pt-invoice-verifier

# 2. Instalar dependências
composer install

# 3. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 4. (Opcional) Ajustar o tamanho máximo de upload em .env
# SAFT_MAX_FILE_SIZE_MB=100

# 5. Iniciar o servidor
php artisan serve
```

Aceda a [http://localhost:8000](http://localhost:8000).

### Configuração para ficheiros grandes (>8MB)

Edite o seu `php.ini` (ou crie um `.htaccess`/`user.ini`) para aumentar os limites de upload:

```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 256M
```

---

## Utilização

### 1. Página de Upload (`GET /`)

- Selecione ou arraste um ficheiro SAF-T PT (XML)
- Opcionalmente, forneça a chave pública RSA do software emissor (ficheiro `.pem` ou `.txt`)
- Clique em **Verificar Assinaturas**

### 2. Página de Resultados (`POST /verify`)

Os resultados são apresentados:
- **Resumo geral**: total de documentos, válidos, inválidos, não verificáveis
- **Informação da empresa**: nome, NIF, período fiscal, certificado de software
- **Tabela por série**: cada documento com estado, data, tipo, total bruto
- **Detalhes expandíveis**: string de assinatura reconstruída, hash Base64

#### Estados dos documentos

| Estado | Cor | Significado |
|--------|-----|-------------|
| ✓ Válido | Verde | Assinatura RSA verificada criptograficamente |
| ✗ Inválido | Vermelho | Assinatura RSA não corresponde à chave pública |
| — Não verificável | Amarelo | 1º documento da série, ou sem chave pública fornecida |

---

## Algoritmo de Verificação

### String de Assinatura

Cada documento de faturação é assinado sobre a seguinte string (campos separados por `;`):

```
InvoiceDate;SystemEntryDate;InvoiceNo;GrossTotal;HashAnterior
```

Exemplo para o documento FT A/2:

```
2024-01-15;2024-01-15T10:30:00;FT A/2;1230.00;Base64HashDoDocumentoAnterior=
```

### Processo de Verificação

1. **Agrupamento por série**: os documentos são agrupados pela parte série do `InvoiceNo` (ex: "A" em "FT A/1")
2. **Ordenação sequencial**: dentro de cada série, os documentos são ordenados pelo número sequencial
3. **1º documento**: marcado como "Não verificável" — quando foi criado, o hash anterior era `""` (vazio), mas não podemos confirmar isso sem a chave privada
4. **Documentos subsequentes**: reconstrói-se a string de assinatura usando o `Hash` do documento anterior
5. **Verificação RSA** (se chave pública fornecida): tenta verificar com SHA-256 e depois SHA-1

### Chave Pública RSA

O software de faturação certificado assina os documentos com a sua chave privada RSA. Para verificar, é necessária a chave pública correspondente, normalmente disponibilizada pelo fabricante do software de faturação.

Sem a chave pública, apenas é possível confirmar que a string de assinatura foi corretamente reconstruída, mas não se a assinatura RSA é válida.

---

## Estrutura do Projeto

```
app/
  DTOs/
    SaftHeader.php          # DTO para o cabeçalho do SAF-T
    InvoiceData.php         # DTO para cada invoice
    VerificationResult.php  # DTO para o resultado de verificação de um documento
    SeriesResult.php        # DTO para o resultado de uma série documental
  Http/
    Controllers/
      SaftVerifierController.php  # Controller principal
  Services/
    SaftParserService.php         # Parsing do XML SAF-T PT
    SaftHashVerifierService.php   # Motor de verificação de hashes RSA
resources/
  views/
    layouts/
      app.blade.php         # Layout base com Tailwind CSS
    saft/
      upload.blade.php      # Página de upload
      results.blade.php     # Página de resultados
routes/
  web.php                   # Rotas (GET / e POST /verify)
config/
  saft.php                  # Configuração (tamanho máximo, namespace)
```

---

## Limitações

1. **1º documento de cada série**: Não é possível verificar a assinatura do primeiro documento de cada série porque, no momento da criação, o hash anterior era uma string vazia (`""`). Não temos forma de confirmar isso sem a chave privada do software emissor.

2. **Chave pública necessária para verificação completa**: Sem a chave pública RSA do software de faturação, não é possível verificar criptograficamente as assinaturas — apenas reconstruir a string de assinatura.

3. **Apenas Invoices**: Esta versão verifica apenas documentos da secção `SalesInvoices`. Outros documentos (pagamentos, movimentos de stock, etc.) não são verificados.

4. **Schema SAF-T PT 1.04_01**: Testado com o namespace `urn:OECD:StandardAuditFile-Tax:PT_1.04_01`.

---

## Referências

- [Portaria n.º 302/2016 de 2 de dezembro](https://dre.pt/pesquisa/-/search/75679183/details/normal) — Aprova as especificações técnicas relativas ao ficheiro SAF-T PT
- [Manual de Integração de Software — Aspetos Específicos](https://info.portaldasfinancas.gov.pt/) — Comunicação de Séries Documentais, AT
- [Schema XSD SAF-T PT 1.04_01](https://info.portaldasfinancas.gov.pt/) — Definição do schema XML
