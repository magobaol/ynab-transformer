# ADR-003: Production Error Handling and Exception Management

## Status
Accepted

## Context
The YNAB Transformer web application was experiencing a critical production issue where users uploading unsupported file formats (particularly in Firefox) would see:

1. A brief "Error processing file: HTTP 422: Unprocessable Content" message
2. An immediate redirect to the Symfony exception page
3. Full exposure of PHP code, stack traces, and debug information
4. Poor user experience and potential security concerns

The issue was caused by the application throwing HTTP exceptions (`BadRequestHttpException`, `UnprocessableEntityHttpException`) which triggered Symfony's default exception handling, resulting in redirects to debug pages in production.

## Decision
We will implement a comprehensive error handling strategy that:

1. **Replace exception throwing with JSON responses** in the TransformController
2. **Add a custom ExceptionListener** to intercept and handle exceptions gracefully
3. **Configure production-specific settings** to prevent debug information exposure
4. **Enhance client-side error handling** to properly display user-friendly messages

## Implementation Details

### 1. Controller-Level Error Handling
- Modified `TransformController::transform()` to return `JsonResponse` instead of throwing exceptions
- All error conditions now return structured JSON with appropriate HTTP status codes:
  - 400 for missing files or invalid CSRF tokens
  - 422 for invalid file types or processing errors
  - 429 for rate limiting violations

### 2. Custom Exception Listener
Created `App\EventListener\ExceptionListener` that:
- Intercepts exceptions for API requests (AJAX, POST to /transform, JSON Accept headers)
- Returns JSON error responses instead of allowing Symfony debug pages
- Logs exceptions for debugging while hiding them from users
- Maintains security by not exposing internal application details

### 3. Production Configuration
- Added production-specific framework configuration to disable debug mode
- Configured monolog for proper error logging without exposing sensitive information
- Registered the ExceptionListener as a high-priority event listener

### 4. Client-Side Error Handling
- Enhanced JavaScript to properly parse JSON error responses
- Added fallback handling for non-JSON responses
- Improved user experience with clear, actionable error messages
- **Prevented traditional form submissions** by removing form element and using AJAX-only approach
- **Added professional progress overlay** with spinner and progress bar for better UX
- **Enhanced drag-and-drop functionality** with proper file validation and automatic upload

## Consequences

### Positive
- ✅ **No more redirects** - Users stay on the same page when errors occur
- ✅ **No PHP code exposure** - Debug information is never shown to users
- ✅ **Better user experience** - Clear, user-friendly error messages
- ✅ **Production security** - No sensitive information leakage
- ✅ **Cross-browser compatibility** - Consistent behavior across all browsers
- ✅ **Proper logging** - Errors are logged for debugging without user exposure
- ✅ **Professional progress UI** - Users see a polished progress overlay during processing
- ✅ **AJAX-only uploads** - No traditional form submissions that could cause redirects
- ✅ **Enhanced file handling** - Better drag-and-drop with automatic upload

### Negative
- **Slightly more complex code** - Additional error handling logic required
- **Additional configuration** - More configuration files to maintain
- **Testing overhead** - Need to test both success and error scenarios

### Risks
- **Low risk** - Changes are backward compatible and don't affect successful operations
- **Maintenance** - Need to ensure error messages remain user-friendly
- **Monitoring** - Should monitor error logs to identify recurring issues

## Alternatives Considered

### 1. Symfony Error Pages
- **Rejected**: Would still expose framework information and require custom error pages
- **Reason**: Doesn't solve the redirect issue and adds complexity

### 2. Global Exception Handler
- **Rejected**: Too broad and could interfere with other application functionality
- **Reason**: Need more granular control over which requests get JSON responses

### 3. Middleware Approach
- **Rejected**: Would require more complex request/response handling
- **Reason**: Controller-level handling is more straightforward and maintainable

## Implementation Status
- ✅ TransformController modified to return JSON responses
- ✅ ExceptionListener created and registered
- ✅ Production configuration added
- ✅ Client-side error handling enhanced
- ✅ **Form submission prevention implemented** - AJAX-only uploads
- ✅ **Professional progress UI added** - Spinner and progress bar overlay
- ✅ **Enhanced file handling** - Improved drag-and-drop functionality
- ✅ **Code cleanup completed** - Removed all debugging artifacts
- ✅ Comprehensive tests added and passing
- ✅ All existing functionality preserved

## Related ADRs
- ADR-001: Web Interface Architecture
- ADR-002: Web Interface Implementation Plan

## References
- [Symfony Exception Handling](https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-listener)
- [HTTP Status Codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)
- [JSON API Error Handling](https://jsonapi.org/format/#errors)
