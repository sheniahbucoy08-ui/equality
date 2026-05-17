pipeline {
    agent any

    options {
        timestamps()
        timeout(time: 1, unit: 'HOURS')
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    environment {
        REGISTRY             = 'docker.io'
        IMAGE_NAME           = 'equalvoice'
        // Tag = buildNumber_shortCommit  (GIT_COMMIT available after checkout)
        DOCKER_CREDS         = credentials('docker-hub-credentials')
        DB_NAME              = 'equalvoice_db'
        DB_USER              = 'equalvoice_user'
        DB_PASS              = credentials('db-password')
        DB_ROOT_PASSWORD     = credentials('db-root-password')
        COMPOSE_FILE         = 'docker-compose.yml'
        COMPOSE_PROJECT_NAME = "equalvoice_${BUILD_NUMBER}"
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
                script {
                    // Write .env without printing passwords to the log
                    writeFile file: '.env', text: """DB_NAME=${env.DB_NAME}
DB_USER=${env.DB_USER}
DB_PASS=${env.DB_PASS}
DB_ROOT_PASSWORD=${env.DB_ROOT_PASSWORD}
BUILD_DATE=${sh(script:'date -u +%Y-%m-%dT%H:%M:%SZ', returnStdout: true).trim()}
VCS_REF=${env.GIT_COMMIT}
BUILD_VERSION=${env.BUILD_NUMBER}
"""
                    sh 'echo "Environment file created (passwords redacted)"'
                    sh 'grep -v "PASS\\|PASSWORD" .env || true'
                }
            }
        }

        // ── 3. PHP syntax check ────────────────────────────────────────────────
        // Runs inside a throwaway php:8.1-cli container so we don't depend on
        // PHP being installed on the Jenkins agent. Uses a temp file instead of
        // bash process substitution so it works under /bin/sh (dash).
        stage('Code Quality') {
            steps {
                sh '''
                    echo "Running PHP syntax check..."

                    find . -type f -name "*.php" \
                        ! -path "./.git/*" \
                        ! -path "./vendor/*" \
                        ! -path "./node_modules/*" > php_files.txt

                    TOTAL=$(wc -l < php_files.txt | tr -d " ")
                    echo "Found ${TOTAL} PHP file(s) to check."

                    if [ "${TOTAL}" -eq 0 ]; then
                        echo "No PHP files found — skipping."
                        rm -f php_files.txt
                        exit 0
                    fi

                    docker run --rm \
                        -v "$(pwd)":/app \
                        -w /app \
                        php:8.1-cli \
                        sh -c '
                            ERRORS=0
                            while IFS= read -r file; do
                                if ! php -l "$file" > /dev/null 2>&1; then
                                    echo "SYNTAX ERROR: $file"
                                    php -l "$file"
                                    ERRORS=$((ERRORS + 1))
                                fi
                            done < php_files.txt
                            if [ "$ERRORS" -gt 0 ]; then
                                echo "Found $ERRORS PHP syntax error(s). Aborting."
                                exit 1
                            fi
                            echo "PHP syntax check passed."
                        '
                    RC=$?
                    rm -f php_files.txt
                    exit $RC
                '''
            }
        }

        // ── 4. Build Docker image ──────────────────────────────────────────────
        stage('Build Docker Image') {
            steps {
                sh '''
                    docker build \
                        --build-arg BUILD_DATE="$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
                        --build-arg VCS_REF="${GIT_COMMIT}" \
                        --build-arg BUILD_VERSION="${BUILD_NUMBER}" \
                        -t "${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}" \
                        -t "${REGISTRY}/${IMAGE_NAME}:latest" \
                        .
                    echo "Built images:"
                    docker images | grep "${IMAGE_NAME}"
                '''
            }
        }

        // ── 5. Integration test ────────────────────────────────────────────────
        stage('Test Docker Image') {
            steps {
                sh '''
                    docker-compose -f "${COMPOSE_FILE}" -p "${COMPOSE_PROJECT_NAME}" up -d

                    # Wait specifically for the web container to be healthy.
                    # Checking any service for "healthy" is not enough — MySQL can become
                    # healthy while the web container is still starting Apache.
                    echo "Waiting for web container to become healthy (up to 180s)..."
                    ELAPSED=0
                    WEB_CONTAINER=$(docker-compose -f "${COMPOSE_FILE}" \
                                        -p "${COMPOSE_PROJECT_NAME}" ps -q web 2>/dev/null)
                    until [ "$(docker inspect --format='{{.State.Health.Status}}' \
                                "${WEB_CONTAINER}" 2>/dev/null)" = "healthy" ]; do
                        sleep 5
                        ELAPSED=$((ELAPSED + 5))
                        # Refresh container ID in case compose restarted it
                        WEB_CONTAINER=$(docker-compose -f "${COMPOSE_FILE}" \
                                            -p "${COMPOSE_PROJECT_NAME}" ps -q web 2>/dev/null)
                        if [ "$ELAPSED" -ge 180 ]; then
                            echo "Web service did not become healthy in time."
                            docker-compose -f "${COMPOSE_FILE}" -p "${COMPOSE_PROJECT_NAME}" logs
                            exit 1
                        fi
                        echo "  Waited ${ELAPSED}s — web status: $(docker inspect \
                            --format='{{.State.Health.Status}}' "${WEB_CONTAINER}" 2>/dev/null || echo unknown)"
                    done
                    echo "Web service healthy."

                    # Verify the app responds — retry up to 5 times in case of a brief lag
                    echo "Checking HTTP response from web..."
                    PASS=false
                    for i in 1 2 3 4 5; do
                        if docker-compose -f "${COMPOSE_FILE}" -p "${COMPOSE_PROJECT_NAME}" \
                                exec -T web curl -sf --max-time 10 http://localhost/index.php \
                                -o /dev/null; then
                            echo "Web OK (attempt $i)"
                            PASS=true
                            break
                        fi
                        echo "  Attempt $i failed — retrying in 5s..."
                        sleep 5
                    done
                    if [ "$PASS" = "false" ]; then
                        echo "Web health check failed after 5 attempts."
                        docker-compose -f "${COMPOSE_FILE}" -p "${COMPOSE_PROJECT_NAME}" logs web
                        exit 1
                    fi

                    # Verify MySQL is reachable
                    docker-compose -f "${COMPOSE_FILE}" -p "${COMPOSE_PROJECT_NAME}" \
                        exec -T mysql mysqladmin ping -u root \
                        --password="${DB_ROOT_PASSWORD}" --silent

                    echo "Integration tests passed."
                '''
            }
            post {
                always {
                    sh 'docker-compose -f "${COMPOSE_FILE}" -p "${COMPOSE_PROJECT_NAME}" down --volumes --remove-orphans || true'
                }
            }
        }

        // ── 6. Security scan ───────────────────────────────────────────────────
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
                sh '''
                    echo "${DOCKER_CREDS_PSW}" | \
                        docker login -u "${DOCKER_CREDS_USR}" --password-stdin "${REGISTRY}"

                    docker push "${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}"
                    docker push "${REGISTRY}/${IMAGE_NAME}:latest"

                    docker logout "${REGISTRY}"
                    echo "Image pushed: ${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}"
                '''
            }
        }

        // ── 8. Deploy (main branch only) ───────────────────────────────────────
        stage('Deploy') {
            when { branch 'main' }
            steps {
                sh '''
                    # Update images and redeploy
                    docker-compose -f "${COMPOSE_FILE}" pull
                    docker-compose -f "${COMPOSE_FILE}" up -d --remove-orphans

                    # Wait for healthy state before running setup
                    sleep 20
                    docker-compose -f "${COMPOSE_FILE}" ps

                    # One-time setup (idempotent)
                    docker-compose -f "${COMPOSE_FILE}" exec -T web \
                        php /var/www/html/setup.php || echo "Setup already completed or not required."

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
                        echo "One or more pages returned unexpected status codes."
                        exit 1
                    fi
                    echo "Post-deployment tests passed."
                '''
            }
        }
    }

    post {
        always {
            sh '''
                # Remove .env (contains secrets)
                rm -f .env

                # Prune dangling images / stopped containers — preserve volumes
                docker system prune -f || true
            '''
        }
        success {
            echo "Pipeline completed successfully - Build ${BUILD_NUMBER} (${env.IMAGE_TAG})"
        }
        failure {
            sh '''
                echo "===== BUILD FAILED — collecting logs ====="
                docker-compose -f "${COMPOSE_FILE}" logs --no-color 2>&1 | tail -100 || true
            '''
            // Uncomment and configure to add Slack / email notifications:
            // slackSend channel: '#deployments', color: 'danger',
            //           message: "FAILED: ${JOB_NAME} #${BUILD_NUMBER}"
        }
        cleanup {
            // Always clean the workspace so credentials are not left on disk
            deleteDir()
        }
    }
}