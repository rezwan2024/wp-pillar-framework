# WP Pillar Framework — Claude Code Master Instructions

> This file is read automatically by Claude Code at the start of every session.
> Follow every instruction in this file without exception.
> Do not skip any step. Do not assume anything is already done.

---

## STEP 1 — READ ALL PROJECT FILES FIRST

Before writing a single line of code, before asking any questions, before
doing anything at all — read these 3 files in this exact order:

```
1. requirements.md   <- full specs, rules, folder structure, file details
2. plan.md           <- phase by phase build instructions + starter prompts
3. progress.md       <- current build status, what is done, what is pending
```

Do not proceed to Step 2 until all 3 files are fully read and understood.

---

## STEP 2 — STUDY ARCHITECTURE PATTERNS

After reading the 3 project files, study the general folder structure, boot
system, routing, and ORM patterns described in `requirements.md` and `plan.md`.

Do not proceed to Step 3 until this is done.

---

## STEP 3 — CHECK CURRENT PROGRESS

Open `progress.md` and check:

- What is the **Active Phase**?
- Which phases are ✅ Complete?
- Which phase is 🔄 In Progress?
- Are there ❌ Failed phases that need to be redone?
- What files have already been created?
- Are there known issues logged?

**Rules:**
- Never rebuild a phase marked ✅ Complete
- Never skip a phase — always work in order (1 → 2 → 3 → 4 → 5 → 6 → 7)
- If 🔄 In Progress — resume from where it stopped
- If ❌ Failed — redo that phase completely before moving on
- If all 7 phases ✅ — report framework is fully built and ready for TicketWise AI

---

## STEP 4 — CONFIRM BEFORE STARTING

Before starting any work, write a short confirmation message that includes:

1. Which phase you are about to work on
2. What files you will create in this session
3. Summary of what was completed in previous sessions
4. Any known issues from progress.md to be aware of

Wait for the user to confirm before proceeding to Step 5.

---

## STEP 5 — EXECUTE THE CURRENT PHASE

Find the current phase in `plan.md` and execute it completely.

**Rules for execution:**
- Read the phase spec in `plan.md` fully before writing any code
- Build every file listed — no skipping
- Every file must have complete working code — no placeholders
- Follow exact file paths, namespaces, class names from `plan.md`
- Follow the architecture patterns studied in Step 2
- Run all required commands (composer install, composer dump-autoload)
- Fix any errors before marking phase complete

---

## STEP 6 — RUN PHASE COMPLETION CHECKS

After building all files, go through the phase checklist in `plan.md` item by item.

For every checklist item:
- Verify it is actually complete
- If not complete — fix it before moving on
- Do not self-certify without actually verifying

Show the full folder tree after every phase.

---

## STEP 7 — UPDATE progress.md

At the end of every session — whether the phase is complete or not —
update `progress.md`:

**Always update:**
- `CURRENT STATUS` — Active Phase, Last Session, Overall Progress
- `PHASE COMPLETION STATUS` table — status emoji for phase worked on
- `PHASE DETAILS` — check off every completed checklist item

**If phase complete:**
- Mark ✅ Complete with date
- Move Active Phase to next phase

**If phase incomplete:**
- Mark 🔄 In Progress
- Note exactly where you stopped and what remains

**Always add to FILES CREATED SO FAR:**
- Every new file created with full path

**Log to KNOWN ISSUES & DECISIONS:**
```
[Phase X] [ISSUE] Description and resolution.
[Phase X] [DECISION] What was decided and why.
```

Never delete existing content — only add or update.

---

## ABSOLUTE RULES — APPLY TO EVERY FILE IN EVERY PHASE

### Database Rules
1. **No `$wpdb` anywhere** — Eloquent ORM only, always
2. **No raw SQL strings** — always Eloquent models or schema builder
3. **No hardcoded table names** — prefix always from `$config['db_prefix']`
4. **Always eager load** — use `->with()` for relationships, never lazy load in loops

### Code Quality Rules
5. **Full type hints** — every property and method typed, no exceptions
6. **Full PHPDoc** — every class and method has complete documentation
7. **No placeholders** — every method has real, working implementation
8. **PHP 8.0+ features** — named args, union types, match, nullsafe allowed

### Security Rules — NEVER SKIP THESE
9. **Nonce in every route** — Router.php must verify `X-WP-Nonce` header on EVERY route
10. **No blind permission callbacks** — Policy class always, never `return true`
11. **PHP version check first** — plugin-entry.php checks PHP 8.0+ BEFORE any other code
12. **WordPress version check** — plugin-entry.php checks WP 6.0+ before loading
13. **Input through Request class** — never access $_POST/$_GET directly in controllers
14. **Multisite block** — every plugin-entry.php blocks multisite activation in v1.0

### Translation Rules — MANDATORY
15. **Translation pattern in AppServiceProvider** — always include wp_localize_script
    with `locale` and `strings` array — never hardcode UI text in Vue files
16. **All strings wrapped in __()** — every string in getTranslationStrings() must
    use `__('text', 'text-domain')` so translation plugins can scan them
17. **Text Domain in plugin header** — every plugin-entry.php must declare Text Domain

### Plugin Compatibility Rules
18. **3 plugin constants** — every plugin-entry.php defines PLUGIN_VERSION, PLUGIN_PATH, PLUGIN_URL
19. **Specific REST namespace** — always use plugin slug in namespace (e.g. ticketwise-ai/v1)
    to prevent collision with other plugins
20. **Specific db_prefix** — use descriptive prefix (e.g. twai_ not tw_)

### Architecture Rules
21. **Follow the standard scaffold patterns** — folder names, boot system, provider pattern
22. **Zero plugin-specific logic in framework/** — framework works for ANY plugin
23. **After every phase** — show complete folder tree
24. **After every phase** — confirm which architecture patterns were followed
25. **After every phase** — update progress.md

### Performance Rules
26. **Always paginate** — never return unbounded collections, always paginate(25)
27. **Eager load relationships** — always `->with('relation')`, never lazy in loops
28. **Index strategy** — all FK columns and search columns must have DB indexes

---

## PROJECT IDENTITY — NEVER CHANGE THESE

```
Framework name:       WP Pillar
PHP namespace:        WPPillar\Framework\
PHP minimum:          8.0+
WordPress minimum:    6.0+
Autoloading:          PSR-4 via Composer
License:              GPL-2.0-or-later
Database:             Eloquent ORM only — zero $wpdb
Frontend:             NOT in framework — each plugin owns its Vue 3 + Vite
Structure:            Copied into each plugin as framework/ folder
Pattern:              Option B — self-contained per plugin
Translation:          wp_localize_script pattern in AppServiceProvider scaffold
Security:             Nonce + Policy + PHP/WP version checks built into scaffold
Multisite:            NOT supported in v1.0 — block on activation
Responsive:           Required — plugins may release on WordPress.org
```

---

## COMPLETE TARGET FOLDER STRUCTURE

```
wp-pillar/
│
├── CLAUDE.md                               <- This file
├── requirements.md                         <- Full specs (v1.1)
├── plan.md                                 <- Phase instructions (v1.1)
├── progress.md                             <- Session memory
│
├── framework/                              <- WP Pillar core
│   ├── src/
│   │   ├── Application.php                 <- Phase 2
│   │   ├── Database/
│   │   │   ├── ORM.php                     <- Phase 3
│   │   │   ├── Migration.php               <- Phase 3 (with try/catch rollback)
│   │   │   └── Model.php                   <- Phase 3
│   │   ├── Http/
│   │   │   ├── Router.php                  <- Phase 4 (nonce verification built in)
│   │   │   ├── Request.php                 <- Phase 4
│   │   │   ├── Response.php                <- Phase 4
│   │   │   └── Controller.php              <- Phase 4
│   │   ├── Auth/
│   │   │   └── Policy.php                  <- Phase 5 (never returns true blindly)
│   │   ├── Support/
│   │   │   ├── ServiceProvider.php         <- Phase 2
│   │   │   ├── Config.php                  <- Phase 2
│   │   │   ├── Str.php                     <- Phase 5
│   │   │   └── helpers.php                 <- Phase 1 stub → Phase 2 full → Phase 4 updated
│   │   ├── View/
│   │   │   └── View.php                    <- Phase 5
│   │   └── Console/
│   │       └── Installer.php               <- Phase 5 (safe uninstall)
│   └── composer.json                       <- Phase 1
│
├── app/                                    <- Example scaffold
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ExampleController.php       <- Phase 6
│   │   ├── Policies/
│   │   │   └── ExamplePolicy.php           <- Phase 6
│   │   └── Routes/
│   │       └── api.php                     <- Phase 6
│   ├── Models/
│   │   └── ExampleModel.php                <- Phase 6
│   ├── Services/
│   │   └── ExampleService.php              <- Phase 6
│   ├── Hooks/
│   │   └── ExampleHook.php                 <- Phase 6
│   └── Providers/
│       └── AppServiceProvider.php          <- Phase 6 (translation pattern)
│
├── boot/
│   └── app.php                             <- Phase 6
│
├── config/
│   └── plugin.php                          <- Phase 6 (includes text_domain)
│
├── database/
│   └── migrations/
│       └── 2026_01_01_000000_create_example_table.php <- Phase 6
│
├── vendor/                                 <- Generated by composer install
├── composer.json                           <- Phase 1
└── plugin-entry.php                        <- Phase 6 (security + constants)
```

---

## WHAT IS NOT IN THIS FRAMEWORK

These items are intentionally excluded — they go in each plugin's own md files:

- Hook system (`do_action` / `apply_filters`) — plugin-specific
- Actual translation strings and text domain — plugin-specific
- API keys and cost limits — plugin-specific security
- GDPR / data privacy — plugin-specific
- Database design — plugin-specific
- External integrations (FreeScout, Claude API) — plugin-specific
- Vue component design — plugin-specific
- Addon plugin extensibility API — plugin-specific

---

## COMPOSER DEPENDENCIES

Only these 3 packages — do not add others without instruction:

```json
{
    "require": {
        "php": ">=8.0",
        "illuminate/database": "^10.0",
        "illuminate/events": "^10.0",
        "illuminate/container": "^10.0"
    }
}
```

All MIT licensed — compatible with GPL-2.0-or-later for WordPress.org.

---

## PHASE QUICK REFERENCE

| Phase | Name | Security added | Translation added |
|---|---|---|---|
| 1 | Foundation | — | — |
| 2 | Container + Config | — | — |
| 3 | Database | Migration rollback | — |
| 4 | HTTP Layer | Nonce in Router | — |
| 5 | Auth + Support | Safe uninstall | — |
| 6 | Example Scaffold | PHP/WP checks, constants, multisite block | wp_localize_script pattern |
| 7 | Verification | Security checklist | Translation checklist |

---

## AFTER FRAMEWORK IS COMPLETE — TICKETWISE AI

Once all 7 phases verified complete, TicketWise AI is built separately:

```
ticketwise-ai/
├── CLAUDE.md           <- TicketWise AI specific instructions
├── requirements.md     <- TicketWise AI specific requirements
├── plan.md             <- TicketWise AI build plan
├── progress.md         <- TicketWise AI progress tracking
├── framework/          <- Copy of WP Pillar framework
└── ... rest of plugin
```

Key TicketWise AI values:
- `db_prefix`: `twai_` (specific — avoids collision)
- `rest_namespace`: `ticketwise-ai/v1`
- `text_domain`: `ticketwise-ai`
- Constants: `TICKETWISE_AI_VERSION`, `TICKETWISE_AI_PATH`, `TICKETWISE_AI_URL`

---

## IF SOMETHING GOES WRONG

1. Do NOT move to next phase
2. Log issue in `progress.md` under KNOWN ISSUES
3. Fix in same session if possible
4. If whole phase needs rewrite — mark ❌ and redo
5. Report clearly what went wrong and what was done to fix it

Common fixes:
- composer install fails → check PHP 8.0+, run `composer clear-cache`
- Class fails to load → check namespace matches folder, run `composer dump-autoload`
- Nonce verification failing → check `X-WP-Nonce` header in request
- Translation not working → verify __() wrapping and text_domain match

---

## FINAL REMINDER

You are building a professional, reusable WordPress plugin framework.
Every file you create will be copied into future WordPress plugins.
Every shortcut now creates technical debt in every future plugin.

Write clean, complete, professional PHP 8.0+ code.
Follow the standard scaffold architecture patterns.
Never skip security rules — nonce, policy, PHP check, multisite block.
Never hardcode UI strings — always through translation pattern.
Update progress.md at the end of every session without fail.
