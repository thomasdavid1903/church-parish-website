# Mother Elisaveta — 2026-10-01 (photo update)

**Verdict:** Yes. The change hardly affects her: the Sisterhood page banner is unchanged and has no credit.

## Goals
| Goal | Result | Notes |
| --- | --- | --- |
| Her page looks the same as before | Yes | The Sisterhood banner is still `icon-flowers`. No credit, as expected. |
| Add a photo of the sisters with a credit | Probably | The Group leader role has `upload_files`, so the Photo credit field will be there for her uploads. Nobody has told her it exists. |

## What worked
- Nothing changed on her page, so nothing new for her to learn.

## Problems
1. **Minor** Group leaders can also open other people's photos in the Media Library, and could change Fr Sergei's credit. That's low risk, but she'd worry about breaking someone else's work. (`group-leaders.php`: `upload_files` doesn't limit editing to her own media.)

## In their words
> "If I put up a photo of the bake sale, do I have to sign it too?"

## Suggested fixes
- Add one sentence in the editing guide: "Only fill in Photo credit if the photographer asked to be named."
- Optional: let group leaders edit only their own media.
