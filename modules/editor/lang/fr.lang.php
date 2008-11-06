<?php
    /**
     * @file   modules/editor/lang/fr.lang.php
     * @author zero <zero@nzeo.com> Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet du langage en français pour le module de Tel-tel Editeur
     **/

    $lang->editor = "Tel-tel Editeur";
    $lang->component_name = "Composant";
    $lang->component_version = "Version";
    $lang->component_author = "Développeur";
    $lang->component_link = "Lien";
    $lang->component_date = "Jour de Création";
    $lang->component_license = 'Licence';
    $lang->component_history = "Histoire";
    $lang->component_description = "Description";
    $lang->component_extra_vars = "Variables d'Option";
    $lang->component_grant = "Configuration de la Permission"; 

    $lang->about_component = "Sur le Composant";
    $lang->about_component_grant = 'Vous pouvez configurer la Permission d\'utiliser des composants additionnels de l\'Editeur.<br /> (Tout le monde aura la Permission si vous ne cochez rien)';
    $lang->about_component_mid = "Vous pouvez désigner les objectifs auquels les composants s'appliquent<br />(Tous les objectifs auront la Permission quand rien n'est choisi.)";

    $lang->msg_component_is_not_founded = 'Ne peut pas trouver Composant %s';
    $lang->msg_component_is_inserted = 'Composant choisi est déjà entré';
    $lang->msg_component_is_first_order = 'Composant choisi est localisé à la première position';
    $lang->msg_component_is_last_order = 'Composant choisi est localisé à la position dernière';
    $lang->msg_load_saved_doc = "Il y a un article conservé automatiquement. Voulez-vous le réstaurer?\nL\'esquisse conservé automatiquement va être débarrasser après conserver l\'article courant.";
    $lang->msg_auto_saved = "Conservé automatiquement";

    $lang->cmd_disable = "Invalider";
    $lang->cmd_enable = "Valider";

    $lang->editor_skin = 'Habillage de l\'Editeur';
    $lang->upload_file_grant = 'Permission de télécharger(téléverser) '; 
    $lang->enable_default_component_grant = 'Permission d\'utiliser les Composants Par Défaut';
    $lang->enable_component_grant = 'Permission d\'utiliser des composants';
    $lang->enable_html_grant = 'Permission d\'utiliser HTML';
    $lang->enable_autosave = 'Valider à conserver automatiquement';
    $lang->height_resizable = 'Permettre de remettre l\'hauteur';
    $lang->editor_height = 'Hauteur de l\'Editeur';

    $lang->about_editor_skin = 'Vous pouvez choisir l\'habillage de l\'Editeur.';
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
    "Arial",
    "'Arial Black'",
    "Tahoma",
    "Verdana",
	"Sans-serif",
	"Serif",
	"Monospace",
	"Cursive",
	"Fantasy",
    );

    $lang->edit->header = "Style";
    $lang->edit->header_list = array(
    "h1" => "Titre 1",
    "h2" => "Titre 2",
    "h3" => "Titre 3",
    "h4" => "Titre 4",
    "h5" => "Titre 5",
    "h6" => "Titre 6",
    );

    $lang->edit->submit = 'Soumettre';

    $lang->edit->help_remove_format = "Supprimer les balises dans l\'endroit sélectionné";
    $lang->edit->help_strike_through = "Représenter la ligne d\'annulation sur les lettres.";
    $lang->edit->help_align_full = "Aligner pleinement selon largeur";

   $lang->edit->help_fontcolor = "Désigner la couleur de la Police de caractères";
    $lang->edit->help_fontbgcolor = "Désigner la couleur de l\'arrière-plan de la Police de caractères.";
    $lang->edit->help_bold = "Caractère gras";
    $lang->edit->help_italic = "Caractère italique";
    $lang->edit->help_underline = "Caractère souligné";
    $lang->edit->help_strike = "Caractère biffé";
    $lang->edit->help_redo = "Réfaire";
    $lang->edit->help_undo = "Annuler";
    $lang->edit->help_align_left = "Aligner à gauche";
    $lang->edit->help_align_center = "Aligner centr";
    $lang->edit->help_align_right = "Aligner  droite";
    $lang->edit->help_add_indent = "Ajouter un Rentré";
    $lang->edit->help_remove_indent = "Enlever un Rentré";
    $lang->edit->help_list_number = "Appliquer la liste numroté";
    $lang->edit->help_list_bullet = "Appliquer la liste à puces";
    $lang->edit->help_use_paragrapth = "Appuyez Ctrl+Enter pour séparer les paragraphe. (Appuyez Alt+S pour conserver)";

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
?>
