<!DOCTYPE html>
<html>
<head>
    <title>System Activity Report</title>
</head>
<body>
<h1>System Activity Report</h1>
<p>Please find attached the latest system activity report.</p>
<p>The report contains:</p>
<ul>
    <li>Method call ratings</li>
    <li>Entity modification ratings</li>
    <li>User activity ratings</li>
</ul>
<p>Report generated at: {{ now()->format('Y-m-d H:i:s') }}</p>
<p>Data interval: {{ env('REPORT_INTERVAL_HOURS', 24) }} hours</p>
</body>
</html>
