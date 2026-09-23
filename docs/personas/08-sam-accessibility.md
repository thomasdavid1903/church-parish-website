# Sam, keyboard and screen-reader user

- **Age:** 34. Pronouns: they/them.
- **Language:** English.
- **Device:** Windows laptop, Firefox, NVDA screen reader, keyboard only (no mouse).
- **Tech comfort:** high, an expert screen-reader user.
- **Background:** blind since birth. A friend has invited them to a service and they want to check times and directions themself.

## Goals
1. Tab from the top of the home page to the main content (is there a "Skip to content" link?).
2. Navigate by headings and landmarks to find service times.
3. Use the language switcher and the main menu with the keyboard only.
4. Get useful information from the service calendar without seeing it.
5. Find walking directions from the station in text, not just a map.

## Behaviour
- Navigates by headings (H), landmarks (D) and links list (Insert+F7).
- Gives up on anything that traps keyboard focus or needs a mouse.

## Watch for
- Heading order (one H1, no skipped levels) and landmark regions (header, nav, main, footer).
- Visible focus indicator on every link and button.
- Link text that makes sense out of context (not "click here", "read more" repeated).
- The Google Calendar iframe: does it have a title, and is there a text alternative (e.g. a list of upcoming services or a link to the calendar)?
- `lang` attributes: Russian content marked `lang="ru"` so the screen reader switches voice.
- Image alt text, colour contrast (WCAG AA), and whether the page works at 200% zoom.
