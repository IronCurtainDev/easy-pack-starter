# API Key Authentication

The easy-pack package includes API key authentication middleware to protect your API endpoints with static API keys.

## Features

- Support for single or multiple API keys
- Simple header-based authentication using `x-api-key`
- Easy configuration via environment variables
- Consistent error responses for unauthorized requests

## Setup

### 1. Configure Environment Variables

Add the following to your `.env` file:

```bash
# Enable/disable API key authentication
API_ACTIVE=true

# One or more API Keys (comma-separated for multiple keys)
API_KEY="your-secret-api-key-here"
```

**Multiple API Keys Example:**
```bash
API_KEY="key1-for-mobile-app,key2-for-web-app,key3-for-admin-panel"
```

### 2. Apply Middleware to Routes

The middleware is registered as `api.key` and can be applied to routes or route groups.

#### Option A: Apply to specific routes

```php
Route::get('/protected-endpoint', [YourController::class, 'index'])
    ->middleware('api.key');
```

#### Option B: Apply to route groups

```php
Route::prefix('api/v1')->middleware('api.key')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
});
```

#### Option C: Combine with other middleware

```php
Route::middleware(['api.key', 'auth:sanctum'])->group(function () {
    // Routes that require both API key AND user authentication
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

## Usage

### Making API Requests

Include the `x-api-key` header in your requests:

**cURL Example:**
```bash
curl -H "x-api-key: your-secret-api-key-here" \
     https://yourapp.com/api/v1/protected-endpoint
```

**JavaScript Example:**
```javascript
fetch('https://yourapp.com/api/v1/protected-endpoint', {
    headers: {
        'x-api-key': 'your-secret-api-key-here'
    }
})
```

**PHP Example:**
```php
$client = new \GuzzleHttp\Client();
$response = $client->get('https://yourapp.com/api/v1/protected-endpoint', [
    'headers' => [
        'x-api-key' => 'your-secret-api-key-here'
    ]
]);
```

## Error Responses

### Missing API Key

**Request:**
```bash
curl https://yourapp.com/api/v1/protected-endpoint
```

**Response (401):**
```json
{
    "result": false,
    "message": "An API Key is required",
    "type": "INVALID_PARAMETER_API_KEY"
}
```

### Invalid API Key

**Request:**
```bash
curl -H "x-api-key: invalid-key" \
     https://yourapp.com/api/v1/protected-endpoint
```

**Response (401):**
```json
{
    "result": false,
    "message": "A valid API Key is required",
    "type": "INVALID_PARAMETER_API_KEY"
}
```

## Security Best Practices

1. **Use Strong Keys**: Generate cryptographically secure random keys:
   ```bash
   php -r "echo base64_encode(random_bytes(32));"
   ```

2. **Rotate Keys Regularly**: Change your API keys periodically and when compromised

3. **Use HTTPS**: Always use HTTPS to prevent key interception

4. **Environment-Specific Keys**: Use different keys for development, staging, and production

5. **Key Management**: Store keys securely and never commit them to version control

6. **Multiple Keys for Different Clients**: Use different keys for mobile apps, web apps, and third-party integrations to easily revoke access if needed

## Feature Toggle

Control API key authentication based on environment using the `API_ACTIVE` configuration:

```php
// In your routes or middleware logic
if (config('features.api.active')) {
    // API is active
}
```

## Combination with Sanctum

You can use API key authentication alongside Laravel Sanctum for hybrid authentication:

```php
// Public endpoints - API key only
Route::middleware('api.key')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected endpoints - API key + user authentication
Route::middleware(['api.key', 'auth:sanctum'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});
```

## Testing

When writing tests, you can set the API key in your test case:

```php
public function test_protected_endpoint()
{
    $response = $this->withHeaders([
        'x-api-key' => config('easypack.api_key'),
    ])->getJson('/api/v1/protected-endpoint');

    $response->assertStatus(200);
}
```

## Advanced: Custom Middleware Configuration

If you need to customize the middleware behavior, you can publish it and modify:

1. Copy the middleware from the package to your app:
   ```bash
   cp vendor/easypack/starter/src/Http/Middleware/ApiAuthenticate.php \
      app/Http/Middleware/ApiAuthenticate.php
   ```

2. Update the namespace in your copied file

3. Register your custom middleware in `bootstrap/app.php`:
   ```php
   $router->aliasMiddleware('api.key', \App\Http\Middleware\ApiAuthenticate::class);
   ```

## Troubleshooting

### Middleware not working

1. Clear config cache: `php artisan config:clear`
2. Check that `API_KEY` is set in `.env`
3. Verify the middleware is applied to your routes: `php artisan route:list`

### Getting 401 errors with valid key

1. Check for extra whitespace in your `.env` file
2. Ensure the header name is exactly `x-api-key` (lowercase)
3. Verify you're using the correct key from your environment

## Related Middleware

The package also provides these middleware aliases:

- `api.key` - API key authentication (this document)
- `convert.x-access-token` - Converts `x-access-token` header to Sanctum's `Authorization: Bearer`
- `track.device` - Tracks device activity for authenticated users
