Write-Host "==> Deploying plugin files to Moodle container..." -ForegroundColor Green
docker cp ./local_securecoursehub/. secure-course-hub-lab-moodle-1:/opt/bitnami/moodle/local/securecoursehub

Write-Host "==> Running Moodle database upgrade..." -ForegroundColor Green
docker exec -u root secure-course-hub-lab-moodle-1 php /opt/bitnami/moodle/admin/cli/upgrade.php --non-interactive

Write-Host "==> Purging Moodle caches..." -ForegroundColor Green
docker exec -u root secure-course-hub-lab-moodle-1 php /opt/bitnami/moodle/admin/cli/purge_caches.php

Write-Host "==> Fixing container file permissions..." -ForegroundColor Yellow
docker exec -u root secure-course-hub-lab-moodle-1 bash -c "chmod -R 777 /opt/bitnami/moodle /bitnami"

Write-Host "++ Deployment & Upgrade Complete ++" -ForegroundColor Cyan
Write-Host "Access your site at: http://127.0.0.1:8080" -ForegroundColor Yellow