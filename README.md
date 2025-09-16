# 🏛️ CEDA - Sistema de Gestión Administrativa

## 📋 Descripción

CEDA es un sistema web de gestión administrativa desarrollado con **Laminas MVC** (PHP) que incluye funcionalidades de autenticación, administración de usuarios, gestión de perfiles y módulos administrativos.

## 🛠️ Stack Tecnológico

- **Framework**: Laminas MVC (PHP 8.1)
- **Base de Datos**: MariaDB 10.9
- **Servidor Web**: Apache 2.4
- **Contenedores**: Docker & Docker Compose
- **Gestión de Dependencias**: Composer
- **Autenticación**: Bcrypt
- **Frontend**: Bootstrap, jQuery

## 📁 Estructura del Proyecto

```
CEDA/
├── config/                 # Configuraciones globales
│   ├── autoload/          # Configuraciones auto-cargables
│   ├── application.config.php
│   └── modules.config.php
├── data/                  # Datos, cache y logs
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── docker/                # Configuraciones Docker
├── module/                # Módulos de la aplicación
│   ├── Application/       # Módulo base
│   ├── Auth/             # Autenticación
│   ├── Administracion/   # Panel administrativo
│   ├── DPPortada/        # Portada
│   ├── ORM/              # Acceso a datos
│   ├── Utilidades/       # Funciones auxiliares
│   └── Meritos/          # Gestión de méritos
├── public/               # Punto de entrada web
│   ├── css/
│   ├── js/
│   ├── img/
│   └── index.php
├── vendor/               # Dependencias Composer
├── docker-compose.yml    # Configuración Docker
├── Dockerfile           # Imagen Docker personalizada
└── README.md
```

## 🚀 Instalación y Configuración

### Prerequisitos

- **Docker** >= 20.10
- **Docker Compose** >= 2.0
- **Git**
- **4GB RAM** mínimo disponible

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tuusuario/ceda.git
cd ceda
```

### 2. Configurar Permisos

```bash
# Dar permisos a los scripts
chmod +x init-dev.sh
chmod +x maintenance.sh

# Crear directorios necesarios
mkdir -p data/{cache,logs,tmp,uploads,sessions}
mkdir -p public/{uploads,reports,temp}
```

### 3. Levantar el Ambiente

```bash
# Opción 1: Script automatizado (recomendado)
./init-dev.sh

# Opción 2: Comandos manuales
docker-compose up --build -d
```

### 4. Verificar la Instalación

Accede a estas URLs para verificar que todo funcione:

- **🌐 Aplicación**: http://localhost:8080
- **🗄️ phpMyAdmin**: http://localhost:8081
- **📊 Base de Datos**: localhost:3306

## 🔑 Credenciales por Defecto

### Base de Datos
- **Host**: localhost:3306
- **Usuario**: `ceda_user`
- **Contraseña**: `ceda_password`
- **Base de datos**: `ceda`

### phpMyAdmin
- **Usuario**: `root`
- **Contraseña**: `root_password`

### Usuario del Sistema (crear si no existe)
```sql
-- Ejecutar en phpMyAdmin
INSERT INTO usuario (username, password, email, nombre, apellido, activo, fecha_creacion) 
VALUES (
    'admin', 
    '$2y$10$BYer6/LTbTHNFGGlxjNhMOsnNYQ7OZMlDsz8LnZLc0fJszKtMhvP.',  -- password: admin123
    'admin@ceda.com',
    'Administrador',
    'Sistema',
    1,
    NOW()
);
```

**Credenciales de acceso:**
- **Usuario**: `admin`
- **Contraseña**: `admin123`

## 🛠️ Comandos de Mantenimiento

El proyecto incluye un script de mantenimiento para facilitar las tareas comunes:

```bash
# Ver todas las opciones disponibles
./maintenance.sh help

# Comandos más utilizados
./maintenance.sh start      # Iniciar contenedores
./maintenance.sh stop       # Detener contenedores
./maintenance.sh restart    # Reiniciar contenedores
./maintenance.sh logs       # Ver logs en tiempo real
./maintenance.sh shell      # Acceder al contenedor web
./maintenance.sh status     # Ver estado de contenedores
./maintenance.sh db-backup  # Crear backup de BD
```

## 🔧 Desarrollo

### Estructura de Módulos

Cada módulo sigue el patrón MVC de Laminas:

```
module/NombreModulo/
├── config/module.config.php  # Configuración del módulo
├── src/
│   ├── Controller/           # Controladores
│   ├── Entity/              # Entidades
│   ├── Form/                # Formularios
│   ├── Service/             # Servicios
│   └── Module.php           # Clase principal
└── view/                    # Vistas/Templates
```

### Agregar un Nuevo Módulo

1. **Crear estructura del módulo**:
```bash
mkdir -p module/NuevoModulo/{config,src/{Controller,Entity,Form,Service},view}
```

2. **Crear clase Module**:
```php
<?php
namespace NuevoModulo;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
```

3. **Registrar en `config/modules.config.php`**:
```php
return [
    'Laminas\Router',
    'Laminas\Validator',
    // ... otros módulos
    'NuevoModulo',  // ← Agregar aquí
];
```

### Crear un Controlador

```php
<?php
namespace NuevoModulo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel([
            'message' => 'Hola desde el nuevo módulo'
        ]);
    }
}
```

### Trabajar con Base de Datos

El proyecto usa **Laminas DB** con el patrón **TableGateway**:

```php
// Ejemplo de acceso a datos
use Laminas\Db\TableGateway\TableGateway;

class UsuarioTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function getUsuario($id)
    {
        return $this->tableGateway->select(['id' => $id])->current();
    }
}
```

## 🐛 Debugging

### Ver Logs
```bash
# Logs de Apache
./maintenance.sh logs

# Logs específicos del contenedor web
docker-compose logs web

# Logs de la base de datos
docker-compose logs db
```

### Acceder al Contenedor
```bash
# Shell del contenedor web
./maintenance.sh shell

# Ejecutar comandos PHP
./maintenance.sh php -v
./maintenance.sh composer --version
```

### Problemas Comunes

**🚫 Puerto ya en uso**
```bash
# Cambiar puertos en docker-compose.yml
ports:
  - "8081:80"  # En lugar de 8080:80
```

**🗄️ Error de conexión a BD**
```bash
# Verificar que la BD esté corriendo
docker-compose ps
./maintenance.sh status

# Reiniciar servicios
./maintenance.sh restart
```

**📁 Permisos de archivos**
```bash
# Corregir permisos
sudo chown -R $USER:$USER data/
chmod -R 755 data/
```

## 📊 Base de Datos

### Tablas Principales

- `usuario` - Usuarios del sistema
- `perfil` - Roles/perfiles de usuario
- `usuario_perfil` - Relación usuarios-perfiles
- `modulo` - Módulos del sistema
- `permiso` - Permisos específicos

### Backup y Restore

```bash
# Crear backup
./maintenance.sh db-backup

# Restaurar backup
./maintenance.sh db-restore backup_20231215_143022.sql
```

## 🚀 Deployment

### Producción

1. **Configurar variables de entorno**:
```bash
cp config/autoload/database.local.php.dist config/autoload/database.local.php
# Editar con credenciales de producción
```

2. **Optimizar para producción**:
```bash
composer install --no-dev --optimize-autoloader
```

3. **Configurar servidor web** (Apache/Nginx) apuntando a `/public`

## 🤝 Contribución

1. **Fork** el repositorio
2. **Crear** una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. **Commit** tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. **Push** a la rama (`git push origin feature/nueva-funcionalidad`)
5. **Crear** un Pull Request

## 📄 Licencia

Este proyecto está bajo la licencia [MIT](LICENSE).

## 👥 Equipo de Desarrollo

- **Desarrollador Principal**: [Tu Nombre]
- **Email**: [tu.email@empresa.com]

## 📞 Soporte

Para reportar bugs o solicitar features:

- **Issues**: [GitHub Issues](https://github.com/tuusuario/ceda/issues)
- **Wiki**: [Documentación](https://github.com/tuusuario/ceda/wiki)
- **Email**: soporte@ceda.com

---

## 🔥 Quick Start

```bash
# 1. Clonar
git clone https://github.com/tuusuario/ceda.git && cd ceda

# 2. Levantar ambiente
chmod +x init-dev.sh && ./init-dev.sh

# 3. Acceder
# Web: http://localhost:8080
# Admin: http://localhost:8081
# Usuario: admin / Contraseña: admin123
```

**¡Listo para desarrollar! 🎉**