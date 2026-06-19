pipeline {
    agent any

    stages {
        stage('1. Deteksi Jalur & Sinkronisasi') {
            steps {
                script {
                    if (env.WORKSPACE.toLowerCase().contains('staging')) {
                        echo '🌿 WORKSPACE STAGING DIDETEKSI!'
                        echo 'Menyelaraskan kode ke folder /srv/apps/finance-staging...'
                        sh '''
                            cd /srv/apps/finance-staging
                            git config --global --add safe.directory /srv/apps/finance-staging
                            git fetch origin
                            git reset --hard HEAD
                            git clean -fd
                            git checkout develop
                            git reset --hard origin/develop
                        '''
                        env.DEPLOY_TARGET = 'staging'
                    } else {
                        echo '🚀 WORKSPACE PRODUCTION DIDETEKSI!'
                        echo 'Menggunakan Jenkins Workspace utama untuk Production...'
                        env.DEPLOY_TARGET = 'production'
                    }
                }
            }
        }

        stage('2. Build Container Layer') {
            steps {
                script {
                    if (env.DEPLOY_TARGET == 'staging') {
                        sh '''
                            cd /srv/apps/finance-staging
                            # Build langsung menggunakan Docker CLI standar server
                            docker build --no-cache -t finance-ai-app:staging .
                        '''
                    } else {
                        sh "docker build --no-cache -t finance-ai-app:production ."
                    }
                }
            }
        }

        stage('3. Deploy & Optimasi Laravel') {
            steps {
                script {
                    if (env.DEPLOY_TARGET == 'staging') {
                        sh '''
                            cd /srv/apps/finance-staging
                            
                            # Hapus container lama jika masih ada/macet
                            docker rm -f finance-staging_app || true
                            
                            # Jalankan container baru menggunakan Docker Run standar (Port 8002)
                            # Menyambungkan volume storage dan shared network yang dibutuhkan
                            docker run -d \
                                --name finance-staging_app \
                                -p 8002:80 \
                                -v /srv/apps/finance-staging/.env:/var/www/html/.env \
                                -v finance-staging_storage_staging:/var/www/html/storage \
                                --network shared \
                                finance-ai-app:staging
                            
                            # Eksekusi optimasi Laravel
                            docker exec -t finance-staging_app php artisan migrate --force
                            docker exec -t finance-staging_app php artisan optimize
                        '''
                        echo '✅ Selesai! Lingkungan Staging (finance-staging_app) berhasil diperbarui.'
                    } else {
                        sh '''
                            docker rm -f asset_prod_app || true
                            docker run -d \
                                --name asset_prod_app \
                                -p 8000:80 \
                                --network shared \
                                finance-ai-app:production
                            docker exec -t asset_prod_app php artisan migrate --force
                            docker exec -t asset_prod_app php artisan optimize
                        '''
                        echo '🚀 Selesai! Lingkungan Production (asset_prod_app) berhasil diperbarui.'
                    }
                }
            }
        }
    }

    post {
        success {
            echo "🎉 Pipeline [${env.DEPLOY_TARGET.toUpperCase()}] berhasil dieksekusi dengan sempurna!"
        }
        failure {
            echo "❌ Pipeline [${env.DEPLOY_TARGET.toUpperCase()}] gagal. Silakan periksa log di atas."
        }
    }
}