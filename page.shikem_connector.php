<?php
/**
 * FreePBX page entrypoint for the Shikem Connector module.
 */

if (!defined('FREEPBX_IS_AUTH')) {
    die('No direct script access allowed');
}

require_once __DIR__ . '/lib/StorageManager.php';

$storageManager = new \Shikem\StorageManager();
$settings = $storageManager->loadSettings();
$isConnected = !empty($settings['connector_token']);
$status = [
    'status' => $isConnected ? 'connected' : 'unconfigured',
    'message' => $isConnected ? 'Connected to Shikem' : 'Not yet connected to Shikem',
    'lastHeartbeat' => $settings['last_heartbeat'] ?? null,
];

include __DIR__ . '/views/form.php';
