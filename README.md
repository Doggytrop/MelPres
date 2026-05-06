# MelPres

Sistema de gestion de prestamos personales desarrollado con Laravel 13, Bootstrap 5 y MySQL. Permite administrar clientes, prestamos, pagos, reestructuraciones y cobranza en campo con mapa interactivo.

---

## Tabla de contenidos

- [Descripcion](#descripcion)
- [Caracteristicas](#caracteristicas)
- [Stack tecnologico](#stack-tecnologico)
- [Requisitos](#requisitos)
- [Instalacion](#instalacion)
- [Roles del sistema](#roles-del-sistema)
- [Modulos](#modulos)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Configuracion](#configuracion)
- [Desarrollado por](#desarrollado-por)
- [Licencia](#licencia)

---

## Descripcion

MelPres es un sistema completo de gestion de prestamos personales orientado a financieras, prestamistas independientes y casas de empenio. El sistema permite controlar todo el ciclo de vida de un prestamo: desde la evaluacion del cliente hasta la cobranza en campo, incluyendo reestructuraciones, generacion de documentos legales en PDF y un portal exclusivo para que los clientes consulten sus prestamos.

El sistema es multiusuario con cinco roles diferenciados (Super Admin, Administrador, Asesor, Cobrador y Cliente), cada uno con su propio nivel de acceso y panel personalizado.

---

## Caracteristicas

### Gestion de clientes
- Registro con datos personales, documentos de identidad y referencias
- Foto de perfil y documentos adjuntos (INE, comprobante de domicilio, nomina)
- Score de credito interno calculado automaticamente segun historial de pagos
- Geolocalizacion con mapa interactivo (Leaflet + OpenStreetMap)
- Busqueda de direcciones con autocompletado via Nominatim
- Creacion automatica de usuario y contrasenia para acceso al portal

### Tres tipos de prestamo
- **Plazo**: capital + interes dividido en cuotas fijas (semanal, quincenal o mensual)
- **Interes**: el cliente paga solo el interes cada periodo, el capital no disminuye hasta liquidar
- **Diario**: interes total fijo sobre el monto, dividido entre los dias del plazo

### Registro de pagos
- Distribucion automatica: mora primero, luego interes pendiente, luego capital
- Cierre automatico del prestamo cuando el saldo llega a cero
- Pago rapido desde modal con buscador de clientes con debounce
- Calendario de pagos con fechas programadas y estado de cada cuota

### Reestructuracion
- Tres opciones: condonacion de mora, extension de plazo y creacion de nuevo prestamo
- Generacion automatica de carta legal en PDF con clausulas y espacios de firma
- Historial completo de reestructuraciones

### Simulador de prestamos
- Soporta los tres tipos de prestamo
- Analisis de capacidad de pago basado en ingreso del cliente
- Evaluacion con semaforo: viable, precaucion o no recomendado
- Incluye compromisos de prestamos activos del cliente

### Modulo de cobranza
- Panel exclusivo para cobradores sin acceso al sistema principal
- Lista automatica de cobros del dia y atrasados (menos de 15 dias)
- Mapa interactivo con pins verdes (hoy) y rojos (atrasados) usando Leaflet
- Boton para abrir Google Maps y navegar a la direccion del cliente
- Registro de cobro en campo con un solo clic
- Resumen de cobros realizados en el dia

### Portal del cliente
- Acceso con numero de telefono y contrasenia generada automaticamente
- Vista exclusiva sin sidebar ni acceso al sistema principal
- Consulta de prestamos activos con saldo, proximo pago y barra de progreso
- Historial de pagos con desglose de capital, interes y mora
- Historial de prestamos liquidados

### Configuracion dinamica
- Nombre, slogan y logo de la empresa
- Colores primario y secundario que se aplican a todo el sistema
- Informacion de contacto (telefono, email, WhatsApp, direccion)
- Configuracion de prestamos: montos minimos/maximos, tasas de interes, penalizaciones
- Permisos de asesores
- Configuracion de PDFs (encabezado, pie de pagina, logo)
- Frecuencias de pago permitidas

### Bitacora de movimientos
- Registro automatico de todas las acciones del sistema
- Filtros por modulo, accion y usuario
- Registro de IP, fecha, hora y descripcion de cada movimiento

### Corte de caja
- Resumen de cobros del dia por usuario
- Generacion de reporte en PDF

### Dashboard
- Metricas principales: prestamos activos, capital prestado, cobros del mes
- Grafica de pagos por mes con Chart.js

---

## Stack tecnologico

| Componente | Tecnologia |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Frontend | Bootstrap 5, JavaScript vanilla |
| Base de datos | MySQL 8 |
| Mapas | Leaflet + OpenStreetMap |
| Geocodificacion | Nominatim (gratis, sin API key) |
| PDFs | barryvdh/laravel-dompdf |
| Graficas | Chart.js |
| Entorno local | Laragon (Windows) |

---

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8 o MariaDB 10.5+
- Node.js 18+ (opcional, para assets)
- Laragon, XAMPP o similar

---

## Instalacion

```bash
# Clonar el repositorio
git clone https://github.com/Doggytrop/MelPres.git
cd MelPres

# Instalar dependencias
composer install

# Copiar archivo de entorno
cp .env.example .env

# Generar clave de aplicacion
php artisan key:generate

# Configurar base de datos en .env
# DB_DATABASE=melpres_db
# DB_USERNAME=root
# DB_PASSWORD=

# Ejecutar migraciones y seeders
php artisan migrate
php artisan db:seed --class=SettingSeeder

# Crear enlace simbolico para storage
php artisan storage:link

# Iniciar el servidor
php artisan serve
```

Despues de la instalacion, registra un usuario y asignale el rol `superadmin` directamente en la base de datos:

```sql
UPDATE users SET role = 'superadmin' WHERE id = 1;
```

---

## Roles del sistema

| Rol | Acceso |
|---|---|
| Super Admin | Acceso total. Gestion de usuarios, configuracion, bitacora. No puede ser eliminado. |
| Admin | Gestion completa de prestamos, clientes, asesores y configuracion. |
| Asesor | Registra pagos, consulta clientes y prestamos. Acceso limitado segun configuracion. |
| Cobrador | Panel exclusivo de cobranza con mapa. Registra cobros en campo. Sin acceso al sistema principal. |
| Cliente | Portal exclusivo de consulta. Ve sus prestamos y pagos. Sin acceso al sistema principal. |

---

## Modulos

| Modulo | Descripcion |
|---|---|
| Dashboard | Metricas y grafica de pagos por mes |
| Clientes | CRUD con documentos, foto, score y geolocalizacion |
| Prestamos | Tres tipos (plazo, interes, diario) con calendario de pagos |
| Pagos | Registro con distribucion automatica mora-interes-capital |
| Historial | Prestamos liquidados con detalle completo |
| Simulador | Evaluacion de viabilidad con analisis de capacidad de pago |
| Reestructuracion | Condonacion, extension o nuevo prestamo con PDF legal |
| Corte de caja | Resumen diario de cobros con PDF |
| Cobranza | Panel con mapa Leaflet para cobros en campo |
| Portal cliente | Consulta exclusiva de prestamos y pagos |
| Asesores | Gestion de usuarios asesores |
| Usuarios | Gestion completa de usuarios del sistema |
| Configuracion | Personalizacion de empresa, colores, logo y parametros |
| Bitacora | Registro de todos los movimientos del sistema |

---

## Estructura del proyecto

```
app/
  Http/
    Controllers/
      CustomerController.php
      LoanController.php
      PaymentController.php
      RestructuringController.php
      SimulatorController.php
      CollectorController.php
      PortalController.php
      DashboardController.php
      UserController.php
      AdvisorController.php
      SettingController.php
      CashRegisterController.php
      ActivityLogController.php
      CustomerDocumentController.php
    Middleware/
      SoloAdmin.php
      RedirectCustomer.php
    Requests/
      StoreLoanRequest.php
      StorePaymentRequest.php
      StoreCustomerRequest.php
      UpdateCustomerRequest.php
      StoreCustomerDocumentRequest.php
  Models/
    Customer.php
    Loan.php
    Payment.php
    Restructuring.php
    CustomerDocument.php
    User.php
    Setting.php
    ActivityLog.php
  Services/
    PaymentService.php
    PenaltyService.php
    ScoreService.php
resources/
  views/
    layouts/app.blade.php
    partials/sidebar.blade.php, navbar.blade.php
    customers/
    loans/
    restructuring/
    simulator/
    collector/
    portal/
    settings/
    users/
    advisors/
    history/
    activity-logs/
    dashboard/
```

---

## Configuracion

El sistema se configura desde el panel de Configuracion (solo Super Admin y Admin). Las opciones se organizan en pestanias:

- **Empresa**: nombre, slogan, logo, colores primario y secundario, contacto
- **Prestamos**: montos minimos/maximos, tasas de interes, penalizaciones, frecuencias permitidas
- **Usuarios**: permisos de asesores
- **Notificaciones**: recordatorios de pago y avisos de mora (preparado para WhatsApp API)
- **Documentos**: configuracion de PDFs (encabezado, pie de pagina, logo)
- **Avanzado**: tiempo de sesion, moneda, zona horaria, bitacora

Los colores configurados se aplican dinamicamente al sidebar, botones y elementos del sistema.

---

## Desarrollado por

**Luis Andrade** 
**Karolina Arvizu** 
-- melSolutions

---

## Licencia

MIT License. Ver archivo [LICENSE](LICENSE) para mas detalles.