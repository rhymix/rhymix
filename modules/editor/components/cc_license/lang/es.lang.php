<?php
    /**
     * @file   /modules/editor/components/cc_license/lang/es.lang.php
     * @author zero <zero@zeroboard.com>
     * @brief  WYSIWYG module >  CCL display component
     **/

    $lang->ccl_default_title = 'Reconocimiento-No comercial-Sin obras derivadas 2.0 España';
    $lang->ccl_default_message = 'Este componente puede ser usado por <a rel="licencia" href="http://creativecommons.org/licenses/by% s% s% s% s" onclick="winopen (this.href); retorno false;" >% S% s% s% s </ a> ';

    $lang->ccl_title = 'Titulo';
    $lang->ccl_use_mark = 'Utilice Mark';
    $lang->ccl_allow_commercial = 'Permitir el uso comercial';
    $lang->ccl_allow_modification = 'Permitir la modificacion de componente';

    $lang->ccl_allow = 'Permitir';
    $lang->ccl_disallow = 'Disallow';
    $lang->ccl_sa = 'Modificar identica condicion';

    $lang->ccl_options = array(
        'ccl_allow_commercial' => array('Y'=>'-Commertial', 'N'=>'-Noncommertial'),
        'ccl_allow_modification' => array('Y'=>'-Inhibit', 'N'=>'-Inhibit', 'SA'=>'-Under Identical Condition'),
    );

    $lang->about_ccl_title = 'Titulo en la pantalla. Default mensaje se muestra cuando no hay nada de entrada.';
    $lang->about_ccl_use_mark = 'Puede mostrar u ocultar la marca. (Por defecto: pantalla)';
    $lang->about_ccl_allow_commercial = 'Usted puede permitir o no permitir el uso comercial. (Por defecto: inhabilitar)';
    $lang->about_ccl_allow_modification = 'Usted puede habilitar o inhabilitar la modificacion de la obra. (Por defecto: permitir)';
?>
