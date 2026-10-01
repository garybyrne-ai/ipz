# IPFO Country Guidance Portal

A custom WordPress plugin that adds a secure, premium Intended Parent client
portal to the existing [ipfertilityoptions.com](https://ipfertilityoptions.com/)
website, without touching the site's theme (UiCore Pro), page builder
(Elementor), existing pages, URLs, or branding.

This document covers the plugin's architecture and how to operate it. For a
short install summary see `readme.txt`.

## 1. Why this is additive, not a rebuild

Nothing in this plugin modifies WordPress core, the active theme, Elementor,
or any existing page. Everything is delivered as:

- Custom post types that are **not publicly queryable** (no new front-end
  URLs are created by the CPTs themselves).
- Custom database tables, namespaced `{$wpdb->prefix}ipfo_*`, created only
  on activation and left untouched by theme/plugin updates elsewhere.
- Eleven shortcodes and matching Elementor widgets that a site editor drops
  onto their own Elementor pages (e.g. `/client-portal/`), exactly like any
  other Elementor widget. The surrounding page — header, footer, global
  colours, fonts, container width — is still 100% the existing UiCore Pro
  page.
- CSS that is fully namespaced under `.ipfo-*` selectors (see
  `assets/css/ipfo-portal.css`) and only enqueued on pages that actually use
  a portal shortcode/widget (`includes/Frontend/Assets.php`), so the public
  site's performance is unaffected.

### Design tokens: copied, not invented

`assets/css/ipfo-variables.css` defines the portal's palette, type and
radii as CSS custom properties. These are not invented — they are copied
directly from the live site's own Elementor global kit
(`wp-content/uploads/uicore-global.css`, Elementor Kit #6):

| Token | Value | Source |
|---|---|---|
| `--ipfo-primary` | `#7F448A` | `--e-global-color-uicore_primary` |
| `--ipfo-secondary` | `#5F226B` | `--e-global-color-uicore_secondary` |
| `--ipfo-accent` | `#EEC7FF` | `--e-global-color-uicore_accent` |
| `--ipfo-headline` | `#100A05` | `--e-global-color-uicore_headline` |
| `--ipfo-text` | `#4C4C4C` | `--e-global-color-uicore_body` |
| `--ipfo-font-heading` | Blinker | `--uicore-typography--h1-f` etc. |
| `--ipfo-font-body` | Manrope | `--uicore-primary-font-family` |
| `--ipfo-radius` / `--ipfo-radius-sm` | 12px / 6px | `--ui-radius` / `--ui-radius-sm` |

If the site's Elementor global colours or fonts are ever changed, update
this one file and the entire portal follows.

## 2. Plugin architecture

```
ipfo-country-guidance-portal.php   Bootstrap: constants, autoloader, activation hooks
includes/
  Plugin.php                      Central boot sequence (hooks everything below together)
  Activator.php / Deactivator.php Install/uninstall lifecycle (data is never deleted on deactivate)
  Capabilities.php                `manage_ipfo_portal` capability + `ipfo_client` role
  Database/Schema.php             dbDelta table definitions, versioned upgrades
  CPT/                            Country Guide, Guide Chapter, Resource, FAQ (all non-public)
  Repositories/                   One prepared-statement CRUD class per custom table
  Services/                       Business logic: access control, invitations, registration,
                                   guides, checklist, acknowledgement, search, downloads,
                                   analytics, notifications (orchestrate repositories)
  Security/                       Token generation/hashing, rate limiting
  Rest/RestController.php         ipfo/v1 REST routes (progress, bookmark, checklist,
                                   acknowledge, search) — cookie-nonce authenticated
  Shortcodes/                     One class per [IPFO_*] shortcode
  Elementor/                      Widget category + a parametrised widget per shortcode
  Admin/                          wp-admin screens (Dashboard, Countries, Invitations,
                                   Users & Access, Checklists, Analytics, Access Logs, Settings)
                                   and CPT meta boxes
  Privacy/GdprHandler.php         WP core Export/Erase Personal Data integration
  Emails/Mailer.php               Branded wp_mail() wrapper (SMTP-plugin agnostic)
templates/                        PHP view templates rendered by the shortcodes
assets/                           Namespaced CSS/JS, conditionally enqueued
```

Everything is plain PHP with a minimal PSR-4-style autoloader
(`includes/Autoloader.php`) — no Composer install step is required to use
the plugin.

## 3. Database structure

Custom tables (created in `includes/Database/Schema.php`, prefix is always
read from `$wpdb->prefix` — never hard-coded as `wp_`):

| Table | Purpose |
|---|---|
| `ipfo_countries` | Country records (name, code, flag, description, status, last reviewed) |
| `ipfo_invitations` | Invitation codes/tokens, scope, usage limits, expiry |
| `ipfo_user_access` | The access-control source of truth: user ↔ (country\|guide\|resource) grants |
| `ipfo_guide_progress` | Per-user reading progress % and bookmarks (JSON) per guide |
| `ipfo_checklist_items` | Admin-defined checklist items per country |
| `ipfo_checklist_progress` | Per-user completion state for checklist items |
| `ipfo_acknowledgements` | Audit record of "I have read and understood" per user/guide/version |
| `ipfo_guide_versions` | Version/changelog history per guide |
| `ipfo_access_logs` | Append-only audit log (login, guide/resource access, downloads, permission changes) |

Custom post types (non-public, admin-only via `manage_ipfo_portal`):
`ipfo_guide`, `ipfo_chapter` (ordered via WordPress's native `menu_order`),
`ipfo_resource`, `ipfo_faq`.

## 4. Installation

1. Upload/activate the plugin as a normal WordPress plugin zip.
2. Activation creates the tables above, the `ipfo_client` role, and
   schedules a daily maintenance cron (expires overdue invitations). It
   does **not** create or modify any page.
3. Configure **IPFO Portal > Settings**: disclaimer text, whether email
   verification and/or admin approval are required, and which Elementor
   page each portal area lives on.

## 5. Admin workflow

1. **IPFO Portal > Countries** — add a country (name, code, flag,
   description, status).
2. **IPFO Portal > Country Guides** (CPT admin screen) — create a guide,
   assign it to a country, set version/review dates, enable/disable
   download & online viewing, mark whether acknowledgement is required.
3. **IPFO Portal > Chapters** — add chapters, assign each to a guide, and
   drag the native "Order" field to sequence the table of contents. No
   HTML editing is required.
4. **IPFO Portal > Resources** — upload a file (served only through the
   protected download endpoint, never a public Media Library URL), or add
   an external/video link; assign it to a country/guide/user/category.
5. **IPFO Portal > Checklists** — pick a country and add/reorder checklist
   items (drag-and-drop).
6. **IPFO Portal > Invitations** — generate a code (e.g. `IPFO-IRE-82K9X`)
   scoped to a country/guide/resources, set expiry and usage limits,
   optionally email it directly from the screen.
7. The Intended Parent registers via the invitation link, is auto-assigned
   the right country/guide/resources, and the admin can monitor everything
   from **IPFO Portal > Users & Access** (grant/revoke access, approve
   pending accounts, view acknowledgement history and activity) and
   **IPFO Portal > Analytics / Access Logs**.

## 6. Client workflow

Register → (optional email verification / admin approval) → secure sign
in → personalised dashboard showing their country, guide, progress and
remaining checklist items → digital booklet reader (table of contents,
previous/next, search, bookmarks, print, light/dark mode) → resources →
interactive checklist → acknowledgement of guidance → notified by email
whenever their guide is updated to a new version.

## 7. Elementor & UiCore Pro integration

- `includes/Elementor/ElementorIntegration.php` registers an **"IPFO
  Portal"** Elementor widget category and one widget per shortcode
  (`ShortcodeWidget`, parametrised per instance) — editors drag these onto
  any Elementor page exactly like a native widget. Nothing is registered,
  and the whole file is a no-op, if Elementor is not active.
- The existing UiCore Pro header/footer are untouched: shortcodes only
  render the inner content area of whatever Elementor page they're placed
  on, so visitors never leave `ipfertilityoptions.com`'s normal layout.
- All CSS is namespaced (`.ipfo-portal`, `.ipfo-dashboard`, `.ipfo-guide`,
  etc.) and never touches generic selectors like `.container`, `.button`,
  `.card`, `.header`.

## 8. Security implementation

- **Nonces** on every form (`wp_nonce_field`/`check_admin_referer`) and
  **CSRF protection for free** on REST writes, since WordPress core already
  rejects a cookie-authenticated REST request without a valid `X-WP-Nonce`.
- **Capability checks**: a single `manage_ipfo_portal` capability (granted
  only to Administrators) gates every CPT and every admin screen.
- **Prepared statements** throughout the repository layer — no raw SQL
  concatenation of user input anywhere.
- **Output escaping** (`esc_html`/`esc_attr`/`esc_url`/`wp_kses_post`)
  throughout every template.
- **Secure invitation tokens**: the human-shareable code (`IPFO-IRE-82K9X`)
  is not itself a secret; the link also carries a 256-bit CSPRNG token
  (`random_bytes`), and only its SHA-256 hash is stored — compared with
  `hash_equals()` to avoid timing attacks (`includes/Security/Tokens.php`).
- **Rate limiting** on login, registration, password reset, invitation
  validation and resource downloads (`includes/Security/RateLimiter.php`,
  transient-backed).
- **Central authorization**: every single access decision — can this user
  see this guide/resource/country — goes through one class,
  `AccessControlService`, so there is exactly one place to audit for
  IDOR-style bugs. Shortcodes, REST handlers and the download endpoint all
  call it; none re-implement their own check.
- **No public document URLs**: resource files are streamed through
  `ResourceDownloadService` behind a nonce + fresh permission check on
  every request, with `nocache_headers()` and `X-Content-Type-Options:
  nosniff`.
- **Privacy-conscious logging**: IP addresses are only ever stored as a
  salted SHA-256 hash, never in the clear.

## 9. Invitation system

`InvitationService` + `wp_ipfo_invitations`:

- `Tokens::invitation_code()` produces a human code like `IPFO-IRE-82K9X`
  (ambiguous characters like `0/O/1/I` excluded).
- `Tokens::secret_token()` produces the high-entropy link token; only its
  hash is persisted.
- An invitation carries an optional country, guide, resource list, max
  uses, single-use flag, and expiry. `validate()` enforces all of these and
  returns a generic error (never revealing *why* a code failed, to avoid
  enumeration). `redeem()` grants the corresponding access rows and
  increments usage. A daily cron (`ipfo_daily_maintenance`) auto-expires
  overdue invitations.

## 10. Guide & version system

- Chapters are ordered WordPress posts (`menu_order`), so the table of
  contents is just "drag to reorder" — no HTML editing.
  `GuideService::reading_time_minutes()` estimates reading time from word
  count.
- Publishing a new version (via the Guide's meta box) writes a row to
  `wp_ipfo_guide_versions`, updates the guide's current version meta, and —
  if "Acknowledgement Required" is set — emails every user with access and
  blocks them from dismissing the guide until they re-acknowledge
  (`AcknowledgementService`, `wp_ipfo_acknowledgements`). Acknowledgement
  is always presented next to the configurable disclaimer and is never
  described as legal advice or legal consent.

## 11. Resource protection

Resources are a non-public CPT. A client-facing "View"/"Download" link is
always a signed, nonced URL to `ResourceDownloadService`, which on every
single request: verifies the nonce, requires an authenticated session,
rate-limits, re-checks `AccessControlService::can_access_resource()`
(covering direct user grants, guide-level and country-level scoping, and
expiry), and only then streams the file with `readfile()` — the Media
Library URL for the underlying attachment is never exposed to the
front end.

## 12. Future expansion (architected for, intentionally not built yet)

Per the brief, these are **not implemented** in this release but the
architecture leaves room for them without reworking what's here:

- **AI Guidance Assistant** — `SearchService` already restricts every
  query to content the logged-in user is authorised to see; an AI layer
  could sit on top of that same authorised result set (retrieval over
  guides/chapters/FAQs the user already has access to) and would need to
  clearly separate general information from legal advice, per the
  existing disclaimer system.
- **Appointment booking, secure messaging, case management, e-signatures,
  journey milestones, webinars, multi-IP cases, agency/partner access,
  automated reminders** — the `Services/` + `Repositories/` + custom-table
  pattern used here (e.g. `AccessControlService`, `wp_ipfo_user_access`)
  is the template for adding any of these as new, isolated modules later
  without touching existing tables or classes.
- **PWA shell** — assets are already conditionally loaded and namespaced;
  a manifest + service worker could be added as an opt-in layer without
  making protected documents available offline (which the brief
  explicitly says must not happen until that is properly secured).
