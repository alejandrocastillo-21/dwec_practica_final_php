<?php
declare(strict_types=1);

namespace src\Models;
use src\Core\Database;
use InvalidArgumentException;

/**
 * Operaciones comunes a todos los modelos (CRUD básico).
 * Es abstracta: no tiene sentido hacer "new BaseModel", solo heredar de ella.
 */
abstract class BaseModel
{
    // "protected" + constructor promocionado (PHP 8): declara y asigna las propiedades en un solo paso.
    // Los hijos (User, Post...) pueden usarlas, pero desde fuera no son accesibles.
    public function __construct(
        protected Database $db,
        protected string $table
    ) {
        $this->assertIdentifier($table);
    }

    /** Todos los registros, o null si la tabla está vacía. */
    public function getAll(): ?array
    {
        return $this->db->fetchAll("SELECT * FROM `{$this->table}`");
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `{$this->table}` WHERE id = :id", ['id' => $id]);
    }

    public function delete(int $id): void
    {
        // No hace falta añadir nada a Database: query() ya ejecuta cualquier SQL.
        $this->db->query("DELETE FROM `{$this->table}` WHERE id = :id", ['id' => $id]);
    }

    /**
     * Inserta un registro a partir de ['campo' => valor, ...] y devuelve su id nuevo.
     * Ejemplo: insert(['title' => 'Vendo libro', 'user_id' => 3, ...])
     * genera:  INSERT INTO `posts` (`title`, `user_id`) VALUES (:title, :user_id)
     */
    public function insert(array $data): int
    {
        $columns = $this->validatedColumns($data);

        $fields       = implode(', ', array_map(fn($c) => "`$c`", $columns));  // `title`, `user_id`
        $placeholders = implode(', ', array_map(fn($c) => ":$c", $columns));   // :title, :user_id

        $this->db->query("INSERT INTO `{$this->table}` ($fields) VALUES ($placeholders)", $data);

        return $this->db->lastInsertId();
    }

    /**
     * Modifica el registro $id con los pares ['campo' => valor].
     * Genera: UPDATE `posts` SET `title` = :title, `body` = :body WHERE id = :__id
     */
    public function update(int $id, array $data): void
    {
        $columns = $this->validatedColumns($data);

        $sets = implode(', ', array_map(fn($c) => "`$c` = :$c", $columns));

        // El id del WHERE va con un nombre de placeholder poco habitual (":__id") para que
        // no choque con una columna llamada "id" que venga dentro de $data.
        $data['__id'] = $id;

        $this->db->query("UPDATE `{$this->table}` SET $sets WHERE id = :__id", $data);
    }

    /**
     * PUNTO DELICADO: los valores van siempre como parámetros, pero los NOMBRES de tabla
     * y de columna no se pueden parametrizar en SQL, así que hay que meterlos en el texto
     * de la consulta. Si alguna vez una clave del array viniera de un formulario sin filtrar
     * (p. ej. $_POST completo), un atacante podría inyectar SQL a través del nombre del campo.
     * Por eso solo aceptamos identificadores "limpios": letras, números y guion bajo.
     */
    private function assertIdentifier(string $name): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Identificador SQL no válido: $name");
        }
    }

    /** Comprueba que $data no está vacío y que todos sus campos son identificadores válidos. */
    private function validatedColumns(array $data): array
    {
        if ($data === []) {
            throw new InvalidArgumentException('No hay datos que guardar.');
        }
        $columns = array_keys($data);
        foreach ($columns as $column) {
            $this->assertIdentifier((string) $column);
        }
        return $columns;
    }
}