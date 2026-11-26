<?php
// Copy this file to config.php and fill in the hosting database details.
// Keep config.php private; never commit real credentials to GitHub.
return [
  'driver' => 'mysql', // mysql for hosting; sqlite for a local PHP installation
  'host' => 'localhost',
  'port' => 3306,
  'database' => 'YOUR_DATABASE_NAME',
  'username' => 'YOUR_DATABASE_USER',
  'password' => 'YOUR_DATABASE_PASSWORD',
  'charset' => 'utf8mb4',
  'api_token' => 'REPLACE_WITH_A_LONG_RANDOM_SECRET',
  'sqlite_path' => __DIR__ . '/storage/project.sqlite',
  'allowed_origin' => 'https://aftabramsar.ir'
];
