pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 30, unit: 'MINUTES')
        timestamps()
    }

    environment {
        IMAGE_NAME           = 'equalvoice-app'
        IMAGE_TAG            = "${env.BUILD_NUMBER}"
        COMPOSE_PROJECT_NAME = 'equalvoice'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
                script {
                    env.GIT_COMMIT_SHORT = sh(returnStdout: true, script: 'git rev-parse --short HEAD').trim()
                    env.GIT_BRANCH       = sh(returnStdout: true, script: 'git rev-parse --abbrev-ref HEAD').trim()
                }
            }
        }

        stage('Build (job1)') {
            steps {
                build job: 'job1', wait: true
            }
        }

        stage('Test (job2)') {
            steps {
                build job: 'job2', wait: true
            }
        }

        stage('Deploy (job3)') {
            when { branch 'main' }
            steps {
                build job: 'job3',
                      parameters: [
                          string(name: 'ENVIRONMENT', value: 'staging'),
                          booleanParam(name: 'RUN_MIGRATIONS', value: true)
                      ],
                      wait: true
            }
        }
    }

    post {
        success { echo "Pipeline OK — build ${env.BUILD_NUMBER}" }
        failure { echo "Pipeline FAILED — build ${env.BUILD_NUMBER}" }
    }
}
