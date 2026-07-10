# Current Application vs Client Needs - Gap Analysis

Assessment date: 2026-07-10

Status: implemented. The original findings are retained below as the pre-implementation baseline.

Scope: compare the application currently at `main` (`37087f0`) with `BRIEFS.md`, every document in `contexts/`, and the client clarification recorded on 2026-07-10 that document upload/download is not required. This is a findings document, not an implementation claim.

## Execution Result

The confirmed-scope plan was executed on 2026-07-10. Document storage and dispatch management were intentionally excluded because the client only requires tracking information and an external GPS link.

Implemented outcomes:

- Milestones now control completion status, shipment type is locked after tracking starts, and cancelled BLs cannot advance accidentally.
- Delivery activation now requires the primary import/export workflow to be complete.
- Staff account management supports active state and workflow responsibilities; full BL editing remains supervisor-only.
- OTP values are hashed, attempts and routes are rate-limited, inactive users are blocked, and demo display remains configurable for Phase 1.
- Retention uses an immutable deadline, retained BLs are protected at model level, expired deletion is soft, and sensitive changes are audited.
- OOCL fixtures match the supplied PDF and synthetic statuses no longer contradict milestones.
- Customer and admin historical queries have targeted indexes; year options are resolved in SQL and admin search includes containers.
- Customer UI now uses one compact search/filter surface, clear status summaries, responsive shipment rows, and separate process/delivery tracking.

Deployment items that remain external to the codebase:

- Configure the production mail provider and set `CUSTOMER_OTP_DISPLAY=false` and `CUSTOMER_OTP_SEND_EMAIL=true` after Phase 1.
- Establish production backup/restore monitoring and operational ownership.
- Confirm whether the client's export wording should say draft PIB or draft PEB; the app currently uses PEB.

Current fit:

| Target | Assessment |
|---|---|
| Phase 1 mockup/client walkthrough | Ready |
| Pilot with real operational users | Ready after mail-provider configuration if displayed OTP is disabled |
| Production system of record | Application work complete for confirmed scope; deployment controls remain |

## Sources Reviewed

### Client requirements

- `BRIEFS.md`
- `contexts/Proses Dokumen Ekspor dan Impor.pdf`
- `contexts/BL KMTCSIN3242091 - PT DOLPIN PUTRA SEJATI419.pdf`
- `contexts/BL MEDUYF895047 PT DOLPIN PUTRA SEJATI.pdf`
- `contexts/BL SSLSGJKTCAE9741  - PT DOLPIN PUTRA SEJATI.pdf`
- `contexts/COSU6394859890.pdf`
- `contexts/OOLU2327606650 DPS.pdf`

The five BL files are scanned/image PDFs. They were visually inspected rather than treated as extractable text. The process PDF was checked both as text and as rendered pages so its timeline sketches and document icons were included in this assessment.

### Application areas checked

- Laravel routes, authentication controllers, and customer authorization
- Filament customer and BL resources
- Models, migrations, policies, workflow configuration, and workflow service
- Customer dashboard and BL detail views
- Demo seed data and all automated tests

Verification on 2026-07-10:

```text
php artisan test
57 tests passed, 183 assertions
```

The sections below retain the original findings for traceability. The execution result above is the authoritative current status.

## Pre-Implementation Requirement Coverage

| Client need | Current state | Fit |
|---|---|---|
| Admin and customer dashboards | Filament admin plus custom Blade customer portal | Meets functionally |
| One account per email | Database unique constraint and form validation | Meets |
| Admin-created customer accounts | Customer Filament resource creates customer role accounts | Meets |
| Customer passwordless login | Six-digit OTP stored in session and displayed in the form | Meets Phase 1 only |
| Customer data isolation | Queries are customer-scoped and cross-customer detail returns 404 | Meets |
| Unique BL number | Database and form uniqueness | Meets |
| Manage BL header/detail data | Broad BL header, party, route, cargo, and date fields exist | Mostly meets |
| Multi-container BL | Child table, form repeater, admin/customer display | Meets core need |
| Import process and lane branching | Pre-lane flow plus SPJH/SPJK/SPJM tails | Mostly meets |
| Export process | Configured PEB/NPE/export-card workflow | Mostly meets; wording needs confirmation |
| Delivery process | Milestone track exists | Partial operational fit |
| Role by phase | Role map and service authorization exist | Partial end-to-end fit |
| Update history | Milestone and free-form updates are recorded | Partial audit coverage |
| External GPS URL | Admin field and customer external link | Meets stated need |
| Document upload/download | Client confirmed this is not required | Meets confirmed scope |
| Minimum three-year retention | UI/model checks exist, but no durable guarantee | Partial |
| 30-50 records/day for 3+ years | No load test or production index review | Unproven |

## Pre-Implementation P0 Findings

### 1. Workflow state is not a single source of truth

Current behavior:

- `shipment_type` remains editable after creation (`BillOfLadingForm.php:47`), but milestone definitions are seeded only on model creation (`BillOfLading.php:179`). Changing import to export does not rebuild or reject the existing workflow.
- The BL `status` remains directly editable (`BillOfLadingForm.php:259`).
- Advance Milestone and Post Progress Update allow any value in `BillOfLading::STATUSES`, including `Completed`, while milestones can still be pending (`AdvanceMilestoneAction.php:31`, `PostProgressUpdateAction.php:25`).
- `postProgressUpdate()` changes the top-level status without advancing a milestone (`BillOfLading.php:285`).
- Synthetic volume records are seeded with arbitrary statuses while every new record starts at its first milestone (`DemoDataSeeder.php:477`, `BillOfLadingWorkflowService.php:15`). A record can therefore display `Completed` or `Cancelled` while `receive_docs` is in progress.

Impact:

- Admin lists, customer badges, filters, and timelines can disagree.
- Changing shipment type can leave an import BL running export metadata or vice versa.
- Demo records can visibly undermine the client walkthrough.

Acceptance needed:

- Define top-level status semantics and derive `Completed` from terminal milestone completion.
- Treat `On Hold` and `Cancelled` as explicit workflow transitions with resume/cancel rules.
- Lock `shipment_type` after milestones/history exist, or implement a guarded reset/migration operation.
- Remove arbitrary phase/status generation from demo factories and seeders.
- Add invariant tests covering every combination of status, current milestone, lane, and shipment type.

### 2. Delivery activation can produce an invalid import sequence

Client need:

- The source document presents import/export processing before the delivery process.

Current behavior:

- The Activate Delivery Track action is visible whenever delivery milestones do not yet exist and the user has the delivery role (`ActivateDeliveryTrackAction.php:19`). It does not require the import/export process to reach an approved handoff.
- The service appends delivery after the current maximum sequence (`BillOfLadingWorkflowService.php:185`).
- For an import BL, delivery can be appended before `pib_response` is completed and before a customs lane is assigned.
- If that happens, the later lane branch is appended after delivery because lane milestones also start after the current maximum sequence (`BillOfLadingWorkflowService.php:62`).

Impact:

- A valid-looking user action can create `import pre-lane -> delivery -> customs lane tail`.
- The next active milestone can become delivery while the BL is still awaiting SPJH/SPJK/SPJM processing.

Acceptance needed:

- Confirm the exact handoff milestone with the client.
- Guard delivery activation on that completed handoff.
- Add service-level validation, not only action visibility.
- Add regression tests for early activation on pre-lane, lane, export, completed, and cancelled BLs.

## Pre-Implementation P1 Findings

### 3. Role-by-phase is only partially operable

What already works:

- Milestones map to workflow roles in `config/bl_workflows.php:29`.
- `completeCurrentMilestone()`, lane assignment, and delivery activation enforce roles in the service.
- Tests cover direct service calls with and without a matching role.

Remaining gaps:

- The custom User resource is customer-only (`UserResource.php:54`). There is no staff-user resource for creating operational admins, setting passwords, disabling accounts, or assigning workflow roles.
- Filament Shield can edit roles, but it does not supply the missing staff account lifecycle.
- The positive workflow-role test assigns only `workflow_documents`. Such a user cannot access the panel because `User::canAccessPanel()` separately requires `super_admin` or `panel_user` (`User.php:65`). The test proves service authorization, not the real UI workflow.
- `BillOfLadingPolicy::update()` checks a generic `Update:BillOfLading` permission, not the current milestone role. A staff user with generic update access can edit customer assignment, shipment type, status, GPS URL, containers, and all BL fields outside the phase action rules.
- `postProgressUpdate()` has no service-level role authorization; its role check is action visibility only.

Impact:

- The client cannot administer the phase-responsibility model through the app.
- Generic edit permission can bypass the intended division of responsibility.

Acceptance needed:

- Staff account management with active/disabled state and role assignment.
- A documented permission bundle: panel access, resource view, and one or more workflow roles.
- Server-side authorization for every mutation, including metadata edits and free-form updates.
- Decide which fields phase admins may edit versus which require a supervisor.
- End-to-end Filament tests for a real panel user, not only service tests.

### 4. Customer timelines do not match the supplied sketches

Current behavior:

- The app exposes nearly all operational milestones in one vertical list.
- It merges only adjacent milestones with the same customer label (`CustomerDashboardController.php:133`).
- Import produces `Custom PIB`, `Pick up DO`, then another `Custom PIB` because `transfer_pib` is separated from the first two PIB steps by DO milestones.
- `Respon PIB` appears even though it is not a node in the client's short customer sketches.
- The yellow sketch omits billing while the app includes it. This may be a source-document inconsistency that needs a client decision.
- Import/export and appended delivery are rendered in one progress list; the client document presents the delivery process as a separate track.

Impact:

- The implementation is structurally correct but does not reproduce the client-approved visual story.
- Internal process detail may be exposed when the intended customer view is a summarized timeline.

Acceptance needed:

- Confirm whether the sketches are exact customer-facing stages or illustrative only.
- Create an explicit customer presentation map rather than deduplicating labels opportunistically.
- Render process and delivery as separate named tracks if the client confirms the source layout.
- Define timestamps and state wording for each customer node.

### 5. OTP is Phase 1 demo behavior, not production passwordless login

What meets Phase 1:

- The code is displayed on the verification form, exactly as requested while SMTP is not configured.
- OTP expires after ten minutes and is removed after successful login.

Production gaps:

- No email is sent; mail is configured to `log`.
- The raw OTP is stored in session.
- There is no request throttling, verification-attempt limit, lockout, resend cooldown, or security event log.
- Different error messages reveal whether an email exists and whether it is an admin/customer account (`CustomerAuthController.php:28`).
- No test covers replay, rate limiting, brute force, session fixation across OTP requests, or mail delivery.

Acceptance needed:

- Mail provider and queue delivery.
- Hashed one-time challenge with expiry, attempt count, resend cooldown, and route throttles.
- Non-enumerating login responses.
- Audit logging and operational handling for delayed/failed email.

### 6. Three-year retention is protected only in normal UI paths

What already works:

- The model calculates a three-year window from `input_date` (`BillOfLading.php:261`).
- BL and customer delete actions hide deletion while related BLs are in that window.
- Policies include retention checks and bulk deletion is disabled.

Remaining gaps:

- Filament Shield defines `super_admin` through a `Gate::before` grant (`config/filament-shield.php:71`). That grant can bypass policy checks before `BillOfLadingPolicy::delete()` runs; current UI visibility is therefore carrying part of the retention guarantee.
- There are no soft deletes, archive state, immutable deletion log, legal hold, backup/restore policy, or scheduled retention verification.
- `input_date` is editable. The seed data also uses BL issue dates as input dates, so the business meaning of the retention start is unclear.
- Database cascades can permanently remove milestones, updates, containers, and document metadata when a BL is deleted.
- Tests check date helper methods, not policy behavior for normal staff and super admin.

Impact:

- The system does not yet demonstrate a durable minimum-retention guarantee.
- Accidental or privileged deletion may be unrecoverable.

Acceptance needed:

- Confirm whether retention starts at system intake, BL issue, shipment completion, or last update.
- Use an immutable retention timestamp and archive/soft-delete behavior.
- Ensure super-admin authorization cannot bypass the business retention rule.
- Add deletion audit, backup/restore requirements, and policy-level tests.

### 7. Audit history does not cover important BL changes

Current behavior:

- Milestone completions and free-form progress updates write history.
- The edit page adds history only when legacy `status`, `phase`, or `note` changes (`EditBillOfLading.php:42`).
- Changes to customer assignment, BL number, shipment type, customs-related fields, GPS URL, `customer_note`, `internal_note`, containers, weights, and parties are not audited.

Impact:

- A user can move a BL to another customer or alter operational/legal data without a durable before/after record.
- This weakens confidentiality investigation and operational accountability.

Acceptance needed:

- Define audited fields with the client.
- Record actor, timestamp, before/after values, and reason for sensitive changes.
- Treat customer reassignment and shipment-type changes as privileged operations.

### 8. Delivery is a milestone list, not a complete delivery operation

The client document names driver/depot/container handoffs. The current app stores only milestone state plus one BL-level GPS URL.

Missing if the client expects operational dispatch:

- driver identity and contact
- truck/plate number
- depot reference and gate data
- planned/actual pickup and delivery timestamps
- proof of delivery
- per-container delivery state for a multi-container BL
- GPS URL per vehicle/container or tracking-link history

Decision needed:

- If the app only links to an external GPS platform, the current BL-level URL meets the brief.
- If the app is expected to manage trucking work, this requires a delivery/dispatch domain rather than more fields on the BL record.

### 9. Seed data is not fully faithful to the supplied BLs

Examples:

- The OOCL source lists six containers: `CCLU7687950`, `FFAU3320525`, `FFAU3136821`, `CSNU7931556`, `FFAU5965864`, and `OOLU6751921`.
- The seeder preserves only `CCLU7687950`; the other five container numbers and several seals are invented (`DemoDataSeeder.php:373`).
- The volume demo assigns arbitrary top-level statuses that can conflict with first-milestone progress.
- `EXPORT-DPS-2026-001` is synthetic and should remain clearly labeled as such because no export BL sample was supplied.

Impact:

- Searching by a real OOCL container from the supplied PDF will fail for five of six containers.
- Inconsistent volume data can make a correct screen appear logically broken during the demo.

Acceptance needed:

- Reconcile every seeded real sample against its PDF.
- Add fixture assertions for all BL numbers, container/seal numbers, totals, routes, and dates.
- Generate synthetic records through the workflow service so status and milestone state agree.

## Pre-Implementation P2 Findings

### 10. Per-container cargo detail exists in the schema but not the form

- `BillOfLadingContainer` supports `goods_description`.
- The Filament container repeater does not expose it (`BillOfLadingForm.php:211`).
- The MSC rider contains per-container cargo, brand/grade, quantity, HS code, tare, weight, and measurement.

Confirm whether per-container cargo must be captured or whether the BL-level cargo summary is sufficient. If required, add the input and decide whether HS code/marks also belong at container level.

### 11. Three-year volume and historical search are not performance-validated

At 30-50 BLs/day, three years is approximately 32,850-54,750 BL records before child rows and history.

Current concerns:

- The demo load test seeds 300 volume records, not a three-year dataset.
- Common filter columns such as `status`, `current_milestone_key`, `shipment_type`, `customs_lane`, `input_date`, and `updated_at` have no explicit composite/index strategy in the client-workflow migration.
- Available-year filters load all matching `input_date` values into PHP on each request (`CustomerDashboardController.php:75`, `BillOfLadingsTable.php:120`).
- Contains searches (`%term%`) will not benefit from a normal prefix index.
- Admin search does not include container number even though customer search does.

This volume is reasonable for Laravel and a relational database, but readiness should be proven with production-like data, query plans, indexes, pagination, backups, and retention/archival tests.

### 12. Customer field visibility needs explicit client approval

The app correctly hides internal notes and admin-only update history by implementation, but there is no central visibility policy for BL fields. Confirm whether customers may see:

- shipper/consignee/notify-party details
- NPWP and agent contacts
- freight terms and references
- container weights/tare
- staff names in update history

Visibility should be explicit and tested rather than determined only by whether a Blade template happens to render a field.

### 13. Export terminology conflicts in the source

The client process PDF says `Pembuatan draft PIB` under export, while the later step correctly says PEB and the app uses `draft_peb`. PEB is operationally plausible for export, but the wording must be confirmed rather than silently corrected.

### 14. Customer portal does not use Laravel Breeze

`BRIEFS.md` names Breeze for the customer dashboard. The current portal uses custom controllers and Blade views and does not install Breeze.

There is no functional gap in Phase 1, and replacing working auth only to match a scaffold name would add risk. Treat this as a contractual/architecture clarification, not a rebuild requirement, unless the client explicitly requires Breeze-generated code.

## Confirmed Non-Gaps

The following older findings are no longer accurate for the current code:

- Customs lane assignment is guarded until `pib_response` is completed.
- Import, export, and delivery milestone definitions exist.
- Workflow role mapping and service-level checks exist for milestone advance, lane assignment, and delivery activation.
- Customer timeline labels collapse adjacent duplicates, although the exact client presentation still needs work.
- Three-year retention helper and UI deletion checks exist, although they are not yet a durable production guarantee.
- Full test execution is not blocked: all 48 current tests pass.

Also not a current requirement gap:

- Document upload/download is intentionally absent. The client confirmed that the tracking app only needs shipment information and the external GPS tracking link. Document icons in the process PDF do not imply an in-app document repository.
- Automatic OCR/PDF extraction is absent, but the brief explicitly expects manual admin entry.
- A GPS provider API is absent, but the brief asks for an external tracking URL.

## Executed Closure Order

1. Completed: enforce workflow invariants for shipment type, status, lane, and delivery sequencing.
2. Completed: correct the real PDF fixtures and inconsistent synthetic demo statuses.
3. Completed: add staff account lifecycle and close generic-edit authorization bypasses.
4. Completed: simplify customer process/delivery timelines and separate their presentation.
5. Completed in application: harden OTP and retention; production mail remains environment configuration.
6. Completed for confirmed scope: audit sensitive changes and keep delivery at information plus external GPS URL.
7. Completed in application: add indexes and SQL-based historical filters; backup/restore remains deployment operations.

## Client Decisions Required

1. Is the customer timeline in the PDF exact, or may it expose every internal milestone?
2. Should import/export processing and delivery appear as separate tracks?
3. Is export draft preparation called PIB or PEB in the client's actual process?
4. Which BL fields and staff identities may a customer see?
5. After Phase 1, should OTP be email-only, or is another delivery channel required?
