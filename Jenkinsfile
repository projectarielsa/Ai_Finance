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
                        # Ambil file .env dari folder lama kamu ke workspace saat ini
                        cp /srv/apps/finance-staging/.env .env
                        
                        # Matikan container lama & nyalakan yang baru
                        docker-compose down || true
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
                        # Bersihkan sisa cache di dalam container jika ada
                        docker exec -t finance_prod_app rm -f bootstrap/cache/services.php bootstrap/cache/packages.php bootstrap/cache/config.php || true
                        
                        # Regenerasi autoloader dan optimasi
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