<?php
// Function to fetch data from URL using cURL
function fetchUrlData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    if ($response === false) {
        die("Error fetching data: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

// Function to calculate elapsed time
function timeElapsed($lastOccurrence) {
    $timezone = new DateTimeZone('Asia/Karachi'); // Adjust to your timezone
    $current_time = new DateTime('now', $timezone);
    $occurrence_time = new DateTime($lastOccurrence, $timezone);
    $interval = $current_time->diff($occurrence_time);

    if ($interval->days > 0) {
        return $interval->format('%a days %h hours %i minutes %s seconds ago');
    } elseif ($interval->h > 0) {
        return $interval->format('%h hours %i minutes %s seconds ago');
    } elseif ($interval->i > 0) {
        return $interval->format('%i minutes %s seconds ago');
    } else {
        return $interval->format('%s seconds ago');
    }
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Fetch data from the URL
$url = "http://13.234.241.103:1880/latestgcl1";
$url_data = fetchUrlData($url);

if (!$url_data) {
    die("Error: No data fetched from URL.");
}

// Database connection
$con = mysqli_connect("15.206.128.214", "jahaann", "Jahaann#321", "gcl");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$meter_to_url_mapping = [
    "Solar 1 Low Voltage" => "G2_U20_VOLTAGE_L_L_AVG_V",
    "Solar 1 High Current" => "G2_U20_CURRENT_TOTAL_A", 
    "Solar 1 High Voltage" => "G2_U20_VOLTAGE_L_L_AVG_V",    
    "Solar 2 Low Voltage" => "U_27_VOLTAGE_L_L_AVG_V", 
    "Solar 2 High Current" => "U_27_CURRENT_AVG_A",  
    "Solar 2 High Voltage" => "U_27_VOLTAGE_L_L_AVG_V",  
    "Tranformer 1 Low Voltage" => "U_24_VOLTAGE_L_L_AVG_V",   
    "Tranformer 1 High Current" => "U_24_CURRENT_TOTAL_A", 
    "Tranformer 1 High Voltage" => "U_24_VOLTAGE_L_L_AVG_V",
    "Tranformer 2 Low Voltage" => "U_25_VOLTAGE_L_L_AVG_V",   
    "Tranformer 2 High Current" => "U_25_CURRENT_TOTAL_A", 
    "Tranformer 2 High Voltage" => "U_25_VOLTAGE_L_L_AVG_V",
    "Air Compressors-1 Low Voltage" => "U_17_VOLTAGE_L_L_AVG_V",
    "Air Compressors-1 High Current" => "U_17_CURRENT_TOTAL_A",
    "Air Compressors-1 High Voltage" => "U_17_VOLTAGE_L_L_AVG_V",
    "Auto Packing Low Voltage" => "U_5_VOLTAGE_L_L_AVG_V",
    "Auto Packing High Current" => "U_5_CURRENT_TOTAL_A",
    "Auto Packing High Voltage" => "U_5_VOLTAGE_L_L_AVG_V",
    "Ball Mills-1 Low Voltage" => "U_23_VOLTAGE_L_L_AVG_V",
    "Ball Mills-1 High Current" => "U_23_CURRENT_TOTAL_A",
    "Ball Mills-1 High Voltage" => "U_23_VOLTAGE_L_L_AVG_V",
    "Ball Mills-2 Low Voltage" => "U_15_VOLTAGE_L_L_AVG_V",
    "Ball Mills-2 High Current" => "U_15_CURRENT_TOTAL_A",
    "Ball Mills-2 High Voltage" => "U_15_VOLTAGE_L_L_AVG_V",
    "Ball Mills-4 Low Voltage" => "U_2_VOLTAGE_L_L_AVG_V",
    "Ball Mills-4 High Current" => "U_2_CURRENT_TOTAL_A",
    "Ball Mills-4 High Voltage" => "U_2_VOLTAGE_L_L_AVG_V",
    "Belt 200 Feeding Low Voltage" => "U_11_VOLTAGE_L_L_AVG_V",
    "Belt 200 Feeding High Current" => "U_11_CURRENT_TOTAL_A",
    "Belt 200 Feeding High Voltage" => "U_11_VOLTAGE_L_L_AVG_V",
    "Belt 300 Feeding Low Voltage" => "U_10_VOLTAGE_L_L_AVG_V",
    "Belt 300 Feeding High Current" => "U_10_CURRENT_TOTAL_A",
    "Belt 300 Feeding High Voltage" => "U_10_VOLTAGE_L_L_AVG_V",
    "Colony D.B Low Voltage" => "U_7_VOLTAGE_L_L_AVG_V",
    "Colony D.B High Current" => "U_7_CURRENT_TOTAL_A",
    "Colony D.B High Voltage" => "U_7_VOLTAGE_L_L_AVG_V",
    "DPM-2 Low Voltage" => "U_6_VOLTAGE_L_L_AVG_V",
    "DPM-2 High Current" => "U_6_CURRENT_TOTAL_A",
    "DPM-2 High Voltage" => "U_6_VOLTAGE_L_L_AVG_V",
    "Glaze Line-1 Low Voltage" => "U_12_VOLTAGE_L_L_AVG_V",
    "Glaze Line-1 High Current" => "U_12_CURRENT_TOTAL_A",
    "Glaze Line-1 High Voltage" => "U_12_VOLTAGE_L_L_AVG_V",
    "Glaze Line-2 Low Voltage" => "U_4_VOLTAGE_L_L_AVG_V",
    "Glaze Line-2 High Current" => "U_4_CURRENT_TOTAL_A",
    "Glaze Line-2 High Voltage" => "U_4_VOLTAGE_L_L_AVG_V",
    "Glaze Ball Mill Low Voltage" => "U_20_VOLTAGE_L_L_AVG_V",
    "Glaze Ball Mill High Current" => "U_20_CURRENT_TOTAL_A",
    "Glaze Ball Mill High Voltage" => "U_20_VOLTAGE_L_L_AVG_V",
    "Kiln Blower Fan - (R.V.E) Low Voltage" => "U_9_VOLTAGE_L_L_AVG_V",
    "Kiln Blower Fan - (R.V.E) High Current" => "U_9_CURRENT_TOTAL_A",
    "Kiln Blower Fan - (R.V.E) High Voltage" => "U_9_VOLTAGE_L_L_AVG_V",
    "Kiln Loading Machine Low Voltage" => "U_19_VOLTAGE_L_L_AVG_V",
    "Kiln Loading Machine High Current" => "U_19_CURRENT_TOTAL_A",
    "Kiln Loading Machine High Voltage" => "U_19_VOLTAGE_L_L_AVG_V",
    "Laboratory Low Voltage" => "U_16_VOLTAGE_L_L_AVG_V",
    "Laboratory High Current" => "U_16_CURRENT_TOTAL_A",
    "Laboratory High Voltage" => "U_16_VOLTAGE_L_L_AVG_V",
    "Light D.B # 01 Low Voltage" => "U_18_VOLTAGE_L_L_AVG_V",
    "Light D.B # 01 High Current" => "U_18_CURRENT_TOTAL_A",
    "Light D.B # 01 High Voltage" => "U_18_VOLTAGE_L_L_AVG_V",
    "Light D.B # 02 Low Voltage" => "U_8_VOLTAGE_L_L_AVG_V",
    "Light D.B # 02 High Current" => "U_8_CURRENT_TOTAL_A",
    "Light D.B # 02 High Voltage" => "U_8_VOLTAGE_L_L_AVG_V",
    "Lighting (Plant) Low Voltage" => "U_22_VOLTAGE_L_L_AVG_V",
    "Lighting (Plant) High Current" => "U_22_CURRENT_TOTAL_A",
    "Lighting (Plant) High Voltage" => "U_22_VOLTAGE_L_L_AVG_V",
    "Masjid Low Voltage" => "U_3_VOLTAGE_L_L_AVG_V",
    "Masjid High Current" => "U_3_CURRENT_TOTAL_A",
    "Masjid High Voltage" => "U_3_VOLTAGE_L_L_AVG_V",
    "Prekiln Low Voltage" => "U_13_VOLTAGE_L_L_AVG_V",
    "Prekiln High Current" => "U_13_CURRENT_TOTAL_A",
    "Prekiln High Voltage" => "U_13_VOLTAGE_L_L_AVG_V",
    "Press PH4300 Low Voltage" => "U_21_VOLTAGE_L_L_AVG_V",
    "Press PH4300 High Current" => "U_21_CURRENT_TOTAL_A",
    "Press PH4300 High Voltage" => "U_21_VOLTAGE_L_L_AVG_V",
    "Layer Dryer Low Voltage" => "U_14_VOLTAGE_L_L_AVG_V",
    "Layer Dryer High Current" => "U_14_CURRENT_TOTAL_A",
    "Layer Dryer High Voltage" => "U_14_VOLTAGE_L_L_AVG_V",
    "Polishing Line 5 Low Voltage" => "G1_U2_VOLTAGE_L_L_AVG_V",
    "Polishing Line 5 High Current" => "G1_U2_CURRENT_TOTAL_A",
    "Polishing Line 5 High Voltage" => "G1_U2_VOLTAGE_L_L_AVG_V",
    "Polishing Line 6 Low Voltage" => "G1_U3_VOLTAGE_L_L_AVG_V",
    "Polishing Line 6 High Current" => "G1_U3_CURRENT_TOTAL_A",
    "Polishing Line 6 High Voltage" => "G1_U3_VOLTAGE_L_L_AVG_V",
    "Glaze Ball Mill  13500L-2 Low Voltage" => "G1_U4_VOLTAGE_L_L_AVG_V",
    "Glaze Ball Mill  13500L-2 High Current" => "G1_U4_CURRENT_TOTAL_A",
    "Glaze Ball Mill  13500L-2 High Voltage" => "G1_U4_VOLTAGE_L_L_AVG_V",
    "Polishing Line 7 Low Voltage" => "G1_U5_VOLTAGE_L_L_AVG_V",
    "Polishing Line 7 High Current" => "G1_U5_CURRENT_TOTAL_A",
    "Polishing Line 7 High Voltage" => "G1_U5_VOLTAGE_L_L_AVG_V",
    "Air Compressor-2 Low Voltage" => "G1_U6_VOLTAGE_L_L_AVG_V",
    "Air Compressor-2 High Current" => "G1_U6_CURRENT_TOTAL_A",
    "Air Compressor-2 High Voltage" => "G1_U6_VOLTAGE_L_L_AVG_V",
    "Glaze Ball Mill 9500L-3 Low Voltage" => "G1_U7_VOLTAGE_L_L_AVG_V",
    "Glaze Ball Mill 9500L-3 High Current" => "G1_U7_CURRENT_TOTAL_A",
    "Glaze Ball Mill 9500L-3 High Voltage" => "G1_U7_VOLTAGE_L_L_AVG_V",
    "G1_U8 Low Voltage" => "G1_U8_VOLTAGE_L_L_AVG_V",
    "G1_U8 High Current" => "G1_U8_CURRENT_TOTAL_A",
    "G1_U8 High Voltage" => "G1_U8_VOLTAGE_L_L_AVG_V",
    "G1_U10 Low Voltage" => "G1_U10_VOLTAGE_L_L_AVG_V",
    "G1_U10 High Current" => "G1_U10_CURRENT_TOTAL_A",
    "G1_U10 High Voltage" => "G1_U10_VOLTAGE_L_L_AVG_V",
    "5 Layer Dryer Low Voltage" => "G1_U11_VOLTAGE_L_L_AVG_V",
    "5 Layer Dryer High Current" => "G1_U11_CURRENT_TOTAL_A",
    "5 Layer Dryer High Voltage" => "G1_U11_VOLTAGE_L_L_AVG_V",
    "5 Layer Dryer Unloading Machine Low Voltage" => "G1_U12_VOLTAGE_L_L_AVG_V",
    "5 Layer Dryer Unloading Machine High Current" => "G1_U12_CURRENT_TOTAL_A",
    "5 Layer Dryer Unloading Machine High Voltage" => "G1_U12_VOLTAGE_L_L_AVG_V",
    "Rental Genset Low Voltage" => "G1_U13_VOLTAGE_L_L_AVG_V",
    "Rental Genset High Current" => "G1_U13_CURRENT_TOTAL_A",
    "Rental Genset High Voltage" => "G1_U13_VOLTAGE_L_L_AVG_V",
    "Water Treatment Area Low Voltage" => "G1_U14_VOLTAGE_L_L_AVG_V",
    "Water Treatment Area High Current" => "G1_U14_CURRENT_TOTAL_A",
    "Water Treatment Area High Voltage" => "G1_U14_VOLTAGE_L_L_AVG_V",
    "G1_U15 Low Voltage" => "G1_U15_VOLTAGE_L_L_AVG_V",
    "G1_U15 High Current" => "G1_U15_CURRENT_TOTAL_A",
    "G1_U15 High Voltage" => "G1_U15_VOLTAGE_L_L_AVG_V",
    "G1_U16 Low Voltage" => "G1_U16_VOLTAGE_L_L_AVG_V",
    "G1_U16 High Current" => "G1_U16_CURRENT_TOTAL_A",
    "G1_U16 High Voltage" => "G1_U16_VOLTAGE_L_L_AVG_V",
    "Press PH  4300/1750-1 Low Voltage" => "G2_U2_VOLTAGE_L_L_AVG_V",
    "Press PH  4300/1750-1 High Current" => "G2_U2_CURRENT_TOTAL_A",
    "Press PH  4300/1750-1 High Voltage" => "G2_U2_VOLTAGE_L_L_AVG_V",
    "Ball Mills -3 Low Voltage" => "G2_U3_VOLTAGE_L_L_AVG_V",
    "Ball Mills -3 High Current" => "G2_U3_CURRENT_TOTAL_A",
    "Ball Mills -3 High Voltage" => "G2_U3_VOLTAGE_L_L_AVG_V",
    "Hard Materials Low Voltage" => "G2_U4_VOLTAGE_L_L_AVG_V",
    "Hard Materials High Current" => "G2_U4_CURRENT_TOTAL_A",
    "Hard Materials High Voltage" => "G2_U4_VOLTAGE_L_L_AVG_V",
    "Polishing Line-1 Low Voltage" => "G2_U7_VOLTAGE_L_L_AVG_V",
    "Polishing Line-1 High Current" => "G2_U7_CURRENT_TOTAL_A",
    "Polishing Line-1 High Voltage" => "G2_U7_VOLTAGE_L_L_AVG_V",
    "Polishing Line-2 Low Voltage" => "G2_U8_VOLTAGE_L_L_AVG_V",
    "Polishing Line-2 High Current" => "G2_U8_CURRENT_TOTAL_A",
    "Polishing Line-2 High Voltage" => "G2_U8_VOLTAGE_L_L_AVG_V",
    "Fan for Spray Dryer Low Voltage" => "G2_U9_VOLTAGE_L_L_AVG_V",
    "Fan for Spray Dryer High Current" => "G2_U9_CURRENT_TOTAL_A",
    "Fan for Spray Dryer High Voltage" => "G2_U9_VOLTAGE_L_L_AVG_V",
    "Slip Piston Pumps & Transfer Tank Low Voltage" => "G2_U10_VOLTAGE_L_L_AVG_V",
    "Slip Piston Pumps & Transfer Tank High Current" => "G2_U10_CURRENT_TOTAL_A",
    "Slip Piston Pumps & Transfer Tank High Voltage" => "G2_U10_VOLTAGE_L_L_AVG_V",
    "Glaze Tank-1 Low Voltage" => "G2_U11_VOLTAGE_L_L_AVG_V",
    "Glaze Tank-1 High Current" => "G2_U11_CURRENT_TOTAL_A",
    "Glaze Tank-1 High Voltage" => "G2_U11_VOLTAGE_L_L_AVG_V",
    "Coal Stove & Coal Conveyer Low Voltage" => "G2_U12_VOLTAGE_L_L_AVG_V",
    "Coal Stove & Coal Conveyer High Current" => "G2_U12_CURRENT_TOTAL_A",
    "Coal Stove & Coal Conveyer High Voltage" => "G2_U12_VOLTAGE_L_L_AVG_V",
    "ST Motor & Iron Remove Low Voltage" => "G2_U13_VOLTAGE_L_L_AVG_V",
    "ST Motor & Iron Remove High Current" => "G2_U13_CURRENT_TOTAL_A",
    "ST Motor & Iron Remove High Voltage" => "G2_U13_VOLTAGE_L_L_AVG_V",
    "Polishing Line -3 Low Voltage" => "G2_U14_VOLTAGE_L_L_AVG_V",
    "Polishing Line -3 High Current" => "G2_U14_CURRENT_TOTAL_A",
    "Polishing Line -3 High Voltage" => "G2_U14_VOLTAGE_L_L_AVG_V",
    "Polishing Line -4 Low Voltage" => "G2_U15_VOLTAGE_L_L_AVG_V",
    "Polishing Line -4 High Current" => "G2_U15_CURRENT_TOTAL_A",
    "Polishing Line -4 High Voltage" => "G2_U15_VOLTAGE_L_L_AVG_V",
    "Belt 100 Feeding to BM500 Low Voltage" => "G2_U16_VOLTAGE_L_L_AVG_V",
    "Belt 100 Feeding to BM500 High Current" => "G2_U16_CURRENT_TOTAL_A",
    "Belt 100 Feeding to BM500 High Voltage" => "G2_U16_VOLTAGE_L_L_AVG_V",
    "No Combustion System Low Voltage" => "G2_U17_VOLTAGE_L_L_AVG_V",
    "No Combustion System High Current" => "G2_U17_CURRENT_TOTAL_A",
    "No Combustion System High Voltage" => "G2_U17_VOLTAGE_L_L_AVG_V",
    "Digital Printing Machine Low Voltage" => "G2_U18_VOLTAGE_L_L_AVG_V",
    "Digital Printing Machine High Current" => "G2_U18_CURRENT_TOTAL_A",
    "Digital Printing Machine High Voltage" => "G2_U18_VOLTAGE_L_L_AVG_V",
    "G2_U5 Low Voltage" => "G2_U5_VOLTAGE_L_L_AVG_V",
    "G2_U5 High Current" => "G2_U5_CURRENT_TOTAL_A",
    " G2_U5 High Voltage" => "G2_U5_VOLTAGE_L_L_AVG_V",
    "Air Compressor 3 Low Voltage" => "G2_U19_VOLTAGE_L_L_AVG_V",
    "Air Compressor 3 High Current" => "G2_U19_CURRENT_TOTAL_A",
    "Air Compressor 3 High Voltage" => "G2_U19_VOLTAGE_L_L_AVG_V",
    "Air Compressor 4 Low Voltage" => "G2_U6_VOLTAGE_L_L_AVG_V",
    "Air Compressor 4 High Current" => "G2_U6_CURRENT_TOTAL_A",
    "Air Compressor 4 High Voltage" => "G2_U6_VOLTAGE_L_L_AVG_V",
];

// Define alarm conditions dynamically
$alarm_conditions = [
    'Low Voltage' => function($db_value, $url_value) { 
        return $url_value != 0 && $url_value <= $db_value; 
    },
    'High Voltage' => function($db_value, $url_value) { 
        return $url_value != 0 && $url_value >= $db_value; 
    },
    'High Current' => function($db_value, $url_value) { 
        return $url_value != 0 && $url_value >= $db_value; 
    },
];


// Fetch meter data
$sql_meter_data = "SELECT * FROM meterdata";
$meter_result = mysqli_query($con, $sql_meter_data);

$meter_data = [];
if ($meter_result) {
    while ($row = mysqli_fetch_assoc($meter_result)) {
        $meter_data[] = $row;
    }
} else {
    die("Error fetching meter data: " . mysqli_error($con));
}

// Process alarms
// Process alarms
foreach ($meter_data as $db_row) {
    $meter_id = $db_row['Source'];
    $status = $db_row['Status'];
    $db_value = (float)$db_row['Value'];

    // Get the corresponding URL key for the current meter and status
    $url_key = $meter_to_url_mapping["$meter_id $status"] ?? null;
    if (!$url_key || !isset($url_data[$url_key])) {
        continue; // Skip if mapping or URL value is missing
    }

    $url_value = (float)$url_data[$url_key];
    $is_condition_met = isset($alarm_conditions[$status]) && $alarm_conditions[$status]($db_value, $url_value);

    // Fetch existing alarm for this meter and status
    $check_query = "
        SELECT * FROM alarms 
        WHERE Source = '$meter_id' 
        AND Status = '$status'
        ORDER BY Time DESC
        LIMIT 1
    ";
    $check_result = mysqli_query($con, $check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        // Alarm exists for this meter and status
        $existing_alarm = mysqli_fetch_assoc($check_result);

        if ($is_condition_met) {
            // If the condition is met, check if this is a new alarm (new value or time)
            if ($existing_alarm['db_value'] != $db_value || strtotime($existing_alarm['Time']) < strtotime('-1 second')) {
                // Insert a new alarm row for the new value
                $insert_query = "
                    INSERT INTO alarms (Source, Status, Value, Time, db_value, url_value, status1, alarm_count, end_time)
                    VALUES ('$meter_id', '$status', '$db_value', NOW(), '$db_value', '$url_value', '$status', 1, NULL)
                ";
                mysqli_query($con, $insert_query);
            } else {
                // Update the existing alarm (but no new line)
                $update_query = "
                    UPDATE alarms 
                    SET url_value = '$url_value', 
                        db_value = '$db_value'
                    WHERE id = {$existing_alarm['id']}
                ";
                mysqli_query($con, $update_query);
            }
        } else {
            // If the condition is no longer met, close the existing alarm
            if ($existing_alarm['end_time'] === null) {
                $update_query = "
                    UPDATE alarms 
                    SET end_time = NOW()
                    WHERE id = {$existing_alarm['id']}
                ";
                mysqli_query($con, $update_query);
            }
        }
    } else {
        // No existing alarm: Create a new one if the condition is met
        if ($is_condition_met) {
            $insert_query = "
                INSERT INTO alarms (Source, Status, Value, Time, db_value, url_value, status1, alarm_count, end_time)
                VALUES ('$meter_id', '$status', '$db_value', NOW(), '$db_value', '$url_value', '$status', 1, NULL)
            ";
            mysqli_query($con, $insert_query);
        }
    }
}

// Fetch and process alarms for response
$sql_fetch_alarms = "
    SELECT id, Source, Status, Value, Time, db_value, url_value, status1, alarm_count
    FROM alarms
    ORDER BY Time DESC
";
$result_alarms = mysqli_query($con, $sql_fetch_alarms);

$alarms = [];
if ($result_alarms) {
    while ($alarm = mysqli_fetch_assoc($result_alarms)) {
        // Add 'state' and 'last_occurrence' based on Time field
        $alarm['state'] = !empty($alarm['Time']) ? timeElapsed($alarm['Time']) : "N/A";
        $alarm['last_occurrence'] = !empty($alarm['Time']) ? date('Y-m-d H:i:s', strtotime($alarm['Time'])) : "N/A";

        // Set 'end_time' dynamically
        if (!empty($alarm['end_time'])) {
            $alarm['end_time'] = date('Y-m-d H:i:s', strtotime($alarm['end_time']));
        } else {
            $alarm['end_time'] = "Condition Active";
        }

        $alarms[] = $alarm;
    }
} else {
    die("Error fetching alarms from database: " . mysqli_error($con));
}

// Return alarms as JSON
echo json_encode(['alarms' => $alarms]);

mysqli_close($con);
?>
