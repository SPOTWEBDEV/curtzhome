<?php
require_once __DIR__ . '/../config.php';



$db = getDB();

// ── GET: list all plans ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $db->query("SELECT * FROM investment_plans ORDER BY sort_order ASC")->fetchAll();
    foreach ($rows as &$r) {
        $r['perks'] = json_decode($r['perks'] ?? '[]', true);
    }
    jsonSuccess(['plans' => $rows]);
}

// ── POST: create / update / delete ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = requireAuth();
    if ($auth['role'] !== 'admin') jsonError('Forbidden', 403);
    $d = input();
    $action = $d['action'] ?? '';

    if ($action === 'delete') {
        $db->prepare("DELETE FROM investment_plans WHERE id = ?")->execute([$d['id']]);
        jsonSuccess(['message' => 'Deleted.']);
    }

    $perks = json_encode($d['perks'] ?? []);

    if ($action === 'create') {
        // Check unique id
        $check = $db->prepare("SELECT id FROM investment_plans WHERE id = ?");
        $check->execute([$d['id']]);
        if ($check->fetch()) jsonError('Plan ID already exists.');

        $maxOrder = $db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM investment_plans")->fetchColumn();
        $db->prepare("INSERT INTO investment_plans
            (id, label, min_usd, rate, tenure_min, tenure_max, freq_default, btn_label, featured, perks, sort_order)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)")
          ->execute([$d['id'],$d['label'],$d['min_usd'],$d['rate'],
                     $d['tenure_min'],$d['tenure_max'],$d['freq_default'],
                     $d['btn_label'],$d['featured'],$perks,$maxOrder]);
        jsonSuccess(['message' => 'Plan created.'], 201);
    }

    if ($action === 'update') {
        $db->prepare("UPDATE investment_plans SET
            label=?, min_usd=?, rate=?, tenure_min=?, tenure_max=?,
            freq_default=?, btn_label=?, featured=?, perks=?
            WHERE id=?")
          ->execute([$d['label'],$d['min_usd'],$d['rate'],
                     $d['tenure_min'],$d['tenure_max'],$d['freq_default'],
                     $d['btn_label'],$d['featured'],$perks,$d['id']]);
        jsonSuccess(['message' => 'Plan updated.']);
    }

    jsonError('Unknown action.');
}
?>