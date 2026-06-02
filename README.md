# Shikem FreePBX Connector

FreePBX module scaffold for connecting a FreePBX system to Shikem. Phase 1 supports the secure connector-claim flow and the shape of read-only sync calls for extensions, CDR, voicemail, and recordings.

## Requirements

- FreePBX 14 or later
- Asterisk 13 or later
- PHP 7.2 or later
- Outbound HTTPS access to Shikem

## Installation

```bash
cd /usr/src
git clone https://github.com/aandrtechs/shikem-freepbx-connector.git
cd shikem-freepbx-connector
sudo ./install.sh
```

Then enable the module in FreePBX:

1. Open FreePBX Admin.
2. Go to Admin > Module Admin.
3. Enable Shikem Connector.
4. Open Settings > Shikem Connector.
5. Enter the Shikem API URL plus the temporary username and password generated in Shikem.

## Security Model

- Temporary credentials expire after 15 minutes.
- Temporary credentials are single-use.
- The permanent connector token is returned once by Shikem and must be stored on the PBX.
- Shikem stores only a hash of the permanent connector token.
- Connector calls authenticate with `Authorization: Bearer <connectorToken>`.
- The connector is intended to be read-only.

## API Endpoints

- `POST /api/customer/integrations/freepbx/claim`
- `POST /api/customer/integrations/freepbx/heartbeat`
- `POST /api/customer/integrations/freepbx/sync`

## Current Status

This repository is a scaffold. It includes a FreePBX settings page at `Settings > Shikem Connector`, but the submit/sync actions are still placeholders. The Shikem backend endpoints and settings UI are implemented in the ARSMS application. The FreePBX module still needs full PHP implementation for:

- Claiming temporary credentials from the FreePBX admin UI
- Persisting the permanent connector token
- Sending scheduled heartbeats
- Collecting read-only extension, CDR, voicemail, and recording data
- Scheduling periodic syncs

## Troubleshooting

- Confirm the Shikem API URL is reachable from the PBX server.
- Generate fresh temporary credentials if more than 15 minutes have passed.
- Check `/etc/asterisk/shikem/connector.conf` permissions.
- Check Asterisk/FreePBX logs for module errors.

## Version

Initial scaffold: 2026-06-02
