# Contributor Guide

Welcome to the Nimbus contributor guide. This document provides everything you need to understand Nimbus's architecture and contribute effectively.

---

## Table of Contents

**Understanding Nimbus**
- [Architecture Overview](#architecture-overview)
- [Core Concepts](#core-concepts)
- [Design Principles](#design-principles)

**Getting Started**
- [Development Environment Setup](#development-environment-setup)
- [Development Workflow](#development-workflow)
- [Testing Strategy](#testing-strategy)

**Contributing**
- [Contributing Process](#contributing-process)
- [Pull Request Guidelines](#pull-request-guidelines)

**Reference**
- [Coding Standards](#coding-standards)
- [Style Guides](#style-guides)
- [Design System](#design-system)
- [Architecture Rules](#architecture-rules)

---

## Architecture Overview

Nimbus follows a modular architecture that separates concerns into distinct, focused modules. The package automatically extracts validation schemas from Laravel routes and provides an interface for testing them.

### High-Level Architecture

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor': '#ffffff', 'primaryTextColor': '#18181b', 'primaryBorderColor': '#d4d4d8', 'lineColor': '#71717a', 'secondaryColor': '#f4f4f5', 'tertiaryColor': '#e4e4e7'}}}%%
graph TB
    subgraph "Laravel Application"
        LaravelRoutes[Routes] --> LaravelControllers[Controllers]
        LaravelControllers -.-> ValidationRules[Inline Validation Rules]
        LaravelControllers -.-> FormRequests[Form Requests]
    end

    subgraph "Schema Initialization"
        RouteExtractor[Rules Extractor] -->|"Passes Normalized Rules"| SchemaBuilder[Schema Builder]
        SchemaBuilder ----->|"produces"| JSONSchema[JSON Schemas]
    end

    RouteExtractor -.->|"Inspects"| ValidationRules
    RouteExtractor -.->|"Inspects"| FormRequests
    RouteExtractor -.->|"reads"| LaravelRoutes

    subgraph "HTTP Client"
        VueFrontend[Vue Frontend \n <small>HTTP Client</small>]
        VueFrontend -->|Makes Request| RequestRelay[Request Relay]
        RequestRelay --> |"Builds"| HTTPResponses["Relay Response"] -->VueFrontend
    end

    JSONSchema --> VueFrontend

    style LaravelRoutes fill:#ffffff,stroke:#71717a,stroke-width:2px,color:#18181b
    style RouteExtractor fill:#f4f4f5,stroke:#71717a,stroke-width:2px,color:#18181b
    style SchemaBuilder fill:#e4e4e7,stroke:#71717a,stroke-width:2px,color:#18181b
    style VueFrontend fill:#d4d4d8,stroke:#71717a,stroke-width:2px,color:#18181b
    style RequestRelay fill:#a1a1aa,stroke:#71717a,stroke-width:2px,color:#ffffff
```

### Module Structure

#### Backend Modules

```
src/
├── Commands/              # Artisan commands
├── Http/                  # Controllers and middleware
├── Intellisenses/         # TypeScript interface generation
└── Modules/               # Core business logic
    ├── Config/            # Configuration enums and constants
    ├── Routes/            # Route extraction and normalization
    ├── Schemas/           # Schema generation and transformation
    └── Relay/             # HTTP request relay and authorization
```

**Module responsibilities:**

- **Config** - Application configuration, enums, and constants.
- **Routes** - Extract and normalize Laravel routes and validation rules.
- **Schemas** - Transform validation rules into JSON schemas.
- **Relay** - Forward HTTP requests and handle authentication.
- **Intellisenses** - Generate TypeScript interfaces for backend entities.

#### Frontend Structure

```
resources/js/
├── app/                  # Application setup and initialization
├── components/           # Vue components
│   ├── base/             # Thin App wrappers over Nuxt UI (buttons, inputs)
│   ├── common/           # Shared components (modals, dropdowns)
│   ├── domain/           # Business logic components (request builder)
│   └── layout/           # Layout components (panels, navigation)
├── composables/          # Vue composables (reactive logic)
├── stores/               # Pinia stores (global state)
├── utils/                # Utility functions
└── interfaces/           # TypeScript interfaces
```

---

## Core Concepts

### Route Extraction Process

The route extraction process analyzes Laravel routes and extracts normalized validation schemas.

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor': '#ffffff', 'primaryTextColor': '#18181b', 'primaryBorderColor': '#d4d4d8', 'lineColor': '#71717a', 'secondaryColor': '#f4f4f5', 'tertiaryColor': '#e4e4e7'}}}%%
flowchart TD
    LaravelRoutes[Laravel Routes] -->|"filter by prefix"| RouteFiltering[Route Filtering]
    RouteFiltering --> TransformRoute[Normalize Route into `ExtractableRoute` object]

    subgraph SchemaLoop["Loop over ExtractableRoute objects"]
        TransformRoute --> SchemaExtraction[Schema Extraction]
        SchemaExtraction -->|"find matching strategy"| StrategySelection[Strategy Selection]
        StrategySelection -->|"generate JSON schema"| SchemaGeneration[Schema Generation]
        SchemaGeneration --> JSONSchemaOutput[ExtractedRoute object Output]
    end

    RouteFiltering -..-> Fork1@{shape: "fork"}
    Fork1 --> PrefixFilter[Apply Prefix Filter]
    Fork1 --> IgnoredRoutesSub[Exclude Ignored Routes]

    style LaravelRoutes fill:#ffffff,stroke:#71717a,stroke-width:2px,color:#18181b
    style JSONSchemaOutput fill:#f4f4f5,stroke:#71717a,stroke-width:2px,color:#18181b
    style SchemaExtraction fill:#e4e4e7,stroke:#71717a,stroke-width:2px,color:#18181b
    style SchemaLoop fill:#f9fafb,stroke:#d4d4d8,stroke-dasharray: 4 2
```

**Key steps:**

1. **Route Filtering** - Filter routes by configured prefix, exclude ignored routes.
2. **Normalization** - Transform Laravel routes into `ExtractableRoute` objects.
3. **Strategy Selection** - Choose appropriate extraction strategy (Form Request vs inline validation).
4. **Schema Generation** - Convert validation rules to JSON schema.
5. **Output** - Produce `ExtractedRoute` objects with complete metadata.

### Schema Building Process

The schema builder converts Laravel validation rules into JSON Schema format with support for nested structures.

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor': '#ffffff', 'primaryTextColor': '#18181b', 'primaryBorderColor': '#d4d4d8', 'lineColor': '#71717a', 'secondaryColor': '#f4f4f5', 'tertiaryColor': '#e4e4e7'}}}%%
flowchart TD
    ValidationRules[Validation Rules] --> SortedRules[Sort by processing order]

    SortedRules --> FieldType

    subgraph FieldLoop["Loop over sorted fields"]
        FieldType{Field is of type:}

        FieldType --> |Root Field| RootProperty[Build Root Property]
        FieldType --> |Dot Notation Field| NestedStructure[Nested Object/Array]
        FieldType --> |Array of Primitives| ArrayProperty[Build Array Items Schema]

        NestedStructure -->|"recurse over segments"| SegmentType

        SegmentType{Segment Type}

        SegmentType -->|Other| IntermediateObjects["Add Object Property <br /><small>(if Missing)</small>"]
        SegmentType -->|`*` segment| ArrayItems[Array Item Creation]
        SegmentType -->|leaf segment| LeafProperty[Build Leaf Property]
    end

    FieldLoop --> FinalSchema[Output Final Schema]

    style ValidationRules fill:#ffffff,stroke:#71717a,stroke-width:2px,color:#18181b
    style FinalSchema fill:#f4f4f5,stroke:#71717a,stroke-width:2px,color:#18181b
    style NestedStructure fill:#e4e4e7,stroke:#71717a,stroke-width:2px,color:#18181b
    style ArrayProperty fill:#e4e4e7,stroke:#71717a,stroke-width:2px,color:#18181b
    style RootProperty fill:#e4e4e7,stroke:#71717a,stroke-width:2px,color:#18181b
```

**Key concepts:**

- **Root fields** - Top-level properties (`name`, `email`).
- **Dot notation** - Nested structures (`user.profile.name`).
- **Array wildcards** - Array items (`items.*.name`).
- **Property building** - Convert validation rules to JSON Schema properties.
- **Recursive processing** - Handle deeply nested structures.

### Response Shape Extraction Process

The response shape extraction process resolves the JSON type structure of a route's response. It is triggered asynchronously when the user trigger the response shape download (from the response viewer tab).

#### Shape Extraction Strategies
The system uses the Strategy pattern to inspect the controller and extract the shape using static analysis:

1. **JsonResource Strategy** - Parses the controller return statement to identify Laravel extractable classes (like Spatie Data or JSON Resource).
2. **Raw JSON Response Strategy** - Parses the controller method body to find returned array literals or `response()->json(...)` calls. It infers types from literal values.
3. **Fallback Strategy** - Used if static analysis does not resolve the shape. It parses the actual response JSON body directly and maps types (string, number, boolean, object, array, null) to JSON Schema.

All schema properties built during the response shape extraction process are marked as required by default.

### Request Flow

How user interactions translate to API calls through the relay system.

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor': '#ffffff', 'primaryTextColor': '#18181b', 'primaryBorderColor': '#d4d4d8', 'lineColor': '#71717a', 'secondaryColor': '#f4f4f5', 'tertiaryColor': '#e4e4e7'}}}%%
sequenceDiagram
    participant User
    participant VueFrontend as Vue Frontend
    participant LaravelBackend as Laravel Backend
    participant APIEndpoint as API Endpoint

    critical Initialization
        LaravelBackend->>VueFrontend: Populate Routes with Schemas
    end
    
    User->>VueFrontend: Select Route
    VueFrontend->>User: Display Route on UI
    
    User->>VueFrontend: Configure Request
    User->>VueFrontend: Trigger Request
    VueFrontend->>VueFrontend: Prepare request for relaying
    VueFrontend->>LaravelBackend: Send Request to Relay Endpoint
    LaravelBackend->>APIEndpoint: Relay HTTP Request
    APIEndpoint->>LaravelBackend: Return Response
    LaravelBackend->>LaravelBackend: Prepare endpoint response into relay response
    LaravelBackend->>VueFrontend: Return relayed response
    VueFrontend->>User: Display Response
```

---

## Design Principles

### Single Responsibility Principle

Each class has one clear purpose and reason to change.

**Examples:**

- `RouteExtractorService` - Orchestrates route extraction.
- `SchemaBuilder` - Converts validation rules to schemas.
- `RequestRelayAction` - Handles HTTP request forwarding.
- `PropertyBuilder` - Builds individual schema properties.

### Dependency Injection

Services are injected rather than instantiated directly.

```php
class RouteExtractorService
{
    public function __construct(
        protected SchemaExtractor $schemaExtractor,
        protected ExtractableRouteFactory $routeFactory,
        protected IgnoredRoutesService $ignoredRoutesService,
    ) {}
}
```

### Immutable Value Objects

Data structures don't change after creation, ensuring predictable behavior.

```php
readonly class ExtractedRoute
{
    public function __construct(
        public readonly Endpoint $uri,
        public readonly array $methods,
        public readonly Schema $schema,
    ) {}
}
```

### Strategy Pattern

Different algorithms encapsulated in separate classes for flexibility.

**Examples:**

- `FormRequestExtractorStrategy` - Extracts rules from Form Request classes.
- `InlineRequestValidatorExtractorStrategy` - Extracts inline validation.
- `BearerAuthorizationHandler` - Handles Bearer token auth.
- `CurrentUserAuthorizationHandler` - Handles session-based auth.

### Actions vs Services

The codebase distinguishes between **Actions** and **Services** based on their responsibilities.

#### Actions

**Definition:** A single, composable unit of work that performs one discrete operation.

**Characteristics:**
- Single responsibility - performs one complete operation.
- Composable - can be called by other actions, services, or controllers.
- Stateless - only holds dependencies, no internal mutable state.
- Returns results - produces DTOs, value objects, or collections.
- Workflow-oriented - may orchestrate multiple steps internally.

**Example:**
```php
class RequestRelayAction
{
    public function execute(RelayRequest $request): RelayResponse
    {
        // Performs one complete workflow: relay HTTP request
    }
}
```

#### Services

**Definition:** A reusable helper that encapsulates domain-specific logic or manages data.

**Characteristics:**
- Reusable logic - provides methods used by multiple actions or controllers.
- May hold state - if part of its responsibility.
- Non-orchestrating - it does not perform complete workflows.
- Domain/infrastructure focused - manages persistence, caching, configuration.

**Example:**
```php
class IgnoredRoutesService
{
    public function isIgnored(string $routeName): bool
    {
        // Provides reusable logic for route filtering
    }
}
```

#### Quick Comparison

| Feature | Action | Service                                 |
|---------|--------|-----------------------------------------|
| **Purpose** | Single unit of work / workflow | Reusable domain or infrastructure logic |
| **State** | Stateless beyond dependencies | Can hold state if necessary             |
| **Return** | Value, DTO, collection | Self, Side-effect, or data              |
| **Orchestration** | Can orchestrate multiple steps | Does not orchestrate                    |
| **Composable** | Yes | Usually used by actions or controllers  |

**Rule of Thumb:** If the class performs one discrete operation and returns a result, it's an **Action**. If the class provides reusable logic or manages state, it's a **Service**.

---

## Development Environment Setup

### Local Development Setup

#### 1. Clone the Package Repository

```bash
git clone https://github.com/sunchayn/nimbus.git
cd nimbus
composer install
npm install
```

#### 2. Clone the Development Repository

The dev repository provides a foundation for testing the package with a Laravel application.

```bash
git clone https://github.com/sunchayn/nimbus-dev.git
cd nimbus-dev
composer run setup
```

#### 3. Start a Web Server

**Important:** The relay endpoint requires a real web server by default. Using `php artisan serve` alone won't work out of the box (check [#3.1](#31-using-it-without-a-real-server) for instructions on how to make it work).

**Suggeted Options:**
- **Laravel Herd** - Recommended for macOS.
- **Laravel Sail** - Docker-based environment.
- **Valet** - Nginx-based for macOS.
- **Docker/Nginx/Apache** - Manual setup.

**Why not the built-in server?**

PHP's built-in server is single-threaded. When the relay endpoint makes a request back to the application, it creates a deadlock where the server waits for itself to respond.

#### 3.1. Using it without a real server

If you need to run Nimbus without a dedicated web server, you can still use php artisan serve with a two-server setup.

A. In the nimbus-dev directory, start the main development process:
```bash
composer dev
```

B. In a separate terminal tab (or windo), start another PHP server:
```bash
php artisan serve
```

C. Once both are running, open your config/nimbus.php file and set the routes.apiBaseUrl value to the URL of the second server (the one started with php artisan serve).

```php
// Example.
'apiBaseUrl' => 'http://127.0.0.1:8000',
```

#### 4. Run Frontend Development Server

```bash
# Inside nimbus directory
npm run dev
```

#### 5. Access Nimbus

Navigate to your Laravel application's Nimbus endpoint:

```
http://your-app.test/nimbus
```

### Testing Different Laravel Versions

Use Composer scripts to switch between Laravel versions:

```bash
# Switch to Laravel 10
composer run switch:l10

# Switch to Laravel 11
composer run switch:l11

# Switch to Laravel 12
composer run switch:l12
```

These scripts update `orchestra/testbench` and all related dependencies automatically.

After switching, run tests to verify compatibility:

```bash
composer test:parallel
```

---

## Development Workflow

### Code Organization

Follow the established module structure when adding new functionality.

**Backend modules:**

```
src/Modules/
├── Config/         # Add new configuration enums here
├── Routes/         # Route extraction and normalization logic
├── Schemas/        # Schema generation and transformation
└── Relay/          # HTTP relay and authorization handlers
```

**Frontend structure:**

```
resources/js/
├── components/
│   ├── base/       # Reusable UI components (buttons, inputs)
│   ├── common/     # Shared components (modals, notifications)
│   ├── domain/     # Business logic components (request builder)
│   └── layout/     # Layout components (panels, sidebar)
├── composables/    # Reactive stateful logic
├── stores/         # Global state management (Pinia)
└── utils/          # Pure utility functions
```

### Git Workflow

#### 1. Fork and Clone

```bash
git clone https://github.com/YOUR_USERNAME/nimbus.git
cd nimbus
git remote add upstream https://github.com/sunchayn/nimbus.git
```

#### 2. Create Feature Branch

```bash
git checkout -b feature/your-feature-name origin/base
```

Use descriptive branch names:
- `feature/add-openapi-support`
- `fix/schema-generation-nested-arrays`
- `refactor/extract-property-builder`

#### 3. Make Changes

- Follow coding standards (see [Coding Standards](#coding-standards)).
- Write tests for new functionality.
- Update documentation if needed.
- Keep commits focused and atomic.

#### 4. Test Your Changes

```bash
# Backend tests
composer test

# Frontend tests
npm run test:run

# Linting
composer style:fix
npm run style:fix
```

#### 5. Commit Changes

Use conventional commit messages:

```bash
git add .
git commit -m "feat(schemas): add support for nested array validation"
```

**Commit format:** `type(scope): description`

**Types:**
- `feat` - New feature.
- `fix` - Bug fix.
- `refactor` - Code restructuring.
- `docs` - Documentation changes.
- `test` - Adding tests.
- `chore` - Maintenance tasks.
- `build` - Dependencies or CI changes.

#### 5. Push and Create Pull Request

```bash
git push -u origin feature/your-feature-name
```

Then create a pull request on GitHub with:
- Clear description of changes.
- Reference to related issues.
- Screenshots for UI changes.
- Testing instructions.

---

## Testing Strategy

### Backend Testing

**Test organization:**
- Tests mirror the module structure.
- Each service has corresponding test classes.
- Integration tests verify end-to-end functionality.
- Unit tests focus on individual methods.

**Testing principles:**
- Test behavior, not implementation.
- Mock external dependencies.
- Use descriptive test names.
- Keep tests focused and independent.

**Running tests:**

```bash
# Run all tests
composer test

# Run tests in parallel (faster)
composer test:parallel

# Run with coverage
composer test:coverage

# Run specific test class
composer test -- --filter SchemaBuilderTest

# Run specific test method
composer test -- --filter testBuildsSchemaFromValidationRules
```

**Test example:**

```php
class SchemaBuilderUnitTest extends TestCase
{
    public function test_it_builds_schema_for_simple_validation_rules(): void
    {
        // Arrange
        
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'age' => ['integer', 'min:18'],
        ];

        // Act
        
        $schema = $this->schemaBuilder->build($rules);

        // Assert
        
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertTrue($schema['properties']['name']['required']);
    }
}
```

### Frontend Testing

**Component testing guidelines:**
- Test user interactions and outcomes.
- Mock external dependencies (API calls, stores).
- Use `data-testid` attributes for selectors.
- Focus on component behavior, not implementation details.
- Never assert Tailwind class names.
- Nuxt UI is installed globally in `resources/js/tests/setup.ts`, so `mount` works for base components without extra setup.
- Overlays such as dialogs, popovers and menus render in `document.body`, so assert on `document.body` or `screen`.

**Linting and formatting:**

```bash
# Check styling issues
npm run style:check

# Automatically fix styling issues
npm run style:fix
```

**Running component tests:**

```bash
# Run all tests
npm run test:run

# Run tests in watch mode
npm run test

# Run specific test file
npm run test:run -- RequestBuilder.test.ts
```

**Test example:**

```typescript
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import RequestBuilder from '@/components/domain/RequestBuilder.vue'

describe('RequestBuilder', () => {
  it('validates required fields', async () => {
    const wrapper = mount(RequestBuilder, {
      props: {
        schema: {
          properties: {
            name: { type: 'string', required: true }
          }
        }
      }
    })

    // Trigger validation
    await wrapper.find('[data-testid="submit-btn"]').trigger('click')

    // Assert error message appears
    expect(wrapper.find('[data-testid="error-message"]').text())
      .toContain('name is required')
  })
})
```

---

## Contributing Process

### Before You Start

1. **Check existing issues** - Someone may already be working on it.
2. **Open an issue first** - For major changes, discuss the approach.
3. **Read the documentation** - Understand the architecture and patterns.
4. **Set up your environment** - Follow the setup guide above.

### Development Process

Follow the [Git Workflow](#git-workflow) guide.

#### Update Documentation

If your changes affect:
- **User-facing features** → Update User Guide
- **Architecture or patterns** → Update Contributor Guide
- **Configuration** → Update config comments and README

#### Submit Pull Request

**PR title format:** `type(scope): brief description`

**PR description should include:**
- What changes were made.
- Why the changes were necessary.
- How to test the changes.
- Screenshots for UI changes.
- Related issues (e.g., "Closes #123").

**Example PR description:**

```markdown
## Description
Adds support for extracting validation rules from custom validation rule objects.

## Motivation
Currently, Nimbus only supports array-based validation rules. Many Laravel apps use custom rule objects, which are not extracted.

## Changes
- Added `RuleObjectExtractor` to handle custom rule objects
- Updated `SchemaExtractor` to detect and use the new extractor
- Added tests for rule object extraction

## Testing
1. Create a route with custom rule object validation
2. Access Nimbus interface
3. Verify the route appears with correct schema

Closes #42
```

### Pull Request Guidelines

#### Code Review Checklist

Before submitting, verify:

- [ ] Code follows established patterns and conventions.
- [ ] All tests pass.
- [ ] New functionality has test coverage.
- [ ] Documentation is updated.
- [ ] No linting or formatting errors.
- [ ] Commits are clean and well-described.
- [ ] PR description is clear and complete.

#### What Reviewers Look For

**Code quality:**
- Follows SOLID principles.
- Appropriate use of Actions vs Services.
- Proper dependency injection.
- Clear, descriptive naming.

**Testing:**
- Adequate test coverage.
- Tests focus on behavior, not implementation.
- Edge cases are considered.
- Tests are independent and repeatable.

**Documentation:**
- Code is self-documenting where possible.
- Complex logic has explanatory comments.
- User-facing changes documented in User Guide.
- Architecture changes documented in Contributor Guide.

**Performance:**
- No obvious performance issues.
- Efficient algorithms and data structures.
- Appropriate use of caching.

**Breaking changes:**
- Clearly documented.
- Migration path provided if applicable.
- Version bump follows semantic versioning.

---

## Coding Standards

### PHP Coding Standards

#### Method Naming

Use action-oriented, domain-specific names that clearly communicate intent.

**Good examples:**

```php
// Action-oriented + domain-specific
public function switchToRouteDefinitionOf(string $method): void
public function initializeRequest(Route $route, array $availableRoutes): void
public function executeCurrentRequest(): void
public function validateUserPreferences(array $data): bool
public function generateCurlCommand(Request $request): string
```

**Poor examples:**

```php
// Implementation-focused, unclear intent
public function updateSchemaAndPayloadForMethod(string $method): void
public function setupRequestData(Route $route, array $routes): void
public function processRequest(): void
public function doValidation(array $data): bool
```

#### Type Declarations

Always use type declarations for parameters and return types.

```php
// Good
public function buildSchema(array $rules): array
{
    // Implementation
}

// Bad
public function buildSchema($rules)
{
    // Implementation
}
```

#### Readonly Properties

Use readonly properties for immutable data.

```php
readonly class ExtractedRoute
{
    public function __construct(
        public readonly Endpoint $uri,
        public readonly array $methods,
        public readonly Schema $schema,
    ) {}
}
```

### Vue.js Coding Standards

#### Composition API

Always use `<script setup>` with TypeScript.

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'

// Props
const props = defineProps<{
  title: string
  items: Item[]
}>()

// Emits
const emit = defineEmits<{
  update: [value: string]
}>()

// State
const isLoading = ref(false)

// Computed
const filteredItems = computed(() => {
  return props.items.filter(item => item.active)
})

// Methods
const handleUpdate = (value: string) => {
  emit('update', value)
}
</script>

<template>
  <!-- Template content -->
</template>
```

#### Component Structure Order

Organize component code in this order:

1. Imports.
2. Props and emits.
3. Reactive state (ref, reactive).
4. Computed properties.
5. Methods/functions.
6. Lifecycle hooks.
7. Watch statements.

#### TypeScript Usage

- Use strict type checking.
- Define interfaces for complex objects.
- Avoid `any` type.
- Use type assertions sparingly.

```typescript
// Good - explicit types
interface RequestConfig {
  method: string
  url: string
  headers: Record<string, string>
  body?: unknown
}

const config: RequestConfig = {
  method: 'POST',
  url: '/api/users',
  headers: { 'Content-Type': 'application/json' }
}

// Bad - any type
const config: any = { /* ... */ }
```

---

## Style Guides

### Comment Style Guide

#### Docblock Format

Use docblocks to explain purpose and business context, not implementation.

```php
/**
 * Builds a JSON Schema from Laravel validation rules.
 *
 * This method handles nested structures using dot notation and array wildcards.
 * It processes fields in a specific order to ensure parent objects exist before
 * child properties are added.
 * 
 * @param array<string, array<int, string|object>> $rules Laravel validation rules
 * @return array<string, mixed> JSON Schema representation
 */
public function build(array $rules): array
{
    // Implementation
}
```

#### Inline Comments

Explain **why** decisions were made, not **what** the code does.

```php
// Good - explains reasoning
// Process parent fields first to ensure nested objects exist
$sortedFields = $this->sortFieldsByDepth($fields);

// Bad - restates the obvious
// Loop through the fields
foreach ($fields as $field) {
    // ...
}
```

#### TODO Comments

Use categorized TODO comments for future work.

```php
// TODO [Feature]: Add support for custom validation rule objects
// TODO [Enhancement]: Cache compiled schemas for better performance
// TODO [Bug]: Fix handling of conditional validation rules
// TODO [Refactor]: Extract cookie decryption into dedicated service
// TODO [Performance]: Optimize schema generation for large rule sets
// TODO [Documentation]: Add examples for complex nested structures
// TODO [Test]: Add integration tests for authentication flow
```

**Allowed categories:**

| Category | Purpose | Example |
|----------|---------|---------|
| **Feature** | New functionality planned | Investigate supporting Closures for route extraction |
| **Enhancement** | Improvement to existing functionality | Convert logged-in user to bearer token |
| **Bug** | Known defect that needs fixing | Implement impersonation logic properly |
| **Refactor** | Code reorganization needed | Extract cookie creation into service |
| **Performance** | Optimization opportunity | Cache authorization handlers |
| **Documentation** | Missing or incomplete docs | Add examples for custom auth strategies |
| **Test** | Missing or incomplete test coverage | Add integration tests for relay flow |

### Tailwind CSS Usage

#### Use Semantic and Token Utilities

Use Tailwind utility classes built from the design system. Avoid custom CSS when possible.

```vue
<!-- Good - semantic and token utilities -->
<div class="flex h-screen max-h-screen overflow-hidden">
  <div class="bg-muted border-r border-default p-4">
    <h2 class="text-lg font-semibold text-highlighted mb-4">Routes</h2>
  </div>
</div>

<!-- Bad - raw palette classes and arbitrary values -->
<div class="bg-white border-r border-zinc-200 p-[14px] z-[50]">
  <h2 class="text-zinc-900">Routes</h2>
</div>
```

Semantic utilities follow dark mode, so a `dark:` twin is not needed.

#### Component Class Extraction

For repeated patterns, extract to component props or composables, not CSS classes.
Component sizes and colors belong to the theme preset, not to the call site.

---

## Design System

Nimbus uses Nuxt UI v4 behind thin `App*` wrappers.

### Structure

1. `resources/css/themes/base.css` holds the default theme on `:root`, with a `.dark` block after each layer for the values that differ in dark mode.
2. `resources/css/variables/layout.css` holds the layout sizes, such as the toolbar height and the sidebar width.
3. `tools/ui/theme.js` holds the Nuxt UI component preset. It has shared class strings at the top and one section per component in alphabetical order. It is passed to the Nuxt UI plugin in `vite.config.js`.
4. `resources/js/components/base` holds the wrappers. A wrapper keeps the `App` name, exposes the Nuxt UI props, and passes attributes through. It holds no sizes, colors or classes.

Only files in `components/base` import from `@nuxt/ui`. The exceptions are `app.ts`, `App.vue`, the test setup and the Vite config.

### Token Layers

1. Primitives are raw scales, such as the type scale, the control scale and the item rows.
2. Semantic tokens are roles, such as the `--ui-*` colors, `--border-w`, `--elevation-control`, `--elevation-overlay`, `--press-translate`, gaps, layers and durations. Components read these.
3. Component tokens are narrow knobs, such as `--tab-active-bg`, `--tab-active-shadow`, `--tab-active-border`, `--sidebar-item-active-bg` and `--overlay-separator`. Each one defaults to a semantic token.

A theme can override any layer. Most themes only touch primitives and semantic tokens.
Component tokens let a theme change one component without touching the others.

### The Preset Never Changes Per Theme

1. `tools/ui/theme.js` holds no theme name, no raw palette class, and no arbitrary px, rem or hex value.
2. Every visual difference between themes is a token. If a difference cannot be expressed with an existing token, add a token to `base.css` with the current value first, then read it in the preset.
3. Run `npm run check:theme` after you edit the preset. It fails on a raw palette class, a theme name, a variable class such as `p-(--card-padding)`, or an arbitrary value.

### Token Utilities

Classes never read a token with the variable syntax, such as `gap-(--gap-base)`.
Each token has a named utility instead.
Most are registered in the `@theme inline` block of `base.css`, so Tailwind builds them from its namespaces.
The control scale has no Tailwind namespace, so `app.css` defines it with `@utility`.
Every utility reads the runtime variable, so a theme that overrides the variable also changes the utility.

| Family | Utilities | Token |
| --- | --- | --- |
| Gaps and spacing | `gap-tight`, `gap-base`, `gap-loose`, also with `p`, `m` and the other spacing prefixes | `--gap-*` |
| Surface padding | `p-card`, `p-dialog`, `p-popover`, `p-menu`, `p-toast`, `p-panel`, `p-tabs` | `--*-padding` |
| Role gaps | `gap-dialog-gap`, `gap-tabs-gap`, `gap-nav-gap` | `--dialog-gap`, `--tabs-gap`, `--nav-gap` |
| Control height | `h-control-*`, `min-h-control-*`, `w-control-*`, `min-w-control-*` | `--control-h-*` |
| Control padding | `px-control-*`, `ps-control-*`, `pe-control-*` | `--control-px-*` |
| Control gap | `gap-control-*` | `--control-gap-*` |
| Control text | `text-control-*`, which also sets the line height | `--control-text-*`, `--control-leading-*` |
| Icons and rows | `size-icon-*`, `size-nav-icon`, `h-item-sm`, `h-item-md` | `--control-icon-*`, `--nav-icon-size`, `--item-h-*` |
| Text | `text-xxs`, `text-menu-label` | `--text-xxs`, `--menu-label-text` |
| Border width | `ring-line`, `border-b-line`, `border-s-line`, `border-e-line`, `w-line`, `h-line` | `--border-w` |
| Elevation | `shadow-control`, `shadow-overlay`, `shadow-modal`, `shadow-tab-active` | `--elevation-*`, `--tab-active-shadow` |
| Layers | `z-sticky`, `z-raised`, `z-overlay`, `z-toast` | `--z-*` |
| Motion | `duration-fast`, `duration-base`, `animate-fade-in-fast`, `animate-fade-out-base`, `animate-collapsible-down` | `--duration-*` |
| States | `opacity-disabled`, `translate-y-press`, `translate-y-tooltip-nudge` | `--disabled-opacity`, `--press-translate`, `--tooltip-nudge` |
| Overlay sizes | `min-w-overlay`, `max-w-popover`, `max-w-dialog`, `max-w-dialog-lg`, `max-h-dialog` | `--overlay-min-w`, `--popover-max-w`, `--dialog-max-w`, `--dialog-lg-max-w`, `--dialog-max-h` |
| Component colors | `bg-separator`, `bg-tab-active-bg`, `text-tab-active-text`, `ring-tab-active-border`, `bg-sidebar-item-active`, `caret-default` | the matching component token |
| Layout | `h-toolbar`, `min-h-toolbar`, `h-sub-toolbar`, `min-h-sub-toolbar` | `--toolbar-height`, `--sub-toolbar-height` |

The control sizes are `xs`, `sm`, `md` and `lg`.
To add a utility, register the token in `base.css` or `app.css`, then add its name to `twMergeConfig` in `tools/ui/theme.js`.
Tailwind merge needs the name to replace a Nuxt UI default class, such as `shadow-overlay` over `shadow-lg`.
Variables that Reka sets, such as `--reka-select-trigger-width`, keep the variable syntax.

### Adding a Theme

1. Create `resources/css/themes/<name>.css`.
2. Put the overrides in one `[data-theme='<name>']` block, and the dark values in a `.dark[data-theme='<name>']` block.
3. The file holds only variable overrides. It has no component selectors and no `@apply`.
4. A theme that needs its own font loads it in the same file, so other themes do not pay for it.
5. The app ships only the default theme today. When a second theme exists, its file is loaded on demand and `data-theme` is set next to the `.dark` class.

### Control Scale

| Size | Control height | Item row |
| --- | --- | --- |
| `xs` | 20px | |
| `sm` | 24px | 24px with `text-xs` |
| `md` | 28px, the default | 28px with `text-sm` |
| `lg` | 32px | |

Use the component `size` prop. For custom elements use `h-control-md`, `px-control-md`, `gap-control-md` and `size-icon-md`.
Radius roles are `rounded-sm` for items, buttons and tooltips, `rounded-md` for inputs, cards and menus, and `rounded-lg` for dialogs and the command popover.
Overlays fade only, with no zoom or slide.

### Spacing Scale

Every padding and gap in the preset comes from one scale of tokens in `resources/css/themes/base.css`.
Wrappers and call sites never pick their own value.

| Token | Value | Used for |
| --- | --- | --- |
| `--gap-tight` | 4px | Gaps inside rows, label to field, title to description |
| `--gap-base` | 8px | Gaps between controls, dialog footer buttons, panel padding |
| `--gap-loose` | 12px | Gaps between groups of content |
| `--menu-padding` | 4px | Inset of dropdown, select and command groups, and their separators |
| `--popover-padding` | 8px | Popover content |
| `--card-padding` | 12px | Cards and panels inside pages |
| `--toast-padding` | 12px | Toasts |
| `--dialog-padding` | 12px | Modal and slideover surface |
| `--dialog-gap` | 12px | Space between the dialog header, body and footer |
| `--dialog-max-w` | 480px | Default dialog width |
| `--dialog-lg-max-w` | 560px | Dialogs that show a code block, set with `max-w-dialog-lg` on the content slot |
| `--dialog-max-h` | 70dvh | Dialog height limit, the body scrolls inside it |

The control scale (`--control-px-*` and `--control-gap-*`) sets the inside of buttons, fields, tabs and menu rows.
Dialogs pad the surface once and use no dividers, so the header, body and footer slots add no padding.
Content passed into a popover or dialog slot adds no outer padding, because the surface already has it.
Dialog titles are `text-sm` semibold with a `size-icon-md` icon, and descriptions are `text-xs` muted, 4px below the title.
The close button is `sm` and sits in the title row.
Dialog footer buttons use `size="sm"`, and code boxes inside a dialog use `text-xs` mono with `p-base` and scroll inside the body.
Alerts pad 8px by 10px with `text-xs` text and a `size-icon-sm` icon.
Group labels in menus and selects use 11px semibold muted text, with no uppercase, and a line above every group after the first.

### Adding a Wrapper

1. Create `resources/js/components/base/<name>/App<Name>.vue` that wraps the Nuxt UI component, imported explicitly, for example `import UButton from '@nuxt/ui/components/Button.vue'`.
2. Expose the Nuxt UI props and slots and pass `data-testid` through.
3. Export it from the folder `index.ts`.
4. Add the visual rules to the component section in `tools/ui/theme.js`, in alphabetical order.
5. Add a test in `resources/js/tests/components/base` that checks behavior.

### Changing a Theme Value

1. Change colors, scale values, elevation, layers and durations in `resources/css/themes/base.css`.
2. Change how a component uses them in its section of `tools/ui/theme.js`, then run `npm run check:theme`.
3. Check the result in the running app, in light and dark mode.

### Icons

1. Icons come from the HugeIcons collection. Write full literal names such as `i-hugeicons-cancel-01`.
2. Use them through `AppIcon` or an `icon`, `leading-icon` or `trailing-icon` prop.
3. Never build a name from parts, because a name built at runtime is not bundled and would fetch from the network.
4. When data holds icons, store the literal string name and render it with `AppIcon`.
5. Nuxt UI's own icons are set in `nuxtUiIcons` in `vite.config.js`. Every key must point to a HugeIcons name, otherwise Nuxt UI falls back to a default icon that is not installed and is fetched from the network.

### Toasts

Use `useAppToast` from `@/components/base/toast`.

```typescript
const toast = useAppToast();

toast.success('Saved');
toast.error('Request failed', { description: 'Try again.' });
```

It offers `success`, `warning` and `error`. Each takes a title and optional `description` and `duration`.

---

## Architecture Rules

### Backend Architecture Rules

#### Controllers

Controllers orchestrate, they don't contain business logic.

```php
// Good - controller delegates to action
class RelayController extends Controller
{
    public function __construct(
        private RequestRelayAction $relayAction
    ) {}

    public function relay(Request $request): JsonResponse
    {
        $relayRequest = RelayRequest::fromRequest($request);
        $relayResponse = $this->relayAction->execute($relayRequest);
        
        return response()->json($relayResponse);
    }
}

// Bad - controller contains business logic
class RelayController extends Controller
{
    public function relay(Request $request): JsonResponse
    {
        // Complex logic here
        $client = new Client();
        $response = $client->request(/* ... */);
        // More logic...
        
        return response()->json($data);
    }
}
```

#### Services

Services handle single business concerns and use dependency injection.

```php
class SchemaExtractorService
{
    public function __construct(
        private FormRequestExtractorStrategy $formRequestStrategy,
        private InlineRequestValidatorExtractorStrategy $inlineStrategy,
    ) {}

    public function extract(ExtractableRoute $route): ?array
    {
        // Single responsibility: extract validation rules
        // Delegates to appropriate strategy
    }
}
```

#### Value Objects and DTOs

Use immutable value objects and DTOs for data transfer and manipulation.

```php
// Immutable value object
readonly class RelayRequest
{
    public function __construct(
        public string $method,
        public string $url,
        public array $headers,
        public ?array $body,
        public ?AuthorizationType $authorization,
    ) {}
    
    public static function fromRequest(Request $request): self
    {
        return new self(
            method: $request->input('method'),
            url: $request->input('url'),
            headers: $request->input('headers', []),
            body: $request->input('body'),
            authorization: AuthorizationType::tryFrom($request->input('authorization.type')),
        );
    }
}
```

### Frontend Architecture Rules

#### Component Organization

Group related code together and keep components focused.

```vue
<script setup lang="ts">
// 1. Imports
import { ref, computed, onMounted } from 'vue'
import { useRequestStore } from '@/stores/request'

// 2. Props and emits
const props = defineProps<{
  route: Route
}>()

const emit = defineEmits<{
  submit: [request: RequestConfig]
}>()

// 3. Stores and composables
const requestStore = useRequestStore()
const { validatePayload } = useValidation()

// 4. Reactive state
const payload = ref<Record<string, unknown>>({})
const errors = ref<ValidationError[]>([])

// 5. Computed properties
const isValid = computed(() => errors.value.length === 0)

// 6. Methods
const handleSubmit = () => {
  errors.value = validatePayload(payload.value)
  if (isValid.value) {
    emit('submit', { ...payload.value })
  }
}

// 7. Lifecycle hooks
onMounted(() => {
  // Initialize
})
</script>
```

#### State Management

Use appropriate state management strategies:

- **Local state** - Component-specific data.
- **Composables** - Reusable reactive logic.
- **Pinia stores** - Global application state.

```typescript
// Composable for reusable reactive logic
export function useValidation(schema: Schema) {
  const errors = ref<ValidationError[]>([])
  
  const validate = (data: unknown): boolean => {
    errors.value = performValidation(data, schema)
    return errors.value.length === 0
  }
  
  return {
    errors: readonly(errors),
    validate
  }
}

// Store for global state
export const useRequestStore = defineStore('request', () => {
  const currentRequest = ref<RequestConfig | null>(null)
  const history = ref<RequestConfig[]>([])
  
  const setCurrentRequest = (request: RequestConfig) => {
    currentRequest.value = request
    history.value.push(request)
  }
  
  return {
    currentRequest,
    history,
    setCurrentRequest
  }
})
```

---

## Final Notes

### Continuous Improvement

The codebase has undergone multiple refactoring rounds. Some inconsistencies or confusing parts may exist and will be addressed as the project matures.

### Getting Help

- **Architecture questions** - Open a discussion on GitHub.
- **Bug reports** - Open an issue with reproduction steps.
- **Feature proposals** - Start a discussion first to align on approach.

---

### Thank You
