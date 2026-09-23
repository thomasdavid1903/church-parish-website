# London Cathedral: parish website

A new website for the **London Orthodox Cathedral of the Nativity of the Mother of God and the Holy Royal Martyrs (ROCOR)**, 57 Harvard Road, Chiswick. It will replace the parish section of [orthodox-europe.org](https://orthodox-europe.org/english/parishes/london-cathedral/).

It is built on **WordPress** so that it runs on the parish's existing Hostinger hosting and can be updated by volunteers without technical knowledge. This repo holds only the parish-specific code. WordPress itself and off-the-shelf plugins are installed on the server.

| Path | What it is |
| --- | --- |
| `wp-content/themes/parish-cathedral/` | The theme: a clean, text-first design with no large hero images. Self-hosted PT Serif and PT Sans fonts, both with full Cyrillic support. |
| `wp-content/plugins/parish-core/` | Parish features: group leader accounts, the Google Calendar "Service schedule" block, and Settings → Parish. |
| `tools/seed.php` | Builds the page structure and draft content in English and Russian on a fresh install. |
| `blueprint.json` | Local dev setup for [WordPress Playground](https://wordpress.github.io/wordpress-playground/). |
| `docs/PROPOSAL.md` | Proposal for the parish council: requirements, approach, costs and timeline. |

## How the requirements are met

| # | Requirement | Solution |
| --- | --- | --- |
| 1 | Bilingual | [Polylang](https://wordpress.org/plugins/polylang/) (free). Every page and news post has linked English and Russian versions, with an **English / Русский** switch in the header. Russian URLs live under `/ru/`. |
| 2 | Neat and informative, no large images | Custom theme: small masthead, info cards (services, address, shop hours, contacts), and news and calendar on the front page. |
| 3 | News updated by non-technical staff | Standard WordPress **Posts → Add New**. The news manager gets the *Editor* role. Posts appear automatically on the home page and News page. |
| 4 | Donations with Gift Aid | *Support the Cathedral* page with Gift Aid explanation, bank and standing order details, and a slot for an online form. The form provider is a council decision; see `docs/PROPOSAL.md`. |
| 5 | Google Calendar of services | **Service schedule** block (`parish/service-calendar`). It embeds the Cathedral's existing public Google Calendar in the visitor's language. Set the calendar ID once under **Settings → Parish**. |
| 6 | Group leaders edit their own page | **Group leader** role. An admin ticks which page(s) a leader may edit (Users → edit user → *Parish group pages*). Leaders can edit only those pages and their translations. They can't create or delete pages, and can't touch news, menus or settings. |

## Local development

Needs only Node.js 18+ (no Docker, PHP or MySQL):

```bash
npm install
```

```bash
npm run dev
```

This opens a throwaway WordPress at http://127.0.0.1:9400 with Polylang installed, the theme and plugin mounted live from this repo, and demo content seeded. Edits to theme and plugin files show up on refresh. The database resets each time you restart.

- Admin: http://127.0.0.1:9400/wp-admin/ (Playground logs you in automatically)
- Demo group leader: user `headsister`, password `headsister` (can edit only the Sisterhood page, EN and RU)

> `.npmrc` sets `ignore-scripts=true` because an optional native dependency of the Playground CLI (`fs-ext`) fails to build on Windows. It isn't needed.

## Deploying to Hostinger

One-time setup in hPanel:

1. **Websites → Add website → WordPress** on the chosen domain. Enable free SSL.
2. In wp-admin: **Plugins → Add New**, then install and activate **Polylang**.
3. Upload the theme and plugin. Either:
   - zip `wp-content/themes/parish-cathedral` and `wp-content/plugins/parish-core`, then upload via **Appearance → Themes → Add New → Upload** and **Plugins → Add New → Upload**, or
   - use the GitHub Action in `.github/workflows/deploy.yml` (SSH/rsync, see below).
4. Activate the theme and plugin. Then, for the initial structure and content, run `wp eval-file tools/seed.php` over SSH (Hostinger has WP-CLI), or build the pages by hand.
5. **Settings → Parish**: check the Google Calendar ID.
6. Create accounts: the news manager (*Editor*), and each group leader (*Group leader*, then tick their page).

### Automatic deploys (optional)

`.github/workflows/deploy.yml` rsyncs the theme and plugin to Hostinger over SSH. Run it manually from the GitHub **Actions** tab. Add these repository secrets first:

| Secret | Example |
| --- | --- |
| `HOSTINGER_HOST` | `123.45.67.89` (hPanel → Advanced → SSH Access) |
| `HOSTINGER_PORT` | `65002` |
| `HOSTINGER_USER` | `u123456789` |
| `HOSTINGER_SSH_KEY` | a private key whose public half is added in hPanel |
| `HOSTINGER_WP_PATH` | `domains/example.org/public_html` |

## Content notes

- Draft content in `tools/seed.php` is taken from the current site (July 2026).
- **The Russian texts are a first draft and need checking by a native speaker.**
- Group pages have placeholders ("name to be added") for the group leaders to fill in themselves.
