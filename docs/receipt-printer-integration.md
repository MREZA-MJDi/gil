# Receipt printer integration (58/80 mm)

## Current behavior

The dashboard builds a receipt, invoice or kitchen slip from the saved order and opens the browser print dialog. The application records that a print was requested; it cannot confirm that paper physically came out. Browser printing is intentionally not described as silent/automatic printing.

## Choose the deployment model

### 1. A USB or network printer that the operating system already sees

This is the simplest way to start. Install the official printer driver on the cashier computer, set the roll width (58 mm or 80 mm) and cut/margin behavior in the operating system printer preferences, then print from the dashboard. The print dialog lets the cashier choose the receipt printer. A network printer can be installed by its IP/hostname if its model supports that.

Advantages: no extra service and little setup. Trade-off: an operator may need to confirm the print dialog, and browser/driver margins vary by model.

### 2. Silent printing with a local desktop bridge (recommended for a busy POS)

Browsers deliberately do not expose unrestricted raw USB/serial/TCP printer access to ordinary web pages. For unattended one-click or automatic printing, install a small trusted local service on the cashier PC, for example a desktop companion using QZ Tray or an Electron-based POS agent. The dashboard sends a structured print job to that local service; the service owns the printer connection and talks ESC/POS or the vendor driver.

A production bridge should:
- listen only on loopback or a controlled local network, never expose an unauthenticated public port;
- allowlist the printer IDs and supported job types;
- authenticate requests (and use the bridge's supported signed-message/certificate workflow where applicable);
- validate job size and payload type and reject arbitrary raw commands from the browser;
- use a bounded queue, a timeout, retry policy and a stable job ID to avoid duplicate receipts;
- return states such as queued, sent, confirmed (only if device feedback exists), failed and unknown; do not equate “sent to driver” with “paper printed”;
- keep a printer health screen for offline / paper-out / cover-open where the hardware supports status reporting.

### 3. Multiple workstations and kitchen printers

Keep printer routing configurable per station: cashier_receipt, kitchen_hot, bar_cold, and optionally invoice. The backend should store printer configuration without secrets, and the local bridge should map logical printer IDs to installed devices. A kitchen job should be sent only after the order is persisted. Delivery/payment receipt should be issued from the stored order/payment ledger, not from unsaved browser state.

## Recommended implementation sequence

1. Start with normal OS-installed printer + browser dialog to validate paper width, font, Persian glyphs and cut behavior.
2. Pick the exact printer model and OS (Windows/Linux/macOS), and whether it is USB, Ethernet or Wi-Fi.
3. Add a signed/local print bridge and a print_jobs outbox table. Create the print job inside the same transaction as the business action where appropriate, process it asynchronously and update its delivery state.
4. Implement idempotent job IDs and retry without creating duplicate financial transactions. Receipt reprints must reuse the order/payment references and be marked as reprints.
5. Test disconnected printer, paper-out, malformed response, server restart, duplicate click, Unicode/Persian text, long order, 58 mm and 80 mm rolls.

## What is not configured yet

The repository does not identify a printer model, OS, connection type, or local bridge. Therefore it is not safe to claim true direct/automatic printing is already wired. The current implementation provides the live server data and browser-print fallback, plus a backend print-attempt log. Select a printer model and deployment method before implementing a concrete bridge.
