# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Widget CSS custom properties no longer leak into the host page and override
  site colors — all properties are now namespaced with `--itk-feedback-` and
  scoped to the widget containers instead of `:root` ([#7](https://github.com/itk-dev/itk-feedback-collector/issues/7))

## [1.0.0] - 2026-05-06

### Added

- Feedback collector Symfony application with API endpoint for receiving feedback
- Embeddable JavaScript widget with element selection, screenshot capture,
  draggable form, and keyboard shortcuts
- Website management admin panel for registering sites and API keys
- FreeScout integration — forward feedback as support conversations
- Leantime integration — forward feedback as change request tickets
- Form-based authentication with user create and password commands
- Origin validation and CORS restriction per registered website
- API rate limiting (10 requests per minute per API key)
- Payload validation with size limits and HTML sanitization
- Danish translations
- Woodpecker CI pipeline for staging deployment
- Webpack Encore builds for app assets and standalone widget bundle
- Test site scaffolding tasks for Drupal 11 and Symfony
