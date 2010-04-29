<?php
  /**
     * @file   ru.lang.php
     * @author zero <zero@nzeo.com> | translation by Maslennikov Evgeny aka X-[Vr]bL1s5 | e-mail: x-bliss[a]tut.by; ICQ: 225035467;
     * @brief  Russian basic language pack
     **/

    $lang->editor = 'WYSIWYG-Редактор';
    $lang->component_name = 'Компонент';
    $lang->component_version = 'Версия';
    $lang->component_author = 'Разработчик';
    $lang->component_link = 'Ссылка';
    $lang->component_date = 'Дата';
    $lang->component_license = 'License';
    $lang->component_history = 'History';
    $lang->component_description = 'Описание';
    $lang->component_extra_vars = 'Экстра перем.';
    $lang->component_grant = 'Настройки прав доступа';
    $lang->content_style = 'Content Style';
    $lang->content_font = 'Content Font';
	$lang->content_font_size = 'Размер шрифта';

  	$lang->about_component = 'О компоненте';
    $lang->about_component_grant = 'Возможен выбор дополнительных компонентов.<br /> (Каждый может использовать его, если режим выключен)';
    $lang->about_component_mid = 'Можно установить пользователя Редактора.<br />(Каждый может использовать его, если режим выключен)';

    $lang->msg_component_is_not_founded = 'Невозможно найти компонент редактора %s';
    $lang->msg_component_is_inserted = 'Выбранный компонент уже присутствует';
    $lang->msg_component_is_first_order = 'Выбранный компонент находится на первой позиции';
    $lang->msg_component_is_last_order = 'Выбранный компонент находится на последней позиции';
    $lang->msg_load_saved_doc = "Существует автоматически сохраненная статья. Хотите ли Вы ее восстановить?\nАвтоматически сохраненный черновик будет отменен после сохранения текущей статьи";
    $lang->msg_auto_saved = 'Автоматически сохранено';

    $lang->cmd_disable = 'Неактивно';
    $lang->cmd_enable = 'Активно';

    $lang->editor_skin = 'Скин Редактора';
    $lang->upload_file_grant = 'Разрешить прикреплять файлы';
    $lang->enable_default_component_grant = 'Разрешить использование основного компонента';
    $lang->enable_component_grant = 'Разрешить ипользование компонента';
    $lang->enable_html_grant = 'Разрешить коррекцию HTML';
    $lang->enable_autosave = 'Автосохранение';
    $lang->height_resizable = 'Возможна коррекция высоты';
    $lang->editor_height = 'Высота Редактора';

    $lang->about_editor_skin = 'Выберите скин редактора';
    $lang->about_content_style = 'Вы можете выбрать стиль для редактирования и просмотра записи ';
    $lang->about_content_font = 'Вы можете выбрать шрифт для редактирования и просмотра записи.<br/> Используйте запятую(,) если выбрали несколько шрифтов.';
	$lang->about_content_font_size = 'Вы можете выбрать размер шрифта для редактирования и просмотра записи.<br/> Пожалуйста, выберите единицы такие как px или em.';
    $lang->about_upload_file_grant = 'Вы можете разрешить прикреплять файлы выбранным группам. (Если оставить поле пустым то право прикреплять файлы будут иметь все)';
    $lang->about_default_component_grant = 'Selected group(s) will be able to use default components of editor. (Leave them blank if you want all groups to have permission)';
    $lang->about_editor_height = 'You may set the height of editor.';
    $lang->about_editor_height_resizable = 'You may decide whether height of editor can be resized.';
    $lang->about_enable_html_grant = 'Selected group(s) will be able to use HTML';
    $lang->about_enable_autosave = 'You may decide whether auto-save function will be used.';

    $lang->edit->fontname = 'Шрифт';
    $lang->edit->fontsize = 'Размер';
    $lang->edit->use_paragraph = 'Функции параграфа';
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

    
	
	
	$lang->edit->header = 'Стиль';
    $lang->edit->header_list = array(
    'h1' => 'Заголовок 1',
    'h2' => 'Заголовок 2',
    'h3' => 'Заголовок 3',
    'h4' => 'Заголовок 4',
    'h5' => 'Заголовок 5',
    'h6' => 'Заголовок 6',
    );

    $lang->edit->submit = 'Принять';

    $lang->edit->fontcolor = 'Цвет текста';
    $lang->edit->fontbgcolor = 'Цвет Фона';
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
    $lang->edit->remove_format = 'Удалить стиль';

    $lang->edit->help_remove_format = 'Выделенный объект будет удален';
	$lang->edit->help_strike_through = 'Strike will be on the words';
	$lang->edit->help_align_full = 'Выровнять (вправо/влево)';
	
	$lang->edit->help_fontcolor = 'Выбрать цвет шрифта';
    $lang->edit->help_fontbgcolor = 'Выбрать цвет фона шрифта';
    $lang->edit->help_bold = 'Сделать шрифт жирным';
    $lang->edit->help_italic = 'Сделать шрифт наклонным';
    $lang->edit->help_underline = 'Сделать шрифт подчеркнутым';
    $lang->edit->help_strike = 'Сделать шрифт зачеркнутым';
    $lang->edit->help_sup = 'Sup';
    $lang->edit->help_sub = 'Sub';
    $lang->edit->help_redo = 'Восстановить отмененное';
    $lang->edit->help_undo = 'Отмена';
    $lang->edit->help_align_left = 'Выровнять по левому краю';
    $lang->edit->help_align_center = 'Выровнять по центру';
    $lang->edit->help_align_right = 'Выровнять по правому краю';
	$lang->edit->help_align_justify = 'Align justity';
    $lang->edit->help_add_indent = 'Добавить отступ';
    $lang->edit->help_remove_indent = 'Удалить отступ';
    $lang->edit->help_list_number = 'Применить числовой список';
    $lang->edit->help_list_bullet = 'Применить маркированный список';
    $lang->edit->help_use_paragraph = 'Нажмите Ctrl+Enter, чтобы отметить параграф. (Нажмите Alt+S , чтобы сохранить)';

    $lang->edit->url = 'URL';
    $lang->edit->blockquote = 'Blockquote';
    $lang->edit->table = 'Table';
    $lang->edit->image = 'Image';
    $lang->edit->multimedia = 'Movie';
    $lang->edit->emoticon = 'Emoticon';

    $lang->edit->upload = 'Вложение';
    $lang->edit->upload_file = 'Прикрепить файл';
    $lang->edit->link_file = 'Вставить в содержание';
    $lang->edit->delete_selected = 'Удалить выбранное';

    $lang->edit->icon_align_article = 'Занять весь параграф';
    $lang->edit->icon_align_left = 'Выровнять по левому краю';
    $lang->edit->icon_align_middle = 'Выровнять по центру';
    $lang->edit->icon_align_right = 'Выровнять по правому краю';

    $lang->about_dblclick_in_editor = 'Вы можете установить детальную конфигурацию компонента двойным щелчком по фону, тексту, рисункам или цитатам';

    $lang->edit->rich_editor = 'редактировать стиль';
    $lang->edit->html_editor = 'редактировать HTML';
    $lang->edit->extension ='Расширенный компонент';
    $lang->edit->help = 'Помощь';
    $lang->edit->help_command = 'Горячие клавиши';
    
    $lang->edit->lineheight = 'Высота строки';
	$lang->edit->fontbgsampletext = 'АВС';
	
	$lang->edit->hyperlink = 'гиперссылка';
	$lang->edit->target_blank = 'Новое окно';
	
	$lang->edit->quotestyle1 = 'Left Solid';
	$lang->edit->quotestyle2 = 'Quote';
	$lang->edit->quotestyle3 = 'Solid';
	$lang->edit->quotestyle4 = 'Solid + Background';
	$lang->edit->quotestyle5 = 'Bold Solid';
	$lang->edit->quotestyle6 = 'Dotted';
	$lang->edit->quotestyle7 = 'Dotted + Background';
	$lang->edit->quotestyle8 = 'Отменить';

	
    $lang->edit->jumptoedit = 'Пропустить Инструменты редактирования';
    $lang->edit->set_sel = 'Установить количество ячеек';
    $lang->edit->row = 'Строка';
    $lang->edit->col = 'Колонка';
    $lang->edit->add_one_row = 'Добавить 1 строку';
    $lang->edit->del_one_row = 'Удалить 1 строку';
    $lang->edit->add_one_col = 'Добавить 1 колонку';
    $lang->edit->del_one_col = 'Удалить 1 колонку';

    $lang->edit->table_config = 'Конфигурация таблицы';
    $lang->edit->border_width = 'Ширина рамки';
    $lang->edit->border_color = 'Цвет рамки';
    $lang->edit->add = 'Добавить';
    $lang->edit->del = 'Удалить';
    $lang->edit->search_color = 'Подобрать цвет';
    $lang->edit->table_backgroundcolor = 'Цвет фона таблицы';
    $lang->edit->special_character = 'Специальные символы';
    $lang->edit->insert_special_character = 'Добавить специальные символы';
    $lang->edit->close_special_character = 'Закрыть слой специальных символов';
    $lang->edit->symbol = 'Символы';
    $lang->edit->number_unit = 'Числа и единицы измерения';
    $lang->edit->circle_bracket = 'Круг, скобки';
    $lang->edit->korean = 'Корейский';
    $lang->edit->greece = 'Греческий';
    $lang->edit->Latin  = 'Латинский';
    $lang->edit->japan  = 'Японский';
    $lang->edit->selected_symbol  = 'Выбранные символы';
	
	$lang->edit->search_replace  = 'Найти/Переместить';
    $lang->edit->close_search_replace  = 'Закрыть слой "Найти/Переместить"';
    $lang->edit->replace_all  = 'Переместить все';
    $lang->edit->search_words  = 'Искомое слово';
    $lang->edit->replace_words  = 'Слово для замены';
    $lang->edit->next_search_words  = 'Искать дальше';
    $lang->edit->edit_height_control  = 'Изменить размеры окна ввода';

    $lang->edit->merge_cells = 'Объединить ячейки';
    $lang->edit->split_row = 'Разбить строки';
    $lang->edit->split_col = 'Разбить колонки';
    
    $lang->edit->toggle_list   = 'Свернуть/Развернуть список';
    $lang->edit->minimize_list = 'Уменьшить';
    
    $lang->edit->move = 'Перейти';
	$lang->edit->refresh = 'Обновить';
    $lang->edit->materials = 'Материалы';
    $lang->edit->temporary_savings = 'Список временных сохранений';

	$lang->edit->paging_prev = 'Предыдущий';
	$lang->edit->paging_next = 'Следующий';
	$lang->edit->paging_prev_help = 'Перейти к предыдущей странице';
	$lang->edit->paging_next_help = 'Перейти кследующей странице';

	$lang->edit->toc = 'Оглавление';
	$lang->edit->close_help = 'Закрыть помощь';
	
	$lang->edit->confirm_submit_without_saving = 'Есть несохраненные параграфы\\nПродолжить?';
	
  	$lang->edit->image_align = 'Выровнять изображения';
	$lang->edit->attached_files = 'Прикрепленный файл';
?>