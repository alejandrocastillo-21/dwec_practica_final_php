<h1><?= e($heading) ?></h1>

<?php foreach ($errors as $error): ?>
    <p class="flash error"><?= e($error) ?></p>
<?php endforeach; ?>

<form action="<?= e($formAction) ?>" method="post">
    <?= csrf_field() ?>
    <label>Título
        <input name="title" value="<?= e($post['title'] ?? '') ?>" maxlength="150" required>
    </label>
    <label>Categoría
        <select name="category" required>
            <option value="">-- Elige una --</option>
            <?php foreach (\src\Models\Post::CATEGORIES as $category): ?>
                <option value="<?= e($category) ?>" <?= ($post['category'] ?? '') === $category ? 'selected' : '' ?>>
                    <?= e($category) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Texto
        <textarea name="body" rows="6" required><?= e($post['body'] ?? '') ?></textarea>
    </label>
    <button type="submit">Guardar</button>
    <a href="<?= e(url('Post', 'index')) ?>">Cancelar</a>
</form>