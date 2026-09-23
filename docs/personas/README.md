# Test personas

Reusable personas for walking through the site and giving feedback. Each one is a realistic visitor or volunteer with a goal, a device, a language and a level of patience. Test the site *as them*, not as the person who built it.

| # | Persona | Language | Device | Tests mainly |
| --- | --- | --- | --- | --- |
| 1 | [Galina, elderly parishioner](01-galina-elderly-parishioner.md) | Russian | Old Android phone, large text | Service times, readability, Russian |
| 2 | [Tom, first-time visitor](02-tom-first-time-visitor.md) | English | iPhone | "What do I need to know before I come?" |
| 3 | [Oksana, parent](03-oksana-parent.md) | Russian and English | Phone | Sunday School, contacting clergy, baptism |
| 4 | [Richard, donor](04-richard-donor.md) | English | Laptop | Donations, Gift Aid, trust |
| 5 | [Marina, news volunteer](05-marina-news-volunteer.md) | Russian first, English OK | Laptop | Posting news in wp-admin, both languages |
| 6 | [Mother Elisaveta, group leader](06-elisaveta-group-leader.md) | English and Russian | Tablet / laptop | Editing only the Sisterhood page |
| 7 | [Fr Deacon Andrew, council member](07-andrew-council-member.md) | English | Phone | Overall impression before the council vote |
| 8 | [Sam, keyboard and screen-reader user](08-sam-accessibility.md) | English | Laptop, keyboard only, NVDA | Accessibility |

## How to run a persona test

1. Start the site: `npm run dev` (http://127.0.0.1:9400). The database resets on restart, so seeded content is always the same.
2. Pick a persona. Set the device first (e.g. 375×812 for phones), then read their **Goals** and try each one using only what's on screen. No URL-typing, no admin knowledge unless the persona has it.
3. Note where they would hesitate, get lost, give up, or misunderstand something. Stay in character: Galina doesn't know what a "hamburger menu" is, Richard won't give money to a page that looks unfinished.
4. Write the feedback using the template below and save it as `docs/personas/feedback/YYYY-MM-DD-<persona>.md`.

With Claude Code you can just ask: *"Test the site as persona 3 and give me feedback"*, or *"Run all the personas"*.

## Feedback template

```markdown
# <Persona> — <date>

**Verdict:** would they succeed? (Yes / With difficulty / No)

## Goals
| Goal | Result | Notes |
| --- | --- | --- |

## What worked

## Problems
1. **[Blocker | Major | Minor]** What happened, where (page/URL), and why it matters to this persona.

## In their words
> A sentence or two the persona might actually say.

## Suggested fixes
```
