# Changelog

All notable changes to NyanHours are documented in this file.

## [0.2.0] - 2026-08-31

### Added

- Client invoice builder available from administrative reports.
- Branded invoice PDFs with editable items, issue date, billing period, and payment details.
- USD and EUR invoice support with separate payment instructions.
- Optional client billing email, prefilled when creating an invoice.
- Conservative grouping of nearly identical invoice item descriptions.
- Dashboard-wide date filtering for recorded time, earnings, and activities.
- Password visibility controls.
- Dedicated NyanHours branding for the internal interface.
- Local session storage support for the development server.

### Changed

- Improved English and Spanish date formatting throughout the interface and PDF reports.
- Simplified profitability information and refined Owner financial reporting.
- Improved report export controls and responsive invoice-builder styling.
- Kept client-facing PDF exports branded with the Nyansei Studio identity.

### Upgrade notes

Existing installations must run `database/migrations/013_client_billing_email.sql` once before deploying this release.
Real database credentials and invoice payment instructions belong in `config/config.local.php`, which is excluded from version control.

## [0.1.0] - 2026-08-29

- Initial public portfolio release with authentication, weekly timesheets, detailed tracking, team management, reports, profitability, and team payments.
