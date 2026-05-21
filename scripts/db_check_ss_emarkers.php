<?php
// Diagnostic: check how many pending evaluators a specific Subject Specialist (role 18) should see.
// Usage:
//   php scripts/db_check_ss_emarkers.php <ss_user_id> [pending|approved|rejected]

$ssId = isset($argv[1]) ? (int) $argv[1] : 0;
$type = isset($argv[2]) ? strtolower((string) $argv[2]) : 'pending';
if (!in_array($type, ['pending', 'approved', 'rejected'], true)) $type = 'pending';

if ($ssId <= 0) {
	fwrite(STDERR, "Provide Subject Specialist user id. Example: php scripts/db_check_ss_emarkers.php 3084 pending\n");
	exit(1);
}

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'pectaalsa2026db2';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
	fwrite(STDERR, "DB connect error: {$mysqli->connect_error}\n");
	exit(2);
}

function q($mysqli, $sql, $params = [])
{
	if (empty($params)) {
		$res = $mysqli->query($sql);
		if ($res === false) {
			fwrite(STDERR, "SQL error: {$mysqli->error}\nSQL: {$sql}\n");
			exit(3);
		}
		return $res;
	}

	$stmt = $mysqli->prepare($sql);
	if (!$stmt) {
		fwrite(STDERR, "Prepare error: {$mysqli->error}\nSQL: {$sql}\n");
		exit(4);
	}
	$types = str_repeat('s', count($params));
	$stmt->bind_param($types, ...$params);
	if (!$stmt->execute()) {
		fwrite(STDERR, "Execute error: {$stmt->error}\n");
		exit(5);
	}
	return $stmt->get_result();
}

$userRes = q($mysqli, "SELECT id, name, role, subjects FROM users WHERE id={$ssId} LIMIT 1");
$ss = $userRes->fetch_assoc();
if (!$ss) {
	fwrite(STDERR, "No user found with id={$ssId}\n");
	exit(6);
}

echo "SS id={$ss['id']} name={$ss['name']} role={$ss['role']}\n";
echo "subjects_raw={$ss['subjects']}\n";

$raw = trim((string) ($ss['subjects'] ?? ''));
$subjects = [];
$decoded = json_decode($raw, true);
if (is_array($decoded)) {
	$subjects = $decoded;
} elseif ($raw !== '') {
	$subjects = preg_split('/\s*,\s*/', $raw);
}
$subjects = array_values(array_unique(array_filter(array_map('trim', (array) $subjects), static function ($v) {
	return $v !== '';
})));
$subjects = array_map('strtoupper', $subjects);

echo "subjects_parsed=[" . implode(', ', $subjects) . "]\n";

if (empty($subjects)) {
	echo "No subjects assigned => SS should see 0 records.\n";
	exit(0);
}

$in = implode(',', array_fill(0, count($subjects), '?'));
$sql = "SELECT COUNT(*) c
	FROM users u
	JOIN teacher_registration_steps s ON s.user_id=u.id
	LEFT JOIN teacher_specializations sp ON sp.user_id=u.id
	WHERE u.role=2
	  AND s.registration_completed=1
	  AND s.review_status=?
	  AND UPPER(sp.specialization) IN ({$in})";
$params = array_merge([$type], $subjects);
$count = q($mysqli, $sql, $params)->fetch_assoc()['c'];
echo "matching {$type} records={$count}\n";

