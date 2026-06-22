# Phase 1 Checkpoint Plan

Goal: build a simple client-ready BL tracking mockup without overengineering the phase workflow, retention strategy, or GPS integration.

## Ground Rules

- Keep Phase 1 focused on the demo workflow, not the final operational system.
- Use Filament for the admin dashboard because it is already in `composer.json`.
- Keep the customer dashboard simple. Breeze is not currently installed, so only add it if it clearly speeds up the customer auth screens.
- OTP can be displayed directly on the login form until SMTP is configured.
- Phase control starts as a plain field/dropdown. Role-based phase locking can wait until the client confirms the process.
- GPS tracking starts as a saved external URL and a customer-facing clickable link.

## Checkpoint 1: Confirm Base App Runs

Build:

- Confirm Laravel boots locally.
- Confirm database connection is configured.
- Confirm migrations can run in the local environment.

Test:

- Run `php artisan test`.
- Run `php artisan migrate:fresh`.
- Open the app homepage and confirm there is no server error.

Pass condition:

- The app loads.
- The test suite passes.
- Migrations complete cleanly.

## Checkpoint 2: Define Minimal Data Model

Build:

- Add customer/admin distinction to users, using a simple role field.
- Create `bill_of_ladings` table:
  - `bl_number`
  - `customer_id`
  - `shipment_description`
  - `input_date`
  - `status`
  - `phase`
  - `gps_tracking_url`
- Create `bill_of_lading_updates` table:
  - `bill_of_lading_id`
  - `user_id`
  - `status`
  - `phase`
  - `note`

Test:

- Migration test: `php artisan migrate:fresh`.
- Model test: a BL can be created for a customer.
- Constraint test: duplicate BL numbers are rejected.
- Relationship test: a BL belongs to one customer and has many updates.

Pass condition:

- The database supports the core BL tracking workflow.
- BL number uniqueness is enforced at database and validation level.

## Checkpoint 3: Seed Demo Accounts And Data

Build:

- Seed 1 admin account.
- Seed 2 customer accounts.
- Seed BL records for both customers.
- Seed update history for each BL.

Test:

- Seeder test: `php artisan migrate:fresh --seed`.
- Verify exactly 1 admin and 2 customers exist.
- Verify both customers have at least one BL.
- Verify customer A and customer B have separate BL records.

Pass condition:

- The app has predictable demo data for client review.

## Checkpoint 4: Admin Login

Build:

- Configure admin login through Filament.
- Only admin users can access the admin panel.
- Customer users must be blocked from the admin panel.

Test:

- Admin can log in and access Filament.
- Customer cannot access Filament.
- Guest user is redirected to login.

Pass condition:

- Admin access is separated from customer access.

## Checkpoint 5: Admin Customer Management

Build:

- Add a Filament resource for customers.
- Admin can create and edit customer records.
- Enforce one account per email.

Test:

- Admin can create a customer.
- Admin can edit a customer name/email.
- Duplicate customer email is rejected.
- Customer records are visible in the admin dashboard.

Pass condition:

- Admin can manage customer accounts manually.

## Checkpoint 6: Admin BL Management

Build:

- Add a Filament resource for BL records.
- Admin can create, edit, and view BL records.
- Admin can assign each BL to a customer.
- Admin can set status, phase, notes, and GPS tracking URL.

Test:

- Admin can create a BL for customer A.
- Admin can create a BL for customer B.
- Admin cannot create two BL records with the same BL number.
- Admin can update status and phase.
- Admin can save a valid GPS URL.

Pass condition:

- Admin can maintain the core tracking records from the dashboard.

## Checkpoint 7: Status Update History

Build:

- When admin updates BL status, phase, or note, save an update history record.
- Show update history on the BL detail page.

Test:

- Updating a BL creates a history row.
- Multiple updates appear in chronological order.
- History records preserve old tracking notes instead of overwriting them.

Pass condition:

- Customers and admins can see a simple audit trail for each BL.

## Checkpoint 8: Customer Passwordless Login

Build:

- Customer enters registered email.
- App generates an OTP.
- For Phase 1, display the OTP directly on the form or next screen.
- Customer submits OTP to log in.
- Unregistered email cannot log in.

Test:

- Registered customer can request OTP.
- Displayed OTP can be used to log in.
- Wrong OTP is rejected.
- Unregistered email is rejected.
- Admin email cannot use the customer OTP login.

Pass condition:

- Customer login works without passwords and without SMTP.

## Checkpoint 9: Customer Dashboard

Build:

- Customer sees only their own BL records.
- Customer can open BL detail.
- Customer can see status, phase, shipment description, GPS URL, and update history.
- Customer cannot create or edit BL records.

Test:

- Customer A sees only customer A BL records.
- Customer B sees only customer B BL records.
- Customer A cannot access customer B BL detail by changing the URL.
- Guest user cannot access the customer dashboard.
- GPS URL renders as a clickable external link when present.

Pass condition:

- Customer data isolation is proven.

## Checkpoint 10: Simple Demo Polish

Build:

- Add clear labels for BL number, customer, status, phase, and last update.
- Add empty states for customers with no BL records.
- Add basic filtering/searching only where it helps the demo:
  - Admin: search by BL number or customer.
  - Customer: search by BL number.

Test:

- Admin can find a BL by BL number.
- Customer can find their own BL by BL number.
- Empty state appears when a customer has no records.
- Pages are usable on desktop and mobile widths.

Pass condition:

- The demo is clear enough for client review without adding advanced features.

## Checkpoint 11: Final Phase 1 Verification

Build:

- Review the full demo flow end to end.
- Fix only demo-blocking issues.
- Defer advanced workflow rules until after client feedback.

Test:

- Run `php artisan test`.
- Run `php artisan migrate:fresh --seed`.
- Manual flow:
  - Admin logs in.
  - Admin creates a customer.
  - Admin creates a BL for that customer.
  - Admin updates status/phase.
  - Customer logs in with OTP.
  - Customer sees only their own BL.
  - Customer opens the GPS URL.

Pass condition:

- Phase 1 is ready to show to the client.

## Explicitly Deferred Until After Phase 1

- Real email delivery for OTP.
- Complex role-based phase locking.
- Multi-admin phase permissions.
- Data archival automation.
- Advanced reports and analytics.
- Full-text search across all historical records.
- GPS platform API integration.
- Notification system.
