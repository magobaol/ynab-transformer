# Security Configuration

This directory contains security-related configuration files.

## Rate Limiting Whitelist

### Symfony Environment Variable: `RATE_LIMIT_WHITELIST`

IP addresses that are exempt from rate limiting restrictions are configured via Symfony's environment system.

### Format
- Comma-separated list of IP addresses
- Spaces around commas are automatically trimmed
- Empty or unset means no whitelist
- Uses Symfony's standard environment variable processing

### Environment Configuration

**Development (.env file):**
```bash
# Add to your .env file
RATE_LIMIT_WHITELIST=127.0.0.1,192.168.1.100
```

**Production (system environment):**
```bash
export RATE_LIMIT_WHITELIST="203.0.113.1,198.51.100.1,10.0.0.50"
```

**Docker:**
```bash
docker run -e RATE_LIMIT_WHITELIST="127.0.0.1,192.168.1.100" your-app
```

**Docker Compose:**
```yaml
environment:
  - RATE_LIMIT_WHITELIST=127.0.0.1,192.168.1.100
```

**Symfony Configuration (services.yaml):**
```yaml
parameters:
    app.rate_limit.whitelist: '%env(RATE_LIMIT_WHITELIST)%'
```

**Example .env file structure:**
```bash
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=your-secret-key-here
###< symfony/framework-bundle ###

###> app/rate-limiting ###
# Comma-separated list of IP addresses to whitelist (bypass rate limiting)
# Example: RATE_LIMIT_WHITELIST=127.0.0.1,192.168.1.100
RATE_LIMIT_WHITELIST=127.0.0.1
###< app/rate-limiting ###
```

### Management Commands

**Set whitelist for current session:**
```bash
export RATE_LIMIT_WHITELIST="127.0.0.1,192.168.1.100"
```

**Add IP to existing whitelist:**
```bash
export RATE_LIMIT_WHITELIST="${RATE_LIMIT_WHITELIST},192.168.1.200"
```

**Clear whitelist:**
```bash
unset RATE_LIMIT_WHITELIST
```

**Check current whitelist:**
```bash
echo $RATE_LIMIT_WHITELIST
```

### Benefits of Environment-Based Configuration

- ✅ **Environment-specific**: Different whitelists for dev/staging/prod
- ✅ **Secure**: No sensitive IPs in version control
- ✅ **Flexible**: Easy to change without code deployment
- ✅ **Container-friendly**: Works perfectly with Docker/Kubernetes
- ✅ **CI/CD ready**: Can be set in deployment pipelines
- ✅ **12-factor compliant**: Follows best practices

### Notes
- Changes require application restart to take effect
- Whitelisted IPs completely bypass rate limiting
- Supports both IPv4 and IPv6 addresses
- Works with proxy headers (X-Forwarded-For, X-Real-IP)
