# Changes — bookingextension_oneclick

All notable changes to the One-Click trial-instance provisioning plugin are documented here.
Versions use the Moodle `YYYYMMDDXX` scheme; the human-readable release tag is in parentheses.

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
