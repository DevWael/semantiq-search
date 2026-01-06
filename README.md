# SemantiQ Search

Vector-based semantic search for WordPress using Qdrant and local embeddings.

## Overview

SemantiQ Search is a production-grade WordPress plugin that transforms your blog into an intelligent search engine. Instead of keyword matching, it uses AI-powered semantic understanding to find truly relevant articles based on meaning, not just words.

**Key Features:**
- 🔍 Semantic search via vector embeddings
- ⚡ Real-time sync with Qdrant vector database
- 🎯 Related article suggestions
- 📊 Admin dashboard with sync controls
- 🔧 Per-post manual sync + auto-sync on update
- 💻 WP-CLI commands for batch operations
- 🛡️ Enterprise-grade security & error handling
- 🔌 Extensible with WordPress hooks

## Requirements

- **PHP:** 7.4 or higher
- **WordPress:** 5.9 or higher
- **Qdrant:** Vector database (local or cloud)
- **Embedding API:** Local HTTP endpoint that generates embeddings

### Optional
- **ACF (Advanced Custom Fields):** For custom field indexing

## Installation

### 1. Install via Composer (Recommended)

```bash
# Clone the plugin repository
git clone https://github.com/yourusername/semantiq-search.git wp-content/plugins/semantiq-search

# Install dependencies
cd wp-content/plugins/semantiq-search
composer install
```

### 2. Manual Installation

1. Download the plugin from GitHub
2. Extract to `wp-content/plugins/semantiq-search`
3. Run `composer install` in the plugin directory
4. Activate the plugin in WordPress admin

### 3. Activate in WordPress

1. Go to **Plugins** in WordPress admin
2. Find "SemantiQ Search"
3. Click **Activate**
4. Go to **Settings → SemantiQ Search** to configure

## Configuration

### 1. Configure Qdrant

In the plugin settings:

1. **Qdrant Host:** Your Qdrant server address (e.g., `qdrant.example.com`)
2. **Port:** Qdrant port (default: 6333)
3. **API Key:** Optional API key for authentication
4. **Collection Name:** Qdrant collection name (default: `wordpress_posts`)
5. Click **Test Qdrant Connection** to verify

### 2. Configure Embedding API

1. **Endpoint URL:** Your local embedding API (e.g., `http://localhost:8000/embed`)
2. **API Key:** Optional API key if your embedding service requires auth
3. **Model Name:** Name of the embedding model (for reference)
4. Click **Test Embedding API** to verify

### 3. Select Post Types

Check which post types to index:
- Posts
- Pages
- Custom post types (if enabled)

### 4. Configure Custom Fields (Optional)

Add ACF field groups or meta keys to include in embeddings:
- Select from registered ACF field groups
- Add manual meta keys
- Set field weights (default: 1.0)

## Usage

### Bulk Sync (Admin Dashboard)

1. Go to **SemantiQ Search → Bulk Sync**
2. Click **Start Sync**
3. Watch real-time progress bar
4. Sync completes and shows summary

### Per-Post Sync

1. Edit any post
2. Scroll to **Vector Search Sync** meta box
3. Click **Re-sync to Vector DB**
4. See inline success/error message
5. Last synced timestamp updates automatically

### Auto-Sync

When you publish or update a post, it automatically syncs to Qdrant (if configured).

### Search API

**Endpoint:** `POST /wp-json/vector-search/v1/search`

```bash
curl -X POST https://example.com/wp-json/vector-search/v1/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "best practices for sustainable farming",
    "limit": 10,
    "post_types": ["post", "page"]
  }'
```

**Response:**
```json
{
  "success": true,
  "results": {
    "post": [
      {
        "id": 123,
        "title": "Organic Farming Guide",
        "excerpt": "Learn the fundamentals...",
        "score": 0.92,
        "url": "https://example.com/article/",
        "featured_image": "https://example.com/image.jpg"
      }
    ],
    "page": []
  }
}
```

### Frontend Search Form

Add the search form to your theme template:

```php
<?php do_action('semantiq_search_form'); ?>
```

Or manually include:

```php
<?php get_template_part('search-form-semantiq'); ?>
```

The form sends AJAX requests to the search API and displays results grouped by post type.

## WP-CLI Commands

### Sync Posts

Sync all posts with progress bar:

```bash
wp vector-search sync
```

With options:

```bash
# Sync specific batch size
wp vector-search sync --batch-size=100

# Sync specific post types
wp vector-search sync --post-type=post,page

# Resume from offset
wp vector-search sync --offset=250
```

### Health Check

Verify all connections are working:

```bash
wp vector-search health
```

Output:

```
SemantiQ Search - Health Check
═════════════════════════════════

Qdrant Configuration:
  ✓ Connection: OK
  ✓ Collection exists: wordpress_posts
  ✓ Points in collection: 523

Local Embedding API:
  ✓ Endpoint reachable
  ✓ Response time: 245 ms
  ✓ Vector size: 384

Plugin Configuration:
  ✓ Post types: post, page
  ✓ Batch size: 50

Overall Status: ✓ Healthy
```

## Extensibility

### Hooks

Customize behavior via WordPress hooks:

```php
// Filter enabled post types
add_filter('semantiq_enabled_post_types', function($post_types) {
    return array_merge($post_types, ['custom_post_type']);
});

// Modify text before embedding
add_filter('semantiq_post_embedding_text', function($text, $post_id) {
    return $text . ' Additional context...';
}, 10, 2);

// Modify search results
add_filter('semantiq_search_results', function($results, $query) {
    // Custom sorting or filtering
    return $results;
}, 10, 2);

// Listen to sync events
add_action('semantiq_after_sync_post', function($post_id, $post, $embedding) {
    // Do something after post syncs
}, 10, 3);
```

### Custom Embedding Provider

Implement the `EmbeddingProviderInterface` to support different embedding services:

```php
class CustomEmbeddingProvider implements EmbeddingProviderInterface {
    public function embed(string $text): array { }
    public function test_connection(): bool { }
    public function get_vector_size(): int { }
    public function get_model_name(): string { }
}
```

## Architecture

The plugin follows enterprise-grade WordPress development standards:

- **OOP Design:** Factory, Repository, Singleton patterns
- **Type Safety:** PHP 7.4+ strict typing
- **Security:** Nonces, capability checks, input sanitization
- **Modularity:** Clean separation of concerns
- **Extensibility:** Hooks and filters throughout
- **Error Handling:** Comprehensive logging and user feedback

See the full [Development Plan](DEVELOPMENT.md) for detailed architecture documentation.

## Troubleshooting

### Qdrant Connection Failed

1. Verify Qdrant is running: `curl http://localhost:6333/health`
2. Check host/port in settings match your Qdrant instance
3. Verify API key (if required)
4. Check firewall rules if Qdrant is remote

### Embedding API Timeout

1. Verify embedding endpoint is reachable
2. Check embedding model is loaded (not still downloading)
3. Increase batch size or reduce post content length
4. Monitor embedding API logs

### Sync Stuck/Slow

1. Reduce batch size in settings (try 10-25)
2. Check server memory usage
3. Monitor embedding API performance
4. Try WP-CLI command instead: `wp vector-search sync --batch-size=10`

### Sync Fails for Specific Posts

1. Check **SemantiQ Search → Bulk Sync** error log
2. Review WordPress debug log: `wp-content/debug.log`
3. Check post content for special characters or encoding issues
4. Try re-syncing individual post from edit screen

## Performance Tips

1. **Batch Size:** Default 50 is good for most sites. Reduce if memory-constrained.
2. **Schedule Sync:** Use WP Cron to sync during off-peak hours
3. **Cache Results:** Search results are transient-cached
4. **Optimize Posts:** Shorter, clearer post content = better embeddings
5. **Use WP-CLI:** Better for large sites (1000+ posts)

## Security

- API credentials stored encrypted in `wp_options`
- All admin actions protected with nonces
- Capability checks on all operations
- Input validation and sanitization
- SQL injection prevention via `$wpdb->prepare()`
- No external API calls expose sensitive data

## Debugging

Enable WordPress debug logging:

**wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs: `wp-content/debug.log`

### Common Log Messages

```
[2026-01-06 10:15:23] SemantiQ Search: INFO - Syncing post ID 123
[2026-01-06 10:15:24] SemantiQ Search: ERROR - Embedding timeout after 30s
[2026-01-06 10:15:25] SemantiQ Search: WARNING - Qdrant response slow (5000ms)
```

## Development

### Setup Development Environment

```bash
# Clone repository
git clone https://github.com/yourusername/semantiq-search.git
cd semantiq-search

# Install dependencies
composer install

# Run code style checks
composer run phpcs

# Run static analysis
composer run phpstan
```

### Project Structure

```
semantiq-search/
├── src/               # Plugin source code
├── views/             # Template files
├── assets/            # CSS and JavaScript
├── languages/         # Translation files
├── tests/             # Unit tests (future)
├── semantiq-search.php # Plugin entry point
└── composer.json      # Dependencies
```

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/my-feature`
5. Submit a pull request

## License

This plugin is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Support

- **Issues:** Report bugs on [GitHub Issues](https://github.com/yourusername/semantiq-search/issues)
- **Documentation:** Full docs in [DEVELOPMENT.md](DEVELOPMENT.md)
- **WP-CLI Help:** `wp vector-search --help`

## Changelog

### Version 1.0.0 (Initial Release)

- ✨ Semantic search with Qdrant integration
- ✨ Bulk sync with AJAX progress tracking
- ✨ Per-post manual and auto-sync
- ✨ Search REST API with result grouping
- ✨ WP-CLI sync and health check commands
- ✨ Settings page with connection testing
- ✨ Comprehensive error handling and logging
- ✨ Production-ready security and architecture

## Credits

Built with ❤️ using [Qdrant](https://qdrant.tech/) and [tenqz/qdrant](https://github.com/tenqz/qdrant).

---

**Ready to transform your WordPress search?** Install SemantiQ Search today and help your users discover content by meaning, not just keywords.