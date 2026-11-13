# Receipt printer integration (58/80 mm)

## What is implemented

The Orders page and order details now provide:
- Customer order copy / receipt, available even before payment. It shows the order number, table, customer, items, totals and payment entries when they exist.
- Kitchen ticket, without financial totals, with the order number, table, items, quantities and customer preparation note.
- Invoice, plus a backend print-attempt log. Reprints reuse the same saved order and are marked as reprints.

The Settings page can connect to QZ Tray on the current cashier workstation, discover printers already installed in that workstation's operating system, and route customer slips and kitchen slips to separate printer names. The selected printer names are stored in that browser's local storage, because each workstation can have different printers.

When a printer is selected and QZ Tray is running, the dashboard sends the rendered HTML ticket to that local printer. If no local printer is configured or QZ Tray is unavailable, the browser print dialog is used as a fallback.

## Workstation setup

1. Install the manufacturer's Windows/macOS/Linux driver for each receipt printer.
2. Connect the printer by USB, Ethernet or Wi-Fi and verify it is visible in the operating system's Printers list. A network printer must first be added to that workstation.
3. Install and run QZ Tray 2.3.x on the cashier workstation. The admin dashboard loads the QZ Tray 2.3.0 JavaScript connector.
4. Sign in to the dashboard on that same workstation. Open Settings → Connect printers → Connect and discover printers.
5. Select the customer/cashier printer and kitchen printer separately, save the routing, and run both test prints.
6. Open an order and choose Customer copy or Kitchen ticket. If the assigned local printer is connected, the job is sent through QZ Tray; otherwise use the browser dialog.

The dashboard discovers the printer names that the local operating system exposes. It does not infer a physical printer's brand, paper width, paper-out status or cutter capabilities from its name; use the exact model's driver and settings for that.

## Security and silent printing

QZ Tray listens on the cashier computer's local machine; the VPS does not and cannot directly access the cashier's USB port. Do not expose QZ Tray's local WebSocket ports to the public internet.

QZ Tray requires signed messages to suppress its security prompts and enable fully silent printing. This repository does not contain a QZ Tray signing certificate/private key, so this release does not claim unattended silent printing. Depending on QZ Tray's trust configuration, it may ask the workstation operator to allow the connection/print request. A certificate and matching private key must be provisioned securely before enabling signed-message callbacks. Never place a private signing key in browser JavaScript or commit it to Git.

A successful QZ Tray print promise means the job was sent to the local print service; it does not prove paper physically came out. The application records a print request, not a sensor-confirmed physical print.

## Printer routing and paper

- Customer / cashier ticket: the workstation's selected cashier printer.
- Kitchen ticket: the workstation's selected kitchen printer.
- Paper width: select 58 mm or 80 mm in restaurant Settings and configure the same width in the operating-system printer driver.
- Multiple workstations: set the printer routing on each workstation/browser separately.

## Remaining hardware-specific verification

No exact printer model, workstation OS, USB/network connection type or QZ signing certificate was supplied. Therefore the repository supports discovery and local routing but still needs a real-device test for Persian glyphs, margins, cutter behavior, 58/80 mm roll layout, offline printer handling and paper-out feedback. A print request is not the same as a confirmed physical print.
