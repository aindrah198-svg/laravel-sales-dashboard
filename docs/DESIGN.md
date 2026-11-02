# From design to working dashboard

The original reference is [design-reference.html](design-reference.html), a self-authored static HTML design concept. It is not a client Figma file and the project does not claim Figma delivery. Open it directly in a browser.

## Design decisions
- Northstar: a fictional independent retail business.
- Forest green, warm white and sage; system fonts; no remote fonts or image services.
- Desktop: persistent navigation, four summary cards, a two-column chart area, then orders.
- Mobile: compact header, two-column summary cards, stacked charts, horizontally scrollable table.

## Reference and implementation
| Reference | Working implementation |
|---|---|
| Four illustrative metrics | SQL aggregates, integer cents and paid-only revenue |
| Illustrative line | Daily database revenue with zero-filled dates and accessible data disclosure |
| Static channel breakdown | Paid revenue grouped by channel |
| Filter placeholders | Validated dates, status and literal customer/order search |
| Two sample table rows | Paginated records; filters retained across pages |
| Export label | Authenticated streaming CSV of all matching rows |

The layout, palette and information hierarchy follow the concept. The application adds login, real filter controls, error/empty states, CSV, keyboard focus and a daily data table. Reference numbers deliberately remain illustrative. See reference.png, dashboard.png and mobile.png for visual evidence.
