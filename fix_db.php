<?php
$conn=new PDO('mysql:host=localhost;dbname=edu_app','blaqdev','codingscience');
$conn->exec("UPDATE users SET profile_photo = REPLACE(profile_photo, 'uploads/profiles/', '') WHERE profile_photo LIKE 'uploads/profiles/%'");
echo "Fixed DB";
