<?php
error_reporting(0);
@ini_set("display_errors", 0);
// ============================================================
// TITAN WING AIRLINES - Bookings API
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? '';
$user   = requireAuth();

switch ($action) {

    // ── CREATE BOOKING ────────────────────────────────────────
    case 'create':
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $flightId    = cleanInt($body['flight_id'] ?? 0);
        $cabinClass  = $body['class']       ?? 'economy';
        $bookingType = $body['booking_type'] ?? 'one-way';
        $passengers  = $body['passengers']  ?? [];
        $seatNos     = $body['seats']        ?? [];
        $payMethod   = clean($body['payment_method']    ?? 'card');
        $payTxnId    = clean($body['transaction_id']    ?? 'DEMO_' . uniqid());
        $retFlightId = cleanInt($body['return_flight_id'] ?? 0);

        if (!$flightId) jsonError('Flight ID required.');
        if (empty($passengers)) jsonError('At least one passenger required.');

        $flight = db()->fetchOne(
            "SELECT f.*, o.code AS origin_code, d.code AS dest_code,
                    o.city AS origin_city, d.city AS dest_city
             FROM flights f
             JOIN airports o ON f.origin_id=o.id
             JOIN airports d ON f.destination_id=d.id
             WHERE f.id=? AND f.is_active=1 AND f.status NOT IN ('cancelled','departed','arrived')",
            [$flightId]
        );
        if (!$flight) jsonError('Flight not found or unavailable.');

        $seatCol  = match($cabinClass) { 'business' => 'available_business', 'first' => 'available_first', default => 'available_economy' };
        $priceCol = match($cabinClass) { 'business' => 'business_price',     'first' => 'first_class_price', default => 'economy_price' };

        $count = count($passengers);
        if ($flight[$seatCol] < $count) jsonError("Not enough $cabinClass seats available.");

        // Calculate total
        $basePrice  = $flight[$priceCol] * $count;
        $taxes      = round($basePrice * 0.12, 2);
        $totalAmount = $basePrice + $taxes;

        db()->beginTransaction();
        try {
            // Create booking
            $bookingRef = generateBookingRef();
            $bookingId  = db()->insert(
                "INSERT INTO bookings (booking_ref,user_id,flight_id,return_flight_id,booking_type,status,total_passengers,total_amount,payment_status,payment_method,payment_transaction_id)
                 VALUES (?,?,?,?,?,'confirmed',?,?,'paid',?,?)",
                [$bookingRef, $user['id'], $flightId, $retFlightId ?: null, $bookingType, $count, $totalAmount, $payMethod, $payTxnId]
            );

            // Insert passengers + assign seats
            foreach ($passengers as $i => $pax) {
                $seatNo   = $seatNos[$i] ?? null;
                $seatId   = null;

                if ($seatNo) {
                    $seat = db()->fetchOne("SELECT id FROM seats WHERE flight_id=? AND seat_number=? AND is_available=1", [$flightId, $seatNo]);
                    if ($seat) {
                        $seatId = $seat['id'];
                        db()->execute("UPDATE seats SET is_available=0 WHERE id=?", [$seatId]);
                    }
                }

                $ticketNo = 'TW' . strtoupper(bin2hex(random_bytes(4)));
                db()->execute(
                    "INSERT INTO passengers (booking_id,first_name,last_name,dob,gender,passport_no,nationality,seat_id,class,meal_preference,special_assistance,ticket_number)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $bookingId,
                        clean($pax['first_name'] ?? $user['first_name']),
                        clean($pax['last_name']  ?? $user['last_name']),
                        $pax['dob']         ?? null,
                        $pax['gender']      ?? 'male',
                        clean($pax['passport'] ?? ''),
                        clean($pax['nationality'] ?? 'Indian'),
                        $seatId,
                        $cabinClass,
                        $pax['meal'] ?? 'standard',
                        (int)($pax['special_assistance'] ?? 0),
                        $ticketNo,
                    ]
                );
            }

            // Update seat availability on flight
            db()->execute("UPDATE flights SET $seatCol = $seatCol - ? WHERE id=?", [$count, $flightId]);

            // Create notification
            db()->execute(
                "INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)",
                [$user['id'], 'booking', '🎉 Booking Confirmed!', "Your booking $bookingRef for {$flight['origin_code']} → {$flight['dest_code']} is confirmed."]
            );

            db()->commit();

            // Send confirmation email
            $bookingData = ['booking_ref' => $bookingRef, 'total_passengers' => $count, 'total_amount' => $totalAmount];
            $flightData  = array_merge($flight, ['origin_code' => $flight['origin_code'], 'dest_code' => $flight['dest_code']]);
            sendBookingConfirmationEmail($bookingData, $user, $flightData);

            jsonSuccess([
                'booking_id'  => $bookingId,
                'booking_ref' => $bookingRef,
                'total'       => $totalAmount,
                'flight'      => ['number' => $flight['flight_number'], 'from' => $flight['origin_code'], 'to' => $flight['dest_code']],
            ], 'Booking confirmed! Check your email.');

        } catch (Exception $e) {
            db()->rollBack();
            jsonError('Booking failed: ' . $e->getMessage());
        }

    // ── MY BOOKINGS ───────────────────────────────────────────
    case 'my':
        $status = $_GET['status'] ?? '';
        $where  = $status ? "AND b.status=?" : "";
        $params = $status ? [$user['id'], $status] : [$user['id']];

        $bookings = db()->fetchAll(
            "SELECT b.*, f.flight_number, f.departure_time, f.arrival_time,
                    o.code AS origin_code, o.city AS origin_city,
                    d.code AS dest_code,   d.city AS dest_city
             FROM bookings b
             JOIN flights f  ON b.flight_id = f.id
             JOIN airports o ON f.origin_id = o.id
             JOIN airports d ON f.destination_id = d.id
             WHERE b.user_id=? $where
             ORDER BY b.created_at DESC",
            $params
        );

        // Attach passengers
        foreach ($bookings as &$bk) {
            $bk['passengers'] = db()->fetchAll(
                "SELECT p.first_name, p.last_name, p.seat_id, p.class, p.meal_preference, p.checkin_status, p.ticket_number,
                        s.seat_number
                 FROM passengers p LEFT JOIN seats s ON p.seat_id=s.id
                 WHERE p.booking_id=?",
                [$bk['id']]
            );
        }

        jsonSuccess($bookings);

    // ── BOOKING DETAIL ────────────────────────────────────────
    case 'detail':
        $ref = strtoupper(trim($_GET['ref'] ?? ''));
        if (!$ref) jsonError('Booking reference required.');

        $booking = db()->fetchOne(
            "SELECT b.*, f.flight_number, f.departure_time, f.arrival_time,
                    f.status AS flight_status, f.flight_type, f.duration_minutes,
                    o.code AS origin_code, o.city AS origin_city, o.name AS origin_name,
                    d.code AS dest_code,   d.city AS dest_city,   d.name AS dest_name,
                    ac.model AS aircraft
             FROM bookings b
             JOIN flights f   ON b.flight_id=f.id
             JOIN airports o  ON f.origin_id=o.id
             JOIN airports d  ON f.destination_id=d.id
             LEFT JOIN aircraft ac ON f.aircraft_id=ac.id
             WHERE b.booking_ref=? AND b.user_id=?",
            [$ref, $user['id']]
        );
        if (!$booking) jsonError('Booking not found.', 404);

        $booking['passengers'] = db()->fetchAll(
            "SELECT p.*, s.seat_number FROM passengers p LEFT JOIN seats s ON p.seat_id=s.id WHERE p.booking_id=?",
            [$booking['id']]
        );
        jsonSuccess($booking);

    // ── CANCEL BOOKING ────────────────────────────────────────
    case 'cancel':
        $ref = strtoupper(trim($_GET['ref'] ?? ''));
        if (!$ref) jsonError('Booking reference required.');

        $booking = db()->fetchOne(
            "SELECT b.*, f.economy_price, f.departure_time FROM bookings b JOIN flights f ON b.flight_id=f.id WHERE b.booking_ref=? AND b.user_id=?",
            [$ref, $user['id']]
        );
        if (!$booking) jsonError('Booking not found.', 404);
        if (in_array($booking['status'], ['cancelled', 'completed'])) jsonError('This booking cannot be cancelled.');

        $depTime = new DateTime($booking['departure_time']);
        $now     = new DateTime();
        $hoursDiff = ($depTime->getTimestamp() - $now->getTimestamp()) / 3600;

        $refundPct = 0;
        if ($hoursDiff > 24)  $refundPct = 100;
        elseif ($hoursDiff > 4) $refundPct = 50;

        $refundAmount = round($booking['total_amount'] * $refundPct / 100, 2);

        db()->execute("UPDATE bookings SET status='cancelled', payment_status=? WHERE booking_ref=?",
            [$refundPct > 0 ? 'refunded' : 'failed', $ref]);

        // Release seats
        db()->execute(
            "UPDATE seats s JOIN passengers p ON s.id=p.seat_id SET s.is_available=1 WHERE p.booking_id=?",
            [$booking['id']]
        );

        db()->execute(
            "INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)",
            [$user['id'], 'booking', '❌ Booking Cancelled', "Booking $ref cancelled. Refund: ₹$refundAmount ({$refundPct}%)"]
        );

        jsonSuccess(['refund_amount' => $refundAmount, 'refund_percent' => $refundPct], "Booking cancelled. Refund of ₹$refundAmount will be processed.");

    // ── ONLINE CHECK-IN ───────────────────────────────────────
    case 'checkin':
        $ref = strtoupper(trim($_GET['ref'] ?? ''));
        if (!$ref) jsonError('Booking reference required.');

        $booking = db()->fetchOne(
            "SELECT b.*, f.departure_time FROM bookings b JOIN flights f ON b.flight_id=f.id WHERE b.booking_ref=? AND b.user_id=?",
            [$ref, $user['id']]
        );
        if (!$booking) jsonError('Booking not found.', 404);
        if ($booking['status'] === 'checked-in') jsonError('Already checked in for this booking.');
        if ($booking['status'] !== 'confirmed')  jsonError('Only confirmed bookings can be checked in.');

        $dep  = new DateTime($booking['departure_time']);
        $now  = new DateTime();
        $diff = ($dep->getTimestamp() - $now->getTimestamp()) / 3600;

        if ($diff > 48) jsonError('Check-in is not open yet. Opens 48 hours before departure.');
        if ($diff < 1)  jsonError('Check-in is closed. Closes 1 hour before departure.');

        db()->execute("UPDATE bookings SET status='checked-in' WHERE booking_ref=?", [$ref]);
        db()->execute("UPDATE passengers SET checkin_status=1, boarding_pass_issued=1 WHERE booking_id=?", [$booking['id']]);

        // Notification
        db()->execute(
            "INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)",
            [$user['id'], 'checkin', '✅ Check-in Complete!', "You are checked in for booking $ref. Your boarding pass is ready!"]
        );

        // Fetch data and email boarding pass
        $flightInfo = db()->fetchOne(
            "SELECT f.*, o.code AS origin_code, o.city AS origin_city,
                    d.code AS dest_code, d.city AS dest_city
             FROM flights f
             JOIN airports o ON f.origin_id=o.id
             JOIN airports d ON f.destination_id=d.id WHERE f.id=?",
            [$booking['flight_id']]
        );
        $passengers = db()->fetchAll(
            "SELECT p.*, s.seat_number FROM passengers p LEFT JOIN seats s ON p.seat_id=s.id WHERE p.booking_id=?",
            [$booking['id']]
        );

        // Build boarding pass HTML email
        $dep = (new DateTime($flightInfo['departure_time']))->format('D, d M Y H:i');
        $arr = (new DateTime($flightInfo['arrival_time']))->format('H:i');
        $paxList = '';
        foreach ($passengers as $i => $p) {
            $seat = $p['seat_number'] ?? 'TBA';
            $gate = 'B' . (($booking['id'] % 20) + 1);
            $paxList .= "
            <div style='background:#f8f6f0;border-radius:8px;padding:14px;margin-bottom:10px;'>
              <table width='100%'><tr>
                <td><strong style='color:#0a1628;font-size:15px;'>{$p['first_name']} {$p['last_name']}</strong>
                    <br><span style='color:#888;font-size:12px;'>{$p['class']} Class</span></td>
                <td align='right'><span style='background:#0a1628;color:#e8b85c;padding:4px 12px;border-radius:100px;font-weight:700;font-size:13px;'>Seat $seat</span></td>
              </tr></table>
              <table width='100%' style='margin-top:10px'><tr>
                <td style='font-size:12px;color:#888'>Gate<br><strong style='color:#0a1628;font-size:16px;'>$gate</strong></td>
                <td style='font-size:12px;color:#888'>Status<br><strong style='color:#27ae60;font-size:14px;'>✅ Checked In</strong></td>
                <td style='font-size:12px;color:#888'>Ticket No.<br><strong style='color:#0a1628;font-size:13px;'>{$p['ticket_number']}</strong></td>
              </tr></table>
            </div>";
        }
        $emailBody = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#fff;'>
          <div style='background:linear-gradient(135deg,#0a1628,#1a3a5c);padding:30px;border-radius:12px 12px 0 0;text-align:center;'>
            <div style='font-size:28px;color:#e8b85c;font-weight:900;letter-spacing:2px;'>✈ TITAN WING</div>
            <div style='color:rgba(255,255,255,0.7);font-size:13px;margin-top:4px;'>AIRLINES</div>
            <div style='color:#fff;font-size:18px;font-weight:700;margin-top:16px;'>🎫 BOARDING PASS</div>
          </div>
          <div style='padding:24px;border:2px solid #e8b85c;border-top:none;border-radius:0 0 12px 12px;'>
            <table width='100%' style='margin-bottom:20px;'>
              <tr>
                <td style='text-align:center;'>
                  <div style='font-size:42px;font-weight:900;color:#0a1628;'>{$flightInfo['origin_code']}</div>
                  <div style='color:#888;font-size:12px;'>{$flightInfo['origin_city']}</div>
                </td>
                <td style='text-align:center;'>
                  <div style='color:#c9973a;font-size:22px;'>✈</div>
                  <div style='color:#888;font-size:11px;'>{$flightInfo['flight_number']}</div>
                </td>
                <td style='text-align:center;'>
                  <div style='font-size:42px;font-weight:900;color:#0a1628;'>{$flightInfo['dest_code']}</div>
                  <div style='color:#888;font-size:12px;'>{$flightInfo['dest_city']}</div>
                </td>
              </tr>
            </table>
            <table width='100%' style='background:#f0ece4;border-radius:8px;padding:12px;margin-bottom:20px;'>
              <tr>
                <td style='font-size:12px;color:#888;padding:6px 12px;'>Departure<br><strong style='color:#0a1628;font-size:14px;'>$dep</strong></td>
                <td style='font-size:12px;color:#888;padding:6px 12px;'>Arrival<br><strong style='color:#0a1628;font-size:14px;'>$arr</strong></td>
                <td style='font-size:12px;color:#888;padding:6px 12px;'>Booking Ref<br><strong style='color:#0a1628;font-size:14px;'>{$ref}</strong></td>
              </tr>
            </table>
            <div style='font-size:13px;font-weight:700;color:#0a1628;margin-bottom:10px;'>Passengers:</div>
            $paxList
            <div style='text-align:center;padding:16px;background:#f8f6f0;border-radius:8px;margin-top:16px;font-family:monospace;font-size:11px;letter-spacing:2px;color:#999;'>
              ▐▌▐▌▐▌▌▐▌▐▌▌▐▌▐▌▌▐▌▌▐▌▐▌▌▐▌▐▌▌▐▌▐▌▌<br>{$ref}
            </div>
            <div style='text-align:center;color:#888;font-size:11px;margin-top:16px;'>
              Please arrive at the airport at least 2 hours before departure.<br>
              <strong>Titan Wing Airlines</strong> — Fly With Confidence
            </div>
          </div>
        </div>";

        sendEmail($user['email'], $user['first_name'], "Boarding Pass - {$ref} | {$flightInfo['origin_code']} → {$flightInfo['dest_code']}", $emailBody, 'boarding_pass', $user['id']);

        jsonSuccess(['booking_ref' => $ref], 'Check-in successful! Boarding pass sent to your email.');

    // ── NOTIFICATIONS ─────────────────────────────────────────
    case 'notifications':
        $notifs = db()->fetchAll(
            "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50",
            [$user['id']]
        );
        $unread = db()->fetchOne("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0", [$user['id']]);
        jsonSuccess(['notifications' => $notifs, 'unread_count' => (int)$unread['cnt']]);

    case 'mark_read':
        db()->execute("UPDATE notifications SET is_read=1 WHERE user_id=?", [$user['id']]);
        jsonSuccess([], 'All notifications marked as read.');

    default:
        jsonError('Invalid action.', 404);
}
