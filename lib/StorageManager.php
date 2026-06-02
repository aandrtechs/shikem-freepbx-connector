<?php
/**
 * Shikem Connector Storage Manager
 * Handles secure storage of credentials and settings
 */

namespace Shikem;

class StorageManager {

    private $configDir = '/etc/asterisk/shikem';
    private $configFile = '/etc/asterisk/shikem/connector.conf';

    public function __construct() {
        // Ensure config directory exists
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0700, true);
            chmod($this->configDir, 0700);
        }
    }

    /**
     * Load connector settings from secure storage
     */
    public function loadSettings() {
        if (!file_exists($this->configFile)) {
            return [];
        }

        $content = file_get_contents($this->configFile);
        return json_decode($content, true) ?? [];
    }

    /**
     * Save connector settings securely
     */
    public function saveSettings($settings) {
        $content = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $result = file_put_contents($this->configFile, $content, LOCK_EX);

        if ($result === false) {
            return [
                'success' => false,
                'error' => 'Failed to write configuration file'
            ];
        }

        // Secure file permissions
        chmod($this->configFile, 0600);

        return [
            'success' => true,
            'message' => 'Settings saved'
        ];
    }

    /**
     * Get connector token
     */
    public function getConnectorToken() {
        $settings = $this->loadSettings();
        return $settings['connector_token'] ?? null;
    }

    /**
     * Set connector token
     */
    public function setConnectorToken($token) {
        $settings = $this->loadSettings();
        $settings['connector_token'] = $token;
        $settings['connector_token_set_at'] = time();
        return $this->saveSettings($settings);
    }

    /**
     * Clear all settings (disconnect)
     */
    public function clearSettings() {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        return [
            'success' => true,
            'message' => 'Settings cleared'
        ];
    }

    /**
     * Get server UUID (unique identifier for this FreePBX system)
     */
    public function getServerUUID() {
        $settings = $this->loadSettings();

        if (empty($settings['server_uuid'])) {
            // Generate new UUID if not set
            $settings['server_uuid'] = $this->generateUUID();
            $this->saveSettings($settings);
        }

        return $settings['server_uuid'];
    }

    /**
     * Generate UUID v4
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Log sync event
     */
    public function logSyncEvent($eventType, $success, $message = null) {
        $settings = $this->loadSettings();

        if (!isset($settings['sync_logs'])) {
            $settings['sync_logs'] = [];
        }

        $event = [
            'type' => $eventType,
            'success' => $success,
            'message' => $message,
            'timestamp' => time(),
        ];

        // Keep only last 100 sync logs
        $settings['sync_logs'][] = $event;
        if (count($settings['sync_logs']) > 100) {
            array_shift($settings['sync_logs']);
        }

        $this->saveSettings($settings);
    }
}
