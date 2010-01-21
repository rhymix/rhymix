<?php
    /**
     * @file   modules/editor/lang/fr.lang.php
     * @author zero <zero@nzeo.com> Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet du langage en français pour le module de Tel-tel Editeur
     **/

    $lang->editor = 'Tel-tel Editeur';
    $lang->component_name = 'Composant';
    $lang->component_version = 'Version';
    $lang->component_author = 'Développeur';
    $lang->component_link = 'Lien';
    $lang->component_date = 'Jour de Création';
    $lang->component_license = 'Licence';
    $lang->component_history = 'Histoire';
    $lang->component_description = 'Description';
    $lang->component_extra_vars = 'Variables d\'Option';
    $lang->component_grant = 'Configuration de la Permission';
    $lang->content_style = 'Content Style';
    $lang->content_font = 'Content Font';
	$lang->content_font_size = '문서 폰트 크기';

    $lang->about_component = 'Sur le Composant';
    $lang->about_component_grant = 'Vous pouvez configurer la Permission d\'utiliser des composants additionnels de l\'Editeur.<br /> (Tout le monde aura la Permission si vous ne cochez rien)';
    $lang->about_component_mid = 'Vous pouvez désigner les objectifs auquels les composants s\'appliquent<br />(Tous les objectifs auront la Permission quand rien n\'est choisi.)';

    $lang->msg_component_is_not_founded = 'Ne peut pas trouver Composant %s';
    $lang->msg_component_is_inserted = 'Composant choisi est déjà entré';
    $lang->msg_component_is_first_order = 'Composant choisi est localisé à la première position';
    $lang->msg_component_is_last_order = 'Composant choisi est localisé à la position dernière';
    $lang->msg_load_saved_doc = "Il y a un article conservé automatiquement. Voulez-vous le réstaurer?\nL'esquisse conservé automatiquement va être débarrasser après conserver l'article courant.";
    $lang->msg_auto_saved = 'Conservé automatiquement';

    $lang->cmd_disable = 'Invalider';
    $lang->cmd_enable = 'Valider';

    $lang->editor_skin = 'Habillage de l\'Editeur';
    $lang->upload_file_grant = 'Permission de télécharger(téléverser) ';
    $lang->enable_default_component_grant = 'Permission d\'utiliser les Composants Par Défaut';
    $lang->enable_component_grant = 'Permission d\'utiliser des composants';
    $lang->enable_html_grant = 'Permission d\'utiliser HTML';
    $lang->enable_autosave = 'Valider à conserver automatiquement';
    $lang->height_resizable = 'Permettre de remettre l\'hauteur';
    $lang->editor_height = 'Hauteur de l\'Editeur';

    $lang->about_editor_skin = 'Vous pouvez choisir l\'habillage de l\'Editeur.';
    $lang->about_content_style = '문서 편집 및 내용 출력시 원하는 서식을 지정할 수 있습니다';
    $lang->about_content_font = '문서 편집 및 내용 출력시 원하는 폰트를 지정할 수 있습니다.<br/>지정하지 않으면 사용자 설정에 따르게 됩니다<br/> ,(콤마)로 여러 폰트를 지정할 수 있습니다.';
	$lang->about_content_font_size = '문서 편집 및 내용 출력시 원하는 폰트의 크기를 지정할 수 있습니다.<br/>12px, 1em등 단위까지 포함해서 입력해주세요.';
    $lang->about_upload_file_grant = 'Vous pouvez configurer la permission d\'attacher les fichiers. (Tout le monde aura la permission si vous ne cochez rien)';
    $lang->about_default_component_grant = 'Vous pouvez configurer la permission d\'utiliser les Composants Par Défaut de l\'Editeur. (Tout le monde aura la permission si vous ne cochez rien)';
    $lang->about_editor_height = 'Vous pouvez configurer l\'hauteur de l\'Editeur.';
    $lang->about_editor_height_resizable = 'Permettre de remettre l\'hauteur de l\'Editeur.';
    $lang->about_enable_html_grant = 'Vous pouvez permettre d\'utiliser HTML';
    $lang->about_enable_autosave = 'Vous pouvez valider la fonction à Conserver Automatiquement pendant écrire des articles.';

    $lang->edit->fontname = 'Police de caractères';
    $lang->edit->fontsize = 'Mesure';
    $lang->edit->use_paragraph = 'Fonctions sur Paragraphe';
    $lang->edit->fontlist = array(
    'Arial'=>'Arial',
    'Arial Black'=>'Arial Black',
    'Tahoma'=>'Tahoma',
    'Verdana'=>'Verdana',
    'Sans-serif'=>'Sans-serif',
    'Serif'=>'Serif',
    'Monospace'=>'Monospace',
    'Cursive'=>'Cursive',
    'Fantasy'=>'Fantasy',
    );

    $lang->edit->header = 'Style';
    $lang->edit->header_list = array(
    'h1' => 'Titre 1',
    'h2' => 'Titre 2',
    'h3' => 'Titre 3',
    'h4' => 'Titre 4',
    'h5' => 'Titre 5',
    'h6' => 'Titre 6',
    );

    $lang->edit->submit = 'Soumettre';

    $lang->edit->fontcolor = 'Text Color';
    $lang->edit->fontbgcolor = 'Background Color';
    $lang->edit->bold = 'Bold';
    $lang->edit->italic = 'Italic';
    $lang->edit->underline = 'Underline';
    $lang->edit->strike = 'Strike';
    $lang->edit->sup = 'Sup';
    $lang->edit->sub = 'Sub';
    $lang->edit->redo = 'Re Do';
    $lang->edit->undo = 'Un Do';
    $lang->edit->align_left = 'Align Left';
    $lang->edit->align_center = 'Align Center';
    $lang->edit->align_right = 'Align Right';
    $lang->edit->align_justify = 'Align Justify';
    $lang->edit->add_indent = 'Indent';
    $lang->edit->remove_indent = 'Outdent';
    $lang->edit->list_number = 'Orderd List';
    $lang->edit->list_bullet = 'Unordered List';
    $lang->edit->remove_format = 'Style Remover';

    $lang->edit->help_remove_format = 'Supprimer les balises dans l\'endroit sélectionné';
    $lang->edit->help_strike_through = 'Représenter la ligne d\'annulation sur les lettres.';
    $lang->edit->help_align_full = 'Aligner pleinement selon largeur';

    $lang->edit->help_fontcolor = 'Désigner la couleur de la Police de caractères';
    $lang->edit->help_fontbgcolor = 'Désigner la couleur de l\'arrière-plan de la Police de caractères.';
    $lang->edit->help_bold = 'Caractère gras';
    $lang->edit->help_italic = 'Caractère italique';
    $lang->edit->help_underline = 'Caractère souligné';
    $lang->edit->help_strike = 'Caractère biffé';
    $lang->edit->help_sup = 'Sup';
    $lang->edit->help_sub = 'Sub';
    $lang->edit->help_redo = 'Réfaire';
    $lang->edit->help_undo = 'Annuler';
    $lang->edit->help_align_left = 'Aligner à gauche';
    $lang->edit->help_align_center = 'Aligner centr';
    $lang->edit->help_align_right = 'Aligner  droite';
	$lang->edit->help_align_justify = 'Align justity';
    $lang->edit->help_add_indent = 'Ajouter un Rentré';
    $lang->edit->help_remove_indent = 'Enlever un Rentré';
    $lang->edit->help_list_number = 'Appliquer la liste numroté';
    $lang->edit->help_list_bullet = 'Appliquer la liste à puces';
    $lang->edit->help_use_paragraph = 'Appuyez Ctrl+Enter pour séparer les paragraphe. (Appuyez Alt+S pour conserver)';

    $lang->edit->url = 'URL';
    $lang->edit->blockquote = 'Blockquote';
    $lang->edit->table = 'Table';
    $lang->edit->image = 'Image';
    $lang->edit->multimedia = 'Movie';
    $lang->edit->emoticon = 'Emoticon';

    $lang->edit->upload = 'Attacher';
    $lang->edit->upload_file = 'Attacher un(des) Fichier(s)';
    $lang->edit->link_file = 'Insérer dans le Texte';
    $lang->edit->delete_selected = 'Supprimer le Sélectionné';

    $lang->edit->icon_align_article = 'Occuper un paragraphe';
    $lang->edit->icon_align_left = 'Placer à gauche du texte';
    $lang->edit->icon_align_middle = 'Placer au centre';
    $lang->edit->icon_align_right = 'Placer à droite du texte';

    $lang->about_dblclick_in_editor = 'Vous pouvez configurer en détail des composants par double-clic sur un arrière-plan, un texte, une image ou une citation';


    $lang->edit->rich_editor = '스타일 편집기';
    $lang->edit->html_editor = 'HTML 편집기';
    $lang->edit->extension ='확장 컴포넌트';
    $lang->edit->help = '도움말';
    $lang->edit->help_command = '단축키 안내';
    
    $lang->edit->lineheight = '줄간격';
	$lang->edit->fontbgsampletext = '가나다';
	
	$lang->edit->hyperlink = '하이퍼링크';
	$lang->edit->target_blank = '새창으로';
	
	$lang->edit->quotestyle1 = '왼쪽 실선';
	$lang->edit->quotestyle2 = '인용 부호';
	$lang->edit->quotestyle3 = '실선';
	$lang->edit->quotestyle4 = '실선 + 배경';
	$lang->edit->quotestyle5 = '굵은 실선';
	$lang->edit->quotestyle6 = '점선';
	$lang->edit->quotestyle7 = '점선 + 배경';
	$lang->edit->quotestyle8 = '적용 취소';


    $lang->edit->jumptoedit = '편집 도구모음 건너뛰기';
    $lang->edit->set_sel = '칸 수 지정';
    $lang->edit->row = '행';
    $lang->edit->col = '열';
    $lang->edit->add_one_row = '1행추가';
    $lang->edit->del_one_row = '1행삭제';
    $lang->edit->add_one_col = '1열추가';
    $lang->edit->del_one_col = '1열삭제';

    $lang->edit->table_config = '표 속성 지정';
    $lang->edit->border_width = '테두리 굵기';
    $lang->edit->border_color = '테두리 색';
    $lang->edit->add = '더하기';
    $lang->edit->del = '빼기';
    $lang->edit->search_color = '색상찾기';
    $lang->edit->table_backgroundcolor = '표 배경색';
    $lang->edit->special_character = '특수문자';
    $lang->edit->insert_special_character = '특수문자 삽입';
    $lang->edit->close_special_character = '특수문자 레이어 닫기';
    $lang->edit->symbol = '일반기호';
    $lang->edit->number_unit = '숫자와 단위';
    $lang->edit->circle_bracket = '원,괄호';
    $lang->edit->korean = '한글';
    $lang->edit->greece = '그리스';
    $lang->edit->Latin  = '라틴어';
    $lang->edit->japan  = '일본어';
    $lang->edit->selected_symbol  = '선택한 기호';

    $lang->edit->search_replace  = '찾기/바꾸기';
    $lang->edit->close_search_replace  = '찾기/바꾸기 레이어 닫기';
    $lang->edit->replace_all  = '모두바꾸기';
    $lang->edit->search_words  = '찾을단어';
    $lang->edit->replace_words  = '바꿀단어';
    $lang->edit->next_search_words  = '다음찾기';
    $lang->edit->edit_height_control  = '입력창 크기 조절';

	$lang->edit->merge_cells = '셀 병합';
    $lang->edit->split_row = '행 분할';
    $lang->edit->split_col = '열 분할';
    
    $lang->edit->toggle_list   = '목록 접기/펼치기';
    $lang->edit->minimize_list = '최소화';
    
    $lang->edit->move = '이동';
	$lang->edit->refresh = 'Refresh';
    $lang->edit->materials = '글감보관함';
    $lang->edit->temporary_savings = '임시저장목록';

	$lang->edit->paging_prev = '이전';
	$lang->edit->paging_next = '다음';
	$lang->edit->paging_prev_help = '이전 페이지로 이동합니다.';
	$lang->edit->paging_next_help = '다음 페이지로 이동합니다.';

	$lang->edit->toc = '목차';
	$lang->edit->close_help = '도움말 닫기';

	$lang->edit->confirm_submit_without_saving = '저장하지 않은 단락이 있습니다.\\n그냥 전송하시겠습니까?';

	$lang->edit->image_align = '이미지 정렬';
	$lang->edit->attached_files = '첨부 파일';
?>