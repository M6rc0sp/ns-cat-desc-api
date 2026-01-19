# Endpoints de Consumo de Descrições

Estes são os endpoints públicos para consumir as descrições de categorias editadas no app.

## 1. Get Description by Category ID

**Endpoint:** `GET /public/descriptions/{categoryId}`

**Descrição:** Retorna a descrição de uma categoria específica

**Exemplo:**
```bash
curl http://localhost:8000/public/descriptions/36162523
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "category_id": "36162523",
    "content": "Descrição em texto plano da categoria",
    "html_content": "{\"root\":{\"children\":[...],\"direction\":null,\"format\":\"\",\"indent\":0,\"type\":\"root\",\"version\":1}}",
    "created_at": "2024-01-14T10:30:00Z",
    "updated_at": "2024-01-14T10:30:00Z"
  },
  "message": "Description retrieved successfully"
}
```

**Response (404 Not Found):**
```json
{
  "success": false,
  "data": null,
  "message": "Description not found for this category",
  "category_id": "36162523"
}
```

---

## 2. Get All Descriptions (Bulk)

**Endpoint:** `GET /public/descriptions`

**Descrição:** Retorna todas as descrições organizadas por category_id

**Exemplo:**
```bash
curl http://localhost:8000/public/descriptions
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "36162523": {
      "id": 1,
      "category_id": "36162523",
      "content": "Descrição da categoria 1",
      "html_content": "{...}",
      "updated_at": "2024-01-14T10:30:00Z"
    },
    "36162524": {
      "id": 2,
      "category_id": "36162524",
      "content": "Descrição da categoria 2",
      "html_content": "{...}",
      "updated_at": "2024-01-14T10:35:00Z"
    }
  },
  "total": 2,
  "message": "All descriptions retrieved successfully"
}
```

---

## Exemplos de Uso

### JavaScript/Frontend

```javascript
// Consumir descrição de uma categoria
async function getDescription(categoryId) {
  try {
    const response = await fetch(`http://localhost:8000/public/descriptions/${categoryId}`);
    const data = await response.json();
    
    if (data.success) {
      console.log('Descrição:', data.data.content);
      return data.data;
    } else {
      console.log('Categoria não tem descrição editada');
    }
  } catch (error) {
    console.error('Erro ao buscar descrição:', error);
  }
}

// Usar
getDescription('36162523');
```

### React Component

```jsx
import { useEffect, useState } from 'react';

export function CategoryDescription({ categoryId }) {
  const [description, setDescription] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`http://localhost:8000/public/descriptions/${categoryId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setDescription(data.data);
        }
        setLoading(false);
      })
      .catch(error => {
        console.error('Erro:', error);
        setLoading(false);
      });
  }, [categoryId]);

  if (loading) return <div>Carregando...</div>;
  if (!description) return <div>Sem descrição customizada</div>;

  return (
    <div>
      <p>{description.content}</p>
    </div>
  );
}
```

### Node.js/Backend

```javascript
const fetch = require('node-fetch');

async function getCategoryDescription(categoryId) {
  const response = await fetch(
    `http://localhost:8000/public/descriptions/${categoryId}`
  );
  const data = await response.json();
  
  if (data.success) {
    return data.data.content;
  }
  return null;
}
```

---

## Estrutura dos Dados

### Content vs HTML Content

- **`content`**: Texto plano extraído do editor (recomendado para SEO e display simples)
- **`html_content`**: JSON do editor Lexical com formatação completa (para reconstruir o editor ou exibir com estilos)

### Exemplo de uso:

```javascript
// Para exibir simples
<p>{description.content}</p>

// Para exibir com formatação (precisa reconstruir o editor)
// Veja documentação do Lexical: https://lexical.dev/
```

---

## CORS

Os endpoints públicos estão configurados para aceitar requisições de qualquer origem. Se precisar de restrições, configure em `app/Http/Middleware/CorsMiddleware.php`.

