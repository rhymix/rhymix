<?php
header('Location: ' . preg_replace('/admin\/|admin/', 'index.php?module=admin', $_SERVER['REQUEST_URI']));
