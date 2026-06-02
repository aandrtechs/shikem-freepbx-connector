<?php
/**
 * Shikem API Client
 * Handles HTTPS communication with Shikem API
 */

namespace Shikem;

class ApiClient {

    private $baseUrl;
    private $connectorToken;

    public function __construct($baseUrl, $connectorToken = null) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->connectorToken = $connectorToken;
    }

    /**
     * Make HTTP request to Shikem API
     */
    private function request($method, $endpoint, $data = null, $useToken = true) {
        $url = $this->baseUrl . $endpoint;

        $options = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Content-Type: application/json',
                ],
                'timeout' => 30,
            ],
        ];

        if ($useToken && $this->connectorToken) {
            $options['http']['header'][] = 'Authorization: Bearer ' . $this->connectorToken;
        }

        if ($data) {
            $options['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($options);

        try {
            $response = file_get_contents($url, false, $context);
            return json_decode($response, true);
        } catch (\Exception $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send heartbeat to Shikem
     */
    public function sendHeartbeat($serverUuid, $pbxData) {
        return $this->request('POST', '/api/customer/integrations/freepbx/heartbeat', [
            'connectorToken' => $this->connectorToken,
            'serverUuid' => $serverUuid,
            'pbxData' => $pbxData,
        ], false);
    }

    /**
     * Sync extensions to Shikem
     */
    public function syncExtensions($serverUuid, $extensions) {
        return $this->request('POST', '/api/customer/integrations/freepbx/sync', [
            'connectorToken' => $this->connectorToken,
            'serverUuid' => $serverUuid,
            'syncType' => 'extensions',
            'data' => $extensions,
        ], false);
    }

    /**
     * Sync CDR to Shikem
     */
    public function syncCDR($serverUuid, $cdrRecords) {
        return $this->request('POST', '/api/customer/integrations/freepbx/sync', [
            'connectorToken' => $this->connectorToken,
            'serverUuid' => $serverUuid,
            'syncType' => 'cdr',
            'data' => $cdrRecords,
        ], false);
    }

    /**
     * Sync voicemail to Shikem
     */
    public function syncVoicemail($serverUuid, $voicemailData) {
        return $this->request('POST', '/api/customer/integrations/freepbx/sync', [
            'connectorToken' => $this->connectorToken,
            'serverUuid' => $serverUuid,
            'syncType' => 'voicemail',
            'data' => $voicemailData,
        ], false);
    }

    /**
     * Sync recordings to Shikem
     */
    public function syncRecordings($serverUuid, $recordingData) {
        return $this->request('POST', '/api/customer/integrations/freepbx/sync', [
            'connectorToken' => $this->connectorToken,
            'serverUuid' => $serverUuid,
            'syncType' => 'recordings',
            'data' => $recordingData,
        ], false);
    }
}
