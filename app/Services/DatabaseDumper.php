<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Logical MySQL dump/restore in pure PHP using the application's own
 * PDO connection. Exists because the mysqldump/mysql client binaries
 * cannot open sockets when PHP runs as the SYSTEM service account
 * (WAMP Apache): error 2004/10106. PDO (mysqlnd) connects fine there.
 *
 * Limitations: base tables only (no views/triggers/routines) and no
 * true binary (BLOB) columns — none of which this app uses.
 */
class DatabaseDumper
{
    private const ROWS_PER_INSERT = 200;

    public function dump(): string
    {
        $pdo = DB::connection()->getPdo();
        $database = (string) DB::connection()->getDatabaseName();

        $out = [];
        $out[] = '-- Respaldo Carto generado el '.now()->format('Y-m-d H:i');
        $out[] = 'SET NAMES utf8mb4;';
        $out[] = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($this->tables($pdo, $database) as $table) {
            $out[] = '';
            $out[] = "DROP TABLE IF EXISTS `{$table}`;";
            $out[] = $this->createTable($pdo, $table).';';
            $out[] = $this->insertStatements($pdo, $table);
        }

        $out[] = '';
        $out[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", array_filter($out, fn ($part) => $part !== ''))."\n";
    }

    public function restore(string $sql): void
    {
        $pdo = DB::connection()->getPdo();

        foreach ($this->splitStatements($sql) as $statement) {
            $statement = trim($statement);

            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }

            try {
                $pdo->exec($statement);
            } catch (\Throwable $e) {
                throw new \RuntimeException('La restauración falló: '.$e->getMessage());
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function tables(PDO $pdo, string $database): array
    {
        $stmt = $pdo->query("SHOW FULL TABLES FROM `{$database}` WHERE TABLE_TYPE LIKE 'BASE TABLE'");
        $tables = [];

        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
            $tables[] = (string) $row[0];
        }

        sort($tables);

        return $tables;
    }

    private function createTable(PDO $pdo, string $table): string
    {
        $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);

        return (string) ($row['Create Table'] ?? throw new \RuntimeException("No se pudo leer la tabla {$table}."));
    }

    private function insertStatements(PDO $pdo, string $table): string
    {
        $columns = [];
        foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = '`'.$column['Field'].'`';
        }

        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

        if ($count === 0) {
            return '';
        }

        $columnList = implode(', ', $columns);
        $offset = 0;
        $statements = [];

        while ($offset < $count) {
            $rows = $pdo->query(
                "SELECT * FROM `{$table}` LIMIT {$offset}, ".self::ROWS_PER_INSERT
            )->fetchAll(PDO::FETCH_NUM);

            if ($rows === []) {
                break;
            }

            $values = [];

            foreach ($rows as $row) {
                $values[] = '('.implode(', ', array_map(
                    fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    $row
                )).')';
            }

            $statements[] = "INSERT INTO `{$table}` ({$columnList}) VALUES\n".implode(",\n", $values).';';
            $offset += self::ROWS_PER_INSERT;
        }

        return implode("\n", $statements);
    }

    /**
     * Split SQL on semicolons outside quotes/backticks/comments.
     *
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $quote = null;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($quote === null && $char === '-' && $next === '-' && ($sql[$i + 2] ?? '') === ' ') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($quote === null && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $len && ! ($sql[$i] === '*' && ($sql[$i + 1] ?? '') === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            if ($quote !== null) {
                $current .= $char;

                if ($char === '\\' && $i + 1 < $len) {
                    $current .= $sql[++$i];
                    continue;
                }

                if ($char === $quote && $next === $quote) {
                    $current .= $next;
                    $i++;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $current .= $char;
                continue;
            }

            if ($char === ';') {
                $statements[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}
