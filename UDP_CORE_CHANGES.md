# UDP Social — Core File Changes

Changes to upstream Friendica files. Every entry here is potential merge debt.
New UDP files (src/Module/Udp/*, UdpFeed.php, etc.) are NOT listed — those have zero conflict risk.

Before merging upstream: re-check each file below against the diff and re-apply as needed.

---

## Classification

| Category | Description | Merge risk |
|---|---|---|
| **1 — Upstream candidate** | Bug fix or improvement Friendica devs may want | Low — PR it, then drop our copy |
| **2 — Clean addition** | New files only, no core edits | None |
| **3 — UDP-specific core edit** | Must survive every upstream merge; cannot live upstream | High — track here |

---

## Tracked Changes

### `src/Module/BaseSettings.php`

**Category 3 — UDP-specific**

Two changes:

1. **Channels tab hidden when feature disabled** (Task 1)
   Wrapped the `Channels` tab entry in `Feature::isEnabled(...)` so it only appears when the Channels feature is on.
   *Why not a subclass:* `BaseSettings` builds the shared tab list used by all settings submodules; there is no single subclass entry point that covers all of them.

2. **Data Portability tab added**
   Injected a new `Data Portability` tab entry pointing to `settings/data-portability`.
   *Why not a subclass:* same reason as above.
   *Upstream candidate?* Possibly — if Friendica ships a native data portability module, coordinate or drop ours.

---

### `static/routes.config.php`

**Mixed: additive entries (Category 2 spirit) + route substitutions (Category 3)**

Route substitutions (UDP-specific, do not upstream):
- `/` → `UdpHome` (was `Module\Home`)
- `/home` → `UdpHome` (was `Module\Home`)
- `/directory` → `Udp\Directory` (was `Module\Directory`)
- `/network[/{content}]` → `UdpNetwork` (was `Module\Conversation\Network`)

New route additions (low conflict risk, additive only):
- `/admin/node-pair[/{action}]` → `Module\Admin\NodePair`
- `/timeline` → `Module\Conversation\UdpFeed`
- `/udp/directory` → `Module\Udp\DirectoryEndpoint`
- `/udp/pair` → `Module\Udp\PairEndpoint`

*Why not avoid this file:* Friendica has no hook or plugin system for adding routes; the config file is the only registration point.

---

### `view/theme/frio/templates/widget/peoplefind.tpl`

**Category 1 (partial) + Category 3**

Two changes bundled in one edit:

1. **Guard on `$nv.global_dir`** *(Category 1 — upstream candidate)*
   The original renders `<a href="">` when `system.directory` is blank, which navigates to the homepage. Wrapped in `{{if $nv.global_dir}}` to suppress the broken link.
   *Action:* PR this fix to Friendica upstream; once merged, revert our copy to theirs.

2. **Label hardcoded to "Directory"** *(Category 3 — UDP-specific)*
   Removed `{{$nv.local_directory}}` ("Local Directory") and the separate global-directory link. Replaced with a single "Directory" entry pointing to our cross-node `/directory` page.
   *Why not Widget.php:* changing the PHP translation string would touch a deeper core file for a cosmetic label. Template layer is the lesser evil.
   *Why not a new template:* frio already has `widget/peoplefind.tpl`; there is no outer layer to override it from.

---

### `view/js/dropzone-factory.js`

**Category 3 — UDP-specific**

Added chunked upload support for non-image files (video, audio, attachments):
- `chunking`, `chunkSize` (50 MB), `retryChunks`, `parallelChunkUploads` options added to the Dropzone constructor
- `processing` event updated to toggle chunking on/off per file type: images keep the existing `/media/photo/upload` endpoint (non-chunked); everything else uses `/media/attachment/upload/chunk` (chunked) with a 2 GB client-side ceiling so Dropzone doesn't reject large video files before upload starts

*Why not a subclass/new file:* dropzone-factory.js is the only Dropzone configuration point; there is no JS module override mechanism equivalent to PHP subclassing.

---

### `src/Module/Conversation/Network.php`

**Category 1 — Upstream candidate**

Two `json_decode()` calls pass the raw return value of `pConfig->get()`, which is `null` when the user hasn't set the preference. `json_decode(null)` generates a deprecation notice on PHP 8.1+ and will be a `TypeError` in PHP 9. Fix: added `?? ''` after each `pConfig->get()` call (lines 170 and 349). `json_decode('')` returns `null` identically; the existing `if (empty(...))` guards below both calls handle it correctly.

*Action:* PR this to Friendica upstream. Once merged, revert our copy.

---

### `src/Module/Welcome.php`

**Category 3 — UDP-specific**

Four string replacements to remove the hardcoded "Friendica" brand:
- Page title and heading: `'Welcome to Friendica'` → `'Welcome to %s'` with `system.sitename`
- Walk-through link: `'Friendica Walk-Through'` → `'Network Walk-Through'`
- Privacy notice: `'Friendica respects your privacy...'` → `'%s respects your privacy...'` with `system.sitename`

Introduced a `$sitename` local var (reads `system.sitename`, default `'Friendica'`) at the top of `content()`.

---

### `src/Util/EMailer/MailBuilder.php`

**Category 3 — UDP-specific**

Email notification title changed from hardcoded `'Friendica Notification'` to `system.network_name . ' Notification'` (short name, default `'Friendica'`). `$this->config` was already available in the class.

---

### `src/Module/Settings/TwoFactor/Verify.php`

**Category 3 — UDP-specific**

`$company` (used as the issuer label in the 2FA QR code / authenticator app) changed from hardcoded `'Friendica'` to `DI::config()->get('system', 'network_name', 'Friendica')`.

---

### `src/Content/Nav.php`

**Category 3 — UDP-specific**

Added `'$sitename'` to the `replaceMacros` call in `getHtml()`, reading from `system.sitename` config (default `'Friendica'`). This makes the brand name configurable per-instance without touching the template logic.

*Why not a subclass:* `Nav` is constructed by the DI container and consumed throughout the framework; no clean override point without wiring a full DI replacement.

---

### `view/theme/frio/templates/nav.tpl`

**Category 3 — UDP-specific**

Replaced hardcoded `Friendica` in `#navbar-brand-text` with `{{$sitename}}` (the variable added in `Nav.php` above). These two changes are a pair.

*Why not a new file:* frio's nav.tpl is the leaf template; there is no outer theme layer to override it from.

---

### `view/theme/udp/` (entire directory)

**Category 2 — New files only, zero conflict risk**

New mobile-first PWA theme that extends frio. Files:
- `theme.php` — `extends: frio` declaration; delegates all frio hooks; adds `viewport-fit=cover` meta
- `style.php` — outputs frio's full CSS then appends UDP mobile overrides (bottom nav, top bar, no sidebar, safe-area padding, iOS input-zoom prevention)
- `php/default.php` — layout: no sidebar, always `col-xs-12`, fixed top bar + bottom nav body padding
- `templates/nav.tpl` — replaces frio's desktop nav with: slim 48px fixed top bar + 5-tab 56px fixed bottom nav (Home→`/timeline`, Notifications, Compose→`/compose`, Messages, Me slide-up panel)

Activated via: `system.theme = udp` and `system.allowed_themes` includes `udp`.
Install theme hooks once via admin panel (Admin → Themes) or the theme will use frio's pre-registered hooks (same behavior, just logged under frio's file path).

*Merge risk:* None — entirely new directory, no upstream files touched.

---

### `src/App/Page.php` (line 494)

**Category 1 — Upstream candidate (PHP 8.2 deprecation fix)**

`mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8')` is deprecated in PHP 8.2 — the HTML-ENTITIES encoding mode was removed. Replaced with the equivalent `mb_encode_numericentity($content, [0x80, 0x10FFFF, 0, 0xFFFFFF], 'UTF-8')`, which converts non-ASCII characters to numeric HTML entities (`&#x...;`) that `DOMDocument::loadHTML()` understands equally well.

*Action:* PR to Friendica upstream.

---

### `src/Model/Post/Media.php` (`insertFromBody`)

**Category 1 — Upstream candidate (local video/audio not displayed)**

`insertFromBody()` strips `[video]` and `[audio]` BBCode tags from the body unconditionally, storing the URL in `post_media` for later rendering via `addVisualAttachments`. For locally uploaded files accessed via `/attach/N`, the `addVisualAttachments` path fails: the background worker can't fetch auth-gated URLs for dimension metadata, so width/height stay 0, and `getVideoAttachment()` falls back to `getAudioAttachment()` (rendering as an audio control or link instead of a video player).

Fix: skip the `str_replace` strip when the URL is local (`DI::baseUrl()->isLocalUrl()`). The BBCode tag remains in the body, `putInCache` renders it as a `<video>`/`<audio>` element (see BBCode.php fix above), and `containsEmbed()` in `addVisualAttachments` detects the URL is already in the body and skips the post_media entry — no duplicate.

*Action:* PR to Friendica upstream. Once merged, revert our copy.

---

### `src/Content/Text/BBCode.php` (`convertAudioVideoToHtml`)

**Category 1 — Upstream candidate (video/audio rendering bug)**

`convertAudioVideoToHtml()` only rendered `[video]` and `[audio]` BBCode as actual `<video>`/`<audio>` HTML elements for the `NPF` output mode (ActivityPub JSON-LD export). For all other modes including `INTERNAL` (local display) and `EXTERNAL`, the tags were converted to `[embed]` which `convertEmbedToHtml` turns into a plain `<a class="embed">` hyperlink — so locally uploaded videos showed as attachment links, not players.

Fix: extended the `<video>`/`<audio>` rendering branch from `$simple_html == self::NPF` to `in_array($simple_html, [self::NPF, self::INTERNAL, self::EXTERNAL])`.

Also fixed a typo in the original NPF audio regex: `>$1">$1</audio>` → `>$1</audio>`.

*Action:* PR to Friendica upstream. Once merged, revert our copy.

---

### `src/Render/FriendicaSmarty.php` (`is_null` modifier registration)

**Category 1 — Upstream candidate (Smarty 4 deprecation fix)**

Smarty 4 deprecated using unregistered PHP functions as template modifiers. `is_null` was used in templates but not registered. Added `registerPlugin('modifier', 'is_null', ...)` alongside the existing `is_string` registration.

*Action:* PR to Friendica upstream.

---

### `src/Core/Theme.php` + `src/Render/FriendicaSmarty.php` + `src/Render/FriendicaSmartyEngine.php`

**Category 1 — Upstream candidate (three-part bug fix for theme inheritance)**

Root cause: `Theme::getInfo()` parsed the `extends:` header from theme.php but silently dropped it because `'extends'` was not in the initial `$info` array (only keys that `array_key_exists` passes get set). Since `theme_info['extends']` is never populated at runtime, both `FriendicaSmarty` (template dirs setup) and `FriendicaSmartyEngine::getTemplateFile()` would skip the parent-theme lookup entirely. Result: any theme extending frio would load `view/templates/head.tpl` (base, no Bootstrap CSS) instead of `view/theme/frio/templates/head.tpl`.

Three fixes applied together:
1. **`Theme.php`**: Added `'extends' => ""` to the initial `$info` array so the header key is not silently discarded.
2. **`FriendicaSmarty.php`**: Added `Theme::getInfo($theme)['extends']` as fallback when `$theme_info['extends']` is empty, so the Smarty template dir for the parent theme is registered.
3. **`FriendicaSmartyEngine.php`**: Same fallback in `getTemplateFile()` + removed the stray `}` typo in the old `sprintf` format string (`'%sview/theme/%s}/%s'` → `'%sview/theme/%s/%s'`).

*Action:* PR all three to Friendica upstream. Once merged, revert our copies.

---

## Re-apply Checklist (after upstream merge)

1. `git diff upstream/stable..HEAD -- src/Module/BaseSettings.php` — reapply both hunks
2. `git diff upstream/stable..HEAD -- static/routes.config.php` — reapply route substitutions; new-route additions usually merge cleanly
3. `git diff upstream/stable..HEAD -- view/theme/frio/templates/widget/peoplefind.tpl` — check if upstream fixed the empty-href bug; if so, only reapply the label hunk
4. `git diff upstream/stable..HEAD -- src/Content/Nav.php` — reapply `$sitename` line in `replaceMacros`
5. `git diff upstream/stable..HEAD -- view/theme/frio/templates/nav.tpl` — reapply `{{$sitename}}` in `#navbar-brand-text`
6. `git diff upstream/stable..HEAD -- view/js/dropzone-factory.js` — reapply chunking options and processing event changes; check if upstream added its own chunking support first
7. `git diff upstream/stable..HEAD -- src/Module/Conversation/Network.php` — reapply `?? ''` on the two `json_decode(pConfig->get(...))` calls; check if upstream fixed it (Cat 1 — if merged upstream, drop ours)
7. `git diff upstream/stable..HEAD -- src/Module/Welcome.php` — reapply `$sitename` var + four string substitutions
8. `git diff upstream/stable..HEAD -- src/Util/EMailer/MailBuilder.php` — reapply `network_name` in notification title
9. `git diff upstream/stable..HEAD -- src/Module/Settings/TwoFactor/Verify.php` — reapply `network_name` for `$company`
10. `git diff upstream/stable..HEAD -- src/Core/Theme.php` — reapply `'extends' => ""` in getInfo(); check if upstream fixed it (Cat 1)
11. `git diff upstream/stable..HEAD -- src/Render/FriendicaSmarty.php` — reapply Theme::getInfo fallback for extends; check if upstream fixed it (Cat 1)
12. `git diff upstream/stable..HEAD -- src/Render/FriendicaSmartyEngine.php` — reapply Theme::getInfo fallback + `}` removal; check if upstream fixed it (Cat 1)
13. `git diff upstream/stable..HEAD -- src/Render/FriendicaSmarty.php` — reapply `is_null` registerPlugin; check if upstream fixed it (Cat 1)
14. `git diff upstream/stable..HEAD -- src/App/Page.php` — reapply `mb_encode_numericentity` replacement; check if upstream fixed it (Cat 1)
15. `git diff upstream/stable..HEAD -- src/Content/Text/BBCode.php` — reapply INTERNAL/EXTERNAL to video/audio rendering condition; check if upstream fixed it (Cat 1)
16. `git diff upstream/stable..HEAD -- src/Model/Post/Media.php` — reapply `isLocalUrl` guard in `insertFromBody` for video/audio; check if upstream fixed it (Cat 1)
