<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_ActiveTags;
$collection->createIndex(['timestamp' => 1]);

// Meter ID to Name Mapping
$meterNameMapping = [
    "G2_U20" => "Solar 1",
    "U_27" => "Solar 2",
    "U_24" => "Transformer 1",
    "U_25" => "Transformer 2",
    "U_17" => "Air Compressors-1",
    "U_5" => "Auto Packing",
    "U_23" => "Ball Mills-1",
    "U_15" => "Ball Mills-2",
    "U_2" => "Ball Mills-4",
    "U_11" => "Belt 200 Feeding",
    "U_10" => "Belt 300 Feeding",
    "U_7" => "Colony D.B",
    "U_6" => "DPM-2",
    "U_12" => "Glaze Line-1",
    "U_4" => "Glaze Line-2",
    "U_20" => "Glaze Ball Mill",
    "U_9" => "Kiln Blower Fan - (R.V.E)",
    "U_19" => "Kiln Loading Machine",
    "U_16" => "Laboratory",
    "U_18" => "Light D.B # 01",
    "U_8" => "Light D.B # 02",
    "U_22" => "Lighting (Plant)",
    "U_3" => "Masjid",
    "U_13" => "Prekiln",
    "U_21" => "Press PH4300",
    "U_14" => "Layer Dryer",
    "G1_U2" => "Polishing Line 5",
    "G1_U3" => "Polishing Line 6",
    "G1_U4" => "Glaze Ball Mill 13500L-2",
    "G1_U5" => "Polishing Line 7",
    "G1_U6" => "Air Compressor-2",
    "G1_U7" => "Glaze Ball Mill 9500L-3",
    "G1_U8" => "G1_U8",
    "G1_U10" => "G1_U10",
    "G1_U11" => "5 Layer Dryer",
    "G1_U12" => "5 Layer Dryer Unloading Machine",
    "G1_U13" => "Rental Genset",
    "G1_U14" => "Water Treatment Area",
    "G1_U15" => "G1_U15",
    "G1_U16" => "G1_U16",
    "G2_U2" => "Press PH 4300/1750-1",
    "G2_U3" => "Ball Mills -3",
    "G2_U4" => "Hard Materials",
    "G2_U7" => "Polishing Line-1",
    "G2_U8" => "Polishing Line-2",
    "G2_U9" => "Fan for Spray Dryer",
    "G2_U10" => "Slip Piston Pumps & Transfer Tank",
    "G2_U11" => "Glaze Tank-1",
    "G2_U12" => "Coal Stove & Coal Conveyor",
    "G2_U13" => "ST Motor & Iron Remove",
    "G2_U14" => "Polishing Line -3",
    "G2_U15" => "Polishing Line -4",
    "G2_U16" => "Belt 100 Feeding to BM500",
    "G2_U17" => "No Combustion System",
    "G2_U18" => "Digital Printing Machine",
    "G2_U5" => "G2_U5",
    "G2_U19" => "Air Compressor 3",
    "G2_U6" => "Air Compressor 4"
];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $meterIds = isset($_GET['meterId']) ? explode(',', $_GET['meterId']) : [];
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    if (empty($meterIds) || empty($suffixes)) {
        echo json_encode(["error" => "Please provide valid meterIds and suffixes parameters."]);
        exit;
    }

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

        $dailyConsumption = [];
        $firstValuesByDay = [];
        $lastValuesByDay = [];

        foreach ($filteredData as $document) {
            $currentDate = (new DateTime($document['timestamp']))->format('Y-m-d');

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";

                    if (isset($document['data'][$key])) {
                        if (!isset($firstValuesByDay[$currentDate][$key])) {
                            $firstValuesByDay[$currentDate][$key] = $document['data'][$key];
                        }
                        $lastValuesByDay[$currentDate][$key] = $document['data'][$key];
                    }
                }
            }
        }

        $dates = array_keys($firstValuesByDay);
        for ($i = 0; $i < count($dates) - 1; $i++) {
            $currentDate = $dates[$i];
            $nextDate = $dates[$i + 1];

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($firstValuesByDay[$nextDate][$key])) {
                        $currentValue = $firstValuesByDay[$currentDate][$key];
                        $nextValue = $firstValuesByDay[$nextDate][$key];
                        $dailyConsumption[$currentDate][$key] = $nextValue - $currentValue;
                    }
                }
            }
        }

        $lastDate = end($dates);
        foreach ($meterIds as $meterId) {
            foreach ($suffixes as $suffix) {
                $key = "{$meterId}_{$suffix}";
                if (isset($firstValuesByDay[$lastDate][$key]) && isset($lastValuesByDay[$lastDate][$key])) {
                    $firstValue = $firstValuesByDay[$lastDate][$key];
                    $lastValue = $lastValuesByDay[$lastDate][$key];
                    $dailyConsumption[$lastDate][$key] = $lastValue - $firstValue;
                }
            }
        }
        // Replace meter IDs with names in the final output
        $totalConsumption = [];
        foreach ($dailyConsumption as $date => $consumption) {
            foreach ($consumption as $key => $value) {
                // Find the meter ID by matching it with the meter IDs in the mapping
                $meterId = null;
                foreach ($meterNameMapping as $id => $name) {
                    if (strpos($key, $id) === 0) { // Check if $key starts with $id
                        $meterId = $id;
                        break;
                    }
                }

                if ($meterId === null) {
                    // If no matching meter ID is found, skip this key
                    continue;
                }

                // Map the meter ID to its name
                $meterName = $meterNameMapping[$meterId] ?? $meterId;

                // Accumulate the total consumption
                if (!isset($totalConsumption[$meterName])) {
                    $totalConsumption[$meterName] = 0;
                }
                $totalConsumption[$meterName] += $value;
            }
        }

        echo json_encode([
            'total_consumption' => $totalConsumption
        ]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date and end_date parameters."]);
}
