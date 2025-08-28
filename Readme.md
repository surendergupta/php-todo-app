# 🚀 PHP Mini Framework (Core PHP + OOP)

A lightweight Core PHP Mini Framework built with OOP, custom routing, middleware support, and query builder, designed for building scalable REST APIs without relying on heavy frameworks.



---

## 📌 Features
- ✅ Clean OOP-based architecture
- ✅ Lightweight custom router
- ✅ Middleware support (CORS, Logging, Auth, Rate Limiter, Validation)
- ✅ JWT Authentication
- ✅ Built-in Query Builder (Laravel-inspired)
- ✅ Modular Controller-Service-Repository pattern
- ✅ Support for soft deletes
- ✅ PSR-4 Autoloading via Composer

---

## 📂 Project Structure
```
PHP-TODO-APP/
├── logs/
├── public/
│   └── index.php          # Entry point
├── routes/
│   └── api.php          # Route definitions
├── src/
│   ├── Controllers/     # Application controllers
|   │   ├── TodoController.php 
|   │   └── UserController.php 
│   ├── Core/            # Router, request/response, kernel
|   │   ├── HasValidation.php   # trait
|   │   ├── Kernel.php 
|   │   ├── MiddlewareInterface.php     # interface
|   │   ├── Request.php 
|   │   ├── Response.php 
|   │   ├── Router.php 
|   │   ├── ValidatableInterface.php    # interface
|   │   └── Validator.php 
│   ├── Database/        # QueryBuilder
|   │   └── QueryBuilder.php 
│   ├── Exceptions/      # Custom middlewares
|   │   ├── BaseException.php 
|   │   ├── ForbiddenException.php 
|   │   ├── RepositoryException.php 
|   │   ├── RouteNotFoundException.php 
|   │   ├── UnauthorizedException.php 
|   │   └── ValidationException.php 
│   ├── Middleware/      # Custom middlewares
|   │   ├── AuthMiddleware.php 
|   │   ├── CorsMiddleware.php 
|   │   ├── LoggingMiddleware.php 
|   │   ├── RateLimiterMiddleware.php 
|   │   └── ValidationMiddleware.php 
│   ├── Repository/      # Data repositories
|   │   ├── BaseRepository.php 
|   │   ├── TodoRepository.php 
|   │   └── UserRepository.php 
│   ├── Security/        # JWT handling
|   │   └── Jwt.php 
│   ├── Services/        # Business logic
|   │   ├── TodoService.php 
|   │   └── UserService.php 
│   └── Database.php     # Database connection
├── tests/
│   ├── TodoApiTest.php  # Unit tests
│   └── UserApiTest.php  # Unit tests
├── vendor/              # Composer dependencies
├── .env                # Environment variables
├── .gitignore          # Git ignore file
├── phpunit.xml         # Unit testing configuration
├── composer.json
└── README.md

```
---

## Installation Guide

### 1. Clone Repository
```bash
git clone <Repository_URL>
cd <project-folder>
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment
Create .env file in the project root:
```bash
APP_ENV=dev
DB_HOST=127.0.0.1
DB_NAME=your_database
DB_USER=root
DB_PASS=secret
JWT_SECRET=your_super_secret_key
```

### 4. Run the Server
```bash
php -S localhost:8000 -t ./public
```
Now your API will be available at:
👉 http://localhost:8000/api/v1

---

## 🛠️ API Routes

### Todos (/api/v1/todos)
| Method | Endpoint | Description     | Middleware        |
| ------ | -------- | --------------- | ----------------- |
| GET    | `/`      | List all todos  | Auth, RateLimiter |
| GET    | `/{id}`  | Get single todo | Auth              |
| POST   | `/`      | Create new todo | Auth, Validation  |
| PUT    | `/{id}`  | Update todo     | Auth, Validation  |
| DELETE | `/{id}`  | Delete todo     | Auth              |

---

### Users (/api/v1/users)

| Method | Endpoint     | Description       | Middleware  |
| ------ | ------------ | ----------------- | ----------- |
| GET    | `/`          | List all users    | RateLimiter |
| GET    | `/{user_id}` | Get single user   | RateLimiter |
| POST   | `/register`  | Register new user | Validation  |
| PUT    | `/{user_id}` | Update user       | Auth        |
| DELETE | `/{user_id}` | Delete user       | Auth        |

---

### Authentication (/api/v1/auth)

| Method | Endpoint    | Description        | Middleware  |
| ------ | ----------- | ------------------ | ----------- |
| POST   | `/login`    | Login user         | Validation  |
| POST   | `/logout`   | Logout user        | Auth        |
| GET    | `/validate` | Validate JWT Token | RateLimiter |

---

## 🔐 Security
- JWT Authentication ensures secure API access.
- Validation Middleware prevents invalid payloads.
- Rate Limiter Middleware mitigates brute force attacks.
- CORS Middleware allows controlled API consumption.

---

## 📖 Documentation

Complete API documentation is available via Postman: 
👉 [Postman Collection](https://documenter.getpostman.com/view/681233/2sB3HgQ3sF)

---

## 🧑‍💻 Example Usage

### Register User
```bash
curl -X POST http://localhost:8000/api/v1/users/register \
-H "Content-Type: application/json" \
-d '{
  "user_id": "john123",
  "email_address": "john@example.com",
  "user_password": "password123",
  "first_name": "John",
  "last_name": "Doe"
}'

```

### Login User
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
-H "Content-Type: application/json" \
-d '{"user_id": "john123", "user_password": "password123"}'

```

### Get Todos (Authenticated)
```bash
curl -X GET http://localhost:8000/api/v1/todos \
-H "Authorization: Bearer <your_jwt_token>"
```

---

## 📝 License

MIT License. Free to use, modify, and distribute.