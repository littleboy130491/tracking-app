# Client Adjustment Plan — BL Tracking App

Goal: adjust the Phase 1 demo so the data model, admin forms, status workflow, and customer tracking UI match PT Dolpin Putra Sejati’s operational documents and process flows.

Source materials in `contexts/`:

| File | What it provides |
|------|------------------|
| `BL KMTCSIN3242091 - PT DOLPIN PUTRA SEJATI419.pdf` | KMTC BL — Singapore → Tanjung Priok, 4×20'GP |
| `BL MEDUYF895047 PT DOLPIN PUTRA SEJATI.pdf` | MSC BL + rider — Tianjin → Jakarta, 4×40'HC |
| `BL SSLSGJKTCAE9741  - PT DOLPIN PUTRA SEJATI.pdf` | Samudera BL + attached sheet — Singapore → Tanjung Priok, 1×20'GP |
| `COSU6394859890.pdf` | COSCO BL — Shuaiba → Jakarta, 4×40HQ |
| `OOLU2327606650 DPS.pdf` | OOCL BL — Yangpu → Jakarta, 6 containers |
| `Proses Dokumen Ekspor dan Impor.pdf` | Import lanes (Hijau/Kuning/Merah), export stages, delivery stages, and customer timeline UI sketches |

---

## 1. What the Client Documents Tell Us

### 1.1 BL documents are multi-carrier and multi-container

All five samples share the same consignee (`PT DOLPIN PUTRA SEJATI`) but differ by carrier, route, container count, and cargo. The app must support:

- Multiple carriers (KMTC, MSC, Samudera, COSCO, OOCL, and future ones)
- One BL → many containers (1 to 6+ in the samples)
- Import-oriented ocean freight into Indonesia (Jakarta / Tanjung Priok)
- Both Original and Non-Negotiable / Surrendered BL copies

### 1.2 Common BL field set (present across samples)

| Group | Fields observed |
|-------|-----------------|
| Identity | BL number, booking number, carrier name, export/service contract refs |
| Parties | Shipper, consignee, notify party, destination agent |
| Routing | Place of receipt, vessel, voyage, POL, POD, place of delivery, movement type (CY-CY / FCL) |
| Cargo | Description of goods, HS code, packages/bags, gross weight (kg), measurement (CBM) |
| Containers | Container no, seal no, size/type (20'GP, 40'HC/HQ), packages per container, weight/CBM per container, tare (sometimes) |
| Commercial | Freight terms (prepaid/collect), free time / demurrage notes, place & date of issue, shipped-on-board date |
| Document state | Original / copy / non-negotiable / surrendered |

### 1.3 Process flow is not a flat 5-phase list

The process PDF replaces the demo’s generic phases (`Input → Customs → Transit → Delivery → Closed`) with **typed workflows**:

**A. Import (with customs lane branching)**

Shared early steps:

1. Penerimaan dokumen customer
2. Pembuatan draft PIB
3. Proses DO
4. DO Release
5. Proses transfer PIB
6. Pengiriman billing
7. Proses respon PIB → assigns lane

Then branch by customs lane:

| Lane | Code | Steps after respon PIB |
|------|------|------------------------|
| Hijau | SPJH | SPPB → Pengiriman kontainer |
| Kuning | SPJK | Submit dokumen → SPPB → Pengiriman kontainer |
| Merah | SPJM | Submit dokumen → Periksa fisik → SPPB → Pengiriman kontainer |

Customer-facing import timeline sketches (from the PDF):

- **Hijau:** Custom PIB → Pick up DO → Bayar billing → SPPB → Pengiriman container ke pabrik
- **Kuning:** Custom PIB → Pick up DO → SPJK → Submit dokumen → SPPB → Pengiriman container ke pabrik
- **Merah:** Custom PIB → Pick up DO → Bayar billing → SPJM → Submit dokumen → Periksa fisik → SPPB → Pengiriman container ke pabrik

**B. Export (PEB process)**

1. Penerimaan dokumen customer
2. Draft dokumen / PEB prep
3. Proses DO
4. Bongkar muat
5. Down to depot
6. Loading shipment
7. Muat container
8. Proses PEB
9. Respon NPE
10. Pembuatan export card
11. Stocking container ke pelabuhan

**C. Delivery / pengiriman (shared operational track)**

1. Finalisasi dokumen
2. Proses kartu exim
3. Switch to driver
4. Proses depot
5. Pengambilan container
6. Shipment / on the way
7. Loading
8. Down container to depot

### 1.4 Tracking UI language from the client sketch

Each milestone node has one of:

- Completed (colored check)
- In progress (sync/refresh icon)
- Pending (grey X)
- Optional document attachment icon on selected nodes (e.g. SPJK, SPPB, export card)

Lane color should drive the progress bar color (green / yellow / red for import).

### 1.5 Gaps vs current demo

| Area | Current demo | Client need |
|------|--------------|-------------|
| Shipment type | None | Import / Export (and possibly delivery track) |
| Phases | Fixed 5 generic phases | Dynamic milestone lists by type + customs lane |
| Customs lane | None | SPJH / SPJK / SPJM after PIB response |
| Containers | Single quantity string | One-to-many container records |
| BL parties / voyage | Origin/destination only | Full shipper, vessel, voyage, POL/POD, carrier, booking, HS code, etc. |
| Documents | None | Attachments on selected milestones |
| Field visibility | Everything shown to customer | Explicit customer vs admin-only fields |
| Role-by-phase | Deferred | Still needed later; design milestones so roles can lock later |
| GPS URL | Present | Keep; useful for delivery/pengiriman track |

---

## 2. Product Decisions for This Adjustment

1. Keep the existing dual-dashboard architecture (Filament admin + customer OTP dashboard).
2. Expand the BL data model to match real BL documents, without trying to store every legal clause from the PDF.
3. Replace flat `phase` with a **milestone workflow engine** driven by shipment type + customs lane.
4. Treat containers as a child table of BL.
5. Define a default customer/admin field visibility matrix now; confirm with client before locking production rules.
6. Keep GPS external URL for the delivery track.
7. Defer SMTP, archival automation, and hard role-phase locking until after this adjustment is reviewed — but design the schema so they fit cleanly.

---

## 3. Proposed Domain Model

### 3.1 `bill_of_ladings` (expanded header)

Keep existing core fields and add operational BL fields.

**Identity & assignment**

| Field | Type | Notes |
|-------|------|-------|
| `bl_number` | string, unique | Primary customer search key |
| `booking_number` | string, nullable | Present on most samples |
| `customer_id` | FK users | Consignee company account |
| `shipment_type` | enum | `import`, `export` |
| `carrier_name` | string, nullable | KMTC / MSC / COSCO / OOCL / Samudera… |
| `bl_document_type` | string, nullable | original / non_negotiable / copy |
| `bl_surrendered` | boolean | From SURRENDERED stamps |
| `input_date` | date | Admin intake date |
| `issue_date` | date, nullable | Place/date of issue |
| `shipped_on_board_date` | date, nullable | Critical milestone date from BL |
| `place_of_issue` | string, nullable | |

**Parties**

| Field | Type | Notes |
|-------|------|-------|
| `shipper_name` | string | |
| `shipper_address` | text, nullable | |
| `consignee_name` | string | Usually matches customer company |
| `consignee_address` | text, nullable | |
| `consignee_npwp` | string, nullable | Tax ID seen on all samples |
| `notify_party_name` | string, nullable | Often “same as consignee” |
| `notify_party_address` | text, nullable | |
| `destination_agent_name` | string, nullable | |
| `destination_agent_contact` | text, nullable | Address / phone / email free text |

**Routing**

| Field | Type | Notes |
|-------|------|-------|
| `place_of_receipt` | string, nullable | |
| `port_of_loading` | string | Replaces vague `origin` or maps from it |
| `port_of_discharge` | string | Replaces vague `destination` or maps from it |
| `place_of_delivery` | string, nullable | |
| `vessel_name` | string, nullable | |
| `voyage_number` | string, nullable | |
| `movement_type` | string, nullable | CY-CY, FCL/FCL |
| `service_type` | string, nullable | |

**Cargo summary**

| Field | Type | Notes |
|-------|------|-------|
| `goods_description` | text | Replaces / absorbs `items_description` |
| `hs_code` | string, nullable | |
| `package_count` | string, nullable | e.g. `2560 BAGS` — keep flexible |
| `container_count_label` | string, nullable | e.g. `4 x 40' HIGH CUBE` |
| `gross_weight_kg` | decimal, nullable | Total |
| `measurement_cbm` | decimal, nullable | Total |
| `shipment_description` | text | Short list/header summary (keep) |
| `marks_and_numbers` | text, nullable | |
| `free_time_notes` | text, nullable | e.g. 14 days free detention |
| `freight_terms` | string, nullable | prepaid / collect |
| `export_reference` | string, nullable | SC# / LD refs |

**Workflow state**

| Field | Type | Notes |
|-------|------|-------|
| `customs_lane` | enum, nullable | `green`, `yellow`, `red` — import only, set after PIB response |
| `current_status` | enum | `pending`, `in_progress`, `on_hold`, `completed`, `cancelled` |
| `current_milestone_key` | string, nullable | Points to active step |
| `gps_tracking_url` | text, nullable | Keep |
| `internal_note` | text, nullable | Admin-only latest note |
| `customer_note` | text, nullable | Optional note visible to customer |

Migration note: map existing `origin` → `port_of_loading`, `destination` → `port_of_discharge`, `items_description` → `goods_description`, `volume_cbm` → `measurement_cbm`, `phase` → derived from milestones, `note` → split into internal/customer notes.

### 3.2 `bill_of_lading_containers` (new)

One BL has many containers.

| Field | Type |
|-------|------|
| `bill_of_lading_id` | FK |
| `container_number` | string |
| `seal_number` | string, nullable |
| `container_type` | string, nullable | 20'GP, 40'HC, 40HQ |
| `package_count` | string, nullable |
| `gross_weight_kg` | decimal, nullable |
| `measurement_cbm` | decimal, nullable |
| `tare_weight_kg` | decimal, nullable |
| `goods_description` | text, nullable | Override if needed |
| `sort_order` | int |

Unique constraint: (`bill_of_lading_id`, `container_number`).

### 3.3 `workflow_definitions` + `workflow_milestones` (config tables or PHP config)

Prefer **PHP/config enums first** for Phase 2 speed; move to DB tables only if client needs to edit steps without deploy.

Suggested structure (config-driven):

```text
workflows:
  import.pre_lane: [receive_docs, draft_pib, process_do, do_release, transfer_pib, send_billing, pib_response]
  import.green:    [sppb, deliver_container]
  import.yellow:   [submit_docs, sppb, deliver_container]
  import.red:      [submit_docs, physical_inspection, sppb, deliver_container]
  export:          [receive_docs, draft_peb, process_do, loading_unloading, down_to_depot, loading_shipment, load_container, process_peb, npe_response, export_card, stock_to_port]
  delivery:        [finalize_docs, exim_card, switch_to_driver, process_depot, pickup_container, on_the_way, loading, down_container_depot]
```

Customer-facing import timelines can be a **presentation mapping** of the operational milestones (shorter labels as in the PDF sketch), not a second source of truth.

### 3.4 `bill_of_lading_milestone_states` (new)

Per-BL progress against the active workflow.

| Field | Type | Notes |
|-------|------|-------|
| `bill_of_lading_id` | FK | |
| `workflow_key` | string | e.g. `import`, `export`, `delivery` |
| `milestone_key` | string | e.g. `sppb` |
| `sequence` | int | Snapshot of order at creation |
| `label` | string | Display label |
| `state` | enum | `pending`, `in_progress`, `completed`, `skipped` |
| `completed_at` | datetime, nullable | |
| `updated_by` | FK users, nullable | |
| `customer_visible` | boolean | Default true for most steps |
| `allows_document` | boolean | From sketch (SPJK/SPPB/etc.) |
| `note` | text, nullable | |

Rules:

- Creating a BL seeds the milestone list from `shipment_type`.
- When import `customs_lane` is set, append the lane-specific tail and lock earlier pre-lane steps as completed/in progress as appropriate.
- Only one milestone may be `in_progress` at a time (unless client later asks for parallel tracks).
- Completing the last milestone can auto-set BL `current_status` to `completed`.

### 3.5 `bill_of_lading_documents` (new)

| Field | Type |
|-------|------|
| `bill_of_lading_id` | FK |
| `milestone_state_id` | FK, nullable |
| `document_type` | string | e.g. `sppb`, `spjk`, `spjm`, `pib`, `peb`, `npe`, `do`, `other` |
| `title` | string |
| `file_path` | string |
| `visibility` | enum | `customer`, `admin_only` |
| `uploaded_by` | FK users |
| `uploaded_at` | datetime |

### 3.6 `bill_of_lading_updates` (keep, enrich)

Keep history rows. Enrich with:

| Field | Change |
|-------|--------|
| `milestone_key` | optional |
| `customs_lane` | optional snapshot |
| `visibility` | `customer` / `admin_only` |
| `status` / `phase` | keep for backward compatibility during migration, then deprecate `phase` |

### 3.7 Users / customers

Keep current customer profile fields. Ensure company name + NPWP can be stored on the customer account and optionally copied onto new BLs as consignee defaults.

---

## 4. Customer vs Admin Field Visibility

Client did not send an explicit visibility matrix. Use this **proposed default** and confirm in Checkpoint 0.

### 4.1 Visible to customer (default)

- BL number, booking number (optional)
- Carrier, vessel, voyage
- POL / POD / place of delivery
- Shipment type
- Goods description (summary), package count, container count label
- Gross weight / CBM totals
- Container numbers + types (seal numbers: confirm; default **show**)
- Shipped-on-board date, issue date
- Customs lane (after assigned)
- Milestone timeline + current status
- Customer-visible notes and customer-visible documents
- GPS tracking URL
- Free time / demurrage notes (useful for planning pickup)

### 4.2 Admin only (default)

- Internal notes / operational comments
- Freight commercial detail beyond prepaid/collect label (rates, exchange rate, collect instructions)
- Destination agent internal contacts if sensitive
- Admin-only documents
- Who updated each milestone (staff identity)
- Any billing amounts / invoice internals (not present in samples as structured data, but likely needed later)
- System metadata (created_by, retention flags)

### 4.3 Confirm with client before build lock

Ask explicitly:

1. May customers see seal numbers?
2. May customers see destination agent contact details?
3. May customers download SPPB / SPJK / SPJM / PIB / PEB files, or only see that a document exists?
4. Should freight terms (`FREIGHT PREPAID`) be customer-visible?
5. Is NPWP customer-visible on the BL detail page?

---

## 5. Admin UX Adjustments

### 5.1 BL create/edit form sections

1. **Assignment** — customer, BL number, booking number, shipment type, input date
2. **Carrier & document** — carrier, BL document type, surrendered flag, issue date/place, shipped-on-board date
3. **Parties** — shipper, consignee, notify, destination agent, NPWP
4. **Routing** — receipt, POL, vessel/voyage, POD, delivery, movement/service type
5. **Cargo summary** — description, HS, packages, totals, free time, freight terms
6. **Containers** — repeater / relation manager
7. **Workflow** — current status, customs lane (import), milestone board / post progress action
8. **Tracking & notes** — GPS URL, customer note, internal note
9. **Documents** — upload tied to milestone/document type

### 5.2 Progress update action

Replace “set phase dropdown” as the primary update path with:

- Select next milestone action (complete current / move to next / put on hold)
- Optional note + visibility
- Optional document upload
- For import: “Set customs lane” action available only after `pib_response` (or equivalent) is reached

### 5.3 List / filters

Admin filters:

- BL number, container number, customer, carrier
- Shipment type, customs lane, current status, current milestone
- Date range (input date / shipped-on-board)

---

## 6. Customer UX Adjustments

### 6.1 Dashboard list

Show: BL number, shipment type, carrier, POL → POD, current milestone label, status, last update date. Keep search by BL number; add optional container number search.

### 6.2 BL detail

Layout:

1. Header: BL number, status badge, shipment type, customs lane badge (if import)
2. Route summary: vessel/voyage, POL → POD, shipped-on-board date
3. **Timeline component** matching client sketch (check / in-progress / pending + document icon)
4. Cargo & containers table
5. Documents list (customer-visible only)
6. GPS link (if present)
7. Update history (customer-visible entries only)

### 6.3 Timeline behavior

- Color by customs lane for import (green/yellow/red)
- Neutral/brand color for export and delivery tracks
- If both export and delivery tracks exist later, show as separate timeline sections
- For Phase 2 adjustment, start with **one active track per BL** based on `shipment_type`, and treat delivery as either:
  - a continuation section after import/export, or
  - a second workflow attached when delivery starts

**Recommendation:** model delivery as an optional second workflow on the same BL, activated by admin when pengiriman begins. Confirm with client.

---

## 7. Seed / Demo Data Refresh

Rebuild demo seeds from the five real BL samples (anonymize only if client asks; currently all are the same consignee company):

| BL | Carrier | Route | Containers | Suggested demo lane/status |
|----|---------|-------|------------|----------------------------|
| KMTCSIN3242091 | KMTC | Singapore → Tanjung Priok | 4×20'GP | Import / Green / completed delivery |
| MEDUYF895047 | MSC | Tianjin → Jakarta | 4×40'HC | Import / Yellow / in progress at SPJK |
| SSLSGJKTCAE9741 | Samudera | Singapore → Tanjung Priok | 1×20'GP | Import / Green / SPPB done |
| COSU6394859890 | COSCO | Shuaiba → Jakarta | 4×40HQ | Import / Red / physical inspection done, SPPB in progress |
| OOLU2327606650 | OOCL | Yangpu → Jakarta | 6 containers | Import / pre-lane / billing stage |

Also seed at least one **export** example using the export milestone list (can be synthetic if no export BL PDF was provided).

Keep 1 admin + 2 customers. Assign all sample BLs to the primary customer (PT Dolpin), and keep a second customer with 0–1 BLs to prove isolation.

---

## 8. Implementation Checkpoints

### Checkpoint 0: Client Confirmation Workshop

Build:

- Share this plan + field visibility matrix + milestone lists.
- Confirm open questions in §4.3 and §6.3.
- Confirm whether delivery is a second track on the same BL.
- Confirm Indonesian labels vs bilingual UI for customer timeline.

Pass condition:

- Written answers on visibility, lane timing, delivery track, and document download rules.

### Checkpoint 1: Schema Migration Plan

Build:

- Migrations for expanded `bill_of_ladings`
- `bill_of_lading_containers`
- `bill_of_lading_milestone_states`
- `bill_of_lading_documents`
- Update `bill_of_lading_updates`
- Data migration from old `origin` / `destination` / `phase` / `note` fields
- Workflow config (PHP) for import/export/delivery + lane tails

Test:

- `migrate:fresh` succeeds
- Old demo fields map without data loss where possible
- Unique BL number still enforced
- Container uniqueness per BL enforced

Pass condition:

- Schema supports one BL, many containers, dynamic milestones, and documents.

### Checkpoint 2: Domain Services

Build:

- `WorkflowFactory` to seed milestones on BL create
- `AssignCustomsLane` to append green/yellow/red tail
- `AdvanceMilestone` to complete current / start next / write history
- Visibility helpers for customer queries

Test:

- Import BL without lane only has pre-lane milestones
- Assigning yellow appends yellow steps only
- Cannot assign lane twice without admin override
- Advancing milestones writes history and updates `current_milestone_key`
- Completing final milestone marks BL completed

Pass condition:

- Workflow rules match the process PDF.

### Checkpoint 3: Admin BL Form + Containers

Build:

- Rebuild Filament BL form into the sections in §5.1
- Container relation manager / repeater
- Validation for required operational fields

Test:

- Admin can recreate each of the 5 sample BLs with containers
- Duplicate BL rejected
- Duplicate container number on same BL rejected

Pass condition:

- Admin can enter real BL data without stuffing everything into free-text.

### Checkpoint 4: Admin Milestone + Document Updates

Build:

- Replace primary phase editing with milestone actions
- Customs lane assignment action
- Document upload with visibility + document type
- History panel shows staff updates

Test:

- Admin can move a BL through green / yellow / red paths
- Document icon appears on allowed milestones
- Admin-only document is stored but not customer-visible

Pass condition:

- Operational update flow matches client process language.

### Checkpoint 5: Customer Timeline + Detail

Build:

- Customer BL detail with timeline component
- Hide admin-only fields/docs/notes
- Container table
- GPS link retained

Test:

- Customer A cannot see Customer B data
- Yellow-lane BL shows yellow timeline nodes and correct pending/in-progress/completed states
- Admin-only note/document never appears in customer HTML
- Seal/agent/NPWP visibility follows confirmed matrix

Pass condition:

- Customer view matches the process PDF sketches closely enough for client review.

### Checkpoint 6: Demo Seed + Polish

Build:

- Seed 5 import samples + 1 export sample
- Filters/search by BL and container number
- Empty states and clear Indonesian/English labels as confirmed

Test:

- `migrate:fresh --seed`
- Full manual walkthrough for green, yellow, and red
- Customer OTP login still works

Pass condition:

- Client can review the app using their own document shapes and process names.

### Checkpoint 7: Adjustment Verification

Build:

- End-to-end QA only; no new scope

Test:

- Admin creates import BL → advances to PIB response → sets lane → completes to delivery
- Admin creates export BL → advances through PEB/NPE/export card
- Customer sees only allowed fields and timeline
- Regression: unique BL, customer isolation, OTP login, GPS link

Pass condition:

- Adjustment is ready for client UAT.

---

## 9. Mapping: Current Demo → Target

| Current | Target |
|---------|--------|
| `phase` enum (5 values) | `milestone_states` + `current_milestone_key` |
| `status` | Keep, normalize values |
| `origin` / `destination` | `port_of_loading` / `port_of_discharge` (+ richer routing fields) |
| `items_description` / `quantity` | `goods_description` / `package_count` + containers |
| `volume_cbm` | `measurement_cbm` |
| `note` | `internal_note` + `customer_note` + history visibility |
| Single BL form progress section | Milestone actions + lane assignment |
| Customer detail phase badge | Timeline component |
| No containers | `bill_of_lading_containers` |
| No documents | `bill_of_lading_documents` |

---

## 10. Explicitly Out of Scope for This Adjustment

- Real SMTP OTP delivery
- Hard role-based “only Admin Phase X can update”
- Carrier API / AIS GPS integration (keep external URL only)
- Automated customs system integration (CEISA / PIB-PEB APIs)
- Billing/invoice amounts module
- OCR upload-to-create-BL from PDF
- Multi-company consignee hierarchies beyond current customer accounts
- Retention job automation (still store ≥ 3 years; no purge job yet)

These remain valid follow-ups after UAT.

---

## 11. Open Questions for the Client

1. **Visibility matrix** — confirm §4 defaults, especially seals, agent contacts, NPWP, and document downloads.
2. **Delivery track** — same BL continuation, or separate shipment record?
3. **Export sample** — do they have an export BL PDF to seed, or is a synthetic export demo acceptable?
4. **Lane timing** — is customs lane known only after “Proses respon PIB”, or sometimes earlier?
5. **Labels** — customer UI in Bahasa Indonesia only, or bilingual?
6. **Who is the customer account?** — always consignee (as in samples), or sometimes shipper/notify party?
7. **Milestone skipping** — can admin skip steps (e.g. jump green path), or must every prior step be completed?
8. **On hold** — does on-hold pause the current milestone only, or freeze the whole BL?

---

## 12. Success Criteria

This adjustment is successful when:

1. Admin can enter any of the five provided BLs with accurate header + container lines.
2. Import BLs can follow Hijau / Kuning / Merah paths with the correct milestone tails.
3. Export BLs can follow the PEB/NPE/export-card path.
4. Customer timeline visually matches the client process sketches.
5. Customer cannot see admin-only notes/documents/fields.
6. Existing demo guarantees still hold: unique BL numbers, OTP customer login, per-customer data isolation, GPS external link.

---

## 13. Suggested Build Order

1. Checkpoint 0 confirmation (short client async review)
2. Schema + workflow config
3. Domain services / milestone engine
4. Admin forms + containers + documents
5. Customer timeline UI
6. Reseed from real BL samples
7. UAT pack for client

Estimated sequencing assumption: schema/workflow first, UI second — do not redesign customer visuals before milestone states exist.
