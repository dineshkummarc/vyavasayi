# Byabsayee: Font Awesome and emoji replaced by your SVG icon collection

This zip contains ONLY the files that changed (same folder layout as the project). Nothing else needs touching.

## What changed
- Font Awesome is gone: the kit script (`kit.fontawesome.com/86c0c1c09a`) is removed from all 5 page templates.
- All ~610 `<i class="fa-...">` tags are now inline SVG icons from your collection.
- All emoji and symbol characters (✓ ✗ ⚠ ✅ 🧾 📦 ...) in pages, scripts, tests and docs are replaced by icons or plain text.
- 135 new icons were drawn (Byabsayee needed far more than the original 98). They are in `public/icons/` and in the updated collection zip.

## How to install
1. Unzip this over your project folder, so the files land in the same paths (replace when asked).
   Do not delete anything; there is nothing to remove.
2. Redeploy as usual (rebuild the image, or with the dev bind mount just reload).
   On startup `bin/migrate_core.php` runs by itself and converts the icon names stored in
   `expense_categories.icon` (old `fa-tag` style names become `tag` style names). It is safe to re-run.
3. Hard-refresh the browser once (Ctrl+Shift+R). nginx caches CSS/JS for 7 days, and the new
   `icons.css`, `icons.js`, `app.css`, `mobile.css`, `settings.css` must be fetched fresh.
4. Optional check (no database needed): `php tests/icons.php`
   It fails if any Font Awesome reference or emoji comes back, if an icon name does not exist,
   or if `public/js/icons.js` is out of date.

## How icons work now
- In a view:  `<?= icon('plus') ?>`   `<?= icon('check-circle', '', 'color:var(--green)') ?>`
  (arguments: name, extra CSS classes, inline style, extra attributes array)
- In JavaScript:  `Icons.svg('plus')`   `Icons.set(element, 'check')` (replaces the element's contents)
- Size comes from `font-size` and colour from the text colour, so `style="font-size:22px;color:red"` works like before.
  Extra classes: `ic-xs` (smaller), `ic-lg` (bigger), `ic-spin` (rotating).
- To add an icon: copy `NAME.svg` into `public/icons/ui/` (or `social/`), then run `php bin/build-icons.php`
  (inside the container: `docker exec <container> php bin/build-icons.php`). PHP picks the file up at once;
  the script only refreshes `public/js/icons.js` for JavaScript.
- An unknown icon name shows a plain circle instead of breaking the page.

## Things to know
- Emails and PDFs: mail clients cannot show inline SVG, so the emoji in the 3 emails were simply removed. PDFs never used icons.
- The language list in Settings no longer has flag emoji (a dropdown option cannot hold an SVG).
- Table sort arrows (they were text arrows) are now drawn from the icons through CSS (`app.css`, `--ic-sort-*`).
- Arrows used inside normal sentences (for example "Customer returning goods → stock added back") were left as text, because they are punctuation, not icons.
  Arrows used as button/link icons ("← Back", "Go to App →") are real icons now.
- `app/Helpers/Icon.php` keeps a small table of the OLD icon names. It is only used to translate values that may still be saved in the
  database or in old links; no Font Awesome code or font is loaded anywhere.
- `public/migrate_profiles.php` output lines now start with `OK:` / `WARN:` / `INFO:` instead of emoji.

## Bugs fixed on the way
- Invoice print page "Back" button had a left-right arrow; it is now a left arrow.
- `views/books/personal.php`: attachment file names were put into the page without escaping (could inject HTML); now escaped.
- Error pages no longer depend on the icon font loading; icons are inline.

## Files in this zip
- New: `app/Helpers/Icon.php`, `public/css/icons.css`, `public/js/icons.js`, `bin/build-icons.php`, `tests/icons.php`, and 231 icons under `public/icons/ui/` and `public/icons/social/`.
- Modified (81):
  - app: `app/Controllers/BookLogsController.php`, `app/Controllers/ComingSoonController.php`, `app/Controllers/EmployeeController.php`, `app/Controllers/ExpensesController.php`, `app/Controllers/ProfileController.php`, `app/Controllers/TwoFactorController.php`, `app/Helpers/helpers.php`, `app/Services/ActivityLogger.php`, `app/Services/Integration/Lifecycle.php`, `app/Services/LedgerService.php`
  - bin: `bin/migrate_core.php`
  - public: `public/css/app.css`, `public/css/mobile.css`, `public/css/settings.css`, `public/index.php`, `public/js/app.js`, `public/migrate_profiles.php`
  - root: `README-INSTALL.md`, `README.md`, `deploy.sh`, `schema.sql`
  - tests: `tests/e2e.php`, `tests/migrate_repair.php`, `tests/phase6.php`, `tests/reports.php`, `tests/settings_ui.php`, `tests/smoke.php`
  - views: `views/auth/2fa-challenge.php`, `views/auth/forgot-password.php`, `views/auth/layout.php`, `views/books/business.php`, `views/books/contacts.php`, `views/books/create.php`, `views/books/edit.php`, `views/books/index.php`, `views/books/integrations.php`, `views/books/logs.php`, `views/books/personal.php`, `views/business/contacts/index.php`, `views/business/coupons/index.php`, `views/business/coupons/print.php`, `views/business/customers/index.php`, `views/business/customers/show.php`, `views/business/debts/index.php`, `views/business/dues/index.php`, `views/business/employees/index.php`, `views/business/employees/invitation.php`, `views/business/employees/show.php`, `views/business/expenses/index.php`, `views/business/funds/index.php`, `views/business/invoices/create.php`, `views/business/invoices/index.php`, `views/business/invoices/pos.php`, `views/business/invoices/print.php`, `views/business/invoices/show.php`, `views/business/invoices/thermal.php`, `views/business/notifications/send.php`, `views/business/privileges/index.php`, `views/business/products/barcodes.php`, `views/business/products/index.php`, `views/business/purchases/index.php`, `views/business/reports/index.php`, `views/business/returns/create.php`, `views/business/returns/index.php`, `views/business/returns/show.php`, `views/business/sales/index.php`, `views/business/suppliers/index.php`, `views/business/suppliers/show.php`, `views/coming-soon.php`, `views/emails/contact-support-confirm.php`, `views/emails/invitation.php`, `views/emails/password-reset.php`, `views/errors/403.php`, `views/partials/dev-warning.php`, `views/partials/layout.php`, `views/partials/print-modal.php`, `views/profile/business-edit.php`, `views/profile/edit.php`, `views/public/business-profile.php`, `views/public/user-profile.php`, `views/settings/index.php`
