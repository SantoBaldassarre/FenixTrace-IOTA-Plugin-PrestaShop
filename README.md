# FenixTrace for PrestaShop

PrestaShop module that sends your product data to **FenixTrace** for traceability and EU compliance — origin proof, EUDR and Digital Product Passport (DPP) readiness, and tamper-proof, anti-counterfeiting evidence. FenixTrace handles all notarization automatically server-side; the module just forwards each product through the FenixTrace Integration Kit.

> Built by [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## How It Works

```
PrestaShop Product → JSON → Integration Kit → FenixTrace (notarization + evidence, server-side) → FenixTrace Scanner
```

The module's only job is to send product data to FenixTrace. Notarization, tamper-proof evidence and compliance records are handled automatically by FenixTrace in the background — you don't manage any of it from the store.

## Requirements

- PrestaShop 1.7+ or 8.x
- PHP 7.4+
- [FenixTrace Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit) running

## Installation

1. Copy the `FenixTrace-IOTA-Plugin-PrestaShop` folder to `modules/fenixtrace/`
2. Go to **Back Office → Modules → Module Manager**
3. Search for **"FenixTrace"** → **Install**
4. Click **Configure** to set the Integration Kit URL

## Configuration

| Setting | Description |
|---|---|
| Integration Kit URL | Where the Kit is running (default: `http://localhost:3005`) |
| Upload Directory | Optional path to Kit's `uploads/` folder |
| Product Template | Category template (agro, pharma, fashion, etc.) |
| Auto-sync on Save | Automatically sync when product is saved |

## Usage

### Single Product
Edit any product → scroll to the **"FenixTrace Blockchain"** panel → click **"Send to FenixTrace"**. FenixTrace then notarizes the product and builds its tamper-proof compliance record automatically — no extra steps in the store.

### Auto-Sync
Enable in module settings — products are automatically synced when saved.

## Database

The module creates a `ps_fenixtrace_sync` table to track sync status per product. The reference values below are produced by FenixTrace server-side and simply stored here so each product links back to its tamper-proof record:
- `state`: draft / queued / synced / error
- `tx_hash`: Notarization reference returned by FenixTrace
- `notarization_tx_hash`: Notarization reference returned by FenixTrace
- `ipfs_hash`: Evidence content reference returned by FenixTrace
- `last_sync_at`: Timestamp of last successful sync

## Other Plugins

| Plugin | Platform | Repository |
|---|---|---|
| **FenixTrace for Odoo** | Odoo 16/17 | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-IOTA-Plugin-Odoo) |
| **FenixTrace for WooCommerce** | WordPress + WooCommerce | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-IOTA-Plugin-WooCommerce) |

## Links

- [FenixTrace Platform](https://fenixtrace.com)
- [FenixTrace Integration Docs](https://fenixtrace.com/docs/integration-gateway)
- [Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit)
- [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## License

AFL-3.0 (Academic Free License)
