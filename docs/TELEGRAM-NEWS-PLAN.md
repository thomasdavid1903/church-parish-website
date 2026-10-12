# Plan: Telegram posts in the website's news feed

Goal: posts in the Cathedral's Telegram channel, [t.me/ROCORinLondon](https://t.me/ROCORinLondon), appear in the website's News automatically, with nobody copying them across.

## What the channel's posts look like

From the channel's public page (t.me/s/ROCORinLondon):

- **Most posts are bilingual in one message:** Russian and English together. Sometimes Russian comes first, sometimes English. The diocese's reposts also have French, and Russian in the old spelling.
- **Many are short notices** like "Vespers resume today" or "No trapezas over summer". They have no title.
- **Some are forwarded** from ROCOR Europe, and some are re-forwards of the channel's own older posts (the Albania pilgrimage appears twice).
- **Media varies:** some have photos, some have videos (often too big to show in the web preview), some are text only.
- **Some include personal phone numbers and emails**, for example the Sunday School head's mobile and Gmail.

## Ways to get the posts

| Option | How it works | Verdict |
| --- | --- | --- |
| **A. Telegram bot** (recommended) | A bot is added as an admin of the channel. Telegram then pushes each new post to the website the moment it's published (a webhook to the site's own address). | Uses Telegram's official API and is instant. Also catches edits. Needs a channel admin to add the bot once. Doesn't bring in old posts. |
| **B. Read the public web page** | The site checks t.me/s/ROCORinLondon every 15–30 minutes and imports anything new. | No admin needed, but it breaks if Telegram changes the page. It only sees about the last 20 posts, and Telegram doesn't officially support it. |
| C. RSS service + import plugin | A third-party service (e.g. RSSHub, rss.app) turns the channel into a feed, and a plugin imports the feed. | Another service to rely on and possibly pay for, with less control over splitting languages. |
| D. Just embed the channel | Show Telegram's own widget on the News page. | Not real news posts: no search, no language split, nothing on the homepage. |

**Recommendation:** go with **A**, a small piece added to our existing `parish-core` plugin. Use **B once, at setup**, to import the older posts.

## How it would work, step by step

1. **Someone posts in Telegram** as they do now. Nothing changes for them.
2. **Telegram sends the post to the website.** The site checks a secret key, so only Telegram can create posts.
3. **The post is split by language.** Each paragraph is sorted by its alphabet: Cyrillic goes to Russian, Latin to English. That makes one English news post and one Russian one, linked as translations (Polylang) so the language switcher works. A post in only one language goes only to that side.
4. **A title is made automatically**, from the first sentence, shortened, with the greeting skipped ("Dear brothers and sisters!" isn't a useful title).
5. **Media:** photos go into the Media Library, and the first becomes the post's main picture (which also gives a preview image when the post is shared). Photo albums become one post with a gallery. Videos show a "Watch on Telegram" link, since they're too large to store on Hostinger.
6. **Formatting is kept:** links, bold and line breaks. A small "Originally posted on Telegram" link goes at the bottom.
7. **Edits in Telegram update the website post.** Deletions don't: Telegram doesn't report them, so a deleted message has to be removed on the website by hand.
8. **Duplicates are skipped.** Each post remembers its Telegram message number. Re-forwards of the channel's own posts are ignored.
9. **Opting out:** add `#nosite` to a Telegram post and it isn't imported.

## Things to decide

1. **Publish straight away, or as a draft for a quick check?**
   - The aim is full automation, so the suggestion is to publish directly.
   - The catch: personal phone numbers and emails would then be on the public web.
   - Suggestion: publish directly, but strip phone numbers and personal emails automatically, replacing them with "contact us via the Contact page".
2. **Diocesan forwards (ROCOR Europe):** import them, skip them, or put them in a separate "Diocese" category?
   - They're long, mostly in old Russian spelling, and already on orthodox-europe.org.
   - Leaning towards skipping them, which also fits David's point about duplication.
3. **Short notices** like "Vespers resume today": should they become news posts, or a small "Latest from Telegram" strip on the homepage instead?
   - Suggestion: the strip for posts under about 200 characters, full news posts for the rest.
   - That keeps the News page from filling up with one-liners.
4. **Better titles (optional):** use Claude to write a proper title and clean up the language split. It would cost pennies a month, but it's one more account and API key to look after.
5. **Avoiding a loop:** the go-live checklist also lists "website news → Telegram".
   - If we build both, posts that came from Telegram must never be sent back. That's easy to handle.
   - But we should decide whether we still want the other direction at all.

## What's needed from the parish

- A channel admin adds our bot to t.me/ROCORinLondon as an admin. It only needs to read posts.
- The live site running on Hostinger with HTTPS. Telegram can't reach the local preview, so testing would use a private test channel.

## Status

The importer is built (`wp-content/plugins/parish-core/includes/telegram.php`) and set up under **Settings → Parish → Telegram → News**. Tested with real posts from the channel pasted into the "Try a post" box: language split, titles, linked translations, duplicate skipping, French dropped, and separate EN/RU posts a minute apart joined as translations. Not yet tested with a real bot (photos, edits, formatting).

Defaults for now: posts publish straight away; forwards from other channels are skipped; every post becomes a news post; phone numbers are kept.

## Test channel setup

1. In Telegram, **New Channel** → e.g. "Cathedral website test", private.
2. Message **@BotFather** → `/newbot` → give it a name → copy the token it sends.
3. In the test channel: **Channel info → Administrators → Add admin** → search for the bot → add it (default rights are fine).
4. On the website: **Settings → Parish**, paste the token in **Bot token**, set **Channel** to the test channel's link (or leave empty for a private channel), **Save**.
5. Post in the test channel (copy a few real posts from ROCOR London, with a photo or two), then click **Check Telegram now**.
6. On the live site, click **Switch on instant delivery** so posts arrive within seconds instead of every 5 minutes.

## Switching from the test channel to the real one

Do this once the live site is running on Hostinger and the test channel works. It takes about 15 minutes. You need a WordPress administrator, and a Telegram admin of t.me/ROCORinLondon with the right to add administrators.

1. **Decide first:** the open questions under "Things to decide" above (publish straight away or as a draft, phone numbers, diocesan forwards, short notices). Whatever the settings are at the moment of the switch is what the parish will see.
2. **Keep the same bot.** There's no need for a new one: the token stays the same, and the bot can be removed from the test channel afterwards.
3. **Add the bot to the real channel:** in t.me/ROCORinLondon, **Channel info → Administrators → Add admin** → search for the bot's username → add it. Default rights are fine; it only reads posts.
4. **Point the website at the real channel:** **Settings → Parish → Telegram → News**, set **Channel** to `ROCORinLondon` (no `@`, no link), **Save**. From now on, posts from any other channel, including the test one, are ignored.
   - Don't leave **Channel** empty: then the site imports from every channel the bot is in.
5. **Remove the test posts:** **Posts**, filter or search for the test posts, and move them to the Bin in both English and Russian. Also delete their photos from the **Media Library** if they're not wanted.
   - Real and test posts can't clash: each imported post is remembered by its channel *and* message number.
6. **Check it works:** ask the channel admin to publish a real post (or wait for the next one). If instant delivery is on it appears within seconds; otherwise click **Check Telegram now**, or wait up to 5 minutes. Check that both the English and Russian news posts appear and are linked by the language switcher.
   - If instant delivery isn't on yet, click **Switch on instant delivery** (live site only, needs HTTPS).
7. **Tidy up:** remove the bot from the test channel (or delete the test channel), and tick the Telegram item in the [go-live checklist](GO-LIVE-CHECKLIST.md).

**Good to know**

- Only posts published *after* the switch come in. Older posts from the channel are not imported. That needs the one-off import (step 4 of the build plan below), which isn't built yet.
- Posts made while the site is set to the test channel are lost for the website: Telegram keeps undelivered posts for 24 hours at most, and the site skips anything not from the named channel.
- Edits to real posts in Telegram update the website post. Deleting a post in Telegram does **not** delete it from the website; delete it there by hand.
- To stop importing altogether: **Switch back to checking every 5 minutes**, then clear the **Bot token**, or remove the bot from the channel.
- If nothing arrives, the status line under **Settings → Parish → Telegram** shows the last check and any error from Telegram (e.g. a wrong token, or the bot isn't an admin).

## Build plan (about a day of work)

1. A test bot and a private test channel.
2. Add the importer to `parish-core`: receiving posts, splitting languages, titles, photos, edits and the duplicate check.
3. A settings section under **Settings → Parish**: bot token, channel name, and switches for diocesan forwards and short notices.
4. A one-off import of the older posts from the public page.
5. Test with real-looking posts: bilingual, photo albums, video, forwarded and edited.
6. A section in the [editing guide](EDITING-GUIDE.md).
