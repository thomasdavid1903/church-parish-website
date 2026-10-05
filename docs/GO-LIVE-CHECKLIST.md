# Go-live checklist

Everything to finish before the new site is switched on and announced to the parish. Tick items off as they're done (`[x]`). Target go-live in the [proposal](PROPOSAL.md): **Tue 3 Nov**.

## 1. Council decisions

- [ ] Main domain chosen, and the second domain to redirect to it
- [ ] Donation option chosen: **A** (GiveWP + Stripe) or **B** (hosted platform, e.g. Donorbox / Enthuse / CAF Donate)
- [ ] Who holds the HMRC Gift Aid registration, and who does the Gift Aid claims
- [ ] News editor(s) named
- [ ] Group leaders named, one per group page
- [ ] Russian proof-reader named
- [ ] Inbox that receives contact-form messages chosen, and who looks after it
- [ ] Whether the old pages on orthodox-europe.org link or redirect to the new site
- [ ] How much content to cut back (David's point about cognitive load, e.g. linking to Wikipedia instead of the long history)
- [ ] Ideas list ([IDEAS.md](IDEAS.md)): agree what's in for launch and what waits until later (e.g. Parish Membership online)

## 2. Hosting and domain

- [ ] Hostinger access granted to the developer (hPanel access management)
- [ ] WordPress installed on Hostinger, latest version
- [ ] Polylang installed and activated
- [ ] Theme and plugin uploaded and activated (zip upload or the GitHub deploy action)
- [ ] GitHub deploy secrets added, if the deploy action is used
- [ ] Content seeded (`wp eval-file tools/seed.php`) or built by hand
- [ ] Free SSL switched on; every page loads on `https://` with no warnings
- [ ] `http://` and `www.` versions redirect to the main `https://` address
- [ ] Domain DNS pointed at Hostinger (updated at the registrar if it isn't Hostinger)
- [ ] Second domain redirects to the main one
- [ ] Hostinger automatic backups switched on; a restore tested once
- [ ] WordPress automatic updates on (core, and Polylang)
- [ ] PHP version is current (8.1 or newer)

## 3. Donations

- [ ] Provider account opened in the Cathedral's name, linked to the Cathedral's bank account
- [ ] Charity number (234203) and HMRC Gift Aid details entered with the provider
- [ ] Donation form added to the Donate page (EN and RU)
- [ ] Gift Aid declaration wording checked against HMRC's model declaration
- [ ] One-off and monthly giving both work
- [ ] Preset amounts and "other amount" agreed
- [ ] Test-mode donation made with a test card: success page shown, receipt email arrives
- [ ] Failed / declined card shows a clear message
- [ ] One small **real** donation made and refunded, to prove money reaches the bank
- [ ] Treasurer can log in, see donations and export the Gift Aid list
- [ ] Receipt email has the Cathedral's name, charity number and a contact address
- [ ] Bank transfer details on the Donate page checked with the treasurer
- [ ] Works on a phone, including Apple Pay / Google Pay if offered
- [ ] Russian Donate page and form checked
- [ ] Fees confirmed and recorded for the council

## 4. Contact form and email

- [ ] **Settings → Parish → Send messages to** set to the chosen inbox
- [ ] Site email sending set up on Hostinger (SMTP or Hostinger mail), with SPF / DKIM for the domain
- [ ] Test message sent for each "Message for" option; each arrives with the right subject line
- [ ] Messages arrive in the inbox, not spam (check Gmail and Outlook)
- [ ] Pressing **Reply** goes to the visitor's address
- [ ] Thank-you message shows after sending, in EN and RU
- [ ] Empty fields and a bad email address give clear errors
- [ ] Sending the form twice quickly, or with junk text, is blocked by the spam protection
- [ ] Inbox keeper briefed (section 7 of the [editing guide](EDITING-GUIDE.md))
- [ ] WordPress admin email set to a parish address someone reads
- [ ] Password-reset email from WordPress arrives

## 5. Content

- [ ] Every page read through by the clergy, EN and RU
- [ ] Russian text proof-read by a native speaker
- [ ] Content trimmed where agreed (history, long pages)
- [ ] Service times checked against what's actually served
- [ ] Clergy names, titles and phone numbers checked
- [ ] Sacraments page checked by the clergy
- [ ] Visit page: station, bus and parking details checked
- [ ] Services page: how long the services last
- [ ] "Annual Parish Assembly" vs "Annual Parish Meeting": one name used everywhere
- [ ] Group pages: placeholders ("name to be added") filled in by group leaders
- [ ] Updated clergy photos: current, good-quality photos of every priest and deacon (including the missing ones of Hieromonk Theodore and Deacon Sergei), with their permission
- [ ] Photo gallery: photos added and published, or left as a draft
- [ ] Permission and credit for every photo; photo policy wording agreed
- [ ] Demo / sample news posts replaced with real ones (or kept only if true and current)
- [ ] Social media links checked (Instagram, Telegram for Cathedral, Youth Group, Diocese)
- [ ] Telegram plugin: new news posts are sent to the Cathedral's Telegram channel (t.me/ROCORinLondon) automatically. Needs a Telegram bot added as a channel admin; test with a post in EN and RU
- [ ] Footer: address, charity number, Gift Aid note correct
- [ ] Search for leftover placeholder text ("lorem", "TBC", "to be added", "Not acquired yet")
- [ ] Privacy policy page written (contact form, donations, cookies, embedded Google Calendar / Maps) and linked in the footer
- [ ] Safeguarding statement / contact, if the diocese requires one (Sunday School, Youth Group)

## 6. Service calendar

- [ ] **Settings → Parish**: the real Google Calendar ID(s) for EN and RU
- [ ] Calendars set to public in Google
- [ ] Upcoming services entered for at least the next few months
- [ ] Calendar shows on Home and Services, on desktop and phone
- [ ] "Add to my calendar" links work for Google and for iPhone / Outlook
- [ ] Daily readings show correctly
- [ ] Someone named to keep the calendar up to date

## 7. Accounts and roles

- [ ] Administrator accounts: at least two people, strong passwords, no shared logins
- [ ] Demo accounts deleted (`headsister`, `newseditor`, and any test admin)
- [ ] News editor account(s) created (Editor role)
- [ ] Group leader accounts created, each ticked to their own page
- [ ] Each volunteer logs in once and changes their password
- [ ] Test: a group leader can edit only their own page, EN and RU
- [ ] Test: the news editor posts a news item in EN and RU unaided
- [ ] Two-factor login for administrators (optional, recommended)

## 8. Testing the whole site

- [ ] Every menu link and footer link works, EN and RU
- [ ] Language switcher goes to the matching page on every page
- [ ] No broken links (run a link checker)
- [ ] Site search finds pages and news, in both languages
- [ ] 404 page shows for a wrong address
- [ ] Phones: iPhone Safari and Android Chrome; menu opens and closes; no sideways scrolling
- [ ] Desktop: Chrome, Safari, Edge, Firefox
- [ ] Older / larger-text users: zoom to 200%, text still readable
- [ ] Keyboard only: can reach and use the menu, search and forms
- [ ] Images have alt text; colour contrast passes
- [ ] Page speed acceptable on a phone connection (PageSpeed Insights)
- [ ] Sharing a page or news post on WhatsApp / Telegram shows a good preview image and title
- [ ] Google Maps link and embedded map open the right place
- [ ] Persona walk-throughs re-run on the live server

## 9. Security and maintenance

- [ ] Only needed plugins installed; unused themes and plugins deleted
- [ ] File editing in wp-admin turned off
- [ ] Login attempts limited (Hostinger tools or a small plugin)
- [ ] Backups confirmed running and a copy kept off Hostinger
- [ ] Someone named to check updates and backups monthly

## 10. Search engines and analytics

- [ ] "Discourage search engines" switched **off** at launch (**Settings → Reading**)
- [ ] Site title and tagline set; favicon shows
- [ ] Google Search Console set up and sitemap submitted
- [ ] Google Business Profile for the Cathedral updated with the new web address
- [ ] Analytics (optional): privacy-friendly, and cookie notice added if needed

## 11. Training and handover

- [ ] Editing guide updated and shared with volunteers
- [ ] ≈30-minute training session for the news editor and group leaders
- [ ] Inbox keeper and treasurer shown their parts
- [ ] List of who to contact when something breaks

## 12. Launch day

- [ ] Final backup taken before switching
- [ ] Domain switched to the new site; both domains checked from a phone off Wi-Fi
- [ ] Search engines allowed (section 10)
- [ ] Contact form and donation test once more on the live domain
- [ ] Diocesan webmaster asked to link (or redirect) the old pages
- [ ] Instagram / Telegram bios updated with the new address

## 13. Announcing to the parish

- [ ] Announcement after the Liturgy, and in the parish newsletter / WhatsApp / Telegram
- [ ] A news post on the site itself welcoming people to the new site
- [ ] A news post on the old website (orthodox-europe.org) announcing the new site, with a link to it (EN and RU; the diocesan webmaster may need to post it)
- [ ] Poster or card with a QR code at the kiosk
- [ ] Way for parishioners to report problems (the contact form) mentioned in the announcement
- [ ] Check the inbox and donations daily for the first two weeks
