<h1>Anuncios</h1>

<!-- Un formulario GET reemplaza la query string de la URL, por eso controller y action
     van como campos ocultos: así la petición sigue llegando a Post::index -->
<form action="<?= e(url('Post', 'index')) ?>" method="get" class="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título o categoría">
    <button type="submit">Buscar</button>
</form>

<?php if (\src\Core\Session::isLoggedIn()): ?>
    <p><a href="<?= e(url('Post', 'create')) ?>">+ Nuevo anuncio</a></p>
<?php endif; ?>

<?php if ($posts === []): ?>
    <p>No hay anuncios<?= $q !== '' ? ' que coincidan con «' . e($q) . '»' : '' ?>.</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <article class="post">
        <h2><a href="<?= e(url('Post', 'show', ['id' => $post['id']])) ?>"><?= e($post['title']) ?></a></h2>
        <p class="meta">
            <span class="tag"><?= e($post['category']) ?></span>
            por <?= e($post['username']) ?> · <?= e(format_date($post['created_at'])) ?>
        </p>
    </article>
<?php endforeach; ?>