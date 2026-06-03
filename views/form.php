<?php
/**
 * Shikem Connector Settings Form
 */

$status = $status ?? [
    'status' => 'unconfigured',
    'message' => 'Not yet connected to Shikem',
    'lastHeartbeat' => null,
];
$isConnected = $status['status'] === 'connected';
$settings = $settings ?? [];
$flash = $flash ?? null;

function shikem_connector_h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function shikem_connector_sync_value($settings, $type, $field, $fallback = null) {
    return $settings['last_sync'][$type][$field] ?? $fallback;
}

function shikem_connector_format_time($timestamp) {
    return $timestamp ? date('Y-m-d H:i:s T', (int) $timestamp) : 'Never';
}
?>

<div class="container-fluid">
  <?php if ($flash): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="alert alert-<?php echo shikem_connector_h($flash['type'] ?? 'info'); ?>" role="alert">
          <?php echo shikem_connector_h($flash['message'] ?? ''); ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Shikem Connector Status</h3>
        </div>
        <div class="card-body">
          <div class="alert alert-<?php echo $isConnected ? 'success' : 'warning'; ?>" role="alert">
            <strong><?php echo shikem_connector_h(ucfirst($status['status'])); ?>:</strong>
            <?php echo shikem_connector_h($status['message']); ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($isConnected): ?>
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Configuration</h3>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label for="shikem_api_url">Shikem API URL</label>
              <input type="text" class="form-control" id="shikem_api_url" readonly value="<?php echo shikem_connector_h($settings['api_url'] ?? '[Configured]'); ?>">
            </div>

            <div class="form-group">
              <label for="connector_status">Connector Status</label>
              <select class="form-control" id="connector_status" disabled>
                <option>Connected (Read-only)</option>
              </select>
            </div>

            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="sync_all">
              <button type="submit" class="btn btn-primary">Sync Now</button>
            </form>
            <button type="button" class="btn btn-warning">Rotate Token</button>
            <button type="button" class="btn btn-danger">Disconnect</button>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Sync Status</h3>
          </div>
          <div class="card-body">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Data Type</th>
                  <th>Last Sync</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Extensions</td>
                  <td id="last_sync_extensions"><?php echo shikem_connector_h(shikem_connector_format_time(shikem_connector_sync_value($settings, 'extensions', 'timestamp'))); ?></td>
                  <td><span class="badge badge-<?php echo shikem_connector_sync_value($settings, 'extensions', 'success') ? 'success' : 'secondary'; ?>"><?php echo shikem_connector_sync_value($settings, 'extensions', 'success') ? 'Synced' : 'Pending'; ?></span></td>
                </tr>
                <tr>
                  <td>Call Records (CDR)</td>
                  <td id="last_sync_cdr"><?php echo shikem_connector_h(shikem_connector_format_time(shikem_connector_sync_value($settings, 'cdr', 'timestamp'))); ?></td>
                  <td><span class="badge badge-<?php echo shikem_connector_sync_value($settings, 'cdr', 'success') ? 'success' : 'secondary'; ?>"><?php echo shikem_connector_sync_value($settings, 'cdr', 'success') ? 'Synced' : 'Pending'; ?></span></td>
                </tr>
                <tr>
                  <td>Voicemail</td>
                  <td id="last_sync_voicemail"><?php echo shikem_connector_h(shikem_connector_format_time(shikem_connector_sync_value($settings, 'voicemail', 'timestamp'))); ?></td>
                  <td><span class="badge badge-<?php echo shikem_connector_sync_value($settings, 'voicemail', 'success') ? 'success' : 'secondary'; ?>"><?php echo shikem_connector_sync_value($settings, 'voicemail', 'success') ? 'Synced' : 'Pending'; ?></span></td>
                </tr>
                <tr>
                  <td>Recordings</td>
                  <td id="last_sync_recordings"><?php echo shikem_connector_h(shikem_connector_format_time(shikem_connector_sync_value($settings, 'recordings', 'timestamp'))); ?></td>
                  <td><span class="badge badge-<?php echo shikem_connector_sync_value($settings, 'recordings', 'success') ? 'success' : 'secondary'; ?>"><?php echo shikem_connector_sync_value($settings, 'recordings', 'success') ? 'Synced' : 'Pending'; ?></span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Connect to Shikem</h3>
          </div>
          <div class="card-body">
            <p>Enter your Shikem API credentials below to connect this FreePBX system.</p>

            <form id="shikem_connect_form" method="post">
              <input type="hidden" name="action" value="connect">
              <div class="form-group">
                <label for="shikem_api_url">Shikem API URL</label>
                <input type="url" class="form-control" id="shikem_api_url" name="shikem_api_url"
                       value="https://shikem.com" placeholder="https://shikem.com" required>
              </div>

              <div class="form-group">
                <label for="temp_username">Temporary Username</label>
                <input type="text" class="form-control" id="temp_username" name="temp_username" required>
              </div>

              <div class="form-group">
                <label for="temp_password">Temporary Password</label>
                <input type="password" class="form-control" id="temp_password" name="temp_password" required>
              </div>

              <button type="submit" class="btn btn-primary">Connect to Shikem</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
