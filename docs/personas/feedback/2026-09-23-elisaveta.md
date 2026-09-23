# Mother Elisaveta — 23 September 2026

**Verdict:** Code review suggests yes. Needs a hands-on run as `headsister`.

Reviewed `wp-content/plugins/parish-core/includes/group-leaders.php`. Not tested in the browser, because Claude doesn't enter passwords.

## What works (by design)
- The dashboard widget "Your group pages" has an **Edit** button for each assigned page, in both languages. She's pointed at her page straight away.
- She can edit only her assigned pages and their translations. She can't delete pages, can't create new pages, and has no access to news (no `edit_posts`), menus or settings.
- Pages → All Pages lists only her pages, in both languages.

## Things to check on a manual run
1. **Minor** In the Polylang Languages panel she might change a page's language or press "+" to add a translation. Adding a translation is blocked by `wp_die` with a friendly message. Changing the language of an existing page is not blocked, and could "lose" the Russian page.
2. **Minor** The default dashboard widgets (Activity, WordPress Events and News) may push her widget down. Consider removing them for leaders.
3. **Minor** She can create drafts through the REST API (`edit_pages` without `publish_pages`). Harmless, but untidy.
4. Check that "Save" in the editor matches the widget text ("Changes appear … as soon as you press Save").
5. Check that revisions are available so she can undo a mistake.
6. Replace the placeholders on the Sisterhood page ("Head Sister: name to be added", "Details to be added"). This is the content the persona exists to fill in.
