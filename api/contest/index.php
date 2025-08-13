<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// This file is not intended to be called directly; use /api/contest/{id}
jsonResponse(['success' => false, 'message' => 'Missing contest id'], 400);
