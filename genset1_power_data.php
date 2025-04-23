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

// Get the meter ID from the query string
$meter = $_GET['meter'] ?? null;

// Define the meter-to-title mapping
$meterTitles = [
    
   
    
    "G1_U17" => "Genset 1",
    "G1_U18" => "Genset 2",
    "G1_U19" => "Genset 3",
   "G1_U16" => "Main Genset",
    
   
];

// Define the keys to fetch for each meter
$meterKeys = [
  "HARMONICS_I1_THD", "HARMONICS_I2_THD","HARMONICS_I3_THD","HARMONICS_V1_THD","HARMONICS_V2_THD","HARMONICS_V3_THD",
   "ACTIVE_POWER_P1_KW","ACTIVE_POWER_P2_KW","ACTIVE_POWER_P3_KW","ACTIVE_POWER_TOTAL_KW","REACTIVE_POWER_Q1_KVAR","REACTIVE_POWER_Q2_KVAR",
   "REACTIVE_POWER_Q3_KVAR","REACTIVE_POWER_TOTAL_KVAR","APPARENT_POWER_S1_KVA","APPARENT_POWER_S2_KVA","APPARENT_POWER_S3_KVA","APPARENT_POWER_TOTAL_KVA",
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
    
        // New condition for G2_U20 for specific apparent power keys
        $apparentPowerKeys = [
            "APPARENT_POWER_TOTAL_KVA",
            "APPARENT_POWER_S1_KVA",
            "APPARENT_POWER_S2_KVA",
            "APPARENT_POWER_S3_KVA"
        ];
    
        if ($meter === "G2_U20" && in_array($key, $apparentPowerKeys)) {
            $meterData[$key] = round($meterData[$key] / 1000, 2);
        }
    }
    

    $response = [
        "authorized" => true,
        "meter" => $meterData
    ];
} else {
    // If no meter is specified or invalid meter, return an error
    $response = [
        "authorized" => false,
        "error" => "Invalid or missing meter ID. Please specify a valid meter ID."
    ];
}

// Output the response as JSON
echo json_encode($response, JSON_PRETTY_PRINT);
exit();
