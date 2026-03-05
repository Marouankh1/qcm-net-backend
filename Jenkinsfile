pipeline {
    agent any
    environment {
        DOCKER_HUB_USER = "marouankhdev1" 
        IMAGE_NAME = "qcm-net-backend" 
        REGISTRY = "${DOCKER_HUB_USER}/${IMAGE_NAME}"
    }
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        stage('Build Image') {
            steps {
                bat "docker build -t ${REGISTRY}:latest -t ${REGISTRY}:${BUILD_NUMBER} ."
            }
        }
        stage('Push to Docker Hub') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'docker-hub-credentials', passwordVariable: 'PASS', usernameVariable: 'USER')]) {
                    bat "docker login -u %USER% -p %PASS%"
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