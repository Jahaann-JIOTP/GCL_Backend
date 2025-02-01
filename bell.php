<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");

// Database connection
$con = mysqli_connect("localhost", "root", "", "gcl");

// Check database connection
if (!$con) {
    $response = ["error" => "Database connection failed."];
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Fetch the latest 5 alarms from the `alarms` table, ordered by `Time` (newest first)
$alarms = [];
$sql_alarms = "SELECT * FROM alarms ORDER BY Time DESC LIMIT 5";
$result = mysqli_query($con, $sql_alarms);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Keep the original status
        $status = $row['status1'];

        // Check if `url_value` exceeds `db_value`
        if (floatval($row['url_value']) > floatval($row['db_value'])) {
            // Append " (Exceeded)" to the original status for clarity
            $status = $row['status1'] . "";
        }

        $alarms[] = [
            "id" => $row['id'],
            "source" => $row['Source'],
            "status" => $status, // Show the original status or appended "Exceeded"
            "value" => $row['Value'],
            "db_value" => $row['db_value'],
            "url_value" => $row['url_value'],
            "alarm_count" => $row['alarm_count'],
            "time" => $row['Time'],
            "end_time" => $row['end_time']
        ];
    }
} else {
    $response = ["error" => "Error fetching alarms: " . mysqli_error($con)];
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Check if there are any new alarms (`status` contains "Exceeded")
$new_alarm_triggered = false;
foreach ($alarms as $alarm) {
    if (strpos($alarm['status'], 'Exceeded') !== false) { // New alarm detected
        $new_alarm_triggered = true;
        break;
    }
}

// Set the bell status
$bell_status = $new_alarm_triggered ? "red" : "blue";

// Prepare the response
$response = [
    "bell_status" => $bell_status, // Red if new alarms, blue otherwise
    "alarms" => $alarms // Only the latest 5 alarms
];

// Set the content type to JSON and output the response
header("Content-Type: application/json");
echo json_encode($response, JSON_PRETTY_PRINT);

// Close the database connection
mysqli_close($con);
?>
