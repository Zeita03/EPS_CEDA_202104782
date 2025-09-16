#!/bin/bash

# Script de inicialización para el ambiente de desarrollo CEDA

echo "🐋 Inicializando ambiente de desarrollo CEDA con Docker..."

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado. Por favor instálalo primero."
    exit 1
fi

# Verificar si Docker Compose está instalado
if ! docker compose version &> /dev/null; then
    echo "❌ Docker Compose no está instalado. Por favor instálalo primero."
    exit 1
fi

# Crear directorios necesarios
echo "📁 Creando directorios necesarios..."
mkdir -p data/cache
mkdir -p archivos
chmod 777 data/cache
chmod 777 archivos

# Detener contenedores existentes si están corriendo
echo "🛑 Deteniendo contenedores existentes..."
docker compose down

# Construir e iniciar contenedores
echo "🏗️  Construyendo e iniciando contenedores..."
docker compose up --build -d

# Esperar a que MariaDB esté listo
echo "⏳ Esperando a que MariaDB esté listo..."
sleep 30

# Verificar estado de los contenedores
echo "📊 Verificando estado de los contenedores..."
docker compose ps

echo ""
echo "✅ ¡Ambiente de desarrollo listo!"
echo ""
echo "🌐 Servicios disponibles:"
echo "   - Aplicación CEDA: http://localhost:8080"
echo "   - phpMyAdmin: http://localhost:8081"
echo "   - Base de datos MariaDB: localhost:3306"
echo ""
echo "📋 Credenciales de la base de datos:"
echo "   - Usuario: ceda_user"
echo "   - Contraseña: ceda_password"
echo "   - Base de datos: ceda"
echo ""
echo "🔧 Para ver logs en tiempo real:"
echo "   docker-compose logs -f"
echo ""
echo "🛑 Para detener el ambiente:"
echo "   docker-compose down"
