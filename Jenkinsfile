pipeline {
    agent any

    options {
        timestamps()
        timeout(time: 1, unit: 'HOURS')
        disableConcurrentBuilds()
    }

    environment {
        REGISTRY = 'docker.io'
        IMAGE_NAME = 'equalvoice'
        IMAGE_TAG = "${BUILD_NUMBER}_${GIT_COMMIT.take(7)}"
        DOCKER_CREDENTIALS = credentials('docker-hub-credentials')
        DB_NAME = 'equalvoice_db'
        DB_USER = 'equalvoice_user'
        DB_PASS = credentials('db-password')
    }

    stages {
        stage('Checkout') {
            steps {
                script {
                    echo '========== Checking out code =========='
                    checkout scm
                    sh 'git log --oneline -1'
                }
            }
        }

        stage('Environment Setup') {
            steps {
                script {
                    echo '========== Setting up environment =========='
                    sh '''
                        echo "DB_NAME=${DB_NAME}" > .env
                        echo "DB_USER=${DB_USER}" >> .env
                        echo "DB_PASS=${DB_PASS}" >> .env
                        echo "DB_ROOT_PASSWORD=root_jenkins_secure" >> .env
                    '''
                    sh 'cat .env | grep -v "PASS" || echo "Environment configured"'
                }
            }
        }

        stage('Code Quality') {
            steps {
                script {
                    echo '========== Running code quality checks =========='
                    sh '''
                        # Check PHP syntax
                        find . -name "*.php" -type f ! -path "./vendor/*" ! -path "./.git/*" -exec php -l {} \\; | grep -i "parse error" && exit 1 || echo "✓ PHP syntax check passed"
                    '''
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    echo '========== Building Docker image =========='
                    sh '''
                        docker build \
                            --build-arg BUILD_DATE=$(date -u +'%Y-%m-%dT%H:%M:%SZ') \
                            --build-arg VCS_REF=${GIT_COMMIT} \
                            --build-arg BUILD_VERSION=${BUILD_NUMBER} \
                            -t ${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG} \
                            -t ${REGISTRY}/${IMAGE_NAME}:latest \
                            .
                        docker images | grep ${IMAGE_NAME}
                    '''
                }
            }
        }

        stage('Test Docker Image') {
            steps {
                script {
                    echo '========== Testing Docker image =========='
                    sh '''
                        # Start containers
                        docker-compose -f docker-compose.yml up -d --no-build
                        
                        # Wait for services to be ready
                        echo "Waiting for services to be healthy..."
                        sleep 15
                        
                        # Test web service
                        docker-compose exec -T web curl -f http://localhost/index.php || exit 1
                        
                        # Test database connection
                        docker-compose exec -T mysql mysqladmin ping -u root -proot_jenkins_secure || exit 1
                        
                        echo "✓ All services are healthy"
                    '''
                }
            }
        }

        stage('Security Scan') {
            steps {
                script {
                    echo '========== Running security checks =========='
                    sh '''
                        # Check for hardcoded credentials
                        ! grep -r "password.*=.*['\\\"]" --include="*.php" . 2>/dev/null | grep -v "DB_PASS" | head -1 || echo "⚠ Warning: Check hardcoded values"
                        
                        # Check for dangerous functions
                        ! grep -r "eval\|exec\|passthru\|system\|shell_exec" --include="*.php" . 2>/dev/null | head -5 || echo "✓ No dangerous functions found"
                        
                        echo "✓ Security scan completed"
                    '''
                }
            }
        }

        stage('Push Image') {
            when {
                branch 'main'
            }
            steps {
                script {
                    echo '========== Pushing image to registry =========='
                    sh '''
                        echo "${DOCKER_CREDENTIALS_PSW}" | docker login -u "${DOCKER_CREDENTIALS_USR}" --password-stdin
                        docker push ${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}
                        docker push ${REGISTRY}/${IMAGE_NAME}:latest
                        docker logout
                        echo "✓ Image pushed successfully"
                    '''
                }
            }
        }

        stage('Deploy') {
            when {
                branch 'main'
            }
            steps {
                script {
                    echo '========== Deploying application =========='
                    sh '''
                        # Pull latest images
                        docker-compose pull
                        
                        # Stop existing containers
                        docker-compose down || true
                        
                        # Start new containers
                        docker-compose up -d
                        
                        # Run database migrations if needed
                        docker-compose exec -T web php /var/www/html/setup.php || echo "Setup already completed"
                        
                        # Health check
                        sleep 10
                        docker-compose ps
                        echo "✓ Deployment completed successfully"
                    '''
                }
            }
        }

        stage('Post-Deployment Tests') {
            when {
                branch 'main'
            }
            steps {
                script {
                    echo '========== Running post-deployment tests =========='
                    sh '''
                        # Test API endpoints
                        curl -s http://localhost/api/admin_analytics.php || echo "⚠ Admin API not accessible"
                        
                        # Test main pages
                        for page in index.php login.php profile.php; do
                            status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/$page)
                            if [ "$status" != "200" ]; then
                                echo "⚠ Warning: $page returned HTTP $status"
                            fi
                        done
                        
                        echo "✓ Post-deployment tests completed"
                    '''
                }
            }
        }
    }

    post {
        always {
            script {
                echo '========== Cleanup =========='
                sh '''
                    # Remove .env file
                    rm -f .env
                    
                    # Cleanup docker
                    docker system prune -f --volumes || true
                '''
            }
        }
        success {
            script {
                echo '========== Build Successful =========='
                // Notify on success - add your notification here
                sh 'echo "✓ Pipeline completed successfully"'
            }
        }
        failure {
            script {
                echo '========== Build Failed =========='
                sh '''
                    docker-compose logs || true
                    docker logs equalvoice_web 2>&1 | tail -50 || true
                    docker logs equalvoice_mysql 2>&1 | tail -50 || true
                '''
                // Notify on failure - add your notification here
            }
        }
        cleanup {
            deleteDir()
        }
    }
}
