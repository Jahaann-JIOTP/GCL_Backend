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
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1]);

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

        echo json_encode([
            'total_consumption' => [
                // "Solars" => $totalConsumption["Solars"],
                // "Transformers" => $totalConsumption["Transformers"],
                // "All_Genset" => $totalConsumption["All_Genset"],
                "Total_Consumption" => number_format($totalConsumption["Total_Consumption"], 5, '.', '')
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date and end_date parameters."]);
}
