# NexusOps Backend

Laravel 13 control plane for lead intake and n8n orchestration.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Set `N8N_LEAD_ENRICHMENT_WEBHOOK_URL` and `N8N_CALLBACK_SECRET` before connecting a real n8n instance.

## Core code

- `app/Services/N8nWorkflowService.php` sends signed, retryable HTTP webhook requests.
- `app/Http/Controllers/LeadController.php` handles lead intake and retry operations.
- `app/Http/Controllers/N8nCallbackController.php` verifies and applies callbacks transactionally.
- `app/Models/AutomationRun.php` stores execution status and observability data.

Run tests with:

```bash
php artisan test --compact
```
