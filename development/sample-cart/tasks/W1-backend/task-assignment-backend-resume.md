# Worker 1b — Backend Engineer (pt-backend-eng) — RESUME SPAWN

You are **worker-1b**, replacing a previous backend worker that terminated early. Your
orchestrator is addressed as **`team-lead`**.

## Read these first (in order)
1. Your full base assignment (authoritative — file boundary, task sequence, reporting protocol):
   `/Users/cace/Local Sites/hrhealthcare/app/public/development/sample-cart/tasks/W1-backend/task-assignment-backend.md`
2. The spec: `/Users/cace/Local Sites/hrhealthcare/app/public/.claude/sample_cart_implementation_guide.md`

## State handoff — what's already done
- **`contract.md` is ALREADY WRITTEN** at
  `development/sample-cart/orchestration-ctx/decisions/contract.md` (8.7 KB, complete).
  **Do NOT rewrite it.** Read it — it's your own binding contract too.
- The **PHP scaffold was NOT started.** That is your immediate work.

## Your immediate work — finish Task #1 (scaffold only)
1. `TaskUpdate(taskId:"1", status:"in_progress")` and set yourself as owner
   (`TaskUpdate(taskId:"1", owner:"worker-1b")`).
2. Build the PHP scaffold (§ Task #1 detail step 3 in the base assignment): main plugin file with
   header docblock, `composer.json` (PSR-4 + phpoffice/phpspreadsheet ^2.0 + WPCS dev deps),
   `phpcs.xml`, run `composer install`, `src/Plugin.php` singleton boot on `plugins_loaded`,
   `src/Support/Manifest.php` (graceful if dist/manifest missing).
3. Verify `composer install` + `composer lint` run clean on the scaffold.
4. Write `completion-report.md`, `TaskUpdate(taskId:"1", status:"completed")`, send
   `COMPLETE: Task #1 done. <result>.` to `team-lead`.
5. Then work the rest of the W1 chain as tasks unblock: #3 → #5 → #7 (call `TaskList`,
   claim lowest available, `TaskGet` for full spec).

## CRITICAL protocol reminders (the previous worker violated these)
- **NEVER originate or emit a `shutdown_request` / `shutdown_response` / `shutdown_approved`
  message.** You were not asked to shut down. Only respond to a shutdown if `team-lead` sends you
  a genuine `shutdown_request`, and then only with a matching `shutdown_response` echoing its
  request_id. Do not emit shutdown-family messages spontaneously under any circumstance.
- Every `SendMessage` to `team-lead` is a **plain string** with one of the prefixes
  (`CHECKPOINT:` / `COMPLETE:` / `BLOCKED:` / `TURNING-POINT-BREAKING:`). Never a JSON object.
- Keep `progress-report.md` current; hold no critical state only in context.
- Report ONLY to `team-lead`; never message worker-2 directly.
