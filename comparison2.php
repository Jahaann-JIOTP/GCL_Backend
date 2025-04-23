<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set("Asia/Karachi");

// Database connection
$con = mysqli_connect("localhost", "jahaann", "Jahaann#321", "gcl");

if (!$con) {
    echo json_encode(["error" => "Database connection failed: " . mysqli_connect_error()]);
    exit();
}

// Get filter parameter (default to 'today')
$filter = $_GET['filter'] ?? 'today';

// Calculate start and end dates based on the filter
$today = date('Y-m-d');
switch (strtolower($filter)) {
    case 'today':
        $start_date = "$today 00:00:00";
        $end_date = "$today 23:59:59";
        break;
    case 'last7days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day')); // Exclude today
        break;
    case 'last15days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-15 days'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day')); // Exclude today
        break;
    case 'last30days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day')); // Exclude today
        break;
    default:
        echo json_encode(["error" => "Invalid filter provided."]);
        exit();
}

// Sync new alarms from the `alarms` table to the `recentalarms` table
$sync_query = "
    INSERT INTO recentalarms (meter, option_selected, db_value, url_value, status, start_time, created_at)
    SELECT 
        Source AS meter,
        Status AS option_selected,
        db_value,
        url_value,
        status1 AS status,
        Time AS start_time,
        NOW() AS created_at
    FROM alarms
    WHERE Time BETWEEN '$start_date' AND '$end_date'
    AND NOT EXISTS (
        SELECT 1 
        FROM recentalarms 
        WHERE alarms.Source = recentalarms.meter 
        AND alarms.Time = recentalarms.start_time
    )
";

$sync_result = mysqli_query($con, $sync_query);
if (!$sync_result) {
    error_log("Sync Error: " . mysqli_error($con));
}

// Update `end_time` for alarms that have ended
$update_end_time_query = "
    UPDATE recentalarms ra
    LEFT JOIN alarms a ON ra.meter = a.Source AND ra.start_time = a.Time
    SET 
        ra.end_time = a.end_time,  -- Fetch the end_time from alarms table
        ra.total_duration = TIMESTAMPDIFF(SECOND, ra.start_time, a.end_time) -- Calculate duration based on end_time from alarms
    WHERE ra.end_time IS NULL 
      AND a.end_time IS NOT NULL;  -- Only update if there is an end_time in alarms
";

$update_result = mysqli_query($con, $update_end_time_query);
if (!$update_result) {
    error_log("End Time Update Error: " . mysqli_error($con));
}

// Fetch the results for recent alarms
$recentalarms_query = "
    SELECT 
        ra.id, -- Use the table alias 'ra' for recentalarms
        ra.meter, 
        ra.option_selected, 
        ra.db_value, 
        ra.url_value, 
        ra.status, 
        ra.start_time, 
        CASE 
            WHEN ra.end_time IS NULL THEN '....' 
            ELSE DATE_FORMAT(ra.end_time, '%Y-%m-%d %H:%i:%s') 
        END AS end_time, 
        CASE 
            WHEN ra.end_time IS NULL THEN TIMESTAMPDIFF(SECOND, ra.start_time, NOW()) 
            ELSE TIMESTAMPDIFF(SECOND, ra.start_time, ra.end_time) 
        END AS total_duration, 
        ra.created_at
    FROM recentalarms ra
    LEFT JOIN alarms a ON ra.meter = a.Source AND ra.start_time = a.Time
    WHERE ra.start_time BETWEEN '$start_date' AND '$end_date'
    ORDER BY ra.start_time DESC;
";

$recentalarms_result = mysqli_query($con, $recentalarms_query);

if (!$recentalarms_result) {
    echo json_encode(["error" => "Error fetching recent alarms: " . mysqli_error($con)]);
    exit();
}

// Process the results
$recentalarms = [];
while ($row = mysqli_fetch_assoc($recentalarms_result)) {
    $row['total_duration'] = sprintf(
        "%02d:%02d:%02d", 
        floor($row['total_duration'] / 3600), 
        floor(($row['total_duration'] % 3600) / 60), 
        $row['total_duration'] % 60
    );
    $recentalarms[] = $row;
}

// Return filtered alarms as JSON
echo json_encode(["recentalarms" => $recentalarms], JSON_PRETTY_PRINT);

mysqli_close($con);
?>
