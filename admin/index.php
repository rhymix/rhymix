<?php
header('Location: ' . preg_replace('/admin\/|admin/', 'index.php?module=admin', $_SERVER['REQUEST_URI']));

/* End of file index.php */
/* Location: ./admin/index.php */
