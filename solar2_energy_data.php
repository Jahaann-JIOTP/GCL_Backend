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
    
    "U_27" => "Solar 2",
   
];

// Define the keys to fetch for energy data
$energyKeys = [
    "ACTIVE_ENERGY_IMPORT_KWH", "ACTIVE_ENERGY_EXPORT_KWH", "REACTIVE_ENERGY_IMPORT_KVARH",
    "REACTIVE_ENERGY_EXPORT_KVARH", "APPARENT_ENERGY_KVAH",
    "APPARENT_ENERGY_EXPORT_KVAH","APPARENT_ENERGY_IMPORT_KVAH","SIGNED_REAL_ENERGY_CONSUMPTION_KWH","APPARENT_ENERGY_CONSUMPTION_KVAH"
];

// Check if the meter parameter is provided and valid
if ($meter && isset($meterTitles[$meter])) {
    $meterData = [
        "meter_id" => $meter,
        "meter_title" => $meterTitles[$meter]
    ];

    foreach ($energyKeys as $key) {
        $fullKey = $meter . "_" . $key;
        $meterData[$key] = isset($msg[$fullKey]) ? round($msg[$fullKey], 2) : 0;
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
?>