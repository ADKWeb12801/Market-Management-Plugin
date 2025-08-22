# Decisions

- Magic link authentication selected with HMAC tokens expiring after 24 hours.
- Profile editing writes directly to Smart Custom Fields post meta to maintain compatibility.
- Weekly highlights grouped by configurable week start day (default Saturday).
- Vendors can be enabled or disabled from the assignment table; disabling removes the `_gffm_enabled` flag.
