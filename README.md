# Nimbus

![Nimbus - Integrated API Client With a Touch of Magic](./art/nimbus-cover.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sunchayn/nimbus.svg?style=flat-square)](https://packagist.org/packages/sunchayn/nimbus)
[![License](https://img.shields.io/packagist/l/sunchayn/nimbus.svg?style=flat-square)](https://packagist.org/packages/sunchayn/nimbus)
[![PHP Version](https://img.shields.io/packagist/php-v/sunchayn/nimbus.svg?style=flat-square)](https://packagist.org/packages/sunchayn/nimbus)
[![codecov](https://codecov.io/github/sunchayn/nimbus/graph/badge.svg?token=IPMYSPI2T4)](https://codecov.io/github/sunchayn/nimbus)

**An integrated, in-browser API client for Laravel with a touch of magic.**  
Nimbus automatically analyzes your routes and validation rules to build interactive request schemas and a native interface for testing and exploring your APIs.

<img src="./art/nimbus-demo-video.gif" style="border: 1px solid black;border-right:0;">

---

## Why Nimbus?

Traditional API testing tools require manual setup for every endpoint. Nimbus removes that friction by automatically discovering your Laravel routes, generating schemas from validation rules, and handling authentication, cookies, and test data. All without leaving your development environment.

### What Nimbus Is NOT

Nimbus is **NOT** an API documentation generator like Swagger or Scribe. It doesn't produce customer-facing API documentation. Instead, it's a **developer-focused API playground** designed to improve your iteration speed while building and testing APIs.

## Key Features

- Automatic Discovery: Routes and schemas generated directly from your Laravel `FormRequest`, `SpatieData` classes and inline validation rules.
- Built-in, polished interface for inspecting API endpoints in your browser.
- Shareable Links: Capture a request state (headers, body, auth) and send it to a colleague.
- Safe Testing (Transaction Mode): Run a request and automatically roll back database changes. Test `DELETE` or `UPDATE` endpoints without dirtying your data.
- OpenAPI as a First-Class Citizen: Use your OpenAPI schema to super-charge discovery while retaining Nimbus's automatic detection for undocumented routes.
- Magic `dd()` Handling: Intercepts `dd()` calls and renders them in a paginated window that doesn't break your UI.
- Multi-Application Support: Switch between different APIs (e.g., `rest-api`, `admin-api`) within the same interface.
- Special Authentication: 
    - Act as the currently logged-in user.
    - Impersonate any user by ID.
    - Bearer and Basic Auth support.
- Global headers automatically applied to every request.
- Value Generators: One-click payload population with realistic test data (UUIDs, names, emails, etc.).

---

## Quick Start

### 1. Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x

### 2. Installation

```bash
composer require sunchayn/nimbus
```

### 3. Setup

```bash
php artisan vendor:publish --tag=nimbus-assets --tag=nimbus-config
```

### 4. Access Nimbus

Start your Laravel application and navigate to:

```
http://your-app.test/nimbus
```

That's it! Nimbus will automatically discover your API routes and their validation schemas.

#### Using Sail or Built-in Server?

If you're using Laravel Sail or `php artisan serve`, Nimbus requires a workaround because these are single-threaded servers. The relay endpoint will hang waiting for API requests that can't be processed on the same thread.

**Solution:** Run two server instances on different ports and configure Nimbus to use the second instance for API requests.

See the detailed guide: [Making Nimbus work with single-threaded servers](wiki/user-guide/README.md#making-nimbus-work-with-single-threaded-servers)
## Documentation

- **[User Guide](wiki/user-guide/README.md)** - Complete guide on using Nimbus's interface, features, and troubleshooting.
- **[Contributor Guide](wiki/contribution-guide/README.md)** - Architecture overview and development guidelines.

## Security Considerations
- **Development Only**: Nimbus is designed for local development environments. Do not deploy it to production servers.
- **User Impersonation**: The impersonation feature allows making requests as any user. Ensure Nimbus is only accessible in trusted development environments.

## Alpha Release Notice

Nimbus is currently an **alpha**. You may encounter unexpected behaviors or bugs. All feedback is welcome:

- Report bugs: [Open an issue](https://github.com/sunchayn/nimbus/issues/new/choose)
- Share ideas: [Start a discussion](https://github.com/sunchayn/nimbus/discussions/categories/ideas)
- Ask questions: [Q&A discussions](https://github.com/sunchayn/nimbus/discussions/categories/q-a)

## License

Nimbus is open-source software licensed under the [MIT license](LICENSE.md).
