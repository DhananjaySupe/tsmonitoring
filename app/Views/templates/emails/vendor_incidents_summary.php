<?php
/**
 * Email body: Daily Incidents Summary for vendor.
 * Variables: $vendorName, $reportDate, $rows, $totalIncidents, $statusCounts
 */
if (!isset($vendorName)) {
    $vendorName = '';
}
if (!isset($reportDate)) {
    $reportDate = '';
}
if (!isset($rows) || !is_array($rows)) {
    $rows = [];
}
if (!isset($totalIncidents)) {
    $totalIncidents = 0;
}
if (!isset($statusCounts) || !is_array($statusCounts)) {
    $statusCounts = [];
}
$statusOrder = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'REOPENED'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        table { border-collapse: collapse; margin: 12px 0; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
        th { background-color: #f0f0f0; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <p>Dear <?= esc($vendorName) ?>,</p>
    <p>Please find below the daily incidents summary for <strong><?= esc($reportDate) ?></strong>.</p>
    <p><strong>By status</strong></p>
    <table>
        <tr><th>Status</th><th>Count</th></tr>
        <?php foreach ($statusOrder as $status): ?>
        <tr><td><?= esc($status) ?></td><td><?= (int) ($statusCounts[$status] ?? 0) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <p><strong>By asset type</strong></p>
    <table>
        <tr><th>Asset Type</th><th>Incident Count</th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= esc($r['asset_type_name'] ?? '') ?></td>
            <td><?= (int) ($r['incident_count'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row"><td>Total</td><td><?= (int) $totalIncidents ?></td></tr>
    </table>
    <p>A PDF copy is attached to this email.</p>
    <p>— Tentage and Sanitation Monitoring</p>
</body>
</html>
