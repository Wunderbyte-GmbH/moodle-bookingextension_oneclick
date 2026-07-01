# Changes — bookingextension_oneclick

All notable changes to the One-Click trial-instance provisioning plugin are documented here.
Versions use the Moodle `YYYYMMDDXX` scheme; the human-readable release tag is in parentheses.

## 2026070102 (v1.3.0) — 2026-07-01

### Added
- `oneclick.list_instances` agent skill (read-only, R0): lists the current user's own trial
  instances by wrapping the provisioner `GET /jobs` endpoint (ownership-scoped via
  `X-Requester-User-Id`, so a user only ever sees their own). Each instance is presented with its
  address, status, template, payment state and expiry; an empty list yields a friendly
  "no instances yet" reply instead of an error. Name-derived read capability
  `bookingextension/oneclick:skill_oneclick_list_instances`.

### Changed
- `oneclick.create_instance` agent skill: `template_id` is now exposed to the planner **only when
  more than one template is configured**. With a single template the field is hidden, so the
  selection planner no longer asks for a template it does not need — the request goes straight to
  confirmation and preflight auto-resolves the only template. With several templates the choice is
  offered as before. Fixes single-template setups still being asked "which template?" (the earlier
  preflight auto-pick alone never ran, because selection asked first).

## 2026063000 (v1.2.1) — 2026-06-30

### Changed
- `oneclick.create_instance` agent skill: when no template is named, the template is now resolved in
  preflight instead of always asking. With exactly **one** configured template it is auto-selected
  (there is nothing to choose); with **several** the list (id + description) is presented for
  selection; a named-but-unknown template still shows the list. `template_id` stays optional input.

## 2026062400 (v1.2.0)

- Baseline: one-click personal trial Moodle/Booking instance provisioning via the oneclick-provisioner
  API (`/spawn` + `/execute`), the `oneclick.create_instance` (R3) and `oneclick.delete_instance` agent
  skills, live spawn preview, SAML2 SP auto-registration, and admin configuration (templates, host
  suffix, shared secret, register URL).
