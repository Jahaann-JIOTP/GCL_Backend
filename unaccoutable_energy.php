<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_ActiveTags;
// $collection->createIndex(['timestamp' => 1]);

$meterIds = [
    "G2_U20", "U_27", "U_24", "U_25", "U_17", "U_5", "U_23", "U_15", "U_2", "U_11", "U_10", "U_7", "U_6",
    "U_12", "U_4", "U_20", "U_9", "U_19", "U_16", "U_18", "U_8", "U_22", "U_3", "U_13", "U_21", "U_14",
    "G1_U2", "G1_U3", "G1_U4", "G1_U5", "G1_U6", "G1_U7", "G1_U8", "G1_U10", "G1_U11", "G1_U12", "G1_U13",
    "G1_U14", "G1_U15", "G1_U16", "G1_U17", "G1_U18", "G1_U19", "G2_U2", "G2_U3", "G2_U4", "G2_U7", "G2_U8", 
    "G2_U9", "G2_U10", "G2_U11", "G2_U12", "G2_U13", "G2_U14", "G2_U15", "G2_U16", "G2_U17", "G2_U18", "G2_U5", 
    "G2_U19", "G2_U6"
];
$suffixes = ["ACTIVE_ENERGY_IMPORT_KWH"];

$solarKeys = ["G2_U20_ACTIVE_ENERGY_IMPORT_KWH", "U_27_ACTIVE_ENERGY_IMPORT_KWH"];
$transformerKeys = ["U_24_ACTIVE_ENERGY_IMPORT_KWH", "U_25_ACTIVE_ENERGY_IMPORT_KWH"];
$allGensetKeys = ["G1_U16_ACTIVE_ENERGY_IMPORT_KWH", "G1_U17_ACTIVE_ENERGY_IMPORT_KWH", "G1_U18_ACTIVE_ENERGY_IMPORT_KWH", "G1_U19_ACTIVE_ENERGY_IMPORT_KWH"];

$ballMill4Key = "U_2_ACTIVE_ENERGY_IMPORT_KWH";
$mosqueKey = "U_3_ACTIVE_ENERGY_IMPORT_KWH";
$Glaze_Line2Key = "U_4_ACTIVE_ENERGY_IMPORT_KWH";
$SortingPacking_LineKey = "U_5_ACTIVE_ENERGY_IMPORT_KWH";
$Digital_printing_machineKey = "U_6_ACTIVE_ENERGY_IMPORT_KWH";
$Colony_DBKey = "U_7_ACTIVE_ENERGY_IMPORT_KWH";
$Light_DB2Key = "U_8_ACTIVE_ENERGY_IMPORT_KWH";
$Kiln_Bowler_FanKey = "U_9_ACTIVE_ENERGY_IMPORT_KWH";
$Belt300_feeding_FanKey = "U_10_ACTIVE_ENERGY_IMPORT_KWH";
$Belt200_feeding_FanKey = "U_11_ACTIVE_ENERGY_IMPORT_KWH";
$Glaze_Line1Key = "U_12_ACTIVE_ENERGY_IMPORT_KWH";
$Perklin_and_KlinKey = "U_13_ACTIVE_ENERGY_IMPORT_KWH";
$Layer_DryerKey = "U_14_ACTIVE_ENERGY_IMPORT_KWH";
$Spare1Key = "U_15_ACTIVE_ENERGY_IMPORT_KWH";
$LaboratoryKey = "U_16_ACTIVE_ENERGY_IMPORT_KWH";
$Air_Compressor1Key = "U_17_ACTIVE_ENERGY_IMPORT_KWH";
$Light_DB1Key = "U_18_ACTIVE_ENERGY_IMPORT_KWH";
$Kiln_Loading_MachineKey = "U_19_ACTIVE_ENERGY_IMPORT_KWH";
$Glaze_Ball_Mill135Key = "U_20_ACTIVE_ENERGY_IMPORT_KWH";
$Press_PHKey = "U_21_ACTIVE_ENERGY_IMPORT_KWH";
$Lightning_plantKey = "U_22_ACTIVE_ENERGY_IMPORT_KWH";
$Ball_Mill1Key = "U_23_ACTIVE_ENERGY_IMPORT_KWH";
$Polishing_Line5Key = "G1_U2_ACTIVE_ENERGY_IMPORT_KWH";
$Polishing_Line6Key = "G1_U3_ACTIVE_ENERGY_IMPORT_KWH";
$Glaze_Ball_MillKey = "G1_U4_ACTIVE_ENERGY_IMPORT_KWH";
$Polishing_Line7Key = "G1_U5_ACTIVE_ENERGY_IMPORT_KWH";
$Air_Compresser2Key = "G1_U6_ACTIVE_ENERGY_IMPORT_KWH";
$Glaze_Ball_Mill_9500LKey = "G1_U7_ACTIVE_ENERGY_IMPORT_KWH";
$Spare2Key = "G1_U8_ACTIVE_ENERGY_IMPORT_KWH";
$Spare4Key = "G1_U10_ACTIVE_ENERGY_IMPORT_KWH";
$Layer5_DryerKey = "G1_U11_ACTIVE_ENERGY_IMPORT_KWH";
$Layer5_Dryer_Unloading_machineKey = "G1_U12_ACTIVE_ENERGY_IMPORT_KWH";
$Rental_GensetKey = "G1_U13_ACTIVE_ENERGY_IMPORT_KWH";
$Water_Treatment_AreaKey = "G1_U14_ACTIVE_ENERGY_IMPORT_KWH";
$Spare5Key = "G1_U15_ACTIVE_ENERGY_IMPORT_KWH";
$Air_Compressor4Key = "G2_U6_ACTIVE_ENERGY_IMPORT_KWH";
$Press_PH4300Key = "G2_U2_ACTIVE_ENERGY_IMPORT_KWH";
$Ball_Mills3Key = "G2_U3_ACTIVE_ENERGY_IMPORT_KWH";
$Hard_MaterialKey = "G2_U4_ACTIVE_ENERGY_IMPORT_KWH";
$Polishing_Line1Key = "G2_U7_ACTIVE_ENERGY_IMPORT_KWH";
$Polishing_Line2Key = "G2_U8_ACTIVE_ENERGY_IMPORT_KWH";
$Fan_For_Spray_DryerKey = "G2_U9_ACTIVE_ENERGY_IMPORT_KWH";
$Slip_Piston_pumpKey = "G2_U10_ACTIVE_ENERGY_IMPORT_KWH";
$Coal_StoveKey = "G2_U12_ACTIVE_ENERGY_IMPORT_KWH";
$ST_MotorsKey = "G2_U13_ACTIVE_ENERGY_IMPORT_KWH";
$Polishing_Line3Key = "G2_U14_ACTIVE_ENERGY_IMPORT_KWH";
$Gaze_Tank1Key = "G2_U11_ACTIVE_ENERGY_IMPORT_KWH";
$Polishing_Line4Key = "G2_U15_ACTIVE_ENERGY_IMPORT_KWH";
$Belt100_feedingKey = "G2_U16_ACTIVE_ENERGY_IMPORT_KWH";
$No_combustion_systemKey = "G2_U17_ACTIVE_ENERGY_IMPORT_KWH";
$Digital_printing_machineKey = "G2_U18_ACTIVE_ENERGY_IMPORT_KWH";
$Spare7Key = "G2_U5_ACTIVE_ENERGY_IMPORT_KWH";
$Air_Compresser3Key = "G2_U19_ACTIVE_ENERGY_IMPORT_KWH";




if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    $projection = ['timestamp' => 1];
    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projection["{$meterId}_{$suffix}"] = 1;
        }
    }

    try {
        $pipeline = [
            ['$match' => ['timestamp' => ['$gte' => $startDate, '$lte' => $endDate]]],
            ['$project' => $projection],
            ['$sort' => ['timestamp' => 1]]
        ];

        $data = $collection->aggregate($pipeline)->toArray();

        $filteredData = array_map(function ($document) use ($meterIds, $suffixes) {
            $meterData = [];
            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($document[$key])) {
                        $meterData[$key] = $document[$key];
                    }
                }
            }
            return ['timestamp' => $document['timestamp'], 'data' => $meterData];
        }, $data);

        $firstValues = [];
        $lastValues = [];

        foreach ($filteredData as $document) {
            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";

                    if (isset($document['data'][$key])) {
                        if (!isset($firstValues[$key])) {
                            $firstValues[$key] = $document['data'][$key];
                        }
                        $lastValues[$key] = $document['data'][$key];
                    }
                }
            }
        }

        $consumptionData = [];
        foreach ($firstValues as $key => $firstValue) {
            if (isset($lastValues[$key])) {
                $consumptionData[$key] = $lastValues[$key] - $firstValue;
            }
        }

        $totalConsumption = [
            "Solars" => 0,
            "Transformers" => 0,
            "All_Genset" => 0
        ];

        foreach ($consumptionData as $key => $value) {
            if (in_array($key, $solarKeys)) {
                $totalConsumption["Solars"] += $value;
            } elseif (in_array($key, $transformerKeys)) {
                $totalConsumption["Transformers"] += $value;
            } elseif (in_array($key, $allGensetKeys)) {
                $totalConsumption["All_Genset"] += $value;
            }
        }

        $totalConsumption["Transformers"] *= 10;

        $totalConsumption["Total_Consumption"] = $totalConsumption["Solars"] + $totalConsumption["Transformers"] + $totalConsumption["All_Genset"];

        $ballMill4Consumption = $consumptionData[$ballMill4Key] ?? 0;
        $mosqueConsumption = $consumptionData[$mosqueKey] ?? 0;
        $mosqueConsumption = $consumptionData[$mosqueKey] ?? 0;
        $Glaze_Line2Consumption= $consumptionData[$Glaze_Line2Key] ?? 0;
        $SortingPacking_LineConsumption= $consumptionData[$SortingPacking_LineKey] ?? 0;
        $Digital_printing_machineConsumption= $consumptionData[$Digital_printing_machineKey] ?? 0;
        $Colony_DBConsumption= $consumptionData[$Colony_DBKey] ?? 0;
        $Light_DB2Consumption= $consumptionData[$Light_DB2Key] ?? 0;
        $Kiln_Bowler_FanConsumption= $consumptionData[$Kiln_Bowler_FanKey] ?? 0;
        $Belt300_feeding_FanConsumption= $consumptionData[$Belt300_feeding_FanKey] ?? 0;
        $Belt200_feeding_FanConsumption= $consumptionData[$Belt200_feeding_FanKey] ?? 0;
        $Glaze_Line1Consumption= $consumptionData[$Glaze_Line1Key] ?? 0;
        $Perklin_and_KlinConsumption= $consumptionData[$Perklin_and_KlinKey] ?? 0;
        $Layer_DryerConsumption= $consumptionData[$Layer_DryerKey] ?? 0;
        $Spare1Consumption= $consumptionData[$Spare1Key] ?? 0;
        $LaboratoryConsumption= $consumptionData[$LaboratoryKey] ?? 0;
        $Air_Compressor1Consumption= $consumptionData[$Air_Compressor1Key] ?? 0;
        $Light_DB1Consumption= $consumptionData[$Light_DB1Key] ?? 0;
        $Kiln_Loading_MachineConsumption= $consumptionData[$Kiln_Loading_MachineKey] ?? 0;
        $Glaze_Ball_Mill135Consumption= $consumptionData[$Glaze_Ball_Mill135Key] ?? 0;
        $Press_PHConsumption= $consumptionData[$Press_PHKey] ?? 0;
        $Lightning_plantConsumption= $consumptionData[$Lightning_plantKey] ?? 0;
        $Ball_Mill1Consumption= $consumptionData[$Ball_Mill1Key] ?? 0;
        $Polishing_Line5Consumption= $consumptionData[$Polishing_Line5Key] ?? 0;
        $Polishing_Line6Consumption= $consumptionData[$Polishing_Line6Key] ?? 0;
        $Glaze_Ball_MillConsumption= $consumptionData[$Glaze_Ball_MillKey] ?? 0;
        $Polishing_Line7Consumption= $consumptionData[$Polishing_Line7Key] ?? 0;
        $Air_Compresser2Consumption= $consumptionData[$Air_Compresser2Key] ?? 0;
        $Glaze_Ball_Mill_9500LConsumption= $consumptionData[$Glaze_Ball_Mill_9500LKey] ?? 0;
        $Spare2Consumption= $consumptionData[$Spare2Key] ?? 0;
        $Spare4Consumption= $consumptionData[$Spare4Key] ?? 0;
        $Layer5_DryerConsumption= $consumptionData[$Layer5_DryerKey] ?? 0;
        $Layer5_Dryer_Unloading_machineConsumption= $consumptionData[$Layer5_Dryer_Unloading_machineKey] ?? 0;
        $Rental_GensetConsumption= $consumptionData[$Rental_GensetKey] ?? 0;
        $Water_Treatment_AreaConsumption= $consumptionData[$Water_Treatment_AreaKey] ?? 0;
        $Spare5Consumption= $consumptionData[$Spare5Key] ?? 0;
        $Air_Compressor4Consumption= $consumptionData[$Air_Compressor4Key] ?? 0;
        $Press_PH4300Consumption= $consumptionData[$Press_PH4300Key] ?? 0;
        $Ball_Mills3Consumption= $consumptionData[$Ball_Mills3Key] ?? 0;
        $Hard_MaterialConsumption= $consumptionData[$Hard_MaterialKey] ?? 0;
        $Polishing_Line1Consumption= $consumptionData[$Polishing_Line1Key] ?? 0;
        $Polishing_Line2Consumption= $consumptionData[$Polishing_Line2Key] ?? 0;
        $Fan_For_Spray_DryerConsumption= $consumptionData[$Fan_For_Spray_DryerKey] ?? 0;
        $Slip_Piston_pumpConsumption= $consumptionData[$Slip_Piston_pumpKey] ?? 0;
        $Coal_StoveConsumption= $consumptionData[$Coal_StoveKey] ?? 0;
        $ST_MotorsConsumption= $consumptionData[$ST_MotorsKey] ?? 0;
        $Polishing_Line3Consumption= $consumptionData[$Polishing_Line3Key] ?? 0;
        $Gaze_Tank1Consumption= $consumptionData[$Gaze_Tank1Key] ?? 0;
        $Polishing_Line4Consumption= $consumptionData[$Polishing_Line4Key] ?? 0;
        $Belt100_feedingConsumption= $consumptionData[$Belt100_feedingKey] ?? 0;
        $No_combustion_systemConsumption= $consumptionData[$No_combustion_systemKey] ?? 0;
        $Digital_printing_machineConsumption= $consumptionData[$Digital_printing_machineKey] ?? 0;
        $Spare7Consumption= $consumptionData[$Spare7Key] ?? 0;
        $Air_Compresser3Consumption= $consumptionData[$Air_Compresser3Key] ?? 0;
        
        
        
        

        $totalproduction = $ballMill4Consumption + $mosqueConsumption + $Glaze_Line2Consumption+ $SortingPacking_LineConsumption +  $Digital_printing_machineConsumption + $Colony_DBConsumption +  $Light_DB2Consumption +  $Kiln_Bowler_FanConsumption + $Belt300_feeding_FanConsumption
        +$Belt200_feeding_FanConsumption+ $Glaze_Line1Consumption +$Perklin_and_KlinConsumption+ $Layer_DryerConsumption+ $Spare1Consumption+$LaboratoryConsumption+$Air_Compressor1Consumption+
        $Light_DB1Consumption+$Kiln_Loading_MachineConsumption+$Glaze_Ball_Mill135Consumption+$Press_PHConsumption+$Lightning_plantConsumption+$Ball_Mill1Consumption+$Polishing_Line5Consumption+$Polishing_Line6Consumption+$Glaze_Ball_MillConsumption+$Polishing_Line7Consumption
        +$Air_Compresser2Consumption+$Glaze_Ball_Mill_9500LConsumption+$Spare2Consumption+$Spare4Consumption+$Layer5_DryerConsumption+$Layer5_Dryer_Unloading_machineConsumption+$Rental_GensetConsumption+$Water_Treatment_AreaConsumption+$Spare5Consumption+$Air_Compressor4Consumption
       +$Press_PH4300Consumption+$Ball_Mills3Consumption+$Hard_MaterialConsumption+$Polishing_Line1Consumption+$Polishing_Line2Consumption+$Fan_For_Spray_DryerConsumption+$Slip_Piston_pumpConsumption+$Coal_StoveConsumption+$ST_MotorsConsumption+$Polishing_Line3Consumption
     +$Gaze_Tank1Consumption+$Polishing_Line4Consumption+$Belt100_feedingConsumption+$No_combustion_systemConsumption+$Digital_printing_machineConsumption+$Spare7Consumption+$Air_Compresser3Consumption;

// Unaccountable Energy ka formula (Total Consumption - Total Energy)
$unaccountableEnergy = $totalConsumption["Total_Consumption"] -  $totalproduction;

        echo json_encode([
            'total_consumption' => [
                "Total_Consumption" => number_format($totalConsumption["Total_Consumption"], 5, '.', ''),
                "Ball_Mill_4" => number_format($ballMill4Consumption, 5, '.', ''),
                "Mosque" => number_format($mosqueConsumption, 5, '.', ''),
                "Glaze_Line2Consumption" => number_format($Glaze_Line2Consumption, 5, '.', ''),
                "SortingPacking_LineConsumption" => number_format($SortingPacking_LineConsumption, 5, '.', ''),
                "Digital_printing_machineConsumption" => number_format($Digital_printing_machineConsumption, 5, '.', ''),
                "Colony_DBConsumption" => number_format($Colony_DBConsumption, 5, '.', ''),
                "Light_DB2Consumption" => number_format($Light_DB2Consumption, 5, '.', ''),
                "Belt300_feeding_FanConsumption" => number_format($Belt300_feeding_FanConsumption, 5, '.', ''),
                "Kiln_Bowler_FanConsumption" => number_format($Kiln_Bowler_FanConsumption, 5, '.', ''),
                "Belt200_feeding_FanConsumption" => number_format($Belt200_feeding_FanConsumption, 5, '.', ''),
                "Glaze_Line1Consumption" => number_format($Glaze_Line1Consumption, 5, '.', ''),
                "Perklin_and_KlinConsumption" => number_format($Perklin_and_KlinConsumption, 5, '.', ''),
                "Layer_DryerConsumption" => number_format($Layer_DryerConsumption, 5, '.', ''),
                "Spare1Consumption" => number_format($Spare1Consumption, 5, '.', ''),
                "LaboratoryConsumption" => number_format($LaboratoryConsumption, 5, '.', ''),
                "Air_Compressor1Consumption" => number_format($Air_Compressor1Consumption, 5, '.', ''),
                "Light_DB1Consumption" => number_format($Light_DB1Consumption, 5, '.', ''),
                "Kiln_Loading_MachineConsumption" => number_format($Kiln_Loading_MachineConsumption, 5, '.', ''),
                "Glaze_Ball_Mill135Consumption" => number_format($Glaze_Ball_Mill135Consumption, 5, '.', ''),
                "Press_PHConsumption" => number_format($Press_PHConsumption, 5, '.', ''),
                "Lightning_plantConsumption" => number_format($Lightning_plantConsumption, 5, '.', ''),
                "Ball_Mill1Consumption" => number_format($Ball_Mill1Consumption, 5, '.', ''),
                "Polishing_Line5Consumption" => number_format($Polishing_Line5Consumption, 5, '.', ''),
                "Polishing_Line6Consumption" => number_format($Polishing_Line6Consumption, 5, '.', ''),
                "Glaze_Ball_MillConsumption" => number_format($Glaze_Ball_MillConsumption, 5, '.', ''),
                "Polishing_Line7Consumption" => number_format($Polishing_Line7Consumption, 5, '.', ''),
                "Air_Compresser2Consumption" => number_format($Air_Compresser2Consumption, 5, '.', ''),
                "Glaze_Ball_Mill_9500LConsumption" => number_format($Glaze_Ball_Mill_9500LConsumption, 5, '.', ''),
                "Spare2Consumption" => number_format($Spare2Consumption, 5, '.', ''),
                "Spare4Consumption" => number_format($Spare4Consumption, 5, '.', ''),
                "Layer5_DryerConsumption" => number_format($Layer5_DryerConsumption, 5, '.', ''),
                "Layer5_Dryer_Unloading_machineConsumption" => number_format($Layer5_Dryer_Unloading_machineConsumption, 5, '.', ''),
                 "Rental_GensetConsumption" => number_format($Rental_GensetConsumption, 5, '.', ''),
                 "Water_Treatment_AreaConsumption" => number_format($Water_Treatment_AreaConsumption, 5, '.', ''),
                 "Spare5Consumption" => number_format($Spare5Consumption, 5, '.', ''),
                 "Air_Compressor4Consumption" => number_format($Air_Compressor4Consumption, 5, '.', ''),
                 "Press_PH4300Consumption" => number_format($Press_PH4300Consumption, 5, '.', ''),
                 "Ball_Mills3Consumption" => number_format($Ball_Mills3Consumption, 5, '.', ''),
                 "Hard_MaterialConsumption" => number_format($Hard_MaterialConsumption, 5, '.', ''),
                 "Polishing_Line1Consumption" => number_format($Polishing_Line1Consumption, 5, '.', ''),
                 "Polishing_Line2Consumption" => number_format($Polishing_Line2Consumption, 5, '.', ''),
                 "Fan_For_Spray_DryerConsumption" => number_format($Fan_For_Spray_DryerConsumption, 5, '.', ''),
                 "Slip_Piston_pumpConsumption" => number_format($Slip_Piston_pumpConsumption, 5, '.', ''),
                 "Coal_StoveConsumption" => number_format($Coal_StoveConsumption, 5, '.', ''),
                 "ST_MotorsConsumption" => number_format($ST_MotorsConsumption, 5, '.', ''),
                 "Polishing_Line3Consumption" => number_format($Polishing_Line3Consumption, 5, '.', ''),
                 "Gaze_Tank1Consumption" => number_format($Gaze_Tank1Consumption, 5, '.', ''),
                 "Polishing_Line4Consumption" => number_format($Polishing_Line4Consumption, 5, '.', ''),
                 "Belt100_feedingConsumption" => number_format($Belt100_feedingConsumption, 5, '.', ''),
                 "No_combustion_systemConsumption" => number_format($No_combustion_systemConsumption, 5, '.', ''),
                 "Digital_printing_machineConsumption" => number_format($Digital_printing_machineConsumption, 5, '.', ''),
                 "Spare7Consumption" => number_format($Spare7Consumption, 5, '.', ''),
                 "Air_Compresser3Consumption" => number_format($Air_Compresser3Consumption, 5, '.', ''),
                 "Unaccountable_Energy" => number_format($unaccountableEnergy, 5, '.', ''),
                 "totalproduction"=> number_format($totalproduction, 5, '.', '')
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date and end_date parameters."]);
}
