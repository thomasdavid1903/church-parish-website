---
name: persona-test
description: Walk through the parish website as one or more of the test personas in docs/personas/ and write feedback. Use when asked to test the site as a persona, run the personas, or get user feedback on the site.
---

# Persona test

Arguments: persona numbers or names (e.g. `3`, `galina`, `1,4,6`), or `all`. Default: `all`.

1. Read `docs/personas/README.md` and the chosen persona files.
2. Start the site with `preview_start {name: "wordpress"}` (stop any old Playground on :9400 first so the seed is fresh). Wait until http://localhost:9400 responds.
3. For each persona:
   - Set the viewport to match their device (`resize_window`: phones 375×812, laptop desktop preset).
   - Switch language if they would (Russian personas use the **Русский** switch, not a typed URL).
   - Attempt every goal using only what's on the page. Use `read_page`/`get_page_text` to read, `computer` to click, screenshots to judge visual issues (text size, contrast, layout). For admin personas, log in at `/wp-admin/` (Playground auto-logs in as admin; for the group leader log out and use `headsister` / `headsister`).
   - Check every item under **Watch for**.
   - Text extraction (`get_page_text`, `innerText`) skips iframes, so check for embeds (Google Map, Google Calendar) with `document.querySelectorAll('iframe')` before reporting that something is "missing".
   - Stay in character: record where they'd hesitate or give up, not just hard failures.
4. Write one file per persona to `docs/personas/feedback/YYYY-MM-DD-<persona-slug>.md` using the template in the README.
5. Reply with a short summary: verdict per persona, then the top issues across all personas ranked by severity, with file/line pointers where the fix lives in the theme, plugin or `tools/seed.php`.

Do not fix anything during the test unless asked. The point is feedback.
