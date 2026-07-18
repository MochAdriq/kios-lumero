<?php
require 'helpers/functions.php';
require 'core/Database.php';
$db = Database::connection();
print_r(outlet_scope_sql('outlet_id', 2));
