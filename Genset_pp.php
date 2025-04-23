<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

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
$collection->createIndex(['timestamp' => 1]);

$gensetKeys = ["G1_U16_ACTIVE_ENERGY_IMPORT_KWH", "G1_U17_ACTIVE_ENERGY_IMPORT_KWH", "G1_U18_ACTIVE_ENERGY_IMPORT_KWH", "G1_U19_ACTIVE_ENERGY_IMPORT_KWH"];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['value'])) {
    if ($_GET['value'] === 'today') {
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        $yesterday = clone $today;
        $yesterday->modify('-1 day');

        $todayEnd = clone $today;
        $todayEnd->setTime(23, 59, 59);

        $projection = ['timestamp' => 1];
        foreach ($gensetKeys as $key) {
            $projection[$key] = 1;
        }

        try {
            $pipeline = [
                ['$match' => ['timestamp' => ['$gte' => $yesterday->format('c'), '$lte' => $todayEnd->format('c')]]],
                ['$project' => $projection],
                ['$sort' => ['timestamp' => 1]]
            ];

            $data = $collection->aggregate($pipeline)->toArray();

            $hourlyConsumption = [];
            $firstValues = [];
            $lastValues = [];

            foreach ($data as $document) {
                $dateTime = new DateTime($document['timestamp']);
                $hour = $dateTime->format('H:00');
                $dayType = $dateTime->format('Y-m-d') === $today->format('Y-m-d') ? "Today" : "Yesterday";

                foreach ($gensetKeys as $key) {
                    if (isset($document[$key])) {
                        if (!isset($firstValues[$hour][$dayType][$key])) {
                            $firstValues[$hour][$dayType][$key] = $document[$key];
                        }
                        $lastValues[$hour][$dayType][$key] = $document[$key];
                    }
                }
            }

            foreach (range(0, 23) as $hour) {
                $hourStr = str_pad($hour, 2, "0", STR_PAD_LEFT) . ":00";
                $totalToday = 0;
                $totalYesterday = 0;

                foreach ($gensetKeys as $key) {
                    if (isset($firstValues[$hourStr]["Today"][$key]) && isset($lastValues[$hourStr]["Today"][$key])) {
                        $totalToday += $lastValues[$hourStr]["Today"][$key] - $firstValues[$hourStr]["Today"][$key];
                    }

                    if (isset($firstValues[$hourStr]["Yesterday"][$key]) && isset($lastValues[$hourStr]["Yesterday"][$key])) {
                        $totalYesterday += $lastValues[$hourStr]["Yesterday"][$key] - $firstValues[$hourStr]["Yesterday"][$key];
                    }
                }

                $hourlyConsumption[] = [
                    "Time" => $hourStr,
                    "Today" => round($totalToday, 2),
                    "Yesterday" => round($totalYesterday, 2)
                ];
            }

            echo json_encode($hourlyConsumption);
        } catch (Exception $e) {
            echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
        }
    }
  


   
    
    
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['value']) && $_GET['value'] === 'week') {
        $today = new DateTime();
        $today->setTime(23, 59, 59);
    
        // Define This Week (Monday - Sunday)
        $thisWeekStart = clone $today;
        $thisWeekStart->modify('this week monday');
        $thisWeekStart->setTime(0, 0, 0);
        
        $thisWeekEnd = clone $thisWeekStart;
        $thisWeekEnd->modify('+6 days'); // Monday to Sunday
        $thisWeekEnd->setTime(23, 59, 59);
    
        // Define Last Week (Previous Monday - Sunday)
        $lastWeekStart = clone $thisWeekStart;
        $lastWeekStart->modify('-7 days'); // Previous Monday
        $lastWeekEnd = clone $lastWeekStart;
        $lastWeekEnd->modify('+6 days'); // Previous Sunday
        $lastWeekEnd->setTime(23, 59, 59);
    
        // Query projection
        $projection = ['timestamp' => 1];
        foreach ($gensetKeys as $key) {
            $projection[$key] = 1;
        }
    
        try {
            // Fetch data for both weeks
            $pipeline = [
                ['$match' => [
                    'timestamp' => ['$gte' => $lastWeekStart->format('c'), '$lte' => $thisWeekEnd->format('c')]
                ]],
                ['$project' => $projection],
                ['$sort' => ['timestamp' => 1]]
            ];
    
            $data = $collection->aggregate($pipeline)->toArray();
    
            $dailyConsumption = [];
            $firstValues = [];
            $lastValues = [];
    
            foreach ($data as $document) {
                $date = (new DateTime($document['timestamp']))->format('Y-m-d');
    
                foreach ($gensetKeys as $key) {
                    if (isset($document[$key])) {
                        if (!isset($firstValues[$date][$key])) {
                            $firstValues[$date][$key] = $document[$key];
                        }
                        $lastValues[$date][$key] = $document[$key];
                    }
                }
            }
    
            // Calculate daily consumption
            foreach ($firstValues as $date => $values) {
                $totalConsumption = 0;
                foreach ($gensetKeys as $key) {
                    if (isset($lastValues[$date][$key]) && isset($firstValues[$date][$key])) {
                        $totalConsumption += $lastValues[$date][$key] - $firstValues[$date][$key];
                    }
                }
                $dailyConsumption[$date] = $totalConsumption;
            }
    
            // Structure data for output
            $weekData = ["This Week" => 0, "Last Week" => 0];
            $finalData = [
                "Mon" => ["This Week" => 0, "Last Week" => 0],
                "Tue" => ["This Week" => 0, "Last Week" => 0],
                "Wed" => ["This Week" => 0, "Last Week" => 0],
                "Thu" => ["This Week" => 0, "Last Week" => 0],
                "Fri" => ["This Week" => 0, "Last Week" => 0],
                "Sat" => ["This Week" => 0, "Last Week" => 0],
                "Sun" => ["This Week" => 0, "Last Week" => 0]
            ];
    
            foreach ($dailyConsumption as $date => $consumption) {
                $dateObj = new DateTime($date);
    
                if ($dateObj >= $thisWeekStart && $dateObj <= $thisWeekEnd) {
                    $dayType = "This Week";
                } elseif ($dateObj >= $lastWeekStart && $dateObj <= $lastWeekEnd) {
                    $dayType = "Last Week";
                } else {
                    continue; // Ignore dates outside the range
                }
    
                $dayName = $dateObj->format('D');
                $weekData[$dayType] += $consumption;
                $finalData[$dayName][$dayType] = $consumption;
            }
    
            $output = [];
            foreach (["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"] as $day) {
                $output[] = [
                    "Day" => $day,
                    "This Week" => number_format($finalData[$day]["This Week"] ?? 0, 2),
                    "Last Week" => number_format($finalData[$day]["Last Week"] ?? 0, 2)


                    
                ];
            }
            
            
            echo json_encode($output);
        } catch (Exception $e) {
            echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
        }

        
    }
    
    ////////////


     
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['value']) && $_GET['value'] === 'month') {
        $today = new DateTime();
        
        // This Month Range
        $thisMonthStart = new DateTime('first day of this month');
        $thisMonthStart->setTime(0, 0, 0);
        $thisMonthEnd = clone $today;
        $thisMonthEnd->setTime(23, 59, 59);
    
        // Last Month Range
        $lastMonthStart = new DateTime('first day of last month');
        $lastMonthStart->setTime(0, 0, 0);
        $lastMonthEnd = new DateTime('last day of last month');
        $lastMonthEnd->setTime(23, 59, 59);
    
        // Query Projection
        $projection = ['timestamp' => 1];
        foreach ($gensetKeys as $key) {
            $projection[$key] = 1;
        }
    
        try {
            // Fetch data for both months
            $pipeline = [
                ['$match' => [
                    'timestamp' => ['$gte' => $lastMonthStart->format('c'), '$lte' => $thisMonthEnd->format('c')]
                ]],
                ['$project' => $projection],
                ['$sort' => ['timestamp' => 1]]
            ];
    
            $data = $collection->aggregate($pipeline)->toArray();
    
            $dailyConsumption = [];
            $firstValues = [];
            $lastValues = [];
    
            foreach ($data as $document) {
                $date = (new DateTime($document['timestamp']))->format('Y-m-d');
    
                foreach ($gensetKeys as $key) {
                    if (isset($document[$key])) {
                        if (!isset($firstValues[$date][$key])) {
                            $firstValues[$date][$key] = $document[$key];
                        }
                        $lastValues[$date][$key] = $document[$key];
                    }
                }
            }
    
            // Calculate daily consumption
            foreach ($firstValues as $date => $values) {
                $totalConsumption = 0;
                foreach ($gensetKeys as $key) {
                    if (isset($lastValues[$date][$key]) && isset($firstValues[$date][$key])) {
                        $totalConsumption += $lastValues[$date][$key] - $firstValues[$date][$key];
                    }
                }
                $dailyConsumption[$date] = $totalConsumption;
            }
    
            // Define Weekly Data Structure
            $weekRanges = [
                "Week1" => ["start" => 1, "end" => 7],
                "Week2" => ["start" => 8, "end" => 14],
                "Week3" => ["start" => 15, "end" => 21],
                "Week4" => ["start" => 22, "end" => 28],
                "Week5" => ["start" => 29, "end" => 31] // Covers remaining days
            ];
    
            $weekData = [
                "This Month" => ["Week1" => 0, "Week2" => 0, "Week3" => 0, "Week4" => 0, "Week5" => 0],
                "Last Month" => ["Week1" => 0, "Week2" => 0, "Week3" => 0, "Week4" => 0, "Week5" => 0]
            ];
    
            // Sum daily consumption into weekly values
            foreach ($dailyConsumption as $date => $consumption) {
                $dateObj = new DateTime($date);
                $day = (int) $dateObj->format('d');
    
                if ($dateObj >= $thisMonthStart && $dateObj <= $thisMonthEnd) {
                    $monthType = "This Month";
                } elseif ($dateObj >= $lastMonthStart && $dateObj <= $lastMonthEnd) {
                    $monthType = "Last Month";
                } else {
                    continue; // Ignore dates outside the range
                }
    
                foreach ($weekRanges as $weekName => $range) {
                    if ($day >= $range['start'] && $day <= $range['end']) {
                        $weekData[$monthType][$weekName] += $consumption;
                        break;
                    }
                }
            }
    
            // Format Output and Limit to 2 Decimal Places
            $output = [];
            foreach (["Week1", "Week2", "Week3", "Week4", "Week5"] as $week) {
                $output[] = [
                    "Weeks" => $week,
                    "Last Month" => number_format($weekData["Last Month"][$week] ?? 0, 2),
                    "This Month" => number_format($weekData["This Month"][$week] ?? 0, 2)
                ];
            }
    
            // Return JSON Output
            echo json_encode($output);
        } catch (Exception $e) {
            echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
        }
    }
    
    
    //////

    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['value']) && $_GET['value'] === 'year') {
        $today = new DateTime();
        $currentYear = $today->format('Y');
        $previousYear = $today->modify('-1 year')->format('Y');
    
        // Define months
        $months = [
            "Jan" => "01", "Feb" => "02", "Mar" => "03", "Apr" => "04",
            "May" => "05", "Jun" => "06", "Jul" => "07", "Aug" => "08",
            "Sep" => "09", "Oct" => "10", "Nov" => "11", "Dec" => "12"
        ];
    
        // Query Projection
        $projection = ['timestamp' => 1];
        foreach ($gensetKeys as $key) {
            $projection[$key] = 1;
        }
    
        try {
            // Fetch data for both years
            $pipeline = [
                ['$match' => [
                    'timestamp' => ['$gte' => "$previousYear-01-01T00:00:00Z", '$lte' => "$currentYear-12-31T23:59:59Z"]
                ]],
                ['$project' => $projection],
                ['$sort' => ['timestamp' => 1]]
            ];
    
            $data = $collection->aggregate($pipeline)->toArray();
    
            $dailyFirstValues = [];
            $dailyLastValues = [];
    
            // Organize data by date
            foreach ($data as $document) {
                $dateObj = new DateTime($document['timestamp']);
                $date = $dateObj->format('Y-m-d');
                $year = $dateObj->format('Y');
    
                foreach ($gensetKeys as $key) {
                    if (isset($document[$key])) {
                        if (!isset($dailyFirstValues[$year][$date][$key])) {
                            $dailyFirstValues[$year][$date][$key] = $document[$key];
                        }
                        $dailyLastValues[$year][$date][$key] = $document[$key];
                    }
                }
            }
    
            // Calculate monthly consumption
            $yearlyConsumption = [
                $currentYear => array_fill_keys(array_keys($months), 0),
                $previousYear => array_fill_keys(array_keys($months), 0)
            ];
    
            foreach ($dailyFirstValues as $year => $dates) {
                foreach ($dates as $date => $values) {
                    $month = (new DateTime($date))->format('M');
                    $totalConsumption = 0;
    
                    foreach ($gensetKeys as $key) {
                        if (isset($dailyLastValues[$year][$date][$key]) && isset($dailyFirstValues[$year][$date][$key])) {
                            $totalConsumption += $dailyLastValues[$year][$date][$key] - $dailyFirstValues[$year][$date][$key];
                        }
                    }
                    $yearlyConsumption[$year][$month] += $totalConsumption;
                }
            }
    
            // Prepare JSON Output
            $output = [];
            foreach ($months as $monthName => $monthNum) {
                $output[] = [
                    "Month" => $monthName,
                    "Current Year" => number_format($yearlyConsumption[$currentYear][$monthName] ?? 0, 2),
                    "Previous Year" => number_format($yearlyConsumption[$previousYear][$monthName] ?? 0, 2)
                ];
            }
    
            // Return JSON Response
            echo json_encode($output);
        } catch (Exception $e) {
            echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
        }
    }
    
    
    
    
    else {
        echo json_encode(["error" => "Invalid request. Use ?value=today or ?value=week"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
