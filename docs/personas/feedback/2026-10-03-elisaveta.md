# Mother Elisaveta — 2026-10-03

**Verdict:** With difficulty, because of an admin bug that already existed before today's changes.

## Goals
| Goal | Result | Notes |
| --- | --- | --- |
| Find her page after login | Partly | The Dashboard "Your group pages" box works. The **Pages** menu item gives "Sorry, you are not allowed to access this page." |
| Edit Sisterhood (EN/RU) | Yes | The edit screen opens. |
| Can't touch other pages | Yes | The new Contact and History pages, Settings → Parish and new news posts are all refused (403). |

## Problems
1. **Major (pre-existing)** `wp-admin/edit.php?post_type=page` returns "not allowed" for the Group leader role. It happens on the last commit (3f3e585) too, so today's changes didn't cause it. It is probably due to a newer WordPress release (the blueprint installs `latest`, now 7.1.2) needing another capability for the list screen. The fix lives in `wp-content/plugins/parish-core/includes/group-leaders.php`.
2. **Minor** The editing guide tells leaders to click **Pages**, which currently errors.

## In their words
> "It says I'm not allowed. Have I done something wrong?"

## Suggested fixes
- Find which capability the Pages list now checks and grant it, or point the Pages menu item at her own page's edit link. Then re-test.
