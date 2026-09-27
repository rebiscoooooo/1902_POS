<?php
function sanitize($data) { return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8'); }
function formatCurrency($amount, $currency = ' ') { return $currency . number_format((float)$amount, 2, '.', ','); }
function getSetting($pdo, $key) { $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?"); $stmt->execute([$key]); return $stmt->fetchColumn() ?: ''; }
function logActivity($pdo, $user_id, $activity_type, $description) { 
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null; 
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)"); 
        $stmt->execute([$user_id, $activity_type, $description, $ip]); 
    } catch (\PDOException $e) {
        // Ignore logging errors so they don't break the main flow
    }
}
function jsonResponse($success, $message = '', $data = []) { header('Content-Type: application/json'); echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]); exit; }