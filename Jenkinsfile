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
                            
                            # 1. Tarik info branch terbaru
                            git fetch origin
                            
                            # 2. PAKSA BUANG semua perubahan lokal atau file untracked yang bikin macet
                            git reset --hard HEAD
                            git clean -fd
                            
                            # 3. Pindah ke develop dan timpa dengan kode terbaru dari GitHub
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
                            # Ganti menjadi docker-compose (pakai strip)
                            docker-compose -f docker-compose.staging.yml build --no-cache app-staging
                        '''
                    } else {
                        # Samakan juga untuk blok production
                        sh "docker-compose -f docker-compose.prod.yml build --no-cache app-prod"
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
                            # Ganti menjadi docker-compose (pakai strip)
                            docker-compose -f docker-compose.staging.yml up -d
                            
                            docker exec -t finance-staging_app php artisan migrate --force
                            docker exec -t finance-staging_app php artisan optimize
                        '''
                        echo '✅ Selesai! Lingkungan Staging (finance-staging_app) berhasil diperbarui.'
                    } else {
                        sh '''
                            docker-compose -f docker-compose.prod.yml up -d
                            docker exec -t asset_prod_app php artisan migrate --force
                            docker exec -t asset_prod_app php artisan optimize
                        '''
                        echo '🚀 Selesai! Lingkungan Production (asset_prod_app) berhasil diperbarui.'
                    }
                }
            }
        }
    }
}