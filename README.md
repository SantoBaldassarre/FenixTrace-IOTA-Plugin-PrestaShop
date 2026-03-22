# FenixTrace for PrestaShop

PrestaShop module that registers products on the **IOTA L1** blockchain via the FenixTrace Integration Kit.

> Built by [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## How It Works

```
PrestaShop Product → JSON → Integration Kit → IPFS + IOTA L1 → FenixTrace Scanner
```

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
Edit any product → scroll to **"FenixTrace Blockchain"** panel → click **"Send to FenixTrace"**

### Auto-Sync
Enable in module settings — products are automatically synced when saved.

## Database

The module creates a `ps_fenixtrace_sync` table to track sync status per product:
- `state`: draft / queued / synced / error
- `tx_hash`: IOTA blockchain transaction hash
- `notarization_tx_hash`: Notarization transaction hash
- `ipfs_hash`: IPFS content hash
- `last_sync_at`: Timestamp of last successful sync

## Links

- [FenixTrace Platform](https://trace.fenixsoftwarelabs.com)
- [Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit)
- [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## License

AFL-3.0 (Academic Free License)
