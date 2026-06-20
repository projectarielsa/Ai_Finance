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
                            # Menggunakan docker-compose (pakai strip) tanpa flag aneh-aneh yang bikin eror
                            docker-compose build --no-cache
                        '''
                    } else {
                        sh '''
                            cd /srv/apps/finance-staging
                            docker-compose -f docker-compose.prod.yml build --no-cache
                        '''
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
                            
                            # KUNCINYA DI SINI: Bersihkan container lama dulu agar docker-compose jadulnya tidak crash
                            docker-compose down || true
                            
                            # Jalankan container baru dari kondisi bersih
                            docker-compose up -d
                            
                            # Jalankan perintah Laravel
                            docker exec -t finance-staging_app php artisan migrate --force
                            docker exec -t finance-staging_app php artisan optimize
                        '''
                        echo '✅ Selesai! Lingkungan Staging (finance-staging_app) berhasil diperbarui.'
                    } else {
                        sh '''
                            cd /srv/apps/finance-staging
                            
                            # Bersihkan container production lama terlebih dahulu
                            docker-compose -f docker-compose.prod.yml down || true
                            
                            # Jalankan container baru production
                            docker-compose -f docker-compose.prod.yml up -d
                            
                            # Jalankan perintah Laravel di production
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