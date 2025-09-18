# YallaMotor Car Marketplace API

A high-performance car marketplace backend built with Laravel 11, featuring advanced caching strategies, lead management with AI-powered scoring, and comprehensive admin controls.

## Features

- **High-Performance Car Listings API** with P95 ≤ 400ms response times
- **Advanced Filtering & Search** with faceted navigation
- **Redis-Based Caching** with tag-based invalidation
- **Lead Management System** with background AI scoring
- **Admin Cache Control** with API key authentication
- **Rate Limiting & Security** with correlation ID tracking
- **Comprehensive Testing** with feature and unit tests

## Quick Start

### Requirements

- PHP 8.2+
- Laravel 11
- Redis
- MySQL

### Installation

```bash
# Clone repository
git clone <repository-url>
cd YallaMotor

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database and Redis in .env
# Run migrations and seed data
php artisan migrate --seed

# Start development server
php artisan serve
```

### Configuration

Add to your `.env`:

```env
# Cache Configuration
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Admin API Key
ADMIN_API_KEY=your-secure-admin-key-here

# Queue Configuration (for lead scoring)
QUEUE_CONNECTION=redis
```

## API Endpoints

### Cars API

#### Get Cars with Filtering
```http
GET /api/v1/cars
```

**Query Parameters:**
- `make` - Filter by car make
- `model` - Filter by car model
- `year_min`, `year_max` - Year range
- `price_min_cents`, `price_max_cents` - Price range (in cents)
- `country_code` - Filter by country code
- `city` - Filter by city
- `dealer_id` - Filter by dealer
- `per_page` - Results per page (default: 15, max: 50)
- `page` - Page number
- `include_facets` - Include faceted counts (true/false)
- `sort_by` - Sort field (listed_at, price_cents)
- `sort_direction` - Sort direction (asc, desc)

**Example Request:**
```bash
curl "http://localhost:8000/api/v1/cars?make=Toyota&price_min_cents=1000000&price_max_cents=5000000&include_facets=true"
```

**Response:**
```json
{
  "status": "success",
  "message": "Cars retrieved successfully",
  "data": {
    "cars": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "make": "Toyota",
          "model": "Camry",
          "year": 2020,
          "price": "$25,000",
          "country_code": "US",
          "city": "New York",
          "dealer": {
            "id": 1,
            "name": "ABC Motors"
          }
        }
      ],
      "total": 45,
      "per_page": 20
    },
    "facets": {
      "makes": [{"value": "Toyota", "count": 15}],
      "models": [{"value": "Camry", "count": 8}],
      "years": [{"value": "2020", "count": 12}],
      "countries": [{"value": "US", "count": 30}]
    }
  },
  "correlation_id": "req_abc123"
}
```

#### Get Single Car
```http
GET /api/v1/cars/{id}
```

### Leads API

#### Submit Lead
```http
POST /api/v1/leads
```

**Request Body:**
```json
{
  "listing_id": 1,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890",
  "message": "I'm interested in this car"
}
```

**Rate Limiting:** 5 requests per hour per IP+email combination

### Listings API (Legacy/Admin)

#### Get All Listings
```http
GET /api/v1/listings
```

**Query Parameters:**
- `filters` - JSON object with filter criteria
- `search` - General search term
- `sort` - JSON object with sort criteria
- `per_page` - Results per page (default: 15, max: 50)
- `page` - Page number

#### Fast Browse Listings
```http
GET /api/v1/listings/fast-browse
```

**Query Parameters:**
- `make`, `model`, `year`, `price_cents` - Filter criteria
- `sort_by` - Sort field (listed_at, price_cents, year)
- `sort_direction` - Sort direction (asc, desc)
- `per_page` - Results per page

#### Popular Makes
```http
GET /api/v1/listings/popular-makes?country=US
```

#### Get Single Listing
```http
GET /api/v1/listings/{id}
```

#### Update Listing Price
```http
POST /api/v1/listings/{id}/price
```

**Request Body:**
```json
{
  "price": 2500000
}
```

#### Update Listing Status
```http
POST /api/v1/listings/{id}/status
```

**Request Body:**
```json
{
  "status": "sold"
}
```

### Dealers API

#### Get All Dealers
```http
GET /api/v1/dealers
```

**Query Parameters:**
- `filters` - JSON object with filter criteria
- `search` - General search term
- `per_page` - Results per page (default: 15, max: 50)
- `page` - Page number

#### Get Single Dealer
```http
GET /api/v1/dealers/{id}
```

#### Create Dealer
```http
POST /api/v1/dealers
```

**Request Body:**
```json
{
  "name": "ABC Motors",
  "country_code": "US"
}
```

#### Update Dealer
```http
POST /api/v1/dealers/{id}
```

**Request Body:**
```json
{
  "name": "ABC Motors Updated",
  "country_code": "US"
}
```

#### Delete Dealer
```http
DELETE /api/v1/dealers/{id}
```

#### Get Dealers by Country
```http
GET /api/v1/dealers/country/{countryCode}
```

**Example:**
```bash
curl "http://localhost:8000/api/v1/dealers/country/US"
```

### Admin API

All admin endpoints require `X-Api-Key` header.

#### Purge Cache
```http
POST /api/v1/admin/cache/purge
Content-Type: application/json
X-Api-Key: your-admin-key
```

**Request Body (optional):**
```json
{
  "keys": ["specific_cache_key"],
  "tags": ["cars_list", "country:US"]
}
```

Empty body purges all cache.

#### Cache Status
```http
GET /api/v1/admin/cache/status
X-Api-Key: your-admin-key
```

## Caching Strategy

### Cache Architecture

The API implements a sophisticated multi-layer caching system:

- **Application Cache**: Redis-based with tag support
- **CDN Cache**: HTTP headers for edge caching
- **ETag Support**: 304 Not Modified responses
- **Cache Stampede Protection**: Prevents thundering herd

### Cache Tags

Cars are cached with hierarchical tags:
- `cars_list` - All car listings
- `country:{code}` - Cars by country
- `dealer:{id}` - Cars by dealer
- `car:{id}` - Individual car data

### Cache Invalidation

Automatic invalidation occurs when:
- Listings are created/updated/deleted
- Dealers are modified
- Manual admin purge

### Cache Headers

Responses include observability headers:
- `X-Cache`: HIT/MISS/STALE
- `X-Query-Time-ms`: Response generation time
- `Cache-Control`: CDN caching directives
- `ETag`: Content versioning

## Lead Scoring System

### Scoring Algorithm

Leads are automatically scored (0-100) based on:

**Email Quality (0-25 points):**
- Valid format: 5 points
- Known domains (gmail, yahoo): 10 points
- Corporate domains: 20 points
- Custom validation rules

**Phone Quality (0-20 points):**
- Valid format: 10 points
- International format: +10 points
- Mobile vs landline detection

**Message Analysis (0-30 points):**
- Length and detail: 5-15 points
- Intent keywords (buy, purchase, financing): +15 points
- Quality indicators

**Listing Context (0-15 points):**
- High-value listings: +10 points
- Recent listings: +5 points
- Dealer reputation factors

**Behavioral Signals (0-10 points):**
- Business hours submission: +5 points
- Source quality (website > api): +5 points
- Duplicate detection: -20 points

### Status Assignment

- **0-39**: `new` - Basic lead
- **40-69**: `qualified` - Good potential
- **70-100**: `hot` - High priority

### Background Processing

Lead scoring runs asynchronously via queues to maintain API performance.

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test tests/Feature/CarsApiTest.php
php artisan test tests/Unit/LeadScoringServiceTest.php

# Run with coverage
php artisan test --coverage
```

### Test Coverage

**Feature Tests:**
- Cars API filtering and pagination
- Cache HIT/MISS behavior
- Lead submission and validation
- Rate limiting
- Admin cache management
- Error handling and correlation IDs

**Unit Tests:**
- Lead scoring algorithm
- Cache service functionality
- Data transformation
- Validation rules

### Performance Testing

The API is designed for P95 ≤ 400ms response times:

```bash
# Test cache warm performance
curl -w "@curl-format.txt" "http://localhost:8000/api/v1/cars"

# Test with various filters
curl -w "@curl-format.txt" "http://localhost:8000/api/v1/cars?make=Toyota&country=US&include_facets=true"
```

## Security

### API Key Authentication

Admin endpoints use header-based API key authentication:
```http
X-Api-Key: your-secure-admin-key
```

### Rate Limiting

- **Leads API**: 5 requests/minute per IP
- **Cars API**: 60 requests/minute per IP
- **Admin API**: No limits (authenticated)

### Data Validation

- Input sanitization and validation
- SQL injection prevention
- XSS protection via JSON responses
- CORS configuration for web clients

## Monitoring & Observability

### Correlation IDs

Every request gets a unique correlation ID for tracing:
- Auto-generated or from `X-Correlation-ID` header
- Included in all responses and logs
- Enables end-to-end request tracking

### Performance Metrics

Response headers provide real-time metrics:
- `X-Query-Time-ms`: Database query time
- `X-Cache`: Cache hit status
- `X-Rate-Limit-Remaining`: Rate limit status

### Logging

Comprehensive logging for:
- Cache operations (hit/miss/invalidation)
- Lead scoring results
- Admin actions
- Performance anomalies
- Error tracking with correlation IDs

## Architecture

### Design Patterns

- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic isolation
- **Observer Pattern**: Event-driven cache invalidation
- **Command Pattern**: Request/response handling
- **Factory Pattern**: Test data generation

### Key Components

```
app/
├── Http/Controllers/API/     # API endpoints
├── Services/                 # Business logic
├── Repositories/             # Data access
├── Http/Middleware/          # Authentication & CORS
├── Http/Resources/           # Response transformation
├── Http/Requests/            # Input validation
├── Models/                   # Eloquent models
├── Observers/                # Event handlers
├── Jobs/                     # Background tasks
└── Traits/                   # Shared functionality
```

### Database Schema

**Core Tables:**
- `dealers` - Car dealerships
- `listings` - Car inventory
- `leads` - Customer inquiries
- `lead_scores` - AI scoring results

**Indexes for Performance:**
- Composite indexes on filter combinations
- Covering indexes for common queries
- Foreign key optimization

## Configuration

### Environment Variables

```env
# Application
APP_NAME="YallaMotor API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yallamotor
DB_USERNAME=root
DB_PASSWORD=

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Admin Security
ADMIN_API_KEY=your-very-secure-admin-key-change-this

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### Cache Configuration

In `config/cache.php`:
```php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

'prefix' => env('CACHE_PREFIX', 'yallamotor_cache'),
```

## Deployment

### Production Checklist

- [ ] Set secure `ADMIN_API_KEY`
- [ ] Configure Redis with persistence
- [ ] Set up queue workers
- [ ] Configure rate limiting
- [ ] Enable opcache
- [ ] Set up monitoring
- [ ] Configure log rotation
- [ ] Test cache invalidation
- [ ] Verify CDN integration

### Queue Workers

```bash
# Start queue workers
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# For production with supervisor
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --daemon
```

### Cache Optimization

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Warm up application cache
curl -H "Cache-Control: no-cache" "https://your-domain.com/api/v1/cars"
```

## API Documentation

### OpenAPI Specification

The API follows OpenAPI 3.0 standards. Generate documentation:

```bash
php artisan l5-swagger:generate
```

Access at: `http://localhost:8000/api/documentation`

### Postman Collection

Import the included Postman collection for easy API testing:
- Collection: `docs/YallaMotor-API.postman_collection.json`
- Environment: `docs/YallaMotor-Local.postman_environment.json`

## Support

### Common Issues

**Cache Not Working:**
- Verify Redis connection
- Check cache driver configuration
- Ensure queue workers are running

**Slow Performance:**
- Check database indexes
- Monitor Redis memory usage
- Review query optimization

**Lead Scoring Issues:**
- Verify queue workers are processing
- Check lead scoring service configuration
- Review background job logs

### Contributing

1. Fork the repository
2. Create feature branch
3. Add comprehensive tests
4. Ensure all tests pass
5. Submit pull request

### License

This project is proprietary software. All rights reserved.
