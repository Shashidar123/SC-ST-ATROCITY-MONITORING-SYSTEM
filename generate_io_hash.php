<?php
// Run this script to generate a bcrypt hash for the IO password
$password = 'io@123';
echo 'Password: ' . $password . "<br>";
echo 'Hash: ' . password_hash($password, PASSWORD_BCRYPT) . "<br>"; 