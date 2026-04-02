<?php
error_reporting(0);
@ini_set("display_errors", 0);
// ============================================================
// TITAN WING AIRLINES — Admin API  (v2 — fixed auth)
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$action = trim($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

// ── ADMIN LOGIN — public, no auth ─────────────────────────────
if ($action === 'login') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password'] ?? '';

    if (!$email || !$pass) jsonError('Email and password are required.');

    $admin = db()->fetchOne("SELECT * FROM admins WHERE email = ? AND is_active = 1", [$email]);
    if (!$admin) jsonError('No active admin account found with this email.');
    if (!password_verify($pass, $admin['password_hash'])) jsonError('Incorrect password.');

    $token = generateJWT([
        'admin_id' => (int)$admin['id'],
        'email'    => $admin['email'],
        'role'     => $admin['role'],
        'is_admin' => true,
    ]);

    db()->execute("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
    setcookie('tw_admin_token', $token, time() + JWT_EXPIRY, '/', '', false, true);

    jsonSuccess([
        'token' => $token,
        'admin' => [
            'id'    => (int)$admin['id'],
            'name'  => $admin['name'],
            'email' => $admin['email'],
            'role'  => $admin['role'],
        ],
    ], 'Login successful. Welcome, ' . $admin['name'] . '!');
}

// ── ALL PROTECTED ROUTES require admin token ──────────────────
$adminUser = requireAdmin();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    case 'stats':
        jsonSuccess([
            'total_flights'    => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM flights WHERE is_active=1")['c'],
            'total_bookings'   => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM bookings")['c'],
            'total_users'      => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM users WHERE is_active=1")['c'],
            'total_revenue'    => (float)db()->fetchOne("SELECT COALESCE(SUM(total_amount),0) AS c FROM bookings WHERE payment_status='paid'")['c'],
            'today_bookings'   => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM bookings WHERE DATE(created_at)=CURDATE()")['c'],
            'today_revenue'    => (float)db()->fetchOne("SELECT COALESCE(SUM(total_amount),0) AS c FROM bookings WHERE DATE(created_at)=CURDATE() AND payment_status='paid'")['c'],
            'confirmed_count'  => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM bookings WHERE status='confirmed'")['c'],
            'cancelled_count'  => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM bookings WHERE status='cancelled'")['c'],
            'domestic_flights' => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM flights WHERE flight_type='domestic' AND is_active=1")['c'],
            'intl_flights'     => (int)db()->fetchOne("SELECT COUNT(*) AS c FROM flights WHERE flight_type='international' AND is_active=1")['c'],
            'top_routes'       => db()->fetchAll("SELECT o.code AS origin, d.code AS dest, COUNT(*) AS bookings FROM bookings b JOIN flights f ON b.flight_id=f.id JOIN airports o ON f.origin_id=o.id JOIN airports d ON f.destination_id=d.id WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY f.origin_id, f.destination_id ORDER BY bookings DESC LIMIT 5"),
        ]);

    case 'flights':
        if ($method === 'GET') {
            $search = trim($_GET['search'] ?? '');
            $status = $_GET['status'] ?? '';
            $page   = max(1,(int)($_GET['page'] ?? 1));
            $pp     = 20;
            $where  = "WHERE f.is_active=1"; $p = [];
            if ($search) { $like="%$search%"; $where.=" AND (f.flight_number LIKE ? OR o.city LIKE ? OR d.city LIKE ?)"; $p=[$like,$like,$like]; }
            if ($status) { $where.=" AND f.status=?"; $p[]=$status; }
            $total = (int)db()->fetchOne("SELECT COUNT(*) AS c FROM flights f JOIN airports o ON f.origin_id=o.id JOIN airports d ON f.destination_id=d.id $where",$p)['c'];
            $pg = paginate($total,$pp,$page); $off=(int)$pg['offset'];
            $flights = db()->fetchAll("SELECT f.*,o.code AS origin_code,o.city AS origin_city,d.code AS dest_code,d.city AS dest_city,ac.model AS aircraft_model FROM flights f JOIN airports o ON f.origin_id=o.id JOIN airports d ON f.destination_id=d.id LEFT JOIN aircraft ac ON f.aircraft_id=ac.id $where ORDER BY f.departure_time DESC LIMIT $pp OFFSET $off",$p);
            jsonSuccess(['flights'=>$flights,'pagination'=>$pg]);
        }
        if ($method === 'POST') {
            foreach(['flight_number','origin_id','destination_id','departure_time','arrival_time','economy_price','business_price','first_class_price'] as $f) {
                if(empty($body[$f])) jsonError("Field '$f' is required.");
            }
            $fn = strtoupper(trim($body['flight_number']));
            if(db()->fetchOne("SELECT id FROM flights WHERE flight_number=?",[$fn])) jsonError("Flight number '$fn' already exists.");
            $dep=strtotime($body['departure_time']); $arr=strtotime($body['arrival_time']);
            if($arr<=$dep) jsonError('Arrival must be after departure.');
            $dur=(int)(($arr-$dep)/60);
            $orig=db()->fetchOne("SELECT country FROM airports WHERE id=?",[(int)$body['origin_id']]);
            $dest=db()->fetchOne("SELECT country FROM airports WHERE id=?",[(int)$body['destination_id']]);
            if(!$orig) jsonError('Origin airport not found.');
            if(!$dest) jsonError('Destination airport not found.');
            $ftype=($orig['country']!==$dest['country'])?'international':'domestic';
            $id=db()->insert("INSERT INTO flights(flight_number,aircraft_id,origin_id,destination_id,departure_time,arrival_time,duration_minutes,economy_price,business_price,first_class_price,available_economy,available_business,available_first,flight_type,status)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,'scheduled')",[$fn,!empty($body['aircraft_id'])?(int)$body['aircraft_id']:null,(int)$body['origin_id'],(int)$body['destination_id'],$body['departure_time'],$body['arrival_time'],$dur,(float)$body['economy_price'],(float)$body['business_price'],(float)$body['first_class_price'],(int)($body['available_economy']??138),(int)($body['available_business']??18),(int)($body['available_first']??6),$ftype]);
            jsonSuccess(['id'=>$id,'flight_type'=>$ftype],"Flight $fn added.");
        }
        if ($method === 'PUT') {
            $id=(int)($_GET['id']??0); if(!$id) jsonError('Flight ID required.');
            $allowed=['departure_time','arrival_time','economy_price','business_price','first_class_price','status','available_economy','available_business','available_first'];
            $sets=[]; $p=[];
            foreach($allowed as $f){ if(array_key_exists($f,$body)){$sets[]="$f=?";$p[]=$body[$f];} }
            if(empty($sets)) jsonError('No fields to update.');
            $p[]=$id; db()->execute("UPDATE flights SET ".implode(',',$sets)." WHERE id=?",$p);
            jsonSuccess([],'Flight updated.');
        }
        if ($method === 'DELETE') {
            $id=(int)($_GET['id']??0); if(!$id) jsonError('ID required.');
            db()->execute("UPDATE flights SET is_active=0 WHERE id=?",[$id]);
            jsonSuccess([],'Flight removed.');
        }
        jsonError('Method not allowed.',405);

    case 'airports':
        if ($method === 'GET') {
            jsonSuccess(db()->fetchAll("SELECT * FROM airports ORDER BY country,city"));
        }
        if ($method === 'POST') {
            foreach(['code','name','city','country','country_code'] as $f){ if(empty($body[$f])) jsonError("Field '$f' required."); }
            $code=strtoupper(trim($body['code']));
            if(db()->fetchOne("SELECT id FROM airports WHERE code=?",[$code])) jsonError("Code '$code' already exists.");
            $id=db()->insert("INSERT INTO airports(code,name,city,country,country_code,timezone,is_international)VALUES(?,?,?,?,?,?,?)",[$code,$body['name'],$body['city'],$body['country'],strtoupper($body['country_code']),$body['timezone']??'UTC',(int)($body['is_international']??1)]);
            jsonSuccess(['id'=>$id],"Airport $code added.");
        }
        if ($method === 'PUT') {
            $id=(int)($_GET['id']??0); if(!$id) jsonError('ID required.');
            $allowed=['name','city','country','timezone','is_international','is_active'];
            $sets=[]; $p=[];
            foreach($allowed as $f){ if(array_key_exists($f,$body)){$sets[]="$f=?";$p[]=$body[$f];} }
            if(empty($sets)) jsonError('No fields.');
            $p[]=$id; db()->execute("UPDATE airports SET ".implode(',',$sets)." WHERE id=?",$p);
            jsonSuccess([],'Airport updated.');
        }
        if ($method === 'DELETE') {
            $id=(int)($_GET['id']??0);
            db()->execute("UPDATE airports SET is_active=0 WHERE id=?",[$id]);
            jsonSuccess([],'Airport deactivated.');
        }
        jsonError('Method not allowed.',405);

    case 'bookings':
        $search=$_GET['search']??''; $status=$_GET['status']??'';
        $page=max(1,(int)($_GET['page']??1)); $pp=25;
        $where="WHERE 1=1"; $p=[];
        if($search){$like="%$search%";$where.=" AND(b.booking_ref LIKE ? OR u.email LIKE ? OR u.first_name LIKE ?)";$p=[$like,$like,$like];}
        if($status){$where.=" AND b.status=?";$p[]=$status;}
        $total=(int)db()->fetchOne("SELECT COUNT(*) AS c FROM bookings b JOIN users u ON b.user_id=u.id $where",$p)['c'];
        $pg=paginate($total,$pp,$page); $off=(int)$pg['offset'];
        $rows=db()->fetchAll("SELECT b.id,b.booking_ref,b.status,b.total_amount,b.payment_status,b.total_passengers,b.created_at,u.first_name,u.last_name,u.email,f.flight_number,f.departure_time,o.code AS origin_code,d.code AS dest_code FROM bookings b JOIN users u ON b.user_id=u.id JOIN flights f ON b.flight_id=f.id JOIN airports o ON f.origin_id=o.id JOIN airports d ON f.destination_id=d.id $where ORDER BY b.created_at DESC LIMIT $pp OFFSET $off",$p);
        jsonSuccess(['bookings'=>$rows,'pagination'=>$pg]);

    case 'users':
        $search=$_GET['search']??''; $page=max(1,(int)($_GET['page']??1)); $pp=25;
        $where="WHERE 1=1"; $p=[];
        if($search){$like="%$search%";$where.=" AND(email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";$p=[$like,$like,$like];}
        $total=(int)db()->fetchOne("SELECT COUNT(*) AS c FROM users $where",$p)['c'];
        $pg=paginate($total,$pp,$page); $off=(int)$pg['offset'];
        $rows=db()->fetchAll("SELECT id,first_name,last_name,email,phone,is_verified,is_active,created_at,(SELECT COUNT(*) FROM bookings WHERE user_id=users.id) AS booking_count FROM users $where ORDER BY created_at DESC LIMIT $pp OFFSET $off",$p);
        jsonSuccess(['users'=>$rows,'pagination'=>$pg]);

    case 'toggle_user':
        $id=(int)($body['user_id']??0); if(!$id) jsonError('user_id required.');
        db()->execute("UPDATE users SET is_active=1-is_active WHERE id=?",[$id]);
        $u=db()->fetchOne("SELECT is_active FROM users WHERE id=?",[$id]);
        jsonSuccess(['is_active'=>(int)$u['is_active']],'User status updated.');

    case 'send_notification':
        $recipients=$body['recipients']??'all';
        $title=clean($body['title']??''); $message=clean($body['message']??'');
        $subject=clean($body['subject']??$title); $type=$body['type']??'system';
        if(!$title||!$message) jsonError('Title and message required.');
        switch($recipients){
            case 'verified': $users=db()->fetchAll("SELECT id,email,first_name FROM users WHERE is_verified=1 AND is_active=1"); break;
            case 'booked': $users=db()->fetchAll("SELECT DISTINCT u.id,u.email,u.first_name FROM users u JOIN bookings b ON u.id=b.user_id WHERE b.status='confirmed' AND u.is_active=1"); break;
            default: $users=db()->fetchAll("SELECT id,email,first_name FROM users WHERE is_active=1 AND is_verified=1");
        }
        if(filter_var($recipients,FILTER_VALIDATE_EMAIL)){$u=db()->fetchOne("SELECT id,email,first_name FROM users WHERE email=?",[$recipients]);$users=$u?[$u]:[];}
        $sent=0;$failed=0;
        $html=emailTemplate($title,"<p>".nl2br(htmlspecialchars($message))."</p>");
        foreach($users as $u){
            db()->execute("INSERT INTO notifications(user_id,type,title,message)VALUES(?,?,?,?)",[(int)$u['id'],$type,$title,$message]);
            sendEmail($u['email'],$u['first_name'],$subject,$html,$type,(int)$u['id'])?$sent++:$failed++;
        }
        jsonSuccess(['sent'=>$sent,'failed'=>$failed,'total'=>count($users)],"Sent to $sent user(s).");

    case 'dispatch_ticket':
        $ref=strtoupper(trim($body['booking_ref']??'')); $toEmail=trim($body['email']??''); $docType=$body['doc_type']??'boarding_pass';
        if(!$ref) jsonError('Booking reference required.');
        $booking=db()->fetchOne("SELECT b.*,u.email AS user_email,u.first_name,u.last_name,f.flight_number,f.departure_time,f.arrival_time,o.code AS origin_code,o.city AS origin_city,d.code AS dest_code,d.city AS dest_city FROM bookings b JOIN users u ON b.user_id=u.id JOIN flights f ON b.flight_id=f.id JOIN airports o ON f.origin_id=o.id JOIN airports d ON f.destination_id=d.id WHERE b.booking_ref=?",[$ref]);
        if(!$booking) jsonError("Booking '$ref' not found.",404);
        $sendTo=$toEmail?:$booking['user_email'];
        $dep=date('D d M Y, H:i',strtotime($booking['departure_time']));
        $arr=date('H:i',strtotime($booking['arrival_time']));
        $passengers=db()->fetchAll("SELECT p.first_name,p.last_name,p.ticket_number,s.seat_number FROM passengers p LEFT JOIN seats s ON p.seat_id=s.id WHERE p.booking_id=?",$booking['id']);
        $paxRows=''; foreach($passengers as $p){$paxRows.="<li><b>{$p['first_name']} {$p['last_name']}</b> — Seat:".($p['seat_number']??"TBA")." | Ticket:{$p['ticket_number']}</li>";}
        $labels=['boarding_pass'=>'Boarding Pass','ticket'=>'E-Ticket','confirmation'=>'Booking Confirmation'];
        $label=$labels[$docType]??'Travel Document';
        $html=emailTemplate("$label — {$booking['booking_ref']}","<p>Dear <b>{$booking['first_name']}</b>,</p><p>Your <b>$label</b>:</p><div class='box' style='text-align:center;font-size:26px;letter-spacing:5px;font-weight:900;color:#0a1628'>{$booking['booking_ref']}</div><div class='box'><b>Flight:</b> {$booking['flight_number']}<br><b>Route:</b> {$booking['origin_code']} &rarr; {$booking['dest_code']}<br><b>Departure:</b> $dep<br><b>Arrival:</b> $arr</div><ul>$paxRows</ul>");
        $ok=sendEmail($sendTo,$booking['first_name'],"$label — {$booking['booking_ref']}",$html,'ticket',(int)$booking['user_id']);
        jsonSuccess(['emailed_to'=>$sendTo,'sent'=>$ok],$ok?"$label sent to $sendTo.":'Queued (SMTP may not be set up).');

    case 'email_logs':
        jsonSuccess(db()->fetchAll("SELECT el.*,u.first_name,u.last_name FROM email_logs el LEFT JOIN users u ON el.user_id=u.id ORDER BY el.sent_at DESC LIMIT 100"));

    case 'logout':
        setcookie('tw_admin_token','',time()-3600,'/');
        unset($_SESSION['admin_token']);
        jsonSuccess([],'Logged out.');

    default:
        jsonError("Unknown action: '$action'",404);
}
