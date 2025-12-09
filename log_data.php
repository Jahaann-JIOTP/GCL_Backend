<?php
require 'vendor/autoload.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

error_reporting(E_ALL & ~E_DEPRECATED);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Parameters fetch section sabse upar
$type = $_GET['type'] ?? null;
$meters = $_GET['meters'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Validate input
if (!$type || !$meters || !$start_date || !$end_date) {
    echo json_encode(["error" => "Missing required parameters: type, meters, start_date, or end_date"]);
    exit;
}

// MongoDB connection function
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/GCL?authSource=admin&readPreference=primary&ssl=false");
        return $client->GCL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();

// Collection selection based on type
if ($type === 'active_energy') {
    $collection = $db->GCL_new;
} else {
    $collection = $db->GCL_new;
}

// Tag Groups - no change needed here
$tagGroups = [
    "voltage" => ["VOLTAGE_LINE_1_V", "VOLTAGE_LINE_2_V", "VOLTAGE_LINE_3_V", "VOLTAGE_L_N_AVG_V", "VOLTAGE_LINE_1_2_V", "VOLTAGE_LINE_2_3_V", "VOLTAGE_LINE_3_1_V", "VOLTAGE_L_L_AVG_V"],
    "current" => ["CURRENT_LINE_1_A", "CURRENT_LINE_2_A", "CURRENT_LINE_3_A", "CURRENT_AVG_A"],
    "power_factor" => ["POWER_FACTOR_PF1", "POWER_FACTOR_PF2", "POWER_FACTOR_PF3", "POWER_FACTOR_TOTAL"],
    "active_power" => ["ACTIVE_POWER_P1_KW","ACTIVE_POWER_P2_KW","ACTIVE_POWER_P3_KW","ACTIVE_POWER_TOTAL_KW"],
    "reactive_power"  =>["REACTIVE_POWER_Q1_KVAR","REACTIVE_POWER_Q2_KVAR","REACTIVE_POWER_Q3_KVAR","REACTIVE_POWER_TOTAL_KVAR"],
    "apparent_power"  =>["APPARENT_POWER_S1_KVA","APPARENT_POWER_S2_KVA","APPARENT_POWER_S3_KVA", "APPARENT_POWER_TOTAL_KVA"],
    "harmonics"  =>["HARMONICS_I1_THD", "HARMONICS_I2_THD","HARMONICS_I3_THD","HARMONICS_V1_THD","HARMONICS_V2_THD", "HARMONICS_V3_THD"],
    "active_energy" =>["ACTIVE_ENERGY_IMPORT_KWH", "ACTIVE_ENERGY_EXPORT_KWH"],
    "reactive_energy"  => [ "REACTIVE_ENERGY_IMPORT_KVARH", "REACTIVE_ENERGY_EXPORT_KVARH"],
    "apparent_energy"=>["APPARENT_ENERGY_KVAH"] 
];

// Type validation
if (!array_key_exists($type, $tagGroups)) {
    echo json_encode(["error" => "Invalid type specified. Allowed types: " . implode(', ', array_keys($tagGroups))]);
    exit;
}

$tagsToFetch = $tagGroups[$type];
$meterIds = explode(',', $meters);

try {
    $query = [
        'timestamp' => [
            '$gte' => $start_date . 'T00:00:00.000+05:00',
            '$lte' => $end_date . 'T23:59:59.999+05:00',
        ],
    ];

    $data = $collection->find($query)->toArray();

    if (empty($data)) {
        echo json_encode([
            "success" => false,
            "message" => "No documents found for the specified date range.",
        ]);
        exit;
    }

    $results = [];
    foreach ($data as $item) {
        foreach ($meterIds as $meterId) {
            $entry = [
                'time' => isset($item['timestamp'])
                    ? (is_string($item['timestamp']) ? $item['timestamp'] : $item['timestamp']->toDateTime()->format('Y-m-d H:i:s'))
                    : null,
                'meterId' => $meterId,
            ];

            foreach ($tagsToFetch as $tag) {
                $field = "{$meterId}_{$tag}";
                if (isset($item[$field])) {
                    if (in_array($type, ['active_power', 'reactive_power', 'apparent_power']) && in_array($meterId, ['U_24', 'U_25'])) {
                        $entry[$tag] = $item[$field];
                    } elseif ($meterId === 'G2_U20' && in_array($tag, [
                        'APPARENT_POWER_S1_KVA',
                        'APPARENT_POWER_S2_KVA',
                        'APPARENT_POWER_S3_KVA',
                        'APPARENT_POWER_TOTAL_KVA'
                    ])) {
                        $entry[$tag] = $item[$field];
                    } else {
                        $entry[$tag] = $item[$field];
                    }
                }
            }

            if (count($entry) > 2) {
                $results[] = $entry;
            }
        }
    }

    echo json_encode(["success" => true, "data" => $results]);
} catch (Exception $e) {
    echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    exit;
}
?>