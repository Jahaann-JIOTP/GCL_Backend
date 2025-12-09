<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Fetch data from the source API
$url = "http://43.204.118.114:6881/latestgcl1";
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
    "G1_U16"=> "Main Genset",
    
    
    
];

// Define the keys to fetch for energy data
$energyKeys = [
    "SIGNED_REAL_ENERGY_CONSUMPTION_KWH", " REACTIVE_ENERGY_IMPORT_KVARH", " ACTIVE_ENERGY_EXPORT_KWH",
    "APPARENT_ENERGY_EXPORT_KVAH", "REACTIVE_ENERGY_EXPORT_KVARH",
    "APPARENT_ENERGY_CONSUMPTION_KVAH","ActiveEnergy_DelmRec_Wh","ReactiveEnergy_DelmRec_VARh","ApparentEnergy_DelmRec_VAh",
    "ActiveEnergy_DelpRec_Wh", "ReactiveEnergy_DelpRec_VARh", "ApparentEnergy_DelpRec_VAh","APPARENT_ENERGY_KVAH"
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