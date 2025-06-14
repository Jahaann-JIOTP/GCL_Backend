<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collectionNew = $db->GCL_new;

// Create index only for the active collection
$collectionNew->createIndex(['timestamp' => 1, 'meterId' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $meterIds = isset($_GET['meterId']) ? explode(',', $_GET['meterId']) : [];
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    if (empty($meterIds) || empty($suffixes)) {
        echo json_encode(["error" => "Please provide valid meterId and suffixes parameters."]);
        exit;
    }

    $projectionNew = ['timestamp' => 1];
    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projectionNew["{$meterId}_{$suffix}"] = 1;
        }
    }

    try {
        $pipelineNew = [
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $startDate,
                        '$lte' => $endDate
                    ]
                ]
            ],
            [
                '$project' => $projectionNew
            ]
        ];

        $dataNew = $collectionNew->aggregate($pipelineNew)->toArray();

        $output = [];
        foreach ($dataNew as $document) {
            $timestamp = $document['timestamp'];
            $meterData = [];

            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($document[$key])) {
                        $meterData[$key] = $document[$key];
                    }
                }
            }

            $output[] = [
                'timestamp' => $timestamp,
                'data' => $meterData
            ];
        }

        usort($output, function ($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });

        echo json_encode($output);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request. Please provide start_date, end_date, meterId, and suffixes parameters."]);
}
?>
