<?php
declare(strict_types=1);

namespace src\Core;
use PDO;
use PDOStatement;

/**
 * Capa de abstracción de datos. Es la ÚNICA clase que conoce PDO:
 * los modelos le piden consultas a ella y nunca llaman a $pdo->prepare().
 */
class Database
{
    private PDO $pdo;

    public function __construct()
    {
        $config = require __DIR__ . '/../../php/config.php';
        $db     = $config['db'];

        $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";

        $this->pdo = new PDO($dsn, $db['user'], $db['pass'], [
            // Los errores SQL lanzan PDOException en lugar de fallar en silencio
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // fetch() devuelve arrays asociativos (['title' => '...']) y no duplicados numéricos
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Prepara y ejecuta una consulta. Sirve para SELECT, INSERT, UPDATE y DELETE.
     *
     * Los valores NUNCA se concatenan en el SQL: se envían aparte en $params y
     * la base de datos los trata siempre como datos, no como código (anti inyección SQL).
     * $params admite dos estilos:
     *   query('... WHERE id = ?',  [1])
     *   query('... WHERE id = :id', ['id' => 1])
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Devuelve todas las filas, o null si no hay ninguna. */
    public function fetchAll(string $sql, array $params = []): ?array
    {
        $rows = $this->query($sql, $params)->fetchAll();
        // fetchAll() devuelve [] cuando no hay resultados; lo convertimos a null
        // para que quien llame use una única comprobación: if ($rows === null)
        return $rows === [] ? null : $rows;
    }

    /** Devuelve solo la primera fila, o null si no hay ninguna. */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        // fetch() devuelve false (no null) cuando no hay más filas
        return $row === false ? null : $row;
    }

    /** Id autogenerado por el último INSERT (lo necesita BaseModel::insert). */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}