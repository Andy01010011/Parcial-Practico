# Parcial Práctico 2FA - Sistema de Registro y Autenticación Segura en PHP

**Materia:** Desarrollo Web  
**Estudiante:** Andrea Sánchez 8-1044-1308, Iliana Navarro 8-1033-476  
**Carrera:** Licenciatura en Ciberseguridad  
**Universidad:** Universidad Tecnológica de Panamá (UTP)  
**Facultad:** Facultad de Ingeniería en Sistemas Computacionales (FISC) - Campus Víctor Levi Sasso  
**Facilitadora:** Ing. Irina Fong  
**Período:** II Semestre 2026  

---

## 📝 Descripción del Proyecto
Aplicación web desarrollada en PHP con MySQL que implementa un flujo completo de registro, inicio de sesión y autenticación de doble factor (2FA) mediante códigos TOTP compatibles con Google Authenticator. El proyecto aplica buenas prácticas de seguridad solicitadas en la rúbrica: privilegios mínimos en la base de datos, sanitización de entradas, hash de contraseñas, protección Anti-CSRF y control de sesiones en dos fases.

**Herramientas utilizadas:** XAMPP (Apache + MySQL), Visual Studio Code, Composer, PHP, HTML5, CSS3 y JavaScript.  
**Librería externa:** `sonata-project/google-authenticator` (instalada con Composer) para generar el secreto 2FA y verificar el código de 6 dígitos.

---

## 📁 Estructura del Proyecto

```
ParcialPractico/
├── clases/
│   ├── Autenticacion2FA.php   # Genera el secreto, el QR y valida el código TOTP
│   ├── Csrf.php               # Genera y valida tokens Anti-CSRF
│   ├── HashInterface.php      # Contrato (interfaz) para generar y verificar hashes
│   ├── PasswordHasher.php     # Implementación de HashInterface con password_hash()
│   ├── RegistroUsuario.php    # Lógica del formulario de registro (responsabilidad mínima por método)
│   └── Sanitizador.php        # Métodos estáticos de sanitización
├── config/
│   └── Database.php           # Conexión PDO a MySQL con usuario de privilegios mínimos
├── public/
│   ├── css/                   # Estilos del login y del formulario
│   ├── registro.php           # Formulario de registro
│   ├── login.php              # Inicio de sesión (fase 1)
│   ├── activar_2fa.php        # Muestra el QR y activa el 2FA
│   ├── verificar_2fa.php      # Solicita el código (fase 2)
│   ├── bienvenido.php         # Zona protegida
│   ├── privilegios.php        # Muestra los privilegios de los usuarios MySQL
│   └── logout.php             # Cierre de sesión
├── vendor/                    # Dependencias de Composer
├── composer.json
└── composer.lock
```

**¿Por qué esta organización?** Se separó la lógica (`clases/`), la configuración (`config/`) y las páginas visibles (`public/`). Así solo `public/` queda expuesta al navegador y el código sensible (credenciales de BD, clases) queda fuera del alcance directo del usuario.

---

## ⚙️ Instalación y Ejecución

1. Clonar el repositorio dentro de `C:\xampp\htdocs\`.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Instalar las dependencias:
   ```bash
   composer install
   ```
4. Crear la base de datos y los usuarios MySQL.
5. Ajustar las credenciales en `config/Database.php`.
6. Abrir en el navegador: `http://localhost/ParcialPractico/public/registro.php`

---

## 🛠️ Desarrollo paso a paso

### 1. Usuarios MySQL con privilegios mínimos
**Qué se pidió:** no usar el superusuario (`root`), mostrar en pantalla los privilegios de los usuarios creados y mostrar el comando que permite verlos.

**Cómo se hizo:**
- Se creó una base de datos propia para el proyecto y usuarios dedicados, otorgando solo los permisos necesarios sobre esa base de datos (nunca `ALL PRIVILEGES` ni `*.*`).
- Ejemplo del patrón usado:
  ```sql
  CREATE USER 'app_usuario'@'localhost' IDENTIFIED BY '********';
  GRANT SELECT, INSERT, UPDATE ON nombre_bd.* TO 'app_usuario'@'localhost';
  FLUSH PRIVILEGES;

  -- Comando para ver los privilegios concedidos:
  SHOW GRANTS FOR 'app_usuario'@'localhost';
  ```
- La página `public/privilegios.php` ejecuta `SHOW GRANTS` y muestra el resultado en pantalla, junto con el comando utilizado.

**Por qué:** el principio de mínimo privilegio reduce el daño si la aplicación es comprometida (por ejemplo, con una inyección SQL el atacante no podría borrar tablas ni crear usuarios).

📸 **Evidencia:**
<img width="528" height="643" alt="image" src="https://github.com/user-attachments/assets/a3ae1d10-e068-47da-b7b9-33529be21d24" />
---

### 2. Formulario de registro con validaciones básicas
**Qué se pidió:** formulario funcional con campos requeridos, correo válido y contraseñas coincidentes.

**Cómo se hizo:** `registro.php` incluye los campos nombre, apellido, sexo, usuario, correo y contraseña (con confirmación). Se valida en dos capas:
- **Cliente (HTML5/JS):** `required`, `type="email"`, longitud mínima y comparación de contraseñas antes de enviar.
- **Servidor (PHP):** se repiten las validaciones con `filter_var($correo, FILTER_VALIDATE_EMAIL)`, longitud mínima de contraseña y coincidencia de ambas contraseñas.

**Por qué:** la validación del cliente mejora la experiencia, pero se puede saltar; por eso la validación del servidor es la que realmente protege.

📸 **Evidencia:**
<img width="1350" height="597" alt="image" src="https://github.com/user-attachments/assets/a573d31d-66c4-44a9-90f0-7ed1f6c286e4" />
---

### 3. Validación de correo y usuario no duplicados en el frontend
**Qué se pidió:** validar que correo y usuario no estén repetidos al momento de ingresar los datos, del lado del frontend.

**Cómo se hizo:** mientras el usuario escribe (o al salir del campo), JavaScript consulta al servidor mediante `fetch()` si el correo o usuario ya existe y muestra un mensaje inmediato en el formulario, sin recargar la página.

**Por qué:** se evita que la persona llene todo el formulario para enterarse al final de que el usuario ya existe. Además, la columna en la BD se define como `UNIQUE` para garantizar la regla aunque falle el frontend.

📸 **Evidencia:**
AQUI FALTA LA FOTO
---

### 4. Clase para el formulario de registro
**Qué se pidió:** una clase para el formulario de registro cuyos métodos tengan una responsabilidad mínima.

**Cómo se hizo:** `clases/RegistroUsuario.php` divide el proceso en métodos pequeños, cada uno con una sola tarea, por ejemplo: validar campos requeridos, validar correo, validar contraseñas, verificar duplicados y guardar el usuario.

**Por qué:** aplicar el Principio de Responsabilidad Única (SRP) hace el código más fácil de leer, probar y mantener.

---

### 5. Sanitización de datos con métodos estáticos
**Qué se pidió:** una clase con métodos estáticos para sanitizar los datos (mínimo 3).

**Cómo se hizo:** `clases/Sanitizador.php` contiene métodos estáticos aplicados según el tipo de campo, siguiendo la tabla de la rúbrica:

| Campo | ¿Se sanitiza? | Técnica aplicada |
|---|---|---|
| Nombre / Apellido | ✅ | `trim()`, `strip_tags()`, `htmlspecialchars()` (conservando tildes y apóstrofes) |
| Sexo | ✅ | Validación contra lista fija de valores permitidos |
| Usuario | ✅ | Solo letras, números y guion bajo + validación de longitud |
| Correo | ✅ | `filter_var()` con `FILTER_VALIDATE_EMAIL` y `FILTER_SANITIZE_EMAIL` |
| Fecha del sistema | ❌ | La genera el servidor (`date()`), no viene del usuario |
| Hash de contraseña | ❌ | Lo genera `password_hash()`; la contraseña en texto plano sí se valida antes |
| Secreto 2FA | ✅ | Validación con expresión regular Base32 (`/^[A-Z2-7=]+$/`) |

**Por qué:** previene ataques XSS e inyección SQL y asegura que los datos almacenados sean limpios y consistentes. Se usan además consultas preparadas (PDO) para el acceso a la BD.

📸 **Evidencia:**
AQUI TAMBIEN FALTA FOTO
---

### 6. Generación del código QR (5 pts)
**Qué se pidió:** generar el código QR.

**Cómo se hizo:** al completar el registro, `Autenticacion2FA.php` genera un secreto único por usuario con la librería `sonata-project/google-authenticator`. Ese secreto se guarda en la BD y se muestra en `activar_2fa.php` como un código QR que el usuario escanea con Google Authenticator.

**Por qué:** el QR evita escribir manualmente el secreto y vincula la cuenta con la app generadora de códigos temporales (TOTP).

📸 **Evidencia:**
![Código QR generado](docs/img/06-qr.png)
![Cuenta agregada en Google Authenticator](docs/img/07-authenticator.png)

---

### 7. Inicio de sesión que solicita el código antes de entrar
**Qué se pidió:** permitir iniciar sesión y solicitar el código QR/código 2FA antes de entrar al sistema.

**Cómo se hizo:** `login.php` valida usuario y contraseña (con `password_verify()`). Si son correctos, no se da acceso todavía: se redirige a `verificar_2fa.php`, donde se pide el código de 6 dígitos de la app.

📸 **Evidencia:**
![Login](docs/img/08-login.png)
![Solicitud del código 2FA](docs/img/09-verificar-2fa.png)

---

### 8. Segunda sesión para confirmar la fase del código
**Qué se pidió:** crear otra sesión que confirme que la fase de solicitud del código fue exitosa.

**Cómo se hizo:** se manejan dos estados de sesión:
1. **Sesión de fase 1:** se crea al validar usuario y contraseña; solo permite acceder a `verificar_2fa.php`.
2. **Sesión de fase 2:** se crea únicamente cuando el código TOTP es correcto; solo con esta se puede entrar a `bienvenido.php`.

Al pasar a la fase 2 se regenera el ID de sesión con `session_regenerate_id(true)`.

**Por qué:** así nadie puede saltarse el 2FA entrando directamente a la URL protegida, y regenerar el ID previene el **secuestro de sesión (session fixation)**.

📸 **Evidencia:**
![Acceso exitoso a bienvenido.php](docs/img/10-bienvenido.png)
![Acceso denegado sin completar el 2FA](docs/img/11-acceso-denegado.png)

---

### 9. Interfaz para generar y validar hash con POO (5 pts)
**Qué se pidió:** una interfaz para generar el hash y validarlo usando POO.

**Cómo se hizo:**
- `HashInterface.php` define el contrato con los métodos para generar y verificar un hash.
- `PasswordHasher.php` implementa esa interfaz usando `password_hash()` y `password_verify()`.

**Por qué:** programar contra una interfaz permite cambiar el algoritmo de hash en el futuro sin modificar el resto del sistema, y las contraseñas nunca se guardan en texto plano.

📸 **Evidencia:**
![Hash almacenado en la base de datos](docs/img/12-hash.png)

---

### 10. Protección Anti-CSRF
**Qué se pidió:** mitigar ataques CSRF con tokens aleatorios que se comparen al enviar el formulario.

**Cómo se hizo:** `clases/Csrf.php`:
1. Genera un token aleatorio criptográficamente seguro (`random_bytes()`) y lo guarda en la sesión.
2. Lo incluye como campo oculto en cada formulario (registro, login y verificación 2FA).
3. Al recibir el formulario, compara el token enviado con el de la sesión usando `hash_equals()`. Si no coinciden, se rechaza la solicitud.

**Por qué:** un atacante no puede leer el token de la sesión de la víctima, por lo que no puede falsificar una petición válida.

📸 **Evidencia:**
![Token CSRF en el código fuente del formulario](docs/img/13-csrf-token.png)
![Solicitud rechazada por token inválido](docs/img/14-csrf-rechazado.png)

---

### 11. Tablas que registran los datos
**Qué se pidió:** verificar que las tablas registren los datos y que la BD se mantenga limpia y consistente.

**Cómo se hizo:** se crearon las tablas necesarias con claves primarias, restricciones `UNIQUE` (usuario y correo) y tipos de datos adecuados. Tras registrar usuarios de prueba se verificó el contenido desde phpMyAdmin.

📸 **Evidencia:**
![Tabla 1 con datos](docs/img/15-tabla-1.png)
![Tabla 2 con datos](docs/img/16-tabla-2.png)

---

### 12. Calidad del CSS y valor agregadO
**Qué se pidió:** buen CSS y valor agregado al login y al formulario.

**Cómo se hizo:** la carpeta `public/css/` contiene los estilos del login y del formulario de registro con diseño uniforme, tarjetas centradas, mensajes de error y éxito diferenciados por color y diseño adaptable.

📸 **Evidencia:**
<img width="489" height="597" alt="image" src="https://github.com/user-attachments/assets/ff09cb41-30c1-49e1-93f9-254319c97a65" />
<img width="432" height="476" alt="image" src="https://github.com/user-attachments/assets/5790fe7c-1bf0-4565-a1a9-a7bdd1ac867c" />

---

## 🛡️ Resumen de Medidas de Seguridad

| Medida | Dónde se aplica |
|---|---|
| Privilegios mínimos en MySQL | Usuarios de BD / `privilegios.php` |
| Sanitización y validación de entradas | `Sanitizador.php`, `RegistroUsuario.php` |
| Consultas preparadas (PDO) | `Database.php` |
| Hash de contraseñas | `HashInterface.php`, `PasswordHasher.php` |
| 2FA con TOTP | `Autenticacion2FA.php` |
| Sesiones en dos fases + regeneración de ID | `login.php`, `verificar_2fa.php` |
| Tokens Anti-CSRF | `Csrf.php` |

---
