<?php
declare(strict_types=1);

namespace src\Models;

use src\Core\Database;

class Post extends BaseModel
{
    // Lista cerrada de categorías: evita erratas ("Proyecto" / "proyectos") y facilita la búsqueda
    public const CATEGORIES = ['Compra-venta', 'Proyectos', 'Objetos perdidos', 'Otros'];

    public function __construct(Database $db)
    {
        parent::__construct($db, 'posts');
    }

    /**
     * Listado de anuncios (los más recientes primero), con el nombre del autor.
     * Si $term no está vacío, filtra por título O categoría con LIKE.
     */
    public function search(?string $term = null): ?array
    {
        // JOIN: une cada post con la fila de su autor en "users" mediante user_id
        $sql = 'SELECT posts.*, users.username
                FROM posts
                JOIN users ON users.id = posts.user_id';
        $params = [];

        if ($term !== null && $term !== '') {
            // En LIKE, % y _ son comodines. Si el usuario escribe "%" no queremos que
            // coincida con todo, así que los escapamos con una barra invertida (el carácter
            // de escape por defecto de MySQL en LIKE). La barra también se escapa a sí misma.
            $escaped = addcslashes($term, '%_\\');
            $like    = '%' . $escaped . '%';   // %texto% = "contiene texto"

            // Dos nombres de parámetro distintos aunque el valor sea el mismo
            $sql .= ' WHERE posts.title LIKE :title OR posts.category LIKE :category';
            $params = ['title' => $like, 'category' => $like];
        }

        $sql .= ' ORDER BY posts.created_at DESC, posts.id DESC';

        return $this->db->fetchAll($sql, $params);
    }

    /** Un anuncio junto con el nombre de su autor, o null si no existe. */
    public function findWithAuthor(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT posts.*, users.username
             FROM posts
             JOIN users ON users.id = posts.user_id
             WHERE posts.id = :id',
            ['id' => $id]
        );
    }
}