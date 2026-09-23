# Proposal: new website for the London Cathedral

*Draft for the Parish Council meeting on 6 October 2026.*

## Summary

A new, self-contained website for the Cathedral in English and Russian, hosted on the parish's existing Hostinger plan and one of the two domains already bought. It is built on WordPress, the most widely used website system, so that volunteers can post news and update pages much as they would write an email. No technical knowledge is needed. A working draft with the Cathedral's real content can be shown at the meeting.

## What the site will have

| Requirement | How it's met |
| --- | --- |
| **Two languages** | Every page and news item has an English and a Russian version, with a switch at the top of each page. |
| **Neat, informative design** | A text-first layout with no large banner photos. The front page shows the week's services, latest news, address, shop hours and main contacts at a glance. It works well on phones. |
| **News updated without IT knowledge** | Volunteers write news in a simple editor (title, text, optional photo, *Publish*). It appears on the front page and the News page straight away. |
| **Donations with Gift Aid** | A *Support the Cathedral* page with online giving (card, Apple Pay, Google Pay, one-off or monthly) including a Gift Aid tick box. Bank transfer, standing order and cheque details stay as today. |
| **Google Calendar of services** | The Cathedral's existing Google Calendar is shown on the front page and the Services page. Updating the calendar updates the website automatically. Nothing needs entering twice. |
| **A page for each parish group** | Sisterhood, Youth Group, Sunday School, Choir and Food Bank each have a page. Each group leader gets a personal login that can edit **only their own page** (both languages) and nothing else on the site. |

Current pages: Home · Services · News · Parish Life (with the group pages) · Clergy · Visit & Contact · Support the Cathedral.

## Donations and Gift Aid: decision needed

The Cathedral is a registered charity (no. 234203). To claim Gift Aid it must be registered with HMRC for Gift Aid, which is probably already the case given the paper declarations used today. Options for the online form:

| Option | Gift Aid | Notes |
| --- | --- | --- |
| **A. GiveWP + Stripe** (on our own site) | Gift Aid declarations collected with each donation and exported for the treasurer to claim from HMRC | Donors never leave the site. Stripe charges standard card fees. The treasurer submits claims (as now). |
| **B. Hosted charity platform** (e.g. Donorbox, Enthuse, CAF Donate) embedded on the page | Declarations collected. Some platforms can also submit the claim to HMRC for you | Less for us to maintain. Platform fee on top of card fees. |

*Recommendation:* option B if the treasurer would like Gift Aid claims handled automatically, otherwise option A to keep fees lowest. Fees to be confirmed before signing up.

## Questions for the council

1. **Domain:** which of the two domains should be the main one? The other can redirect to it.
2. **Who does what:** who will post news? Who are the group leaders who need logins?
3. **Donations:** option A or B above, and who at the Cathedral holds the HMRC Gift Aid registration?
4. **The "few more requirements":** what else is needed? For example a contact form, a photo gallery, a parish newsletter sign-up, or keeping the old news archive.
5. **Russian text:** who can proof-read the Russian pages?
6. **Old site:** should orthodox-europe.org link or redirect to the new site once it's live?

## Hosting

The site runs on the parish's **existing Hostinger plan**. Nothing extra needs buying.

- **What runs where:**
  - WordPress runs on Hostinger, with Hostinger's free SSL certificate and its automatic backups.
  - The site's design and features are kept on GitHub and copied to Hostinger when they change.
  - Pages, news and photos live in WordPress itself, so editors never need GitHub.
- **Preview first:**
  - Before the council meeting the draft goes on Hostinger at a preview address, e.g. `new.<domain>`, or the temporary address Hostinger provides.
  - It's hidden from Google, so councillors can click through a real site on their own phones.
- **Domains:**
  - At go-live the chosen domain points at the new site, and the second domain forwards to it.
  - If the domains were bought somewhere other than Hostinger, their settings are updated to point at Hostinger.
- **Moving from the old site:**
  - The current pages sit inside the diocesan website (orthodox-europe.org), which we don't control, so they can't be "transferred" as a whole.
  - The content has already been copied into the new site instead.
  - If the old news archive is wanted, we'll ask the diocesan webmaster for an export.
  - We'll also ask them to link the old pages to the new site.
- **Access:**
  - The organiser adds the developer to the Hostinger account through hPanel's *access management*, so no passwords need to be shared.
  - Parish volunteers get their own WordPress logins.

**Needed from the organiser now:**
- Hostinger access.
- Which Hostinger plan the parish has.
- Where the two domains were registered.

## Timeline

| When | What |
| --- | --- |
| **by Fri 25 Sep** | Organiser grants Hostinger access and confirms the plan and the domain registrar |
| **by Wed 30 Sep** | WordPress set up on Hostinger at a hidden preview address; site and draft content installed; group leader login tested |
| **Thu 1 – Mon 5 Oct** | Organiser and clergy review the preview; fixes made; Russian proof-read begins |
| **Tue 6 Oct** | **Parish Council:** live demo on the preview address, decisions on the questions above |
| by Tue 13 Oct | Council decisions applied: main domain chosen, donation provider signed up, volunteer logins created |
| by Tue 20 Oct | Content finalised with clergy and group leaders; Russian proof-read complete; donation form tested |
| week of 26 Oct | Short training session (≈30 min) for the news editor and group leaders |
| **Tue 3 Nov** | **Go live:** main domain switched to the new site, second domain redirected; diocesan site links to it |

## Running costs

- **Hosting and domains:** already paid (Hostinger).
- **Software:** WordPress, Polylang and the parish's custom theme and plugin are free.
- **Donations:** card-processing fees, plus a platform fee for option B.
- **Maintenance:** WordPress updates itself. Volunteer time only.
