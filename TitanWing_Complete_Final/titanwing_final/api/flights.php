<?php
error_reporting(0);
@ini_set("display_errors", 0);
// ============================================================
// TITAN WING AIRLINES - Flights API
// GET /api/flights.php?action=search|detail|seats
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'search';

switch ($action) {

    // ── SEARCH FLIGHTS ────────────────────────────────────────
    case 'search':
        $from       = strtoupper(trim($_GET['from']     ?? ''));
        $to         = strtoupper(trim($_GET['to']       ?? ''));
        $date       = $_GET['date']       ?? '';
        $passengers = max(1, cleanInt($_GET['passengers'] ?? 1));
        $cls        = $_GET['class']      ?? 'economy';
        $tripType   = $_GET['trip_type']  ?? 'one-way';
        $retDate    = $_GET['return_date'] ?? '';

        if (!$from || !$to || !$date) jsonError('Origin, destination and date are required.');
        if ($from === $to) jsonError('Origin and destination cannot be the same.');

        // Validate date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jsonError('Invalid date format. Use YYYY-MM-DD.');

        $seatCol = match($cls) {
            'business' => 'available_business',
            'first'    => 'available_first',
            default    => 'available_economy',
        };
        $priceCol = match($cls) {
            'business' => 'business_price',
            'first'    => 'first_class_price',
            default    => 'economy_price',
        };

        $sql = "SELECT f.*,
                       o.code AS origin_code, o.city AS origin_city, o.name AS origin_name,
                       d.code AS dest_code,   d.city AS dest_city,   d.name AS dest_name,
                       ac.model AS aircraft_model
                FROM flights f
                JOIN airports o  ON f.origin_id      = o.id
                JOIN airports d  ON f.destination_id = d.id
                LEFT JOIN aircraft ac ON f.aircraft_id = ac.id
                WHERE o.code = ?
                  AND d.code = ?
                  AND DATE(f.departure_time) = ?
                  AND f.$seatCol >= ?
                  AND f.status NOT IN ('cancelled','departed','arrived')
                  AND f.is_active = 1
                ORDER BY f.departure_time ASC";

        $flights = db()->fetchAll($sql, [$from, $to, $date, $passengers]);

        // Return flights
        $result = ['outbound' => $flights];

        // Round trip — also search return
        if ($tripType === 'round-trip' && $retDate) {
            $retFlights = db()->fetchAll($sql, [$to, $from, $retDate, $passengers]);
            $result['return'] = $retFlights;
        }

        jsonSuccess($result, count($flights) . ' flight(s) found.');

    // ── FLIGHT DETAIL ─────────────────────────────────────────
    case 'detail':
        $id = cleanInt($_GET['id'] ?? 0);
        if (!$id) jsonError('Flight ID required.');

        $flight = db()->fetchOne(
            "SELECT f.*, o.code AS origin_code, o.city AS origin_city, o.name AS origin_name,
                    o.country AS origin_country,
                    d.code AS dest_code, d.city AS dest_city, d.name AS dest_name,
                    d.country AS dest_country,
                    ac.model AS aircraft_model, ac.registration,
                    ac.economy_seats, ac.business_seats, ac.first_class_seats
             FROM flights f
             JOIN airports o  ON f.origin_id=o.id
             JOIN airports d  ON f.destination_id=d.id
             LEFT JOIN aircraft ac ON f.aircraft_id=ac.id
             WHERE f.id=? AND f.is_active=1",
            [$id]
        );
        if (!$flight) jsonError('Flight not found.', 404);
        jsonSuccess($flight);

    // ── SEAT MAP ──────────────────────────────────────────────
    case 'seats':
        $flightId = cleanInt($_GET['flight_id'] ?? 0);
        if (!$flightId) jsonError('Flight ID required.');

        $seats = db()->fetchAll(
            "SELECT seat_number, class, is_available, is_window, is_aisle, is_extra_legroom
             FROM seats WHERE flight_id=? ORDER BY class DESC, seat_number ASC",
            [$flightId]
        );

        if (empty($seats)) {
            // Auto-generate seat map if not yet created
            $flight = db()->fetchOne("SELECT * FROM flights f JOIN aircraft ac ON f.aircraft_id=ac.id WHERE f.id=?", [$flightId]);
            if (!$flight) jsonError('Flight not found.', 404);
            $seats = generateSeatMap($flightId, $flight);
        }

        $grouped = ['first' => [], 'business' => [], 'economy' => []];
        foreach ($seats as $s) {
            $grouped[$s['class']][] = $s;
        }
        jsonSuccess($grouped);

    // ── AIRPORTS AUTOCOMPLETE ─────────────────────────────────
    case 'airports':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) jsonSuccess([]);

        $airports = db()->fetchAll(
            "SELECT code, name, city, country, is_international FROM airports
             WHERE is_active=1 AND (code LIKE ? OR city LIKE ? OR name LIKE ? OR country LIKE ?)
             ORDER BY is_international DESC, city ASC LIMIT 10",
            ["%$q%", "%$q%", "%$q%", "%$q%"]
        );
        jsonSuccess($airports);

    default:
        jsonError('Invalid action.', 404);
}

// Auto-generate seats for a flight
function generateSeatMap(int $flightId, array $flight): array {
    $seats = [];
    $rows = [
        'first'    => ['start' => 1,  'end' => 3,  'cols' => ['A','C','D','F']],
        'business' => ['start' => 4,  'end' => 9,  'cols' => ['A','C','D','F']],
        'economy'  => ['start' => 10, 'end' => 32, 'cols' => ['A','B','C','D','E','F']],
    ];

    $inserts = [];
    foreach ($rows as $cls => $cfg) {
        for ($r = $cfg['start']; $r <= $cfg['end']; $r++) {
            foreach ($cfg['cols'] as $col) {
                $seatNum = $r . $col;
                $isWindow = in_array($col, ['A','F']);
                $isAisle  = in_array($col, ['C','D']);
                $isExtra  = ($r >= 10 && $r <= 12);
                $inserts[] = [$flightId, $seatNum, $cls, 1, (int)$isWindow, (int)$isAisle, (int)$isExtra];
                $seats[] = ['seat_number' => $seatNum, 'class' => $cls, 'is_available' => 1,
                            'is_window' => (int)$isWindow, 'is_aisle' => (int)$isAisle, 'is_extra_legroom' => (int)$isExtra];
            }
        }
    }

    foreach ($inserts as $row) {
        try {
            db()->execute(
                "INSERT IGNORE INTO seats (flight_id,seat_number,class,is_available,is_window,is_aisle,is_extra_legroom) VALUES (?,?,?,?,?,?,?)",
                $row
            );
        } catch (Exception $e) { /* ignore duplicates */ }
    }

    return $seats;
}
