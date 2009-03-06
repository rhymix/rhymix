/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2008 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This is the sample plugin definition file.
 */





// Register the related commands.
FCKCommands.RegisterCommand( 'XeComponent', new FCKDialogCommand('XE Component', 'XE Component', FCKConfig.PluginsPath + 'xecomponent/xecomponent.html'	, 340, 170 ) ) ;

// Create the "Find" toolbar button.
var oFindItem		= new FCKToolbarButton( 'XeComponent', 'XE Component' ) ;
oFindItem.IconPath	= FCKConfig.PluginsPath + 'xecomponent/xecomponent.gif' ;

FCKToolbarItems.RegisterItem( 'XeComponent', oFindItem ) ;			// 'My_Find' is the name used in the Toolbar config.


/*

var EquationEditorCommand=function(){};

EquationEditorCommand.prototype.Execute=function(){}

EquationEditorCommand.GetState=function() {
        return FCK_TRISTATE_OFF;
}

EquationEditorCommand.Execute=function() {
//alert(window.event);
}

// Register the related commands.
FCKCommands.RegisterCommand( 'My_Find' , EquationEditorCommand) ;

// Create the "Find" toolbar button.
var oFindItem       = new FCKToolbarButton( 'My_Find', FCKLang['DlgMyFindTitle'] ) ;
oFindItem.IconPath  = FCKConfig.PluginsPath + 'findreplace/find.gif' ;
FCKToolbarItems.RegisterItem( 'My_Find', oFindItem ) ;          // 'My_Find' is the name used in the Toolbar config.
*/