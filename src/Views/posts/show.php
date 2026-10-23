<article class="post">
    <h1><?= e($post['title']) ?></h1>
    <p class="meta">
        <span class="tag"><?= e($post['category']) ?></span>
        por <?= e($post['username']) ?> · <?= e(format_date($post['created_at'])) ?>
    </p>

    <!-- Orden importante: PRIMERO e() (neutraliza el HTML malicioso) y DESPUÉS nl2br()
         (convierte los saltos de línea en <br>). Al revés, se escaparían los <br> -->
    <p><?= nl2br(e($post['body'])) ?></p>

    <?php if ($isOwner): ?>
        <div class="actions">
            <a href="<?= e(url('Post', 'edit', ['id' => $post['id']])) ?>">Editar</a>
            <form action="<?= e(url('Post', 'destroy', ['id' => $post['id']])) ?>" method="post" class="inline"
                  onsubmit="return confirm('¿Seguro que quieres borrar este anuncio?');">
                <?= csrf_field() ?>
                <button type="submit">Borrar</button>
            </form>
        </div>
    <?php endif; ?>
</article>

<section id="comentarios">
    <h2>Comentarios (<?= count($comments) ?>)</h2>

    <?php if ($comments === []): ?>
        <p>Todavía no hay comentarios.</p>
    <?php endif; ?>

    <?php foreach ($comments as $comment): ?>
        <div class="comment">
            <p class="meta">
                <strong><?= e($comment['username']) ?></strong> · <?= e(format_date($comment['created_at'])) ?>
            </p>
            <p><?= nl2br(e($comment['body'])) ?></p>

            <?php if ($currentUserId !== null && (int) $comment['user_id'] === $currentUserId): ?>
                <form action="<?= e(url('Comment', 'destroy', ['id' => $comment['id']])) ?>" method="post" 
                            --onsubmit="return confirm('¿Borrar este comentario?');">
                        <?= csrf_field() ?>
                    <button type="submit">Borrar</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if ($currentUserId !== null): ?>
        <form action="<?= e(url('Comment', 'store', ['post_id' => $post['id']])) ?>" method="post">
            <?= csrf_field() ?>
            <label>Añadir un comentario
                <textarea name="body" rows="3" maxlength="1000" required></textarea>
            </label>
            <button type="submit">Comentar</button>
        </form>
    <?php else: ?>
        <p><a href="<?= e(url('Auth', 'showLoginForm')) ?>">Inicia sesión</a> para comentar.</p>
    <?php endif; ?>
</section>

<p><a href="<?= e(url('Post', 'index')) ?>">← Volver al listado</a></p>