<?php
/**
 * FreePBX page entrypoint for the Shikem Connector module.
 */

if (!defined('FREEPBX_IS_AUTH')) {
    die('No direct script access allowed');
}

require_once __DIR__ . '/lib/StorageManager.php';
require_once __DIR__ . '/lib/ApiClient.php';

$storageManager = new \Shikem\StorageManager();
$flash = null;

function shikem_connector_value($key) {
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}

function shikem_connector_command($command) {
    $output = [];
    $status = 0;
    @exec($command . ' 2>/dev/null', $output, $status);
    return $status === 0 ? trim(implode("\n", $output)) : '';
}

function shikem_connector_detect_public_ip() {
    $ip = shikem_connector_command('hostname -I');
    if ($ip !== '') {
        $parts = preg_split('/\s+/', $ip);
        foreach ($parts as $part) {
            if (filter_var($part, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $part;
            }
        }
    }
    return $_SERVER['SERVER_ADDR'] ?? 'unknown';
}

function shikem_connector_pbx_data($storageManager) {
    $hostname = php_uname('n') ?: 'unknown';
    $freepbxVersion = shikem_connector_command('fwconsole -V');
    $asteriskVersion = shikem_connector_command('asterisk -rx "core show version"');

    return [
        'hostname' => $hostname,
        'publicIp' => shikem_connector_detect_public_ip(),
        'localIp' => $_SERVER['SERVER_ADDR'] ?? null,
        'freepbxVersion' => $freepbxVersion ?: 'unknown',
        'asteriskVersion' => $asteriskVersion ?: 'unknown',
        'moduleVersion' => '1.0.2',
        'serverUuid' => $storageManager->getServerUUID(),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && shikem_connector_value('action') === 'connect') {
    $apiUrl = shikem_connector_value('shikem_api_url');
    $tempUsername = shikem_connector_value('temp_username');
    $tempPassword = shikem_connector_value('temp_password');

    if ($apiUrl === '' || $tempUsername === '' || $tempPassword === '') {
        $flash = [
            'type' => 'danger',
            'message' => 'Shikem API URL, temporary username, and temporary password are required.',
        ];
    } else {
        $client = new \Shikem\ApiClient($apiUrl);
        $pbxData = shikem_connector_pbx_data($storageManager);
        $result = $client->claimConnection($tempUsername, $tempPassword, $pbxData);

        if (!empty($result['ok']) && !empty($result['data']['connectorToken'])) {
            $settings = $storageManager->loadSettings();
            $settings['api_url'] = rtrim($apiUrl, '/');
            $settings['connector_token'] = $result['data']['connectorToken'];
            $settings['connector_id'] = $result['data']['connectorId'] ?? null;
            $settings['server_uuid'] = $pbxData['serverUuid'];
            $settings['hostname'] = $pbxData['hostname'];
            $settings['public_ip'] = $pbxData['publicIp'];
            $settings['connected_at'] = time();
            $saveResult = $storageManager->saveSettings($settings);

            $flash = [
                'type' => !empty($saveResult['success']) ? 'success' : 'danger',
                'message' => !empty($saveResult['success'])
                    ? 'Connected to Shikem successfully.'
                    : ($saveResult['error'] ?? 'Connected to Shikem, but failed to save local settings.'),
            ];
        } else {
            $flash = [
                'type' => 'danger',
                'message' => $result['error'] ?? 'Failed to connect to Shikem.',
            ];
        }
    }
}

$settings = $storageManager->loadSettings();
$isConnected = !empty($settings['connector_token']);
$status = [
    'status' => $isConnected ? 'connected' : 'unconfigured',
    'message' => $isConnected ? 'Connected to Shikem' : 'Not yet connected to Shikem',
    'lastHeartbeat' => $settings['last_heartbeat'] ?? null,
];

include __DIR__ . '/views/form.php';
