#!/bin/bash

# Script de mantenimiento para el ambiente de desarrollo CEDA

show_help() {
    echo "🔧 Script de mantenimiento CEDA"
    echo ""
    echo "Uso: ./maintenance.sh [COMANDO]"
    echo ""
    echo "Comandos disponibles:"
    echo "  start       - Iniciar todos los contenedores"
    echo "  stop        - Detener todos los contenedores"
    echo "  restart     - Reiniciar todos los contenedores"
    echo "  rebuild     - Reconstruir e iniciar contenedores"
    echo "  logs        - Ver logs en tiempo real"
    echo "  db-backup   - Crear respaldo de la base de datos"
    echo "  db-restore  - Restaurar base de datos desde backup"
    echo "  composer    - Ejecutar comandos de composer en el contenedor"
    echo "  php         - Ejecutar comandos PHP en el contenedor"
    echo "  shell       - Abrir shell en el contenedor web"
    echo "  status      - Ver estado de los contenedores"
    echo "  clean       - Limpiar contenedores, imágenes y volúmenes no usados"
    echo "  help        - Mostrar esta ayuda"
}

case "$1" in
    "start")
        echo "🚀 Iniciando contenedores..."
        docker compose up -d
        docker compose ps
        ;;
    "stop")
        echo "🛑 Deteniendo contenedores..."
        docker compose down
        ;;
    "restart")
        echo "🔄 Reiniciando contenedores..."
        docker compose down
        docker compose up -d
        docker compose ps
        ;;
    "rebuild")
        echo "🏗️  Reconstruyendo contenedores..."
        docker compose down
        docker compose build --no-cache
        docker compose up -d
        docker compose ps
        ;;
    "logs")
        echo "📋 Mostrando logs en tiempo real (Ctrl+C para salir)..."
        docker compose logs -f
        ;;
    "db-backup")
        echo "💾 Creando respaldo de la base de datos..."
        BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
        docker compose exec database mysqldump -u ceda_user -pceda_password ceda > "$BACKUP_FILE"
        echo "✅ Respaldo creado: $BACKUP_FILE"
        ;;
    "db-restore")
        if [ -z "$2" ]; then
            echo "❌ Especifica el archivo de respaldo: ./maintenance.sh db-restore archivo.sql"
            exit 1
        fi
        echo "📥 Restaurando base de datos desde $2..."
        docker compose exec -T database mysql -u ceda_user -pceda_password ceda < "$2"
        echo "✅ Base de datos restaurada"
        ;;
    "composer")
        shift
        echo "📦 Ejecutando composer en el contenedor..."
        docker compose exec web composer "$@"
        ;;
    "php")
        shift
        echo "🐘 Ejecutando PHP en el contenedor..."
        docker compose exec web php "$@"
        ;;
    "shell")
        echo "🐚 Abriendo shell en el contenedor web..."
        docker compose exec web bash
        ;;
    "status")
        echo "📊 Estado de los contenedores:"
        docker compose ps
        echo ""
        echo "📈 Uso de recursos:"
        docker stats --no-stream
        ;;
    "clean")
        echo "🧹 Limpiando contenedores, imágenes y volúmenes no usados..."
        docker compose down
        docker system prune -f
        docker volume prune -f
        echo "✅ Limpieza completada"
        ;;
    "help"|"")
        show_help
        ;;
    *)
        echo "❌ Comando desconocido: $1"
        show_help
        exit 1
        ;;
esac
