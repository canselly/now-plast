<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $c = $GLOBALS['config']['db'];
    if ($c['driver'] === 'sqlite') {
        $dir = dirname($c['sqlite_path']);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $c['sqlite_path']);
    } else {
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['user'], $c['pass']);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function db_driver(): string
{
    return $GLOBALS['config']['db']['driver'];
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function val(string $sql, array $params = [])
{
    $r = q($sql, $params)->fetchColumn();
    return $r === false ? null : $r;
}

/** Tablo/kolon adları yalnızca koddan gelir, kullanıcı girdisinden değil. */
function db_insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = "INSERT INTO $table (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function db_update(string $table, int $id, array $data): void
{
    if (!$data) {
        return;
    }
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
    $params = array_values($data);
    $params[] = $id;
    q("UPDATE $table SET $set WHERE id = ?", $params);
}

function db_installed(): bool
{
    try {
        val('SELECT COUNT(*) FROM settings');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function in_placeholders(array $values): string
{
    return implode(',', array_fill(0, max(1, count($values)), '?'));
}
