# Device Manager - Sistema de Gerenciamento de Dispositivos

Aplicação CRUD para teste da empresa Snapbot com backend Laravel, frontend Angular e banco MySQL.

## Tecnologias

- **Backend:** Laravel 11 + PHP 8.2
- **Frontend:** Angular 21
- **Banco de Dados:** MySQL 8.0
- **Containerização:** Docker + Docker Compose

## Pré-requisitos

- Docker Desktop instalado
- Docker Compose instalado
- Portas disponíveis: 3306 (MySQL), 8000 (Backend), 4200 (Frontend)

## Instalação e Execução

### 1. Clonar o repositório
```bash
git clone https://github.com/J-Leonardo/Snapbot-teste.git
cd projeto-device-manager
```

### 2. Configurar variáveis de ambiente

#### Backend (Laravel)
```bash
cd device-manager-api
cp .env.docker .env
```

Configurações importantes no `.env`:
```env
DB_HOST=mysql
DB_DATABASE=device_manager
DB_USERNAME=device_user
DB_PASSWORD=device_password
```

#### Frontend (Angular)
Verificar `src/environments/environment.prod.ts`:
```typescript
apiUrl: 'http://localhost:8000/api'
```

### 3. Gerar APP_KEY do Laravel
```bash
cd device-manager-api
php artisan key:generate
```

### 4. Construir e iniciar os containers
```bash
cd ..

docker-compose build

docker-compose up -d
```

### 5. Rodar migrations
```bash
docker-compose exec laravel-app php artisan migrate
```

### 6. Acessar a aplicação

- **Frontend:** http://localhost:4200
- **Backend API:** http://localhost:8000/api
- **MySQL:** localhost:3306

## Execução de testes
```bash
docker-compose exec laravel-app php artisan test
```

### Endpoints Principais

#### Autenticação
- `POST /api/register` - Registrar usuário
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/me` - Dados do usuário

#### Dispositivos
- `GET /api/devices` - Listar dispositivos
- `POST /api/devices` - Criar dispositivo
- `PUT /api/devices/{id}` - Atualizar dispositivo
- `DELETE /api/devices/{id}` - Excluir dispositivo
- `PATCH /api/devices/{id}/use` - Toggle status