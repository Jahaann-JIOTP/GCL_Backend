<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collectionNew = $db->GCL_new;
$collectionActiveTags = $db->GCL_new;

$collectionNew->createIndex(['timestamp' => 1, 'meterId' => 1]);
$collectionActiveTags->createIndex(['timestamp' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $meterIds = isset($_GET['meterId']) ? explode(',', $_GET['meterId']) : [];
    $suffixes = isset($_GET['suffixes']) ? explode(',', $_GET['suffixes']) : [];

    // Format the start and end dates
    $startDate = $_GET['start_date'] . 'T00:00:00.000+05:00';
    $endDate = $_GET['end_date'] . 'T23:59:59.999+05:00';

    if (empty($meterIds) || empty($suffixes)) {
        echo json_encode(["error" => "Please provide valid meterId and suffixes parameters."]);
        exit;
    }

    $projectionNew = ['timestamp' => 1]; // For GCL_new
    $projectionActiveTags = ['timestamp' => 1]; // For GCL_ActiveTags

    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projectionNew["{$meterId}_{$suffix}"] = 1;
            $projectionActiveTags["{$meterId}_{$suffix}"] = 1;
        }
    }

    try {
        // Pipeline for GCL_new
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

        // Pipeline for GCL_ActiveTags
        $pipelineActiveTags = [
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $startDate,
                        '$lte' => $endDate
                    ]
                ]
            ],
            [
                '$project' => $projectionActiveTags
            ]
        ];

        // Fetch data from both collections
        $dataNew = $collectionNew->aggregate($pipelineNew)->toArray();
        $dataActiveTags = $collectionActiveTags->aggregate($pipelineActiveTags)->toArray();

        // Combine and format data
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

        foreach ($dataActiveTags as $document) {
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

        // Sort the output by timestamp
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
