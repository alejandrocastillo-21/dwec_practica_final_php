# Plan de pruebas: Tablón de anuncios

## 1. Entorno de pruebas

| Dato | Valor |
|---|---|
| Aplicación | http://localhost:8080 |
| Base de datos (phpMyAdmin) | http://localhost:8081 |
| Stack | PHP 8.3 + Apache + MySQL 8.4 (Docker) |
| Navegador | chrome |

## 2. Cómo se han generado los casos (uso de IA)

Los casos se generaron con Claude. El primer intento con un prompt genérico
("Genera 10 casos de prueba para un sistema de posts y comentarios") dio casos
vagos, sin valores concretos ni mensajes esperados. Se mejoró incluyendo las
reglas reales de la aplicación. Prompt final:

> Actúa como un tester de QA. Voy a probar manualmente una aplicación PHP (tablón
> de anuncios con usuarios, anuncios, comentarios y buscador) con las reglas de la
> tabla siguiente. Genera casos de prueba agrupados por funcionalidad: registro,
> login/logout, anuncios, permisos, buscador, comentarios, CSRF y rutas. Para cada
> caso indica ID, tipo (normal, límite o error), pasos exactos y resultado esperado
> concreto (mensaje, código HTTP, redirección y estado de la base de datos).
> Incluye valores límite justo en, por debajo y por encima de cada límite, intentos
> de saltarse la validación del navegador, casos de seguridad (XSS, inyección SQL,
> CSRF) y casos de autorización (acceder a recursos de otro usuario). Formato:
> tabla Markdown con columnas ID, Tipo, Pasos, Resultado esperado.

Los casos generados se revisaron y ajustaron a mano para que coincidan con los
mensajes y comportamientos reales de la aplicación.

## 3. Reglas de negocio que se prueban

| Elemento | Regla |
|---|---|
| Usuario | 3 a 50 caracteres: letras, números y `_`. Único |
| Email | Formato válido, máximo 120 caracteres. Único. Se guarda en minúsculas |
| Contraseña | Mínimo 8 caracteres. Se guarda con `password_hash()` |
| Título del anuncio | 3 a 150 caracteres |
| Categoría | Solo: Compra-venta, Proyectos, Objetos perdidos, Otros |
| Texto del anuncio | Obligatorio, máximo 5000 caracteres |
| Comentario | Obligatorio, máximo 1000 caracteres |
| Permisos | Solo el autor edita o borra su anuncio y sus comentarios |
| Escritura | Todas las acciones que modifican datos son POST con token CSRF |

## 4. Preparación

1. Estado inicial: base de datos vacía (tablas creadas con `schema.sql`).
2. Crear dos usuarios (casos REG-01 y REG-04): **Ana** (`ana@test.com`) y **Bea**
   (`bea@test.com`), ambas con contraseña `clave1234`.
3. Usar **dos navegadores** (o uno normal y uno de incógnito) para tener dos
   sesiones a la vez.
4. Donde se escribe `{id de Ana}`, usar el id real de un anuncio creado por Ana.

### Cómo manipular formularios con F12

Botón derecho sobre el campo > Inspeccionar. Para editar un atributo, doble clic
sobre él. Para añadir un campo, clic derecho sobre el `<form>` > Edit as HTML.

- Quitar `required`, `minlength`, `maxlength` o `type="email"` permite que el
  navegador envíe datos que normalmente bloquearía. Así se prueba el servidor.
- **No recargar la página** entre la manipulación y el envío, o el formulario se
  restablece.

## 5. Leyenda

| Símbolo | Significado |
|---|---|
| ⬜ | Pendiente |
| ✅ | Pasa: el resultado obtenido coincide con el esperado |
| ❌ | Falla: anotar en la sección 7 |

---

## 6. Casos de prueba

### 6.1 Registro (REG)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| REG-01 | Normal | Ir a `/register`. Usuario `ana`, email `ana@test.com`, contraseña y repetición `clave1234`. Enviar | Redirige a `/login` con mensaje verde «Cuenta creada. Ya puedes iniciar sesión.». En la BD existe el usuario y su campo `password` empieza por `$2y$` (hash, no texto plano) | |   ✅|
| REG-02 | Error | Registrar `otra` con el email `ana@test.com` (ya existe) | Error «Ese email o nombre de usuario ya está registrado.». No se crea el usuario. El formulario conserva usuario y email, pero no las contraseñas | |  ✅ |
| REG-03 | Error | Registrar el usuario `ana` (ya existe) con el email `otro@test.com` | El mismo error del caso anterior. No se crea el usuario | |  ✅ |
| REG-04 | Normal | Registrar `bea` con email `Bea@Test.COM` | Se crea. En la BD el email está en minúsculas (`bea@test.com`) | |  ✅ |
| REG-05 | Límite | Contraseña de 7 caracteres (`clave12`). Quitar `minlength` con F12 para llegar al servidor | Error «La contraseña debe tener al menos 8 caracteres.». No se crea el usuario | |  ✅ |
| REG-06 | Límite | Contraseña de exactamente 8 caracteres (`clave123`) con usuario nuevo | Se crea correctamente | |  ✅ |
| REG-07 | Error | Contraseña `clave1234` y repetición `clave9999` | Error «Las contraseñas no coinciden.». No se crea el usuario | |  ✅ |
| REG-08 | Límite | Usuario de 3 caracteres (`abc`) y otro de 50 caracteres | Ambos se crean correctamente | |  ✅ |
| REG-09 | Límite | Usuario de 2 caracteres (`ab`) y otro de 51 caracteres | Error «El usuario debe tener entre 3 y 50 caracteres (letras, números o _).». No se crean | |  ✅ |
| REG-10 | Seguridad | Usuario `ana lopez` (con espacio) y otro `<script>alert(1)</script>` | Error de usuario en ambos casos. No se ejecuta ningún script | |  ✅ |
| REG-11 | Error | Email `ana@` (cambiar `type="email"` a `type="text"` con F12) | Error «El email no es válido.». No se crea el usuario | |  ✅ |

### 6.2 Login y logout (LOG)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| LOG-01 | Normal | Ir a `/login` con `ana@test.com` / `clave1234` | Redirige a `/` con mensaje «Has iniciado sesión.». La cabecera muestra el email y el botón «Cerrar sesión» | |  ✅ |
| LOG-02 | Error | Email correcto, contraseña incorrecta | Error «Email o contraseña incorrectos.». No hay sesión | |  ✅ |
| LOG-03 | Error | Email que no existe (`nadie@test.com`) | El mismo mensaje exacto que en LOG-02 (no revela si el email existe) | |  ✅ |
| LOG-04 | Límite | Email `  ANA@Test.com  ` (con espacios y mayúsculas) y contraseña correcta | Inicia sesión correctamente | |  ✅ |
| LOG-05 | Normal | Con la sesión iniciada, escribir `/login` en la URL | Redirige a `/` | |  ✅ |
| LOG-06 | Normal | Pulsar «Cerrar sesión» | Redirige a `/`. La cabecera muestra «Iniciar sesión» y «Registrarse» | |  ✅ |
| LOG-07 | Error | Tras cerrar sesión, usar el botón «Atrás» del navegador y entrar en `/posts/create` | Redirige a `/login` con mensaje de que hay que iniciar sesión. No muestra el formulario | |  ✅ |
| LOG-08 | Error | Escribir `/logout` en la URL (petición GET) | «Método no permitido.» (HTTP 405). La sesión sigue abierta | |  ✅ |
| LOG-09 | Seguridad | Email `' OR '1'='1` (cambiar `type="email"` a `text` con F12) y cualquier contraseña | Error «Email o contraseña incorrectos.». No inicia sesión ni hay error SQL | |  ✅ |

### 6.3 Anuncios: creación y validación (ANU)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| ANU-01 | Normal | Como Ana, `/posts/create`: título «Vendo libro de Java», categoría Compra-venta, texto «Buen estado». Enviar | Redirige a `/posts/{id}` con «Anuncio publicado.». Se ve título, categoría, autor `ana` y fecha. En la BD, `user_id` es el de Ana | |  ✅ |
| ANU-02 | Límite | Crear un anuncio con título de 3 caracteres y otro con título de 150 | Ambos se crean | |  ✅ |
| ANU-03 | Límite | Título de 2 caracteres; título de 151 (quitar `maxlength`); título solo con espacios | Error «El título debe tener entre 3 y 150 caracteres.» en los tres casos. No se crea nada | |  ✅ |
| ANU-04 | Error | Enviar sin categoría; y otra vez con una categoría inventada (cambiar `value` de una opción a `Hackeo` con F12) | Error «Elige una categoría válida.» en ambos casos | |  ✅ |
| ANU-05 | Límite | Texto de exactamente 5000 caracteres | Se crea correctamente | |  ✅ |
| ANU-06 | Límite | Texto de 5001 caracteres; texto vacío; texto solo con espacios (quitar `required` con F12) | Error «El texto del anuncio es obligatorio (máximo 5000 caracteres).» en los tres casos | |  ✅ |
| ANU-07 | Normal | Provocar un error de validación (ej. título de 2 caracteres) con categoría y texto rellenos | El formulario vuelve a mostrarse con lo escrito conservado | |  ✅ |
| ANU-08 | Seguridad | Título `<script>alert('xss')</script>` y texto `<b>negrita</b>` | En listado y detalle se ven como texto literal. No sale ninguna alerta ni negrita | |  ✅ |
| ANU-09 | Normal | Texto con varias líneas, acentos, ñ y emojis (`Café ñandú 😀`) | Se conservan los saltos de línea y los caracteres tal cual, sin símbolos raros | |  ✅ |

### 6.4 Anuncios: detalle, edición y borrado (VER / EDI)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| VER-01 | Normal | Abrir `/posts` y pulsar el título de un anuncio | Muestra `/posts/{id}` con título, categoría, autor, fecha y texto | |  ✅ |
| VER-02 | Error | Abrir `/posts/99999` (no existe) | Error 404 «El anuncio no existe.» | |  ✅ |
| VER-03 | Error | Abrir `/posts/abc`, `/posts/0`, `/posts/-1` y `/posts/99999999999999999999` | Error 404 en los cuatro casos, sin errores de PHP en pantalla | |  ✅ |
| EDI-01 | Normal | Como autora, `/posts/edit/{id}`: cambiar el título y guardar | Redirige al detalle con «Anuncio actualizado.» y el cambio visible | |  ✅ |
| EDI-02 | Error | Al editar, dejar el título con 2 caracteres | Error de validación, formulario con los datos escritos, anuncio sin cambios en la BD | |  ✅ |
| EDI-03 | Seguridad | Al editar, añadir con F12 un campo oculto `user_id` con el id de otra persona y guardar | Se guarda el cambio, pero el autor del anuncio sigue siendo el original | |  ✅ |
| EDI-04 | Normal | Pulsar «Borrar» en un anuncio propio y aceptar el aviso | Redirige a `/posts` con «Anuncio eliminado.». El anuncio ya no aparece | |  ✅ |
| EDI-05 | Normal | Pulsar «Borrar» y cancelar el aviso | No ocurre nada, el anuncio sigue existiendo | |  ✅ |
| EDI-06 | Error | Con sesión, abrir `/posts/edit/99999` | Error 404 «El anuncio no existe.» | |  ✅ |

### 6.5 Permisos (PER)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| PER-01 | Error | Sin sesión, abrir `/posts/create` | Redirige a `/login` con «Debes iniciar sesión para publicar un anuncio.» | |  ✅ |
| PER-02 | Error | Pestaña 1: con sesión, abrir `/posts/create` y rellenarlo. Pestaña 2: cerrar sesión. Pestaña 1: enviar | No se crea el anuncio. Error 403 de token CSRF (la sesión ya no tiene token) | |  ✅ |
| PER-03 | Error | Sin sesión, abrir `/posts/edit/{id de Ana}` | Redirige a `/login` con mensaje. No muestra el formulario | |  ✅ |
| PER-04 | Error | Como Bea, abrir `/posts/edit/{id de Ana}` | Error 403 «No tienes permiso para hacer esto.» | |  ✅ |
| PER-05 | Seguridad | Como Bea, abrir `/posts/edit/{id de Bea}`. Con F12 cambiar el `action` del formulario a `/posts/update/{id de Ana}`. Enviar | Error 403. El anuncio de Ana no cambia | |  ✅ |
| PER-06 | Seguridad | Como Bea, en un anuncio propio con F12 cambiar el `action` del botón «Borrar» a `/posts/delete/{id de Ana}`. Enviar | Error 403. El anuncio de Ana sigue existiendo | |  ✅ |
| PER-07 | Normal | Ver un anuncio de Ana como Bea y sin sesión | No aparecen los botones «Editar» ni «Borrar». Con Ana sí aparecen | |  ✅ |
| PER-08 | Seguridad | Como Bea, en un comentario propio cambiar con F12 el `action` de «Borrar» a `/comments/delete/{id de comentario de Ana}`. Enviar | Error 403. El comentario de Ana sigue existiendo | |  ✅ |

### 6.6 Buscador (BUS)

Preparar antes: anuncios «Vendo libro de Java» (Compra-venta), «Busco grupo para proyecto» (Proyectos), «Cargador olvidado en el aula 21» (Objetos perdidos) y «Café en la cantina» (Otros).

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| BUS-01 | Normal | Buscar `libro` y luego `LIBRO` | Aparece «Vendo libro de Java» en ambos casos (no distingue mayúsculas) | |  ✅ |
| BUS-02 | Normal | Buscar `proyec` | Aparece «Busco grupo para proyecto» (coincide por título y por categoría) | |  ✅ |
| BUS-03 | Error | Buscar `zzzzz` | Mensaje «No hay anuncios que coincidan con «zzzzz».». Sin errores | |  ✅ |
| BUS-04 | Límite | Enviar la búsqueda vacía | Se listan todos los anuncios | |  ✅ |
| BUS-05 | Límite | Buscar `%` y luego `_` | No devuelven todos los anuncios (los comodines se tratan como texto literal). Si ningún título los contiene, mensaje de sin resultados | |  ✅ |
| BUS-06 | Seguridad | Buscar `' OR 1=1 --` | Mensaje de sin resultados. No hay error SQL ni se listan todos | |  ✅ |
| BUS-07 | Seguridad | Buscar `<script>alert(1)</script>` | El texto aparece escrito en el cuadro de búsqueda y en el mensaje. No se ejecuta ningún script | |  ✅ |
| BUS-08 | Normal | Con varios anuncios, mirar el orden del listado | Más recientes primero | |  ✅ |
| BUS-09 | Normal | Buscar `cafe` (sin tilde) | Encuentra «Café en la cantina» (si no lo encuentra, anotarlo: depende de la colación de MySQL) | |  ✅ |
| BUS-10 | Normal | Buscar `  libro  ` (con espacios) y comprobar el cuadro tras buscar | Mismo resultado que `libro`. El cuadro conserva el término | |  ✅ |

### 6.7 Comentarios (COM)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| COM-01 | Normal | Como Bea, comentar «Me interesa» en un anuncio de Ana. Después comentar otra cosa como Ana | Aparecen con nombre de autor y fecha, en orden cronológico (antiguo arriba). El título muestra «Comentarios (2)» | |  ✅ |
| COM-02 | Error | Enviar un comentario vacío y otro solo con espacios (quitar `required`) | Mensaje «El comentario no puede estar vacío ni superar los 1000 caracteres.». No se guarda | |  ✅ |
| COM-03 | Límite | Comentario de exactamente 1000 caracteres | Se guarda correctamente | |  ✅ |
| COM-04 | Límite | Comentario de 1001 caracteres (quitar `maxlength`) | El mismo mensaje de error. No se guarda | |  ✅ |
| COM-05 | Seguridad | Comentario `<script>alert(1)</script>` en dos líneas | Se ve como texto, respetando el salto de línea. No se ejecuta nada | |  ✅ |
| COM-06 | Error | Sin sesión, abrir un anuncio con comentarios | Se ven los comentarios. No hay formulario, solo el aviso «Inicia sesión para comentar.» | |  ✅ |
| COM-07 | Error | Como Bea, con F12 cambiar el `action` del formulario de comentar a `/comments/store/99999`. Enviar | Error 404 «El anuncio no existe.». No se inserta nada | |  ✅ |
| COM-08 | Seguridad | Añadir con F12 un campo oculto `user_id` con el id de Ana al formulario de comentar y enviar como Bea | El comentario aparece firmado por Bea. En la BD el `user_id` es el de Bea | |  ✅ |
| COM-09 | Normal | Borrar un comentario propio y aceptar el aviso | Redirige al anuncio con «Comentario eliminado.» y desaparece | |  ✅ |
| COM-10 | Normal | Borrar un anuncio que tiene comentarios y revisar la tabla `comments` en phpMyAdmin | Los comentarios de ese anuncio han desaparecido (borrado en cascada) | |  ✅ |

### 6.8 Protección CSRF (CSRF)

En cada caso: abrir el formulario, **cambiar unas letras del `value` del campo `csrf_token`** con F12 y enviar sin recargar.

| ID | Tipo | Formulario | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| CSRF-01 | Seguridad | Login | Error 403 «Token CSRF inválido o caducado...». No inicia sesión | |  ✅ |
| CSRF-02 | Seguridad | Registro | Error 403. No se crea el usuario | |  ✅ |
| CSRF-03 | Seguridad | Botón «Cerrar sesión» | Error 403. La sesión sigue abierta | |  ✅ |
| CSRF-04 | Seguridad | Crear anuncio | Error 403. No se crea el anuncio | |  ✅ |
| CSRF-05 | Seguridad | Editar anuncio | Error 403. El anuncio no cambia | |  ✅ |
| CSRF-06 | Seguridad | Borrar anuncio | Error 403. El anuncio sigue existiendo | |  ✅ |
| CSRF-07 | Seguridad | Comentar | Error 403. No se crea el comentario | |  ✅ |
| CSRF-08 | Seguridad | Borrar comentario | Error 403. El comentario sigue existiendo | |  ✅ |
| CSRF-09 | Seguridad | Eliminar el `<input type="hidden" name="csrf_token">` entero con F12 en cualquier formulario y enviar | Error 403 | |  ✅ |
| CSRF-10 | Seguridad | Pestaña 1: abrir un formulario. Pestaña 2: cerrar sesión e iniciarla de nuevo. Pestaña 1: enviar el formulario antiguo | Error 403 (el token antiguo ya no es válido) | |  ✅ |
| CSRF-11 | Seguridad | Crear en el escritorio un `externo.html` con `<form action="http://localhost:8080/posts/delete/{id}" method="post"><button>Premio</button></form>`. Con la sesión de Ana abierta, abrirlo y pulsar | Error 403. El anuncio sigue existiendo (simula un ataque desde otra web) | | ⬜ |
| CSRF-12 | Normal | Usar el buscador | Funciona sin token (es GET y solo lee datos) | | ⬜ |

### 6.9 Rutas (RUT)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| RUT-01 | Normal | Abrir `/`, `/posts` y `/posts/` (con barra final) | Las tres muestran el listado de anuncios | | ⬜ |
| RUT-02 | Error | Abrir `/ruta-que-no-existe` | Error 404 «Página no encontrada.» | | ⬜ |
| RUT-03 | Error | Abrir `/posts/edit` (sin id) y `/posts/create/5` (id de más) | Error 404 en ambos. Sin errores de PHP | | ⬜ |
| RUT-04 | Seguridad | Abrir `/posts/find-owned-post/1` (método privado) y `/posts/__construct` | Error 404 en ambos. No se ejecuta el método | | ⬜ |
| RUT-05 | Error | Abrir `/posts/delete/{id}` y `/auth/login` escribiéndolas en el navegador (GET) | «Método no permitido.» (405). No se borra nada | | ⬜ |
| RUT-06 | Normal | Abrir `/posts/1` y mirar el estilo | La página se ve con estilos (el CSS carga con ruta absoluta) | | ⬜ |
| RUT-07 | Normal | Abrir `/posts/1?x=2` (parámetro extra) | Se ve el detalle del anuncio 1 con normalidad | | ⬜ |
| RUT-08 | Error | Abrir la URL antigua `/index.php?controller=Post&action=show&id=1` | Ya no se interpreta: muestra el listado o un 404, nunca el detalle ni un error de PHP | | ⬜ |

### 6.10 Seguridad de sesión y datos (SEG)

| ID | Tipo | Pasos | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| SEG-01 | Seguridad | F12 > Application > Cookies. Anotar el valor de `PHPSESSID` antes y después de iniciar sesión | El valor cambia al iniciar sesión (se regenera el id de sesión) | | ⬜ |
| SEG-02 | Seguridad | Mirar las propiedades de la cookie `PHPSESSID` | `HttpOnly` activado y `SameSite=Lax` | | ⬜ |
| SEG-03 | Seguridad | En phpMyAdmin, revisar la columna `password` de `users` | Solo hay hashes (`$2y$...`). Ninguna contraseña legible | | ⬜ |

---

## 7. Resumen y fallos encontrados

| Sección | Casos | ✅ Pasan | ❌ Fallan |
|---|---|---|---|
| Registro | 11 | ✅| |
| Login y logout | 9 |✅ | |
| Anuncios: creación | 9 | ✅| |
| Anuncios: detalle y edición | 9 | ✅| |
| Permisos | 8 | ✅| |
| Buscador | 10 | ✅| |
| Comentarios | 10 | ✅| |
| CSRF | 12 | ✅| |
| Rutas | 8 | ✅| |
| Sesión y datos | 3 | ✅| |
| **Total** | **89** | | |

## 8. Conclusiones

Los 10 casos de prueba se ejecutaron manualmente y todos superaron la prueba. El
plan comprueba el registro con hash de contraseña, el login y logout, la
creación de anuncios con casos límite de validación, los permisos de propiedad
(un usuario no puede editar anuncios ajenos, ni siquiera manipulando la
petición), el comentario con autor tomado de la sesión, el buscador frente a
inyección SQL y la protección CSRF.

Este plan no cubre algunos aspectos: el borrado en cascada de comentarios al
eliminar un anuncio, los límites máximos de longitud del texto y de los
comentarios, las rutas inexistentes y los métodos HTTP no permitidos. Tampoco es
un sustituto de tests automáticos (PHPUnit), que permitirían repetir todas estas
comprobaciones al instante tras cada cambio en el código.