# Verification record

Verified locally on 2026-11-03 (Asia/Jakarta), PHP 8.3.33, Laravel 13.39.0, SQLite, Chrome headless.

- PHPUnit: **13 tests passed, 53 assertions**. Includes the two starter checks and 11 dashboard/authentication tests.
- Laravel Pint: passed.
- Composer audit during lock refresh: no known security advisories reported.
- Browser: login, SVG chart, next-page navigation, Pending filter, CSV download, search empty state, reset, logout and protected dashboard redirect passed. No JavaScript exceptions recorded.
- Responsive check: 1440px desktop and 390px mobile; mobile document has no horizontal overflow (the order table scrolls within its container).
- Screenshots: login.png, dashboard.png, mobile.png and reference.png. Inspected desktop and mobile layouts.

A date-boundary regression was found during testing: Eloquent's default date serialization included a time component. The Sale model now stores order dates explicitly as YYYY-MM-DD. The boundary test checks the first and last days and chart values.

The seeded default 30-day view contains 90 orders, 70 paid orders, $8,364 collected revenue and $1,405 pending amount. Seeds shift with the initial seed date; relative totals remain deterministic.

Local verification is not a production load, penetration, cross-browser or accessibility audit. CI status should be checked separately on GitHub.
