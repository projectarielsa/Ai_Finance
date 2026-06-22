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
                        # Bersihkan sisa manifest cache di dalam kontainer jika ada
                        docker exec -t finance_prod_app rm -f bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php || true
                        
                        # Regenerasi autoloader paket vendor dan cache production Laravel
                        docker exec -t finance_prod_app composer dump-autoload --optimize
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