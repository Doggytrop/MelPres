# 💰 MelPres

Sistema web profesional para la gestión de préstamos personales, desarrollado con Laravel.

---

## 📌 Descripción

**MelPres** es una aplicación web diseñada para ayudar a prestamistas a administrar customers, préstamos y payments de forma flexible y realista, adaptándose a diferentes esquemas de negocio.

El sistema permite manejar desde préstamos simples hasta escenarios más complejos como payments partiales, interestes variables y control de mora.

---

## 🚀 Características principales

* 👤 Gestión de customers
* 💰 Administración de préstamos
* 💳 Registro de payments (flexibles)
* 📊 Dashboard con métricas
* 🔐 Sistema de autenticación
* ⚙️ interestes personalizables
* ⏱️ Soporte para payments:

  * weeklyes
  * biweeklyes
  * monthlyes
* 🚨 Control de mora (sanciones)

---

## 🧠 Tipos de préstamos soportados

### 🔴 Préstamo tipo interés (renovable)

* El customer paga solo interestes por periodo
* El capital permanece intacto
* Ideal para esquemas tipo “payment de interés monthly”

### 🟢 Préstamo a term

* Interés acumulado por duración
* payments divididos en periodos
* Liquidación total al finalizar

---

## 💳 Sistema de payments

El sistema permite:

* payments completes
* payments partiales
* payments mayores al esperado
* payments solo de interés
* payments mixeds

Todo basado en el concepto de:

```plaintext
remaining_balance
```

---

## 🛠️ Tecnologías utilizadas

* PHP 8+
* Laravel
* Blade
* MySQL
* Bootstrap
* Laragon (entorno local)

---

## ⚙️ Instalación

1. Clonar el repositorio:

```bash
git clone https://github.com/tuusuario/melpres.git
cd melpres
```

2. Instalar dependencias:

```bash
composer install
npm install
```

3. Configurar entorno:

```bash
cp .env.example .env
```

Editar el archivo `.env` con tus datos de base de datos.

4. Generar clave:

```bash
php artisan key:generate
```

5. Ejecutar migraciones:

```bash
php artisan migrate
```

6. Iniciar servidor:

```bash
php artisan serve
```

---

## 📁 Estructura del proyecto

* `app/Http/Controllers` → lógica de controladores
* `resources/views` → vistas Blade
* `routes/web.php` → rutas web
* `database/migrations` → estructura de base de datos

---

## 📊 status del proyecto

🚧 En desarrollo

Módulos actuales:

* Autenticación ✔️
* Dashboard ✔️
* customers (en progreso)
* Préstamos (en diseño)
* payments (pendiente)

---

## 🔮 Futuras mejoras

* Notificaciones (WhatsApp)
* Reportes avanzados
* Control de usuarios/roles
* Exportación de datos
* Dashboard avanzado

---

## 👨‍💻 Autor

Desarrollado por **Luis Angel Andrade**

---

## 📄 Licencia

Este proyecto es de uso privado / comercial.
