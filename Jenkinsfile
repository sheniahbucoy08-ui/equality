pipeline {
    agent any

    options {
        timestamps()
        timeout(time: 1, unit: 'HOURS')
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    environment {
        REGISTRY     = 'docker.io'
        IMAGE_NAME   = 'equalvoice'
        DB_NAME      = 'equalvoice_db'
        DB_USER      = 'equalvoice_user'
        COMPOSE_FILE = 'docker-compose.yml'
        // Docker socket path — Jenkins runs commands on the host via mounted socket
        DOCKER_HOST  = 'unix:///var/run/docker.sock'
    }

    stages {

        // ── 1. Checkout ────────────────────────────────────────────────────────
        stage('Checkout') {
            steps {
                checkout scm
                script {
                    env.GIT_SHORT = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                    env.IMAGE_TAG = "${BUILD_NUMBER}_${env.GIT_SHORT}"
                    echo "Building image tag: ${env.IMAGE_TAG}"
                    sh 'git log --oneline -5'
                }
            }
        }

        // ── 2. Create .env for compose ─────────────────────────────────────────
        stage('Environment Setup') {
            steps {
                withCredentials([
                    string(credentialsId: 'db-password',      variable: 'DB_PASS'),
                    string(credentialsId: 'db-root-password', variable: 'DB_ROOT_PASSWORD')
                ]) {
                    sh '''
                        echo "DB_NAME=${DB_NAME}"           > .env
                        echo "DB_USER=${DB_USER}"          >> .env
                        echo "DB_PASS=${DB_PASS}"          >> .env
                        echo "DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}" >> .env
                        echo "BUILD_VERSION=${BUILD_NUMBER}" >> .env
                        echo "VCS_REF=${GIT_COMMIT}"       >> .env
                        echo "Environment file created."
                        grep -v "PASS\\|PASSWORD" .env || true
                    '''
                }
            }
        }

        // ── 3. PHP syntax check ────────────────────────────────────────────────
        stage('Code Quality') {
            steps {
                sh '''
                    echo "Running PHP syntax check..."
                    if ! command -v php > /dev/null 2>&1; then
                        echo "PHP not available in Jenkins agent — skipping syntax check."
                        exit 0
                    fi
                    ERRORS=0
                    while IFS= read -r file; do
                        if ! php -l "$file" > /dev/null 2>&1; then
                            echo "SYNTAX ERROR: $file"
                            php -l "$file"
                            ERRORS=$((ERRORS + 1))
                        fi
                    done < <(find . -name "*.php" -type f \
                               ! -path "./.git/*" \
                               ! -path "./vendor/*")
                    if [ "$ERRORS" -gt 0 ]; then
                        echo "Found $ERRORS PHP syntax error(s). Aborting."
                        exit 1
                    fi
                    echo "PHP syntax check passed."
                '''
            }
        }

        // ── 4. Build Docker Image ──────────────────────────────────────────────
        stage('Build Docker Image') {
            steps {
                withCredentials([
                    usernamePassword(credentialsId: 'docker-hub-credentials',
                                     usernameVariable: 'DOCKER_USER',
                                     passwordVariable: 'DOCKER_PASS')
                ]) {
                    sh '''
                        docker build \
                            --build-arg BUILD_DATE="$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
                            --build-arg VCS_REF="${GIT_COMMIT}" \
                            --build-arg BUILD_VERSION="${BUILD_NUMBER}" \
                            -t "${REGISTRY}/${DOCKER_USER}/${IMAGE_NAME}:${IMAGE_TAG}" \
                            -t "${REGISTRY}/${DOCKER_USER}/${IMAGE_NAME}:latest" \
                            .
                        echo "Built images:"
                        docker images | grep "${IMAGE_NAME}"
                    '''
                }
            }
        }

        // ── 5. Integration test ────────────────────────────────────────────────
        stage('Test Docker Image') {
            steps {
                sh '''
                    docker-compose -f "${COMPOSE_FILE}" up -d

                    echo "Waiting for services to become healthy (up to 90s)..."
                    ELAPSED=0
                    until docker-compose -f "${COMPOSE_FILE}" ps | grep -q "healthy"; do
                        sleep 5
                        ELAPSED=$((ELAPSED + 5))
                        if [ "$ELAPSED" -ge 90 ]; then
                            echo "Services did not become healthy in time."
                            docker-compose -f "${COMPOSE_FILE}" logs
                            exit 1
                        fi
                    done
                    echo "All services healthy."

                    docker-compose -f "${COMPOSE_FILE}" exec -T web \
                        curl -sf http://localhost/index.php -o /dev/null && echo "Web OK"
                '''
            }
            post {
                always {
                    sh 'docker-compose -f "${COMPOSE_FILE}" down --volumes || true'
                }
            }
        }

        // ── 6. Security Scan ───────────────────────────────────────────────────
        stage('Security Scan') {
            steps {
                sh '''
                    echo "Checking for dangerous PHP functions..."
                    DANGEROUS=$(grep -rn -E "eval|passthru|shell_exec|proc_open|popen" \
                                    --include="*.php" \
                                    --exclude-dir=".git" \
                                    . 2>/dev/null || true)
                    if [ -n "$DANGEROUS" ]; then
                        echo "WARNING - potentially dangerous functions found:"
                        echo "$DANGEROUS"
                    else
                        echo "No dangerous functions found."
                    fi

                    echo "Checking for hardcoded credentials..."
                    CREDS=$(grep -rn -E "password[[:space:]]*=[[:space:]]*['\"][^$]" \
                                --include="*.php" \
                                --exclude-dir=".git" \
                                . 2>/dev/null || true)
                    if [ -n "$CREDS" ]; then
                        echo "WARNING - possible hardcoded credentials:"
                        echo "$CREDS"
                    else
                        echo "No hardcoded credentials detected."
                    fi

                    echo "Security scan complete."
                '''
            }
        }

        // ── 7. Push to registry (main branch only) ─────────────────────────────
        stage('Push Image') {
            when { branch 'main' }
            steps {
                withCredentials([
                    usernamePassword(credentialsId: 'docker-hub-credentials',
                                     usernameVariable: 'DOCKER_USER',
                                     passwordVariable: 'DOCKER_PASS')
                ]) {
                    sh '''
                        echo "${DOCKER_PASS}" | docker login -u "${DOCKER_USER}" --password-stdin
                        docker push "${REGISTRY}/${DOCKER_USER}/${IMAGE_NAME}:${IMAGE_TAG}"
                        docker push "${REGISTRY}/${DOCKER_USER}/${IMAGE_NAME}:latest"
                        docker logout
                        echo "Image pushed: ${REGISTRY}/${DOCKER_USER}/${IMAGE_NAME}:${IMAGE_TAG}"
                    '''
                }
            }
        }

        // ── 8. Deploy (main branch only) ───────────────────────────────────────
        stage('Deploy') {
            when { branch 'main' }
            steps {
                sh '''
                    docker-compose -f "${COMPOSE_FILE}" pull
                    docker-compose -f "${COMPOSE_FILE}" up -d --remove-orphans
                    sleep 20
                    docker-compose -f "${COMPOSE_FILE}" ps
                    echo "Deployment complete."
                '''
            }
        }

        // ── 9. Post-deployment smoke tests (main branch only) ──────────────────
        stage('Post-Deployment Tests') {
            when { branch 'main' }
            steps {
                sh '''
                    echo "Running post-deployment smoke tests..."
                    ALL_PASS=true
                    for page in index.php login.php; do
                        STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/$page)
                        if [ "$STATUS" != "200" ] && [ "$STATUS" != "302" ]; then
                            echo "WARN: $page returned HTTP $STATUS"
                            ALL_PASS=false
                        else
                            echo "OK:   $page returned HTTP $STATUS"
                        fi
                    done
                    if [ "$ALL_PASS" = "false" ]; then
                        exit 1
                    fi
                    echo "Post-deployment tests passed."
                '''
            }
        }
    }

    post {
        always {
            node('') {
                sh '''
                    rm -f .env || true
                    docker system prune -f || true
                '''
            }
        }
        success {
            echo "Pipeline completed successfully - Build ${BUILD_NUMBER}"
        }
        failure {
            node('') {
                sh '''
                    echo "===== BUILD FAILED - collecting logs ====="
                    docker-compose -f docker-compose.yml logs --no-color 2>&1 | tail -100 || true
                '''
            }
        }
        cleanup {
            node('') {
                deleteDir()
            }
        }
    }
}
