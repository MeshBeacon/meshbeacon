# Custom Domains with BunkerWeb

MeshBeacon's default `docker-compose.yml` configures BunkerWeb with a strict "zero-trust" routing mode (`DISABLE_DEFAULT_SERVER=yes`), which drops any requests unless the HTTP `Host` header perfectly matches an allowed domain in the `SERVER_NAME` environment variable. 

By default, this is restricted to `localhost`.

If you point a custom domain (e.g., `mesh.example.com`) to your server without updating BunkerWeb's configuration, BunkerWeb will reject the requests (resulting in connection drops, 400 Bad Request, or 403 Forbidden errors).

## Updating Configuration for Custom Domains

To allow access from a custom domain, you must edit `docker-compose.yml` and add your domain to the BunkerWeb (`waf`) environment variables.

### 1. Locate the WAF Service

Open your `docker-compose.yml` and locate the `waf` service.

### 2. Add your Domain to `SERVER_NAME`

Append your custom domain to the `SERVER_NAME` list (space-separated).

### 3. Add Multisite Reverse Proxy Rules

Because `MULTISITE=yes` is enabled, you must duplicate the `localhost` reverse proxy variables for your custom domain, substituting `localhost` with your actual domain name.

### Example Configuration

```yaml
  waf:
    image: bunkerity/bunkerweb:1.5.8
    # ... other configuration ...
    environment:
      - API_WHITELIST_IP=127.0.0.0/8 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16
      
      # 1. ADD YOUR DOMAIN HERE
      - SERVER_NAME=localhost mesh.example.com
      
      - DISABLE_DEFAULT_SERVER=yes
      - HTTP_PORT=8080
      - MULTISITE=yes
      
      # Localhost routing
      - localhost_USE_REVERSE_PROXY=yes
      - localhost_REVERSE_PROXY_URL=/
      - localhost_REVERSE_PROXY_HOST=http://webserver
      
      # 2. ADD YOUR DOMAIN ROUTING
      - mesh.example.com_USE_REVERSE_PROXY=yes
      - mesh.example.com_REVERSE_PROXY_URL=/
      - mesh.example.com_REVERSE_PROXY_HOST=http://webserver
```

### 4. Restart the Stack

After updating the compose file, apply the changes by restarting the services:

```sh
docker compose down
docker compose up -d
```
