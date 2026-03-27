<?php
chdir('staff/auth');
$_POST = [
    'action' => 'generate',
    'num_questions' => 2,
    'difficulty' => 'easy',
    'topic' => 'photosynthesis'
];
session_start();
$_SESSION['role'] = 'staff';
$_SESSION['user_id'] = 1;
$_SESSION['first_name'] = 'Test';
$_SESSION['surname'] = 'User';

require 'ai_generator_api.php';
