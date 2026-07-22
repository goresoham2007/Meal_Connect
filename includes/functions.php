<?php
// Small shared helpers used across the site.

function h($str){
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function require_student_login(){
    if (empty($_SESSION['student_id'])) {
        header('Location: /mealconnect/student/login.php');
        exit;
    }
}

function require_owner_login(){
    if (empty($_SESSION['owner_id'])) {
        header('Location: /mealconnect/owner/login.php');
        exit;
    }
}

function initials($name){
    $parts = preg_split('/\s+/', trim($name));
    $init = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') $init .= strtoupper($p[0]);
    }
    return $init ?: 'U';
}

function badge_class($veg_type){
    if ($veg_type === 'Pure Veg') return 'veg';
    if ($veg_type === 'Non-Veg Special') return 'nonveg';
    return 'both';
}

function stars_html($rating){
    $rating = round($rating * 2) / 2; // nearest half
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5;
    $out = str_repeat('★', $full);
    if ($half) $out .= '½';
    return $out;
}

function days_between($from, $to){
    $d1 = new DateTime($from);
    $d2 = new DateTime($to);
    return (int)$d1->diff($d2)->format('%r%a');
}

function gen_txn_id(){
    return 'TXN' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 10));
}
