# Website editing guide

How to keep the Cathedral website up to date. No technical knowledge is needed: if you can write an email, you can do all of this.

> **⚠️ Draft: update this guide when the website goes live.**
> It was written while the site was still being built, so some details are placeholders or not yet confirmed. Before handing it to volunteers, check and update:
> - the real website address (replace `https://example.org/wp-admin` in section 1);
> - which Google account owns the English and Russian service calendars (section 4);
> - the names of the administrators, news editor and group leaders, if you want to include them (sections 2 and 6);
> - every step against the live admin screens, especially button names, which can change with WordPress and Polylang updates.
>
> Then delete this note.

## Contents

1. [Logging in](#1-logging-in)
2. [Who can do what](#2-who-can-do-what)
3. [News](#3-news)
4. [Service schedule (the calendar)](#4-service-schedule-the-calendar)
5. [Editing pages](#5-editing-pages)
6. [Group leader pages](#6-group-leader-pages)
7. [The menu](#7-the-menu)
8. [Things that need a developer](#8-things-that-need-a-developer)
9. [Quick reference](#9-quick-reference)

---

## 1. Logging in

Go to the website address followed by `/wp-admin`, for example `https://example.org/wp-admin`, and sign in with your username and password.

You'll see the **Dashboard**, with a menu down the left-hand side. To go back to the public site, click the Cathedral name in the black bar at the top.

Forgotten your password? Click **Lost your password?** on the login screen and a reset link will be emailed to you.

---

## 2. Who can do what

Each person has their own login. What you can do depends on your role.

| Role | Who | Can do |
| --- | --- | --- |
| **Administrator** | One or two trusted people | Everything: accounts, settings, menus, all pages and news. |
| **Editor** | The news volunteer | Write and edit news, and edit any page. Can't change settings, menus or accounts. |
| **Group leader** | Head sister, youth leader, choir director, etc. | Edit **only their own group's page** (English and Russian). Nothing else. |

Please don't share logins. If someone new needs access, an administrator can create an account for them (see [section 6](#6-group-leader-pages)).

---

## 3. News

News items are **Posts** in WordPress. You never edit the News page itself: every published post appears there automatically, newest first. The 4 newest also appear under "Latest news" on the home page.

### Add a news item (English)

1. In the left-hand menu, go to **Posts → Add New Post**.
2. Type the **title** at the top, then click below it and write the text.
   - Press **Enter** for a new paragraph.
   - To add a photo, a heading or a list, click the **+** button and choose **Image**, **Heading** or **List**.
   - To add a link, select some words and click the 🔗 link button.
3. In the right-hand sidebar, check that **Language** says **English**. The **Languages** box is the last one in the sidebar: scroll down to see it.
4. Click **Publish** (top right). The confirmation panel shows **Language: English** at the bottom. Click **Publish** again to confirm.

> If you write in Russian first, the editor starts new posts in whichever language is selected in the language filter at the top of the admin (the news editor's account is set to **Русский**). If a Russian title is set to English, a yellow warning appears with a **Switch to Русский** button. Click it, then **OK** when asked to confirm.

The post is now live on the News page and the home page.

### Add the Russian version

Every news item should have a Russian version, so that Russian-speaking visitors see it too.

1. Go to **Posts → All Posts**.
2. Find the English post. In the language columns next to it, click the **+** under the Russian flag.
3. A new, empty post opens, already linked to the English one. Write the Russian title and text.
4. Click **Publish**.

The Russian post appears on the Russian site (addresses starting `/ru/`). Visitors can switch between the two versions with **English / Русский** at the top of the page.

> If the news is only relevant in one language, it's fine to publish only that version.

### Tips for good news posts

- **Make the first sentence count.** The News page shows the date, title and the first couple of lines of each post. Put the key facts (what, when, where) first.
- **Keep titles short and clear**, e.g. "Sunday School resumes on 13 September".
- **Photos are optional.** If you add one, choose something small and clear. Please don't add photos of children without their parents' permission.
- **Dates:** a post is dated the moment you publish it. To back-date an old announcement, click the date next to **Publish** in the sidebar and change it before publishing.

### Edit or remove a news item

- **Edit:** go to **Posts → All Posts**, hover over the post and click **Edit**. Make your changes and click **Save**. Remember to update the Russian version too.
- **Remove:** hover over the post and click **Trash**. Trashed posts can be restored from the **Trash** link at the top of the list for 30 days.
- **Draft for later:** click **Save draft** instead of **Publish**. The post stays private until you publish it.

---

## 4. Service schedule (the calendar)

**The schedule is not edited on the website.** The calendars on the Home page and the Services page are live views of the parish's **Google Calendars**. When a service is added or changed in Google Calendar, the website shows it straight away. There's nothing to update on the website itself.

There are **two** Google Calendars:

- the **English** calendar, shown on English pages;
- the **Russian** calendar, shown on Russian pages.

Please make each change in **both** calendars. If the Russian calendar isn't set up, Russian pages show the English one.

### Add or change a service

1. Open [Google Calendar](https://calendar.google.com) and sign in with the account that owns the parish calendars.
2. Click the day and time of the service, or an existing service to change it.
3. Enter the service name (e.g. "Divine Liturgy", "All-night Vigil"), the start and end times, and a description if needed.
4. Make sure the correct calendar (English or Russian) is selected in the drop-down, then click **Save**.
5. Repeat in the other language's calendar.

For a service that happens every week, use the **Does not repeat** drop-down to choose **Weekly on Saturday**, etc. Then there's no need to add it each week.

To cancel a single service, click it and delete **this event** only, not the whole series.

### What visitors see

- On computers, the calendar shows a month view on both the Home and Services pages.
- On phones, it switches to a list of upcoming services, which is easier to read on a small screen.
- Under each calendar is an **Open in Google Calendar** link. Visitors can use it to add the parish calendar to their own phone.

### Setting which calendars are used (administrators)

This is normally done once, when the site is set up.

1. In Google Calendar, open **Settings** (⚙) and click the calendar's name on the left.
2. Under **Access permissions for events**, tick **Make available to public**.
3. Under **Integrate calendar**, copy the **Calendar ID** (it looks like `abc123@group.calendar.google.com`).
4. On the website, go to **Settings → Parish** and paste it into **Google Calendar ID (English)** or **(Russian)**. You can also paste the full embed code; the site picks out the ID.
5. Click **Save Changes**.

### Adding a calendar to another page

A calendar can be added to any page. In the page editor, click **+** and search for **Service schedule**. Select the block, and the right-hand sidebar lets you choose:

- **View:** a list of upcoming services, a week or a month;
- **Height (px):** how tall the calendar is;
- **Different calendar ID (optional):** e.g. to show a separate calendar for one group. Leave it empty to use the parish calendar.

### Daily readings

The **Daily Readings** page (under Services) fills itself in every day. It shows the Epistle and Gospel, the saints of the day and the troparia, following the Old Style calendar. The content comes from the [Holy Trinity Orthodox Church calendar](https://www.holytrinityorthodox.com/calendar/), so there is nothing to update. You can edit the introduction above it like any other page. The Russian page shows the readings in Russian and links to «Азбука веры» for the full texts. If the calendar website is down, the page says "The readings could not be loaded just now" and tries again after 15 minutes.

---

## 5. Editing pages

Pages are the permanent parts of the site: Services, Parish Life, Clergy, Visit & Contact, Support the Cathedral, and so on.

1. Go to **Pages → All Pages** and click the page's title.
2. Click the text you want to change and type. Changes are made directly on the page, as they'll look to visitors.
3. Click **Save** (top right).
4. **Update the Russian version as well:** go back to **Pages**, then click the ✏️ pencil in the Russian column for that page.

### Useful things to know

- **Undo:** press **Ctrl + Z** (or ⌘ + Z on a Mac), or use the ↶ arrow at the top left.
- **Previous versions:** every save is kept. In the right-hand sidebar under **Page**, click **Revisions** to see or restore an earlier version.
- **Boxes and columns:** the boxed "cards" (e.g. a clergy member, or opening hours) are groups of blocks. Click inside a card to edit its text. Use the **List View** button (☰, top left) to see the page's layout and select exactly what you want.
- **Clergy photos:** click a photo, then **Replace** in the toolbar above it, and choose a new image from the **Media Library** or upload one. Portrait photos about 250 × 320 pixels work best. To add a photo for someone who hasn't got one, click at the top of their card, press **+**, choose **Image**, and add the class `parish-person__photo` under **Advanced** in the sidebar so it's styled like the others.
- **Phone numbers:** write them in full, e.g. `+44 7935 700 721`.

---

## 6. Group leader pages

Each parish group (Sisterhood, Youth Group, Sunday School, Choir, Food Bank) has its own page under **Parish Life**. The group's leader can have a login that edits **only that page**, in both languages.

### For group leaders

1. Log in and click **Pages** in the left-hand menu. You can open and edit only your own group's page(s).
2. Click your page, edit the text (meeting times, contact details, upcoming events) and click **Save**.
3. Use the pencil in the Russian column to update the Russian version.

You can't create or delete pages, or change news, menus or other people's pages. If you need something beyond your page, please ask an administrator.

### For administrators: create a group leader login

1. Go to **Users → Add New User**.
2. Enter a username, their email address and name. Set **Role** to **Group leader**.
3. Click **Add New User**. They'll get an email to set their password.
4. Go to **Users → All Users** and click the new user's name.
5. Scroll down to **Parish group pages** and tick their group's page. The Russian version is included automatically.
6. Click **Update User**.

When a group leader changes, edit the new person's account the same way. Then delete or downgrade the old account under **Users**.

---

## 7. The menu

*Administrators only.*

The menu across the top of every page is edited under **Appearance → Menus**. There are two menus, one per language: **Main menu (EN)** and **Main menu (RU)**.

- **Add a page:** tick it in the **Pages** box on the left, click **Add to Menu**, then drag it into place.
- **Make a drop-down:** drag an item slightly to the right, under another item (as the group pages sit under Parish Life).
- **Remove an item:** click the arrow on the item and choose **Remove**. This only removes it from the menu, not from the site.
- Click **Save Menu** when done, and make the same change in the other language's menu.

A menu can also be shown in the footer under "Links". Create a menu, then under **Manage Locations** assign it to **Footer** for each language.

---

## 8. Things that need a developer

Most things can be changed from the admin screens. These are built into the site's design, so please ask the web developer to change them:

- the footer text: the address, Google Maps link, Gift Aid note and charity number;
- the Cathedral name and subtitle in the header;
- colours, fonts and the general layout;
- new kinds of feature (e.g. a contact form, a photo gallery or online donations).

Please **don't** install or update plugins or themes, or change settings under **Settings**, unless the developer has asked you to. It can break the site.

---

## 9. Quick reference

| I want to… | Go to |
| --- | --- |
| Post news | **Posts → Add New Post** |
| Add the Russian version of a post | **Posts → All Posts**, then **+** in the Russian column |
| Change a service time | **Google Calendar** (both English and Russian calendars), not the website |
| Edit a page | **Pages → All Pages**, then click the title |
| Replace a clergy photo | **Pages → Clergy**, click the photo, **Replace** |
| Restore an older version of a page | In the editor sidebar: **Revisions** |
| Give a group leader a login | **Users → Add New User** (role *Group leader*), then tick their page |
| Change the top menu | **Appearance → Menus** (both EN and RU menus) |
| Change which Google Calendar is shown | **Settings → Parish** |
