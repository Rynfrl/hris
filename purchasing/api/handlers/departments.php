<?php
/**
 * api/handlers/departments.php
 * Daftar departemen dari modul HR (tabel `divisi`), read-only,
 * dipakai untuk dropdown "Departemen" di form Purchase Requisition.
 */

function h_departments_list(PDO $pdo): void
{
    $rows = $pdo->query(
        'SELECT id_divisi AS id, CAST(id_divisi AS CHAR) AS code, divisi AS name
         FROM divisi ORDER BY divisi'
    )->fetchAll();
    pur_json_response(['data' => array_map(function ($r) {
        $r['id'] = (int) $r['id'];
        return $r;
    }, $rows)]);
}
