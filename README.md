# Recipe Bank - Full Stack Application

A comprehensive recipe management system built with Laravel backend and Vue.js frontend, featuring role-based access control, file uploads, and comprehensive API documentation.

## 🏗️ Architecture Overview

- **Backend**: Laravel 10 with Laravel Sanctum authentication
- **Frontend**: Vue.js 2 with Element UI components
- **Database**: MySQL with polymorphic relationships
- **Authentication**: Laravel Sanctum API tokens
- **Authorization**: Spatie Laravel Permission package
- **File Storage**: Laravel file system with polymorphic attachments

## 📋 Features

### User Management
- ✅ User registration and login
- ✅ Role-based access control (Admin, Sub-Admin, Owner)
- ✅ Profile management
- ✅ Secure password handling

### Recipe Management
- ✅ Full CRUD operations for recipes
- ✅ Image upload with polymorphic attachments
- ✅ Search and filter functionality
- ✅ Cuisine type categorization
- ✅ Ingredient and step management

### Authorization System
- ✅ Laravel Policies for fine-grained permissions
- ✅ Configuration-based role and permission management
- ✅ Role-specific data filtering

### API Features
- ✅ RESTful API design
- ✅ Form request validation
- ✅ API resource transformations
- ✅ Comprehensive error handling

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- Node.js 16+
- MySQL 8.0+
- Composer
- npm/yarn

### Backend Setup

1. **Clone and navigate to backend directory**
   ```bash
   cd backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   ```

4. **Configure database in `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=recipe_bank
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   APP_URL=http://localhost:8000
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

### Frontend Setup

1. **Navigate to client directory**
   ```bash
   cd client
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Configure API endpoint in `.env.development`**
   ```env
   VUE_APP_BASE_API = 'http://localhost:8000/api/v1'
   ```

4. **Start development server**
   ```bash
   npm run dev
   ```

## 👥 Test Accounts

After running the seeders, you can use these test accounts:

| Role | Email | Password | Permissions |
|------|-------|----------|-------------|
| **Admin** | admin@recipebank.com | password123 | All permissions (manage recipes, users, cuisine types) |
| **Sub-Admin** | sub-admin@recipebank.com | password123 | View recipes only |
| **Owner** | owner@recipebank.com | password123 | Create and view own recipes |

## 🔗 API Documentation

### API Documentation URL
```
http://127.0.0.1:8000/docs
```

## Demo Video
```
https://drive.google.com/file/d/1b_vGE9LWpd343IjyFBMdFeSlaZ6jFyX0/view?usp=sharing
```

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
All API requests require Bearer token authentication:
```
Authorization: Bearer {your-token}
```

### API Endpoints

#### Authentication
- `POST /auth/register` - User registration
- `POST /auth/login` - User login
- `POST /auth/logout` - User logout
- `GET /auth/user` - Get authenticated user profile

#### Recipes
- `GET /recipes` - List recipes (with search, filter, pagination)
- `POST /recipes` - Create new recipe
- `GET /recipes/{id}` - Get specific recipe
- `PUT /recipes/{id}` - Update recipe
- `DELETE /recipes/{id}` - Delete recipe

#### Cuisine Types
- `GET /cuisine-types/dropdown` - Get cuisine types for dropdown selection

### Sample API Requests

#### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@recipebank.com",
    "password": "password123"
  }'
```

#### Create Recipe
```bash
curl -X POST http://localhost:8000/api/v1/recipes \
  -H "Authorization: Bearer {your-token}" \
  -H "Content-Type: multipart/form-data" \
  -F "name=Pasta Carbonara" \
  -F "description=Classic Italian pasta dish" \
  -F "ingredients[]=Spaghetti" \
  -F "ingredients[]=Eggs" \
  -F "steps[]=Boil pasta" \
  -F "steps[]=Mix with eggs" \
  -F "cuisine_type_id=1" \
  -F "image=@/path/to/image.jpg"
```

#### Search Recipes
```bash
curl -X GET "http://localhost:8000/api/v1/recipes?search=pasta&cuisine_type_id=1" \
  -H "Authorization: Bearer {your-token}"
```

#### Get Cuisine Types for Dropdown
```bash
curl -X GET http://localhost:8000/api/v1/cuisine-types/dropdown
```

## 🧪 Testing

### Backend Tests

```bash
cd backend

# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/Api/AuthControllerTest.php
php artisan test tests/Feature/Api/RecipeControllerTest.php
php artisan test tests/Feature/Api/CuisineTypeControllerTest.php

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- ✅ Authentication API tests (registration, login, logout, profile)
- ✅ Recipe API tests (CRUD, authorization, file uploads)
- ✅ Cuisine Type API tests (dropdown functionality)

## 📁 Project Structure

```
recipe_bank/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/    # API Controllers
│   │   │   ├── Requests/          # Form Request Validation
│   │   │   └── Resources/         # API Resources
│   │   ├── Models/               # Eloquent Models
│   │   ├── Policies/            # Authorization Policies
│   │   ├── Repositories/        # Data Layer
│   │   └── Services/            # Business Logic
│   ├── database/
│   │   ├── migrations/          # Database Migrations
│   │   ├── seeders/            # Database Seeders
│   │   └── factories/          # Model Factories
│   └── tests/                  # PHPUnit Tests
├── client/                      # Vue.js Frontend
│   ├── src/
│   │   ├── api/               # API Service Layer
│   │   ├── components/        # Reusable Components
│   │   ├── views/            # Page Components
│   │   ├── store/            # Vuex State Management
│   │   └── utils/            # Utility Functions
└── README.md
```

## 🔒 Permissions System

### Roles and Permissions

```php
// config/constants.php
'permissions' => [
    'admin' => [
        'list-recipes',
        'add-recipe', 
        'edit-recipe',
        'delete-recipe',
        'manage-users',
        'manage-cuisine-types',
    ],
    'sub-admin' => [
        'list-recipes',
    ],
    'owner' => [
        'list-recipes',
        'add-recipe',
    ],
]
```

### Policy-Based Authorization
- `RecipePolicy` - Controls recipe access based on user roles

## 🚀 Deployment

### Production Setup

1. **Environment Configuration**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   ```

2. **Optimize for Production**
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Frontend Build**
   ```bash
   npm run build
   ```

## 🛠️ Development

### Adding New Features

1. **Database Changes**
   ```bash
   php artisan make:migration create_new_table
   php artisan make:seeder NewTableSeeder
   ```

2. **API Development**
   ```bash
   php artisan make:controller Api/NewController
   php artisan make:request StoreNewRequest
   php artisan make:resource NewResource
   ```

3. **Business Logic**
   ```bash
   php artisan make:service NewService
   php artisan make:repository NewRepository
   ```

4. **Testing**
   ```bash
   php artisan make:test NewFeatureTest
   ```

## 📝 Contributing

1. Follow PSR-12 coding standards
2. Write tests for new features
3. Use Laravel best practices
4. Follow Vue.js style guide
5. Update documentation

## 🐛 Troubleshooting

### Common Issues

1. **Storage Permission Issues**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

2. **Database Connection**
   - Verify MySQL service is running
   - Check database credentials in `.env`
   - Ensure database exists

3. **CORS Issues**
   - Configure `config/cors.php`
   - Update allowed origins

4. **File Upload Issues**
   - Check `php.ini` upload limits
   - Verify storage directory permissions
