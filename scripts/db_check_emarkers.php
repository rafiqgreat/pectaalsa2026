<?php
// Quick diagnostic script for Emarkers listing filters.
// Run: php scripts/db_check_emarkers.php

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'pectaalsa2026db2';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
	fwrite(STDERR, "DB connect error: {$mysqli->connect_error}\n");
	exit(1);
}

function q($mysqli, $sql) {
	$res = $mysqli->query($sql);
	if ($res === false) {
		fwrite(STDERR, "SQL error: {$mysqli->error}\nSQL: {$sql}\n");
		exit(2);
	}
	return $res;
}

// Determine which role column exists.
$hasRole = q($mysqli, "SHOW COLUMNS FROM users LIKE 'role'")->num_rows > 0;
$hasRoleId = q($mysqli, "SHOW COLUMNS FROM users LIKE 'role_id'")->num_rows > 0;
$roleCol = $hasRole ? 'role' : ($hasRoleId ? 'role_id' : 'role');

echo "users role column: {$roleCol}\n";

$usersRole2 = q($mysqli, "SELECT COUNT(*) c FROM users WHERE `{$roleCol}`=2")->fetch_assoc()['c'];
echo "users with role=2: {$usersRole2}\n";

$hasSteps = q($mysqli, "SHOW TABLES LIKE 'teacher_registration_steps'")->num_rows > 0;
echo "teacher_registration_steps table: " . ($hasSteps ? 'yes' : 'no') . "\n";
if ($hasSteps) {
	$hasReview = q($mysqli, "SHOW COLUMNS FROM teacher_registration_steps LIKE 'review_status'")->num_rows > 0;
	echo "teacher_registration_steps.review_status: " . ($hasReview ? 'yes' : 'no') . "\n";
	$hasRejectionReason = q($mysqli, "SHOW COLUMNS FROM teacher_registration_steps LIKE 'rejection_reason'")->num_rows > 0;
	$hasRejectedAt = q($mysqli, "SHOW COLUMNS FROM teacher_registration_steps LIKE 'rejected_at'")->num_rows > 0;
	$hasUpdatedAt = q($mysqli, "SHOW COLUMNS FROM teacher_registration_steps LIKE 'updated_at'")->num_rows > 0;
	echo "teacher_registration_steps.rejection_reason: " . ($hasRejectionReason ? 'yes' : 'no') . "\n";
	echo "teacher_registration_steps.rejected_at: " . ($hasRejectedAt ? 'yes' : 'no') . "\n";
	echo "teacher_registration_steps.updated_at: " . ($hasUpdatedAt ? 'yes' : 'no') . "\n";

	$completed = q($mysqli, "SELECT COUNT(*) c FROM teacher_registration_steps WHERE registration_completed=1")->fetch_assoc()['c'];
	echo "steps with registration_completed=1: {$completed}\n";

	if ($hasReview) {
		$pending = q($mysqli, "SELECT COUNT(*) c FROM teacher_registration_steps WHERE registration_completed=1 AND review_status='pending'")->fetch_assoc()['c'];
		$approved = q($mysqli, "SELECT COUNT(*) c FROM teacher_registration_steps WHERE registration_completed=1 AND review_status='approved'")->fetch_assoc()['c'];
		$rejected = q($mysqli, "SELECT COUNT(*) c FROM teacher_registration_steps WHERE registration_completed=1 AND review_status='rejected'")->fetch_assoc()['c'];
		echo "steps pending/approved/rejected (completed=1): {$pending}/{$approved}/{$rejected}\n";
	}
}

// Check evaluator specialization coverage.
$hasSpec = q($mysqli, "SHOW TABLES LIKE 'teacher_specializations'")->num_rows > 0;
echo "teacher_specializations table: " . ($hasSpec ? 'yes' : 'no') . "\n";
if ($hasSpec) {
	$specCount = q($mysqli, "SELECT COUNT(*) c FROM teacher_specializations")->fetch_assoc()['c'];
	echo "teacher_specializations rows: {$specCount}\n";
	$specDistinct = q($mysqli, "SELECT COUNT(DISTINCT specialization) c FROM teacher_specializations")->fetch_assoc()['c'];
	echo "distinct specializations: {$specDistinct}\n";
	$specRows = q($mysqli, "SELECT DISTINCT specialization FROM teacher_specializations ORDER BY specialization ASC LIMIT 20");
	echo "sample specializations:\n";
	while ($r = $specRows->fetch_assoc()) {
		echo "- " . (string) $r['specialization'] . "\n";
	}

	$trimIssues = q($mysqli, "SELECT COUNT(*) c FROM teacher_specializations WHERE specialization <> TRIM(specialization)")->fetch_assoc()['c'];
	echo "specializations with trim issues: {$trimIssues}\n";
}

// Pending counts by specialization (what admin listing roughly shows).
if ($hasSteps && $hasSpec) {
	$sql = "SELECT sp.specialization, COUNT(*) c
		FROM users u
		JOIN teacher_registration_steps s ON s.user_id=u.id
		LEFT JOIN teacher_specializations sp ON sp.user_id=u.id
		WHERE u.`{$roleCol}`=2 AND s.registration_completed=1 AND s.review_status='pending'
		GROUP BY sp.specialization
		ORDER BY c DESC";
	$res = q($mysqli, $sql);
	echo "pending counts by specialization:\n";
	while ($r = $res->fetch_assoc()) {
		echo "- " . (string) ($r['specialization'] ?? '(null)') . ": " . (int) $r['c'] . "\n";
	}
}

echo "OK\n";

// Optional: show one Subject Specialist row (role=18) subjects value.
$ss = q($mysqli, "SELECT id, name, subjects FROM users WHERE `{$roleCol}`=18 ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($ss) {
	echo "sample SS user id={$ss['id']} name={$ss['name']} subjects_raw={$ss['subjects']}\n";
}
