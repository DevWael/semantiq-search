# SemantiQ Search - WordPress Plugin Development Plan

**Plugin Name:** SemantiQ Search  
**Purpose:** Vector-based semantic search for WordPress using Qdrant and local embeddings  
**Version:** 1.0.0  
**PHP Minimum:** 7.4  
**WordPress Minimum:** 5.9  
**License:** MIT (or your choice)

---

## 1. Project Overview

SemantiQ Search is a production-grade WordPress plugin that enables semantic search capabilities by:
- Converting blog posts to vector embeddings using a local API
- Syncing embeddings to Qdrant vector database
- Providing semantic search via REST API
- Offering related article suggestions
- Delivering an AJAX-powered search interface

The plugin is built with enterprise-grade architecture: OOP design patterns, strict typing, PSR-4 autoloading, comprehensive error handling, and WordPress best practices.

---

## 2. Architecture & File Structure

### 2.1 Directory Layout

```
semantiq-search/
├── composer.json                    # Dependency management
├── composer.lock
├── README.md
├── LICENSE
├── semantiq-search.php              # Plugin entry point
├── uninstall.php                    # Cleanup on uninstall
├── assets/
│   ├── css/
│   │   ├── admin.css               # Admin panel styles
│   │   └── search-form.css         # Frontend search form styles
│   └── js/
│       ├── admin-sync.js           # Admin sync page polling
│       ├── admin-settings.js       # Settings page interactions
│       ├── search-form.js          # AJAX search form handler
│       └── post-meta-box.js        # Per-post sync button handler
├── src/
│   ├── SemantiQ.php                # Main singleton class
│   ├── Admin/
│   │   ├── SettingsPage.php        # Settings page UI
│   │   ├── SyncPage.php            # Bulk sync admin page
│   │   ├── PostMetaBox.php         # Per-post sync meta box
│   │   ├── PostListColumns.php     # Sync status in posts list
│   │   └── AdminNotices.php        # Error/success notifications
│   ├── API/
│   │   ├── SearchEndpoint.php      # REST search endpoint
│   │   ├── SyncPostEndpoint.php    # Per-post sync endpoint
│   │   └── SyncBatchEndpoint.php   # Batch sync progress endpoint
│   ├── CLI/
│   │   ├── SyncCommand.php         # WP-CLI sync command
│   │   └── HealthCommand.php       # WP-CLI health check
│   ├── Core/
│   │   ├── Config.php              # Configuration management
│   │   ├── Logger.php              # Custom logging
│   │   └── Exceptions.php          # Custom exceptions
│   ├── Database/
│   │   ├── QdrantClient.php        # Qdrant API wrapper
│   │   └── PostMetaRepository.php  # Post meta access layer
│   ├── Embedding/
│   │   ├── EmbeddingProviderInterface.php
│   │   └── LocalEmbeddingProvider.php
│   ├── Sync/
│   │   ├── SyncManager.php         # Orchestrates all syncing
│   │   ├── PostSyncer.php          # Single post sync logic
│   │   ├── BatchProcessor.php      # Batch processing with resume
│   │   └── SyncStateManager.php    # Progress tracking via transients
│   ├── Search/
│   │   ├── SearchService.php       # Search query handling
│   │   ├── QueryBuilder.php        # Build Qdrant queries
│   │   └── ResultFormatter.php     # Format results by post type
│   └── Hooks/
│       ├── AdminHooks.php          # Admin-related actions/filters
│       ├── PostHooks.php           # Post save_post hooks
│       ├── RESTHooks.php           # REST API registration
│       └── CLIHooks.php            # WP-CLI command registration
├── views/
│   ├── admin/
│   │   ├── settings-page.php       # Settings page template
│   │   ├── sync-page.php           # Bulk sync page template
│   │   ├── post-meta-box.php       # Meta box template
│   │   └── admin-notices.php       # Notice templates
│   └── frontend/
│       └── search-form.php         # Search form template
├── languages/
│   └── semantiq-search.pot         # Translation file
└── tests/                          # (Optional for future)
    └── bootstrap.php
```

### 2.2 Namespace Structure

All classes follow `SemantiQ\[Module]\ClassName` pattern:

- `SemantiQ\SemantiQ` - Main singleton
- `SemantiQ\Admin\SettingsPage`
- `SemantiQ\API\SearchEndpoint`
- `SemantiQ\CLI\SyncCommand`
- `SemantiQ\Database\QdrantClient`
- `SemantiQ\Embedding\LocalEmbeddingProvider`
- `SemantiQ\Sync\SyncManager`
- `SemantiQ\Search\SearchService`
- etc.

---

## 3. Core Features & Implementation Details

### 3.1 Settings Page (`src/Admin/SettingsPage.php`)

**Functionality:**
- WordPress Settings API for configuration management
- Stores in `wp_options` with `semantiq_` prefix

**Fields:**

```
Qdrant Configuration:
- Host URL (text input)
- Port (number input, default 6333)
- API Key (password input, optional)
- Collection Name (text input, default "wordpress_posts")
- "Test Qdrant Connection" button (appears after save)

Local Embedding API:
- Endpoint URL (text input, e.g., http://localhost:8000/embed)
- API Key (password input, optional)
- Model Name (text input, for reference)
- "Test Embedding API" button (appears after save)

Post Types:
- Checkboxes listing all public post types (post, page, etc.)
- Only checked types are synced and searched

Custom Fields Configuration:
- Multi-select of available ACF field groups
- Manual meta key input
- Field weight settings (for weighting in embeddings)
- Preview of concatenated content before embedding

Advanced:
- Batch size for syncing (default 50)
- Embedding vector dimensions (informational)
```

**Validation & Hooks:**
- `sanitize_callback` for all inputs using WordPress functions
- `validate_callback` to check format before saving
- Apply filters: `apply_filters('semantiq_enabled_post_types', $types)`
- Apply filters: `apply_filters('semantiq_custom_fields', $fields)`

### 3.2 Bulk Sync Admin Page (`src/Admin/SyncPage.php`)

**UI Components:**

```
┌─────────────────────────────────────────────────┐
│ SemantiQ Search - Bulk Synchronization         │
├─────────────────────────────────────────────────┤
│                                                 │
│ Status: Ready to sync                           │
│                                                 │
│ [Start Sync]                [Cancel]           │
│                                                 │
│ Progress: ███████░░░░░░░░░░░░░░ 35%           │
│                                                 │
│ Processed: 175 / 500 posts                     │
│ Estimated time remaining: 2:30                 │
│ Processing rate: 3.5 posts/sec                 │
│                                                 │
│ [Show Errors] (15 failed posts)                │
│                                                 │
│ ✓ Posts (120/150 synced)                       │
│ ✓ Pages (55/55 synced)                         │
│ ⚠ Products (0/295 synced)                      │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Functionality:**
- Pre-flight validation (check APIs are configured and accessible)
- AJAX polling mechanism to fetch batch processing status
- Real-time progress bar with percentage
- Stats display: processed count, total, rate, ETA
- Error log showing failed post IDs with reasons
- "Retry Failed" button to reprocess errored posts
- Support for resuming interrupted syncs
- Displays by-post-type summary

**JavaScript Flow:**
1. Admin clicks "Start Sync"
2. Initializes transient with progress state
3. Triggers first batch via `POST /wp-json/vector-search/v1/sync-batch`
4. Polls `/wp-json/vector-search/v1/sync-status` every 500ms
5. Updates progress bar and stats
6. When complete, shows summary

**Error Handling:**
- Display banner if Qdrant not configured
- Display banner if embedding API unavailable
- Log individual post failures without stopping batch
- Provide actionable error messages

### 3.3 Per-Post Sync Management

#### 3.3.1 Post Edit Meta Box (`src/Admin/PostMetaBox.php`)

```
┌───────────────────────────────┐
│ Vector Search Sync            │
├───────────────────────────────┤
│                               │
│ ● Last Synced:                │
│   2026-01-06 10:15:23         │
│                               │
│ [Re-sync to Vector DB]       │
│                               │
│ ✓ Sync successful             │
│                               │
│ ⏳ Syncing... (spinner)       │
│                               │
└───────────────────────────────┘
```

**Functionality:**
- Displays last sync timestamp from `_vector_search_synced` post meta
- Manual "Re-sync" button triggers AJAX call
- Shows inline success/error messages
- Disabled during sync with loading spinner
- Status indicators (up-to-date, outdated, never synced, failed)

**Hooks:**
- `add_meta_boxes` to register meta box
- AJAX handler for manual sync (nonce-protected)
- Capability check: `edit_post`

#### 3.3.2 Auto-Sync on Save (`src/Hooks/PostHooks.php`)

**Hook:** `save_post_{post_type}` priority 20

**Logic:**
```php
function auto_sync_post($post_id, $post, $update) {
    // Skip if autosave/revision/non-published
    if (DOING_AUTOSAVE || !is_published($post) || 
        !is_enabled_post_type($post->post_type)) {
        return;
    }
    
    // Unhook to prevent infinite loop
    remove_action('save_post', 'auto_sync_post', 20);
    
    // Sync to Qdrant
    $sync_manager->sync_single_post($post_id);
    
    // Update sync timestamp
    update_post_meta($post_id, '_vector_search_synced', 
        current_time('timestamp'));
    
    // Re-hook
    add_action('save_post', 'auto_sync_post', 20, 3);
}
```

**Error Handling:**
- Catch exceptions and log to debug log
- Show admin notice if sync fails (non-blocking)
- Continue post save regardless of sync outcome

#### 3.3.3 Posts List Actions (`src/Admin/PostListColumns.php`)

**Columns Added:**
- "Vector Sync" column with:
  - Color-coded status indicator (● green/orange/gray/red)
  - Quick "Re-sync" button
  - Last sync timestamp

**Bulk Actions:**
- Add "Sync to Vector DB" bulk action
- Process selected posts sequentially
- Show admin notice with count of synced posts

**Status Indicators:**
- ● Green = Synced and up-to-date (last_synced > post_modified)
- ● Orange = Post modified after sync (last_synced < post_modified)
- ○ Gray = Never synced (no last_synced meta)
- ● Red = Sync failed (has error in meta)

---

## 4. REST API Endpoints

### 4.1 Search Endpoint

**Route:** `POST /wp-json/vector-search/v1/search`

**Request:**
```json
{
  "query": "best practices for sustainable farming",
  "limit": 10,
  "post_types": ["post", "page"],
  "filters": {
    "min_score": 0.5
  }
}
```

**Response:**
```json
{
  "success": true,
  "query": "best practices for sustainable farming",
  "total_results": 15,
  "results": {
    "post": [
      {
        "id": 123,
        "title": "Organic Farming Guide",
        "excerpt": "Learn the fundamentals of organic farming...",
        "score": 0.92,
        "post_type": "post",
        "url": "https://example.com/organic-farming-guide/",
        "featured_image": "https://..."
      },
      {
        "id": 456,
        "title": "Sustainable Agriculture Tips",
        "excerpt": "10 practical tips for sustainable farming...",
        "score": 0.88,
        "post_type": "post",
        "url": "https://example.com/sustainable-tips/",
        "featured_image": "https://..."
      }
    ],
    "page": [
      {
        "id": 789,
        "title": "About Our Farm",
        "excerpt": "We practice sustainable methods...",
        "score": 0.75,
        "post_type": "page",
        "url": "https://example.com/about/",
        "featured_image": null
      }
    ]
  }
}
```

**Implementation (`src/API/SearchEndpoint.php`):**
- Register via `register_rest_route()`
- Input validation and sanitization
- Capability: public or `read_posts`
- Query building via `QueryBuilder`
- Result formatting via `ResultFormatter`
- Results grouped by `post_type` key
- Each result includes relevance score for frontend display

### 4.2 Per-Post Sync Endpoint

**Route:** `POST /wp-json/vector-search/v1/sync-post/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Post synced successfully",
  "post_id": 123,
  "synced_at": "2026-01-06 10:15:23"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Qdrant connection failed: timeout",
  "code": "qdrant_error"
}
```

**Implementation (`src/API/SyncPostEndpoint.php`):**
- Capability check: `edit_post`
- Validate post exists and is enabled type
- Call `SyncManager::sync_single_post()`
- Return result or error
- Log errors for debugging

### 4.3 Batch Sync Progress Endpoint

**Route:** `POST /wp-json/vector-search/v1/sync-batch` (called repeatedly)

**Request:**
```json
{
  "offset": 0,
  "batch_size": 50
}
```

**Response:**
```json
{
  "success": true,
  "processed_count": 50,
  "total_posts": 500,
  "current_offset": 50,
  "is_complete": false,
  "status": "Processing batch 2/10",
  "errors": []
}
```

**When Complete:**
```json
{
  "success": true,
  "processed_count": 500,
  "total_posts": 500,
  "current_offset": 500,
  "is_complete": true,
  "status": "Sync completed successfully",
  "errors": [
    {"post_id": 45, "error": "Embedding generation timeout"},
    {"post_id": 67, "error": "Qdrant upsert failed"}
  ]
}
```

**Implementation (`src/API/SyncBatchEndpoint.php`):**
- Capability: `manage_options`
- Query posts with pagination (offset, batch_size)
- Respect enabled post types from settings
- Process embeddings and Qdrant upsert
- Store progress in transient (3600s TTL)
- Collect errors without stopping
- Return continue/completion status

### 4.4 Sync Status Endpoint

**Route:** `GET /wp-json/vector-search/v1/sync-status`

**Response:**
```json
{
  "total": 500,
  "processed": 175,
  "status": "Processing batch 3/10",
  "errors": 2,
  "is_running": true
}
```

**Implementation (`src/API/SearchStatusEndpoint.php`):**
- Retrieve progress transient
- No capability check needed (read-only status)
- Return formatted sync state

---

## 5. Sync Architecture

### 5.1 Sync Manager (`src/Sync/SyncManager.php`)

**Responsibilities:**
- Orchestrate all sync operations
- Validate configuration and connections
- Choose between single-post, batch, or bulk sync
- Delegate to appropriate handler
- Error collection and reporting

**Key Methods:**
```php
public function sync_single_post($post_id): void
public function sync_batch($offset = 0, $batch_size = 50): array
public function get_progress(): array
public function cancel_sync(): void
public function get_enabled_post_types(): array
public function get_enabled_fields(): array
```

### 5.2 Post Syncer (`src/Sync/PostSyncer.php`)

**Responsibilities:**
- Extract post content (title, content, excerpt)
- Extract meta/ACF fields based on settings
- Concatenate fields for embedding
- Call embedding provider
- Format for Qdrant
- Handle single post sync errors

**Process:**
```
1. Fetch post and enabled meta fields
2. Sanitize content (strip HTML, decode entities)
3. Concatenate into single text with field labels
4. Call LocalEmbeddingProvider::embed($text)
5. Create Qdrant point with payload (post_id, post_type, etc.)
6. Upsert to Qdrant collection
7. Update post meta (_vector_search_synced timestamp)
8. Return success or throw exception
```

### 5.3 Batch Processor (`src/Sync/BatchProcessor.php`)

**Responsibilities:**
- Paginate through posts using `WP_Query`
- Process each post via `PostSyncer`
- Collect errors without stopping
- Respect memory limits with cache cleanup
- Support resume from offset

**Logic:**
```php
public function process_batch($offset, $batch_size, $post_types): array {
    // Get posts for this batch
    $posts = get_posts_batch($offset, $batch_size, $post_types);
    
    $processed = 0;
    $errors = [];
    
    foreach ($posts as $post) {
        try {
            $this->syncer->sync_post($post->ID);
            $processed++;
        } catch (Exception $e) {
            $errors[] = [
                'post_id' => $post->ID,
                'error' => $e->getMessage()
            ];
        }
        
        // Clean object cache to prevent bloat
        wp_cache_flush();
    }
    
    return [
        'processed' => $processed,
        'errors' => $errors,
        'next_offset' => $offset + $batch_size
    ];
}
```

### 5.4 Sync State Manager (`src/Sync/SyncStateManager.php`)

**Responsibilities:**
- Store/retrieve progress via transients
- Track total posts, processed count, errors
- Resume capability using transients

**Storage:**
```
Transient: 'semantiq_sync_progress'
TTL: 3600 seconds

{
  'total': 500,
  'processed': 175,
  'offset': 175,
  'errors': [
    {'post_id': 45, 'error': 'Timeout'},
    ...
  ],
  'status': 'Processing batch 3/10',
  'started_at': 1641475800,
  'last_updated': 1641475920
}
```

---

## 6. Database & Vector Store

### 6.1 Qdrant Client (`src/Database/QdrantClient.php`)

**Wrapper around tenqz/qdrant library**

**Responsibilities:**
- Initialize connection using Config
- Create/manage collections
- Insert/update/delete points
- Execute search queries
- Health checks

**Methods:**
```php
public function connect(): bool
public function test_connection(): bool
public function create_collection($name, $vector_size): bool
public function upsert_points($collection, $points): bool
public function search($collection, $vector, $limit, $filters): array
public function get_collection_info($collection): array
public function delete_point($collection, $point_id): bool
```

**Collection Structure:**
```
Collection: "wordpress_posts" (configurable)
Vector Size: 384 (from embedding model)
Distance: Cosine

Point Structure:
{
  "id": 123,  // WordPress post ID
  "vector": [0.123, 0.456, ...],  // Embedding
  "payload": {
    "post_id": 123,
    "post_type": "post",
    "post_title": "Article Title",
    "post_excerpt": "Short excerpt...",
    "post_url": "https://example.com/article/",
    "featured_image_url": "https://...",
    "post_author": "admin",
    "post_date": "2026-01-06",
    "field_1": "value1",
    "field_2": "value2"
  }
}
```

### 6.2 Post Meta Repository (`src/Database/PostMetaRepository.php`)

**Abstraction for post meta operations**

**Methods:**
```php
public function get_sync_timestamp($post_id): ?int
public function set_sync_timestamp($post_id, $timestamp): void
public function get_sync_error($post_id): ?string
public function set_sync_error($post_id, $error): void
public function clear_sync_error($post_id): void
public function get_post_for_sync($post_id): ?array
public function get_posts_batch($offset, $limit, $post_types): array
```

---

## 7. Embedding Provider

### 7.1 Embedding Provider Interface (`src/Embedding/EmbeddingProviderInterface.php`)

```php
interface EmbeddingProviderInterface {
    public function embed(string $text): array;
    public function test_connection(): bool;
    public function get_vector_size(): int;
    public function get_model_name(): string;
}
```

### 7.2 Local Embedding Provider (`src/Embedding/LocalEmbeddingProvider.php`)

**Calls the local embedding API**

**Implementation:**
- Make HTTP POST request to configured endpoint
- Pass text in request body
- Handle optional API key authentication
- Parse response and extract embedding vector
- Error handling with retries

**Request Example:**
```json
{
  "text": "Article title. Full article content with all fields...",
  "model": "sentence-transformers/all-MiniLM-L6-v2"
}
```

**Response Example:**
```json
{
  "embedding": [0.123, 0.456, ...],
  "model": "sentence-transformers/all-MiniLM-L6-v2",
  "tokens": 45
}
```

**Error Handling:**
- Timeout (30s max)
- Connection refused
- Invalid JSON response
- Model errors
- Throw descriptive exceptions

---

## 8. Search & Query

### 8.1 Search Service (`src/Search/SearchService.php`)

**Responsibilities:**
- Handle search requests
- Validate input
- Call QueryBuilder
- Execute search via QdrantClient
- Format results via ResultFormatter

**Flow:**
```
1. Receive search query text
2. Validate: not empty, reasonable length
3. Generate embedding via EmbeddingProvider
4. Build Qdrant query via QueryBuilder
5. Execute search
6. Format results by post_type
7. Return structured response
```

### 8.2 Query Builder (`src/Search/QueryBuilder.php`)

**Builds Qdrant search request**

**Features:**
- Construct vector search query
- Apply post_type filters
- Apply score threshold filter
- Set limit and offset
- Include payload in results

**Example:**
```php
$query = (new QueryBuilder())
    ->setVector($embedding)
    ->setLimit(10)
    ->addFilter('post_type', ['post', 'page'])
    ->setMinScore(0.5)
    ->build();
```

### 8.3 Result Formatter (`src/Search/ResultFormatter.php`)

**Formats raw Qdrant results for API response**

**Responsibilities:**
- Group results by post_type
- Extract relevant post data
- Add metadata (title, excerpt, URL, featured image)
- Sort by relevance score
- Format for frontend consumption

**Output:**
```json
{
  "post": [
    {
      "id": 123,
      "title": "Post Title",
      "excerpt": "Short excerpt...",
      "score": 0.92,
      "post_type": "post",
      "url": "https://example.com/post/",
      "featured_image": "https://example.com/image.jpg"
    }
  ],
  "page": [...]
}
```

---

## 9. WP-CLI Commands

### 9.1 Sync Command (`src/CLI/SyncCommand.php`)

**Usage:**
```bash
wp vector-search sync [--batch-size=50] [--offset=0] [--post-type=post,page]
```

**Features:**
- Terminal progress bar using `WP_CLI\Utils\make_progress_bar()`
- Batch-based processing
- Memory-safe (cleanup between batches)
- Configurable batch size
- Filter by post type(s)
- Resume capability via offset

**Output Example:**
```
Starting sync...
Syncing posts: 45% [██████████░░░░░░░░] 0:15 / 0:33

✓ Synced 225 posts successfully
✗ 5 posts failed
✓ Completed in 33 seconds
```

**Error Handling:**
- `WP_CLI::error()` for fatal issues (no API configured)
- `WP_CLI::warning()` for non-fatal issues (some posts failed)
- Detailed error log with post IDs

### 9.2 Health Command (`src/CLI/HealthCommand.php`)

**Usage:**
```bash
wp vector-search health
```

**Checks:**
1. **Qdrant Connection**
   - Host reachable
   - API key valid (if configured)
   - Collection exists
   - Collection stats

2. **Embedding API Connection**
   - Endpoint reachable
   - Test embedding generation
   - Optional API key working
   - Response format valid

3. **Plugin Configuration**
   - Post types configured
   - Custom fields valid
   - Database tables present (if any)

**Output Example:**
```
SemantiQ Search - Health Check
═════════════════════════════════

Qdrant Configuration:
  ✓ Connection: OK (6333 ms response)
  ✓ Authentication: OK
  ✓ Collection exists: wordpress_posts
  ✓ Points in collection: 523

Local Embedding API:
  ✓ Endpoint reachable: http://localhost:8000/embed
  ✓ Response time: 245 ms
  ✓ Vector size: 384
  ✓ Model: sentence-transformers/all-MiniLM-L6-v2

Plugin Configuration:
  ✓ Post types: post, page, product
  ✓ Custom fields: 5 fields configured
  ✓ Batch size: 50

Overall Status: ✓ Healthy
```

**Error Output Example:**
```
SemantiQ Search - Health Check
═════════════════════════════════

Qdrant Configuration:
  ✗ Connection failed: Connection refused
  ✗ Please check host and port in settings

Local Embedding API:
  ✓ Endpoint reachable
  ⚠ Response time: 5000 ms (slow)

Overall Status: ✗ Issues found
Run: wp vector-search health --verbose
```

---

## 10. Hooks & Extensibility

### 10.1 Actions (Admin Hooks - `src/Hooks/AdminHooks.php`)

```php
// Fired before syncing a post
do_action('semantiq_before_sync_post', $post_id, $post);

// Fired after syncing a post
do_action('semantiq_after_sync_post', $post_id, $post, $embedding);

// Fired when sync fails
do_action('semantiq_sync_error', $post_id, $exception);

// Fired on batch completion
do_action('semantiq_batch_synced', $processed_count, $errors);

// Fired on full sync completion
do_action('semantiq_sync_complete', $total_synced, $total_errors);
```

### 10.2 Filters (Admin Hooks)

```php
// Modify enabled post types for sync
apply_filters('semantiq_enabled_post_types', $post_types);

// Modify fields to include in embedding
apply_filters('semantiq_custom_fields', $fields, $post_id);

// Modify text before embedding
apply_filters('semantiq_post_embedding_text', $text, $post_id, $fields);

// Modify Qdrant point before upsert
apply_filters('semantiq_qdrant_point', $point, $post_id);

// Modify search results
apply_filters('semantiq_search_results', $results, $query);
```

### 10.3 REST Hooks (`src/Hooks/RESTHooks.php`)

```php
// Modify search request before processing
apply_filters('semantiq_search_request', $request_params);

// Modify search response
apply_filters('semantiq_search_response', $response, $query);
```

### 10.4 CLI Hooks (`src/Hooks/CLIHooks.php`)

```php
// Register custom WP-CLI commands
do_action('semantiq_register_cli_commands');
```

---

## 11. Configuration Management

### 11.1 Config Class (`src/Core/Config.php`)

**Responsibilities:**
- Centralize all configuration retrieval
- Provide defaults and validation
- Support environment variable overrides

**Methods:**
```php
public static function get_qdrant_host(): string
public static function get_qdrant_port(): int
public static function get_qdrant_api_key(): ?string
public static function get_qdrant_collection(): string

public static function get_embedding_api_url(): string
public static function get_embedding_api_key(): ?string
public static function get_embedding_model(): string

public static function get_enabled_post_types(): array
public static function get_enabled_fields(): array
public static function get_batch_size(): int

public static function is_configured(): bool
public static function validate_qdrant_config(): array // returns ['valid' => bool, 'errors' => array]
public static function validate_embedding_config(): array
```

**Option Keys:**
```php
'semantiq_qdrant_host'        // qdrant.example.com
'semantiq_qdrant_port'        // 6333
'semantiq_qdrant_api_key'     // (encrypted if available)
'semantiq_qdrant_collection'  // wordpress_posts

'semantiq_embedding_url'      // http://localhost:8000/embed
'semantiq_embedding_api_key'  // (optional)
'semantiq_embedding_model'    // sentence-transformers/all-MiniLM-L6-v2

'semantiq_post_types'         // ['post', 'page']
'semantiq_custom_fields'      // ['field_1', 'field_2']
'semantiq_batch_size'         // 50
```

---

## 12. Error Handling & Logging

### 12.1 Custom Exceptions (`src/Core/Exceptions.php`)

```php
class QdrantException extends \Exception {}
class EmbeddingException extends \Exception {}
class ConfigurationException extends \Exception {}
class ValidationException extends \Exception {}
class SyncException extends \Exception {}
```

### 12.2 Logger Class (`src/Core/Logger.php`)

**Wrapper around WordPress debug logging**

**Methods:**
```php
public static function info(string $message, array $context = []): void
public static function warning(string $message, array $context = []): void
public static function error(string $message, array $context = []): void
public static function debug(string $message, array $context = []): void
```

**Output to:** `wp-content/debug.log` (when `WP_DEBUG_LOG` enabled)

**Format:**
```
[2026-01-06 10:15:23] SemantiQ Search: INFO - Syncing post ID 123
[2026-01-06 10:15:24] SemantiQ Search: ERROR - Qdrant upsert failed: Connection timeout
[2026-01-06 10:15:25] SemantiQ Search: WARNING - Embedding API response slow (5000ms)
```

### 12.3 Error Handling Patterns

**In Sync Operations:**
```php
try {
    $embedding = $embedding_provider->embed($text);
    $qdrant->upsert_points($collection, [$point]);
    update_post_meta($post_id, '_vector_search_synced', time());
} catch (EmbeddingException $e) {
    Logger::error('Embedding failed', ['post_id' => $post_id, 'error' => $e->getMessage()]);
    update_post_meta($post_id, '_vector_search_error', $e->getMessage());
    throw new SyncException("Sync failed for post {$post_id}");
} catch (QdrantException $e) {
    Logger::error('Qdrant error', ['post_id' => $post_id, 'error' => $e->getMessage()]);
    throw new SyncException("Qdrant upsert failed");
}
```

**In REST Endpoints:**
```php
public function search_callback($request) {
    try {
        // validate, search, format
        return new WP_REST_Response($results, 200);
    } catch (Exception $e) {
        Logger::error('Search failed', ['query' => $query, 'error' => $e->getMessage()]);
        return new WP_REST_Response([
            'error' => 'Search temporarily unavailable',
            'code' => 'search_error'
        ], 503);
    }
}
```

**User-Facing Messages:**
- API errors shown as admin notices
- User-friendly messages in frontend (generic "Search error occurred")
- Detailed logs for debugging

---

## 13. Security

### 13.1 Nonce Verification

**All admin/AJAX actions:**
```php
check_ajax_referer('semantiq_sync_post_' . $post_id);
check_admin_referer('semantiq_settings_nonce');
```

### 13.2 Capability Checks

```php
// Settings page
current_user_can('manage_options')

// Per-post sync
current_user_can('edit_post', $post_id)

// Bulk sync admin page
current_user_can('manage_options')

// REST endpoints
'permission_callback' => function() {
    return current_user_can('read_posts');
}
```

### 13.3 Data Validation & Sanitization

**Settings page inputs:**
```php
'sanitize_callback' => 'sanitize_text_field'  // URLs, text
'sanitize_callback' => 'sanitize_key'        // Field names
'sanitize_callback' => 'wp_json_encode'      // JSON
'validate_callback' => function($value) {
    return is_numeric($value) && $value > 0;
}
```

**REST endpoint inputs:**
```php
'sanitize_callback' => 'sanitize_text_field'
'validate_callback' => 'rest_validate_request_arg'
```

**Database queries:**
```php
$wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d", $post_id)
```

### 13.4 API Key Handling

- Store API keys in `wp_options` with proper sanitization
- Never output API keys in admin UI (show masked values)
- Use password input type in forms
- Consider encrypting at rest if needed (future enhancement)
- Never log full API keys (truncate in debug logs)

---

## 14. Frontend Search Form

### 14.1 Search Form (`views/frontend/search-form.php`)

```html
<form id="semantiq-search-form" class="semantiq-search-form">
    <input 
        type="text" 
        id="semantiq-search-input"
        class="semantiq-search-input"
        placeholder="Search articles..."
        autocomplete="off"
    />
    <button type="submit" class="semantiq-search-btn">Search</button>
</form>

<div id="semantiq-results" class="semantiq-results"></div>

<script>
// Handled by assets/js/search-form.js
</script>
```

### 14.2 AJAX Search Handler (`assets/js/search-form.js`)

**Functionality:**
- Debounce input for performance
- Fetch from `/wp-json/vector-search/v1/search`
- Display results grouped by post type
- Show loading state
- Handle errors gracefully
- Keyboard navigation support

**Result Display Template:**
```html
<div class="semantiq-results-section">
  <h3>Posts (5 results)</h3>
  <div class="semantiq-results-list">
    <article class="semantiq-result post">
      <img src="..." class="semantiq-result-image" />
      <div>
        <h4><a href="...">Post Title</a></h4>
        <p>Post excerpt...</p>
        <span class="semantiq-score">92% relevant</span>
      </div>
    </article>
    <!-- more results -->
  </div>
</div>
```

---

## 15. Code Standards & Best Practices

### 15.1 PHP Standards

**All PHP files start with:**
```php
<?php
declare(strict_types=1);

namespace SemantiQ\Module;
```

**Class Structure:**
```php
class ClassName implements InterfaceNameInterface {
    private string $property1;
    private int $property2;
    
    public function __construct(DependencyClass $dependency) {
        $this->dependency = $dependency;
    }
    
    public function publicMethod(): string {
        // Implementation
    }
    
    private function privateHelper(): void {
        // Implementation
    }
}
```

**Type Hints:**
```php
public function process(int $post_id, array $fields): ?array {
    // Every parameter and return typed
}
```

**Docblocks:**
```php
/**
 * Synchronize a single post to Qdrant.
 *
 * @param int   $post_id  The WordPress post ID
 * @param array $fields   Fields to include in embedding
 * 
 * @return bool True if sync succeeded, false otherwise
 * 
 * @throws SyncException If sync fails
 * 
 * @since 1.0.0
 */
public function sync_single_post(int $post_id, array $fields): bool {
    // ...
}
```

### 15.2 WordPress Standards

- Use `wp_*` functions for all WordPress operations
- Use `$wpdb->prepare()` for all queries
- Use nonces for all admin/AJAX actions
- Use capabilities for permission checks
- Follow WordPress coding style (snake_case, 4-space indents)

### 15.3 OOP Design Patterns

**Singleton:**
```php
class SemantiQ {
    private static ?self $instance = null;
    
    private function __construct() {}
    
    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

**Factory:**
```php
class ClientFactory {
    public static function create_qdrant_client(Config $config): QdrantClient {
        return new QdrantClient($config);
    }
}
```

**Repository:**
```php
interface PostRepositoryInterface {
    public function get(int $id): ?Post;
    public function get_batch(int $offset, int $limit): array;
    public function save(Post $post): void;
}
```

**Dependency Injection:**
```php
public function __construct(
    private QdrantClient $qdrant_client,
    private EmbeddingProvider $embedding_provider,
    private Config $config
) {}
```

---

## 16. Database Schema

### 16.1 Post Meta Fields

**Post meta added/used:**
```
_vector_search_synced       (int)    Timestamp of last successful sync
_vector_search_error        (string) Error message if sync failed
```

**Queries:**
```sql
-- Find all posts that need syncing (modified after sync)
SELECT ID FROM wp_posts 
WHERE post_modified > (
    SELECT meta_value FROM wp_postmeta 
    WHERE post_id = ID AND meta_key = '_vector_search_synced'
)

-- Find posts that failed to sync
SELECT post_id FROM wp_postmeta 
WHERE meta_key = '_vector_search_error'
```

### 16.2 Qdrant Collection Schema

**Collection name:** `wordpress_posts` (configurable)

**Point structure:**
- ID: WordPress post ID
- Vector: 384-dimensional embedding
- Payload:
  - post_id (int)
  - post_type (string)
  - post_title (string)
  - post_excerpt (string)
  - post_url (string)
  - featured_image_url (string, nullable)
  - post_author (string)
  - post_date (string, ISO 8601)
  - custom_fields (dynamic, based on config)

---

## 17. Deployment & Activation

### 17.1 Plugin Entry Point (`semantiq-search.php`)

```php
<?php
/**
 * Plugin Name: SemantiQ Search
 * Plugin URI: https://github.com/yourusername/semantiq-search
 * Description: Vector-based semantic search for WordPress using Qdrant
 * Version: 1.0.0
 * Author: Your Name
 * License: MIT
 * Text Domain: semantiq-search
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires WP: 5.9
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap plugin
SemantiQ\SemantiQ::get_instance()->init();

// Register activation hook
register_activation_hook(__FILE__, function() {
    SemantiQ\SemantiQ::get_instance()->activate();
});

// Register deactivation hook
register_deactivation_hook(__FILE__, function() {
    SemantiQ\SemantiQ::get_instance()->deactivate();
});
```

### 17.2 Activation Hook (`src/SemantiQ.php`)

```php
public function activate(): void {
    // Verify PHP version
    if (version_compare(phpversion(), '7.4', '<')) {
        wp_die('SemantiQ Search requires PHP 7.4 or higher');
    }
    
    // Create default options
    add_option('semantiq_qdrant_port', 6333);
    add_option('semantiq_qdrant_collection', 'wordpress_posts');
    add_option('semantiq_batch_size', 50);
    add_option('semantiq_post_types', ['post', 'page']);
    
    // Flush rewrite rules for REST endpoints
    flush_rewrite_rules();
    
    // Set activation notice
    set_transient('semantiq_activated', true, 5 * 60);
}
```

### 17.3 Uninstall Hook (`uninstall.php`)

```php
<?php
// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove all plugin options
delete_option('semantiq_qdrant_host');
delete_option('semantiq_qdrant_port');
delete_option('semantiq_qdrant_api_key');
delete_option('semantiq_qdrant_collection');
delete_option('semantiq_embedding_url');
delete_option('semantiq_embedding_api_key');
delete_option('semantiq_post_types');
delete_option('semantiq_custom_fields');
delete_option('semantiq_batch_size');

// Clean up post meta
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} 
     WHERE meta_key IN ('_vector_search_synced', '_vector_search_error')"
);

// Remove transients
delete_transient('semantiq_sync_progress');
```

---

## 18. Composer Configuration

### 18.1 composer.json

```json
{
    "name": "your-vendor/semantiq-search",
    "description": "Vector-based semantic search for WordPress using Qdrant",
    "type": "wordpress-plugin",
    "license": "MIT",
    "authors": [
        {
            "name": "Your Name",
            "email": "your@email.com"
        }
    ],
    "require": {
        "php": "^7.4",
        "tenqz/qdrant": "^1.0"
    },
    "require-dev": {
        "phpstan/phpstan": "^1.0",
        "squizlabs/php_codesniffer": "^3.7"
    },
    "autoload": {
        "psr-4": {
            "SemantiQ\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "SemantiQ\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "phpstan": "phpstan analyse src/",
        "phpcs": "phpcs --standard=WordPress src/",
        "phpcbf": "phpcbf --standard=WordPress src/"
    }
}
```

---

## 19. Testing Strategy (Future)

### 19.1 Unit Tests

- QdrantClient connection and methods
- EmbeddingProvider API calls
- SearchService query building
- SyncManager orchestration
- Config validation

### 19.2 Integration Tests

- Full sync workflow (post to Qdrant)
- Search endpoint response
- Admin page rendering
- WP-CLI commands

### 19.3 Manual Testing Checklist

- [ ] Settings page save/test buttons work
- [ ] Bulk sync completes without errors
- [ ] Per-post sync button works
- [ ] Auto-sync on post update works
- [ ] Search API returns grouped results
- [ ] Frontend search form displays results
- [ ] WP-CLI commands execute correctly
- [ ] Error messages display appropriately
- [ ] Progress bar shows accurate percentages

---

## 20. Development Roadmap

### Phase 1: Core (MVP)
- [ ] Settings page with Qdrant & embedding API config
- [ ] Connection test buttons
- [ ] Post type selection checkboxes
- [ ] Bulk sync admin page with progress
- [ ] Per-post manual sync button
- [ ] Auto-sync on save_post
- [ ] Search REST endpoint
- [ ] AJAX search form
- [ ] WP-CLI sync command
- [ ] Error handling & logging

### Phase 2: Enhancement
- [ ] WP-CLI health check command
- [ ] Custom field/ACF indexing UI
- [ ] Related articles suggestion endpoint
- [ ] Search result filtering by post type (frontend)
- [ ] Admin dashboard with index stats
- [ ] Bulk actions in posts list

### Phase 3: Advanced (Future)
- [ ] Support multiple embedding providers (OpenAI, etc.)
- [ ] Query analytics and popular searches
- [ ] Multi-language support
- [ ] Advanced search filters (date range, author, etc.)
- [ ] Content gap detection
- [ ] Performance optimizations (caching, indexing)
- [ ] Admin UI for testing search relevance
- [ ] Unit & integration tests

---

## 21. Documentation Requirements

- [ ] README.md with installation and usage
- [ ] CHANGELOG.md for releases
- [ ] Code comments for complex logic
- [ ] Inline docblocks for all classes/methods
- [ ] Developer hooks documentation
- [ ] WP-CLI command documentation
- [ ] REST API endpoint documentation
- [ ] Troubleshooting guide

---

## 22. Important Notes

### Security
- All API credentials stored in wp_options (consider encryption for sensitive keys in future)
- Nonces on all admin/AJAX actions
- Capability checks on all operations
- Input validation and sanitization throughout
- SQL injection prevention via wpdb->prepare()

### Performance
- Batch processing prevents memory exhaustion
- Polling-based sync allows browser navigation
- Results caching via transients
- WP-CLI for large-scale operations
- Configurable batch size for server tuning

### Compatibility
- PHP 7.4+ with strict typing
- WordPress 5.9+ (for REST API improvements)
- Composer autoloading (no manual requires)
- No external JavaScript dependencies
- Works with standard WordPress installations

### Scalability
- Stateless design (no server session state)
- Progress stored in transients (scalable)
- REST API allows headless usage
- Support for distributed WordPress setups
- Horizontal scaling compatible

---

## Conclusion

This development plan provides a comprehensive roadmap for building SemantiQ Search as a production-ready, enterprise-grade WordPress plugin. The architecture emphasizes clean code, security, extensibility, and excellent user experience through thoughtful UI/UX design and clear error handling.

The modular structure allows for incremental development, starting with the MVP in Phase 1 and expanding with advanced features as needed. All code follows WordPress and PHP best practices, ensuring maintainability and compatibility with the broader WordPress ecosystem.