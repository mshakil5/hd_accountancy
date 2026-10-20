# HD Accountancy — Meta / WhatsApp Lead System Deployment

This document covers the lead-intake integration only. It assumes the Laravel app
(`hd_backend`) is already deployed and its normal migrations are working.

## 1. Environment

Add these to the production `.env`:

```dotenv
META_GRAPH_VERSION=v24.0
META_WHATSAPP_ACCESS_TOKEN=
META_WHATSAPP_PHONE_NUMBER_ID=
META_WHATSAPP_BUSINESS_ACCOUNT_ID=
META_WHATSAPP_VERIFY_TOKEN=
META_APP_SECRET=
META_ADS_ACCESS_TOKEN=
META_WHATSAPP_REENGAGEMENT_TEMPLATE=
META_WHATSAPP_REENGAGEMENT_TEMPLATE_LANGUAGE=en_GB

OPENAI_LEAD_ENABLED=true
OPENAI_API_KEY=
OPENAI_LEAD_MODEL=gpt-4.1-mini

LEAD_BRAND_NAME="HD Accountancy"
LEAD_ALERT_EMAIL=
LEAD_ALERT_PHONE=
LEAD_ALERT_SMS_ENABLED=false
LEAD_RETENTION_DAYS=365

TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_SMS_FROM=
```

`META_APP_SECRET` and `META_WHATSAPP_VERIFY_TOKEN` are **required** now:
the webhook fails closed (403) if either is missing. Never leave them blank in
production.

Run `php artisan config:cache` after changing `.env`. All lead services read
these values through config files, so config caching is safe.

## 2. Database

```bash
php artisan migrate
```

The lead tables are `leads`, `lead_messages`, and the shared `jobs` table.
The pruning command deletes only old `REJECTED` and `NON_SALES` leads.

## 3. Queue worker

Inbound messages are queued on the `leads` queue. The webhook returns immediately
and the qualification conversation runs in the worker.

```bash
php artisan queue:work --queue=leads,default --tries=3 --timeout=60
```

Run this under Supervisor/systemd. If `QUEUE_CONNECTION=sync`, Meta waits for
OpenAI and WhatsApp sends inside the webhook request; that is not suitable for
production.

## 4. Meta webhook

Set the callback URL in the Meta app dashboard to:

```text
https://your-domain/api/webhooks/whatsapp
```

Set the verify token to the same value as `META_WHATSAPP_VERIFY_TOKEN`.

Subscribe to the `messages` field for the WhatsApp Business Account. Meta signs
every POST with `X-Hub-Signature-256`; the app verifies it against
`META_APP_SECRET`.

For local development, expose the app with a tunnel (for example ngrok) and set
the same callback URL in a test Meta app. A blank app secret is rejected.

## 5. Re-engagement template

Free-form replies are only allowed inside WhatsApp's 24-hour customer-service
window. Outside it, the admin page can send an approved template. Create the
template in Meta, then set:

```dotenv
META_WHATSAPP_REENGAGEMENT_TEMPLATE=your_template_name
META_WHATSAPP_REENGAGEMENT_TEMPLATE_LANGUAGE=en_GB
```

## 6. Scheduled pruning

Add the command to the scheduler (`app/Console/Kernel.php` or
`routes/console.php`, depending on the app):

```php
$schedule->command('leads:prune')->daily();
```

It uses `LEAD_RETENTION_DAYS` when `--days` is not supplied.

## 7. Health check

Open **Admin → Leads → Integration health** at `/admin/leads/health`.
It reports whether the Meta credentials, OpenAI key, alert destination, and
asynchronous queue are configured. It does not call the upstream APIs.

## 8. Tests

The lead suite runs against the normal MySQL test setup used by this project:

```bash
php artisan test tests/Feature/LeadQualificationTest.php \
  tests/Feature/LeadInterpreterTest.php \
  tests/Feature/MetaWhatsAppServiceTest.php \
  tests/Feature/MetaAdAttributionTest.php \
  tests/Feature/LeadConversationTest.php \
  tests/Feature/LeadWebhookTest.php \
  tests/Feature/LeadAdminTest.php \
  tests/Feature/LeadOpsTest.php
```

The tests use database transactions and HTTP/Guzzle fakes; they do not call Meta,
OpenAI, Twilio, or send real WhatsApp messages.
