dp.sh.Brushes.Abap = function()
{
 var datatypes =
 'ACCP CHAR CLNT CUKY CURR DATS DEC FLTP INT1 INT2 INT4 LANG LCHR LRAW NUMC PREC QUAN RAW RAWSTRING SSTRING STRING TIMS UNIT';

 var keywords =
 'IF RETURN WHILE CASE DEFAULT DO ELSE FOR ENDIF ELSEIF EQ NOT AND DATA TYPES SELETION-SCREEN PARAMETERS ' +
 'FIELD-SYMBOLS EXTERN INLINE REPORT WRITE APPEND SELECT ENDSELECT CALL METHOD CALL FUNCTION LOOP ENDLOOP ' +
 'RAISE READ TABLE CONCATENATE SPLIT SHIFT CONDENSE DESCRIBE CLEAR ENDFUNCTION ASSIGN CREATE DATA TRANSLATE ' +
 'CONTINUE START-OF-SELECTION AT SELECTION-SCREEN MODIFY CALL SCREEN CREATE OBJECT PERFORM FORM ENDFORM ' +
 'REUSE_ALV_BLOCK_LIST_INIT ZBCIALV INCLUDE TYPE REF TO TYPE BEGIN\SOF END\SOF LIKE INTO FROM WHERE ORDER BY ' +
 'WITH KEY INTO STRING SEPARATED BY EXPORTING IMPORTING TO UPPER CASE TO EXCEPTIONS TABLES USING CHANGING';

 this.regexList = [
  { regex: new RegExp('^\\*.*$', 'gm'),						css: 'comment' },   // one line comments
  { regex: new RegExp('\\".*$', 'gm'),						css: 'comment' },   // one line comments
  { regex: dp.sh.RegexLib.SingleQuotedString,				css: 'string' },   // strings
  { regex: new RegExp(this.GetKeywords(datatypes), 'gm'),	css: 'datatypes' },
  { regex: new RegExp(this.GetKeywords(keywords), 'gm'),	css: 'keyword' }
  ];

 this.CssClass = 'dp-abap';
}

dp.sh.Brushes.Abap.prototype = new dp.sh.Highlighter();
dp.sh.Brushes.Abap.Aliases = ['abap'];