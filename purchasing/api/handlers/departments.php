<?php
/**
 * api/handlers/departments.php
 * Daftar departemen dari master `departments` (didukung view `divisi`),
 * dipakai untuk dropdown "Departemen" di form Purchase Requisition.
 */

function h_departments_list(PDO $pdo): void
{
    $rows = $pdo->query(
        'SELECT department_id AS id, department_code AS code, department_name AS name
         FROM departments WHERE is_active = 1 ORDER BY department_name'
    )->fetchAll();
    pur_json_response(['data' => array_map(function ($r) {
        $r['id'] = (int) $r['id'];
        return $r;
    }, $rows)]);
}
