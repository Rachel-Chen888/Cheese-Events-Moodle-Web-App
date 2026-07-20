Write-Host "==> Deploying plugin files to Moodle container..." -ForegroundColor Green
docker cp ./local_securecoursehub secure-course-hub-lab-moodle-1:/opt/bitnami/moodle/local/securecoursehub

Write-Host "==> Running Moodle database upgrade..." -ForegroundColor Green
docker exec secure-course-hub-lab-moodle-1 php /opt/bitnami/moodle/admin/cli/upgrade.php --non-interactive

Write-Host "++ Deployment Complete ++" -ForegroundColor Cyan
Write-Host "Access your site at: http://127.0.0.1:8080" -ForegroundColor Yellow
