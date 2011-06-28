<?php
    $oContext = &Context::getInstance();
    
    $db_info->db_type = 'mssql';
    $db_info->db_table_prefix = 'xe';
    
    $oContext->setDbInfo($db_info);
?>