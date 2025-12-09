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
    "G2_U20" => "Solar 1",
    "U_27" => "Solar 2",
    "U_24" => "Trafo1",
    "U_25" => "Trafo2",
    "U_2" => "Ball Mill 4",
    "U_3" => "Masjid",
    "U_4" => "glaze line 2",
    "U_5" => "Sorting & Packing Line",
    "U_6" => "Digital Printing Machine",
    "U_7" => "Colony D.B",
    "U_8" => "Light D.B # 02",
    "U_9" => "Kiln Blower Fan - (R.V.E)",
    "U_10" => "Belt 300 Feeding to Press PH4300",
    "U_11" => "Belt 200 Feeding to Silo",
    "U_12" => "Glaze Line 1",
    "U_13" => "Perklin + Kiln",
    "U_14" => "Layer Dryer",
    "U_15" => "Spare 01",
    "U_16" => "Laboratory",
    "U_17" => "Air Compressor 1",
    "U_18" => "Light D.B 1",
    "U_19" => "Kiln Loading Machine with Compensation",
    "U_20" => "Glaze Ball Mill 13500L-2/9500L-1",
    "U_21" => "Press PH 4300/1750-2",
    "U_22" => "Lightning Plant",
    "U_23" => "Ball Mill 1",
    "G1_U2" => "Polishing Line 5",
    "G1_U3" => "Polishing Line 6",
    "G1_U4" => "Glaze Ball Mill 13500L-2",
    "G1_U5" => "Polishing Line 7",
    "G1_U6" => "Air Compressor 2",
    "G1_U7" => "Glaze Ball Mill 9500L-3",
    "G1_U8" => "Spare 02",
    "G1_U10" => "Spare 04",
    "G1_U11" => "5 Layer Dryer",
    "G1_U12" => "5 Layer Dryer Unloading Machine",
    "G1_U13" => "Rental Genset",
    "G1_U14" => "Water Treatment Area",
    "G1_U15" => "Spare 05",
    "G1_U16" => "Main Genset",
    "G1_U17" => "Genset 1",
    "G1_U18" => "Genset 2",
    "G1_U19" => "Genset 3",
    "G2_U2" => "Press PH 4300/1750-1",
    "G2_U3" => "Ball Mills 03",
    "G2_U4" => "Hard Material",
    "G2_U7" => "Polishing Line 1",
    "G2_U8" => "Polishing Line 2",
    "G2_U9" => "Fan for Spray Dryer",
    "G2_U10" => "Slip Piston Pumps & Transfer Tanks",
    "G2_U12" => "Coal Stove & Coal Conveyor",
    "G2_U13" => "ST Motor & Iron Remove",
    "G2_U14" => "Polishing Line 3",
    "G2_U11" => "Gaze Tank-1",
    "G2_U15" => "Polishing Line 4",
    "G2_U16" => "Belt 100 Feeding to BM500",
    "G2_U17" => "No Combustion System",
    "G2_U18" => "Digital Printing Machine",
    "G2_U5" => "Spare 07",
    "G2_U19" => "Air Compressor 3",
    "G2_U6" => "Air Compressor 4",
];

// Define the keys to fetch for each meter
$meterKeys = [
  "HARMONICS_I1_THD", "HARMONICS_I2_THD","HARMONICS_I3_THD","HARMONICS_V1_THD","HARMONICS_V2_THD","HARMONICS_V3_THD", "ACTIVE_POWER_P1_KW","ACTIVE_POWER_P2_KW","ACTIVE_POWER_P3_KW","ACTIVE_POWER_TOTAL_KW","REACTIVE_POWER_Q1_KVAR","REACTIVE_POWER_Q2_KVAR","REACTIVE_POWER_Q3_KVAR","REACTIVE_POWER_TOTAL_KVAR","APPARENT_POWER_S1_KVA","APPARENT_POWER_S2_KVA","APPARENT_POWER_S3_KVA","APPARENT_POWER_TOTAL_KVA",
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
            $meterData[$key] = round($meterData[$key], 2);
        }
    
        // New condition for G2_U20 for specific apparent power keys
        $apparentPowerKeys = [
            "APPARENT_POWER_TOTAL_KVA",
            "APPARENT_POWER_S1_KVA",
            "APPARENT_POWER_S2_KVA",
            "APPARENT_POWER_S3_KVA"
        ];
    
        if ($meter === "G2_U20" && in_array($key, $apparentPowerKeys)) {
            $meterData[$key] = round($meterData[$key], 2);
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
