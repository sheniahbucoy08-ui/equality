# EqualVoice Docker & Jenkins Setup Guide

## 📋 Overview

This guide provides instructions for setting up the EqualVoice application using Docker and Jenkins for containerization and CI/CD automation.

---

## 🐳 Docker Setup

### Prerequisites

- Docker Desktop (or Docker Engine)
- Docker Compose (usually included with Docker Desktop)
- Git
- 2GB minimum free disk space

### Quick Start

1. **Clone and navigate to the project:**
   ```bash
   cd ~/projects/equalvoice
   ```

2. **Create environment file:**
   ```bash
   cp .env.example .env
   ```

3. **Build and start services:**
   ```bash
   docker-compose up -d --build
   ```

4. **Access the application:**
   - **Main App**: http://localhost
   - **phpMyAdmin**: http://localhost:8080
   - **Database Host**: `mysql:3306`

### Docker Services

#### Web Service (PHP 8.1 + Apache)
- Port: `80` (HTTP)
- Health check: Every 30 seconds
- Volumes: Application code mounted

#### MySQL Database
- Port: `3306`
- Database: `equalvoice_db`
- Root Password: `root_password_123` (change in production)
- User: `equalvoice_user`

#### phpMyAdmin
- Port: `8080`
- Database management interface
- Access: http://localhost:8080

### Common Docker Commands

```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f web
docker-compose logs -f mysql

# Stop services
docker-compose down

# Rebuild images
docker-compose build --no-cache

# Execute command in container
docker-compose exec web php -v
docker-compose exec mysql mysql -u root -p${DB_ROOT_PASSWORD} -e "SHOW DATABASES;"

# Remove all containers and volumes
docker-compose down -v
```

---

## 🚀 Jenkins CI/CD Pipeline

### Prerequisites

- Jenkins server (2.300+)
- Docker installed on Jenkins agent
- GitHub/Git repository access
- Docker Hub account (for image registry)

### Jenkins Setup

1. **Create Jenkins Credentials:**
   ```
   - docker-hub-credentials: Docker Hub username/token
   - db-password: Secure database password
   ```

2. **Create New Pipeline Job:**
   - Job Type: Pipeline
   - Pipeline Definition: Pipeline script from SCM
   - SCM: Git
   - Repository URL: `https://github.com/sheniahbucoy08-ui/equality.git`
   - Script Path: `Jenkinsfile`
   - Branch: `*/main`

3. **Configure Job Triggers:**
   - GitHub webhook (for auto-trigger on push)
   - Poll SCM: `H/15 * * * *` (every 15 minutes)

### Pipeline Stages

1. **Checkout** - Clone repository
2. **Environment Setup** - Create .env file
3. **Code Quality** - PHP syntax validation
4. **Build Docker Image** - Create container image
5. **Test Docker Image** - Run services and health checks
6. **Security Scan** - Check for vulnerabilities
7. **Push Image** - Upload to Docker registry (main branch only)
8. **Deploy** - Start production containers
9. **Post-Deployment Tests** - Verify endpoints

### Jenkins Environment Variables

```
REGISTRY=docker.io
IMAGE_NAME=equalvoice
DB_NAME=equalvoice_db
DB_USER=equalvoice_user
```

---

## 🔒 Production Deployment

### Environment Variables (.env)

Create production `.env` file:
```env
DB_HOST=mysql
DB_NAME=equalvoice_db
DB_USER=equalvoice_user
DB_PASS=secure_password_here
DB_ROOT_PASSWORD=secure_root_password
APP_ENV=production
APP_DEBUG=false
```

### Security Checklist

- [ ] Change default MySQL passwords
- [ ] Use HTTPS/SSL certificates
- [ ] Enable firewall rules
- [ ] Set up regular backups
- [ ] Use environment variables for secrets
- [ ] Implement database user privileges
- [ ] Enable Apache security headers
- [ ] Configure rate limiting
- [ ] Set up logging and monitoring

### Database Backup

```bash
# Backup
docker-compose exec mysql mysqldump -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} > backup.sql

# Restore
docker-compose exec -T mysql mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} < backup.sql
```

### SSL/HTTPS Setup

1. **Generate SSL Certificate:**
   ```bash
   mkdir -p docker/ssl
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout docker/ssl/privkey.pem \
     -out docker/ssl/fullchain.pem
   ```

2. **Update apache.conf** - Uncomment SSL section

3. **Update docker-compose.yml:**
   ```yaml
   ports:
     - "443:443"
   volumes:
     - ./docker/ssl:/etc/ssl/certs
   ```

### Performance Optimization

- Use Docker volume for database persistence
- Enable opcache for PHP (configured in php.ini)
- Configure Apache caching for static files
- Use CDN for static assets
- Monitor container resource usage

---

## 🧪 Testing

### Manual Testing

```bash
# Test web service
docker-compose exec web curl http://localhost/index.php

# Test database connection
docker-compose exec mysql mysql -u root -proot_password_123 -e "SHOW DATABASES;"

# Test API endpoint
curl http://localhost/api/admin_analytics.php

# Check PHP configuration
docker-compose exec web php -i
```

### Automated Testing

Run through Jenkins pipeline:
- PHP syntax validation
- Docker image build verification
- Service health checks
- Endpoint availability tests

---

## 📊 Monitoring & Logging

### View Logs

```bash
# Web server logs
docker-compose logs -f web

# MySQL logs
docker-compose logs -f mysql

# All services
docker-compose logs -f

# Specific time range
docker-compose logs --since 2024-05-17 --until 2024-05-18
```

### Container Inspection

```bash
# Container status
docker-compose ps

# Container processes
docker-compose top web

# Container resource usage
docker stats

# Container events
docker events --filter container=equalvoice_web
```

---

## 🐛 Troubleshooting

### MySQL Connection Error

```bash
# Check MySQL is running
docker-compose ps mysql

# Verify credentials in .env
docker-compose logs mysql

# Test connection
docker-compose exec web mysql -h mysql -u equalvoice_user -p equalvoice_db -e "SELECT 1;"
```

### Web Service Not Starting

```bash
# Check Apache logs
docker-compose logs web

# Verify PHP syntax
docker-compose exec web php -l /var/www/html/index.php

# Check file permissions
docker-compose exec web ls -la /var/www/html
```

### Database Import Issues

```bash
# Check if SQL file exists
ls -la sql/

# Try manual import
docker-compose exec mysql mysql -u root -proot_password_123 equalvoice_db < sql/equalvoice.sql

# Check MySQL version compatibility
docker-compose exec mysql mysql --version
```

### Port Already in Use

```bash
# Find process using port
lsof -i :80
lsof -i :3306

# Change port in docker-compose.yml
# ports:
#   - "8000:80"  # Change 80 to different port
```

---

## 📝 Best Practices

1. **Always use version control** - Track all changes
2. **Use .env for secrets** - Never commit sensitive data
3. **Keep backups** - Regular database snapshots
4. **Monitor logs** - Check for errors regularly
5. **Update dependencies** - Keep base images current
6. **Test changes** - Use staging environment first
7. **Document changes** - Maintain configuration records
8. **Use health checks** - Detect service failures
9. **Implement security** - Use strong passwords and SSL
10. **Scale wisely** - Monitor resource usage

---

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Jenkins Documentation](https://www.jenkins.io/doc/)
- [PHP Docker Images](https://hub.docker.com/_/php)
- [MySQL Docker Images](https://hub.docker.com/_/mysql)

---

## 📞 Support

For issues or questions:
1. Check the logs: `docker-compose logs`
2. Review the troubleshooting section
3. Check Docker and Jenkins documentation
4. Contact development team

---

**Last Updated**: May 17, 2026
**Version**: 1.0.0
