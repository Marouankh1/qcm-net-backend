pipeline {
    agent any
    environment {
        DOCKER_HUB_USER = "marouankhdev1" 
        IMAGE_NAME = "qcm-net-backend" 
        REGISTRY = "${DOCKER_HUB_USER}/${IMAGE_NAME}"
        SCANNER_HOME = tool 'sonar-scanner'
    }
    stages {
        stage('Checkout') {
            steps { checkout scm }
        }
        stage('SAST: SonarQube') {
            steps {
                withSonarQubeEnv('SonarQube-Server') { 
                    bat "${SCANNER_HOME}/bin/sonar-scanner -Dsonar.projectKey=qcm-net-back -Dsonar.sources=. -Dsonar.exclusions=vendor/**,storage/**,tests/**"
                }
            }
        }
        stage('Build & Image Scan') {
            steps {
                bat "docker build -t ${REGISTRY}:latest -t ${REGISTRY}:${BUILD_NUMBER} ."
                bat "C:\\trivy\\trivy.exe image --severity HIGH,CRITICAL ${REGISTRY}:latest"
            }
        }
        stage('Push to Docker Hub') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'docker-hub-credentials', passwordVariable: 'PASS', usernameVariable: 'USER')]) {
                    bat "echo %PASS% | docker login -u %USER% --password-stdin"
                    bat "docker push ${REGISTRY}:latest"
                    bat "docker push ${REGISTRY}:${BUILD_NUMBER}"
                }
            }
        }
    }
    post {
        success {
            slackSend(color: '#00FF00',   
                    channel: '#qcm-net-channel',
                    message: "SUCCESS: Job '${env.JOB_NAME}' [${env.BUILD_NUMBER}]", 
                    tokenCredentialId: 'slack-webhook-url')
        }
        failure {
            slackSend(color: '#FF0000',   
                    channel: '#qcm-net-channel',
                    message: "FAILURE: Job '${env.JOB_NAME}' [${env.BUILD_NUMBER}]", 
                    tokenCredentialId: 'slack-webhook-url')
        }
    }
}