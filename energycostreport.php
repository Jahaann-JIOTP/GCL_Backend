<?php
require 'vendor/autoload.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Connect to MongoDB
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/GCL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->GCL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Log the input for debugging
    file_put_contents('php://stderr', "Received POST data: " . print_r($input, true), FILE_APPEND);

    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $meterIds = $input['meterIds'] ?? []; // Expecting an array of meter IDs
    $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];

    if (!$startDate || !$endDate || empty($meterIds)) {
        echo json_encode(["error" => "Missing required parameters: start_date, end_date, or meterIds."]);
        exit;
    }

    // Format the dates for MongoDB
    $startOfRange = $startDate . 'T00:00:00.000+05:00';
    $endOfRange = $endDate . 'T23:59:59.999+05:00';

    $consumptionData = [];

    try {
        foreach ($meterIds as $meterId) {
            $suffix = $suffixes[0]; // Assuming only one suffix for simplicity

            // Find the first document in the range
            $firstDoc = $collection->findOne(
                [
                    'timestamp' => ['$gte' => $startOfRange, '$lte' => $endOfRange]
                ],
                [
                    'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                    'sort' => ['timestamp' => 1]
                ]
            );

            // Find the last document in the range
            $lastDoc = $collection->findOne(
                [
                    'timestamp' => ['$gte' => $startOfRange, '$lte' => $endOfRange]
                ],
                [
                    'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                    'sort' => ['timestamp' => -1]
                ]
            );

            if ($firstDoc && $lastDoc) {
                $startValue = $firstDoc["{$meterId}_{$suffix}"] ?? 0;
                $endValue = $lastDoc["{$meterId}_{$suffix}"] ?? 0;
                $consumption = $endValue - $startValue;

                $consumptionData[] = [
                    'meterId' => $meterId,
                    'startValue' => $startValue,
                    'endValue' => $endValue,
                    'consumption' => $consumption
                ];
            }
        }

        echo json_encode($consumptionData);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method. POST expected."]);
}
?>
