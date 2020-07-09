/**
 * iOS enter key fix for IME
 *
 * https://github.com/rhymix/rhymix/issues/932
 */
CKEDITOR.plugins.add( 'ios_enterkey', {
  icons: 'ios_enterkey',
  init: function(editor) {
    editor.setKeystroke( [
      [ 13, '' ],
      [ CKEDITOR.SHIFT + 13, '' ]
    ] );

    editor.on('contentDom', function() {
      var selection;
      var bookmarks;
      var range;
      var data;
      var shift = false;

      var editable = editor.editable();
      editable.attachListener(editable, 'keyup', function(e) {

        if(e.data.getKey() === 13) {
          var eventData = {
            dataValue: data
          };
          editor.fire( 'afterSetData', eventData );

          range.moveToBookmark(bookmarks[0]);
          range.collapse( true );
          range.select();

          if(shift) {
            shiftEnter(editor);
          } else {
            enter(editor);
          }

          shift = false;
        }
      });

      editable.attachListener(editable, 'keydown', function(e) {
        if(e.data.getKey() === 13) {
          selection = editor.getSelection();
          bookmarks = selection.createBookmarks(true);
          data = editor.getData();
          range = selection.getRanges()[0];

          if(e.data.$.shiftKey) shift = true;
        }
      });
    });

    plugin = CKEDITOR.plugins.enterkey;
    enterBr = plugin.enterBr;
    enterBlock = plugin.enterBlock;
    headerTagRegex = /^h[1-6]$/;

    function shiftEnter( editor ) {
      // On SHIFT+ENTER:
      // 1. We want to enforce the mode to be respected, instead
      // of cloning the current block. (https://dev.ckeditor.com/ticket/77)
      return enter( editor, editor.activeShiftEnterMode, 1 );
    }

    function enter( editor, mode, forceMode ) {
      forceMode = editor.config.forceEnterMode || forceMode;

      // Only effective within document.
      if ( editor.mode != 'wysiwyg' )
        return;

      if ( !mode )
        mode = editor.activeEnterMode;

      // TODO this should be handled by setting editor.activeEnterMode on selection change.
      // Check path block specialities:
      // 1. Cannot be a un-splittable element, e.g. table caption;
      var path = editor.elementPath();

      if ( path && !path.isContextFor( 'p' ) ) {
        mode = CKEDITOR.ENTER_BR;
        forceMode = 1;
      }

      editor.fire( 'saveSnapshot' ); // Save undo step.

      if ( mode == CKEDITOR.ENTER_BR )
        enterBr( editor, mode, null, forceMode );
      else
        enterBlock( editor, mode, null, forceMode );

      editor.fire( 'saveSnapshot' );
    }

    function getRange( editor ) {
      // Get the selection ranges.
      var ranges = editor.getSelection().getRanges( true );

      // Delete the contents of all ranges except the first one.
      for ( var i = ranges.length - 1; i > 0; i-- ) {
        ranges[ i ].deleteContents();
      }

      // Return the first range.
      return ranges[ 0 ];
    }

    function replaceRangeWithClosestEditableRoot( range ) {
      var closestEditable = range.startContainer.getAscendant( function( node ) {
        return node.type == CKEDITOR.NODE_ELEMENT && node.getAttribute( 'contenteditable' ) == 'true';
      }, true );

      if ( range.root.equals( closestEditable ) ) {
        return range;
      } else {
        var newRange = new CKEDITOR.dom.range( closestEditable );

        newRange.moveToRange( range );
        return newRange;
      }
    }
  }
});
