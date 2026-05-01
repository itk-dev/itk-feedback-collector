# Tidy Feedback Collector

A Symfony application for collecting user feedback from websites. Website owners
embed a JavaScript widget on their site, allowing visitors to submit visual
feedback with screenshots and element selection.

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
        data-endpoint="https://your-collector-domain.com/api/feedback"
        data-api-key="YOUR-API-KEY"></script>
```

No additional CSS file is needed — styles are bundled into the JavaScript file.

### Features

- Element selection with visual highlighting
- Automatic screenshot capture
- Draggable feedback form
- Resizable region overlay
- Keyboard shortcuts: `Shift+C` (open), `Ctrl+Enter` (submit), `Escape`
  (cancel)
- Email address remembered in localStorage
