# scaffold.ps1 - Generates the complete Secure Course Hub plugin and deployment stack

Write-Host "Creating directory structure..." -ForegroundColor Cyan

# Create Directory Hierarchy
$dirs = @(
    "local_securecoursehub",
    "local_securecoursehub/db",
    "local_securecoursehub/lang/en",
    "local_securecoursehub/classes/local",
    "local_securecoursehub/amd/src"
)

foreach ($dir in $dirs) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
}

Write-Host "Generating files..." -ForegroundColor Cyan

# 1. docker-compose.yml
@'
services:
  mariadb:
    image: bitnamilegacy/mariadb:11.4
    environment:
      - ALLOW_EMPTY_PASSWORD=yes
      - MARIADB_USER=bn_moodle
      - MARIADB_PASSWORD=bitnami
      - MARIADB_DATABASE=bitnami_moodle
    volumes:
      - 'mariadb_data:/bitnami/mariadb'

  moodle:
    image: bitnamilegacy/moodle:4.5
    ports:
      - '8080:8080'
      - '8443:8443'
    environment:
      - MARIADB_HOST=mariadb
      - MARIADB_PORT_NUMBER=3306
      - MOODLE_DATABASE_USER=bn_moodle
      - MOODLE_DATABASE_PASSWORD=bitnami
      - MOODLE_DATABASE_NAME=bitnami_moodle
      - ALLOW_EMPTY_PASSWORD=yes
    volumes:
      - 'moodle_data:/bitnami/moodle'
      - './local_securecoursehub:/plugin-src'
    depends_on:
      - mariadb

volumes:
  mariadb_data:
    driver: local
  moodle_data:
    driver: local
'@ | Out-File -FilePath "docker-compose.yml" -Encoding utf8

# 2. setup.ps1
@'
Write-Host "==> Deploying plugin files to Moodle container..." -ForegroundColor Green
docker cp ./local_securecoursehub secure-course-hub-lab-moodle-1:/opt/bitnami/moodle/local/securecoursehub

Write-Host "==> Running Moodle database upgrade..." -ForegroundColor Green
docker exec secure-course-hub-lab-moodle-1 php /opt/bitnami/moodle/admin/cli/upgrade.php --non-interactive

Write-Host "++ Deployment Complete ++" -ForegroundColor Cyan
Write-Host "Access your site at: http://127.0.0.1:8080" -ForegroundColor Yellow
'@ | Out-File -FilePath "setup.ps1" -Encoding utf8

# 3. local_securecoursehub/version.php
@'
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_securecoursehub';
$plugin->version   = 2026071300;
$plugin->requires  = 2024100700;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';
'@ | Out-File -FilePath "local_securecoursehub/version.php" -Encoding utf8

# 4. local_securecoursehub/lang/en/local_securecoursehub.php
@'
<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Secure Course Hub';
$string['securecoursehub:viewown'] = 'View own help requests';
$string['securecoursehub:createrequest'] = 'Create new help request';
$string['securecoursehub:managecourserequests'] = 'Manage course help requests';
'@ | Out-File -FilePath "local_securecoursehub/lang/en/local_securecoursehub.php" -Encoding utf8

# 5. local_securecoursehub/db/access.php
@'
<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/securecoursehub:viewown' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ],
    'local/securecoursehub:createrequest' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ],
    'local/securecoursehub:managecourserequests' => [
        'captype' => 'write',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ]
];
'@ | Out-File -FilePath "local_securecoursehub/db/access.php" -Encoding utf8

# 6. local_securecoursehub/db/install.xml
@'
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="local/securecoursehub/db" VERSION="20260713" COMMENT="XMLDB file for Secure Course Hub">
  <TABLES>
    <TABLE NAME="local_securecoursehub" COMMENT="Stores course help requests">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="courseid" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="userid" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="title" TYPE="char" LENGTH="255" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="description" TYPE="text" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="status" TYPE="char" LENGTH="20" NOTNULL="true" DEFAULT="open" SEQUENCE="false"/>
        <FIELD NAME="response" TYPE="text" NOTNULL="false" SEQUENCE="false"/>
        <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="timemodified" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
      </KEYS>
      <INDEXES>
        <INDEX NAME="courseid_idx" UNIQUE="false" FIELDS="courseid"/>
        <INDEX NAME="userid_idx" UNIQUE="false" FIELDS="userid"/>
      </INDEXES>
    </TABLE>
  </TABLES>
</XMLDB>
'@ | Out-File -FilePath "local_securecoursehub/db/install.xml" -Encoding utf8

# 7. local_securecoursehub/index.php
@'
<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);

require_login($course);
$context = context_course::instance($courseid);
require_capability('local/securecoursehub:viewown', $context);

$PAGE->set_url(new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_securecoursehub'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_securecoursehub'));

echo html_writer::tag('p', 'Welcome to Secure Course Hub, ' . s(fullname($USER)));

echo $OUTPUT->footer();
'@ | Out-File -FilePath "local_securecoursehub/index.php" -Encoding utf8

# 8. README.md
@'
# Secure Course Hub Plugin (CSI 3140 - Lab 5)

## Overview
Secure Course Hub is a local Moodle plugin enabling student help requests with role-based access control, CSRF protections, and JSON communication.

## Local Environment
- Platform: Moodle 4.5
- Database: MariaDB 11.4
- Server: Apache + PHP 8.1+
'@ | Out-File -FilePath "README.md" -Encoding utf8

Write-Host "++ Generation Complete! All files created. ++" -ForegroundColor Green