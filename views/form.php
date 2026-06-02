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
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Shikem Connector Status</h3>
        </div>
        <div class="card-body">
          <div class="alert alert-<?php echo $isConnected ? 'success' : 'warning'; ?>" role="alert">
            <strong><?php echo ucfirst($status['status']); ?>:</strong>
            <?php echo $status['message']; ?>
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
              <input type="text" class="form-control" id="shikem_api_url" readonly value="[Configured]">
            </div>

            <div class="form-group">
              <label for="connector_status">Connector Status</label>
              <select class="form-control" id="connector_status" disabled>
                <option>Connected (Read-only)</option>
              </select>
            </div>

            <button type="button" class="btn btn-primary">Sync Now</button>
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
                  <td id="last_sync_extensions">Never</td>
                  <td><span class="badge badge-secondary">Pending</span></td>
                </tr>
                <tr>
                  <td>Call Records (CDR)</td>
                  <td id="last_sync_cdr">Never</td>
                  <td><span class="badge badge-secondary">Pending</span></td>
                </tr>
                <tr>
                  <td>Voicemail</td>
                  <td id="last_sync_voicemail">Never</td>
                  <td><span class="badge badge-secondary">Pending</span></td>
                </tr>
                <tr>
                  <td>Recordings</td>
                  <td id="last_sync_recordings">Never</td>
                  <td><span class="badge badge-secondary">Pending</span></td>
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
              <div class="form-group">
                <label for="shikem_api_url">Shikem API URL</label>
                <input type="url" class="form-control" id="shikem_api_url" name="shikem_api_url"
                       placeholder="https://shikem.com" required>
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

<script>
document.getElementById('shikem_connect_form')?.addEventListener('submit', function(e) {
  e.preventDefault();
  // TODO: Implement connection flow
  alert('Connection method not yet implemented');
});
</script>
