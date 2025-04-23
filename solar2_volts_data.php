<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Fetch data from the source API
$url = "http://13.234.241.103:1880/latestgcl1";
$json = file_get_contents($url);

if ($json === false) {
    echo json_encode(["error" => "Unable to fetch data from source API."]);
    exit();
}

$msg = json_decode($json, true);

if ($msg === null) {
    echo json_encode(["error" => "Invalid JSON response from source API."]);
    exit();
}

// Get the meter ID from the query string (if provided)
$meter = $_GET['meter'] ?? null;

// Define the meter-to-title mapping
$meterTitles = [
   
    "U_27" => "Solar 2",
   






];

// Define the keys to fetch for each meter
$meterKeys = [
    "VOLTAGE_LINE_1_V",
    "VOLTAGE_LINE_2_V",
    "VOLTAGE_LINE_3_V",
    "VOLTAGE_L_N_AVG_V",
    "VOLTAGE_LINE_1_2_V",
    "VOLTAGE_LINE_2_3_V",
    "VOLTAGE_LINE_3_1_V",
    "VOLTAGE_L_L_AVG_V",
    "CURRENT_LINE_1_A",
    "CURRENT_LINE_2_A",
    "CURRENT_LINE_3_A",
    "CURRENT_AVG_A",
    "CURRENT_AVG_A",
    "ACTIVE_POWER_P1_KW",
    "ACTIVE_POWER_P2_KW",
    "ACTIVE_POWER_P3_KW",
    "ACTIVE_POWER_TOTAL_KW",
    "REACTIVE_POWER_TOTAL_KVAR",
    "APPARENT_POWER_TOTAL_KVA",
    "FREQUENCY_F",
    "POWER_FACTOR_TOTAL",
    "POWER_FACTOR_PF1",
    "POWER_FACTOR_PF2",
    "POWER_FACTOR_PF3",




];

// Check if the meter parameter is provided and valid
if ($meter && isset($meterTitles[$meter])) {
    $meterData = [
        "meter_id" => $meter,
        "meter_title" => $meterTitles[$meter]
    ];

    foreach ($meterKeys as $key) {
        $fullKey = $meter . "_" . $key;
        $meterData[$key] = isset($msg[$fullKey]) ? round($msg[$fullKey], 2) : 0;
    
        // Existing condition for U_24 and U_25
        if (in_array($meter, ["U_24", "U_25"]) && strpos($key, "POWER") !== false) {
            $meterData[$key] = round($meterData[$key] / 1000, 2);
        }
    
        // New condition specifically for G2_U20 and APPARENT_POWER_TOTAL_KVA
        if ($meter === "G2_U20" && $key === "APPARENT_POWER_TOTAL_KVA") {
            $meterData[$key] = round($meterData[$key] / 1000, 2);
        }
    }
    

    $data = [
        "authorized" => true,
        "meter" => $meterData
    ];
} else {
    // If no meter is specified or invalid meter, return an error
    $data = [
        "authorized" => false,
        "error" => "Invalid or missing meter ID. Please specify a valid meter ID."
    ];
}

// Output the data as JSON
echo json_encode($data, JSON_PRETTY_PRINT);
exit();
