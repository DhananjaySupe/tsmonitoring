<?php
/**
 * PDF document template: Daily Incidents Summary for vendor.
 * Variables: $vendorName, $reportDate, $rows, $totalIncidents, $statusCounts (OPEN, ASSIGNED, etc.)
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Incidents Summary – <?= esc($reportDate) ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; margin: 15px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #444; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background-color: #eee; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f5f5f5; }
        .footer { margin-top: 16px; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <h1>Daily Incidents Summary</h1>
    <div class="meta">
        <strong>Vendor:</strong> <?= esc($vendorName) ?><br>
        <strong>Report Date:</strong> <?= esc($reportDate) ?>
    </div>
    <h2 style="font-size: 13px; margin-top: 12px;">By status</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statusOrder as $status): ?>
            <tr>
                <td><?= esc($status) ?></td>
                <td><?= (int) ($statusCounts[$status] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h2 style="font-size: 13px; margin-top: 12px;">By asset type</h2>
    <table>
        <thead>
            <tr>
                <th>Asset Type</th>
                <th>Incident Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= esc($row['asset_type_name'] ?? '') ?></td>
                <td><?= (int) ($row['incident_count'] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>Total</td>
                <td><?= (int) $totalIncidents ?></td>
            </tr>
        </tbody>
    </table>
    <div class="footer">
        Generated on <?= date('Y-m-d H:i:s') ?> – Tentage and Sanitation Monitoring
    </div>
</body>
</html>
