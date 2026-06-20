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
                            
                            # Jalankan container
                            docker-compose down || true
                            docker-compose up -d
                            
                            # KUNCINYA: Hapus file fisik cache bootstraper Laravel yang nyangkut
                            docker exec -t finance-staging_app rm -f bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php
                            
                            # Jalankan perintah Laravel (Sekarang dijamin lancar)
                            docker exec -t finance-staging_app php artisan migrate --force
                            docker exec -t finance-staging_app php artisan optimize
                        '''
                        echo '✅ Selesai! Lingkungan Staging (finance-staging_app) berhasil diperbarui.'
                    } else {
                        sh '''
                            cd /srv/apps/finance-staging
                            docker-compose -f docker-compose.prod.yml down || true
                            docker-compose -f docker-compose.prod.yml up -d
                            
                            # Hapus file fisik cache untuk Production juga
                            docker exec -t asset_prod_app rm -f bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php
                            
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