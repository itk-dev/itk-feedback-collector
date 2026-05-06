# Tidy Feedback Collector

A Symfony application for collecting user feedback from websites. Website owners
embed a JavaScript widget on their site, allowing visitors to submit visual
feedback with screenshots and element selection.

## Prerequisites

- Docker Desktop
- [itkdev-docker-compose](https://github.com/itk-dev/devops_itkdev-docker)
- [Task](https://taskfile.dev/)

## Getting Started

```bash
# Start containers
task compose-up

# Install dependencies
task composer -- install
task npm-install

# Build assets
task build

# Run migrations
task console -- doctrine:migrations:migrate --no-interaction

# Load fixtures (optional)
task console -- doctrine:fixtures:load --no-interaction
```

The site is available at `tidy-feedback-collector.local.itkdev.dk`.

## Authentication

All pages require login except the API endpoints and the embeddable widget
assets.

**Local development:**

```bash
task console -- app:user:create admin@example.com
task console -- app:user:password admin@example.com
```

**Server:**

```bash
itkdev-docker-compose-server exec phpfpm bin/console app:user:create admin@example.com
itkdev-docker-compose-server exec phpfpm bin/console app:user:password admin@example.com
```

## Environment Variables

Configure these in `.env.local`:

```dotenv

# FreeScout integration (optional — needed for "Report as bug")
FREESCOUT_API_URL=https://your-freescout-instance.com
FREESCOUT_API_KEY=your-freescout-api-key
```

## Building Assets

The project has two separate asset builds:

```bash
task build          # Build everything
task build-app      # Build site assets only (CSS/JS for the application)
task build-widget   # Build the embeddable widget only
```

- **App** assets are output to `public/build/app/` and included in the site
  templates.
- **Widget** assets are output to `public/build/widget/` and served as a
  standalone script for embedding on external websites.

Each build has its own webpack config (`webpack.config.js` for app,
`webpack.widget.config.js` for widget).

## Widget

The feedback widget is a single JavaScript file that can be embedded on any
website. It provides a UI for submitting feedback with screenshots and element
selection.

### Setup

1. Create a website entry at `/admin/website/create`
2. Copy the embed snippet from the website detail page

### Embedding

Add the script tag to the target website's HTML:

```html
<script src="https://your-collector-domain.com/build/widget/widget.js"
        data-api-key="YOUR-API-KEY"></script>
```

The API endpoint is derived automatically from the script's `src` attribute. No
additional CSS file is needed — styles are bundled into the JavaScript file.

### Features

- Element selection with visual highlighting
- Automatic screenshot capture
- Draggable feedback form
- Resizable region overlay
- Keyboard shortcuts: `Shift+C` (open), `Ctrl+Enter` (submit), `Escape`
  (cancel)
- Email address remembered in localStorage

## Security

The widget uses a client-side API key to authenticate feedback submissions.
Since the key is visible in the page source, it cannot be treated as a secret.
A determined attacker could extract the key and submit feedback from anywhere.
This is an accepted trade-off for client-side feedback tools (the same model
used by Siteimprove, Google Analytics, etc.).

The following measures are in place to limit abuse:

- **Rate limiting** — Each API key is limited to 10 requests per minute
  (sliding window). Exceeding the limit returns `429 Too Many Requests`.
  See [`config/packages/rate_limiter.yaml`](config/packages/rate_limiter.yaml)
  and [`src/Controller/ApiController.php`](src/Controller/ApiController.php).
- **Origin validation** — The `Origin` header is checked against the registered
  website URL. Requests from unrecognized origins are rejected. Browsers enforce
  this header and it cannot be spoofed from client-side code.
  See [`src/Controller/ApiController.php`](src/Controller/ApiController.php).
- **CORS restriction** — The `Access-Control-Allow-Origin` header only reflects
  origins that match a registered website domain, rather than allowing `*`. This
  prevents other websites from making cross-origin requests with the API key.
  See [`src/EventSubscriber/CorsSubscriber.php`](src/EventSubscriber/CorsSubscriber.php).
- **Payload validation** — Requests are rejected if the total payload exceeds
  5 MB, the `data` field exceeds 2 MB, or the JSON is malformed. All string
  values in the data are stripped of HTML tags.
  See [`src/Controller/ApiController.php`](src/Controller/ApiController.php).
- **Write-only access** — The API key only permits creating feedback entries.
  It cannot be used to read, update, or delete data.
  See [`src/Controller/ApiController.php`](src/Controller/ApiController.php).


## How to setup a client

To set up the client on an external site, add the following to the site's
`.env.local`:

```dotenv
TIDY_FEEDBACK_CLIENT_URL=https://your-collector-domain.com
TIDY_FEEDBACK_CLIENT_API_KEY=your-api-key
```

## Test Sites

For development, test sites for Drupal and Symfony can be scaffolded into the
`sites/` directory:

```bash
task dev:setup
```

This sets up all three sites (collector + Drupal + Symfony) and configures API
keys automatically.


| Site | URL |
|---|---|
| Collector | `tidy-feedback-collector.local.itkdev.dk` |
| Drupal | `tidy-feedback-drupal.local.itkdev.dk` |
| Symfony | `tidy-feedback-symfony.local.itkdev.dk` |
