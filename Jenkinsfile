pipeline {
    agent any

    stages {
        stage('1. Deteksi Branch & Sinkronisasi') {
            steps {
                script {
                    // Deteksi jika branch saat ini adalah develop (Staging)
                    if (env.BRANCH_NAME == 'develop') {
                        echo '🌿 Branch DEVELOP dideteksi. Sinkronisasi ke /srv/apps/finance-staging...'
                        sh '''
                            cd /srv/apps/finance-staging
                            git fetch origin
                            git checkout develop
                            git reset --hard origin/develop
                        '''
                    } 
                    // Deteksi jika branch saat ini adalah main (Production)
                    else if (env.BRANCH_NAME == 'main') {
                        echo '🚀 Branch MAIN dideteksi. Menggunakan Jenkins Workspace untuk Production...'
                        checkout scm
                    } 
                    else {
                        error "Abaikan build. Branch [${env.BRANCH_NAME}] tidak diatur untuk auto-deploy."
                    }
                }
            }
        }

        stage('2. Build Container Layer') {
            steps {
                script {
                    if (env.BRANCH_NAME == 'develop') {
                        sh '''
                            cd /srv/apps/finance-staging
                            docker compose -f docker-compose.yml build --no-cache app-staging
                        '''
                    } else if (env.BRANCH_NAME == 'main') {
                        sh "docker compose -f docker-compose.yml build --no-cache app-prod"
                    }
                }
            }
        }

        stage('3. Deploy & Optimasi Laravel') {
            steps {
                script {
                    if (env.BRANCH_NAME == 'develop') {
                        sh '''
                            cd /srv/apps/finance-staging
                            docker compose -f docker-compose.yml up -d
                            docker exec -t finance-staging_app php artisan migrate --force
                            docker exec -t finance-staging_app php artisan optimize
                        '''
                        echo '✅ Selesai! Staging (finance-staging_app) berhasil diperbarui.'
                    } else if (env.BRANCH_NAME == 'main') {
                        sh '''
                            docker compose -f docker-composo.yml up -d
                            docker exec -t asset_prod_app php artisan migrate --force
                            docker exec -t asset_prod_app php artisan optimize
                        '''
                        echo '🚀 Selesai! Production (asset_prod_app) berhasil diperbarui.'
                    }
                }
            }
        }
    }

    post {
        success {
            echo "🎉 Pipeline berhasil mengeksekusi branch [${env.BRANCH_NAME}] dengan aman!"
        }
        failure {
            echo "❌ Pipeline gagal pada branch [${env.BRANCH_NAME}]. Silakan periksa log di atas."
        }
    }
}
