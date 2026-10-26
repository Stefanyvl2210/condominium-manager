# Deploying to Laravel Cloud

The public demo runs on [Laravel Cloud](https://cloud.laravel.com) with the
compute and the database scaling to zero when nobody is using them, which keeps
a low-traffic demo inside the plan's monthly usage credit.

## What the app expects

| Concern | How it is handled |
|---|---|
| Uploaded files | Expense invoices and payment proofs use the `uploads` disk. Locally it is `storage/app/private/uploads`; on Cloud a private bucket named `uploads` replaces it automatically. |
| Database | MySQL (same engine as local development). |
| Queues | None. Notifications are sent synchronously (`QUEUE_CONNECTION=sync`). |
| Mail | Written to the log (`MAIL_MAILER=log`); no mail provider needed for the demo. |
| Scheduler | `billing:apply-late-fees` daily at 01:00 and `demo:reset` daily at 04:00. |
| Demo data | `demo:reset` wipes the database and the demo uploads, then seeds again. It refuses to run unless `APP_DEMO=true`. |
| HTTPS / proxies | Laravel trusts the Cloud proxy automatically. |

## One-time setup

1. **Create the application** from the GitHub repository, branch `master`.
   Pick the region closest to your users (for Venezuela, a US East region).
2. **App cluster**: the smallest Flex size with at least 1 GB of RAM (the demo
   seeder and PDF generation need it). Turn on **Scale to Zero** and the
   **Scheduler**.
3. **Database**: add a **Laravel MySQL** database, smallest Flex size, with
   Scale to Zero enabled. Cloud injects the `DB_*` variables.
4. **Bucket**: add a **Laravel Object Storage** bucket with
   - visibility: **Private**
   - disk name: **`uploads`**
   - "Use as default disk": **off**
5. **Environment variables** (Settings → Environment variables):

   ```ini
   APP_NAME="Condominium Manager"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-app.laravel.cloud
   APP_TIMEZONE=America/Caracas
   APP_DEMO=true

   DB_CONNECTION=mysql
   CACHE_STORE=database
   SESSION_DRIVER=database
   QUEUE_CONNECTION=sync
   MAIL_MAILER=log
   ```

   Make sure `APP_KEY` is set. If Cloud did not generate one, create it locally
   with `php artisan key:generate --show` and paste the value.

6. **Build commands**:

   ```sh
   composer install --no-dev --optimize-autoloader
   npm ci --audit false
   npm run build
   php artisan optimize
   php artisan filament:optimize
   ```

7. **Deploy commands**:

   ```sh
   php artisan migrate --force
   ```

8. **Deploy**, then open the environment's **Commands** tab and run once:

   ```sh
   php artisan demo:reset
   ```

   It takes about a minute. From then on the scheduler resets the demo every
   night at 04:00 (`APP_TIMEZONE`).

## Running it with real data

Set `APP_DEMO=false` before storing any real information. That hides the
one-click demo sign-in buttons and disables `demo:reset`. Then create the
first administrator from the Commands tab with `php artisan tinker`, configure
a real mail provider and consider a managed queue so emails are sent in the
background.
