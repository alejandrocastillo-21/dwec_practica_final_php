<h1>Crear cuenta</h1>

<?php foreach ($errors as $error): ?>
    <p class="flash error"><?= e($error) ?></p>
<?php endforeach; ?>

<form action="<?= e(url('Auth', 'register')) ?>" method="post">
    <?= csrf_field() ?>
    <label>Usuario
        <input name="username" value="<?= e($old['username'] ?? '') ?>" required>
    </label>
    <label>Email
        <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
    </label>
    <label>Contraseña (mínimo 8 caracteres)
        <input type="password" name="password" minlength="8" required>
    </label>
    <label>Repite la contraseña
        <input type="password" name="confirm" required>
    </label>
    <button type="submit">Registrarme</button>
</form>
<p>¿Ya tienes cuenta? <a href="<?= e(url('Auth', 'showLoginForm')) ?>">Inicia sesión</a></p>