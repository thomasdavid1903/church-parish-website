# Ideas for the website

Possible additions beyond the six requirements in `docs/PROPOSAL.md`, ranked by how much they'd help the [test personas](personas/README.md). Items marked **Done** are built.

## Worth doing first

| # | Idea | Why | Status |
| --- | --- | --- | --- |
| 1 | **Contact form** (or a parish email address) | The most common dead end in testing: Tom, Oksana and Richard had no way to write to anyone, and "Send a message" on group pages points to the Visit page. A form sent to the churchwarden or office avoids publishing a personal email address. | Idea |
| 2 | **Demo Editor account + news-posting test** | Marina (news volunteer) has never been tested. Add a `newseditor` login to `tools/seed.php` and run her walkthrough to prove volunteers can post news unaided (requirement 3). | Idea |
| 3 | **Sacraments page** | Baptism, weddings, confession, Communion, funerals and memorial services, each with who to contact. Galina's panikhida question had no clear answer before. | **Done:** `/sacraments/` and `/ru/tainstva/`, in the main menu |

## If the council wants them

These match question 4 in the proposal.

| # | Idea | Why | Status |
| --- | --- | --- | --- |
| 4 | **Default Cathedral photo** | Shared news has no preview image unless the post has its own photo. One good exterior photo fixes that and warms up the home page without a big banner. | Idea |
| 5 | **Newsletter / WhatsApp sign-up** | Parishioners already share news on WhatsApp. A link to join a broadcast channel is simpler than an email newsletter. | Idea |
| 6 | **Photo gallery** | Feasts, pilgrimages and parish events. Includes a photo policy, because photography during services isn't allowed. | **Done:** Parish Life → Photo gallery (EN + RU). Seeded as a **draft**; add photos and publish, and it appears in the menu by itself |
| 7 | **Old news archive** | Import the old news from orthodox-europe.org. Needs an export from the diocesan webmaster. | Idea |

## Nice to have

| # | Idea | Why | Status |
| --- | --- | --- | --- |
| 8 | **Feast-day banner** | A notice on the home page for major feasts (Pascha, Nativity) and changed service times, which editors switch on and off. | Idea |
| 9 | **"Add to my calendar"** | Lets parishioners subscribe to the service calendar on their phones, so changes appear automatically. | **Done:** under every service calendar, for Google Calendar and for iPhone / Outlook |
| 10 | **Parish House hire page** | The Visit page mentions hiring the Parish House but gives no details. | Idea |

## Probably not worth it

Live-streaming, event booking and member logins. They'd add upkeep for volunteers, and nothing in testing asked for them.

## Content still to confirm

Content that's left as it is for now, but should be checked before go-live:
- **Visit page:** station, bus and parking details.
- **Services page:** how long the services last.
- **News posts:** "Annual Parish Assembly" vs "Annual Parish Meeting".
- **Clergy page:** photos of Hieromonk Theodore and Deacon Sergei.
- **Russian text:** a native speaker's check.
- **Sacraments page:** the text is drafted from the existing pages and general Orthodox practice. Ask the clergy to check it.
