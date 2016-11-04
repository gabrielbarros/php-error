<?php
echo password_hash('123456', PASSWORD_BCRYPT, array(
    'cost' => 8,
    'salt' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
));
