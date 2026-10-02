# Fr Deacon Andrew — 2026-10-02 (live site, full link and page test)

**Verdict:** With difficulty: fine to show the council, not yet to launch.

## Checked
- 120 pages crawled, 0 broken internal links, 0 PHP errors, unique page titles.
- External links (Google Calendar, Maps, holytrinityorthodox.com readings, azbyka.ru) all respond. 51 readings links were sampled.
- The 404 page and search work in both languages.

## Problems
1. **Major** Placeholders visible to the public on 4 group pages ×2 languages.
2. **Major** No email contact.
3. **Minor** Daily Readings links to the previous and next day without end, so search engines can crawl thousands of near-identical pages. Suggest `noindex` on dated readings pages.
4. **Minor** The live site still serves `style.css?ver=0.1.0`, so returning visitors may see old styles. Fixed in code; needs the theme upload.
