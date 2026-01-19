# Nuvemshop Category Description API

API REST para gerenciar descrições avançadas de categorias com editor WYSIWYG integrado ao painel da Nuvemshop.

## Funcionalidades

- ✅ Editor WYSIWYG para descrições de categorias
- ✅ Sem limite de caracteres
- ✅ Integração com painel da Nuvemshop
- ✅ Suporte a formatação básica (negrito, itálico, listas, links, HTML)
- ✅ API REST para gerenciamento de descrições
- ✅ Endpoints para CRUD de descrições de categorias

## Requisitos

- PHP 8.2+
- Composer
- MySQL 5.7+ ou SQLite
- Lumen 11+

## Instalação

```bash
# Instalar dependências
composer install

# Configurar arquivo .env
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Executar migrations
php artisan migrate

# Iniciar servidor
php artisan serve
```

## Configuração

Adicione as seguintes variáveis ao arquivo `.env`:

```
APP_NAME="Nuvemshop Category Description API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nuvemshop_descriptions
DB_USERNAME=root
DB_PASSWORD=

NUVEMSHOP_API_KEY=sua-chave-api
NUVEMSHOP_ACCESS_TOKEN=seu-token-de-acesso
```

## Endpoints da API

### GET `/api/v1/descriptions`
Retorna todas as descrições de categorias.

```bash
curl -X GET http://localhost:8000/api/v1/descriptions
```

### POST `/api/v1/descriptions`
Cria uma nova descrição de categoria.

```bash
curl -X POST http://localhost:8000/api/v1/descriptions \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": "cat-123",
    "content": "Descrição em texto plano",
    "html_content": "<p>Descrição em <strong>HTML</strong></p>"
  }'
```

### GET `/api/v1/descriptions/{id}`
Retorna uma descrição específica por ID.

```bash
curl -X GET http://localhost:8000/api/v1/descriptions/1
```

### GET `/api/v1/descriptions/category/{categoryId}`
Retorna a descrição de uma categoria específica.

```bash
curl -X GET http://localhost:8000/api/v1/descriptions/category/cat-123
```

### PUT `/api/v1/descriptions/{id}`
Atualiza uma descrição existente.

```bash
curl -X PUT http://localhost:8000/api/v1/descriptions/1 \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Descrição atualizada",
    "html_content": "<p>Descrição <em>atualizada</em></p>"
  }'
```

### DELETE `/api/v1/descriptions/{id}`
Deleta uma descrição.

```bash
curl -X DELETE http://localhost:8000/api/v1/descriptions/1
```

## Estrutura de Diretórios

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── DescriptionController.php
│   │   └── Middleware/
│   ├── Models/
│   │   └── CategoryDescription.php
│   └── Providers/
├── bootstrap/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── web.php
├── tests/
├── .env.example
├── composer.json
└── README.md
```

## Modelo de Dados

A tabela `category_descriptions` possui os seguintes campos:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | ID da descrição (chave primária) |
| category_id | STRING | ID da categoria (única) |
| content | LONGTEXT | Conteúdo em texto plano |
| html_content | LONGTEXT | Conteúdo em HTML/WYSIWYG |
| created_at | TIMESTAMP | Data de criação |
| updated_at | TIMESTAMP | Data de atualização |

## Respostas da API

### Sucesso (200)
```json
{
  "id": 1,
  "category_id": "cat-123",
  "content": "Descrição em texto plano",
  "html_content": "<p>Descrição em <strong>HTML</strong></p>",
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-15T10:30:00Z"
}
```

### Erro (404)
```json
{
  "message": "Description not found"
}
```

### Erro (409)
```json
{
  "message": "Description already exists for this category"
}
```

## Testes

```bash
php artisan test
```

## Autor

Nuvemshop

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
