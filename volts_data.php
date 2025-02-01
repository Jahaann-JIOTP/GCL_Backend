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
"G1_U16" => "Spare 06",
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
   "VOLTAGE_LINE_1_V", "VOLTAGE_LINE_2_V", "VOLTAGE_LINE_3_V", "VOLTAGE_L_N_AVG_V",
    "VOLTAGE_LINE_1_2_V", "VOLTAGE_LINE_2_3_V", "VOLTAGE_LINE_3_1_V",
    "VOLTAGE_L_L_AVG_V","CURRENT_LINE_1_A","CURRENT_LINE_2_A","CURRENT_LINE_3_A","CURRENT_AVG_A","ACTIVE_POWER_P1_KW","ACTIVE_POWER_P2_KW","ACTIVE_POWER_P3_KW","ACTIVE_POWER_TOTAL_KW","REACTIVE_POWER_TOTAL_KVAR","APPARENT_POWER_TOTAL_KVA","FREQUENCY_F","POWER_FACTOR_TOTAL","POWER_FACTOR_PF1","POWER_FACTOR_PF2","POWER_FACTOR_PF3",

  

    
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
