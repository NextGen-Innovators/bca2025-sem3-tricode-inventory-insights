# Copilot / Agent Instructions — WasteWise Nepal (Tricode Inventory Insights)

Purpose: Help an AI coding assistant become productive in this repo quickly by describing the architecture, key files, developer workflows, and project-specific patterns.

1. Big-picture

- This is a small, procedural PHP app (no framework) that runs on a typical LAMP/XAMPP stack.
- Frontend: Bootstrap 5 + small inline JS in pages under `store/`.
- Backend: PHP + MySQL (mysqli). DB schema is in `Database/Database_table`.
- Primary domain: store owners register/login, manage products, and get waste alerts & auto-discounts.

2. Key files & entry points

- `index.php` — Marketing/landing page.
- `login.php`, `register.php`, `logout.php` — Auth flows. Passwords are stored/checked using `md5()` in current code.
- `includes/config.php` — Global bootstrap: `session_start()`, timezone, mysqli `$conn` connection. Adjust DB credentials here.
- `includes/auth.php` — Helpers: `checkLogin()` (redirects if no `$_SESSION['store_id']`) and `getStoreInfo($conn)`.
- `Database/Database_table` — SQL to create `wastewise_nepal`, `stores`, `products`, `waste_alerts`.
- `store/` — Main app pages (require `../includes/config.php` and `../includes/auth.php`):
  - `dashboard.php`, `products.php`, `add_product.php`, `edit_product.php`, `alerts.php`, `remove_discount.php`

3. Authentication & session

- Session key: `$_SESSION['store_id']` (set on successful login in `login.php`). Many pages call `checkLogin()` at top.
- `getStoreInfo($conn)` uses a prepared statement to fetch store details.

4. DB access patterns & conventions

- Uses `new mysqli(...)` in `includes/config.php` as a global `$conn` object.
- Prepared statements are used in many places (see `login.php`, `add_product.php`, `edit_product.php`, `store` pages), but raw queries are also present (e.g., aggregate `SELECT` in `products.php`).
- SQL schema: `stores`, `products`, `waste_alerts` — refer to `Database/Database_table` for column names and types.

5. UI & behavior patterns

- All UIs use Bootstrap 5 CDN and some Bootstrap Icons CDN.
- Many actions are implemented via GET query params (e.g., `products.php?delete_id=...`, `products.php?apply_discount=1&product_id=...&percent=...`). Agents should preserve these patterns when modifying behavior.
- Client-side: small JS snippets for auto-refresh and confirm dialogs. Keep unobtrusive JS and server-side checks.

6. Project-specific conventions & shortcuts

- Relative includes: pages in `store/` include `../includes/config.php` and `../includes/auth.php` (be careful with relative paths when adding new files or refactors).
- Session-based store scoping: queries almost always filter by `store_id = $_SESSION['store_id']` — preserve this to avoid cross-store data leaks.
- Category values are an ENUM in DB (see `Database/Database_table`) — prefer these tokens (e.g., `dairy`, `bakery`, `fruits_veg`) when creating or updating data.

7. Security & gotchas (discoverable facts)

- Passwords currently hashed with `md5()` in `register.php`/`login.php`. This is how the app checks credentials today — any changes to hashing must include a migration plan.
- CSRF tokens are not present — write code defensively and follow existing patterns if you must add CSRF (document changes clearly).
- Inputs are partially validated server-side; prepared statements are used in several critical places — follow the same approach.

8. Developer workflows (how to run & test locally)

- Environment: XAMPP (Apache + PHP + MySQL) on Windows is the expected development environment.
- DB setup: import `Database/Database_table` into MySQL (default DB name: `wastewise_nepal`).
- DB credentials: set in `includes/config.php` (default is `root` with empty password).
- Access app: `http://localhost/<repo-folder>/` after starting Apache/MySQL in XAMPP.

9. Common changes and examples

- Add a protected page under `store/`: copy pattern from `products.php` — `require_once '../includes/config.php'; require_once '../includes/auth.php'; checkLogin();` and use `$store_id = $_SESSION['store_id'];`.
- Perform DB reads/writes via `$conn` (prefer prepared statements): see `login.php` and `getStoreInfo()` in `includes/auth.php`.
- To add a new category, update the DB ENUM in `Database/Database_table` and any UI select lists using those tokens.

10. Where to look for behavior when debugging

- Authentication path: `login.php`, `register.php`, `includes/auth.php`.
- DB schema & initial data: `Database/Database_table`.
- Product-related behavior and discount logic: `store/products.php`, `store/remove_discount.php`, and `store/alerts.php`.

If anything here is unclear or you'd like more detail (for example, a recommended migration plan for modern password hashing or a test plan for the discount flow), tell me which area and I'll update this doc with concrete steps and code examples.
