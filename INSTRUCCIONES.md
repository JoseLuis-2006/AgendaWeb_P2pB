# AgendaWeb · Qué se corrigió y cómo subirlo a DOM Cloud

## Qué se corrigió

| Archivo | Problema | Corrección |
|---|---|---|
| `db.sql` | Hacía `CREATE DATABASE agenda` y `USE agenda`. En DOM Cloud no puedes crear bases (la tuya se llama algo como `tuusuario_db`), así que la importación fallaba. La tabla tampoco declaraba `utf8mb4` por sí misma. | Ahora solo crea la tabla (`CREATE TABLE IF NOT EXISTS`) con `DEFAULT CHARSET=utf8mb4`. Se puede importar más de una vez sin error. |
| `conexion.ejemplo.php` | No se parecía a `conexion.php` (definía `$host`, `$user`...) y el proyecto usa `$mysqli`. Quien lo copiara rompía la app. | Ahora es igual a `conexion.php`, pero con `TU_USUARIO` / `TU_CONTRASEÑA`. DOM Cloud lo usa para crear tu `conexion.php`. |
| `registrar.php` | La hora no se validaba: una hora inválida hacía caer la página al guardar. | Se valida `HH:MM` y el error se muestra junto al campo, igual que los demás. La redirección ahora es `303`. |
| `conexion.php` | Terminaba en `?>`: cualquier espacio después rompe el `header('Location: ...')`. | Se quitó el `?>`. (Este archivo no se sube a GitHub.) |

Todo lo demás ya estaba bien: formulario con `method="post"`, validación en servidor con lista blanca,
formulario pegajoso, consulta preparada y PRG con mensaje de éxito.

---

## Paso 1 · Subir los cambios a GitHub

Abre una terminal en esta carpeta y ejecuta:

```bash
git add -A
git commit -m "Corrige db.sql, plantilla de conexión y validación de hora"
git push
```

`conexion.php` no se sube (está en `.gitignore`), y así debe ser.

---

## Paso 2 · Crear el sitio en DOM Cloud

1. En DOM Cloud, crea un sitio nuevo y elige **Custom template** (abajo a la derecha).
2. Borra lo que aparezca y pega exactamente esto:

```yaml
source: https://github.com/JoseLuis-2006/AgendaWeb_P2pB
features:
  - mysql
nginx:
  root: public_html
  fastcgi: "on"
  index: index.php
commands:
  - cp conexion.ejemplo.php conexion.php
  - sed -ri "s|new mysqli\(.*\);|new mysqli(\"localhost\", \"$USERNAME\", \"${MYPASSWD:-$PASSWORD}\", \"$DATABASE\");|" conexion.php
  - mysql -u "$USERNAME" -p"${MYPASSWD:-$PASSWORD}" "$DATABASE" < db.sql
```

3. Ponle un nombre al sitio y créalo. Espera a que termine la instalación; si alguna línea dice `ERROR`, toma captura.

Qué hace cada parte:

| Línea | Qué hace |
|---|---|
| `source` | Descarga tu repositorio de GitHub |
| `features: - mysql` | Crea tu base de datos (MariaDB, compatible con MySQL) |
| `fastcgi: "on"` | Enciende PHP (en DOM Cloud viene apagado) |
| `index: index.php` | La página principal es `index.php` |
| `cp ...` | Crea `conexion.php` a partir de la plantilla |
| `sed ...` | Le pone el usuario, contraseña y base que te dio DOM Cloud |
| `mysql ... < db.sql` | Crea la tabla `eventos` |

---

## Paso 3 · Probarlo

1. Abre tu dirección. DOM Cloud muestra una advertencia en los sitios gratuitos (`*.dom.my.id`):
   da clic en **"I understand, I trust this site"**. No es un error.
2. Entra a **Nuevo evento** y envía el formulario vacío: deben aparecer los errores y conservarse lo escrito.
3. Llénalo bien y guarda: debe regresarte a **Mis eventos** con el mensaje "Evento guardado correctamente".

---

## Si después cambias algo

Sube los cambios a GitHub (Paso 1) y en DOM Cloud, en la sección **Deploy** de tu sitio, ejecuta:

```yaml
source: https://github.com/JoseLuis-2006/AgendaWeb_P2pB
nginx:
  root: public_html
  fastcgi: "on"
  index: index.php
commands:
  - cp conexion.ejemplo.php conexion.php
  - sed -ri "s|new mysqli\(.*\);|new mysqli(\"localhost\", \"$USERNAME\", \"${MYPASSWD:-$PASSWORD}\", \"$DATABASE\");|" conexion.php
```

No vuelve a importar la base, así que tus eventos no se tocan.

---

## En tu computadora (si necesitas crear la base desde cero)

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS agenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p agenda < db.sql
```
