<?php /** @var string $content HTML de la vista concreta, capturado por View::render() */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tablón de clase</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header>
    <a href="<?= e(url()) ?>" class="brand">📌 Tablón de clase</a>
    <nav>
        <?php if (\src\Core\Session::isLoggedIn()): ?>
            <span><?= e(\src\Core\Session::userEmail()) ?></span>
            <form action="<?= e(url('Auth', 'logout')) ?>" method="post" class="inline">
                <?= csrf_field() ?>
                <button type="submit">Cerrar sesión</button>
            </form>
        <?php else: ?>
            <a href="<?= e(url('Auth', 'showLoginForm')) ?>">Iniciar sesión</a>
            <a href="<?= e(url('Auth', 'showRegisterForm')) ?>">Registrarse</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <?php foreach (\src\Core\Session::pullFlash() as $flash): ?>
        <p class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
    <?php endforeach; ?>

    <?= $content /* ya es HTML nuestro y seguro: aquí NO se escapa */ ?>
</main>
</body>
</html>