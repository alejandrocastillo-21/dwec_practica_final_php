<?php
declare(strict_types=1);

namespace src\Models;

use src\Core\Database;

class Comment extends BaseModel
{
    public function __construct(Database $db)
    {
        parent::__construct($db, 'comments');
    }

    /**
     * Comentarios de un anuncio (los más antiguos primero, como una conversación),
     * con el nombre de su autor. Esta es la parte "muchos" de la relación 1:N:
     * todos los comentarios cuyo post_id coincide con el del anuncio.
     */
    public function findByPost(int $postId): ?array
    {
        return $this->db->fetchAll(
            'SELECT comments.*, users.username
             FROM comments
             JOIN users ON users.id = comments.user_id
             WHERE comments.post_id = :post_id
             ORDER BY comments.created_at ASC, comments.id ASC',
            ['post_id' => $postId]
        );
    }
}