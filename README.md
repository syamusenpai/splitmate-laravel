# SplitMate (Laravel)

A web-based group expense splitting system for the ITT626 Back-End Technology project.
Groups record shared expenses, SplitMate works out who owes whom, and suggests the fewest transfers to settle up.

**Stack:** Laravel 13 (PHP 8.4) · MySQL 8 · Blade + Tailwind CSS + Alpine.js · Laravel Breeze (auth)

## Run it (Laragon)

1. Start Laragon and click **Start All** (Apache isn't needed, but MySQL must be running).
2. In this folder:

```bash
composer install
npm install && npm run build
cp .env.example .env        # first time only, then set DB_* to MySQL (see below)
php artisan key:generate    # first time only
php artisan migrate:fresh --seed
php artisan serve
```

3. Open http://127.0.0.1:8000

`.env` database settings:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=splitmate
DB_USERNAME=root
DB_PASSWORD=
APP_TIMEZONE=Asia/Kuala_Lumpur
```

## Demo accounts (password: `password`)

| Name | Email |
|---|---|
| Hisyam Rosli | hisyam@splitmate.test |
| Aina Sofea | aina@splitmate.test |
| Ben Tan | ben@splitmate.test |
| Chong Wei | chong@splitmate.test |

Seeded groups: **Rumah Sewa Seksyen 7** (4 members, 4 expenses, 1 payment) and **Trip Cameron Highlands**.

## Modules and CRUD

| Module | Create | Read | Update | Delete |
|---|---|---|---|---|
| Groups | New group | Dashboard, group page | Rename (creator) | Delete group (creator) |
| Members | Add by email | Members list | – | Remove (creator) / leave group |
| Expenses | Add expense | Expense list + split details | Edit expense | Delete expense |
| Payments | Mark as paid | Payment history | – | Undo payment |
| Account | Register | Account page | Name, username, email, password | Delete account |

**Added complexity:** `app/Services/BalanceCalculator.php`
- Net balance per member = paid − own shares + payments made − payments received (in sen, so no rounding errors).
- Equal splits hand leftover sen to the first members so shares always add up exactly.
- Settle-up suggestions use a greedy algorithm (largest debtor ↔ largest creditor), so a group needs at most n − 1 transfers.

## Where things are

| Path | What |
|---|---|
| `routes/web.php` | All routes (behind `auth` middleware) |
| `app/Http/Controllers/` | Dashboard, Group, GroupMember, Expense, Settlement controllers |
| `app/Models/` | User, Group, Expense, ExpenseSplit, Settlement |
| `app/Services/BalanceCalculator.php` | Balance and settlement algorithm |
| `database/migrations/2026_11_10_000001_create_splitmate_tables.php` | SplitMate tables |
| `database/seeders/DatabaseSeeder.php` | Demo data |
| `resources/views/` | Blade pages (`dashboard`, `groups/show`, `expenses/_form`, ...) |
| `tests/Feature/SplitMateTest.php` | Feature tests (`php artisan test`) |

## Google login (optional)

The "Continue with Google" / "Sign up with Google" buttons appear on the login and register pages once Google keys are set.

1. Google Cloud Console → **APIs & Services → Credentials** → create (or reuse) an **OAuth client ID**, type *Web application*.
2. Add this **Authorised redirect URI**: `http://127.0.0.1:8000/auth/google/callback`
3. Put the keys in `.env`:

```
GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxx
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

New Google users get an account automatically; if the email already has a SplitMate account, Google is linked to it.
Code: `app/Http/Controllers/Auth/GoogleAuthController.php` (Laravel Socialite), tests in `tests/Feature/GoogleLoginTest.php`.
