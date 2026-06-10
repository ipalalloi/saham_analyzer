<?php
class Database
{
    private static ?PDO $pdo = null;
    public static function init(array $db): void
    {
        if (self::$pdo !== null) return;
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset'] ?? 'utf8mb4');
        self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    public static function pdo(): PDO { return self::$pdo; }
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql); $stmt->execute($params);
        $row = $stmt->fetch(); return $row ?: null;
    }
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql); $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql); $stmt->execute($params);
        return $stmt->rowCount();
    }
    public static function lastId(): string { return self::pdo()->lastInsertId(); }
}
