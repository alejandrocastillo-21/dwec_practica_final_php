<h1>Iniciar sesión</h1>

<?php if ($error): ?>
    <p class="flash error"><?= e($error) ?></p>
<?php endif; ?>

<form action="<?= e(url('Auth', 'login')) ?>" method="post">
    <?= csrf_field() ?>
    <label>Email
        <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
    </label>
    <label>Contraseña
        <input type="password" name="password" required>
    </label>
    <button type="submit">Entrar</button>
</form>
<p>¿No tienes cuenta? <a href="<?= e(url('Auth', 'showRegisterForm')) ?>">Regístrate</a></p>