# Tidy Feedback — Development Environment

Development setup for testing the Tidy Feedback ecosystem: a collector
application, an embeddable widget client, and test sites for Drupal and Symfony.

## Prerequisites

- Docker Desktop
- [itkdev-docker-compose](https://github.com/itk-dev/devops_itkdev-docker)
- [Task](https://taskfile.dev/)

## Project Structure

```
tidy-feedback/
├── Taskfile.yml                # Root orchestrator
├── tidy-feedback-collector/    # Feedback collector (Symfony app)
└── site/                       # Test sites (scaffolded by tasks)
    ├── drupal/                 # Drupal 11 test site
    └── symfony/                # Symfony test site
```

## Getting Started

Start Traefik (if not already running):

```bash
itkdev-docker-compose traefik:start
```

Set up all three sites:

```bash
task setup
```

This will:

1. Start the collector and run migrations
2. Scaffold a Drupal 11 site, install Drush, install the client module
3. Scaffold a Symfony site, create a home controller, install the client bundle

## Post-setup

After setup, configure the API key for each test site. Create a website entry
in the collector admin at `tidy-feedback-collector.local.itkdev.dk/admin/website/create`,
then set the API key in each site's `.env.local`:

**Testsites**:

```dotenv
TIDY_FEEDBACK_CLIENT_URL=http://tidy-feedback-collector.local.itkdev.dk
TIDY_FEEDBACK_CLIENT_API_KEY=your-api-key
```

## Sites

| Site | URL |
|---|---|
| Collector | `tidy-feedback-collector.local.itkdev.dk` |
| Drupal | `tidy-feedback-drupal.local.itkdev.dk` |
| Symfony | `tidy-feedback-symfony.local.itkdev.dk` |

