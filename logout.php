<?php
require_once __DIR__ . '/includes/functions.php';
logout_user();
flash('info', 'You have been logged out. See you soon!');
redirect('/login.php');
