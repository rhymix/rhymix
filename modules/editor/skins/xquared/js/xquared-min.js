var xq={majorVersion:"0.2",minorVersion:"20071205"};
xq.Class=function(){var D=null,C=xq.$A(arguments);
if(typeof C[0]=="function"){D=C.shift()
}function A(){this.initialize.apply(this,arguments)
}if(D){for(var B in D.prototype){A.prototype[B]=D.prototype[B]
}}for(var B in C[0]){A.prototype[B]=C[0][B]
}if(!A.prototype.initialize){A.prototype.initialize=function(){}
}A.prototype.constructor=A;
return A
};
xq.observe=function(B,A,C){if(B.addEventListener){B.addEventListener(A,C,false)
}else{B.attachEvent("on"+A,C)
}B=null
};
xq.stopObserving=function(B,A,C){if(B.removeEventListener){B.removeEventListener(A,C,false)
}else{B.detachEvent("on"+A,C)
}B=null
};
xq.cancelHandler=function(A){xq.stopEvent(A);
return false
};
xq.stopEvent=function(A){if(A.preventDefault){A.preventDefault()
}if(A.stopPropagation){A.stopPropagation()
}A.returnValue=false;
A.cancelBubble=true;
A.stopped=true
};
xq.isButton=function(B,A){return B.which?(B.which===A+1):(B.button===A)
};
xq.isLeftClick=function(A){return isButton(A,0)
};
xq.isMiddleClick=function(A){return isButton(A,1)
};
xq.isRightClick=function(A){return isButton(A,2)
};
xq.getEventPoint=function(A){return{x:A.pageX||(A.clientX+(document.documentElement.scrollLeft||document.body.scrollLeft)),y:A.pageY||(A.clientY+(document.documentElement.scrollTop||document.body.scrollTop))}
};
xq.getCumulativeOffset=function(A){var C=0,B=0;
do{C+=A.offsetTop||0;
B+=A.offsetLeft||0;
A=A.offsetParent
}while(A);
return{top:C,left:B}
};
xq.$=function(A){return document.getElementById(A)
};
xq.isEmptyHash=function(B){for(var A in B){return false
}return true
};
xq.$A=function(C){var A=C.length,B=new Array(A);
while(A--){B[A]=C[A]
}return B
};
xq.hasClassName=function(A,B){var C=A.className;
return(C.length>0&&(C==B||new RegExp("(^|\\s)"+B+"(\\s|$)").test(C)))
};
xq.serializeForm=function(F){try{var J={hash:true};
var C={};
var A=F.getElementsByTagName("*");
for(var D=0;
D<A.length;
D++){var E=A[D];
var B=E.tagName.toLowerCase();
if(E.disabled||!E.name||["input","textarea","option","select"].indexOf(B)==-1){continue
}var I=E.name;
var H=xq.getValueOfElement(E);
if(H===undefined){continue
}if(I in C){if(C[I].constructor==Array){C[I]=[C[I]]
}C[I].push(H)
}else{C[I]=H
}}return C
}catch(G){alert(G)
}};
xq.getValueOfElement=function(B){var A=B.type.toLowerCase();
if(A=="checkbox"||A=="radio"){return B.checked?B.value:undefined
}return B.value
};
xq.getElementsByClassName=function(D,E){if(D.getElementsByClassName){return D.getElementsByClassName(E)
}var F=D.getElementsByTagName("*");
var B=F.length;
var A=[];
var G=new RegExp("(^|\\s)"+E+"($|\\s)");
for(var C=0;
C<B;
C++){var H=F[C];
if(G.test(H.className)){A.push(H)
}}return A
};
try{Prototype.version;
__prototype=true
}catch(ignored){__prototype=false
}if(!__prototype){if(!Function.prototype.bind){Function.prototype.bind=function(){var B=this,A=xq.$A(arguments),C=A.shift();
return function(){return B.apply(C,A.concat(xq.$A(arguments)))
}}
}if(!Function.prototype.bindAsEventListener){Function.prototype.bindAsEventListener=function(){var B=this,A=xq.$A(arguments),C=A.shift();
return function(D){return B.apply(C,[D||window.event].concat(A))
}}
}Array.prototype.find=function(B){for(var A=0;
A<this.length;
A++){if(B(this[A])){return this[A]
}}};
Array.prototype.findAll=function(C){var A=[];
for(var B=0;
B<this.length;
B++){if(C(this[B])){A.push(this[B])
}}return A
};
Array.prototype.first=function(){return this[0]
};
Array.prototype.last=function(){return this[this.length-1]
};
Array.prototype.include=function(C){if(this.indexOf(C)!=-1){return true
}var B=false;
for(var A=0;
A<this.length;
A++){if(this[A]==C){return true
}}return false
};
Array.prototype.flatten=function(){var A=[];
var B=function(D){for(var C=0;
C<D.length;
C++){if(D[C].constructor===Array){B(D[C])
}else{A.push(D[C])
}}};
B(this);
return A
};
String.prototype.blank=function(){return/^\s*$/.test(this)
};
String.prototype.stripTags=function(){return this.replace(/<\/?[^>]+>/gi,"")
};
String.prototype.escapeHTML=function(){xq._text.data=this;
return xq._div.innerHTML
};
xq._text=document.createTextNode("");
xq._div=document.createElement("div");
xq._div.appendChild(xq._text);
String.prototype.strip=function(){return this.replace(/^\s+/,"").replace(/\s+$/,"")
};
Array.prototype.indexOf=function(B){for(var A=0;
A<this.length;
A++){if(this[A]==B){return A
}}return -1
}}xq.asEventSource=function(A,D,C){A._listeners=[];
A._registerEventFirer=function(F,E){this["_fireOn"+E]=function(){for(var G=0;
G<this._listeners.length;
G++){var I=this._listeners[G];
var H=I["on"+F+E];
if(H){H.apply(I,xq.$A(arguments))
}}}
};
A.addListener=function(E){this._listeners.push(E)
};
for(var B=0;
B<C.length;
B++){A._registerEventFirer(D,C[B])
}};
Date.preset=null;
Date.pass=function(A){if(Date.preset==null){return 
}Date.preset=new Date(Date.preset.getTime()+A)
};
Date.get=function(){return Date.preset==null?new Date():Date.preset
};
Date.prototype.elapsed=function(A){return Date.get().getTime()-this.getTime()>=A
};
String.prototype.merge=function(B){var A=this;
for(k in B){A=A.replace("{"+k+"}",B[k])
}return A
};
String.prototype.parseURL=function(){var E=this.match(/((((\w+):\/\/(((([^@:]+)(:([^@]+))?)@)?([^:\/\?#]+)?(:(\d+))?))?([^\?#]+)?)(\?([^#]+))?)(#(.+))?/);
var D=E[0];
var B=E[1]||undefined;
var M=E[2]||undefined;
var L=E[3]||undefined;
var A=null;
var N=E[4]||undefined;
var G=E[8]||undefined;
var K=E[10]||undefined;
var F=E[11]||undefined;
var C=E[13]||undefined;
var O=E[14]||undefined;
var J=E[16]||undefined;
var H=E[18]||undefined;
if(!O||O=="/"){A=L+"/"
}else{var I=O.lastIndexOf("/");
A=L+O.substring(0,I+1)
}return{includeAnchor:D,includeQuery:B,includePath:M,includeBase:A,includeHost:L,protocol:N,user:G,password:K,domain:F,port:C,path:O,query:J,anchor:H}
};
xq.autoFinalizeQueue=[];
xq.addToFinalizeQueue=function(A){xq.autoFinalizeQueue.push(A)
};
xq.finalize=function(B){if(typeof B.finalize=="function"){try{B.finalize()
}catch(A){}}for(key in B){B[key]=null
}};
xq.observe(window,"unload",function(){for(var A=0;
A<xq.autoFinalizeQueue.length;
A++){xq.finalize(xq.autoFinalizeQueue[A])
}xq=null
});
xq.findXquaredScript=function(){return xq.$A(document.getElementsByTagName("script")).find(function(A){return A.src&&A.src.match(/xquared\.js/i)
})
};
xq.shouldLoadOthers=function(){var A=xq.findXquaredScript();
return A&&!!A.src.match(/xquared\.js\?load_others=1/i)
};
xq.loadScript=function(A){document.write("<script type=\"text/javascript\" src=\""+A+"\"></script>")
};
xq.loadOthers=function(){var A=xq.findXquaredScript();
var D=A.src.match(/(.*\/)xquared\.js.*/i)[1];
var C=["Editor.js","Browser.js","Shortcut.js","DomTree.js","RichDom.js","RichDomW3.js","RichDomGecko.js","RichDomWebkit.js","RichDomTrident.js","RichTable.js","Validator.js","ValidatorW3.js","ValidatorGecko.js","ValidatorWebkit.js","ValidatorTrident.js","EditHistory.js","Controls.js","_ui_templates.js"];
for(var B=0;
B<C.length;
B++){xq.loadScript(D+C[B])
}};
if(xq.shouldLoadOthers()){xq.loadOthers()
}xq.Editor=xq.Class({initialize:function(B,A){xq.addToFinalizeQueue(this);
if(typeof B=="string"){B=xq.$(B)
}if(!B){throw"[contentElement] is null"
}if(B.nodeType!=1){throw"[contentElement] is not an element"
}if(typeof A=="string"){A=xq.$(A)
}xq.asEventSource(this,"Editor",["ElementChanged","BeforeEvent","AfterEvent","CurrentContentChanged","StaticContentChanged","CurrentEditModeChanged"]);
this.config={};
this.config.enableLinkClick=false;
this.config.changeCursorOnLink=false;
this.config.generateDefaultToolbar=true;
this.config.defaultToolbarButtonMap=[[{className:"foregroundColor",title:"Foreground color",handler:"xed.handleForegroundColor()"},{className:"backgroundColor",title:"Background color",handler:"xed.handleBackgroundColor()"}],[{className:"link",title:"Link",handler:"xed.handleLink()"},{className:"strongEmphasis",title:"Strong emphasis",handler:"xed.handleStrongEmphasis()"},{className:"emphasis",title:"Emphasis",handler:"xed.handleEmphasis()"},{className:"underline",title:"Underline",handler:"xed.handleUnderline()"},{className:"strike",title:"Strike",handler:"xed.handleStrike()"},{className:"superscription",title:"Superscription",handler:"xed.handleSuperscription()"},{className:"subscription",title:"Subscription",handler:"xed.handleSubscription()"}],[{className:"removeFormat",title:"Remove format",handler:"xed.handleRemoveFormat()"}],[{className:"justifyLeft",title:"Justify left",handler:"xed.handleJustify('left')"},{className:"justifyCenter",title:"Justify center",handler:"xed.handleJustify('center')"},{className:"justifyRight",title:"Justify right",handler:"xed.handleJustify('right')"},{className:"justifyBoth",title:"Justify both",handler:"xed.handleJustify('both')"}],[{className:"indent",title:"Indent",handler:"xed.handleIndent()"},{className:"outdent",title:"Outdent",handler:"xed.handleOutdent()"}],[{className:"unorderedList",title:"Unordered list",handler:"xed.handleList('UL')"},{className:"orderedList",title:"Ordered list",handler:"xed.handleList('OL')"}],[{className:"paragraph",title:"Paragraph",handler:"xed.handleApplyBlock('P')"},{className:"heading1",title:"Heading 1",handler:"xed.handleApplyBlock('H1')"},{className:"blockquote",title:"Blockquote",handler:"xed.handleApplyBlock('BLOCKQUOTE')"},{className:"code",title:"Code",handler:"xed.handleList('CODE')"},{className:"division",title:"Division",handler:"xed.handleApplyBlock('DIV')"}],[{className:"table",title:"Table",handler:"xed.handleTable(3,3,'tl')"},{className:"separator",title:"Separator",handler:"xed.handleSeparator()"}],[{className:"html",title:"Edit source",handler:"xed.toggleSourceAndWysiwygMode()"}],[{className:"undo",title:"Undo",handler:"xed.handleUndo()"},{className:"redo",title:"Redo",handler:"xed.handleRedo()"}]];
this.config.imagePathForDefaultToobar="img/toolbar/";
this.config.imagePathForContent="img/content/";
this.config.urlValidationMode="absolute";
this.config.automaticallyHookSubmitEvent=true;
this.config.allowedTags=["a","abbr","acronym","address","blockquote","br","caption","cite","code","dd","dfn","div","dl","dt","em","h1","h2","h3","h4","h5","h6","hr","img","kbd","li","ol","p","pre","q","samp","span","sup","sub","strong","table","thead","tbody","td","th","tr","ul","var"];
this.config.allowedAttributes=["alt","cite","class","datetime","height","href","id","rel","rev","src","style","title","width"];
this.config.shortcuts={};
this.config.autocorrections={};
this.config.autocompletions={};
this.config.templateProcessors={};
this.config.contextMenuHandlers={};
this.contentElement=B;
this.doc=this.contentElement.ownerDocument;
this.body=this.doc.body;
this.currentEditMode="readonly";
this.rdom=xq.RichDom.createInstance();
this.validator=null;
this.outmostWrapper=null;
this.sourceEditorDiv=null;
this.sourceEditorTextarea=null;
this.wysiwygEditorDiv=null;
this.editorFrame=null;
this.editorWin=null;
this.editorDoc=null;
this.editorBody=null;
this.toolbarContainer=A;
this.toolbarButtons=null;
this._toolbarAnchorsCache=[];
this.editHistory=null;
this._contextMenuContainer=null;
this._contextMenuItems=null;
this._validContentCache=null;
this._lastModified=null;
this.addShortcuts(this._getDefaultShortcuts());
this.addTemplateProcessors(this._getDefaultTemplateProcessors());
this.addListener({onEditorCurrentContentChanged:function(D){var C=D.rdom.getCurrentElement();
if(!C){return 
}if(D._lastFocusElement!=C){if(!D.rdom.tree.isBlockOnlyContainer(D._lastFocusElement)&&D.rdom.tree.isBlock(D._lastFocusElement)){D.rdom.removeTrailingWhitespace(D._lastFocusElement)
}D._fireOnElementChanged(D._lastFocusElement,C);
D._lastFocusElement=C
}D.updateAllToolbarButtonsStatus(C)
}})
},finalize:function(){for(var A=0;
A<this._toolbarAnchorsCache.length;
A++){this._toolbarAnchorsCache[A].xed=null;
this._toolbarAnchorsCache[A].handler=null;
this._toolbarAnchorsCache[A]=null
}this._toolbarAnchorsCache=null
},_getDefaultShortcuts:function(){if(xq.Browser.isMac){return[{event:"Ctrl+Shift+SPACE",handler:"this.handleAutocompletion(); stop = true;"},{event:"ENTER",handler:"this.handleEnter(false, false)"},{event:"Ctrl+ENTER",handler:"this.handleEnter(true, false)"},{event:"Ctrl+Shift+ENTER",handler:"this.handleEnter(true, true)"},{event:"TAB",handler:"this.handleTab()"},{event:"Shift+TAB",handler:"this.handleShiftTab()"},{event:"DELETE",handler:"this.handleDelete()"},{event:"BACKSPACE",handler:"this.handleBackspace()"},{event:"Ctrl+B",handler:"this.handleStrongEmphasis()"},{event:"Ctrl+I",handler:"this.handleEmphasis()"},{event:"Ctrl+U",handler:"this.handleUnderline()"},{event:"Ctrl+K",handler:"this.handleStrike()"},{event:"Meta+Z",handler:"this.handleUndo()"},{event:"Meta+Shift+Z",handler:"this.handleRedo()"},{event:"Meta+Y",handler:"this.handleRedo()"}]
}else{if(xq.Browser.isUbuntu){return[{event:"Ctrl+SPACE",handler:"this.handleAutocompletion(); stop = true;"},{event:"ENTER",handler:"this.handleEnter(false, false)"},{event:"Ctrl+ENTER",handler:"this.handleEnter(true, false)"},{event:"Ctrl+Shift+ENTER",handler:"this.handleEnter(true, true)"},{event:"TAB",handler:"this.handleTab()"},{event:"Shift+TAB",handler:"this.handleShiftTab()"},{event:"DELETE",handler:"this.handleDelete()"},{event:"BACKSPACE",handler:"this.handleBackspace()"},{event:"Ctrl+B",handler:"this.handleStrongEmphasis()"},{event:"Ctrl+I",handler:"this.handleEmphasis()"},{event:"Ctrl+U",handler:"this.handleUnderline()"},{event:"Ctrl+K",handler:"this.handleStrike()"},{event:"Ctrl+Z",handler:"this.handleUndo()"},{event:"Ctrl+Y",handler:"this.handleRedo()"}]
}else{return[{event:"Ctrl+SPACE",handler:"this.handleAutocompletion(); stop = true;"},{event:"ENTER",handler:"this.handleEnter(false, false)"},{event:"Ctrl+ENTER",handler:"this.handleEnter(true, false)"},{event:"Ctrl+Shift+ENTER",handler:"this.handleEnter(true, true)"},{event:"TAB",handler:"this.handleTab()"},{event:"Shift+TAB",handler:"this.handleShiftTab()"},{event:"DELETE",handler:"this.handleDelete()"},{event:"BACKSPACE",handler:"this.handleBackspace()"},{event:"Ctrl+B",handler:"this.handleStrongEmphasis()"},{event:"Ctrl+I",handler:"this.handleEmphasis()"},{event:"Ctrl+U",handler:"this.handleUnderline()"},{event:"Ctrl+K",handler:"this.handleStrike()"},{event:"Ctrl+Z",handler:"this.handleUndo()"},{event:"Ctrl+Y",handler:"this.handleRedo()"}]
}}},_getDefaultTemplateProcessors:function(){return[{id:"predefinedKeywordProcessor",handler:function(C){var A=Date.get();
var B={year:A.getFullYear(),month:A.getMonth()+1,date:A.getDate(),hour:A.getHours(),min:A.getMinutes(),sec:A.getSeconds()};
return C.replace(/\{xq:(year|month|date|hour|min|sec)\}/img,function(E,D){return B[D]||D
})
}}]
},addShortcut:function(A,B){this.config.shortcuts[A]={"event":new xq.Shortcut(A),"handler":B}
},addShortcuts:function(B){for(var A=0;
A<B.length;
A++){this.addShortcut(B[A].event,B[A].handler)
}},getShortcut:function(A){return this.config.shortcuts[A]
},getShortcuts:function(){return this.config.shortcuts
},removeShortcut:function(A){delete this.config.shortcuts[A]
},addAutocorrection:function(D,C,A){if(C.exec){var B=C;
C=function(E){return E.match(B)
}}this.config.autocorrections[D]={"criteria":C,"handler":A}
},addAutocorrections:function(B){for(var A=0;
A<B.length;
A++){this.addAutocorrection(B[A].id,B[A].criteria,B[A].handler)
}},getAutocorrection:function(A){return this.config.autocorrection[A]
},getAutocorrections:function(){return this.config.autocorrections
},removeAutocorrection:function(A){delete this.config.autocorrections[A]
},addAutocompletion:function(D,C,A){if(C.exec){var B=C;
C=function(F){var E=B.exec(F);
return E?E.index:-1
}}this.config.autocompletions[D]={"criteria":C,"handler":A}
},addAutocompletions:function(B){for(var A=0;
A<B.length;
A++){this.addAutocompletion(B[A].id,B[A].criteria,B[A].handler)
}},getAutocompletion:function(A){return this.config.autocompletions[A]
},getAutocompletions:function(){return this.config.autocompletions
},removeAutocompletion:function(A){delete this.config.autocompletions[A]
},addTemplateProcessor:function(B,A){this.config.templateProcessors[B]={"handler":A}
},addTemplateProcessors:function(B){for(var A=0;
A<B.length;
A++){this.addTemplateProcessor(B[A].id,B[A].handler)
}},getTemplateProcessor:function(A){return this.config.templateProcessors[A]
},getTemplateProcessors:function(){return this.config.templateProcessors
},removeTemplateProcessor:function(A){delete this.config.templateProcessors[A]
},addContextMenuHandler:function(B,A){this.config.contextMenuHandlers[B]={"handler":A}
},addContextMenuHandlers:function(B){for(var A=0;
A<B.length;
A++){this.addContextMenuHandler(B[A].id,B[A].handler)
}},getContextMenuHandler:function(A){return this.config.contextMenuHandlers[A]
},getContextMenuHandlers:function(){return this.config.contextMenuHandlers
},removeContextMenuHandler:function(A){delete this.config.contextMenuHandlers[A]
},getCurrentEditMode:function(){return this.currentEditMode
},toggleSourceAndWysiwygMode:function(){var A=this.getCurrentEditMode();
if(A=="readonly"){return 
}this.setEditMode(A=="wysiwyg"?"source":"wysiwyg");
return true
},setEditMode:function(B){if(this.currentEditMode==B){return 
}var A=B!=false&&B!="readonly"&&!this.outmostWrapper;
if(A){this._createEditorFrame();
this._registerEventHandlers();
this.loadCurrentContentFromStaticContent();
this.editHistory=new xq.EditHistory(this.rdom)
}if(B=="wysiwyg"){if(this.currentEditMode=="source"){this.setStaticContent(this.getSourceContent())
}this.loadCurrentContentFromStaticContent();
this.contentElement.style.display="none";
this.sourceEditorDiv.style.display="none";
this.wysiwygEditorDiv.style.display="block";
this.outmostWrapper.style.display="block";
this.currentEditMode=B;
if(!xq.Browser.isTrident){window.setTimeout(function(){if(this.getDoc().designMode=="On"){return 
}this.getDoc().designMode="On";
try{this.getDoc().execCommand("enableInlineTableEditing",false,"false")
}catch(C){}}.bind(this),0)
}this.enableToolbarButtons();
if(!A){this.focus()
}}else{if(B=="source"){if(this.currentEditMode=="wysiwyg"){this.setStaticContent(this.getWysiwygContent())
}this.loadCurrentContentFromStaticContent();
this.contentElement.style.display="none";
this.sourceEditorDiv.style.display="block";
this.wysiwygEditorDiv.style.display="none";
this.outmostWrapper.style.display="block";
this.currentEditMode=B;
this.disableToolbarButtons(["html"]);
if(!A){this.focus()
}}else{this.setStaticContent(this.getCurrentContent());
this.loadCurrentContentFromStaticContent();
this.outmostWrapper.style.display="none";
this.contentElement.style.display="block";
this.currentEditMode=B
}}this._fireOnCurrentEditModeChanged(this,B)
},loadStylesheet:function(C){var A=this.editorDoc.getElementsByTagName("HEAD")[0];
var B=this.editorDoc.createElement("LINK");
B.rel="Stylesheet";
B.type="text/css";
B.href=C;
A.appendChild(B)
},loadCurrentContentFromStaticContent:function(){var A=this.validator.invalidate(this.getStaticContentAsDOM());
A=this.removeUnnecessarySpaces(A);
if(A.blank()){this.rdom.clearRoot()
}else{this.rdom.getRoot().innerHTML=A
}this.rdom.wrapAllInlineOrTextNodesAs("P",this.rdom.getRoot(),true);
var B=this.getWysiwygContent(true,true);
this.sourceEditorTextarea.value=B;
if(xq.Browser.isWebkit){this.sourceEditorTextarea.innerHTML=B
}this._fireOnCurrentContentChanged(this)
},enableToolbarButtons:function(A){if(!this.toolbarContainer){return 
}this._execForAllToolbarButtons(A,function(B,C){B.firstChild.className=!C?"":"disabled"
});
if(xq.Browser.isIE6){this.toolbarContainer.style.display="none";
setTimeout(function(){this.toolbarContainer.style.display="block"
}.bind(this),0)
}},disableToolbarButtons:function(A){this._execForAllToolbarButtons(A,function(B,C){B.firstChild.className=C?"":"disabled"
})
},_execForAllToolbarButtons:function(E,A){if(!this.toolbarContainer){return 
}E=E||[];
var B=this.toolbarContainer.getElementsByTagName("LI");
for(var D=0;
D<B.length;
D++){var F=B[D].className.split(" ").find(function(G){return G!="xq_separator"
});
var C=E.indexOf(F)!=-1;
A(B[D],C)
}},_updateToolbarButtonStatus:function(C,B){var A=this.toolbarButtons[C];
if(A){A.firstChild.firstChild.className=B?"selected":""
}},updateAllToolbarButtonsStatus:function(C){if(!this.toolbarContainer){return 
}if(!this.toolbarButtons){var G=["emphasis","strongEmphasis","underline","strike","superscription","subscription","justifyLeft","justifyCenter","justifyRight","justifyBoth","unorderedList","orderedList","code","paragraph","heading1","heading2","heading3","heading4","heading5","heading6"];
this.toolbarButtons={};
for(var B=0;
B<G.length;
B++){var E=xq.getElementsByClassName(this.toolbarContainer,G[B]);
var A=E&&E.length>0?E[0]:null;
if(A){this.toolbarButtons[G[B]]=A
}}}var D=this.toolbarButtons;
var F=this.rdom.collectStructureAndStyle(C);
this._updateToolbarButtonStatus("emphasis",F.em);
this._updateToolbarButtonStatus("strongEmphasis",F.strong);
this._updateToolbarButtonStatus("underline",F.underline);
this._updateToolbarButtonStatus("strike",F.strike);
this._updateToolbarButtonStatus("superscription",F.superscription);
this._updateToolbarButtonStatus("subscription",F.subscription);
this._updateToolbarButtonStatus("justifyLeft",F.justification=="left");
this._updateToolbarButtonStatus("justifyCenter",F.justification=="center");
this._updateToolbarButtonStatus("justifyRight",F.justification=="right");
this._updateToolbarButtonStatus("justifyBoth",F.justification=="justify");
this._updateToolbarButtonStatus("orderedList",F.list=="OL");
this._updateToolbarButtonStatus("unorderedList",F.list=="UL");
this._updateToolbarButtonStatus("code",F.list=="CODE");
this._updateToolbarButtonStatus("paragraph",F.block=="P");
this._updateToolbarButtonStatus("heading1",F.block=="H1");
this._updateToolbarButtonStatus("heading2",F.block=="H2");
this._updateToolbarButtonStatus("heading3",F.block=="H3");
this._updateToolbarButtonStatus("heading4",F.block=="H4");
this._updateToolbarButtonStatus("heading5",F.block=="H5");
this._updateToolbarButtonStatus("heading6",F.block=="H6")
},removeUnnecessarySpaces:function(A){var C=this.rdom.tree.getBlockTags().join("|");
var B=new RegExp("\\s*<(/?)("+C+")>\\s*","img");
return A.replace(B,"<$1$2>")
},getCurrentContent:function(A){if(this.getCurrentEditMode()=="source"){return this.getSourceContent(A)
}else{return this.getWysiwygContent(A)
}},getWysiwygContent:function(A,B){if(B||!A){return this.validator.validate(this.rdom.getRoot(),A)
}var C=this.editHistory.getLastModifiedDate();
if(this._lastModified!=C){this._validContentCache=this.validator.validate(this.rdom.getRoot(),A);
this._lastModified=C
}return this._validContentCache
},getSourceContent:function(C){var B=this.sourceEditorTextarea[xq.Browser.isWebkit?"innerHTML":"value"];
var A=document.createElement("div");
A.innerHTML=this.removeUnnecessarySpaces(B);
var D=xq.RichDom.createInstance();
D.setRoot(document.body);
D.wrapAllInlineOrTextNodesAs("P",A,true);
return this.validator.validate(A,C)
},setStaticContent:function(A){if(this.contentElement.nodeName=="TEXTAREA"){this.contentElement.value=A;
if(xq.Browser.isWebkit){this.contentElement.innerHTML=A
}}else{this.contentElement.innerHTML=A
}this._fireOnStaticContentChanged(this,A)
},getStaticContent:function(){var A;
if(this.contentElement.nodeName=="TEXTAREA"){A=this.contentElement[xq.Browser.isWebkit?"innerHTML":"value"]
}else{A=this.contentElement.innerHTML
}return A
},getStaticContentAsDOM:function(){if(this.contentElement.nodeName=="TEXTAREA"){var A=this.doc.createElement("DIV");
A.innerHTML=this.contentElement[xq.Browser.isWebkit?"innerHTML":"value"];
return A
}else{return this.contentElement
}},focus:function(){if(this.getCurrentEditMode()=="wysiwyg"){this.rdom.focus();
window.setTimeout(function(){this.updateAllToolbarButtonsStatus(this.rdom.getCurrentElement())
}.bind(this),0)
}else{if(this.getCurrentEditMode()=="source"){this.sourceEditorTextarea.focus()
}}},getFrame:function(){return this.editorFrame
},getWin:function(){return this.editorWin
},getDoc:function(){return this.editorDoc
},getOutmostWrapper:function(){return this.outmostWrapper
},getBody:function(){return this.editorBody
},_createEditorFrame:function(){this.outmostWrapper=this.doc.createElement("div");
this.outmostWrapper.className="xquared";
this.contentElement.parentNode.insertBefore(this.outmostWrapper,this.contentElement);
if(!this.toolbarContainer&&this.config.generateDefaultToolbar){this.toolbarContainer=this._generateDefaultToolbar();
this.outmostWrapper.appendChild(this.toolbarContainer)
}this.sourceEditorDiv=this.doc.createElement("div");
this.sourceEditorDiv.className="editor source_editor";
this.sourceEditorDiv.style.display="none";
this.outmostWrapper.appendChild(this.sourceEditorDiv);
this.sourceEditorTextarea=this.doc.createElement("textarea");
this.sourceEditorDiv.appendChild(this.sourceEditorTextarea);
this.wysiwygEditorDiv=this.doc.createElement("div");
this.wysiwygEditorDiv.className="editor wysiwyg_editor";
this.wysiwygEditorDiv.style.display="none";
this.outmostWrapper.appendChild(this.wysiwygEditorDiv);
this.editorFrame=this.doc.createElement("iframe");
this.rdom.setAttributes(this.editorFrame,{"frameBorder":"0","marginWidth":"0","marginHeight":"0","leftMargin":"0","topMargin":"0","allowTransparency":"true"});
this.wysiwygEditorDiv.appendChild(this.editorFrame);
var B=this.editorFrame.contentWindow.document;
if(xq.Browser.isTrident){B.designMode="On"
}B.open();
B.write("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">");
B.write("<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ko\">");
B.write("<head>");
if(!xq.Browser.isTrident){B.write("<base href=\"./\" />")
}B.write("<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\" />");
B.write("<title>XQuared</title>");
if(this.config.changeCursorOnLink){B.write("<style>.xed a {cursor: pointer !important;}</style>")
}B.write("</head>");
B.write("<body><p>"+this.rdom.makePlaceHolderString()+"</p></body>");
B.write("</html>");
B.close();
this.editorWin=this.editorFrame.contentWindow;
this.editorDoc=this.editorWin.document;
this.editorBody=this.editorDoc.body;
this.editorBody.className="xed";
if(xq.Browser.isIE6){this.editorDoc.documentElement.style.overflowY="auto";
this.editorDoc.documentElement.style.overflowX="hidden"
}if(this.config.generateDefaultToolbar){this._addStyleRules([{selector:".xquared div.toolbar",rule:"background-image: url("+this.config.imagePathForDefaultToobar+"toolbarBg.gif)"},{selector:".xquared ul.buttons li",rule:"background-image: url("+this.config.imagePathForDefaultToobar+"toolbarButtonBg.gif)"},{selector:".xquared ul.buttons li.xq_separator",rule:"background-image: url("+this.config.imagePathForDefaultToobar+"toolbarSeparator.gif)"}])
}this.rdom.setWin(this.editorWin);
this.rdom.setRoot(this.editorBody);
this.validator=xq.Validator.createInstance(this.doc.location.href,this.config.urlValidationMode,this.config.allowedTags,this.config.allowedAttributes);
if(this.config.automaticallyHookSubmitEvent&&this.contentElement.nodeName=="TEXTAREA"&&this.contentElement.form){var A=this.contentElement.form.onsubmit;
this.contentElement.form.onsubmit=function(){this.contentElement.value=this.getCurrentContent(true);
if(A){return A()
}else{return true
}}.bind(this)
}},_addStyleRules:function(D){if(!this.dynamicStyle){if(xq.Browser.isTrident){this.dynamicStyle=this.doc.createStyleSheet()
}else{var B=this.doc.createElement("style");
this.doc.body.appendChild(B);
this.dynamicStyle=xq.$A(this.doc.styleSheets).last()
}}for(var A=0;
A<D.length;
A++){var C=D[A];
if(xq.Browser.isTrident){this.dynamicStyle.addRule(D[A].selector,D[A].rule)
}else{this.dynamicStyle.insertRule(D[A].selector+" {"+D[A].rule+"}",this.dynamicStyle.cssRules.length)
}}},_defaultToolbarClickHandler:function(e){var src=e.target||e.srcElement;
while(src.nodeName!="A"){src=src.parentNode
}if(xq.hasClassName(src.parentNode,"disabled")||xq.hasClassName(this.toolbarContainer,"disabled")){xq.stopEvent(e);
return false
}if(xq.Browser.isTrident){this.focus()
}var handler=src.handler;
var xed=this;
var stop=(typeof handler=="function")?handler(this):eval(handler);
if(stop){xq.stopEvent(e);
return false
}else{return true
}},_generateDefaultToolbar:function(){var B=this.doc.createElement("div");
B.className="toolbar";
var G=this.doc.createElement("ul");
G.className="buttons";
B.appendChild(G);
var A=this.config.defaultToolbarButtonMap;
for(var F=0;
F<A.length;
F++){for(var D=0;
D<A[F].length;
D++){var C=A[F][D];
var J=this.doc.createElement("li");
G.appendChild(J);
J.className=C.className;
var I=this.doc.createElement("span");
J.appendChild(I);
var H=this.doc.createElement("a");
I.appendChild(H);
H.href="#";
H.title=C.title;
H.handler=C.handler;
this._toolbarAnchorsCache.push(H);
xq.observe(H,"mousedown",xq.cancelHandler);
xq.observe(H,"click",this._defaultToolbarClickHandler.bindAsEventListener(this));
var E=this.doc.createElement("img");
H.appendChild(E);
E.src=this.config.imagePathForDefaultToobar+C.className+".gif";
if(D==0&&F!=0){J.className+=" xq_separator"
}}}return B
},_registerEventHandlers:function(){var B=["keydown","click","keyup","mouseup","contextmenu"];
if(xq.Browser.isTrident&&this.config.changeCursorOnLink){B.push("mousemove")
}if(xq.Browser.isMac&&xq.Browser.isGecko){B.push("keypress")
}for(var A=0;
A<B.length;
A++){xq.observe(this.getDoc(),B[A],this._handleEvent.bindAsEventListener(this))
}},_handleEvent:function(e){this._fireOnBeforeEvent(this,e);
var stop=false;
var modifiedByCorrection=false;
if(e.type=="mousemove"&&this.config.changeCursorOnLink){var link=!!this.rdom.getParentElementOf(e.srcElement,["A"]);
var editable=this.editorBody.contentEditable;
editable=editable=="inherit"?false:editable;
if(editable!=link&&!this.rdom.hasSelection()){this.editorBody.contentEditable=!link
}}else{if(e.type=="click"&&e.button==0&&this.config.enableLinkClick){var a=this.rdom.getParentElementOf(e.target||e.srcElement,["A"]);
if(a){stop=this.handleClick(e,a)
}}else{if(e.type==(xq.Browser.isMac&&xq.Browser.isGecko?"keypress":"keydown")){var undoPerformed=false;
modifiedByCorrection=this.rdom.correctParagraph();
for(var key in this.config.shortcuts){if(!this.config.shortcuts[key].event.matches(e)){continue
}var handler=this.config.shortcuts[key].handler;
var xed=this;
stop=(typeof handler=="function")?handler(this):eval(handler);
if(key=="undo"){undoPerformed=true
}}}else{if(["mouseup","keyup"].indexOf(e.type)!=-1){modifiedByCorrection=this.rdom.correctParagraph()
}else{if(["contextmenu"].indexOf(e.type)!=-1){this._handleContextMenu(e)
}}}}}if(stop){xq.stopEvent(e)
}this._fireOnCurrentContentChanged(this);
this._fireOnAfterEvent(this,e);
if(!undoPerformed&&!modifiedByCorrection){this.editHistory.onEvent(e)
}return !stop
},handleAutocorrection:function(){var block=this.rdom.getCurrentBlockElement();
var text=this.rdom.getInnerText(block).replace(/&nbsp;/gi," ");
var acs=this.config.autocorrections;
var performed=false;
var stop=false;
for(var key in acs){var ac=acs[key];
if(ac.criteria(text)){try{this.editHistory.onCommand();
this.editHistory.disable();
if(typeof ac.handler=="String"){var xed=this;
var rdom=this.rdom;
eval(ac.handler)
}else{stop=ac.handler(this,this.rdom,block,text)
}this.editHistory.enable()
}catch(ignored){}block=this.rdom.getCurrentBlockElement();
text=this.rdom.getInnerText(block);
performed=true;
if(stop){break
}}}return stop
},handleAutocompletion:function(){var acs=this.config.autocompletions;
if(xq.isEmptyHash(acs)){return 
}if(this.rdom.hasSelection()){var text=this.rdom.getSelectionAsText();
this.rdom.deleteSelection();
var wrapper=this.rdom.insertNode(this.rdom.createElement("SPAN"));
wrapper.innerHTML=text;
var marker=this.rdom.pushMarker();
var filtered=[];
for(var key in acs){filtered.push([key,acs[key].criteria(text)])
}filtered=filtered.findAll(function(elem){return elem[1]!=-1
});
if(filtered.length==0){this.rdom.popMarker(true);
return 
}var minIndex=0;
var min=filtered[0][1];
for(var i=0;
i<filtered.length;
i++){if(filtered[i][1]<min){minIndex=i;
min=filtered[i][1]
}}var ac=acs[filtered[minIndex][0]];
this.editHistory.disable()
}else{var marker=this.rdom.pushMarker();
var filtered=[];
for(var key in acs){filtered.push([key,this.rdom.testSmartWrap(marker,acs[key].criteria).textIndex])
}filtered=filtered.findAll(function(elem){return elem[1]!=-1
});
if(filtered.length==0){this.rdom.popMarker(true);
return 
}var minIndex=0;
var min=filtered[0][1];
for(var i=0;
i<filtered.length;
i++){if(filtered[i][1]<min){minIndex=i;
min=filtered[i][1]
}}var ac=acs[filtered[minIndex][0]];
this.editHistory.disable();
var wrapper=this.rdom.smartWrap(marker,"SPAN",ac.criteria)
}var block=this.rdom.getCurrentBlockElement();
var text=this.rdom.getInnerText(wrapper).replace(/&nbsp;/gi," ");
try{if(typeof ac.handler=="String"){var xed=this;
var rdom=this.rdom;
eval(ac.handler)
}else{ac.handler(this,this.rdom,block,wrapper,text)
}}catch(ignored){}try{this.rdom.unwrapElement(wrapper)
}catch(ignored){}if(this.rdom.isEmptyBlock(block)){this.rdom.correctEmptyElement(block)
}this.editHistory.enable();
this.editHistory.onCommand();
this.rdom.popMarker(true)
},handleClick:function(C,B){var A=decodeURI(B.href);
if(!xq.Browser.isTrident){if(!C.ctrlKey&&!C.shiftKey&&C.button!=1){window.location.href=A;
return true
}}else{if(C.shiftKey){window.open(A,"_blank")
}else{window.location.href=A
}return true
}return false
},handleLink:function(){var C=this.rdom.getSelectionAsText()||"";
var A=new xq.controls.FormDialog(this,xq.ui_templates.basicLinkDialog,function(D){if(C){D.form.text.value=C;
D.form.url.focus();
D.form.url.select()
}},function(E){this.focus();
if(xq.Browser.isTrident){var D=this.rdom.rng();
D.moveToBookmark(B);
D.select()
}if(!E){return 
}this.handleInsertLink(false,E.url,E.text,E.text)
}.bind(this));
if(xq.Browser.isTrident){var B=this.rdom.rng().getBookmark()
}A.show({position:"centerOfEditor"});
return true
},handleInsertLink:function(G,C,F,E){if(G&&!this.rdom.hasSelection()){var B=this.rdom.pushMarker();
var A=this.rdom.smartWrap(B,"A",function(I){var H=I.lastIndexOf(" ");
return H==-1?H:H+1
});
A.href=C;
A.title=F;
if(E){A.innerHTML="";
A.appendChild(this.rdom.createTextNode(E))
}else{if(!A.hasChildNodes()){this.rdom.deleteNode(A)
}}this.rdom.popMarker(true)
}else{E=E||(this.rdom.hasSelection()?this.rdom.getSelectionAsText():null);
if(!E){return 
}this.rdom.deleteSelection();
var A=this.rdom.createElement("A");
A.href=C;
A.title=F;
A.appendChild(this.rdom.createTextNode(E));
this.rdom.insertNode(A)
}var D=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleEnter:function(D,G){if(this.rdom.hasSelection()){return false
}if(!D&&this.handleAutocorrection()){return true
}var B=this.rdom.isCaretAtEmptyBlock();
var A=B||this.rdom.isCaretAtBlockStart();
var H=B||(!A&&this.rdom.isCaretAtBlockEnd());
var C=B||A||H;
if(!C){var E=this.rdom.getCurrentBlockElement();
var F=this.rdom.pushMarker();
if(this.rdom.isFirstLiWithNestedList(E)&&!G){var I=E.parentNode;
this.rdom.unwrapElement(E);
E=I
}else{if(E.nodeName!="LI"&&this.rdom.tree.isBlockContainer(E)){E=this.rdom.wrapAllInlineOrTextNodesAs("P",E,true).first()
}}this.rdom.splitElementUpto(F,E);
this.rdom.popMarker(true)
}else{if(B){this._handleEnterAtEmptyBlock()
}else{this._handleEnterAtEdge(A,G)
}}return true
},handleMoveBlock:function(A){var C=this.rdom.moveBlock(this.rdom.getCurrentBlockElement(),A);
if(C){this.rdom.selectElement(C,false);
C.scrollIntoView(false);
var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this)
}return true
},handleTab:function(){var A=this.rdom.hasSelection();
var B=this.rdom.getParentElementOf(this.rdom.getCurrentBlockElement(),["TABLE"]);
if(A){this.handleIndent()
}else{if(B&&B.className=="datatable"){this.handleMoveToNextCell()
}else{if(this.rdom.isCaretAtBlockStart()){this.handleIndent()
}else{this.handleInsertTab()
}}}return true
},handleShiftTab:function(){var A=this.rdom.hasSelection();
var B=this.rdom.getParentElementOf(this.rdom.getCurrentBlockElement(),["TABLE"]);
if(A){this.handleOutdent()
}else{if(B&&B.className=="datatable"){this.handleMoveToPreviousCell()
}else{this.handleOutdent()
}}return true
},handleInsertTab:function(){this.rdom.insertHtml("&nbsp;");
this.rdom.insertHtml("&nbsp;");
this.rdom.insertHtml("&nbsp;");
return true
},handleDelete:function(){if(this.rdom.hasSelection()||!this.rdom.isCaretAtBlockEnd()){return false
}return this._handleMerge(true)
},handleBackspace:function(){if(this.rdom.hasSelection()||!this.rdom.isCaretAtBlockStart()){return false
}return this._handleMerge(false)
},_handleMerge:function(C){var E=this.rdom.getCurrentBlockElement();
var B=this.rdom.pushMarker();
var A=this.rdom.mergeElement(E,C,C);
if(!A&&!C){this.rdom.extractOutElementFromParent(E)
}this.rdom.popMarker(true);
if(A){this.rdom.correctEmptyElement(A)
}var D=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return !!A
},handleMoveToNextCell:function(){this._handleMoveToCell("next")
},handleMoveToPreviousCell:function(){this._handleMoveToCell("prev")
},handleMoveToAboveCell:function(){this._handleMoveToCell("above")
},handleMoveToBelowCell:function(){this._handleMoveToCell("below")
},_handleMoveToCell:function(B){var C=this.rdom.getCurrentBlockElement();
var H=this.rdom.getParentElementOf(C,["TD","TH"]);
var J=this.rdom.getParentElementOf(H,["TABLE"]);
var I=new xq.RichTable(this.rdom,J);
var E=null;
if(["next","prev"].indexOf(B)!=-1){var G=B=="next";
E=G?I.getNextCellOf(H):I.getPreviousCellOf(H)
}else{var F=B=="below";
E=F?I.getBelowCellOf(H):I.getAboveCellOf(H)
}if(!E){var A=function(K){return["TD","TH"].indexOf(K.nodeName)==-1&&this.tree.isBlock(K)&&!this.tree.hasBlocks(K)
}.bind(this.rdom);
var D=function(K){return this.tree.isBlock(K)&&!this.tree.isDescendantOf(this.getRoot(),K)
}.bind(this.rdom);
E=(G||F)?this.rdom.tree.findForward(H,A,D):this.rdom.tree.findBackward(J,A,D)
}if(E){this.rdom.placeCaretAtStartOf(E)
}},handleStrongEmphasis:function(){this.rdom.applyStrongEmphasis();
var A=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleEmphasis:function(){this.rdom.applyEmphasis();
var A=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleUnderline:function(){this.rdom.applyUnderline();
var A=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleStrike:function(){this.rdom.applyStrike();
var A=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleRemoveFormat:function(){this.rdom.applyRemoveFormat();
var A=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleTable:function(F,E,C){var H=this.rdom.getCurrentBlockElement();
if(this.rdom.getParentElementOf(H,["TABLE"])){return true
}var B=xq.RichTable.create(this.rdom,F,E,C);
if(this.rdom.tree.isBlockContainer(H)){var D=this.rdom.wrapAllInlineOrTextNodesAs("P",H,true);
H=D.last()
}var A=this.rdom.insertNodeAt(B.getDom(),H,"after");
this.rdom.placeCaretAtStartOf(B.getCellAt(0,0));
if(this.rdom.isEmptyBlock(H)){this.rdom.deleteNode(H,true)
}var G=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleInsertNewRowAt:function(A){var F=this.rdom.getCurrentBlockElement();
var D=this.rdom.getParentElementOf(F,["TR"]);
if(!D){return true
}var C=this.rdom.getParentElementOf(D,["TABLE"]);
var B=new xq.RichTable(this.rdom,C);
var E=B.insertNewRowAt(D,A);
this.rdom.placeCaretAtStartOf(E.cells[0]);
return true
},handleInsertNewColumnAt:function(A){var D=this.rdom.getCurrentBlockElement();
var E=this.rdom.getParentElementOf(D,["TD"],true);
if(!E){return true
}var C=this.rdom.getParentElementOf(E,["TABLE"]);
var B=new xq.RichTable(this.rdom,C);
B.insertNewCellAt(E,A);
this.rdom.placeCaretAtStartOf(D);
return true
},handleDeleteRow:function(){var E=this.rdom.getCurrentBlockElement();
var C=this.rdom.getParentElementOf(E,["TR"]);
if(!C){return true
}var B=this.rdom.getParentElementOf(C,["TABLE"]);
var A=new xq.RichTable(this.rdom,B);
var D=A.deleteRow(C);
this.rdom.placeCaretAtStartOf(D);
return true
},handleDeleteColumn:function(){var C=this.rdom.getCurrentBlockElement();
var D=this.rdom.getParentElementOf(C,["TD"],true);
if(!D){return true
}var B=this.rdom.getParentElementOf(D,["TABLE"]);
var A=new xq.RichTable(this.rdom,B);
A.deleteCell(D);
return true
},handleIndent:function(){if(this.rdom.hasSelection()){var C=this.rdom.getBlockElementsAtSelectionEdge(true,true);
if(C.first()!=C.last()){var D=this.rdom.indentElements(C.first(),C.last());
this.rdom.selectBlocksBetween(D.first(),D.last());
var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
}}var A=this.rdom.getCurrentBlockElement();
var D=this.rdom.indentElement(A);
if(D){this.rdom.placeCaretAtStartOf(D);
var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this)
}return true
},handleOutdent:function(){if(this.rdom.hasSelection()){var C=this.rdom.getBlockElementsAtSelectionEdge(true,true);
if(C.first()!=C.last()){var D=this.rdom.outdentElements(C.first(),C.last());
this.rdom.selectBlocksBetween(D.first(),D.last());
var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
}}var A=this.rdom.getCurrentBlockElement();
var D=this.rdom.outdentElement(A);
if(D){this.rdom.placeCaretAtStartOf(D);
var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this)
}return true
},handleList:function(A){if(this.rdom.hasSelection()){var D=this.rdom.getBlockElementsAtSelectionEdge(true,true);
if(D.first()!=D.last()){D=this.rdom.applyLists(D.first(),D.last(),A)
}else{D[0]=D[1]=this.rdom.applyList(D.first(),A)
}this.rdom.selectBlocksBetween(D.first(),D.last())
}else{var C=this.rdom.applyList(this.rdom.getCurrentBlockElement(),A);
this.rdom.placeCaretAtStartOf(C)
}var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleJustify:function(A){var D=this.rdom.getCurrentBlockElement();
var A=(A=="left"||A=="both")&&(D.style.textAlign=="left"||D.style.textAlign=="")?"both":A;
if(this.rdom.hasSelection()){var C=this.rdom.getSelectedBlockElements();
this.rdom.justifyBlocks(C,A);
this.rdom.selectBlocksBetween(C.first(),C.last())
}else{this.rdom.justifyBlock(D,A)
}var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleRemoveBlock:function(){var B=this.rdom.getCurrentBlockElement();
var A=this.rdom.removeBlock(B);
this.rdom.placeCaretAtStartOf(A);
A.scrollIntoView(false)
},handleBackgroundColor:function(A){if(A){this.rdom.applyBackgroundColor(A);
var D=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this)
}else{var B=new xq.controls.FormDialog(this,xq.ui_templates.basicColorPickerDialog,function(E){},function(F){this.focus();
if(xq.Browser.isTrident){var E=this.rdom.rng();
E.moveToBookmark(C);
E.select()
}if(!F){return 
}this.handleBackgroundColor(F.color)
}.bind(this));
if(xq.Browser.isTrident){var C=this.rdom.rng().getBookmark()
}B.show({position:"centerOfEditor"})
}return true
},handleForegroundColor:function(A){if(A){this.rdom.applyForegroundColor(A);
var D=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this)
}else{var B=new xq.controls.FormDialog(this,xq.ui_templates.basicColorPickerDialog,function(E){},function(F){this.focus();
if(xq.Browser.isTrident){var E=this.rdom.rng();
E.moveToBookmark(C);
E.select()
}if(!F){return 
}this.handleForegroundColor(F.color)
}.bind(this));
if(xq.Browser.isTrident){var C=this.rdom.rng().getBookmark()
}B.show({position:"centerOfEditor"})
}return true
},handleSuperscription:function(){this.rdom.applySuperscription();
var A=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleSubscription:function(){this.rdom.applySubscription();
var A=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleApplyBlock:function(A){if(this.rdom.hasSelection()){var E=this.rdom.getBlockElementsAtSelectionEdge(true,true);
if(E.first()!=E.last()){var B=this.rdom.applyTagIntoElements(A,E.first(),E.last());
this.rdom.selectBlocksBetween(B.first(),B.last());
var D=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
}}var C=this.rdom.getCurrentBlockElement();
this.rdom.pushMarker();
var B=this.rdom.applyTagIntoElement(A,C)||C;
this.rdom.popMarker(true);
if(this.rdom.isEmptyBlock(B)){this.rdom.correctEmptyElement(B);
this.rdom.placeCaretAtStartOf(B)
}var D=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleSeparator:function(){this.rdom.collapseSelection();
var C=this.rdom.getCurrentBlockElement();
var A=this.rdom.isCaretAtBlockStart();
if(this.rdom.tree.isBlockContainer(C)){C=this.rdom.wrapAllInlineOrTextNodesAs("P",C,true)[0]
}this.rdom.insertNodeAt(this.rdom.createElement("HR"),C,A?"before":"after");
this.rdom.placeCaretAtStartOf(C);
var B=this.editHistory.onCommand();
this._fireOnCurrentContentChanged(this);
return true
},handleUndo:function(){var A=this.editHistory.undo();
this._fireOnCurrentContentChanged(this);
var B=this.rdom.getCurrentBlockElement();
if(!xq.Browser.isTrident&&B){B.scrollIntoView(false)
}return true
},handleRedo:function(){var A=this.editHistory.redo();
this._fireOnCurrentContentChanged(this);
var B=this.rdom.getCurrentBlockElement();
if(!xq.Browser.isTrident&&B){B.scrollIntoView(false)
}return true
},_handleContextMenu:function(D){if(xq.Browser.isWebkit){if(D.metaKey||xq.isLeftClick(D)){return false
}}else{if(D.shiftKey||D.ctrlKey||D.altKey){return false
}}var J=xq.getEventPoint(D);
var H=J.x;
var F=J.y;
var G=xq.getCumulativeOffset(this.getFrame());
H+=G.left;
F+=G.top;
this._contextMenuTargetElement=D.target||D.srcElement;
if(!H||!F||xq.Browser.isTrident){var G=xq.getCumulativeOffset(this._contextMenuTargetElement);
var B=xq.getCumulativeOffset(this.getFrame());
H=G.left+B.left-this.getDoc().documentElement.scrollLeft;
F=G.top+B.top-this.getDoc().documentElement.scrollTop
}if(!xq.Browser.isTrident){var I=this.getDoc();
var C=this.getBody();
H-=I.documentElement.scrollLeft;
F-=I.documentElement.scrollTop;
if(I!=C){H-=C.scrollLeft;
F-=C.scrollTop
}}for(var A in this.config.contextMenuHandlers){var E=this.config.contextMenuHandlers[A].handler(this,this._contextMenuTargetElement,H,F);
if(E){xq.stopEvent(D);
return true
}}return false
},showContextMenu:function(C,A,D){if(!C||C.length<=0){return 
}if(!this._contextMenuContainer){this._contextMenuContainer=this.doc.createElement("UL");
this._contextMenuContainer.className="xqContextMenu";
this._contextMenuContainer.style.display="none";
xq.observe(this.doc,"click",this._contextMenuClicked.bindAsEventListener(this));
xq.observe(this.rdom.getDoc(),"click",this.hideContextMenu.bindAsEventListener(this));
this.body.appendChild(this._contextMenuContainer)
}else{while(this._contextMenuContainer.childNodes.length>0){this._contextMenuContainer.removeChild(this._contextMenuContainer.childNodes[0])
}}for(var B=0;
B<C.length;
B++){C[B]._node=this._addContextMenuItem(C[B])
}this._contextMenuContainer.style.display="block";
this._contextMenuContainer.style.left=Math.min(Math.max(this.doc.body.scrollWidth,this.doc.documentElement.clientWidth)-this._contextMenuContainer.offsetWidth,A)+"px";
this._contextMenuContainer.style.top=Math.min(Math.max(this.doc.body.scrollHeight,this.doc.documentElement.clientHeight)-this._contextMenuContainer.offsetHeight,D)+"px";
this._contextMenuItems=C
},hideContextMenu:function(){if(this._contextMenuContainer){this._contextMenuContainer.style.display="none"
}},_addContextMenuItem:function(B){if(!this._contextMenuContainer){throw"No conext menu container exists"
}var A=this.doc.createElement("LI");
if(B.disabled){A.className+=" disabled"
}if(B.title=="----"){A.innerHTML="&nbsp;";
A.className="separator"
}else{if(B.handler){A.innerHTML="<a href=\"javascript:;\" onclick=\"return false;\">"+(B.title.toString().escapeHTML())+"</a>"
}else{A.innerHTML=(B.title.toString().escapeHTML())
}}if(B.className){A.className=B.className
}this._contextMenuContainer.appendChild(A);
return A
},_contextMenuClicked:function(e){this.hideContextMenu();
if(!this._contextMenuContainer){return 
}var node=e.srcElement||e.target;
while(node&&node.nodeName!="LI"){node=node.parentNode
}if(!node||!this.rdom.tree.isDescendantOf(this._contextMenuContainer,node)){return 
}for(var i=0;
i<this._contextMenuItems.length;
i++){if(this._contextMenuItems[i]._node==node){var handler=this._contextMenuItems[i].handler;
if(!this._contextMenuItems[i].disabled&&handler){var xed=this;
var element=this._contextMenuTargetElement;
if(typeof handler=="function"){handler(xed,element)
}else{eval(handler)
}}break
}}},insertTemplate:function(A){return this.rdom.insertHtml(this._processTemplate(A))
},insertTemplateAt:function(B,C,A){return this.rdom.insertHtmlAt(this._processTemplate(B),C,A)
},_processTemplate:function(B){var D=this.getTemplateProcessors();
for(var A in D){var C=D[A];
B=C.handler(B)
}return B=this.removeUnnecessarySpaces(B)
},_handleEnterAtEmptyBlock:function(){var A=this.rdom.getCurrentBlockElement();
if(this.rdom.tree.isTableCell(A)&&this.rdom.isFirstBlockOfBody(A)){A=this.rdom.insertNodeAt(this.rdom.makeEmptyParagraph(),this.rdom.getRoot(),"start")
}else{A=this.rdom.outdentElement(A)||this.rdom.extractOutElementFromParent(A)||this.rdom.replaceTag("P",A)||this.rdom.insertNewBlockAround(A)
}this.rdom.placeCaretAtStartOf(A);
if(!xq.Browser.isTrident){A.scrollIntoView(false)
}},_handleEnterAtEdge:function(B,A){var D=this.rdom.getCurrentBlockElement();
var C;
if(B&&this.rdom.isFirstBlockOfBody(D)){C=this.rdom.insertNodeAt(this.rdom.makeEmptyParagraph(),this.rdom.getRoot(),"start")
}else{if(this.rdom.tree.isTableCell(D)){A=true
}var E=this.rdom.insertNewBlockAround(D,B,A?"P":null);
C=!B?E:E.nextSibling
}this.rdom.placeCaretAtStartOf(C);
if(!xq.Browser.isTrident){C.scrollIntoView(false)
}}});
xq.Browser={isTrident:navigator.appName=="Microsoft Internet Explorer",isWebkit:navigator.userAgent.indexOf("AppleWebKit/")>-1,isGecko:navigator.userAgent.indexOf("Gecko")>-1&&navigator.userAgent.indexOf("KHTML")==-1,isKHTML:navigator.userAgent.indexOf("KHTML")!=-1,isPresto:navigator.appName=="Opera",isMac:navigator.userAgent.indexOf("Macintosh")!=-1,isUbuntu:navigator.userAgent.indexOf("Ubuntu")!=-1,isIE:navigator.appName=="Microsoft Internet Explorer",isIE6:navigator.userAgent.indexOf("MSIE 6")!=-1,isIE7:navigator.userAgent.indexOf("MSIE 7")!=-1};
xq.Shortcut=xq.Class({initialize:function(A){xq.addToFinalizeQueue(this);
this.keymap=(typeof A=="string")?xq.Shortcut.interprete(A).keymap:A
},matches:function(A){var B=xq.Browser.isGecko&&xq.Browser.isMac?(A.keyCode+"_"+A.charCode):A.keyCode;
var D=(this.keymap.which==B)||(this.keymap.which==32&&B==25);
if(typeof A.metaKey=="undefined"){A.metaKey=false
}var C=(typeof this.keymap.shiftKey=="undefined"||this.keymap.shiftKey==A.shiftKey)&&(typeof this.keymap.altKey=="undefined"||this.keymap.altKey==A.altKey)&&(typeof this.keymap.ctrlKey=="undefined"||this.keymap.ctrlKey==A.ctrlKey)&&(typeof this.keymap.metaKey=="undefined"||this.keymap.metaKey==A.metaKey);
return C&&D
}});
xq.Shortcut.interprete=function(G){G=G.toUpperCase();
var F=xq.Shortcut._interpreteWhich(G.split("+").pop());
var E=xq.Shortcut._interpreteModifier(G,"CTRL");
var C=xq.Shortcut._interpreteModifier(G,"ALT");
var B=xq.Shortcut._interpreteModifier(G,"SHIFT");
var D=xq.Shortcut._interpreteModifier(G,"META");
var A={};
A.which=F;
if(typeof E!="undefined"){A.ctrlKey=E
}if(typeof C!="undefined"){A.altKey=C
}if(typeof B!="undefined"){A.shiftKey=B
}if(typeof D!="undefined"){A.metaKey=D
}return new xq.Shortcut(A)
};
xq.Shortcut._interpreteModifier=function(A,B){return A.match("\\("+B+"\\)")?undefined:A.match(B)?true:false
};
xq.Shortcut._interpreteWhich=function(A){var B=A.length==1?((xq.Browser.isMac&&xq.Browser.isGecko)?"0_"+A.toLowerCase().charCodeAt(0):A.charCodeAt(0)):xq.Shortcut._keyNames[A];
if(typeof B=="undefined"){throw"Unknown special key name: ["+A+"]"
}return B
};
xq.Shortcut._keyNames=xq.Browser.isMac&&xq.Browser.isGecko?{BACKSPACE:"8_0",TAB:"9_0",RETURN:"13_0",ENTER:"13_0",ESC:"27_0",SPACE:"0_32",LEFT:"37_0",UP:"38_0",RIGHT:"39_0",DOWN:"40_0",DELETE:"46_0",HOME:"36_0",END:"35_0",PAGEUP:"33_0",PAGEDOWN:"34_0",COMMA:"0_44",HYPHEN:"0_45",EQUAL:"0_61",PERIOD:"0_46",SLASH:"0_47",F1:"112_0",F2:"113_0",F3:"114_0",F4:"115_0",F5:"116_0",F6:"117_0",F7:"118_0",F8:"119_0"}:{BACKSPACE:8,TAB:9,RETURN:13,ENTER:13,ESC:27,SPACE:32,LEFT:37,UP:38,RIGHT:39,DOWN:40,DELETE:46,HOME:36,END:35,PAGEUP:33,PAGEDOWN:34,COMMA:188,HYPHEN:xq.Browser.isTrident?189:109,EQUAL:xq.Browser.isTrident?187:61,PERIOD:190,SLASH:191,F1:112,F2:113,F3:114,F4:115,F5:116,F6:117,F7:118,F8:119,F9:120,F10:121,F11:122,F12:123};
xq.DomTree=xq.Class({initialize:function(){xq.addToFinalizeQueue(this);
this._blockTags=["DIV","DD","LI","ADDRESS","CAPTION","DT","H1","H2","H3","H4","H5","H6","HR","P","BODY","BLOCKQUOTE","PRE","PARAM","DL","OL","UL","TABLE","THEAD","TBODY","TR","TH","TD"];
this._blockContainerTags=["DIV","DD","LI","BODY","BLOCKQUOTE","UL","OL","DL","TABLE","THEAD","TBODY","TR","TH","TD"];
this._listContainerTags=["OL","UL","DL"];
this._tableCellTags=["TH","TD"];
this._blockOnlyContainerTags=["BODY","BLOCKQUOTE","UL","OL","DL","TABLE","THEAD","TBODY","TR"];
this._atomicTags=["IMG","OBJECT","BR","HR"]
},getBlockTags:function(){return this._blockTags
},findCommonAncestorAndImmediateChildrenOf:function(E,C){if(E.parentNode==C.parentNode){return{left:E,right:C,parent:E.parentNode}
}else{var D=this.collectParentsOf(E,true);
var G=this.collectParentsOf(C,true);
var B=this.getCommonAncestor(D,G);
var F=D.find(function(H){return H.parentNode==B
});
var A=G.find(function(H){return H.parentNode==B
});
return{left:F,right:A,parent:B}
}},getLeavesAtEdge:function(C){if(!C.hasChildNodes()){return[null,null]
}var D=function(G){for(var F=0;
F<G.childNodes.length;
F++){if(G.childNodes[F].nodeType==1&&this.isBlock(G.childNodes[F])){return D(G.childNodes[F])
}}return G
}.bind(this);
var B=function(G){for(var F=G.childNodes.length;
F--;
){if(G.childNodes[F].nodeType==1&&this.isBlock(G.childNodes[F])){return B(G.childNodes[F])
}}return G
}.bind(this);
var E=D(C);
var A=B(C);
return[E==C?null:E,A==C?null:A]
},getCommonAncestor:function(B,A){for(var D=0;
D<B.length;
D++){for(var C=0;
C<A.length;
C++){if(B[D]==A[C]){return B[D]
}}}},collectParentsOf:function(D,C,A){var B=[];
if(C){B.push(D)
}while((D=D.parentNode)&&(D.nodeName!="HTML")&&!(typeof A=="function"&&A(D))){B.push(D)
}return B
},isDescendantOf:function(B,C){if(B.length>0){for(var A=0;
A<B.length;
A++){if(this.isDescendantOf(B[A],C)){return true
}}return false
}if(B==C){return false
}while(C=C.parentNode){if(C==B){return true
}}return false
},walkForward:function(A){if(A.hasChildNodes()){return A.firstChild
}if(A.nextSibling){return A.nextSibling
}while(A=A.parentNode){if(A.nextSibling){return A.nextSibling
}}return null
},walkBackward:function(A){if(A.previousSibling){A=A.previousSibling;
while(A.hasChildNodes()){A=A.lastChild
}return A
}return A.parentNode
},walkNext:function(A){return A.nextSibling
},walkPrev:function(A){return A.previousSibling
},checkTargetForward:function(B,A){return this._check(B,this.walkForward,A)
},checkTargetBackward:function(B,A){return this._check(B,this.walkBackward,A)
},findForward:function(C,B,A){return this._find(C,this.walkForward,B,A)
},findBackward:function(C,B,A){return this._find(C,this.walkBackward,B,A)
},_check:function(C,B,A){if(C==A){return false
}while(C=B(C)){if(C==A){return true
}}return false
},_find:function(D,B,C,A){while(D=B(D)){if(A&&A(D)){return null
}if(C(D)){return D
}}return null
},collectNodesBetween:function(D,A,C){if(D==A){return[D,A].findAll(C||function(){return true
})
}var B=this.collectForward(D,function(E){return E==A
},C);
if(D!=A&&typeof C=="function"&&C(A)){B.push(A)
}return B
},collectForward:function(C,A,B){return this.collect(C,this.walkForward,A,B)
},collectBackward:function(C,A,B){return this.collect(C,this.walkBackward,A,B)
},collectNext:function(C,A,B){return this.collect(C,this.walkNext,A,B)
},collectPrev:function(C,A,B){return this.collect(C,this.walkPrev,A,B)
},collect:function(E,D,A,C){var B=[E];
while(true){E=D(E);
if((E==null)||(typeof A=="function"&&A(E))){break
}B.push(E)
}return(typeof C=="function")?B.findAll(C):B
},hasBlocks:function(C){var A=C.childNodes;
for(var B=0;
B<A.length;
B++){if(this.isBlock(A[B])){return true
}}return false
},hasMixedContents:function(C){if(!this.isBlock(C)){return false
}if(!this.isBlockContainer(C)){return false
}var A=false;
var E=false;
for(var B=0;
B<C.childNodes.length;
B++){var D=C.childNodes[B];
if(!A&&this.isTextOrInlineNode(D)){A=true
}if(!E&&this.isBlock(D)){E=true
}if(A&&E){break
}}if(!A||!E){return false
}return true
},isBlockOnlyContainer:function(A){if(!A){return false
}return this._blockOnlyContainerTags.indexOf(typeof A=="string"?A:A.nodeName)!=-1
},isTableCell:function(A){if(!A){return false
}return this._tableCellTags.indexOf(typeof A=="string"?A:A.nodeName)!=-1
},isBlockContainer:function(A){if(!A){return false
}return this._blockContainerTags.indexOf(typeof A=="string"?A:A.nodeName)!=-1
},isHeading:function(A){if(!A){return false
}return(typeof A=="string"?A:A.nodeName).match(/H\d/)
},isBlock:function(A){if(!A){return false
}return this._blockTags.indexOf(typeof A=="string"?A:A.nodeName)!=-1
},isAtomic:function(A){if(!A){return false
}return this._atomicTags.indexOf(typeof A=="string"?A:A.nodeName)!=-1
},isListContainer:function(A){if(!A){return false
}return this._listContainerTags.indexOf(typeof A=="string"?A:A.nodeName)!=-1
},isTextOrInlineNode:function(A){return A&&(A.nodeType==3||!this.isBlock(A))
}});
xq.RichDom=xq.Class({initialize:function(){xq.addToFinalizeQueue(this);
this.tree=new xq.DomTree();
this._lastMarkerId=0
},setWin:function(A){if(!A){throw"[win] is null"
}this.win=A
},setRoot:function(A){if(!A){throw"[root] is null"
}if(this.win&&(A.ownerDocument!=this.win.document)){throw"root.ownerDocument != this.win.document"
}this.root=A;
this.doc=this.root.ownerDocument
},getWin:function(){return this.win
},getDoc:function(){return this.doc
},getRoot:function(){return this.root
},clearRoot:function(){this.root.innerHTML="";
this.root.appendChild(this.makeEmptyParagraph())
},removePlaceHoldersAndEmptyNodes:function(D){var C=D.childNodes;
if(!C){return 
}var A=this.getBottommostLastChild(D);
if(!A){return 
}A=this.tree.walkForward(A);
while(true){if(!D||D==A){break
}if(this.isPlaceHolder(D)||(D.nodeType==3&&D.nodeValue=="")||(!this.getNextSibling(D)&&D.nodeType==3&&D.nodeValue.strip()=="")){var B=D;
D=this.tree.walkForward(D);
this.deleteNode(B)
}else{D=this.tree.walkForward(D)
}}},setAttributes:function(B,C){for(var A in C){B.setAttribute(A,C[A])
}},createTextNode:function(A){return this.doc.createTextNode(A)
},createElement:function(A){return this.doc.createElement(A)
},createElementFromHtml:function(A){var B=this.createElement("div");
B.innerHTML=A;
if(B.childNodes.length!=1){throw"Illegal HTML fragment"
}return this.getFirstChild(B)
},deleteNode:function(D,A,C){if(!D||!D.parentNode){return 
}var B=D.parentNode;
B.removeChild(D);
if(A){while(!B.hasChildNodes()){D=B;
B=D.parentNode;
if(!B||this.getRoot()==D){break
}B.removeChild(D)
}}if(C&&this.isEmptyBlock(B)){B.innerHTML="";
this.correctEmptyElement(B)
}},insertNode:function(A){throw"Not implemented"
},insertHtml:function(A){return this.insertNode(this.createElementFromHtml(A))
},insertText:function(A){this.insertNode(this.createTextNode(A))
},insertNodeAt:function(B,F,E,D){if(["HTML","HEAD"].indexOf(F.nodeName)!=-1||"BODY"==F.nodeName&&["before","after"].indexOf(E)!=-1){throw"Illegal argument. Cannot move node["+B.nodeName+"] to '"+E+"' of target["+F.nodeName+"]"
}var C;
var I;
var G;
switch(E.toLowerCase()){case"before":C=F.parentNode;
I="insertBefore";
G=F;
break;
case"start":if(F.firstChild){C=F;
I="insertBefore";
G=F.firstChild
}else{C=F;
I="appendChild"
}break;
case"end":C=F;
I="appendChild";
break;
case"after":if(F.nextSibling){C=F.parentNode;
I="insertBefore";
G=F.nextSibling
}else{C=F.parentNode;
I="appendChild"
}break
}if(D&&this.tree.isListContainer(C)&&B.nodeName!="LI"){var H=this.createElement("LI");
H.appendChild(B);
B=H;
C[I](B,G)
}else{if(D&&!this.tree.isListContainer(C)&&B.nodeName=="LI"){this.wrapAllInlineOrTextNodesAs("P",B,true);
var A=this.createElement("DIV");
this.moveChildNodes(B,A);
this.deleteNode(B);
C[I](A,G);
B=this.unwrapElement(A,true)
}else{C[I](B,G)
}}return B
},insertTextAt:function(C,B,A){return this.insertNodeAt(this.createTextNode(C),B,A)
},insertHtmlAt:function(B,C,A){return this.insertNodeAt(this.createElementFromHtml(B),C,A)
},replaceTag:function(A,B){if(B.nodeName==A){return null
}if(this.tree.isTableCell(B)){return null
}var C=this.createElement(A);
this.moveChildNodes(B,C);
this.copyAttributes(B,C,true);
B.parentNode.replaceChild(C,B);
if(!C.hasChildNodes()){this.correctEmptyElement(C)
}return C
},unwrapUnnecessaryParagraph:function(A){if(!A){return false
}if(!this.tree.isBlockOnlyContainer(A)&&A.childNodes.length==1&&A.firstChild.nodeName=="P"&&!this.hasImportantAttributes(A.firstChild)){var B=A.firstChild;
this.moveChildNodes(B,A);
this.deleteNode(B);
return true
}return false
},unwrapElement:function(B,A){if(A){this.wrapAllInlineOrTextNodesAs("P",B)
}var C=B.firstChild;
while(B.firstChild){this.insertNodeAt(B.firstChild,B,"before")
}this.deleteNode(B);
return C
},wrapElement:function(A,B){var C=this.insertNodeAt(this.createElement(A),B,"before");
C.appendChild(B);
return C
},testSmartWrap:function(A,B){return this.smartWrap(A,null,B,true)
},smartWrap:function(G,S,F,R){var H=this.getParentBlockElementOf(G);
S=S||"SPAN";
F=F||function(T){return -1
};
if(!R&&(!G.previousSibling||this.isEmptyBlock(H))){var E=this.insertNodeAt(this.createElement(S),G,"before");
return E
}var B=this.tree.collectForward(H,function(T){return T==G
},function(T){return T.nodeType==3
});
var M=0;
var Q=[];
for(var L=0;
L<B.length;
L++){Q.push(B[L].nodeValue)
}var P=Q.join("");
var N=F(P);
var C=N;
if(C==-1){C=0
}else{P=P.substring(C)
}for(var L=0;
L<B.length;
L++){if(C>Q[L].length){C-=Q[L].length
}else{M=L;
break
}}if(R){return{text:P,textIndex:N,nodeIndex:M,breakPoint:C}
}if(C!=0){var I=B[M].splitText(C);
M++;
B.splice(M,0,I)
}var A=B[M]||H.firstChild;
var O=this.tree.findCommonAncestorAndImmediateChildrenOf(A,G);
var K=O.parent;
if(K){if(A.parentNode!=K){A=this.splitElementUpto(A,K,true)
}if(G.parentNode!=K){G=this.splitElementUpto(G,K,true)
}var D=A.previousSibling;
var J=G.nextSibling;
if(D&&D.nodeType==1&&this.isEmptyBlock(D)){this.deleteNode(D)
}if(J&&J.nodeType==1&&this.isEmptyBlock(J)){this.deleteNode(J)
}var E=this.insertNodeAt(this.createElement(S),A,"before");
while(E.nextSibling!=G){E.appendChild(E.nextSibling)
}return E
}else{var E=this.insertNodeAt(this.createElement(S),G,"before");
return E
}},wrapAllInlineOrTextNodesAs:function(A,B,E){var D=[];
if(!E&&!this.tree.hasMixedContents(B)){return D
}var C=B.firstChild;
while(C){if(this.tree.isTextOrInlineNode(C)){var F=this.wrapInlineOrTextNodesAs(A,C);
D.push(F);
C=F.nextSibling
}else{C=C.nextSibling
}}return D
},wrapInlineOrTextNodesAs:function(A,B){var D=this.createElement(A);
var C=B;
C.parentNode.replaceChild(D,C);
D.appendChild(C);
while(D.nextSibling&&this.tree.isTextOrInlineNode(D.nextSibling)){D.appendChild(D.nextSibling)
}return D
},turnElementIntoListItem:function(C,D){D=D.toUpperCase();
var B=this.createElement(D=="UL"?"UL":"OL");
if(D=="CODE"){B.className="code"
}if(this.tree.isTableCell(C)){var E=this.wrapAllInlineOrTextNodesAs("P",C,true)[0];
B=this.insertNodeAt(B,C,"start");
var A=this.insertNodeAt(this.createElement("LI"),B,"start");
A.appendChild(E)
}else{B=this.insertNodeAt(B,C,"after");
var A=this.insertNodeAt(this.createElement("LI"),B,"start");
A.appendChild(C)
}this.unwrapUnnecessaryParagraph(A);
this.mergeAdjustLists(B);
return A
},extractOutElementFromParent:function(B){if(B==this.root||this.root==B.parentNode||!B.offsetParent){return null
}if(B.nodeName=="LI"){this.wrapAllInlineOrTextNodesAs("P",B,true);
B=B.firstChild
}var A=B.parentNode;
var D=null;
if(A.nodeName=="LI"&&A.parentNode.parentNode.nodeName=="LI"){if(B.previousSibling){this.splitContainerOf(B,true);
this.correctEmptyElement(B)
}this.outdentListItem(B);
D=B
}else{if(A.nodeName=="LI"){if(this.tree.isListContainer(B.nextSibling)){var E=A.parentNode;
this.splitContainerOf(A,true);
this.correctEmptyElement(B);
D=A.firstChild;
while(A.firstChild){this.insertNodeAt(A.firstChild,E,"before")
}var C=E.previousSibling;
this.deleteNode(E);
if(C&&this.tree.isListContainer(C)){this.mergeAdjustLists(C)
}}else{this.splitContainerOf(B,true);
this.correctEmptyElement(B);
var E=this.splitContainerOf(A);
this.insertNodeAt(B,E.parentNode,"before");
this.deleteNode(E.parentNode);
D=B
}}else{if(this.tree.isTableCell(A)||this.tree.isTableCell(B)){}else{this.splitContainerOf(B,true);
this.correctEmptyElement(B);
D=this.insertNodeAt(B,A,"before");
this.deleteNode(A)
}}}return D
},insertNewBlockAround:function(E,D,B){var C=E.nodeName=="LI"||E.parentNode.nodeName=="LI";
this.removeTrailingWhitespace(E);
if(this.isFirstLiWithNestedList(E)&&!B&&D){var A=this.getParentElementOf(E,["LI"]);
var F=this._insertNewBlockAround(A,D);
return F
}else{if(C&&!B){var A=this.getParentElementOf(E,["LI"]);
var F=this._insertNewBlockAround(E,D);
if(A!=E){F=this.splitContainerOf(F,false,"prev")
}return F
}else{if(this.tree.isBlockContainer(E)){this.wrapAllInlineOrTextNodesAs("P",E,true);
return this._insertNewBlockAround(E.firstChild,D,B)
}else{return this._insertNewBlockAround(E,D,this.tree.isHeading(E)?"P":B)
}}}},_insertNewBlockAround:function(B,C,A){var D=this.createElement(A||B.nodeName);
this.copyAttributes(B,D,false);
this.correctEmptyElement(D);
D=this.insertNodeAt(D,B,C?"before":"after");
return D
},applyTagIntoElement:function(A,B){if(this.tree.isBlockOnlyContainer(A)){return this.wrapBlock(A,B)
}else{if(this.tree.isBlockContainer(B)){var C=this.createElement(A);
this.moveChildNodes(B,C);
return this.insertNodeAt(C,B,"start")
}else{if(this.tree.isBlockContainer(A)&&this.hasImportantAttributes(B)){return this.wrapBlock(A,B)
}else{return this.replaceTag(A,B)
}}}throw"IllegalArgumentException - ["+A+", "+B+"]"
},applyTagIntoElements:function(C,K,L){var F=[];
if(this.tree.isBlockContainer(C)){var H=this.tree.findCommonAncestorAndImmediateChildrenOf(K,L);
var D=H.left;
var B=this.insertNodeAt(this.createElement(C),D,"before");
var M=H.parent.nodeName=="LI"&&H.parent.parentNode.childNodes.length==1&&!H.left.previousSilbing&&!H.right.nextSibling;
if(M){var I=D.parentNode.parentNode;
this.insertNodeAt(B,I,"before");
B.appendChild(I)
}else{while(D!=H.right){next=D.nextSibling;
B.appendChild(D);
D=next
}B.appendChild(H.right)
}F.push(B)
}else{var A=this.getBlockElementsBetween(K,L);
for(var G=0;
G<A.length;
G++){if(this.tree.isBlockContainer(A[G])){var J=this.wrapAllInlineOrTextNodesAs(C,A[G],true);
for(var E=0;
E<J.length;
E++){F.push(J[E])
}}else{F.push(this.replaceTag(C,A[G]))
}}}return F
},moveBlock:function(H,A){H=this.getParentElementOf(H,["TR"])||H;
while(H.nodeName!="TR"&&H.parentNode!=this.getRoot()&&!H.previousSibling&&!H.nextSibling&&!this.tree.isListContainer(H.parentNode)){H=H.parentNode
}var G,B;
if(A){G=H.previousSibling;
if(G){var F=G.nodeName=="LI"&&((G.childNodes.length==1&&this.tree.isBlock(G.firstChild))||!this.tree.hasBlocks(G));
var E=["TABLE","TR"].indexOf(G.nodeName)!=-1;
B=this.tree.isBlockContainer(G)&&!F&&!E?"end":"before"
}else{if(H.parentNode!=this.getRoot()){G=H.parentNode;
B="before"
}}}else{G=H.nextSibling;
if(G){var F=G.nodeName=="LI"&&((G.childNodes.length==1&&this.tree.isBlock(G.firstChild))||!this.tree.hasBlocks(G));
var E=["TABLE","TR"].indexOf(G.nodeName)!=-1;
B=this.tree.isBlockContainer(G)&&!F&&!E?"start":"after"
}else{if(H.parentNode!=this.getRoot()){G=H.parentNode;
B="after"
}}}if(!G){return null
}if(["TBODY","THEAD"].indexOf(G.nodeName)!=-1){return null
}this.wrapAllInlineOrTextNodesAs("P",G,true);
if(this.isFirstLiWithNestedList(H)){this.insertNewBlockAround(H,false,"P")
}var D=H.parentNode;
var C=this.insertNodeAt(H,G,B,true);
if(!D.hasChildNodes()){this.deleteNode(D,true)
}this.unwrapUnnecessaryParagraph(C);
this.unwrapUnnecessaryParagraph(G);
if(A){if(C.previousSibling&&this.isEmptyBlock(C.previousSibling)&&!C.previousSibling.previousSibling&&C.parentNode.nodeName=="LI"&&this.tree.isListContainer(C.nextSibling)){this.deleteNode(C.previousSibling)
}}else{if(C.nextSibling&&this.isEmptyBlock(C.nextSibling)&&!C.previousSibling&&C.parentNode.nodeName=="LI"&&this.tree.isListContainer(C.nextSibling.nextSibling)){this.deleteNode(C.nextSibling)
}}this.correctEmptyElement(C);
return C
},removeBlock:function(E){var D;
while(E.parentNode!=this.getRoot()&&!E.previousSibling&&!E.nextSibling&&!this.tree.isListContainer(E.parentNode)){E=E.parentNode
}var C=function(F){return this.tree.isBlock(F)&&!this.tree.isAtomic(F)&&!this.tree.isDescendantOf(E,F)&&!this.tree.hasBlocks(F)
}.bind(this);
var A=function(F){return this.tree.isBlock(F)&&!this.tree.isDescendantOf(this.getRoot(),F)
}.bind(this);
if(this.isFirstLiWithNestedList(E)){D=this.outdentListItem(E.nextSibling.firstChild);
this.deleteNode(D.previousSibling,true)
}else{if(this.tree.isTableCell(E)){var B=new xq.RichTable(this,this.getParentElementOf(E,["TABLE"]));
D=B.getBelowCellOf(E);
if(E.parentNode.parentNode.nodeName=="TBODY"&&B.hasHeadingAtTop()&&B.getDom().tBodies[0].rows.length==1){return D
}D=D||this.tree.findForward(E,C,A)||this.tree.findBackward(E,C,A);
this.deleteNode(E.parentNode,true)
}else{D=D||this.tree.findForward(E,C,A)||this.tree.findBackward(E,C,A);
if(!D){D=this.insertNodeAt(this.makeEmptyParagraph(),E,"after")
}this.deleteNode(E,true)
}}if(!this.getRoot().hasChildNodes()){D=this.createElement("P");
this.getRoot().appendChild(D);
this.correctEmptyElement(D)
}return D
},removeTrailingWhitespace:function(A){throw"Not implemented"
},changeListTypeTo:function(C,D){D=D.toUpperCase();
var A=this.getParentElementOf(C,["LI"]);
if(!A){throw"IllegalArgumentException"
}var B=A.parentNode;
this.splitContainerOf(A);
var E=this.insertNodeAt(this.createElement(D=="UL"?"UL":"OL"),B,"before");
if(D=="CODE"){E.className="code"
}this.insertNodeAt(A,E,"start");
this.deleteNode(B);
this.mergeAdjustLists(E);
return C
},splitContainerOf:function(C,F,B){if([C,C.parentNode].indexOf(this.getRoot())!=-1){return C
}var A=C.parentNode;
if(C.previousSibling&&(!B||B.toLowerCase()=="prev")){var E=this.createElement(A.nodeName);
this.copyAttributes(A,E);
while(A.firstChild!=C){E.appendChild(A.firstChild)
}this.insertNodeAt(E,A,"before");
this.unwrapUnnecessaryParagraph(E)
}if(C.nextSibling&&(!B||B.toLowerCase()=="next")){var D=this.createElement(A.nodeName);
this.copyAttributes(A,D);
while(A.lastChild!=C){this.insertNodeAt(A.lastChild,D,"start")
}this.insertNodeAt(D,A,"after");
this.unwrapUnnecessaryParagraph(D)
}if(!F){C=this.unwrapUnnecessaryParagraph(A)?A:C
}return C
},splitParentElement:function(A){var C=A.parentNode;
if(["HTML","HEAD","BODY"].indexOf(C.nodeName)!=-1){throw"Illegal argument. Cannot seperate element["+C.nodeName+"]"
}var D=A.previousSibling;
var E=A.nextSibling;
var F=this.insertNodeAt(this.createElement(C.nodeName),C,"after");
var B;
while(B=A.nextSibling){F.appendChild(B)
}this.insertNodeAt(A,F,"start");
this.copyAttributes(C,F);
return F
},splitElementUpto:function(B,A,C){while(B.previousSibling!=A){if(C&&B.parentNode==A){break
}B=this.splitParentElement(B)
}return B
},mergeElement:function(E,J,I){this.wrapAllInlineOrTextNodesAs("P",E.parentNode,true);
if(J){var D=E;
var F=this.tree.findForward(E,function(K){return this.tree.isBlock(K)&&!this.tree.isListContainer(K)&&K!=E.parentNode
}.bind(this))
}else{var F=E;
var D=this.tree.findBackward(E,function(K){return this.tree.isBlock(K)&&!this.tree.isListContainer(K)&&K!=E.parentNode
}.bind(this))
}if(F&&this.tree.isDescendantOf(this.getRoot(),F)){var G=F.parentNode;
if(this.tree.isBlockContainer(F)){G=F;
this.wrapAllInlineOrTextNodesAs("P",G,true);
F=G.firstChild
}}else{F=null
}if(D&&this.tree.isDescendantOf(this.getRoot(),D)){var H=D.parentNode;
if(this.tree.isBlockContainer(D)){H=D;
this.wrapAllInlineOrTextNodesAs("P",H,true);
D=H.lastChild
}}else{D=null
}try{var C=H&&(this.tree.isTableCell(H)||["TR","THEAD","TBODY"].indexOf(H.nodeName)!=-1)&&G&&(this.tree.isTableCell(G)||["TR","THEAD","TBODY"].indexOf(G.nodeName)!=-1);
if(C&&H!=G){return null
}if((!I||!D)&&F&&this.outdentElement(F)){return E
}if(G&&G.nodeName=="LI"&&this.tree.isListContainer(F.nextSibling)){this.extractOutElementFromParent(G);
return D
}if(G&&G.nodeName=="LI"&&this.tree.isListContainer(G.parentNode.previousSibling)){this.mergeAdjustLists(G.parentNode.previousSibling,true,"next");
return D
}if(F&&!C&&H&&H.nodeName=="LI"&&G&&G.nodeName=="LI"&&H.parentNode.nextSibling==G.parentNode){var A=G.parentNode;
this.moveChildNodes(G.parentNode,H.parentNode);
this.deleteNode(A);
return D
}if(F&&!C&&H&&H.nextSibling==G&&((I&&H.nodeName!="LI")||(!I&&H.nodeName=="LI"))){this.moveChildNodes(G,H);
return D
}if(G&&G.nodeName!="LI"&&!this.getParentElementOf(G,["TABLE"])&&!this.tree.isListContainer(G)&&G!=this.getRoot()&&!F.previousSibling){return this.unwrapElement(G,true)
}if(J&&G&&G.nodeName=="TABLE"){this.deleteNode(G,true);
return D
}else{if(!J&&H&&this.tree.isTableCell(H)&&!this.tree.isTableCell(G)){this.deleteNode(this.getParentElementOf(H,["TABLE"]),true);
return F
}}if(D==F){return null
}if(!D||!F||!H||!G){return null
}if(this.getParentElementOf(D,["TD","TH"])!=this.getParentElementOf(F,["TD","TH"])){return null
}var B=false;
if(xq.Browser.isTrident&&D.childNodes.length>=2&&this.isMarker(D.lastChild.previousSibling)&&D.lastChild.nodeType==3&&D.lastChild.nodeValue.length==1&&D.lastChild.nodeValue.charCodeAt(0)==160){this.deleteNode(D.lastChild)
}this.removePlaceHoldersAndEmptyNodes(D);
if(this.isEmptyBlock(D)){if(this.tree.isAtomic(D)){D=this.replaceTag("P",D)
}D=this.replaceTag(F.nodeName,D)||D;
D.innerHTML=""
}else{if(D.firstChild==D.lastChild&&this.isMarker(D.firstChild)){D=this.replaceTag(F.nodeName,D)||D
}}if(this.isEmptyBlock(F)){if(this.tree.isAtomic(F)){F=this.replaceTag("P",F)
}F.innerHTML=""
}this.moveChildNodes(F,D);
this.deleteNode(F);
return D
}finally{if(H&&this.isEmptyBlock(H)){this.deleteNode(H,true)
}if(G&&this.isEmptyBlock(G)){this.deleteNode(G,true)
}if(H){this.unwrapUnnecessaryParagraph(H)
}if(G){this.unwrapUnnecessaryParagraph(G)
}}},mergeAdjustLists:function(A,G,D){var F=A.previousSibling;
var C=F&&(F.nodeName==A.nodeName&&F.className==A.className);
if((!D||D.toLowerCase()=="prev")&&(C||(G&&this.tree.isListContainer(F)))){while(F.lastChild){this.insertNodeAt(F.lastChild,A,"start")
}this.deleteNode(F)
}var E=A.nextSibling;
var B=E&&(E.nodeName==A.nodeName&&E.className==A.className);
if((!D||D.toLowerCase()=="next")&&(B||(G&&this.tree.isListContainer(E)))){while(E.firstChild){this.insertNodeAt(E.firstChild,A,"end")
}this.deleteNode(E)
}},moveChildNodes:function(B,A){if(this.tree.isDescendantOf(B,A)||["HTML","HEAD"].indexOf(A.nodeName)!=-1){throw"Illegal argument. Cannot move children of element["+B.nodeName+"] to element["+A.nodeName+"]"
}if(B==A){return 
}while(B.firstChild){A.appendChild(B.firstChild)
}},copyAttributes:function(E,D,B){var A=E.attributes;
if(!A){return 
}for(var C=0;
C<A.length;
C++){if(A[C].nodeName=="class"&&A[C].nodeValue){D.className=A[C].nodeValue
}else{if((B||"id"!=A[C].nodeName)&&A[C].nodeValue){D.setAttribute(A[C].nodeName,A[C].nodeValue)
}}}},_indentElements:function(C,E,D){for(var B=0;
B<D.length;
B++){if(D[B]==C||this.tree.isDescendantOf(D[B],C)){return 
}}leaves=this.tree.getLeavesAtEdge(C);
if(E.include(leaves[0])){var F=this.indentElement(C,true);
if(F){D.push(F);
return 
}}if(E.include(C)){var F=this.indentElement(C,true);
if(F){D.push(F);
return 
}}var A=xq.$A(C.childNodes);
for(var B=0;
B<A.length;
B++){this._indentElements(A[B],E,D)
}return 
},indentElements:function(H,G){var E=this.getBlockElementsBetween(H,G);
var C=this.tree.findCommonAncestorAndImmediateChildrenOf(H,G);
var D=[];
leaves=this.tree.getLeavesAtEdge(C.parent);
if(E.include(leaves[0])){var F=this.indentElement(C.parent);
if(F){return[F]
}}var B=xq.$A(C.parent.childNodes);
for(var A=0;
A<B.length;
A++){this._indentElements(B[A],E,D)
}D=D.flatten();
return D.length>0?D:E
},outdentElementsCode:function(A){if(A.tagName=="LI"){A=A.parentNode
}if(A.tagName=="OL"&&A.className=="code"){return true
}return false
},_outdentElements:function(C,F,E){for(var B=0;
B<E.length;
B++){if(E[B]==C||this.tree.isDescendantOf(E[B],C)){return 
}}leaves=this.tree.getLeavesAtEdge(C);
if(F.include(leaves[0])&&!this.outdentElementsCode(leaves[0])){var G=this.outdentElement(C,true);
if(G){E.push(G);
return 
}}if(F.include(C)){var A=xq.$A(C.parentNode.childNodes);
var D=this.outdentElementsCode(C);
var G=this.outdentElement(C,true,D);
if(G){if(A.include(G)&&this.tree.isListContainer(C.parentNode)&&!D){for(var B=0;
B<A.length;
B++){if(F.include(A[B])&&!E.include(A[B])){E.push(A[B])
}}}else{E.push(G)
}return 
}}var A=xq.$A(C.childNodes);
for(var B=0;
B<A.length;
B++){this._outdentElements(A[B],F,E)
}return 
},outdentElements:function(I,J){var B,D;
if(I.parentNode.tagName=="LI"){B=I.parentNode
}if(J.parentNode.tagName=="LI"){D=J.parentNode
}var A=this.getBlockElementsBetween(I,J);
var G=this.tree.findCommonAncestorAndImmediateChildrenOf(I,J);
var H=[];
leaves=this.tree.getLeavesAtEdge(G.parent);
if(A.include(leaves[0])&&!this.outdentElementsCode(G.parent)){var E=this.outdentElement(G.parent);
if(E){return[E]
}}var C=xq.$A(G.parent.childNodes);
for(var F=0;
F<C.length;
F++){this._outdentElements(C[F],A,H)
}if(I.offsetParent&&J.offsetParent){B=I;
D=J
}else{if(A.first().offsetParent&&A.last().offsetParent){B=A.first();
D=A.last()
}}H=H.flatten();
if(!B||!B.offsetParent){B=H.first()
}if(!D||!D.offsetParent){D=H.last()
}return this.getBlockElementsBetween(B,D)
},indentElement:function(E,D,A){if(!A&&(E.nodeName=="LI"||(!this.tree.isListContainer(E)&&!E.previousSibling&&E.parentNode.nodeName=="LI"))){return this.indentListItem(E,D)
}var C=this.getRoot();
if(!E||E==C){return null
}if(E.parentNode!=C&&!E.previousSibling&&!D){E=E.parentNode
}var F=E.style.marginLeft;
var B=F?this._getCssValue(F,"px"):{value:0,unit:"em"};
B.value+=2;
E.style.marginLeft=B.value+B.unit;
return E
},outdentElement:function(E,D,A){if(!A&&E.nodeName=="LI"){return this.outdentListItem(E,D)
}var C=this.getRoot();
if(!E||E==C){return null
}var F=E.style.marginLeft;
var B=F?this._getCssValue(F,"px"):{value:0,unit:"em"};
if(B.value==0){return E.previousSibling||A?null:this.outdentElement(E.parentNode,D)
}B.value-=2;
E.style.marginLeft=B.value<=0?"":B.value+B.unit;
if(E.style.cssText==""){E.removeAttribute("style")
}return E
},indentListItem:function(E,B){var A=this.getParentElementOf(E,["LI"]);
var C=A.parentNode;
var G=A.previousSibling;
if(!A.previousSibling){return this.indentElement(C)
}if(A.parentNode.nodeName=="OL"&&A.parentNode.className=="code"){return this.indentElement(A,B,true)
}if(!G.lastChild){G.appendChild(this.makePlaceHolder())
}var F=this.tree.isListContainer(G.lastChild)?G.lastChild:this.insertNodeAt(this.createElement(C.nodeName),G,"end");
this.wrapAllInlineOrTextNodesAs("P",G,true);
F.appendChild(A);
if(!B&&A.lastChild&&this.tree.isListContainer(A.lastChild)){var D=A.lastChild;
var H;
while(H=D.lastChild){this.insertNodeAt(H,A,"after")
}this.deleteNode(D)
}this.unwrapUnnecessaryParagraph(A);
return A
},outdentListItem:function(E,C){var B=this.getParentElementOf(E,["LI"]);
var D=B.parentNode;
if(!B.previousSibling){var H=this.outdentElement(D);
if(H){return H
}}if(B.parentNode.nodeName=="OL"&&B.parentNode.className=="code"){return this.outdentElement(B,C,true)
}var A=D.parentNode;
if(A.nodeName!="LI"){return null
}if(C){while(D.lastChild!=B){this.insertNodeAt(D.lastChild,A,"after")
}}else{if(B.nextSibling){var G=B.lastChild&&this.tree.isListContainer(B.lastChild)?B.lastChild:this.insertNodeAt(this.createElement(D.nodeName),B,"end");
this.copyAttributes(D,G);
var F;
while(F=B.nextSibling){G.appendChild(F)
}}}B=this.insertNodeAt(B,A,"after");
if(D.childNodes.length==0){this.deleteNode(D)
}if(B.firstChild&&this.tree.isListContainer(B.firstChild)){this.insertNodeAt(this.makePlaceHolder(),B,"start")
}this.wrapAllInlineOrTextNodesAs("P",B);
this.unwrapUnnecessaryParagraph(A);
return B
},justifyBlock:function(C,B){while(C.parentNode!=this.getRoot()&&!C.previousSibling&&!C.nextSibling&&!this.tree.isListContainer(C.parentNode)){C=C.parentNode
}var A=B.toLowerCase()=="both"?"justify":B;
if(A=="left"){C.style.textAlign="";
if(C.style.cssText==""){C.removeAttribute("style")
}}else{C.style.textAlign=A
}return C
},justifyBlocks:function(C,A){for(var B=0;
B<C.length;
B++){this.justifyBlock(C[B],A)
}return C
},applyList:function(C,D){D=D.toUpperCase();
var A=D=="UL"?"UL":"OL";
if(C.nodeName=="LI"||(C.parentNode.nodeName=="LI"&&!C.previousSibling)){var C=this.getParentElementOf(C,["LI"]);
var B=C.parentNode;
if(B.nodeName==A){return this.extractOutElementFromParent(C)
}else{return this.changeListTypeTo(C,D)
}}else{return this.turnElementIntoListItem(C,D)
}},applyLists:function(M,N,K){K=K.toUpperCase();
var I=K=="UL"?"UL":"OL";
var A=this.getBlockElementsBetween(M,N);
var J=A.findAll(function(P){return P.nodeName=="LI"||!this.tree.isBlockContainer(P)
}.bind(this));
var B=J.findAll(function(P){return P.nodeName=="LI"
}.bind(this));
var H=J.findAll(function(P){return P.nodeName!="LI"&&!(P.parentNode.nodeName=="LI"&&!P.previousSibling&&!P.nextSibling)&&!this.tree.isDescendantOf(B,P)
}.bind(this));
var O=B.findAll(function(P){return P.parentNode.nodeName!=I
}.bind(this));
var E=H.length>0;
var D=O.length>0;
var L=null;
if(E){L=H
}else{if(D){L=O
}else{L=B
}}for(var F=0;
F<L.length;
F++){var C=L[F];
var G=A.indexOf(C);
A[G]=this.applyList(C,K)
}return A
},correctEmptyElement:function(A){throw"Not implemented"
},correctParagraph:function(){throw"Not implemented"
},makePlaceHolder:function(){throw"Not implemented"
},makePlaceHolderString:function(){throw"Not implemented"
},makeEmptyParagraph:function(){throw"Not implemented"
},applyBackgroundColor:function(A){throw"Not implemented"
},applyForegroundColor:function(A){this.execCommand("forecolor",A)
},execCommand:function(A,B){throw"Not implemented"
},applyRemoveFormat:function(){throw"Not implemented"
},applyEmphasis:function(){throw"Not implemented"
},applyStrongEmphasis:function(){throw"Not implemented"
},applyStrike:function(){throw"Not implemented"
},applyUnderline:function(){throw"Not implemented"
},applySuperscription:function(){this.execCommand("superscript")
},applySubscription:function(){this.execCommand("subscript")
},indentBlock:function(B,A){return(!B.previousSibling&&B.parentNode.nodeName=="LI")?this.indentListItem(B,A):this.indentElement(B)
},outdentBlock:function(B,A){while(true){if(!B.previousSibling&&B.parentNode.nodeName=="LI"){B=this.outdentListItem(B,A);
return B
}else{var C=this.outdentElement(B);
if(C){return C
}if(!B.previousSibling){B=B.parentNode
}else{break
}}}return null
},wrapBlock:function(B,F,C){if(this.tree._blockTags.indexOf(B)==-1){throw"Unsuppored block container: ["+B+"]"
}if(!F){F=this.getCurrentBlockElement()
}if(!C){C=F
}var A=false;
if(F==C){A=true
}else{if(F.parentNode==C.parentNode&&!F.previousSibling&&!C.nextSibling){A=true;
F=C=F.parentNode
}else{A=(F.parentNode==C.parentNode)&&(F.nodeName!="LI")
}}if(!A){return null
}var E=this.createElement(B);
if(F==C){if(this.tree.isBlockContainer(F)&&!this.tree.isListContainer(F)){if(this.tree.isBlockOnlyContainer(E)){this.correctEmptyElement(F);
this.wrapAllInlineOrTextNodesAs("P",F,true)
}this.moveChildNodes(F,E);
F.appendChild(E)
}else{E=this.insertNodeAt(E,F,"after");
E.appendChild(F)
}this.correctEmptyElement(E)
}else{E=this.insertNodeAt(E,F,"before");
var D=F;
while(D!=C){next=D.nextSibling;
E.appendChild(D);
D=next
}E.appendChild(D)
}return E
},focus:function(){throw"Not implemented"
},sel:function(){throw"Not implemented"
},rng:function(){throw"Not implemented"
},hasSelection:function(){throw"Not implemented"
},hasFocus:function(){var A=this.getCurrentElement();
return(A&&A.ownerDocument==this.getDoc())
},scrollIntoView:function(C,B,A){C.scrollIntoView(B);
if(A){this.placeCaretAtStartOf(C)
}},selectAll:function(){return this.execCommand("selectall")
},selectElement:function(B,A){throw"Not implemented"
},selectBlocksBetween:function(B,A){throw"Not implemented"
},deleteSelection:function(){throw"Not implemented"
},collapseSelection:function(A){throw"Not implemented"
},getSelectionAsHtml:function(){throw"Not implemented"
},getSelectionAsText:function(){throw"Not implemented"
},placeCaretAtStartOf:function(A){throw"Not implemented"
},isEmptyTextNode:function(A){return A.nodeType==3&&A.nodeValue.length==0
},isCaretAtEmptyBlock:function(){return this.isEmptyBlock(this.getCurrentBlockElement())
},isCaretAtBlockStart:function(){throw"Not implemented"
},isCaretAtBlockEnd:function(){throw"Not implemented"
},saveSelection:function(){throw"Not implemented"
},restoreSelection:function(A){throw"Not implemented"
},createMarker:function(){var A=this.createElement("SPAN");
A.id="xquared_marker_"+(this._lastMarkerId++);
A.className="xquared_marker";
return A
},pushMarker:function(){var A=this.createMarker();
return this.insertNode(A)
},popMarker:function(B){var C="xquared_marker_"+(--this._lastMarkerId);
var A=this.$(C);
if(!A){return 
}if(B){this.selectElement(A,true);
this.collapseSelection(false)
}this.deleteNode(A)
},isMarker:function(A){return(A.nodeType==1&&A.nodeName=="SPAN"&&A.className=="xquared_marker")
},isFirstBlockOfBody:function(C){var A=this.getRoot();
var B=this.tree.findBackward(C,function(D){return(D==A)||D.previousSibling
}.bind(this));
return B==A
},getOuterHTML:function(A){throw"Not implemented"
},getInnerText:function(A){return A.innerHTML.stripTags()
},isPlaceHolder:function(A){throw"Not implemented"
},isFirstLiWithNestedList:function(A){return !A.previousSibling&&A.parentNode.nodeName=="LI"&&this.tree.isListContainer(A.nextSibling)
},searchAnchors:function(B,D){if(!B){B=this.getRoot()
}if(!D){D=[]
}var C=B.getElementsByTagName("A");
for(var A=0;
A<C.length;
A++){D.push(C[A])
}return D
},searchHeadings:function(D,G){if(!D){D=this.getRoot()
}if(!G){G=[]
}var F=/^h[1-6]/ig;
var B=D.childNodes;
if(!B){return[]
}for(var C=0;
C<B.length;
C++){var E=B[C]&&this.tree._blockContainerTags.indexOf(B[C].nodeName)!=-1;
var A=B[C]&&B[C].nodeName.match(F);
if(E){this.searchHeadings(B[C],G)
}else{if(A){G.push(B[C])
}}}return G
},collectStructureAndStyle:function(F){if(!F||F.nodeName=="#document"){return{}
}var E=this.getParentBlockElementOf(F);
if(E==null){return{}
}var L=this.tree.collectParentsOf(F,true,function(Q){return E.parentNode==Q
});
var K=E.nodeName;
var D={};
var N=this.getDoc();
var B=N.queryCommandState("Italic");
var O=N.queryCommandState("Bold");
var I=N.queryCommandState("Strikethrough");
var C=N.queryCommandState("Underline")&&!this.getParentElementOf(F,["A"]);
var H=N.queryCommandState("superscript");
var P=N.queryCommandState("subscript");
while(E.parentNode&&E.parentNode!=this.getRoot()&&!E.previousSibling&&!E.nextSibling&&!this.tree.isListContainer(E.parentNode)){E=E.parentNode
}var G=false;
if(E.nodeName=="LI"){var M=E.parentNode;
var J=M.nodeName=="OL"&&M.className=="code";
G=J?"CODE":M.nodeName
}var A=E.style.textAlign||"left";
return{block:K,em:B,strong:O,strike:I,underline:C,superscription:H,subscription:P,list:G,justification:A}
},hasImportantAttributes:function(A){throw"Not implemented"
},isEmptyBlock:function(A){throw"Not implemented"
},getCurrentElement:function(){throw"Not implemented"
},getCurrentBlockElement:function(){var B=this.getCurrentElement();
if(!B){return null
}var A=this.getParentBlockElementOf(B);
if(!A){return null
}return(A.nodeName=="BODY")?null:A
},getParentBlockElementOf:function(A){while(A){if(this.tree._blockTags.indexOf(A.nodeName)!=-1){return A
}A=A.parentNode
}return null
},getParentElementOf:function(B,A){while(B){if(A.indexOf(B.nodeName)!=-1){return B
}B=B.parentNode
}return null
},getBlockElementsBetween:function(B,A){return this.tree.collectNodesBetween(B,A,function(C){return C.nodeType==1&&this.tree.isBlock(C)
}.bind(this))
},getBlockElementAtSelectionStart:function(){throw"Not implemented"
},getBlockElementAtSelectionEnd:function(){throw"Not implemented"
},getBlockElementsAtSelectionEdge:function(B,A){throw"Not implemented"
},getSelectedBlockElements:function(){var B=this.getBlockElementsAtSelectionEdge(true,true);
var C=B[0];
var A=B[1];
return this.tree.collectNodesBetween(C,A,function(D){return D.nodeType==1&&this.tree.isBlock(D)
}.bind(this))
},getElementById:function(A){return this.doc.getElementById(A)
},$:function(A){return this.getElementById(A)
},getFirstChild:function(B){if(!B){return null
}var A=xq.$A(B.childNodes);
return A.find(function(C){return !this.isEmptyTextNode(C)
}.bind(this))
},getLastChild:function(A){throw"Not implemented"
},getNextSibling:function(A){while(A=A.nextSibling){if(A.nodeType!=3||A.nodeValue.strip()!=""){break
}}return A
},getBottommostFirstChild:function(A){while(A.firstChild&&A.nodeType==1){A=A.firstChild
}return A
},getBottommostLastChild:function(A){while(A.lastChild&&A.nodeType==1){A=A.lastChild
}return A
},_getCssValue:function(C,A){if(!C||C.length==0){return{value:0,unit:A}
}var B=C.match(/(\d+)(.*)/);
return{value:parseInt(B[1]),unit:B[2]||A}
}});
xq.RichDom.createInstance=function(){if(xq.Browser.isTrident){return new xq.RichDomTrident()
}else{if(xq.Browser.isWebkit){return new xq.RichDomWebkit()
}else{return new xq.RichDomGecko()
}}};
xq.RichDomW3=xq.Class(xq.RichDom,{insertNode:function(B){var A=this.rng();
A.insertNode(B);
A.selectNode(B);
A.collapse(false);
return B
},removeTrailingWhitespace:function(A){},getOuterHTML:function(A){var B=A.ownerDocument.createElement("div");
B.appendChild(A.cloneNode(true));
return B.innerHTML
},correctEmptyElement:function(A){if(!A||A.nodeType!=1||this.tree.isAtomic(A)){return 
}if(A.firstChild){this.correctEmptyElement(A.firstChild)
}else{A.appendChild(this.makePlaceHolder())
}},correctParagraph:function(){if(this.hasSelection()){return false
}var D=this.getCurrentElement();
var A=false;
if(this.tree.isBlockOnlyContainer(D)){this.execCommand("InsertParagraph");
var E=this.getCurrentElement();
if(this.tree.isAtomic(E.previousSibling)){var C=this.tree.findForward(E,function(F){return this.tree.isBlock(F)&&!this.tree.isBlockOnlyContainer(F)
}.bind(this));
if(C){this.deleteNode(E);
this.placeCaretAtStartOf(C)
}}A=true
}else{if(this.tree.hasMixedContents(D)){this.wrapAllInlineOrTextNodesAs("P",D,true);
A=true
}}D=this.getCurrentElement();
if(this.tree.isBlock(D)&&!this._hasPlaceHolderAtEnd(D)){D.appendChild(this.makePlaceHolder());
A=true
}if(this.tree.isBlock(D)){var B=D.parentNode.lastChild;
if(this.isPlaceHolder(B)){this.deleteNode(B);
A=true
}}return A
},_hasPlaceHolderAtEnd:function(A){if(!A.hasChildNodes()){return false
}return this.isPlaceHolder(A.lastChild)||this._hasPlaceHolderAtEnd(A.lastChild)
},applyBackgroundColor:function(A){this.execCommand("styleWithCSS","true");
this.execCommand("hilitecolor",A);
this.execCommand("styleWithCSS","false");
var E=this.saveSelection();
var F=this.getSelectedBlockElements();
if(F.length==0){return 
}for(var D=0;
D<F.length;
D++){if((D==0||D==F.length-1)&&!F[D].style.backgroundColor){continue
}var C=this.wrapAllInlineOrTextNodesAs("SPAN",F[D],true);
for(var B=0;
B<C.length;
B++){C[B].style.backgroundColor=A
}F[D].style.backgroundColor=""
}this.restoreSelection(E)
},execCommand:function(A,B){return this.doc.execCommand(A,false,B||null)
},saveSelection:function(){var A=this.rng();
return[A.startContainer,A.startOffset,A.endContainer,A.endOffset]
},restoreSelection:function(B){var A=this.rng();
A.setStart(B[0],B[1]);
A.setEnd(B[2],B[3])
},applyRemoveFormat:function(){this.execCommand("RemoveFormat");
this.execCommand("Unlink")
},applyEmphasis:function(){this.execCommand("styleWithCSS","false");
this.execCommand("italic")
},applyStrongEmphasis:function(){this.execCommand("styleWithCSS","false");
this.execCommand("bold")
},applyStrike:function(){this.execCommand("styleWithCSS","false");
this.execCommand("strikethrough")
},applyUnderline:function(){this.execCommand("styleWithCSS","false");
this.execCommand("underline")
},execHeading:function(A){this.execCommand("Heading","H"+A)
},focus:function(){setTimeout(this._focus.bind(this),0)
},_focus:function(){this.win.focus();
if(!this.hasSelection()&&this.getCurrentElement().nodeName=="HTML"){this.selectElement(this.doc.body.firstChild);
this.collapseSelection(true)
}},sel:function(){return this.win.getSelection()
},rng:function(){var A=this.sel();
return(A==null||A.rangeCount==0)?null:A.getRangeAt(0)
},hasSelection:function(){var A=this.sel();
return A&&!A.isCollapsed
},deleteSelection:function(){this.rng().deleteContents();
this.sel().collapseToStart()
},selectElement:function(A,B){throw"Not implemented yet"
},selectBlocksBetween:function(D,B){try{if(!xq.Browser.isMac){this.doc.execCommand("SelectAll",false,null)
}}catch(C){}var A=this.rng();
A.setStart(D.firstChild,0);
A.setEnd(B,B.childNodes.length)
},collapseSelection:function(A){this.rng().collapse(A)
},placeCaretAtStartOf:function(A){while(this.tree.isBlock(A.firstChild)){A=A.firstChild
}this.selectElement(A,false);
this.collapseSelection(true)
},getSelectionAsHtml:function(){var A=document.createElement("div");
A.appendChild(this.rng().cloneContents());
return A.innerHTML
},getSelectionAsText:function(){return this.rng().toString()
},hasImportantAttributes:function(A){return !!(A.id||A.className||A.style.cssText)
},isEmptyBlock:function(C){if(!C.hasChildNodes()){return true
}var B=C.childNodes;
for(var A=0;
A<B.length;
A++){if(!this.isPlaceHolder(B[A])&&!this.isEmptyTextNode(B[A])){return false
}}return true
},getLastChild:function(C){if(!C||!C.hasChildNodes()){return null
}var A=xq.$A(C.childNodes).reverse();
for(var B=0;
B<A.length;
B++){if(!this.isPlaceHolder(A[B])&&!this.isEmptyTextNode(A[B])){return A[B]
}}return null
},getCurrentElement:function(){var B=this.rng();
if(!B){return null
}var A=B.startContainer;
return A.nodeType==3?A.parentNode:A
},getBlockElementsAtSelectionEdge:function(E,A){var F=this.getBlockElementAtSelectionStart();
var B=this.getBlockElementAtSelectionEnd();
var D=false;
if(E&&F!=B&&this.tree.checkTargetBackward(F,B)){var C=F;
F=B;
B=C;
D=true
}if(A&&F!=B){}return[F,B]
},getBlockElementAtSelectionStart:function(){var A=this.getParentBlockElementOf(this.sel().anchorNode);
while(this.tree.isBlockContainer(A)&&A.firstChild&&this.tree.isBlock(A.firstChild)){A=A.firstChild
}return A
},getBlockElementAtSelectionEnd:function(){var A=this.getParentBlockElementOf(this.sel().focusNode);
while(this.tree.isBlockContainer(A)&&A.lastChild&&this.tree.isBlock(A.lastChild)){A=A.lastChild
}return A
},isCaretAtBlockStart:function(){if(this.isCaretAtEmptyBlock()){return true
}if(this.hasSelection()){return false
}var C=this.rng();
var D=this.getCurrentBlockElement();
var A=false;
if(D==C.startContainer){var B=this.pushMarker();
while(D=this.getFirstChild(D)){if(D==B){A=true;
break
}}this.popMarker()
}else{while(D=D.firstChild){if(D==C.startContainer&&C.startOffset==0){A=true;
break
}}}return A
},isCaretAtBlockEnd:function(){if(this.isCaretAtEmptyBlock()){return true
}if(this.hasSelection()){return false
}var C=this.rng();
var D=this.getCurrentBlockElement();
var A=false;
if(D==C.startContainer){var B=this.pushMarker();
while(D=this.getLastChild(D)){if((D==B)||(this.isPlaceHolder(D)&&D.previousSibling==B)){A=true;
break
}}this.popMarker()
}else{while(D=this.getLastChild(D)){if(D==C.endContainer&&C.endContainer.nodeType==1){A=true;
break
}else{if(D==C.endContainer&&C.endOffset==D.nodeValue.length){A=true;
break
}}}}return A
}});
xq.RichDomGecko=xq.Class(xq.RichDomW3,{makePlaceHolder:function(){var A=this.createElement("BR");
A.setAttribute("type","_moz");
return A
},makePlaceHolderString:function(){return"<br type=\"_moz\" />"
},makeEmptyParagraph:function(){return this.createElementFromHtml("<p><br type=\"_moz\" /></p>")
},isPlaceHolder:function(B){if(B.nodeType!=1){return false
}var A=B.nodeName=="BR"&&B.getAttribute("type")=="_moz";
if(A){return true
}var C=B.nodeName=="BR"&&!this.getNextSibling(B);
if(C){return true
}return false
},selectElement:function(B,C){if(!B){throw"[element] is null"
}if(B.nodeType!=1){throw"[element] is not an element"
}try{if(!xq.Browser.isMac){this.doc.execCommand("SelectAll",false,null)
}}catch(A){}if(C){this.rng().selectNode(B)
}else{this.rng().selectNodeContents(B)
}}});
xq.RichDomWebkit=xq.Class(xq.RichDomW3,{makePlaceHolder:function(){var A=this.createElement("BR");
A.className="webkit-block-placeholder";
return A
},makePlaceHolderString:function(){return"<br class=\"webkit-block-placeholder\" />"
},makeEmptyParagraph:function(){return this.createElementFromHtml("<p><br class=\"webkit-block-placeholder\" /></p>")
},isPlaceHolder:function(A){return A.nodeName=="BR"&&A.className=="webkit-block-placeholder"
},rng:function(){var B=this.sel();
var A=this.doc.createRange();
if(!this._rng||this._anchorNode!=B.anchorNode||this._anchorOffset!=B.anchorOffset||this._focusNode!=B.focusNode||this._focusOffset!=B.focusOffset){if(B.type!="None"){A.setStart(B.anchorNode,B.anchorOffset);
A.setEnd(B.focusNode,B.focusOffset)
}this._anchorNode=B.anchorNode;
this._anchorOffset=B.anchorOffset;
this._focusNode=B.focusNode;
this._focusOffset=B.focusOffset;
this._rng=A
}return this._rng
},selectElement:function(B,C){if(!B){throw"[element] is null"
}if(B.nodeType!=1){throw"[element] is not an element"
}var A=this.rng();
if(C){A.selectNode(B)
}else{A.selectNodeContents(B)
}this._setSelectionByRange(A)
},deleteSelection:function(){this.rng().deleteContents()
},collapseSelection:function(B){var A=this.rng();
A.collapse(B);
this._setSelectionByRange(A)
},getSelectionAsHtml:function(){var B=this.createElement("div");
var A=this.rng();
var C=this.rng().cloneContents();
if(C){B.appendChild(C)
}return B.innerHTML
},_setSelectionByRange:function(A){var B=this.sel();
B.setBaseAndExtent(A.startContainer,A.startOffset,A.endContainer,A.endOffset);
this._anchorNode=B.anchorNode;
this._anchorOffset=B.anchorOffset;
this._focusNode=B.focusNode;
this._focusOffset=B.focusOffset
}});
xq.RichDomTrident=xq.Class(xq.RichDom,{makePlaceHolder:function(){return this.createTextNode(" ")
},makePlaceHolderString:function(){return"&nbsp;"
},makeEmptyParagraph:function(){return this.createElementFromHtml("<p>&nbsp;</p>")
},isPlaceHolder:function(A){return false
},getOuterHTML:function(A){return A.outerHTML
},insertNode:function(B){if(this.hasSelection()){this.collapseSelection(true)
}this.rng().pasteHTML("<span id=\"xquared_temp\"></span>");
var A=this.$("xquared_temp");
if(B.id=="xquared_temp"){return A
}A.replaceNode(B);
return B
},removeTrailingWhitespace:function(E){if(!E){return 
}if(this.tree.isBlockContainer(E)){return 
}if(this.isEmptyBlock(E)){return 
}var D=E.innerText;
var B=D.charCodeAt(D.length-1);
if(D.length<=1||[32,160].indexOf(B)==-1){return 
}var C=E;
while(C&&C.nodeType!=3){C=C.lastChild
}if(!C){return 
}var A=C.nodeValue;
if(A.length<=1){this.deleteNode(C,true)
}else{C.nodeValue=A.substring(0,A.length-1)
}},correctEmptyElement:function(A){if(!A||A.nodeType!=1||this.tree.isAtomic(A)){return 
}if(A.firstChild){this.correctEmptyElement(A.firstChild)
}else{A.innerHTML="&nbsp;"
}},copyAttributes:function(C,B,A){B.mergeAttributes(C,!A)
},correctParagraph:function(){if(!this.hasFocus()){return false
}if(this.hasSelection()){return false
}var D=this.getCurrentElement();
if(D.nodeName=="BODY"){D=this.insertNode(this.makeEmptyParagraph());
var B=D.nextSibling;
if(this.tree.isAtomic(B)){D=this.insertNodeAt(D,B,"after");
this.placeCaretAtStartOf(D);
var C=this.tree.findForward(D,function(E){return this.tree.isBlock(E)&&!this.tree.isBlockOnlyContainer(E)
}.bind(this));
if(C){this.deleteNode(D);
this.placeCaretAtStartOf(C)
}return true
}else{var C=this.tree.findForward(D,function(E){return this.tree.isBlock(E)&&!this.tree.isBlockOnlyContainer(E)
}.bind(this));
if(C){this.deleteNode(D);
this.placeCaretAtStartOf(C)
}else{this.placeCaretAtStartOf(D)
}return true
}}else{D=this.getCurrentBlockElement();
if(D.nodeType==3){D=D.parentNode
}if(this.tree.hasMixedContents(D)){var A=this.pushMarker();
this.wrapAllInlineOrTextNodesAs("P",D,true);
this.popMarker(true);
return true
}else{if((this.tree.isTextOrInlineNode(D.previousSibling)||this.tree.isTextOrInlineNode(D.nextSibling))&&this.tree.hasMixedContents(D.parentNode)){this.wrapAllInlineOrTextNodesAs("P",D.parentNode,true);
return true
}else{return false
}}}},execCommand:function(A,B){return this.doc.execCommand(A,false,B)
},applyBackgroundColor:function(A){this.execCommand("BackColor",A)
},applyEmphasis:function(){this.execCommand("Italic")
},applyStrongEmphasis:function(){this.execCommand("Bold")
},applyStrike:function(){this.execCommand("strikethrough")
},applyUnderline:function(){this.execCommand("underline")
},applyRemoveFormat:function(){this.execCommand("RemoveFormat");
this.execCommand("Unlink")
},execHeading:function(A){this.execCommand("FormatBlock","<H"+A+">")
},focus:function(){this.win.focus();
if(!this._focusedBefore){this.correctParagraph();
this.placeCaretAtStartOf(this.getCurrentBlockElement());
this._focusedBefore=true
}},sel:function(){return this.doc.selection
},rng:function(){try{var B=this.sel();
return(B==null)?null:B.createRange()
}catch(A){return null
}},hasSelection:function(){var A=this.sel().type.toLowerCase();
if("none"==A){return false
}if("text"==A&&this.getSelectionAsHtml().length==0){return false
}return true
},deleteSelection:function(){if(this.getSelectionAsText()!=""){this.sel().clear()
}},placeCaretAtStartOf:function(A){var B=this.insertNodeAt(this.createElement("SPAN"),A,"start");
this.selectElement(B);
this.collapseSelection(false);
this.deleteNode(B)
},selectElement:function(B,C){if(!B){throw"[element] is null"
}if(B.nodeType!=1){throw"[element] is not an element"
}var A=this.rng();
A.moveToElementText(B);
A.select()
},selectBlocksBetween:function(D,B){var A=this.rng();
var C=this.rng();
C.moveToElementText(D);
A.setEndPoint("StartToStart",C);
C.moveToElementText(B);
A.setEndPoint("EndToEnd",C);
A.select()
},collapseSelection:function(B){var A=this.rng();
A.collapse(B);
A.select()
},getSelectionAsHtml:function(){var A=this.rng();
return A&&A.htmlText?A.htmlText:""
},getSelectionAsText:function(){var A=this.rng();
return A&&A.text?A.text:""
},hasImportantAttributes:function(A){return !!(A.id||A.className||A.style.cssText)
},isEmptyBlock:function(A){if(!A.hasChildNodes()){return true
}if(A.nodeType==3&&!A.nodeValue){return true
}if(["&nbsp;"," ",""].indexOf(A.innerHTML)!=-1){return true
}return false
},getLastChild:function(C){if(!C||!C.hasChildNodes()){return null
}var A=xq.$A(C.childNodes).reverse();
for(var B=0;
B<A.length;
B++){if(A[B].nodeType!=3||A[B].nodeValue.length!=0){return A[B]
}}return null
},getCurrentElement:function(){if(this.sel().type.toLowerCase()=="control"){return this.rng().item(0)
}return this.rng().parentElement()
},getBlockElementAtSelectionStart:function(){var B=this.rng();
var C=B.duplicate();
C.collapse(true);
var A=this.getParentBlockElementOf(C.parentElement());
if(A.nodeName=="BODY"){A=A.firstChild
}return A
},getBlockElementAtSelectionEnd:function(){var B=this.rng();
var C=B.duplicate();
C.collapse(false);
var A=this.getParentBlockElementOf(C.parentElement());
if(A.nodeName=="BODY"){A=A.lastChild
}return A
},getBlockElementsAtSelectionEdge:function(B,A){return[this.getBlockElementAtSelectionStart(),this.getBlockElementAtSelectionEnd()]
},isCaretAtBlockStart:function(){if(this.isCaretAtEmptyBlock()){return true
}if(this.hasSelection()){return false
}var C=this.getCurrentBlockElement();
var B=this.pushMarker();
var A=false;
while(C=this.getFirstChild(C)){if(C==B){A=true;
break
}}this.popMarker();
return A
},isCaretAtBlockEnd:function(){if(this.isCaretAtEmptyBlock()){return true
}if(this.hasSelection()){return false
}var D=this.getCurrentBlockElement();
var B=this.pushMarker();
var A=false;
while(D=this.getLastChild(D)){var C=D.nodeValue;
if(D==B){A=true;
break
}else{if(D.nodeType==3&&D.previousSibling==B&&(C==" "||(C.length==1&&C.charCodeAt(0)==160))){A=true;
break
}}}this.popMarker();
return A
},saveSelection:function(){return this.rng()
},restoreSelection:function(A){A.select()
}});
xq.RichTable=xq.Class({initialize:function(B,A){xq.addToFinalizeQueue(this);
this.rdom=B;
this.table=A
},insertNewRowAt:function(E,C){var F=this.rdom.createElement("TR");
var B=E.cells;
for(var D=0;
D<B.length;
D++){var A=this.rdom.createElement(B[D].nodeName);
this.rdom.correctEmptyElement(A);
F.appendChild(A)
}return this.rdom.insertNodeAt(F,E,C)
},insertNewCellAt:function(B,D){var C=[];
var A=this.getXIndexOf(B);
var G=0;
while(true){var F=this.getCellAt(A,G);
if(!F){break
}C.push(F);
G++
}for(var E=0;
E<C.length;
E++){var B=this.rdom.createElement(C[E].nodeName);
this.rdom.correctEmptyElement(B);
this.rdom.insertNodeAt(B,C[E],D)
}},deleteRow:function(A){return this.rdom.removeBlock(A)
},deleteCell:function(B){if(!B.previousSibling&&!B.nextSibling){this.rdom.deleteNode(this.table);
return 
}var C=[];
var A=this.getXIndexOf(B);
var F=0;
while(true){var E=this.getCellAt(A,F);
if(!E){break
}C.push(E);
F++
}for(var D=0;
D<C.length;
D++){this.rdom.deleteNode(C[D])
}},getPreviousCellOf:function(A){if(A.previousSibling){return A.previousSibling
}var B=this.getPreviousRowOf(A.parentNode);
if(B){return B.lastChild
}return null
},getNextCellOf:function(A){if(A.nextSibling){return A.nextSibling
}var B=this.getNextRowOf(A.parentNode);
if(B){return B.firstChild
}return null
},getPreviousRowOf:function(B){if(B.previousSibling){return B.previousSibling
}var A=B.parentNode;
if(A.previousSibling&&A.previousSibling.lastChild){return A.previousSibling.lastChild
}return null
},getNextRowOf:function(B){if(B.nextSibling){return B.nextSibling
}var A=B.parentNode;
if(A.nextSibling&&A.nextSibling.firstChild){return A.nextSibling.firstChild
}return null
},getAboveCellOf:function(B){var C=this.getPreviousRowOf(B.parentNode);
if(!C){return null
}var A=this.getXIndexOf(B);
return C.cells[A]
},getBelowCellOf:function(B){var C=this.getNextRowOf(B.parentNode);
if(!C){return null
}var A=this.getXIndexOf(B);
return C.cells[A]
},getXIndexOf:function(A){var C=A.parentNode;
for(var B=0;
B<C.cells.length;
B++){if(C.cells[B]==A){return B
}}return -1
},getYIndexOf:function(A){var D=-1;
var C=row.parentNode;
for(var B=0;
B<C.rows.length;
B++){if(C.rows[B]==row){D=B;
break
}}if(this.hasHeadingAtTop()&&C.nodeName=="TBODY"){D=D+1
}return D
},getLocationOf:function(B){var A=this.getXIndexOf(B);
var C=this.getYIndexOf(B);
return{x:A,y:C}
},getCellAt:function(A,B){var B=this.getRowAt(B);
return(B&&B.cells.length>A)?B.cells[A]:null
},getRowAt:function(A){if(this.hasHeadingAtTop()){return A==0?this.table.tHead.rows[0]:this.table.tBodies[0].rows[A-1]
}else{var B=this.table.tBodies[0].rows;
return(B.length>A)?B[A]:null
}},getDom:function(){return this.table
},hasHeadingAtTop:function(){return !!(this.table.tHead&&this.table.tHead.rows[0])
},hasHeadingAtLeft:function(){return this.table.tBodies[0].rows[0].cells[0].nodeName=="TH"
},correctEmptyCells:function(){var A=xq.$A(this.table.getElementsByTagName("TH"));
var C=xq.$A(this.table.getElementsByTagName("TD"));
for(var B=0;
B<C.length;
B++){A.push(C[B])
}for(var B=0;
B<A.length;
B++){if(this.rdom.isEmptyBlock(A[B])){this.rdom.correctEmptyElement(A[B])
}}}});
xq.RichTable.create=function(E,G,J,C){if(["t","tl","lt"].indexOf(C)!=-1){var I=true
}if(["l","tl","lt"].indexOf(C)!=-1){var K=true
}var F=[];
F.push("<table class=\"datatable\">");
if(I){F.push("<thead><tr>");
for(var D=0;
D<G;
D++){F.push("<th></th>")
}F.push("</tr></thead>");
J-=1
}F.push("<tbody>");
for(var D=0;
D<J;
D++){F.push("<tr>");
for(var B=0;
B<G;
B++){if(K&&B==0){F.push("<th></th>")
}else{F.push("<td></td>")
}}F.push("</tr>")
}F.push("</tbody>");
F.push("</table>");
var A=E.createElement("div");
A.innerHTML=F.join("");
var H=new xq.RichTable(E,A.firstChild);
H.correctEmptyCells();
return H
};
xq.Validator=xq.Class({initialize:function(C,A,D,B){xq.addToFinalizeQueue(this);
this.allowedTags=(D||["a","abbr","acronym","address","blockquote","br","caption","cite","code","dd","dfn","div","dl","dt","em","h1","h2","h3","h4","h5","h6","hr","img","kbd","li","ol","p","pre","q","samp","span","sup","sub","strong","table","thead","tbody","td","th","tr","ul","var"]).join(" ")+" ";
this.allowedAttrs=(B||["alt","cite","class","datetime","height","href","id","rel","rev","src","style","title","width"]).join(" ")+" ";
this.curUrl=C;
this.curUrlParts=C?C.parseURL():null;
this.urlValidationMode=A
},validate:function(B,A){throw"Not implemented"
},invalidate:function(A){throw"Not implemented"
},validateStrike:function(A){A=A.replace(/<strike(>|\s+[^>]*>)/ig,"<span class=\"strike\"$1");
A=A.replace(/<\/strike>/ig,"</span>");
return A
},validateUnderline:function(A){A=A.replace(/<u(>|\s+[^>]*>)/ig,"<em class=\"underline\"$1");
A=A.replace(/<\/u>/ig,"</em>");
return A
},replaceTag:function(A,C,B){return A.replace(new RegExp("(</?)"+C+"(>|\\s+[^>]*>)","ig"),"$1"+B+"$2")
},validateSelfClosingTags:function(A){return A.replace(/<(br|hr|img)([^>]*?)>/img,function(D,B,C){return"<"+B+C+" />"
})
},removeComments:function(A){return A.replace(/<!--.*?-->/img,"")
},removeDangerousElements:function(C){var A=xq.$A(C.getElementsByTagName("SCRIPT")).reverse();
for(var B=0;
B<A.length;
B++){A[B].parentNode.removeChild(A[B])
}},applyWhitelist:function(A){var C=this.allowedTags;
var B=this.allowedAttrs;
return A.replace(new RegExp("(</?)([^>]+?)(>|\\s+([^>]*?)(\\s?/?)>)","g"),function(H,K,M,G,L,I){if(C.indexOf(M)==-1){return""
}if(L){L=L.replace(/(^|\s")([^"=]+)(\s|$)/g,"$1$2=\"$2\"$3");
var J=[];
var E=L.match(/([^=]+)="[^"]*?"/g);
for(var F=0;
F<E.length;
F++){E[F]=E[F].strip();
var D=E[F].split("=")[0];
if(B.indexOf(D)!=-1){J.push(E[F])
}}L=J.join(" ");
if(L!=""){L=" "+L
}return K+M+L+I+">"
}else{return H
}})
},makeUrlsRelative:function(A){var B=this.curUrl;
var C=this.curUrlParts;
return A.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g,function(H,G,D,F,E){if(F){F=F.replace(/(href|src)="([^"]+)"/g,function(M,L,K){var J=null;
if(K.charAt(0)=="#"){J=C.includeQuery+K
}else{if(K.charAt(0)=="?"){J=C.includePath+K
}else{if(K.charAt(0)=="/"){J=C.includeHost+K
}else{if(K.match(/^\w+:\/\//)){J=K
}else{J=C.includeBase+K
}}}}var I=J;
if(J.indexOf(C.includeQuery)==0){I=J.substring(C.includeQuery.length)
}else{if(J.indexOf(C.includePath)==0){I=J.substring(C.includePath.length)
}else{if(J.indexOf(C.includeBase)==0){I=J.substring(C.includeBase.length)
}else{if(J.indexOf(C.includeHost)==0){I=J.substring(C.includeHost.length)
}}}}if(I==""){I="#"
}return L+"=\""+I+"\""
});
return G+F+E+">"
}else{return H
}});
return A
},makeUrlsHostRelative:function(A){var B=this.curUrl;
var C=this.curUrlParts;
return A.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g,function(H,G,D,F,E){if(F){F=F.replace(/(href|src)="([^"]+)"/g,function(M,L,K){var J=null;
if(K.charAt(0)=="#"){J=C.includeQuery+K
}else{if(K.charAt(0)=="?"){J=C.includePath+K
}else{if(K.charAt(0)=="/"){J=C.includeHost+K
}else{if(K.match(/^\w+:\/\//)){J=K
}else{J=C.includeBase+K
}}}}var I=J;
if(J.indexOf(C.includeHost)==0){I=J.substring(C.includeHost.length)
}if(I==""){I="#"
}return L+"=\""+I+"\""
});
return G+F+E+">"
}else{return H
}});
return A
},makeUrlsAbsolute:function(A){var B=this.curUrl;
var C=this.curUrlParts;
return A.replace(/(<\w+\s+)(\/|([^>]+?)(\/?))>/g,function(H,G,D,F,E){if(F){F=F.replace(/(href|src)="([^"]+)"/g,function(L,K,J){var I=null;
if(J.charAt(0)=="#"){I=C.includeQuery+J
}else{if(J.charAt(0)=="?"){I=C.includePath+J
}else{if(J.charAt(0)=="/"){I=C.includeHost+J
}else{if(J.match(/^\w+:\/\//)){I=J
}else{I=C.includeBase+J
}}}}return K+"=\""+I+"\""
});
return G+F+E+">"
}else{return H
}})
}});
xq.Validator.createInstance=function(C,A,D,B){if(xq.Browser.isTrident){return new xq.ValidatorTrident(C,A,D,B)
}else{if(xq.Browser.isWebkit){return new xq.ValidatorWebkit(C,A,D,B)
}else{return new xq.ValidatorGecko(C,A,D,B)
}}};
xq.ValidatorW3=xq.Class(xq.Validator,{validate:function(C,B){C=C.cloneNode(true);
var F=xq.RichDom.createInstance();
F.setRoot(C);
F.removePlaceHoldersAndEmptyNodes(C);
this.removeDangerousElements(C);
this.validateFontColor(C);
var E=C.innerHTML;
try{E=this.replaceTag(E,"b","strong");
E=this.replaceTag(E,"i","em");
E=this.validateStrike(E);
E=this.validateUnderline(E);
E=this.addNbspToEmptyBlocks(E);
if(B){E=this.performFullValidation(E)
}}catch(A){}var G=F.tree.getBlockTags().join("|");
var D=new RegExp("</("+G+")>([^\n])","img");
E=E.replace(D,"</$1>\n$2");
return E
},invalidate:function(C){var F=xq.RichDom.createInstance();
F.setRoot(C);
var E=xq.getElementsByClassName(F.getRoot(),"strike");
for(var B=0;
B<E.length;
B++){if("SPAN"==E[B].nodeName){F.replaceTag("strike",E[B]).removeAttribute("class")
}}var A=xq.getElementsByClassName(F.getRoot(),"underline");
for(var B=0;
B<A.length;
B++){if(["EM","I"].indexOf(A[B].nodeName)!=-1){F.replaceTag("u",A[B]).removeAttribute("class")
}}var D=F.getRoot().innerHTML;
D=this.replaceTag(D,"strong","b");
D=this.replaceTag(D,"em","i");
D=this.removeComments(D);
D=this.replaceNbspToBr(D);
return D
},performFullValidation:function(A){A=this.validateSelfClosingTags(A);
A=this.applyWhitelist(A);
if(this.urlValidationMode=="relative"){A=this.makeUrlsRelative(A)
}else{if(this.urlValidationMode=="host_relative"){A=this.makeUrlsHostRelative(A)
}else{if(this.urlValidationMode=="absolute"){A=this.makeUrlsAbsolute(A)
}}}return A
},validateFontColor:function(D){var F=xq.RichDom.createInstance();
F.setRoot(D);
var G=xq.$A(D.getElementsByTagName("FONT")).reverse();
for(var C=0;
C<G.length;
C++){var B=G[C];
var A=B.getAttribute("color");
if(A){var E=F.replaceTag("span",B);
E.removeAttribute("color");
E.style.color=A
}}},addNbspToEmptyBlocks:function(B){var C=new xq.DomTree().getBlockTags().join("|");
var A=new RegExp("<("+C+")>\\s*?</("+C+")>","img");
return B.replace(A,"<$1>&nbsp;</$2>")
},replaceNbspToBr:function(B){var D=new xq.DomTree().getBlockTags().join("|");
var A=new RegExp("<("+D+")>(&nbsp;)?</("+D+")>","img");
var C=xq.RichDom.createInstance();
return B.replace(A,"<$1>"+C.makePlaceHolderString()+"</$3>")
}});
xq.ValidatorGecko=xq.Class(xq.ValidatorW3,{});
xq.ValidatorWebkit=xq.Class(xq.ValidatorW3,{});
xq.ValidatorTrident=xq.Class(xq.Validator,{validate:function(C,B){C=C.cloneNode(true);
this.removeDangerousElements(C);
this.validateFontColor(C);
this.validateBackgroundColor(C);
var D=C.innerHTML;
try{D=this.validateStrike(D);
D=this.validateUnderline(D);
if(B){D=this.performFullValidation(D)
}}catch(A){}return D
},invalidate:function(C){var F=xq.RichDom.createInstance();
F.setRoot(C);
this.invalidateFontColor(C);
this.invalidateBackgroundColor(C);
var E=xq.getElementsByClassName(F.getRoot(),"strike");
for(var B=0;
B<E.length;
B++){if("SPAN"==E[B].nodeName){F.replaceTag("strike",E[B]).removeAttribute("className")
}}var A=xq.getElementsByClassName(F.getRoot(),"underline");
for(var B=0;
B<A.length;
B++){if(["EM","I"].indexOf(A[B].nodeName)!=-1){F.replaceTag("u",A[B]).removeAttribute("className")
}}var D=F.getRoot().innerHTML;
D=this.removeComments(D);
return D
},performFullValidation:function(A){A=this.lowerTagNamesAndUniformizeQuotation(A);
A=this.validateSelfClosingTags(A);
A=this.applyWhitelist(A);
if(this.urlValidationMode=="relative"){A=this.makeUrlsRelative(A)
}else{if(this.urlValidationMode=="host_relative"){A=this.makeUrlsHostRelative(A)
}else{if(this.urlValidationMode=="absolute"){}}}return A
},validateFontColor:function(D){var F=xq.RichDom.createInstance();
F.setRoot(D);
var G=xq.$A(D.getElementsByTagName("FONT")).reverse();
for(var C=0;
C<G.length;
C++){var B=G[C];
var A=B.getAttribute("color");
if(A){var E=F.replaceTag("span",B);
E.removeAttribute("color");
E.style.color=A
}}},invalidateFontColor:function(E){var G=xq.RichDom.createInstance();
G.setRoot(E);
var D=xq.$A(E.getElementsByTagName("SPAN")).reverse();
for(var C=0;
C<D.length;
C++){var F=D[C];
var B=F.style.color;
if(B){var A=G.replaceTag("font",F);
A.style.color="";
A.setAttribute("color",B)
}}},validateBackgroundColor:function(B){var C=xq.RichDom.createInstance();
C.setRoot(B);
var D=xq.$A(B.getElementsByTagName("FONT")).reverse();
for(var A=0;
A<D.length;
A++){if(D[A].style.color||D[A].style.backgroundColor){C.replaceTag("span",D[A])
}}},invalidateBackgroundColor:function(C){var D=xq.RichDom.createInstance();
D.setRoot(C);
var B=xq.$A(C.getElementsByTagName("SPAN")).reverse();
for(var A=0;
A<B.length;
A++){if(B[A].style.color||B[A].style.backgroundColor){D.replaceTag("font",B[A])
}}},lowerTagNamesAndUniformizeQuotation:function(A){A=A.replace(/<(\/?)(\w+)([^>]*?)>/img,function(E,B,D,C){return"<"+B+D.toLowerCase()+this.correctHtmlAttrQuotation(C)+">"
}.bind(this));
return A
},correctHtmlAttrQuotation:function(A){A=A.replace(/\s(\w+?)=\s+"([^"]+)"/mg,function(D,B,C){return" "+B.toLowerCase()+"=\""+C+"\""
});
A=A.replace(/\s(\w+?)=([^ "]+)/mg,function(D,B,C){return" "+B.toLowerCase()+"=\""+C+"\""
});
return A
}});
xq.EditHistory=xq.Class({initialize:function(B,A){xq.addToFinalizeQueue(this);
if(!B){throw"IllegalArgumentException"
}this.disabled=false;
this.max=A||100;
this.rdom=B;
this.root=B.getRoot();
this.clear();
this.lastModified=Date.get()
},getLastModifiedDate:function(){return this.lastModified
},isUndoable:function(){return this.queue.length>0&&this.index>0
},isRedoable:function(){return this.queue.length>0&&this.index<this.queue.length-1
},disable:function(){this.disabled=true
},enable:function(){this.disabled=false
},undo:function(){this.pushContent();
if(this.isUndoable()){this.index--;
this.popContent();
return true
}else{return false
}},redo:function(){if(this.isRedoable()){this.index++;
this.popContent();
return true
}else{return false
}},onCommand:function(){this.lastModified=Date.get();
if(this.disabled){return false
}return this.pushContent()
},onEvent:function(A){this.lastModified=Date.get();
if(this.disabled){return false
}if("keydown"==A.type&&!(A.ctrlKey||A.metaKey)){return false
}if(["keydown","keyup","keypress"].indexOf(A.type)!=-1&&!A.ctrlKey&&!A.altKey&&!A.metaKey&&[33,34,35,36,37,38,39,40].indexOf(A.keyCode)==-1){return false
}if(["keydown","keyup","keypress"].indexOf(A.type)!=-1&&(A.ctrlKey||A.metaKey)&&[89,90].indexOf(A.keyCode)!=-1){return false
}if([16,17,18,224].indexOf(A.keyCode)!=-1){return false
}return this.pushContent()
},popContent:function(){this.lastModified=Date.get();
var B=this.queue[this.index];
if(B.caret>0){var A=B.html.substring(0,B.caret)+"<span id=\"caret_marker_00700\"></span>"+B.html.substring(B.caret);
this.root.innerHTML=A
}else{this.root.innerHTML=B.html
}this.restoreCaret()
},pushContent:function(B){if(xq.Browser.isTrident&&!B&&!this.rdom.hasFocus()){return false
}if(!this.rdom.getCurrentElement()){return false
}var A=this.root.innerHTML;
if(A==(this.queue[this.index]?this.queue[this.index].html:null)){return false
}var C=B?-1:this.saveCaret();
if(this.queue.length>=this.max){this.queue.shift()
}else{this.index++
}this.queue.splice(this.index,this.queue.length-this.index,{html:A,caret:C});
return true
},clear:function(){this.index=-1;
this.queue=[];
this.pushContent(true)
},saveCaret:function(){if(this.rdom.hasSelection()){return null
}var A=this.rdom.pushMarker();
var C=xq.Browser.isTrident?"<SPAN class="+A.className:"<span class=\""+A.className+"\"";
var B=this.rdom.getRoot().innerHTML.indexOf(C);
this.rdom.popMarker(true);
return B
},restoreCaret:function(){var A=this.rdom.$("caret_marker_00700");
if(A){this.rdom.selectElement(A,true);
this.rdom.collapseSelection(false);
this.rdom.deleteNode(A)
}else{var B=this.rdom.tree.findForward(this.rdom.getRoot(),function(C){return this.isBlock(C)&&!this.hasBlocks(C)
}.bind(this.rdom.tree));
this.rdom.selectElement(B,false);
this.rdom.collapseSelection(false)
}}});
xq.controls={};
xq.controls.FormDialog=xq.Class({initialize:function(D,C,B,A){xq.addToFinalizeQueue(this);
this.xed=D;
this.html=C;
this.onLoadHandler=B||function(){};
this.onCloseHandler=A||function(){};
this.form=null
},show:function(C){C=C||{};
C.position=C.position||"centerOfWindow";
C.mode=C.mode||"modal";
C.cancelOnEsc=C.cancelOnEsc||true;
var B=this;
var A=document.createElement("DIV");
A.style.display="none";
document.body.appendChild(A);
A.innerHTML=this.html;
this.form=A.getElementsByTagName("FORM")[0];
this.form.onsubmit=function(){B.onCloseHandler(xq.serializeForm(this));
B.close();
return false
};
var E=xq.getElementsByClassName(this.form,"cancel")[0];
E.onclick=function(){B.onCloseHandler();
B.close()
};
document.body.appendChild(this.form);
A.parentNode.removeChild(A);
this.setPosition(C.position);
var D=xq.getElementsByClassName(this.form,"initialFocus");
if(D.length>0){D[0].focus()
}if(C.cancelOnEsc){xq.observe(this.form,"keydown",function(F){if(F.keyCode==27){this.onCloseHandler();
this.close()
}return false
}.bind(this))
}this.onLoadHandler(this)
},close:function(){this.form.parentNode.removeChild(this.form)
},setPosition:function(E){var F=null;
var B=0;
var H=0;
if(E=="centerOfWindow"){F=document.documentElement
}else{if(E=="centerOfEditor"){F=this.xed.getFrame();
var A=F;
do{B+=A.offsetLeft;
H+=A.offsetTop
}while(A=A.offsetParent)
}else{if(E=="nearbyCaret"){throw"Not implemented yet"
}else{throw"Invalid argument: "+E
}}}var I=F.clientWidth;
var D=F.clientHeight;
var C=this.form.clientWidth;
var G=this.form.clientHeight;
B+=parseInt((I-C)/2);
H+=parseInt((D-G)/2);
this.form.style.left=B+"px";
this.form.style.top=H+"px"
}});
xq.controls.QuickSearchDialog=xq.Class({initialize:function(A,B){xq.addToFinalizeQueue(this);
this.xed=A;
this.rdom=xq.RichDom.createInstance();
this.rdom.setRoot(document.body);
this.param=B;
if(!this.param.renderItem){this.param.renderItem=function(C){return this.rdom.getInnerText(C)
}.bind(this)
}this.container=null
},getQuery:function(){if(!this.container){return""
}return this._getInputField().value
},onSubmit:function(A){if(this.matchCount()>0){this.param.onSelect(this.xed,this.list[this._getSelectedIndex()])
}this.close();
xq.stopEvent(A);
return false
},onCancel:function(A){if(this.param.onCancel){this.param.onCancel(this.xed)
}this.close()
},onBlur:function(A){setTimeout(function(){this.onCancel(A)
}.bind(this),400)
},onKey:function(C){var B=new xq.Shortcut("ESC");
var D=new xq.Shortcut("ENTER");
var A=new xq.Shortcut("UP");
var E=new xq.Shortcut("DOWN");
if(B.matches(C)){this.onCancel(C)
}else{if(D.matches(C)){this.onSubmit(C)
}else{if(A.matches(C)){this._moveSelectionUp()
}else{if(E.matches(C)){this._moveSelectionDown()
}else{this.updateList()
}}}}},onClick:function(C){var B=C.srcElement||C.target;
if(B.nodeName=="LI"){var A=this._getIndexOfLI(B);
this.param.onSelect(this.xed,this.list[A])
}},onList:function(A){this.list=A;
this.renderList(A)
},updateList:function(){window.setTimeout(function(){this.param.listProvider(this.getQuery(),this.xed,this.onList.bind(this))
}.bind(this),0)
},renderList:function(D){var B=this._getListContainer();
B.innerHTML="";
for(var C=0;
C<D.length;
C++){var A=this.rdom.createElement("LI");
A.innerHTML=this.param.renderItem(D[C]);
B.appendChild(A)
}if(B.hasChildNodes()){B.firstChild.className="selected"
}},show:function(){if(!this.container){this.container=this._create()
}var A=this.rdom.insertNodeAt(this.container,this.rdom.getRoot(),"end");
this.setPosition("centerOfEditor");
this.updateList();
this.focus()
},close:function(){this.rdom.deleteNode(this.container)
},focus:function(){this._getInputField().focus()
},setPosition:function(E){var F=null;
var B=0;
var H=0;
if(E=="centerOfWindow"){F=document.documentElement
}else{if(E=="centerOfEditor"){F=this.xed.getFrame();
var A=F;
do{B+=A.offsetLeft;
H+=A.offsetTop
}while(A=A.offsetParent)
}else{if(E=="nearbyCaret"){throw"Not implemented yet"
}else{throw"Invalid argument: "+E
}}}var I=F.clientWidth;
var D=F.clientHeight;
var C=this.container.clientWidth;
var G=this.container.clientHeight;
B+=parseInt((I-C)/2);
H+=parseInt((D-G)/2);
this.container.style.left=B+"px";
this.container.style.top=H+"px"
},matchCount:function(){return this.list?this.list.length:0
},_create:function(){var A=this.rdom.createElement("DIV");
A.className="xqQuickSearch";
if(this.param.title){var F=this.rdom.createElement("H1");
F.innerHTML=this.param.title;
A.appendChild(F)
}var C=this.rdom.createElement("DIV");
C.className="input";
var D=this.rdom.createElement("FORM");
var B=this.rdom.createElement("INPUT");
B.type="text";
B.value="";
D.appendChild(B);
C.appendChild(D);
A.appendChild(C);
var E=this.rdom.createElement("OL");
xq.observe(B,"blur",this.onBlur.bindAsEventListener(this));
xq.observe(B,"keypress",this.onKey.bindAsEventListener(this));
xq.observe(E,"click",this.onClick.bindAsEventListener(this),true);
xq.observe(D,"submit",this.onSubmit.bindAsEventListener(this));
xq.observe(D,"reset",this.onCancel.bindAsEventListener(this));
A.appendChild(E);
return A
},_getInputField:function(){return this.container.getElementsByTagName("INPUT")[0]
},_getListContainer:function(){return this.container.getElementsByTagName("OL")[0]
},_getSelectedIndex:function(){var A=this._getListContainer();
for(var B=0;
B<A.childNodes.length;
B++){if(A.childNodes[B].className=="selected"){return B
}}},_getIndexOfLI:function(A){var B=this._getListContainer();
for(var C=0;
C<B.childNodes.length;
C++){if(B.childNodes[C]==A){return C
}}},_moveSelectionUp:function(){var C=this.matchCount();
if(C==0){return 
}var B=this._getSelectedIndex();
var A=this._getListContainer();
A.childNodes[B].className="";
B--;
if(B<0){B=C-1
}A.childNodes[B].className="selected"
},_moveSelectionDown:function(){var C=this.matchCount();
if(C==0){return 
}var B=this._getSelectedIndex();
var A=this._getListContainer();
A.childNodes[B].className="";
B++;
if(B>=C){B=0
}A.childNodes[B].className="selected"
}});
if(!xq){xq={}
}if(!xq.ui_templates){xq.ui_templates={}
}xq.ui_templates.basicColorPickerDialog="<form action=\"#\" class=\"xqFormDialog xqBasicColorPickerDialog\">\n\t\t<div>\n\t\t\t<label>\n\t\t\t\t<input type=\"radio\" class=\"initialFocus\" name=\"color\" value=\"black\" checked=\"checked\" />\n\t\t\t\t<span style=\"color: black;\">Black</span>\n\t\t\t</label>\n\t\t\t<label>\n\t\t\t\t<input type=\"radio\" name=\"color\" value=\"red\" />\n\t\t\t\t<span style=\"color: red;\">Red</span>\n\t\t\t</label>\n\t\t\t\t<input type=\"radio\" name=\"color\" value=\"yellow\" />\n\t\t\t\t<span style=\"color: yellow;\">Yellow</span>\n\t\t\t</label>\n\t\t\t</label>\n\t\t\t\t<input type=\"radio\" name=\"color\" value=\"pink\" />\n\t\t\t\t<span style=\"color: pink;\">Pink</span>\n\t\t\t</label>\n\t\t\t<label>\n\t\t\t\t<input type=\"radio\" name=\"color\" value=\"blue\" />\n\t\t\t\t<span style=\"color: blue;\">Blue</span>\n\t\t\t</label>\n\t\t\t<label>\n\t\t\t\t<input type=\"radio\" name=\"color\" value=\"green\" />\n\t\t\t\t<span style=\"color: green;\">Green</span>\n\t\t\t</label>\n\t\t\t\n\t\t\t<input type=\"submit\" value=\"Ok\" />\n\t\t\t<input type=\"button\" class=\"cancel\" value=\"Cancel\" />\n\t\t</div>\n\t</form>";
if(!xq){xq={}
}if(!xq.ui_templates){xq.ui_templates={}
}xq.ui_templates.basicLinkDialog="<form action=\"#\" class=\"xqFormDialog xqBasicLinkDialog\">\n\t\t<h3>Link</h3>\n\t\t<div>\n\t\t\t<input type=\"text\" class=\"initialFocus\" name=\"text\" value=\"\" />\n\t\t\t<input type=\"text\" name=\"url\" value=\"http://\" />\n\t\t\t\n\t\t\t<input type=\"submit\" value=\"Ok\" />\n\t\t\t<input type=\"button\" class=\"cancel\" value=\"Cancel\" />\n\t\t</div>\n\t</form>"
