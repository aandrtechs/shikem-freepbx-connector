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
5. Enter the temporary username and password generated in Shikem. Leave the API URL as `https://shikem.com` for production, or change it to `https://test.shikem.com` when using staging credentials.

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

## Collector Coverage

The module has read-only collectors for:

- Extensions: users, devices, SIP/PJSIP/IAX non-secret settings
- CDR: recent call detail records
- Voicemail: mailbox metadata and message metadata files
- Recordings: recording file metadata
- Inventory foundation: trunks, inbound routes, outbound routes, queues, ring groups, IVRs, time conditions, time groups, and conferences

Credential-like fields such as secrets, passwords, tokens, private keys, and API keys are filtered out before sync.

## Current Status

This repository is an early connector module. It includes a FreePBX settings page at `Settings > Shikem Connector`, the temporary credential claim flow, and a read-only collector/sync foundation for extensions, CDR, voicemail, recordings, and PBX inventory.

The Shikem backend endpoints and settings UI are implemented in the ARSMS application. The FreePBX module still needs full PHP implementation for:

- Scheduling periodic heartbeats and syncs
- Displaying the collected inventory in Shikem
- Deep feature-specific handling for trunks, routes, queues, IVRs, and ring groups

## Troubleshooting

- Confirm the Shikem API URL is reachable from the PBX server.
- Generate fresh temporary credentials if more than 15 minutes have passed.
- Check `/etc/asterisk/shikem/connector.conf` permissions.
- Check Asterisk/FreePBX logs for module errors.

## Version

Initial scaffold: 2026-06-02
