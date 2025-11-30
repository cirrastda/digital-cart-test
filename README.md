# Digital Cart

API RESTful para sistema de carrinho digital com gestão de saldo, saques, depósitos e transferências.

## 📋 Pré-requisitos

- Docker e Docker Compose instalados
- Portas disponíveis: `8080` (nginx)

## Iniciar a aplicação 

```bash
docker compose up -d
```

Isso irá:
- Construir as imagens Docker
- Criar e iniciar os containers (PHP, Nginx)
- Executar as migrations automaticamente
- Deixar a API disponível em `http://localhost:8080`

## Parar a aplicação
```bash
docker compose down
```
IMPORTANTE:
- A primeira vez que a aplicação é iniciada, as migrations serão executadas automaticamente.
- Se você precisar reiniciar a aplicação, use `docker compose down` e `docker compose up -d` novamente.

## Utilização da Aplicação

### Endpoints da API

A API estará disponível em: `http://localhost:8080/`

#### Autenticação
- Exceto `POST /users`, todos os endpoints exigem autenticação via Sanctum.
- Envie o cabeçalho `Authorization: Bearer <token>`.
- O `<token>` é retornado na criação de usuário (`POST /users`).

#### Usuários
- `POST /users` — Criar usuário
  - Não requer autenticação
  - Corpo (`application/json`): `{ "name": "Seu Nome", "email": "email@exemplo.com", "password": "senha12345" }`
  - Respostas: `201` sucesso (retorna `user` e `token`), `422` validação, `500` erro interno
  - Regras de negócio:
    - `name`: obrigatório, texto, máx. 255
    - `email`: obrigatório, formato válido, único
    - `password`: obrigatório, texto, mínimo 8

- `GET /users/balance` — Consultar saldo atual
  - Cabeçalho: `Authorization: Bearer <token>`
  - Respostas: `200` `{ "balance": 0.00 }`, `401` não autenticado, `500` erro interno

#### Transações
- `POST /deposit` — Depositar
  - Cabeçalho: `Authorization: Bearer <token>`
  - Corpo: `{ "amount": 100.00 }`
  - Respostas: `200` sucesso, `422` validação, `401` não autenticado, `500` erro interno
  - Regras de negócio:
    - `amount`: obrigatório, numérico, mínimo `0.01`
    - Limite diário de depósito: `1000.00` (valor da requisição não pode exceder, nem a soma do dia)

- `POST /withdraw` — Sacar
  - Cabeçalho: `Authorization: Bearer <token>`
  - Corpo: `{ "amount": 50.00 }`
  - Respostas: `200` sucesso, `422` validação, `401` não autenticado, `500` erro interno
  - Regras de negócio:
    - `amount`: obrigatório, numérico, mínimo `0.01`
    - Saldo insuficiente: não permite sacar acima do saldo
    - Limite diário de saque: `1000.00` (valor da requisição não pode exceder, nem a soma do dia)

- `POST /transfer` — Transferir para outro usuário
  - Cabeçalho: `Authorization: Bearer <token>`
  - Corpo: `{ "amount": 25.00, "recipient": "destinatario@exemplo.com" }`
  - Respostas: `200` sucesso, `422` validação, `401` não autenticado, `500` erro interno
  - Regras de negócio:
    - `amount`: obrigatório, numérico, mínimo `0.01`
    - `recipient`: obrigatório, e-mail existente na base (`exists:users,email`)
    - Saldo insuficiente: não permite transferir acima do saldo

- `GET /history` — Histórico de transações
  - Cabeçalho: `Authorization: Bearer <token>`
  - Respostas: `200` `{ "transactions": [ { "id": 1, "type": "deposit", "created_at": "2025-11-28T00:00:00Z", "amount": 10.00, "recipient": null, "sender": null }, ... ] }`, `401`, `500`
  - Observações:
    - `type` pode ser: `deposit`, `withdraw`, `transfer`, `transfer-received`
    - `recipient` é preenchido para transferências enviadas; `sender` para transferências recebidas.

### Exemplos de uso (curl)
- Criar usuário e obter token:
  - `curl -s -X POST http://localhost:8080/users -H "Content-Type: application/json" -d '{"name":"Alice","email":"alice@example.com","password":"segura123"}'`
- Consultar saldo:
  - `curl -s http://localhost:8080/users/balance -H "Authorization: Bearer <token>"`
- Depositar:
  - `curl -s -X POST http://localhost:8080/deposit -H "Authorization: Bearer <token>" -H "Content-Type: application/json" -d '{"amount":100.00}'`
- Sacar:
  - `curl -s -X POST http://localhost:8080/withdraw -H "Authorization: Bearer <token>" -H "Content-Type: application/json" -d '{"amount":50.00}'`
- Transferir:
  - `curl -s -X POST http://localhost:8080/transfer -H "Authorization: Bearer <token>" -H "Content-Type: application/json" -d '{"amount":25.00, "recipient":"bob@example.com"}'`
- Histórico:
  - `curl -s http://localhost:8080/history -H "Authorization: Bearer <token>"`

### Swagger / OpenAPI
- UI: `http://localhost:8080/docs`
- Especificação JSON: `http://localhost:8080/openapi.json`

