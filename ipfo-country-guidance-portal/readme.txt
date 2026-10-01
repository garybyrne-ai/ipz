=== IPFO Country Guidance Portal ===
Contributors: ipfertilityoptions
Tags: client portal, surrogacy, guidance, membership, elementor
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A secure, premium Intended Parent client portal for IP Fertility Options: country-specific surrogacy guidance, digital booklets, checklists, protected resources and invitation-based access control — built as an additive plugin on top of the existing UiCore Pro + Elementor website.

== Description ==

This plugin does not replace or modify the existing ipfertilityoptions.com website, theme, or Elementor content. It adds:

* Non-public custom post types for Country Guides, Guide Chapters, Resources and FAQs, managed only by administrators.
* A "Countries" management screen and a country-based access control model.
* A secure invitation/access-code system (e.g. `IPFO-IRE-82K9X`) with expiry, usage limits and per-invitation country/guide/resource assignment.
* Eleven Elementor-compatible shortcodes (`[IPFO_LOGIN]`, `[IPFO_REGISTER]`, `[IPFO_DASHBOARD]`, `[IPFO_MY_GUIDES]`, `[IPFO_GUIDE]`, `[IPFO_COUNTRY_GUIDES]`, `[IPFO_RESOURCES]`, `[IPFO_CHECKLIST]`, `[IPFO_PROFILE]`, `[IPFO_NOTIFICATIONS]`, `[IPFO_LOGOUT]`) and matching native Elementor widgets.
* A premium digital booklet reader (table of contents, chapter navigation, reading progress, bookmarks, search, print, light/dark reading mode) instead of a flat PDF download.
* Interactive country checklists, guide version tracking with required acknowledgement, and a protected resource library served through a nonce + permission-checked endpoint — never a raw Media Library URL.
* A WordPress-native admin area ("IPFO Portal") for countries, invitations, users & access, checklists, analytics and access logs.
* GDPR-conscious logging (IP addresses are hashed, never stored raw) and full integration with WordPress's native Export/Erase Personal Data tools.

See README.md in this folder for full architecture documentation.

== Installation ==

1. Upload the `ipfo-country-guidance-portal` folder to `/wp-content/plugins/`, or install the zip through Plugins > Add New > Upload Plugin.
2. Activate the plugin. This creates the plugin's database tables and the "Intended Parent (IPFO Client)" role; it does not touch any existing content.
3. Go to **IPFO Portal > Settings** and configure the disclaimer text, registration safeguards, and the Elementor pages where you've placed each portal shortcode/widget.
4. Go to **IPFO Portal > Countries** and add your first country.
5. Create a Country Guide (admin menu: IPFO Portal > Country Guides), add Chapters, and assign resources.
6. Go to **IPFO Portal > Invitations** to generate a secure invitation for an Intended Parent.

== Changelog ==

= 1.0.0 =
* Initial release.
