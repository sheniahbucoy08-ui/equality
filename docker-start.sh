#!/bin/bash
# EqualVoice Docker Quick Start Script

set -e

echo "╔════════════════════════════════════════════╗"
echo "║   EqualVoice Docker Setup & Start Script  ║"
echo "╚════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }

# Check if Docker is installed
print_info "Checking Docker installation..."
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi
print_success "Docker is installed"

# Check if Docker Compose is installed
print_info "Checking Docker Compose installation..."
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi
print_success "Docker Compose is installed"

# Check if .env file exists
print_info "Checking environment file..."
if [ ! -f ".env" ]; then
    print_warning "No .env file found. Creating from .env.example..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
        print_success ".env file created"
    else
        print_error ".env.example not found"
        exit 1
    fi
else
    print_success ".env file exists"
fi

# Check if Docker daemon is running
print_info "Checking Docker daemon..."
if ! docker ps &> /dev/null; then
    print_error "Docker daemon is not running. Please start Docker and try again."
    exit 1
fi
print_success "Docker daemon is running"

echo ""
print_info "Available commands:"
echo "  1) Build and start containers"
echo "  2) Start containers (no build)"
echo "  3) Stop containers"
echo "  4) View logs"
echo "  5) Reset everything (remove volumes)"
echo "  6) Import database"
echo "  7) Backup database"
echo "  0) Exit"
echo ""

read -p "Select option (0-7): " choice

case $choice in
    1)
        print_info "Building and starting containers..."
        docker-compose down --remove-orphans 2>/dev/null || true
        docker-compose up -d --build
        print_success "Containers are starting..."
        echo ""
        sleep 5
        docker-compose ps
        echo ""
        print_info "Access your application:"
        echo "  🌐 Main App: http://localhost"
        echo "  📊 phpMyAdmin: http://localhost:8080"
        echo "  🗄️  Database: mysql:3306"
        ;;
    2)
        print_info "Starting containers..."
        docker-compose up -d
        print_success "Containers started"
        docker-compose ps
        ;;
    3)
        print_info "Stopping containers..."
        docker-compose down
        print_success "Containers stopped"
        ;;
    4)
        echo ""
        read -p "View logs for which service? (web/mysql/phpmyadmin/all): " service
        if [ "$service" == "all" ]; then
            docker-compose logs -f
        else
            docker-compose logs -f "$service"
        fi
        ;;
    5)
        print_warning "This will remove all containers and data!"
        read -p "Are you sure? (yes/no): " confirm
        if [ "$confirm" == "yes" ]; then
            print_info "Removing all containers and volumes..."
            docker-compose down -v
            print_success "Everything has been removed"
        else
            print_info "Cancelled"
        fi
        ;;
    6)
        print_info "Importing database..."
        if [ -f "sql/equalvoice.sql" ]; then
            docker-compose exec -T mysql mysql -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d= -f2)" equalvoice_db < sql/equalvoice.sql
            print_success "Database imported successfully"
        else
            print_error "sql/equalvoice.sql not found"
        fi
        ;;
    7)
        print_info "Backing up database..."
        TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
        BACKUP_FILE="backup_${TIMESTAMP}.sql"
        docker-compose exec -T mysql mysqldump -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d= -f2)" equalvoice_db > "$BACKUP_FILE"
        print_success "Database backed up to: $BACKUP_FILE"
        ;;
    0)
        print_info "Exiting..."
        exit 0
        ;;
    *)
        print_error "Invalid option"
        exit 1
        ;;
esac

echo ""
print_success "Done!"
