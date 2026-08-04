<?php
$hash = '$2y$10$0QW8RxD.7pnfKDewkyVWe.3B7vWfCgsOcKe/gpC51TIvACe1wKIIm';
$passwords = ['123456', 'admin', 'admin123', 'password', 'lumero', 'admin-klb', 'kalibunder', 'adminklb', '12345', '12345678', 'qwerty'];
foreach ($passwords as $p) {
    if (password_verify($p, $hash)) {
        echo "Password is: $p\n";
        exit;
    }
}
echo "Password not found in common list.\n";
