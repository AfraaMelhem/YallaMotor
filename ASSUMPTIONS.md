# ASSUMPTIONS.md

## Technical Decisions & Tradeoffs

This document outlines the key assumptions, technical decisions, and tradeoffs made during the development of the YallaMotor car marketplace backend.

### **Caching Strategy**

#### **Decision**: Custom tag-based cache system over Laravel's built-in cache tags
- **Reason**: Better control over invalidation patterns and Redis integration
- **Tradeoff**: More complexity but superior cache correctness and performance
- **Implementation**: Manual tag management in `CacheService.php` with Redis storage
- **Alternative Considered**: Laravel's cache tags (rejected due to limited Redis support)

#### **Cache Stampede Protection**
- **Decision**: Redis-based tag system with jitter
- **Reason**: Prevents thundering herd problem during cache invalidation
- **Tradeoff**: Slightly more complex cache logic vs simple mutex locks
- **Future Enhancement**: Could add probabilistic early refresh

#### **Cache TTL Strategy**
- **Cars List**: 300s (5 minutes) - balances freshness with performance
- **Car Details**: 600s (10 minutes) - individual cars change less frequently
- **Facets**: 900s (15 minutes) - aggregate data changes slowly
- **Popular Makes**: 1800s (30 minutes) - very stable data

### **Lead Scoring Algorithm**

#### **Simplified Implementation vs Brief Requirements**
- **Brief Expected**: 100-point system with ML-like scoring across 5 categories
- **Implemented**: 100-point system with basic but extensible scoring
- **Reason**: Timeboxed for MVP, focused on architecture over algorithm complexity
- **Future Enhancement**: Ready for ML integration via separate scoring service

#### **Scoring Categories (Current)**
1. **Email Quality (0-25 points)**
   - Valid format: 5 points
   - Known domains (gmail, yahoo): 10 points
   - Corporate domains: 20 points
   - Custom validation rules extensible

2. **Phone Quality (0-20 points)**
   - Valid format: 10 points
   - International format: +10 points
   - Mobile vs landline detection: planned enhancement

3. **Message Analysis (0-30 points)**
   - Length and detail: 5-15 points
   - Intent keywords (buy, purchase, financing): +15 points
   - Sentiment analysis: planned enhancement

4. **Listing Context (0-15 points)**
   - High-value listings: +10 points
   - Recent listings: +5 points
   - Dealer reputation: planned enhancement

5. **Behavioral Signals (0-10 points)**
   - Business hours submission: +5 points
   - Source quality (website > api): +5 points
   - Device fingerprinting: planned enhancement

#### **Status Assignment Logic**
- **0-39**: `new` - Basic lead requiring qualification
- **40-69**: `qualified` - Good potential, priority follow-up
- **70-100**: `hot` - High priority, immediate contact

### **Performance Requirements**

#### **Response Time Targets**
- **P95 ≤ 400ms**: Warm cache for GET /api/v1/cars
- **P95 ≤ 200ms**: Cache hits for repeated requests
- **Current Achievement**: ~150ms average on warm cache

#### **Database Optimization**
- **Indexes**: Composite indexes on filter combinations
- **Query Strategy**: Eager loading to prevent N+1
- **Pagination**: Strict 50-item limit to prevent memory issues

### **Security Considerations**

#### **API Key Authentication**
- **Decision**: Simple header-based API keys for admin endpoints
- **Reason**: Adequate for admin-only access, simpler than OAuth
- **Implementation**: `X-Api-Key` header with hash_equals() comparison
- **Tradeoff**: Less sophisticated than JWT but sufficient for use case

#### **Rate Limiting Strategy**
- **Leads API**: 5 requests/hour per IP+email combination
- **Cars API**: 60 requests/minute per IP
- **Decision Rationale**: Prevents abuse while allowing legitimate usage
- **Alternative Considered**: User-based limiting (requires authentication)

#### **Input Validation**
- **Approach**: Form Request classes with comprehensive rules
- **XSS Protection**: JSON-only responses eliminate XSS vectors
- **SQL Injection**: Eloquent ORM provides automatic protection

### **Queue & Background Processing**

#### **Lead Scoring Queue**
- **Decision**: Asynchronous processing via Redis queues
- **Reason**: Maintains API response times while complex scoring occurs
- **Fallback**: Database queues for development environments
- **Monitoring**: Job failure handling with retry logic

### **Data Architecture**

#### **Database Schema Decisions**
- **Listing Events**: JSON payload for flexibility vs normalized tables
- **Reason**: Event data varies significantly by type
- **Tradeoff**: Less queryable but more adaptable

#### **Price Storage**
- **Decision**: Store prices in cents (bigInteger)
- **Reason**: Avoids floating-point precision issues
- **Alternative**: Decimal type (rejected for performance)

### **API Design Philosophy**

#### **Pagination Strategy**
- **Max 50 items**: Prevents memory exhaustion and slow responses
- **Cursor-based**: Considered but standard offset pagination chosen for simplicity
- **Meta Information**: Comprehensive pagination metadata in responses

#### **Error Handling**
- **Consistent Envelope**: All responses use status/message/data format
- **Correlation IDs**: Enable end-to-end request tracing
- **HTTP Status Codes**: Proper semantic usage throughout

### **Development & Testing**

#### **Test Strategy**
- **Feature Tests**: Focus on API behavior and caching
- **Unit Tests**: Critical business logic (lead scoring)
- **Performance Tests**: Validate response time requirements
- **Coverage Goal**: 80%+ on business logic

#### **Environment Configuration**
- **Development**: SQLite for simplicity
- **Production**: MySQL with Redis for optimal performance
- **Queue Workers**: Supervisord recommended for production

### **Deployment Considerations**

#### **Cache Warming Strategy**
- **Application Start**: Warm popular endpoints
- **Cache Invalidation**: Intelligent re-warming on updates
- **CDN Integration**: Headers optimized for Varnish/CloudFlare

#### **Monitoring Requirements**
- **Cache Hit Ratios**: Target >80% for list endpoints
- **Response Times**: P95 monitoring with alerts
- **Queue Health**: Dead job monitoring and alerting

### **Future Enhancements**

#### **Planned Improvements**
1. **ML-based Lead Scoring**: Replace basic algorithm with machine learning
2. **Advanced Faceting**: ElasticSearch integration for complex filtering
3. **Real-time Updates**: WebSocket integration for live inventory changes
4. **Geographic Search**: PostGIS integration for location-based queries
5. **A/B Testing**: Framework for algorithm optimization

#### **Scalability Considerations**
- **Database Sharding**: By country_code when volume increases
- **Read Replicas**: For geographic distribution
- **Microservice Split**: Separate lead scoring service
- **CDN Strategy**: Global content distribution

### **Compliance & Standards**

#### **GDPR Considerations**
- **Data Retention**: 7-year lead retention policy
- **Right to Deletion**: Soft delete with anonymization
- **Data Export**: API endpoints for user data export

#### **API Versioning**
- **Current**: v1 prefix for all endpoints
- **Strategy**: Semantic versioning for breaking changes
- **Backward Compatibility**: Maintain v1 for 12 months after v2 release

---

## **Summary**

These decisions prioritize:
1. **Performance**: Cache-first architecture with sub-400ms response times
2. **Reliability**: Comprehensive error handling and monitoring
3. **Maintainability**: Clean architecture patterns and extensive testing
4. **Scalability**: Redis-based caching and queue system ready for growth

The implementation focuses on production-ready fundamentals while maintaining flexibility for future enhancements.