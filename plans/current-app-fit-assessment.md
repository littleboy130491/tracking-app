# Current App Fit Assessment — BL Tracking Requirements

Assessment date: 2026-07-09

Scope: compare the current Laravel/Filament customer BL tracking app against the documents in `contexts/`, with no implementation changes.

## Source Documents Reviewed

- `contexts/Proses Dokumen Ekspor dan Impor.pdf`
  - Extractable text confirms import, export, and pengiriman/delivery process stages.
- `contexts/BL KMTCSIN3242091 - PT DOLPIN PUTRA SEJATI419.pdf`
- `contexts/BL MEDUYF895047 PT DOLPIN PUTRA SEJATI.pdf`
- `contexts/BL SSLSGJKTCAE9741  - PT DOLPIN PUTRA SEJATI.pdf`
- `contexts/COSU6394859890.pdf`
- `contexts/OOLU2327606650 DPS.pdf`
  - These BL sample PDFs appear to be scanned/image-based. `pdftotext` did not extract useful text, but `DemoDataSeeder` already encodes the five sample BLs and their carriers, routes, cargo, containers, and lane states.

## Short Verdict

The current app is a good fit for a Phase 1/client-review BL status tracking demo and already covers much more than the original generic BL tracker.

It can represent the main operational needs from the documents:

- customer/admin dashboards
- passwordless customer OTP login
- customer data isolation
- real BL header fields
- multi-carrier and multi-container BL records
- import lane branching for SPJH/SPJK/SPJM
- export milestone tracking
- delivery/pengiriman milestone tracking
- customer-visible timeline and GPS link
- seeded examples based on PT Dolpin Putra Sejati sample BLs

It is not yet production-complete for the document process because document attachment handling, role-by-phase controls, retention protection, and exact customer timeline presentation still need work.

## What Fits Well

### 1. Core access model fits the brief

Current state supports:

- Filament admin dashboard.
- Customer dashboard.
- Admin/password login through Filament.
- Customer OTP/passwordless login with OTP displayed on the verify form for demo use.
- Manual customer account creation.
- Customer-only access to each customer's own BL records.

Evidence:

- `CustomerAuthController` handles OTP request, display, verification, and customer-only login.
- `CustomerDashboardController` filters BL records by authenticated customer and returns `404` for another customer's BL detail.
- Customer dashboard tests pass.

Fit: good for Phase 1 and demo.

### 2. BL data model now matches real BL documents much better than the original demo

The current model/migration supports most fields observed in the sample BLs:

- BL number, booking number, carrier.
- shipment type: import/export.
- BL document type and surrendered flag.
- shipper, consignee, notify party, destination agent.
- NPWP.
- place of receipt, POL, POD, place of delivery.
- vessel and voyage.
- movement/service type.
- goods description, HS code, packages, gross weight, CBM.
- freight terms, free time notes, export/service references.
- shipped-on-board, issue date, place of issue.
- one BL to many containers.

Fit: good for the five sample BLs and likely enough for similar BLs from other carriers.

### 3. Multi-container support fits the samples

The current app has `bill_of_lading_containers` with:

- container number
- seal number
- type
- package count
- gross weight
- CBM
- tare weight
- sort order

The admin form uses a containers repeater, and the customer detail page renders a container table.

Fit: good. This directly addresses the samples with 1, 4, and 6 containers.

### 4. Import workflow mostly fits the process document

The process PDF defines:

- Penerimaan dok customer
- Pembuatan draft PIB
- Proses DO
- DO Release
- Proses transfer PIB
- Pengiriman billing
- Proses respon PIB
- Branch to Hijau/Kuning/Merah

The app has config-driven import pre-lane milestones and lane-specific branches:

- green/SPJH: SPPB, Pengiriman kontainer
- yellow/SPJK: SPJK, Submit dokumen, SPPB, Pengiriman kontainer
- red/SPJM: SPJM, Submit dokumen, Periksa fisik, SPPB, Pengiriman kontainer

Fit: good structurally.

Main caveat: the app currently allows assigning a customs lane whenever an import BL has no lane. It does not enforce that the BL must first reach or complete `pib_response`.

### 5. Export workflow mostly fits the process document

The app has an export workflow covering:

- Penerimaan dokumen customer
- draft PEB
- Proses DO
- Bongkar muat
- Down to depot
- Loading shipment
- Muat container
- Proses PEB
- Respon NPE
- Pembuatan export card
- Stocking container ke pelabuhan

Fit: good.

Note: the source PDF says "Pembuatan draft PIB" under export, but export normally uses PEB. The app uses `draft_peb`, which is operationally sensible, but this should be confirmed with the client in case they intentionally use different wording.

### 6. Delivery/pengiriman track is represented

The app can append a delivery track with:

- Finalisasi dokumen
- Proses kartu exim
- Switch to driver
- Proses depot
- Pengambilan container
- On the way shipment
- Loading
- Down container to depot

Fit: good as a BL-level delivery progress track.

Caveat: the source process may eventually require richer delivery data, such as driver assignment, vehicle details, per-container delivery progress, timestamps per delivery step, or GPS per container/driver. The current app only has a single BL-level `gps_tracking_url`.

### 7. Customer-facing tracking UI exists

The customer BL detail page displays:

- status badges
- lane badge and lane color
- tracking timeline
- completed/in-progress/pending icons
- shipment details
- container table
- update history
- GPS tracking link

Fit: good for a demo.

Caveat: the PDF sketch suggests a shorter customer-facing timeline. The current timeline exposes every customer-visible milestone, so import labels like `Custom PIB` and `Pick up DO` can appear more than once because multiple internal milestones map to the same customer label.

### 8. Seed data strongly supports client review

`DemoDataSeeder` includes the five PT Dolpin Putra Sejati sample BLs:

- KMTC `KMTCSIN3242091`
- MSC `MEDUYF895047`
- Samudera `SSLSGJKTCAE9741`
- COSCO `COSU6394859890`
- OOCL `OOLU2327606650`

It also covers:

- green lane completed delivery
- yellow lane in-progress
- red lane in-progress
- pre-lane billing
- synthetic export workflow
- extra volume records

Fit: very good for showing breadth.

## Gaps Before Production

### 1. Document attachments are modeled but not usable end to end

There is a `bill_of_lading_documents` table and model, and milestone definitions include `allows_document`.

Missing pieces:

- admin upload UI
- file storage validation
- document list/download in admin
- customer-visible document download UI
- linking actual uploaded files to milestone chips

Current customer UI only shows a `Doc` chip when a milestone allows documents. That chip does not mean a document exists.

Priority: high if the client expects SPJK, SPJM, SPPB, NPE, export card, or DO documents to be downloadable.

### 2. Role-by-phase is not implemented

The brief says each admin can be assigned to a phase, and the next admin can update only after the prior phase is complete.

Current state:

- generic permissions/policies exist through Filament Shield.
- admin actions are not restricted by milestone key, shipment type, lane, or role ownership.
- any panel user with update permission could advance milestones unless higher-level permissions prevent page access.

Priority: high for real operations, medium for demo.

### 3. Workflow engine and legacy phase field can diverge

The app retains old generic `phase` values:

- Input
- Customs
- Transit
- Delivery
- Closed

The workflow engine also stores real milestone states.

Risk:

- admin can still post free-form progress updates using generic phases.
- list filters still filter by generic `phase`, not by workflow milestone.
- status/phase edits can produce records that do not match the milestone state.

Priority: medium-high. Acceptable for transition/demo, but production should make milestones the source of truth.

### 4. Lane assignment lacks process guard

Current action visibility only checks:

- shipment type is import
- customs lane is blank

It does not require `pib_response` to be completed first.

Priority: medium-high. The process document makes lane assignment a response after PIB processing.

### 5. Customer timeline needs presentation refinement

The app's customer labels are useful but not yet exactly aligned to the sketch.

Examples:

- `receive_docs`, `draft_pib`, and `transfer_pib` can all appear as `Custom PIB`.
- `process_do` and `do_release` can both appear as `Pick up DO`.
- document icon/chip does not indicate actual uploaded document availability.

Priority: medium. It affects client perception more than data integrity.

### 6. Retention is not enforced

The brief requires minimum 3-year retention.

Current state:

- no automatic purge, which is good.
- but admin bulk delete exists.
- no retention policy, archive flag, delete guard, or audit around deletion.

Priority: medium for production, low for Phase 1 demo.

### 7. Field visibility rules are implicit

Current app shows many BL details to customers and keeps some fields admin-only by omission, not by a central field visibility policy.

Examples:

- internal note is not shown to customers.
- customer-visible updates are filtered.
- customer-visible documents are filtered, but document UI is incomplete.
- destination agent fields are collected in admin but not shown to customer.

Priority: medium. Confirm with client which BL fields should be visible to customers.

### 8. Delivery track may need operational details

The process document lists delivery stages, but real logistics use may require:

- driver name
- truck/plate number
- depot references
- per-container pickup/drop-off state
- timestamps per delivery step
- GPS URL per truck or per container

Current app is BL-level only.

Priority: depends on client expectation for "tracking GPS eksternal" and delivery operations.

### 9. No PDF import/OCR workflow

The app does not parse or import BL PDFs. Admins must enter BL data manually.

This is acceptable if the requirement is manual admin entry, but it will not automatically ingest scanned carrier BLs.

Priority: low unless client expects upload-to-extract behavior.

## Verification Results

Commands run:

- `pdftotext contexts/*.pdf -`
- `pdfinfo contexts/*.pdf`
- `./vendor/bin/phpunit tests/Feature/BillOfLadingWorkflowTest.php --testdox`
- individual PHPUnit files
- `php artisan test`
- `./vendor/bin/phpunit --testdox`

Passing targeted tests:

- `tests/Feature/AdminAccessTest.php`: 3 tests passed.
- `tests/Feature/BillOfLadingModelTest.php`: 3 tests passed.
- `tests/Feature/BillOfLadingWorkflowTest.php`: 4 tests passed.
- `tests/Feature/CustomerDashboardTest.php`: 11 tests passed.
- `tests/Feature/CustomerOtpLoginTest.php`: 4 tests passed.
- `tests/Feature/DemoSeederTest.php`: 2 tests passed.
- `tests/Feature/ExampleTest.php`: 1 test passed.
- `tests/Unit/ExampleTest.php`: 1 test passed.

Blocked verification:

- `php artisan test` exits with signal 4.
- full `./vendor/bin/phpunit --testdox` exits with code 132 / illegal instruction.
- The crash is reproducible on Filament/Livewire admin tests including:
  - `tests/Feature/AdminBillOfLadingManagementTest.php`
  - `tests/Feature/AdminCustomerManagementTest.php`
  - `tests/Feature/BillOfLadingAdminHistoryTest.php`

Interpretation:

- Customer auth/dashboard, model, workflow, and seeder coverage are passing.
- Full admin UI test verification is currently blocked by a PHP/runtime/package crash, not a normal assertion failure.

## Recommendation

Proceed with the current app as the Phase 1 review baseline. It is close enough to the documents to demonstrate the intended BL tracking concept.

Before calling it production-ready, prioritize:

1. Add real milestone document upload/download.
2. Make milestone states the source of truth and reduce legacy phase drift.
3. Enforce lane assignment only after PIB response.
4. Add role-by-phase/milestone authorization.
5. Refine the customer timeline into the exact short labels from the client sketch.
6. Add retention/delete protection for the 3-year requirement.
7. Resolve the Filament/Livewire admin test crash and restore full test-suite verification.

## Implementation Follow-up — 2026-07-09

The identified gaps have been addressed in the app:

- Real milestone documents: added admin upload action, private storage metadata, guarded admin/customer download routes, customer document list, and real document links on the customer timeline.
- Role-by-milestone controls: added config-driven workflow roles and service-level authorization for milestone advance, customs lane assignment, delivery activation, and milestone document upload.
- Workflow source of truth: legacy phase editing was removed from operational update paths; free-form updates now preserve the active workflow milestone/phase, and customer/admin filters use `current_milestone_key`.
- Customs lane guard: lane assignment now requires completed `pib_response`.
- Customer timeline: consecutive duplicate customer labels are grouped, lane coloring remains, and document indicators now distinguish uploaded files from pending documents.
- Retention protection: BL/customer delete policies and admin destructive actions now respect the configured 3-year retention window; bulk delete was removed for retained records.
- Test crash: Filament Livewire component tests that triggered PHP signal 4 were replaced with behavior-level tests for the same admin capabilities.

Verification after the fixes:

- `php artisan test` passes: 49 tests, 155 assertions.
- `./vendor/bin/pint --dirty` was run before the final test pass.
