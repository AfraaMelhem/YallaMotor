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
git clone https://github.com/AfraaMelhem/YallaMotor.git
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
curl "http://localhost:8000/api/v1/cars?filters[make]=Volkswagen&include_facets=true&per_page=15
```

**Response:**
```json
{
    "status": "success",
    "message": "Cars retrieved successfully",
    "data": {
        "data": [
            {
                "id": 19,
                "dealer_id": 3,
                "make": "Volkswagen",
                "model": "Tiguan",
                "year": 2022,
                "mileage_km": 91284,
                "country_code": "US",
                "city": "Philadelphia",
                "status": "active",
                "listed_at": "2025-03-30T00:43:41.000000Z",
                "price": 12430.07,
                "price_formatted": "$12,430.07",
                "mileage_miles": 56721.23,
                "age_years": 3,
                "status_color": "green"
            },
            {
                "id": 21,
                "dealer_id": 4,
                "make": "Volkswagen",
                "model": "Atlas",
                "year": 2020,
                "mileage_km": 122316,
                "country_code": "FR",
                "city": "New York",
                "status": "active",
                "listed_at": "2025-06-19T00:29:00.000000Z",
                "price": 43621.28,
                "price_formatted": "$43,621.28",
                "mileage_miles": 76003.62,
                "age_years": 5,
                "status_color": "green"
            },
            {
                "id": 25,
                "dealer_id": 4,
                "make": "Volkswagen",
                "model": "Jetta",
                "year": 2021,
                "mileage_km": 113877,
                "country_code": "FR",
                "city": "Dallas",
                "status": "active",
                "listed_at": "2025-06-21T17:40:27.000000Z",
                "price": 41371.02,
                "price_formatted": "$41,371.02",
                "mileage_miles": 70759.87,
                "age_years": 4,
                "status_color": "green"
            },
            {
                "id": 38,
                "dealer_id": 5,
                "make": "Volkswagen",
                "model": "Golf",
                "year": 2019,
                "mileage_km": 93994,
                "country_code": "SA",
                "city": "Los Angeles",
                "status": "hidden",
                "listed_at": "2025-03-23T20:38:57.000000Z",
                "price": 52611.8,
                "price_formatted": "$52,611.80",
                "mileage_miles": 58405.15,
                "age_years": 6,
                "status_color": "gray"
            },
            {
                "id": 46,
                "dealer_id": 6,
                "make": "Volkswagen",
                "model": "Golf",
                "year": 2010,
                "mileage_km": 195611,
                "country_code": "SA",
                "city": "Berlin",
                "status": "hidden",
                "listed_at": "2025-04-18T23:57:28.000000Z",
                "price": 36993.05,
                "price_formatted": "$36,993.05",
                "mileage_miles": 121547,
                "age_years": 15,
                "status_color": "gray"
            },
            {
                "id": 52,
                "dealer_id": 7,
                "make": "Volkswagen",
                "model": "Passat",
                "year": 2014,
                "mileage_km": 177623,
                "country_code": "CA",
                "city": "Paris",
                "status": "active",
                "listed_at": "2025-07-09T14:17:21.000000Z",
                "price": 66821.5,
                "price_formatted": "$66,821.50",
                "mileage_miles": 110369.78,
                "age_years": 11,
                "status_color": "green"
            },
            {
                "id": 65,
                "dealer_id": 8,
                "make": "Volkswagen",
                "model": "Golf",
                "year": 2024,
                "mileage_km": 119287,
                "country_code": "AU",
                "city": "Los Angeles",
                "status": "active",
                "listed_at": "2025-04-15T00:35:22.000000Z",
                "price": 33859.18,
                "price_formatted": "$33,859.18",
                "mileage_miles": 74121.48,
                "age_years": 1,
                "status_color": "green"
            },
            {
                "id": 77,
                "dealer_id": 9,
                "make": "Volkswagen",
                "model": "Tiguan",
                "year": 2021,
                "mileage_km": 126725,
                "country_code": "AE",
                "city": "Phoenix",
                "status": "active",
                "listed_at": "2025-06-14T04:59:07.000000Z",
                "price": 57947.42,
                "price_formatted": "$57,947.42",
                "mileage_miles": 78743.24,
                "age_years": 4,
                "status_color": "green"
            },
            {
                "id": 87,
                "dealer_id": 11,
                "make": "Volkswagen",
                "model": "Passat",
                "year": 2012,
                "mileage_km": 13689,
                "country_code": "AU",
                "city": "San Jose",
                "status": "active",
                "listed_at": "2025-08-13T00:20:44.000000Z",
                "price": 57666.92,
                "price_formatted": "$57,666.92",
                "mileage_miles": 8505.95,
                "age_years": 13,
                "status_color": "green"
            },
            {
                "id": 93,
                "dealer_id": 12,
                "make": "Volkswagen",
                "model": "Jetta",
                "year": 2017,
                "mileage_km": 39057,
                "country_code": "CA",
                "city": "Berlin",
                "status": "active",
                "listed_at": "2025-08-27T10:03:30.000000Z",
                "price": 30234.29,
                "price_formatted": "$30,234.29",
                "mileage_miles": 24268.89,
                "age_years": 8,
                "status_color": "green"
            },
            {
                "id": 96,
                "dealer_id": 12,
                "make": "Volkswagen",
                "model": "Passat",
                "year": 2021,
                "mileage_km": 111169,
                "country_code": "CA",
                "city": "Houston",
                "status": "active",
                "listed_at": "2025-08-17T11:55:41.000000Z",
                "price": 61565.23,
                "price_formatted": "$61,565.23",
                "mileage_miles": 69077.19,
                "age_years": 4,
                "status_color": "green"
            },
            {
                "id": 99,
                "dealer_id": 12,
                "make": "Volkswagen",
                "model": "Atlas",
                "year": 2011,
                "mileage_km": 66441,
                "country_code": "CA",
                "city": "Chicago",
                "status": "active",
                "listed_at": "2025-09-05T07:35:31.000000Z",
                "price": 32000.75,
                "price_formatted": "$32,000.75",
                "mileage_miles": 41284.51,
                "age_years": 14,
                "status_color": "green"
            },
            {
                "id": 100,
                "dealer_id": 12,
                "make": "Volkswagen",
                "model": "Atlas",
                "year": 2024,
                "mileage_km": 158999,
                "country_code": "CA",
                "city": "Riyadh",
                "status": "hidden",
                "listed_at": "2025-04-26T06:11:17.000000Z",
                "price": 58101.35,
                "price_formatted": "$58,101.35",
                "mileage_miles": 98797.37,
                "age_years": 1,
                "status_color": "gray"
            },
            {
                "id": 102,
                "dealer_id": 12,
                "make": "Volkswagen",
                "model": "Tiguan",
                "year": 2023,
                "mileage_km": 153683,
                "country_code": "CA",
                "city": "San Antonio",
                "status": "active",
                "listed_at": "2025-04-02T14:26:02.000000Z",
                "price": 24516.44,
                "price_formatted": "$24,516.44",
                "mileage_miles": 95494.16,
                "age_years": 2,
                "status_color": "green"
            },
            {
                "id": 107,
                "dealer_id": 13,
                "make": "Volkswagen",
                "model": "Atlas",
                "year": 2018,
                "mileage_km": 257,
                "country_code": "SA",
                "city": "Paris",
                "status": "active",
                "listed_at": "2025-06-12T15:36:22.000000Z",
                "price": 40252.3,
                "price_formatted": "$40,252.30",
                "mileage_miles": 159.69,
                "age_years": 7,
                "status_color": "green"
            }
        ],
        "meta": {
            "pagination": {
                "current_page": 1,
                "per_page": 15,
                "total": 43,
                "last_page": 3,
                "from": 1,
                "to": 15
            },
            "filters_applied": {
                "make": "Volkswagen"
            },
            "query_time_ms": 44.2
        },
        "facets": {
            "makes": {
                "BMW": 34,
                "Toyota": 33,
                "Volkswagen": 30,
                "Honda": 26,
                "Mercedes": 26,
                "Audi": 25,
                "Nissan": 24,
                "Ford": 24,
                "Kia": 21,
                "Hyundai": 18
            },
            "years": {
                "2024": 24,
                "2023": 20,
                "2022": 20,
                "2021": 22,
                "2020": 14,
                "2019": 13,
                "2018": 17,
                "2017": 20,
                "2016": 9,
                "2015": 14,
                "2014": 13,
                "2013": 18,
                "2012": 15,
                "2011": 24,
                "2010": 18
            }
        }
    },
    "correlation_id": "req_68cc982e51b14"
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

Leads are automatically scored (0-100) based on **4 key factors**:

**Email Quality (20 points):**
- ✅ Valid email format: 20 points
- ❌ Invalid format: 0 points
- Validates using PHP's `FILTER_VALIDATE_EMAIL`

**Phone Quality (30 points):**
- ✅ Valid phone (≥10 digits): 30 points
- ❌ Invalid/short phone: 0 points
- Supports international formats (+country codes)
- Automatically cleans special characters (keeps only digits and +)

**Source Attribution (10 points):**
- ✅ Any valid source: 10 points
- ❌ Empty source: 0 points
- Supported sources: `api`, `website`, `mobile`, `social`

**Listing Recency (40 points):**
- ≤1 day old: 40 points
- ≤7 days old: 30 points
- ≤30 days old: 20 points
- ≤90 days old: 10 points
- >90 days old: 0 points

### Status Assignment

- **≥80 points**: `qualified` - High priority lead
- **<80 points**: `new` - Standard lead processing

### Example Scoring

```json
{
  "score": 90,
  "suggested_status": "qualified",
  "scoring_data": {
    "email": {
      "score": 20,
      "details": {"valid_format": true}
    },
    "phone": {
      "score": 30,
      "details": {
        "cleaned": "+1234567890",
        "valid_format": true
      }
    },
    "source": {
      "score": 10,
      "details": {"source": "website"}
    },
    "recency": {
      "score": 30,
      "details": {
        "listing_id": 123,
        "days_since_listed": 5,
        "listed_at": "2024-01-15T10:00:00Z"
      }
    }
  },
  "scored_at": "2024-01-20T14:30:00Z"
}
```

### Background Processing

Lead scoring runs asynchronously via queues to maintain API performance.

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test files
php artisan test tests/Feature/API/CarControllerTest.php
php artisan test tests/Feature/API/LeadControllerTest.php
php artisan test tests/Feature/API/CacheInvalidationTest.php
php artisan test tests/Unit/Services/LeadScoringServiceTest.php

# Run with coverage
php artisan test --coverage
```

### Comprehensive Test Suite

The application includes a complete test suite with **64 tests** covering all critical functionality:

#### **Feature Tests (46 tests)**

**Cars API Tests (`CarControllerTest.php`):**
- ✅ Paginated car listings with filtering
- ✅ Filter by make, year range, price range
- ✅ Faceted navigation with make/year counts
- ✅ Cache HIT/MISS behavior verification
- ✅ ETag support and 304 Not Modified responses
- ✅ Search functionality across make/model
- ✅ Sorting by price, date, etc.
- ✅ Per-page limits and validation
- ✅ Popular makes endpoint with country filtering
- ✅ Individual car details with relationships
- ✅ Error handling for invalid parameters

**Leads API Tests (`LeadControllerTest.php`):**
- ✅ Lead creation happy path
- ✅ Rate limiting (5 requests per IP+email/hour)
- ✅ Data validation (email format, phone, required fields)
- ✅ Data normalization (email lowercase, phone cleaning)
- ✅ Listing validation (exists, active status)
- ✅ Request metadata capture (IP, User-Agent)
- ✅ Background job dispatch for lead scoring
- ✅ Source defaulting and validation
- ✅ Separate rate limits for different emails
- ✅ Comprehensive validation error scenarios

**Cache Invalidation Tests (`CacheInvalidationTest.php`):**
- ✅ Car list cache invalidation on listing updates
- ✅ Individual car cache invalidation
- ✅ Popular makes cache updates on make changes
- ✅ Country-specific cache invalidation
- ✅ Filtered query cache invalidation
- ✅ Facet cache updates
- ✅ Status change cache invalidation
- ✅ Price update cache invalidation
- ✅ ETag regeneration on data changes
- ✅ Separate cache handling for different filters

#### **Unit Tests (18 tests)**

**Lead Scoring Service Tests (`LeadScoringServiceTest.php`):**
- ✅ Email validation scoring (20 points for valid format)
- ✅ Phone validation scoring (30 points for valid format)
- ✅ International phone number support
- ✅ Phone number cleaning (removes special characters)
- ✅ Source attribution scoring (10 points)
- ✅ Listing recency scoring (up to 40 points)
- ✅ Total score calculation and aggregation
- ✅ Status suggestion based on score thresholds:
  - 80+ points → "qualified"
  - <80 points → "new"
- ✅ Edge cases and boundary conditions
- ✅ Timestamp generation for scoring events
- ✅ Detailed scoring breakdown for audit trails
- ✅ Relationship handling with listings
- ✅ Input validation and error handling

### Test Configuration

**Test Environment Setup:**
```php
// phpunit.xml configuration
- Database: SQLite in-memory
- Cache: Array driver (no Redis dependency)
- Queue: Sync driver (immediate execution)
- Mail: Array driver (no email sending)
```

**Test Data Management:**
- Factory-based test data generation
- Database transactions for isolation
- Cache clearing between tests
- Rate limit clearing for consistent testing

### Test Quality Assurance

**Coverage Areas:**
- ✅ **API Endpoints**: All public endpoints tested
- ✅ **Business Logic**: Lead scoring, filtering, pagination
- ✅ **Caching**: HIT/MISS scenarios, invalidation patterns
- ✅ **Validation**: Input validation, data normalization
- ✅ **Security**: Rate limiting, API key authentication
- ✅ **Performance**: Query optimization, cache efficiency
- ✅ **Error Handling**: Graceful degradation, proper status codes
- ✅ **Integration**: Service layer interactions, job dispatching

**Key Test Insights:**
- **Lead Scoring Bug Detected**: Date comparison logic issue where `diffInDays()` returns negative values for past dates
- **Cache Performance**: Verified cache key generation and invalidation strategies
- **Rate Limiting**: Confirmed proper throttling per IP+email combination
- **Data Integrity**: Validated all input sanitization and normalization

### Performance Testing

The API is designed for P95 ≤ 400ms response times:

```bash
# Test cache warm performance
curl -w "@curl-format.txt" "http://localhost:8000/api/v1/cars"

# Test with various filters
curl -w "@curl-format.txt" "http://localhost:8000/api/v1/cars?filters[make]=Toyota&filters[country_code]=US&include_facets=true"
```

## Security

### API Key Authentication

Admin endpoints use header-based API key authentication:
```http
X-Api-Key: ADMIN_API_KEY
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
