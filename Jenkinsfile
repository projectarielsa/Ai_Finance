pipeline {
    agent any

    stages {
        stage('1. Build Container Layer') {
            steps {
                script {
                    echo '📦 Membangun ulang Docker Image di dalam Workspace Jenkins...'
                    sh 'docker-compose build --no-cache'
                }
            }
        }

        stage('2. Deploy & Inject Konfigurasi') {
            steps {
                script {
                    echo '⚡ Mengambil .env dari folder server & Menyalakan Container...'
                    sh '''
                        # 1. KUNCI UTAMA: Hancurkan folder .env palsu sisa build lalu yang dibuat Docker
                        rm -rf .env || true
                        
                        # 2. Salin file .env text asli yang valid dari folder server
                        cp /srv/apps/finance-staging/.env .env
                        
                        # 3. Segarkan state Docker Compose dan bersihkan kontainer yatim (orphans)
                        docker-compose down --remove-orphans || true
                        
                        # 4. Angkat kontainer Production utama
                        docker-compose up -d
                    '''
                }
            }
        }

        stage('3. Optimasi Laravel') {
            steps {
                script {
                    echo '⚙️ Menjalankan Migrasi & Cache Bersih...'
                    sh '''
                        # 1. Hapus paksa file cache bootstrap yang tersangkut di dalam kontainer
                        docker exec -t finance_prod_app rm -rf bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php
                        
                        # 2. Jalankan dump-autoload DENGAN flag --no-scripts agar tidak memicu discover otomatis yang rusak
                        docker exec -t finance_prod_app composer dump-autoload --optimize --no-scripts
                        
                        # 3. Sekarang jalankan discover secara manual dalam kondisi bootstrap yang sudah bersih total
                        docker exec -t finance_prod_app php artisan package:discover --ansi
                        
                        # 4. Jalankan migrasi database dan optimasi cache production
                        docker exec -t finance_prod_app php artisan migrate --force
                        docker exec -t finance_prod_app php artisan optimize
                    '''
                }
            }
        }
    }

    post {
        success {
            echo "🎉 Pipeline [PRODUCTION VIA WORKSPACE] sukses besar!"
        }
        failure {
            echo "❌ Pipeline [PRODUCTION VIA WORKSPACE] gagal."
        }
    }
}