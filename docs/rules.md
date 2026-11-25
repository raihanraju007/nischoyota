# Coding Rules

These rules extend the Laravel Boost guidelines in `CLAUDE.md`. If they conflict, **this file wins**.

**Goal:** any developer can open any file and understand what it does and where to find related code.
We follow clean code, strict separation of concerns and modern PHP 8.3 / Laravel 13 features.

## 0. Before writing code

1. Read the feature's `feature.md` and `tasks.md`.
2. Inspect the existing code: modules, models, migrations, services, repositories, controllers, views, tests.
3. Find the closest existing implementation and **copy its pattern**.
4. Plan, then implement.
5. Stop and ask if the change is architectural (see [workflow.md](workflow.md#change-size-decides-the-process)).

## 1. Separation of concerns: each layer has one job

```
HTTP request
   │
   ▼
FormRequest ── "Is this input valid? Is this user allowed?"
   │
   ▼
Controller ─── "Which use case? Which response?"
   │
   ▼
Service ────── "What are the business rules?"
   │
   ▼
Repository ─── "How do we read and write the database?"
   │
   ▼
Model ──────── "What does this table look like and relate to?"
```

| Layer | Responsible for | Must NOT |
|---|---|---|
| **FormRequest** (`Http/Requests`) | Validation rules, custom messages, `authorize()`, preparing/normalising input (`prepareForValidation`), typed accessors (`toData()`) | Query for business decisions, write to the DB, contain business rules |
| **Controller** (`Http/Controllers`) | Receive the FormRequest, call **one** service method, return a view / redirect / Resource. Usually 3–8 lines per action | Validate, query, use `DB::`, calculate, contain `if` business branching |
| **Service** (`Services`) | Business rules, calculations, orchestration of repositories, `DB::transaction()`, dispatching events, throwing domain exceptions | Touch `request()`, sessions or HTTP responses, build queries, return views |
| **Repository** (`Repositories`) | Every Eloquent / query-builder call: find, list, filter, paginate, create, update, delete, eager loading | Contain business rules, validate, know about HTTP, throw HTTP exceptions |
| **Model** (`Models`) | Table mapping: `$fillable`, `casts()`, relations, query scopes, simple accessors | Business logic, calling services or repositories |
| **Policy** (`Policies`) | "Can this user do this action on this model?" | Business logic beyond authorization |
| **Resource** (`Http/Resources`) | Shaping JSON output | Queries (use loaded relations only) |
| **Blade view** | Rendering the data it receives | Queries, calculations, business decisions |

### Example: the whole chain

```php
// Http/Requests/StoreProductRequest.php
final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }

    public function toData(): ProductData
    {
        return new ProductData(
            name: $this->string('name')->toString(),
            price: $this->integer('price'),
            status: $this->enum('status', ProductStatus::class),
        );
    }
}

// Http/Controllers/Admin/ProductController.php
final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($request->toData());

        return to_route('admin.products.show', $product)->with('success', __('Product created.'));
    }
}

// Services/ProductService.php
final class ProductService
{
    public function __construct(private readonly ProductRepository $productRepository) {}

    public function create(ProductData $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $slug = $this->productRepository->uniqueSlugFor($data->name);

            return $this->productRepository->create($data, $slug);
        });
    }
}

// Repositories/ProductRepository.php
final class ProductRepository
{
    public function create(ProductData $data, string $slug): Product
    {
        return Product::query()->create([
            'name' => $data->name,
            'slug' => $slug,
            'price' => $data->price,
            'status' => $data->status,
        ]);
    }

    public function uniqueSlugFor(string $name): string { /* query */ }
}
```

## 2. Services and repositories

- Concrete `final` classes, injected by constructor promotion with `private readonly`. **No interfaces** unless there are two real implementations (e.g. payment gateways).
- Naming: `<Entity>Service`, `<Entity>Repository`, one per aggregate (e.g. `OrderRepository` also handles order items).
- Repository method names say what they return: `findActiveBySlug()`, `paginateForAdmin(ProductFilter $filter)`, `decrementStock()`. Not `get()` or `data()`.
- Services return models, collections, DTOs or scalars, never loose mixed arrays.
- A service may call another module's **service**, never another module's repository.
- Multi-step writes go in `DB::transaction()` inside the **service**.

## 3. Modern PHP and Laravel features (required)

| Use | Instead of |
|---|---|
| Type declarations on **every** parameter, property and return value | Untyped code |
| Constructor property promotion with `readonly` | Manual property assignment |
| **Backed enums** for every fixed set of values: statuses, types, methods (`OrderStatus`, `PaymentMethod`) | String/int constants, magic strings like `'pending'` |
| Enum casts on models: `'status' => OrderStatus::class` | Comparing raw strings |
| Enum methods for labels and behaviour: `$status->label()`, `$status->canTransitionTo()` | `switch` statements spread around the code |
| `Rule::enum(OrderStatus::class)` in FormRequests | `in:pending,paid` |
| `readonly` **DTO** classes in `Data/` to pass validated input to services | Passing `$request` or raw `array $data` deep into services |
| `match` expressions | Long `if/elseif` / `switch` |
| Named arguments for constructors and methods with several parameters | Positional boolean/int soup |
| `casts()` method on models | `$casts` property |
| First-class callable syntax, arrow functions | Verbose closures |
| Nullsafe operator `?->` | Nested null checks |
| `final` on classes not designed for extension | Open inheritance by default |
| `to_route()`, `route()` with named routes | Hard-coded URLs |
| Route model binding (by `slug` where public) | Manual `find()` in controllers |

Enum example:

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Paid => __('Paid'),
            self::Shipped => __('Shipped'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Paid], true);
    }
}
```

Enum cases are TitleCase. Enums live in the module's `Enums/` folder.

## 4. Clean code and readability

- **Names explain intent:** `$isEligibleForFreeShipping`, `calculateOrderTotal()`, `ProductNotAvailableException`. No abbreviations (`$prd`, `$qty2`) and no generic names (`$data2`, `process()`, `handle2()`).
- Booleans start with `is`, `has`, `can` or `should`.
- One class, one reason to change. Split a service at about 300 lines or when it mixes responsibilities.
- A method does one thing. Aim for ≤ 20 lines. Extract well-named private methods instead of writing comments.
- **Guard clauses and early returns**, max 2 levels of nesting. Always use curly braces.
- No magic numbers: use constants, enums or config (`config('shop.free_shipping_threshold')`).
- PHPDoc only where types can't say it (array shapes, generics, "why" of non-obvious logic). No comments that repeat the code.
- Throw **domain exceptions** from services (`InsufficientStockException`) and handle them at the edge (exception handler or controller).
- No dead code, commented-out code, `dd()`, `dump()` or `ray()` in commits.
- Consistent structure: a developer who has seen one module knows where everything is in every module.

## 5. Don'ts

- Don't create abstractions "for later" (YAGNI): no base repositories, generic CRUD services or unused interfaces.
- Don't duplicate business logic. If two places need it, it belongs in a service.
- Don't modify unrelated modules or rewrite working code while doing a feature.
- Don't add Composer/npm packages without approval and an ADR.
- Don't build Phase 2/3 (vendor, supplier) features during Phase 1.

## 6. Database

- Create migrations with `php artisan make:migration`. Never edit a migration that has been merged; add a new one.
- Foreign keys with explicit `constrained()` and a deliberate `onDelete` behaviour.
- Index columns used in `where`/`orderBy` (slug, status, foreign keys).
- Enum-backed columns are `string` columns cast to the enum, not MySQL `ENUM` (new cases need no migration).
- Money is stored as integers in the smallest unit (paisa) or `decimal(12,2)`, as the ADR decides. **Never floats.**
- Every model gets a factory (with states for each important enum value). Seeders for reference data.
- Avoid N+1: eager load in the repository and enable `Model::preventLazyLoading()` outside production.

## 7. Blade / frontend

- Reuse components in `resources/views/components/` before creating new ones.
- The UI must match the approved prototype (see [design.md](design.md)).
- Views receive ready-to-render data. No queries or business logic in Blade.
- Enum labels are shown via `$order->status->label()`, not with `@if` chains.
- Tailwind utilities only; follow existing class patterns.

## 8. Security

- All input goes through a FormRequest. Mass assignment is limited with `$fillable`.
- Authorization via Policies (`authorize()` in the FormRequest, or `can` middleware).
- Never trust client-sent prices, totals or stock. Recalculate on the server.
- Escape output (`{{ }}`). `{!! !!}` only for sanitized, trusted HTML.

## 9. Tests

- **Tests never touch the development database.** They run on `nischoyota_testing` only. See [testing.md](testing.md#test-database).
- Every feature ships with feature tests for its happy path, validation, authorization and key edge cases.
- Service business rules (money, stock, status transitions) also get unit tests.

## 10. Definition of done

- [ ] Acceptance criteria in `feature.md` met
- [ ] Layers respected (§1): no queries in controllers/services, no business logic in controllers/repositories
- [ ] Enums used for all fixed value sets
- [ ] Tests written and passing on the test database
- [ ] `vendor/bin/pint --dirty --format agent` run
- [ ] No unrelated changes in the diff
- [ ] Feature `tasks.md`, roadmap `tasks.md` and `memory.md` updated
