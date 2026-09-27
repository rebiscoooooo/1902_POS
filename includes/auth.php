<?php
session_start();
function isLoggedIn() { return isset($_SESSION['user_id']); }
function requireLogin() { if (!isLoggedIn()) { header("Location: /1902_pos/login.php"); exit; } }
function hasRole($roles) { if (!isLoggedIn()) return false; if (is_array($roles)) return in_array($_SESSION['role'], $roles); return $_SESSION['role'] === $roles; }
function requireRole($roles) { requireLogin(); if (!hasRole($roles)) die("<h1>Access Denied</h1>"); }
function isSwitchedUser() { return isset($_SESSION['original_admin_id']); }
function getOriginalAdminId() { return $_SESSION['original_admin_id'] ?? null; }