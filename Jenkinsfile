pipeline {
    agent any
    environment {
        DOCKER_HUB_USER = "marouankhdev1" 
        IMAGE_NAME = "backend-qcm-net" 
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
                    bat "echo %PASS% | docker login -u %USER% --password-stdin"
                    bat "docker push ${REGISTRY}:latest"
                    bat "docker push ${REGISTRY}:${BUILD_NUMBER}"
                }
            }
        }
    }
}