<img src="art/nimbus-logo.png" width="220"/>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sunchayn/nimbus.svg?style=flat-square)](https://packagist.org/packages/sunchayn/nimbus)
[![Total Downloads](https://img.shields.io/packagist/dt/sunchayn/nimbus.svg?style=flat-square)](https://packagist.org/packages/sunchayn/nimbus)
[![License](https://img.shields.io/packagist/l/sunchayn/nimbus.svg?style=flat-square)](https://packagist.org/packages/sunchayn/nimbus)
[![PHP Version](https://img.shields.io/packagist/php-v/sunchayn/nimbus.svg?style=flat-square)](https://packagist.org/packages/sunchayn/nimbus)
<a href="https://packagist.org/packages/sunchayn/nimbus"><img src="https://badge.laravel.cloud/badge/sunchayn/nimbus?style=flat" alt="Laravel versions"></a>
[![codecov](https://codecov.io/github/sunchayn/nimbus/graph/badge.svg?token=IPMYSPI2T4)](https://codecov.io/github/sunchayn/nimbus)

**The framework-aware HTTP client built specifically for Laravel.**

Nimbus reads your application routes, validation rules, and data transfer objects directly from your codebase through static analysis. It provides an integrated in-browser workspace where you can test endpoints, inspect database queries, authenticate as any user, and safely roll back database changes.

<img src="./art/nimbus-demo-video.gif">

---

## Why Nimbus?

Traditional API clients such as Postman or Bruno operate outside your application container. They require manual URL construction, duplicate header management, repetitive token refreshing, and separate database cleanup scripts.

Nimbus runs directly inside your local development environment. Because it understands Laravel contracts and conventions, it eliminates setup friction entirely:

* **Zero Endpoint Configuration:** Nimbus parses your routes and validation schemas on load. You never have to manually copy endpoints or construct JSON bodies from scratch.
* **Deep Framework Integration:** Authenticate as any user across any guard with one click, decrypt cookies on the fly, and inspect queries directly beside the HTTP response.
* **Safe State:** Test destructive requests inside transaction rollbacks to keep your local database clean.

## What Nimbus Is NOT

Nimbus is **NOT** an API documentation generator like Swagger, Scribe, or Scramble. It does not produce customer-facing documentation or public API portals.

Nimbus is also not an all-purpose external API client. It makes intentional trade-offs to deliver deep Laravel integration rather than broad, generic protocol coverage.

_For a detailed feature breakdown contrasting Nimbus with Postman, Swagger, and Telescope, see the [Tool Comparison](#comparison-with-other-tools) below._

## Key Features

- **Zero-Config Route & Schema Discovery:** Automatically extracts routes, parameters, and types from Form Requests, Data Transfer Objects, and inline validation rules without executing application code.
- **Actor Impersonation Across Any Guard:** Authenticate as any user across any guard with support for integer IDs, UUIDs, and ULIDs without creating session records or altering database rows. Includes an interactive actor search picker.
- **Multi-Connection Database Rollback:** Run potentially destructive requests (POST, PUT, DELETE) inside database transactions that automatically roll back across all configured database connections upon completion.
- **Real-Time Request Insights:** Inspect executed database queries, execution times, N+1 query warnings, application logs, and dispatched events directly beside your response on a single screen.
- **Debug Interception:** Intercepts native debug statements cleanly, rendering them in a dedicated, paginated viewer.
- **Git-Backed Workspaces:** Save pre-configured requests as plain text files directly in your repository, enabling review and collaboration through Git.
- **Native MCP Server for AI Agents:** Built-in Model Context Protocol server enabling AI coding assistants to discover routes, inspect schemas, and verify endpoint behavior safely inside database rollbacks.
- **Request Sandbox & Time Travel:** Mock outbound HTTP calls, mail delivery, queued jobs, and notifications per request, or simulate requests at specific dates and times.
- **Laravel Developer Toolbox:** Built-in utilities to decrypt session cookies, verify and create temporary signed URLs, decode JWT and Sanctum tokens, and test password hashes.
- **Persistent Request History & Autofill:** Search past requests, restore previous interface states, and populate request bodies instantly with realistic test data.
- **OpenAPI Specification support:** Consumes OpenAPI specifications when present, merging external documentation with internal route detection.

## Installation

### Requirements
* PHP 8.2 or higher
* Laravel 10.x, 11.x, 12.x, or 13.x

### 1. Install via Composer
```bash
composer require --dev sunchayn/nimbus
```

### 2. Publish Assets and Config
```bash
php artisan vendor:publish --tag=nimbus-assets --tag=nimbus-config
```

### 3. Open Nimbus
Start your application and navigate to:
```text
http://your-app.test/nimbus
```

> [!NOTE]
> **Single-Threaded Server Environments**
> When running with `php artisan serve` or Laravel Sail, concurrent relay requests can encounter timeouts due to PHP's single-threaded nature.
> Refer to the [Single-Threaded Setup Guide](wiki/user-guide/README.md#making-nimbus-work-with-single-threaded-servers) for recommended workarounds.

## Documentation

* **[User Guide](wiki/user-guide/README.md):** Comprehensive interface walkthrough and troubleshooting.
* **[Contributor Guide](wiki/contribution-guide/README.md):** Architecture overview, AST extractors, and local workbench setup.


## Comparison With Other Tools

| Capability | Nimbus | Generic Clients (Postman, Bruno) | Doc Generators (Swagger, Scribe) | Diagnostics (Laravel Telescope) |
| :--- | :---: | :---: | :---: | :---: |
| **Zero-Config Route & Rule Discovery** | **Yes (AST Analysis)** | No (Manual setup) | Yes (Generates static docs) | No (Request log only) |
| **Interactive In-Browser Request Builder** | **Yes** | Yes | Interactive playground only | No |
| **Actor Impersonation Across Any Guard** | **Yes (Zero DB Side Effects)** | Manual tokens | No | No |
| **Multi-Connection Database Rollback** | **Yes (Automatic)** | No | No | No |
| **In-Line Request Insights (Queries & Logs)** | **Yes (Beside Response)** | No | No | Separate dashboard |
| **Interception of `dd()`** | **Yes (Dedicated Viewer)** | Partial (renders html) | No | Intercepts dumps only |
| **MCP Server for AI Agents** | Yes | Yes | No | No |
| **Git-Backed Workspaces** | **Yes** | Partial | Not applicable | Not applicable |
| **Arbitrary External Third-Party APIs** | No (Local Host Only) | **Yes** | No | Outbound HTTP logs only |
| **Multi-Protocol (GraphQL, gRPC, WebSockets)** | No (HTTP Only) | **Yes** | Partial | No |
| **Custom JavaScript Test Scripting Engine** | No (Declarative Only) | **Yes** | No | No |
| **Headless CI/CD Collection Runner** | No (Local UI & MCP Only) | **Yes (Newman / CLI)** | Partial (Spec linting) | No |
| **Hosted Cloud Sync & Team Workspaces** | No (Git Only) | **Yes** | Hostable static sites | Self-hosted dashboard |
| **Customer-Facing Public Documentation** | No | No | **Yes** | No |
| **Production Environment Monitoring** | No (Local Dev Only) | Monitors only | No | **Yes (Staging / Production)** |

## Security

* **Local Development Only:** Nimbus is designed strictly for local development and testing environments. Do not enable it on publicly accessible production servers.
* **Actor Impersonation:** The impersonation feature allows executing requests as any user in your system. Ensure Nimbus remains accessible only on trusted development environments.

## License

Nimbus is open-source software licensed under the [MIT license](LICENSE.md).
