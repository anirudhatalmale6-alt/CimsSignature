# CIMS Signature Module - Installation Guide

## Overview
Wacom STU-430/G Signature Capture Module for SmartDash.

## Requirements
- Wacom STU-430/G signature pad connected via USB
- Wacom SigCaptX service installed and running (for hardware capture)
- OR use the built-in canvas fallback for mouse/touch signatures

## Installation Steps

### 1. Copy Module
Copy the `CimsSignature` folder to your `Modules/` directory:
```
Modules/CimsSignature/
```

### 2. Enable Module
Add to `modules_statuses.json`:
```json
{
    "CimsSignature": true
}
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Clear Cache
```bash
php artisan optimize:clear
```

## Access
- Signature List: `/signatures`
- Capture New: `/signatures/capture`
- Quick Capture: `/signatures/quick`

## Features
- Capture signatures from Wacom STU-430/G via SigCaptX
- Fallback canvas for mouse/touch signatures (works without Wacom)
- Store signatures linked to client name/reference
- Retrieve signatures via API for use in other modules
- Signature integrity verification (SHA256 hash)

## API Endpoints

### Get Signature Image
```
GET /signatures/{id}/image
```
Returns signature data for a specific signature ID.

### Get Latest Client Signature
```
GET /signatures/client/{clientReference}
```
Returns the most recent signature for a client reference.

## Using Signatures in Other Modules

To embed a saved signature in documents:

```php
use Modules\CimsSignature\Models\ClientSignature;

// Get latest signature for a client
$signature = ClientSignature::getLatestForClient('CLT001');

// Use in Blade template
<img src="{{ $signature->signature_data }}" alt="Signature">
```

## SigCaptX Setup

1. Download SigCaptX from https://developer.wacom.com/
2. Install the SigCaptX service on Windows
3. Ensure the service is running (check Windows Services)
4. The module will auto-detect and connect

If SigCaptX is not available, the module falls back to canvas-based signature capture.

## Database Table
- `client_signatures` - Stores all captured signatures with metadata
