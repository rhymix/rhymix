/**
 * SmartEditor2 NAVER_Library:SE2.8.2.O11969
 * Copyright NAVER Corp. Licensed under LGPL v2
 * See license text at http://dev.naver.com/projects/smarteditor/wiki/LICENSE
 */
if (typeof window.nhn == 'undefined') {
    window.nhn = {};
}
if (!nhn.husky) {
    nhn.husky = {};
}
/**
 * @fileOverview This file contains Husky framework core
 * @name HuskyCore.js
 */
(function() {
    var _rxMsgHandler = /^\$(LOCAL|BEFORE|ON|AFTER)_/,
        _rxMsgAppReady = /^\$(BEFORE|ON|AFTER)_MSG_APP_READY$/,
        _aHuskyCores = [], // HuskyCore instance list
        _htLoadedFile = {}; // lazy-loaded file list

    nhn.husky.HuskyCore = jindo.$Class({
        name: "HuskyCore",
        aCallerStack: null,
        bMobile: jindo.$Agent().navigator().mobile || jindo.$Agent().navigator().msafari,

        $init: function(htOptions) {
            this.htOptions = htOptions || {};

            _aHuskyCores.push(this);
            if (this.htOptions.oDebugger) {
                nhn.husky.HuskyCore.getCore = function() {
                    return _aHuskyCores;
                };
                this.htOptions.oDebugger.setApp(this);
            }

            // To prevent processing a Husky message before all the plugins are registered and ready,
            // Queue up all the messages here until the application's status is changed to READY
            this.messageQueue = [];

            this.oMessageMap = {};
            this.oDisabledMessage = {};
            this.oLazyMessage = {};
            this.aPlugins = [];

            this.appStatus = nhn.husky.APP_STATUS.NOT_READY;

            this.aCallerStack = [];

            this._fnWaitForPluginReady = jindo.$Fn(this._waitForPluginReady, this).bind();

            // Register the core as a plugin so it can receive messages
            this.registerPlugin(this);
        },

        setDebugger: function(oDebugger) {
            this.htOptions.oDebugger = oDebugger;
            oDebugger.setApp(this);
        },

        exec: function(msg, args, oEvent) {
            // If the application is not yet ready just queue the message
            if (this.appStatus == nhn.husky.APP_STATUS.NOT_READY) {
                this.messageQueue[this.messageQueue.length] = {
                    'msg': msg,
                    'args': args,
                    'event': oEvent
                };
                return true;
            }

            this.exec = this._exec;
            this.exec(msg, args, oEvent);
        },

        delayedExec: function(msg, args, nDelay, oEvent) {
            var fExec = jindo.$Fn(this.exec, this).bind(msg, args, oEvent);
            setTimeout(fExec, nDelay);
        },

        _exec: function(msg, args, oEvent) {
            return (this._exec = this.htOptions.oDebugger ? this._execWithDebugger : this._execWithoutDebugger).call(this, msg, args, oEvent);
        },
        _execWithDebugger: function(msg, args, oEvent) {
            this.htOptions.oDebugger.log_MessageStart(msg, args);
            var bResult = this._doExec(msg, args, oEvent);
            this.htOptions.oDebugger.log_MessageEnd(msg, args);
            return bResult;
        },
        _execWithoutDebugger: function(msg, args, oEvent) {
            return this._doExec(msg, args, oEvent);
        },
        _doExec: function(msg, args, oEvent) {
            var bContinue = false;

            // Lazy메시지가 있으면 파일을 로딩한다.
            if (this.oLazyMessage[msg]) {
                var htLazyInfo = this.oLazyMessage[msg];
                this._loadLazyFiles(msg, args, oEvent, htLazyInfo.aFilenames, 0);
                return false;
            }

            if (!this.oDisabledMessage[msg]) {
                var allArgs = [];
                if (args && args.length) {
                    var iLen = args.length;
                    for (var i = 0; i < iLen; i++) {
                        allArgs[i] = args[i];
                    }
                }
                if (oEvent) {
                    allArgs[allArgs.length] = oEvent;
                }

                bContinue = this._execMsgStep("BEFORE", msg, allArgs);
                if (bContinue) {
                    bContinue = this._execMsgStep("ON", msg, allArgs);
                }
                if (bContinue) {
                    bContinue = this._execMsgStep("AFTER", msg, allArgs);
                }
            }

            return bContinue;
        },


        registerPlugin: function(oPlugin) {
            if (!oPlugin) {
                throw ("An error occured in registerPlugin(): invalid plug-in");
            }

            oPlugin.nIdx = this.aPlugins.length;
            oPlugin.oApp = this;
            this.aPlugins[oPlugin.nIdx] = oPlugin;

            // If the plugin does not specify that it takes time to be ready, change the stauts to READY right away
            if (oPlugin.status != nhn.husky.PLUGIN_STATUS.NOT_READY) {
                oPlugin.status = nhn.husky.PLUGIN_STATUS.READY;
            }

            // If run() function had been called already, need to recreate the message map
            if (this.appStatus != nhn.husky.APP_STATUS.NOT_READY) {
                for (var funcName in oPlugin) {
                    if (_rxMsgHandler.test(funcName)) {
                        this.addToMessageMap(funcName, oPlugin);
                    }
                }
            }

            this.exec("MSG_PLUGIN_REGISTERED", [oPlugin]);

            return oPlugin.nIdx;
        },

        disableMessage: function(sMessage, bDisable) {
            this.oDisabledMessage[sMessage] = bDisable;
        },

        registerBrowserEvent: function(obj, sEvent, sMessage, aParams, nDelay) {
            aParams = aParams || [];
            var func = (nDelay) ? jindo.$Fn(this.delayedExec, this).bind(sMessage, aParams, nDelay) : jindo.$Fn(this.exec, this).bind(sMessage, aParams);
            return jindo.$Fn(func, this).attach(obj, sEvent);
        },

        run: function(htOptions) {
            this.htRunOptions = htOptions || {};

            // Change the status from NOT_READY to let exec to process all the way
            this._changeAppStatus(nhn.husky.APP_STATUS.WAITING_FOR_PLUGINS_READY);

            // Process all the messages in the queue
            var iQueueLength = this.messageQueue.length;
            for (var i = 0; i < iQueueLength; i++) {
                var curMsgAndArgs = this.messageQueue[i];
                this.exec(curMsgAndArgs.msg, curMsgAndArgs.args, curMsgAndArgs.event);
            }

            this._fnWaitForPluginReady();
        },

        acceptLocalBeforeFirstAgain: function(oPlugin, bAccept) {
            // LOCAL_BEFORE_FIRST will be fired again if oPlugin._husky_bRun == false
            oPlugin._husky_bRun = !bAccept;
        },

        // Use this also to update the mapping
        createMessageMap: function(sMsgHandler) {
            this.oMessageMap[sMsgHandler] = [];

            var nLen = this.aPlugins.length;
            for (var i = 0; i < nLen; i++) {
                this._doAddToMessageMap(sMsgHandler, this.aPlugins[i]);
            }
        },

        addToMessageMap: function(sMsgHandler, oPlugin) {
            // cannot "ADD" unless the map is already created.
            // the message will be added automatically to the mapping when it is first passed anyways, so do not add now
            if (!this.oMessageMap[sMsgHandler]) {
                return;
            }

            this._doAddToMessageMap(sMsgHandler, oPlugin);
        },

        _changeAppStatus: function(appStatus) {
            this.appStatus = appStatus;

            // Initiate MSG_APP_READY if the application's status is being switched to READY
            if (this.appStatus == nhn.husky.APP_STATUS.READY) {
                this.exec("MSG_APP_READY");
            }
        },


        _execMsgStep: function(sMsgStep, sMsg, args) {
            return (this._execMsgStep = this.htOptions.oDebugger ? this._execMsgStepWithDebugger : this._execMsgStepWithoutDebugger).call(this, sMsgStep, sMsg, args);
        },
        _execMsgStepWithDebugger: function(sMsgStep, sMsg, args) {
            this.htOptions.oDebugger.log_MessageStepStart(sMsgStep, sMsg, args);
            var bStatus = this._execMsgHandler("$" + sMsgStep + "_" + sMsg, args);
            this.htOptions.oDebugger.log_MessageStepEnd(sMsgStep, sMsg, args);
            return bStatus;
        },
        _execMsgStepWithoutDebugger: function(sMsgStep, sMsg, args) {
            return this._execMsgHandler("$" + sMsgStep + "_" + sMsg, args);
        },
        _execMsgHandler: function(sMsgHandler, args) {
            var i;
            if (!this.oMessageMap[sMsgHandler]) {
                this.createMessageMap(sMsgHandler);
            }

            var aPlugins = this.oMessageMap[sMsgHandler];
            var iNumOfPlugins = aPlugins.length;

            if (iNumOfPlugins === 0) {
                return true;
            }

            var bResult = true;

            // two similar codes were written twice due to the performace.
            if (_rxMsgAppReady.test(sMsgHandler)) {
                for (i = 0; i < iNumOfPlugins; i++) {
                    if (this._execHandler(aPlugins[i], sMsgHandler, args) === false) {
                        bResult = false;
                        break;
                    }
                }
            } else {
                for (i = 0; i < iNumOfPlugins; i++) {
                    if (!aPlugins[i]._husky_bRun) {
                        aPlugins[i]._husky_bRun = true;
                        if (typeof aPlugins[i].$LOCAL_BEFORE_FIRST == "function" && this._execHandler(aPlugins[i], "$LOCAL_BEFORE_FIRST", [sMsgHandler, args]) === false) {
                            continue;
                        }
                    }

                    if (typeof aPlugins[i].$LOCAL_BEFORE_ALL == "function") {
                        if (this._execHandler(aPlugins[i], "$LOCAL_BEFORE_ALL", [sMsgHandler, args]) === false) {
                            continue;
                        }
                    }

                    if (this._execHandler(aPlugins[i], sMsgHandler, args) === false) {
                        bResult = false;
                        break;
                    }
                }
            }

            return bResult;
        },


        _execHandler: function(oPlugin, sHandler, args) {
            return (this._execHandler = this.htOptions.oDebugger ? this._execHandlerWithDebugger : this._execHandlerWithoutDebugger).call(this, oPlugin, sHandler, args);
        },
        _execHandlerWithDebugger: function(oPlugin, sHandler, args) {
            this.htOptions.oDebugger.log_CallHandlerStart(oPlugin, sHandler, args);
            var bResult;
            try {
                this.aCallerStack.push(oPlugin);
                bResult = oPlugin[sHandler].apply(oPlugin, args);
                this.aCallerStack.pop();
            } catch (e) {
                this.htOptions.oDebugger.handleException(e);
                bResult = false;
            }
            this.htOptions.oDebugger.log_CallHandlerEnd(oPlugin, sHandler, args);
            return bResult;
        },
        _execHandlerWithoutDebugger: function(oPlugin, sHandler, args) {
            this.aCallerStack.push(oPlugin);
            var bResult = oPlugin[sHandler].apply(oPlugin, args);
            this.aCallerStack.pop();

            return bResult;
        },


        _doAddToMessageMap: function(sMsgHandler, oPlugin) {
            if (typeof oPlugin[sMsgHandler] != "function") {
                return;
            }

            var aMap = this.oMessageMap[sMsgHandler];
            // do not add if the plugin is already in the mapping
            for (var i = 0, iLen = aMap.length; i < iLen; i++) {
                if (this.oMessageMap[sMsgHandler][i] == oPlugin) {
                    return;
                }
            }
            this.oMessageMap[sMsgHandler][i] = oPlugin;
        },

        _waitForPluginReady: function() {
            var bAllReady = true;
            for (var i = 0; i < this.aPlugins.length; i++) {
                if (this.aPlugins[i].status == nhn.husky.PLUGIN_STATUS.NOT_READY) {
                    bAllReady = false;
                    break;
                }
            }
            if (bAllReady) {
                this._changeAppStatus(nhn.husky.APP_STATUS.READY);
            } else {
                setTimeout(this._fnWaitForPluginReady, 100);
            }
        },

        /**
         * Lazy로딩을 실행한다.
         * @param {Object} oPlugin  플러그인 인스턴스
         * @param {String} sMsg 메시지명
         * @param {Array} aArgs 메시지에 전달되는 매개변수
         * @param {Event} oEvent 메시지에 전달되는 이벤트
         * @param {Array} aFilenames Lazy로딩할 파일명
         * @param {Integer} nIdx 로딩할 파일인덱스
         */
        _loadLazyFiles: function(sMsg, aArgs, oEvent, aFilenames, nIdx) {
            var nLen = aFilenames.length;
            if (nLen <= nIdx) {
                // 파일이 모두 로딩된 상태라면 oLazyMessage 에서 정보를 제거하고 메시지를 실행한다.
                this.oLazyMessage[sMsg] = null;
                this.oApp.exec(sMsg, aArgs, oEvent);
                return;
            }

            var sFilename = aFilenames[nIdx];

            if (_htLoadedFile[sFilename]) {
                // 파일이 이미 로딩된 경우 다음 파일을 로딩한다.
                this._loadLazyFiles(sMsg, aArgs, oEvent, aFilenames, nIdx + 1);
            } else {
                // 파일을 Lazy로딩한다.
                // TODO: 진도컴포넌트 디펜던시 제거?
                // TODO: 응답결과가 정상적이지 않을 경우에 대한 처리?
                jindo.LazyLoading.load(nhn.husky.SE2M_Configuration.LazyLoad.sJsBaseURI + "/" + sFilename,
                    jindo.$Fn(function(sMsg, aArgs, oEvent, aFilenames, nIdx) {
                        // 로딩완료된 파일은 상태를 변경하고
                        var sFilename = aFilenames[nIdx];
                        _htLoadedFile[sFilename] = 1;
                        // 다음 파일을 로딩한다.
                        this._loadLazyFiles(sMsg, aArgs, oEvent, aFilenames, nIdx + 1);
                    }, this).bind(sMsg, aArgs, oEvent, aFilenames, nIdx),
                    "utf-8"
                );
            }
        },

        /**
         * Lazy로딩으로 처리할 메시지를 등록한다.
         * @param {Array} aMsgs 메시지명
         * @param {Array} aFilenames Lazy로딩할 파일명
         */
        registerLazyMessage: function(aMsgs, aFilenames) {
            aMsgs = aMsgs || [];
            aFilenames = aFilenames || [];

            for (var i = 0, sMsg, htLazyInfo;
                (sMsg = aMsgs[i]); i++) {
                htLazyInfo = this.oLazyMessage[sMsg];
                if (htLazyInfo) {
                    htLazyInfo.aFilenames = htLazyInfo.aFilenames.concat(aFilenames);
                } else {
                    this.oLazyMessage[sMsg] = {
                        sMsg: sMsg,
                        aFilenames: aFilenames
                    };
                }
            }
        }
    });

    /**
     * Lazy로딩완료된 파일목록
     */
    nhn.husky.HuskyCore._htLoadedFile = {};
    /**
     * Lazy로딩완료된 파일목록에 파일명을 추가한다.
     * @param {String} sFilename Lazy로딩완료될 경우 마킹할 파일명
     */
    nhn.husky.HuskyCore.addLoadedFile = function(sFilename) {
        _htLoadedFile[sFilename] = 1;
    };
    nhn.husky.APP_STATUS = {
        'NOT_READY': 0,
        'WAITING_FOR_PLUGINS_READY': 1,
        'READY': 2
    };

    nhn.husky.PLUGIN_STATUS = {
        'NOT_READY': 0,
        'READY': 1
    };
})();
if (typeof window.nhn == 'undefined') {
    window.nhn = {};
}

nhn.CurrentSelection_IE = function() {
    this.getCommonAncestorContainer = function() {
        try {
            this._oSelection = this._document.selection;
            if (this._oSelection.type == "Control") {
                return this._oSelection.createRange().item(0);
            } else {
                return this._oSelection.createRangeCollection().item(0).parentElement();
            }
        } catch (e) {
            return this._document.body;
        }
    };

    this.isCollapsed = function() {
        this._oSelection = this._document.selection;

        return this._oSelection.type == "None";
    };
};

nhn.CurrentSelection_FF = function() {
    this.getCommonAncestorContainer = function() {
        return this._getSelection().commonAncestorContainer;
    };

    this.isCollapsed = function() {
        var oSelection = this._window.getSelection();

        if (oSelection.rangeCount < 1) {
            return true;
        }
        return oSelection.getRangeAt(0).collapsed;
    };

    this._getSelection = function() {
        try {
            return this._window.getSelection().getRangeAt(0);
        } catch (e) {
            return this._document.createRange();
        }
    };
};

nhn.CurrentSelection = new(jindo.$Class({
    $init: function() {
        var oAgentInfo = jindo.$Agent().navigator();
        if (oAgentInfo.ie && document.selection) {
            nhn.CurrentSelection_IE.apply(this);
        } else {
            nhn.CurrentSelection_FF.apply(this);
        }
    },

    setWindow: function(oWin) {
        this._window = oWin;
        this._document = oWin.document;
    }
}))();

/**
 * @fileOverview This file contains a cross-browser implementation of W3C's DOM Range
 * @name W3CDOMRange.js
 */
nhn.W3CDOMRange = jindo.$Class({
    $init: function(win) {
        this.reset(win);
    },

    reset: function(win) {
        this._window = win;
        this._document = this._window.document;

        this.collapsed = true;
        this.commonAncestorContainer = this._document.body;
        this.endContainer = this._document.body;
        this.endOffset = 0;
        this.startContainer = this._document.body;
        this.startOffset = 0;

        this.oBrowserSelection = new nhn.BrowserSelection(this._window);
        this.selectionLoaded = this.oBrowserSelection.selectionLoaded;
    },

    cloneContents: function() {
        var oClonedContents = this._document.createDocumentFragment();
        var oTmpContainer = this._document.createDocumentFragment();

        var aNodes = this._getNodesInRange();

        if (aNodes.length < 1) {
            return oClonedContents;
        }

        var oClonedContainers = this._constructClonedTree(aNodes, oTmpContainer);

        // oTopContainer = aNodes[aNodes.length-1].parentNode and this is not part of the initial array and only those child nodes should be cloned
        var oTopContainer = oTmpContainer.firstChild;

        if (oTopContainer) {
            var elCurNode = oTopContainer.firstChild;
            var elNextNode;

            while (elCurNode) {
                elNextNode = elCurNode.nextSibling;
                oClonedContents.appendChild(elCurNode);
                elCurNode = elNextNode;
            }
        }

        oClonedContainers = this._splitTextEndNodes({
            oStartContainer: oClonedContainers.oStartContainer,
            iStartOffset: this.startOffset,
            oEndContainer: oClonedContainers.oEndContainer,
            iEndOffset: this.endOffset
        });

        if (oClonedContainers.oStartContainer && oClonedContainers.oStartContainer.previousSibling) {
            nhn.DOMFix.parentNode(oClonedContainers.oStartContainer).removeChild(oClonedContainers.oStartContainer.previousSibling);
        }

        if (oClonedContainers.oEndContainer && oClonedContainers.oEndContainer.nextSibling) {
            nhn.DOMFix.parentNode(oClonedContainers.oEndContainer).removeChild(oClonedContainers.oEndContainer.nextSibling);
        }

        return oClonedContents;
    },

    _constructClonedTree: function(aNodes, oClonedParentNode) {
        var oClonedStartContainer = null;
        var oClonedEndContainer = null;

        var oStartContainer = this.startContainer;
        var oEndContainer = this.endContainer;

        var _recurConstructClonedTree = function(aAllNodes, iCurIdx, oClonedParentNode) {

            if (iCurIdx < 0) {
                return iCurIdx;
            }

            var iChildIdx = iCurIdx - 1;

            var oCurNodeCloneWithChildren = aAllNodes[iCurIdx].cloneNode(false);

            if (aAllNodes[iCurIdx] == oStartContainer) {
                oClonedStartContainer = oCurNodeCloneWithChildren;
            }
            if (aAllNodes[iCurIdx] == oEndContainer) {
                oClonedEndContainer = oCurNodeCloneWithChildren;
            }

            while (iChildIdx >= 0 && nhn.DOMFix.parentNode(aAllNodes[iChildIdx]) == aAllNodes[iCurIdx]) {
                iChildIdx = this._recurConstructClonedTree(aAllNodes, iChildIdx, oCurNodeCloneWithChildren);
            }

            // this may trigger an error message in IE when an erroneous script is inserted
            oClonedParentNode.insertBefore(oCurNodeCloneWithChildren, oClonedParentNode.firstChild);

            return iChildIdx;
        };
        this._recurConstructClonedTree = _recurConstructClonedTree;
        aNodes[aNodes.length] = nhn.DOMFix.parentNode(aNodes[aNodes.length - 1]);
        this._recurConstructClonedTree(aNodes, aNodes.length - 1, oClonedParentNode);

        return {
            oStartContainer: oClonedStartContainer,
            oEndContainer: oClonedEndContainer
        };
    },

    cloneRange: function() {
        return this._copyRange(new nhn.W3CDOMRange(this._window));
    },

    _copyRange: function(oClonedRange) {
        oClonedRange.collapsed = this.collapsed;
        oClonedRange.commonAncestorContainer = this.commonAncestorContainer;
        oClonedRange.endContainer = this.endContainer;
        oClonedRange.endOffset = this.endOffset;
        oClonedRange.startContainer = this.startContainer;
        oClonedRange.startOffset = this.startOffset;
        oClonedRange._document = this._document;

        return oClonedRange;
    },

    collapse: function(toStart) {
        if (toStart) {
            this.endContainer = this.startContainer;
            this.endOffset = this.startOffset;
        } else {
            this.startContainer = this.endContainer;
            this.startOffset = this.endOffset;
        }

        this._updateRangeInfo();
    },

    compareBoundaryPoints: function(how, sourceRange) {
        switch (how) {
            case nhn.W3CDOMRange.START_TO_START:
                return this._compareEndPoint(this.startContainer, this.startOffset, sourceRange.startContainer, sourceRange.startOffset);
            case nhn.W3CDOMRange.START_TO_END:
                return this._compareEndPoint(this.endContainer, this.endOffset, sourceRange.startContainer, sourceRange.startOffset);
            case nhn.W3CDOMRange.END_TO_END:
                return this._compareEndPoint(this.endContainer, this.endOffset, sourceRange.endContainer, sourceRange.endOffset);
            case nhn.W3CDOMRange.END_TO_START:
                return this._compareEndPoint(this.startContainer, this.startOffset, sourceRange.endContainer, sourceRange.endOffset);
        }
    },

    _findBody: function(oNode) {
        if (!oNode) {
            return null;
        }
        while (oNode) {
            if (oNode.tagName == "BODY") {
                return oNode;
            }
            oNode = nhn.DOMFix.parentNode(oNode);
        }
        return null;
    },

    _compareEndPoint: function(oContainerA, iOffsetA, oContainerB, iOffsetB) {
        return this.oBrowserSelection.compareEndPoints(oContainerA, iOffsetA, oContainerB, iOffsetB);

        var iIdxA, iIdxB;

        if (!oContainerA || this._findBody(oContainerA) != this._document.body) {
            oContainerA = this._document.body;
            iOffsetA = 0;
        }

        if (!oContainerB || this._findBody(oContainerB) != this._document.body) {
            oContainerB = this._document.body;
            iOffsetB = 0;
        }

        var compareIdx = function(iIdxA, iIdxB) {
            // iIdxX == -1 when the node is the commonAncestorNode
            // if iIdxA == -1
            // -> [[<nodeA>...<nodeB></nodeB>]]...</nodeA>
            // if iIdxB == -1
            // -> <nodeB>...[[<nodeA></nodeA>...</nodeB>]]
            if (iIdxB == -1) {
                iIdxB = iIdxA + 1;
            }
            if (iIdxA < iIdxB) {
                return -1;
            }
            if (iIdxA == iIdxB) {
                return 0;
            }
            return 1;
        };

        var oCommonAncestor = this._getCommonAncestorContainer(oContainerA, oContainerB);

        // ================================================================================================================================================
        //  Move up both containers so that both containers are direct child nodes of the common ancestor node. From there, just compare the offset
        // Add 0.5 for each contaienrs that has "moved up" since the actual node is wrapped by 1 or more parent nodes and therefore its position is somewhere between idx & idx+1
        // <COMMON_ANCESTOR>NODE1<P>NODE2</P>NODE3</COMMON_ANCESTOR>
        // The position of NODE2 in COMMON_ANCESTOR is somewhere between after NODE1(idx1) and before NODE3(idx2), so we let that be 1.5

        // container node A in common ancestor container
        var oNodeA = oContainerA;
        var oTmpNode = null;
        if (oNodeA != oCommonAncestor) {
            while ((oTmpNode = xe.DOMFix.parentNode(oNodeA)) != oCommonAncestor) {
                oNodeA = oTmpNode;
            }

            iIdxA = this._getPosIdx(oNodeA) + 0.5;
        } else iIdxA = iOffsetA;

        // container node B in common ancestor container
        var oNodeB = oContainerB;
        if (oNodeB != oCommonAncestor) {
            while ((oTmpNode = xe.DOMFix.parentNode(oNodeB)) != oCommonAncestor) {
                oNodeB = oTmpNode;
            }

            iIdxB = this._getPosIdx(oNodeB) + 0.5;
        } else iIdxB = iOffsetB;

        return compareIdx(iIdxA, iIdxB);
    },

    _getCommonAncestorContainer: function(oNode1, oNode2) {
        oNode1 = oNode1 || this.startContainer;
        oNode2 = oNode2 || this.endContainer;

        var oComparingNode = oNode2;

        while (oNode1) {
            while (oComparingNode) {
                if (oNode1 == oComparingNode) {
                    return oNode1;
                }
                oComparingNode = nhn.DOMFix.parentNode(oComparingNode);
            }
            oComparingNode = oNode2;
            oNode1 = nhn.DOMFix.parentNode(oNode1);
        }

        return this._document.body;
    },

    deleteContents: function() {
        if (this.collapsed) {
            return;
        }

        this._splitTextEndNodesOfTheRange();

        var aNodes = this._getNodesInRange();

        if (aNodes.length < 1) {
            return;
        }
        var oPrevNode = aNodes[0].previousSibling;

        while (oPrevNode && this._isBlankTextNode(oPrevNode)) {
            oPrevNode = oPrevNode.previousSibling;
        }

        var oNewStartContainer, iNewOffset = -1;
        if (!oPrevNode) {
            oNewStartContainer = nhn.DOMFix.parentNode(aNodes[0]);
            iNewOffset = 0;
        }

        for (var i = 0; i < aNodes.length; i++) {
            var oNode = aNodes[i];

            if (!oNode.firstChild || this._isAllChildBlankText(oNode)) {
                if (oNewStartContainer == oNode) {
                    iNewOffset = this._getPosIdx(oNewStartContainer);
                    oNewStartContainer = nhn.DOMFix.parentNode(oNode);
                }
                nhn.DOMFix.parentNode(oNode).removeChild(oNode);
            } else {
                if (oNewStartContainer == oNode && iNewOffset === 0) {
                    iNewOffset = this._getPosIdx(oNewStartContainer);
                    oNewStartContainer = nhn.DOMFix.parentNode(oNode);
                }
            }
        }

        if (!oPrevNode) {
            this.setStart(oNewStartContainer, iNewOffset, true, true);
        } else {
            if (oPrevNode.tagName == "BODY") {
                this.setStartBefore(oPrevNode, true);
            } else {
                this.setStartAfter(oPrevNode, true);
            }
        }

        this.collapse(true);
    },

    extractContents: function() {
        var oClonedContents = this.cloneContents();
        this.deleteContents();
        return oClonedContents;
    },

    getInsertBeforeNodes: function() {
        var oFirstNode = null;

        var oParentContainer;

        if (this.startContainer.nodeType == "3") {
            oParentContainer = nhn.DOMFix.parentNode(this.startContainer);
            if (this.startContainer.nodeValue.length <= this.startOffset) {
                oFirstNode = this.startContainer.nextSibling;
            } else {
                oFirstNode = this.startContainer.splitText(this.startOffset);
            }
        } else {
            oParentContainer = this.startContainer;
            oFirstNode = nhn.DOMFix.childNodes(this.startContainer)[this.startOffset];
        }

        if (!oFirstNode || !nhn.DOMFix.parentNode(oFirstNode)) {
            oFirstNode = null;
        }

        return {
            elParent: oParentContainer,
            elBefore: oFirstNode
        };
    },

    insertNode: function(newNode) {
        var oInsertBefore = this.getInsertBeforeNodes();

        oInsertBefore.elParent.insertBefore(newNode, oInsertBefore.elBefore);

        this.setStartBefore(newNode);
    },

    selectNode: function(refNode) {
        this.reset(this._window);

        this.setStartBefore(refNode);
        this.setEndAfter(refNode);
    },

    selectNodeContents: function(refNode) {
        this.reset(this._window);

        this.setStart(refNode, 0, true);
        this.setEnd(refNode, nhn.DOMFix.childNodes(refNode).length);
    },

    _endsNodeValidation: function(oNode, iOffset) {
        if (!oNode || this._findBody(oNode) != this._document.body) {
            throw new Error("INVALID_NODE_TYPE_ERR oNode is not part of current document");
        }

        if (oNode.nodeType == 3) {
            if (iOffset > oNode.nodeValue.length) {iOffset = oNode.nodeValue.length; }
        } else {
            if (iOffset > nhn.DOMFix.childNodes(oNode).length) {
                iOffset = nhn.DOMFix.childNodes(oNode).length;
            }
        }

        return iOffset;
    },


    setEnd: function(refNode, offset, bSafe, bNoUpdate) {
        if (!bSafe) {
            offset = this._endsNodeValidation(refNode, offset);
        }

        this.endContainer = refNode;
        this.endOffset = offset;

        if (!bNoUpdate) {
            if (!this.startContainer || this._compareEndPoint(this.startContainer, this.startOffset, this.endContainer, this.endOffset) != -1) {
                this.collapse(false);
            } else {
                this._updateRangeInfo();
            }
        }
    },

    setEndAfter: function(refNode, bNoUpdate) {
        if (!refNode) {
            throw new Error("INVALID_NODE_TYPE_ERR in setEndAfter");
        }

        if (refNode.tagName == "BODY") {
            this.setEnd(refNode, nhn.DOMFix.childNodes(refNode).length, true, bNoUpdate);
            return;
        }
        this.setEnd(nhn.DOMFix.parentNode(refNode), this._getPosIdx(refNode) + 1, true, bNoUpdate);
    },

    setEndBefore: function(refNode, bNoUpdate) {
        if (!refNode) {
            throw new Error("INVALID_NODE_TYPE_ERR in setEndBefore");
        }

        if (refNode.tagName == "BODY") {
            this.setEnd(refNode, 0, true, bNoUpdate);
            return;
        }

        this.setEnd(nhn.DOMFix.parentNode(refNode), this._getPosIdx(refNode), true, bNoUpdate);
    },

    setStart: function(refNode, offset, bSafe, bNoUpdate) {
        if (!bSafe) {
            offset = this._endsNodeValidation(refNode, offset);
        }

        this.startContainer = refNode;
        this.startOffset = offset;

        if (!bNoUpdate) {
            if (!this.endContainer || this._compareEndPoint(this.startContainer, this.startOffset, this.endContainer, this.endOffset) != -1) {
                this.collapse(true);
            } else {
                this._updateRangeInfo();
            }
        }
    },

    setStartAfter: function(refNode, bNoUpdate) {
        if (!refNode) {
            throw new Error("INVALID_NODE_TYPE_ERR in setStartAfter");
        }

        if (refNode.tagName == "BODY") {
            this.setStart(refNode, nhn.DOMFix.childNodes(refNode).length, true, bNoUpdate);
            return;
        }

        this.setStart(nhn.DOMFix.parentNode(refNode), this._getPosIdx(refNode) + 1, true, bNoUpdate);
    },

    setStartBefore: function(refNode, bNoUpdate) {
        if (!refNode) {
            throw new Error("INVALID_NODE_TYPE_ERR in setStartBefore");
        }

        if (refNode.tagName == "BODY") {
            this.setStart(refNode, 0, true, bNoUpdate);
            return;
        }
        this.setStart(nhn.DOMFix.parentNode(refNode), this._getPosIdx(refNode), true, bNoUpdate);
    },

    surroundContents: function(newParent) {
        newParent.appendChild(this.extractContents());
        this.insertNode(newParent);
        this.selectNode(newParent);
    },

    toString: function() {
        var oTmpContainer = this._document.createElement("DIV");
        oTmpContainer.appendChild(this.cloneContents());

        return oTmpContainer.textContent || oTmpContainer.innerText || "";
    },

    // this.oBrowserSelection.getCommonAncestorContainer which uses browser's built-in API runs faster but may return an incorrect value.
    // Call this function to fix the problem.
    //
    // In IE, the built-in API would return an incorrect value when,
    // 1. commonAncestorContainer is not selectable
    // AND
    // 2. The selected area will look the same when its child node is selected
    // eg)
    // when <P><SPAN>TEST</SPAN></p> is selected, <SPAN>TEST</SPAN> will be returned as commonAncestorContainer
    fixCommonAncestorContainer: function() {
        if (!jindo.$Agent().navigator().ie) {
            return;
        }

        this.commonAncestorContainer = this._getCommonAncestorContainer();
    },

    _isBlankTextNode: function(oNode) {
        if (oNode.nodeType == 3 && oNode.nodeValue == "") {
            return true;
        }
        return false;
    },

    _isAllChildBlankText: function(elNode) {
        for (var i = 0, nLen = elNode.childNodes.length; i < nLen; i++) {
            if (!this._isBlankTextNode(elNode.childNodes[i])) {
                return false;
            }
        }
        return true;
    },

    _getPosIdx: function(refNode) {
        var idx = 0;
        for (var node = refNode.previousSibling; node; node = node.previousSibling) {
            idx++;
        }

        return idx;
    },

    _updateRangeInfo: function() {
        if (!this.startContainer) {
            this.reset(this._window);
            return;
        }

        // isCollapsed may not function correctly when the cursor is located,
        // (below a table) AND (at the end of the document where there's no P tag or anything else to actually hold the cursor)
        this.collapsed = this.oBrowserSelection.isCollapsed(this) || (this.startContainer === this.endContainer && this.startOffset === this.endOffset);
        //      this.collapsed = this._isCollapsed(this.startContainer, this.startOffset, this.endContainer, this.endOffset);
        this.commonAncestorContainer = this.oBrowserSelection.getCommonAncestorContainer(this);
        //      this.commonAncestorContainer = this._getCommonAncestorContainer(this.startContainer, this.endContainer);
    },

    _isCollapsed: function(oStartContainer, iStartOffset, oEndContainer, iEndOffset) {
        var bCollapsed = false;

        if (oStartContainer == oEndContainer && iStartOffset == iEndOffset) {
            bCollapsed = true;
        } else {
            var oActualStartNode = this._getActualStartNode(oStartContainer, iStartOffset);
            var oActualEndNode = this._getActualEndNode(oEndContainer, iEndOffset);

            // Take the parent nodes on the same level for easier comparison when they're next to each other
            // eg) From
            //  <A>
            //      <B>
            //          <C>
            //          </C>
            //      </B>
            //      <D>
            //          <E>
            //              <F>
            //              </F>
            //          </E>
            //      </D>
            //  </A>
            //  , it's easier to compare the position of B and D rather than C and F because they are siblings
            //
            // If the range were collapsed, oActualEndNode will precede oActualStartNode by doing this
            oActualStartNode = this._getNextNode(this._getPrevNode(oActualStartNode));
            oActualEndNode = this._getPrevNode(this._getNextNode(oActualEndNode));

            if (oActualStartNode && oActualEndNode && oActualEndNode.tagName != "BODY" &&
                (this._getNextNode(oActualEndNode) == oActualStartNode || (oActualEndNode == oActualStartNode && this._isBlankTextNode(oActualEndNode)))
            ) {
                bCollapsed = true;
            }
        }

        return bCollapsed;
    },

    _splitTextEndNodesOfTheRange: function() {
        var oEndPoints = this._splitTextEndNodes({
            oStartContainer: this.startContainer,
            iStartOffset: this.startOffset,
            oEndContainer: this.endContainer,
            iEndOffset: this.endOffset
        });

        this.startContainer = oEndPoints.oStartContainer;
        this.startOffset = oEndPoints.iStartOffset;

        this.endContainer = oEndPoints.oEndContainer;
        this.endOffset = oEndPoints.iEndOffset;
    },

    _splitTextEndNodes: function(oEndPoints) {
        oEndPoints = this._splitStartTextNode(oEndPoints);
        oEndPoints = this._splitEndTextNode(oEndPoints);

        return oEndPoints;
    },

    _splitStartTextNode: function(oEndPoints) {
        var oStartContainer = oEndPoints.oStartContainer;
        var iStartOffset = oEndPoints.iStartOffset;

        var oEndContainer = oEndPoints.oEndContainer;
        var iEndOffset = oEndPoints.iEndOffset;

        if (!oStartContainer) {
            return oEndPoints;
        }
        if (oStartContainer.nodeType != 3) {
            return oEndPoints;
        }
        if (iStartOffset === 0) {
            return oEndPoints;
        }

        if (oStartContainer.nodeValue.length <= iStartOffset) {
            return oEndPoints;
        }

        var oLastPart = oStartContainer.splitText(iStartOffset);

        if (oStartContainer == oEndContainer) {
            iEndOffset -= iStartOffset;
            oEndContainer = oLastPart;
        }
        oStartContainer = oLastPart;
        iStartOffset = 0;

        return {
            oStartContainer: oStartContainer,
            iStartOffset: iStartOffset,
            oEndContainer: oEndContainer,
            iEndOffset: iEndOffset
        };
    },

    _splitEndTextNode: function(oEndPoints) {
        var oStartContainer = oEndPoints.oStartContainer;
        var iStartOffset = oEndPoints.iStartOffset;

        var oEndContainer = oEndPoints.oEndContainer;
        var iEndOffset = oEndPoints.iEndOffset;

        if (!oEndContainer) {
            return oEndPoints;
        }
        if (oEndContainer.nodeType != 3) {
            return oEndPoints;
        }

        if (iEndOffset >= oEndContainer.nodeValue.length) {
            return oEndPoints;
        }
        if (iEndOffset === 0) {
            return oEndPoints;
        }

        oEndContainer.splitText(iEndOffset);

        return {
            oStartContainer: oStartContainer,
            iStartOffset: iStartOffset,
            oEndContainer: oEndContainer,
            iEndOffset: iEndOffset
        };
    },

    _getNodesInRange: function() {
        if (this.collapsed) {
            return [];
        }

        var oStartNode = this._getActualStartNode(this.startContainer, this.startOffset);
        var oEndNode = this._getActualEndNode(this.endContainer, this.endOffset);

        return this._getNodesBetween(oStartNode, oEndNode);
    },

    _getActualStartNode: function(oStartContainer, iStartOffset) {
        var oStartNode = oStartContainer;

        if (oStartContainer.nodeType == 3) {
            if (iStartOffset >= oStartContainer.nodeValue.length) {
                oStartNode = this._getNextNode(oStartContainer);
                if (oStartNode.tagName == "BODY") {
                    oStartNode = null;
                }
            } else {
                oStartNode = oStartContainer;
            }
        } else {
            if (iStartOffset < nhn.DOMFix.childNodes(oStartContainer).length) {
                oStartNode = nhn.DOMFix.childNodes(oStartContainer)[iStartOffset];
            } else {
                oStartNode = this._getNextNode(oStartContainer);
                if (oStartNode.tagName == "BODY") {
                    oStartNode = null;
                }
            }
        }

        return oStartNode;
    },

    _getActualEndNode: function(oEndContainer, iEndOffset) {
        var oEndNode = oEndContainer;

        if (iEndOffset === 0) {
            oEndNode = this._getPrevNode(oEndContainer);
            if (oEndNode.tagName == "BODY") {
                oEndNode = null;
            }
        } else if (oEndContainer.nodeType == 3) {
            oEndNode = oEndContainer;
        } else {
            oEndNode = nhn.DOMFix.childNodes(oEndContainer)[iEndOffset - 1];
        }

        return oEndNode;
    },

    _getNextNode: function(oNode) {
        if (!oNode || oNode.tagName == "BODY") {
            return this._document.body;
        }

        if (oNode.nextSibling) {
            return oNode.nextSibling;
        }

        return this._getNextNode(nhn.DOMFix.parentNode(oNode));
    },

    _getPrevNode: function(oNode) {
        if (!oNode || oNode.tagName == "BODY") {
            return this._document.body;
        }

        if (oNode.previousSibling) {
            return oNode.previousSibling;
        }

        return this._getPrevNode(nhn.DOMFix.parentNode(oNode));
    },

    // includes partially selected
    // for <div id="a"><div id="b"></div></div><div id="c"></div>, _getNodesBetween(b, c) will yield to b, "a" and c
    _getNodesBetween: function(oStartNode, oEndNode) {
        var aNodesBetween = [];
        this._nNodesBetweenLen = 0;

        if (!oStartNode || !oEndNode) {
            return aNodesBetween;
        }

        // IE may throw an exception on "oCurNode = oCurNode.nextSibling;" when oCurNode is 'invalid', not null or undefined but somehow 'invalid'.
        // It happened during browser's build-in UNDO with control range selected(table).
        try {
            this._recurGetNextNodesUntil(oStartNode, oEndNode, aNodesBetween);
        } catch (e) {
            return [];
        }

        return aNodesBetween;
    },

    _recurGetNextNodesUntil: function(oNode, oEndNode, aNodesBetween) {
        if (!oNode) {
            return false;
        }

        if (!this._recurGetChildNodesUntil(oNode, oEndNode, aNodesBetween)) {
            return false;
        }

        var oNextToChk = oNode.nextSibling;

        while (!oNextToChk) {
            if (!(oNode = nhn.DOMFix.parentNode(oNode))) {
                return false;
            }

            aNodesBetween[this._nNodesBetweenLen++] = oNode;

            if (oNode == oEndNode) {
                return false;
            }

            oNextToChk = oNode.nextSibling;
        }

        return this._recurGetNextNodesUntil(oNextToChk, oEndNode, aNodesBetween);
    },

    _recurGetChildNodesUntil: function(oNode, oEndNode, aNodesBetween) {
        if (!oNode) {
            return false;
        }

        var bEndFound = false;
        var oCurNode = oNode;
        if (oCurNode.firstChild) {
            oCurNode = oCurNode.firstChild;
            while (oCurNode) {
                if (!this._recurGetChildNodesUntil(oCurNode, oEndNode, aNodesBetween)) {
                    bEndFound = true;
                    break;
                }
                oCurNode = oCurNode.nextSibling;
            }
        }
        aNodesBetween[this._nNodesBetweenLen++] = oNode;

        if (bEndFound) {
            return false;
        }
        if (oNode == oEndNode) {
            return false;
        }

        return true;
    }
});

nhn.W3CDOMRange.START_TO_START = 0;
nhn.W3CDOMRange.START_TO_END = 1;
nhn.W3CDOMRange.END_TO_END = 2;
nhn.W3CDOMRange.END_TO_START = 3;


/**
 * @fileOverview This file contains a cross-browser function that implements all of the W3C's DOM Range specification and some more
 * @name HuskyRange.js
 */
nhn.HuskyRange = jindo.$Class({
    _rxCursorHolder: /^(?:\uFEFF|\u00A0|\u200B|<br>)$/i,
    _rxTextAlign: /text-align:[^"';]*;?/i,

    setWindow: function(win) {
        this.reset(win || window);
    },

    $init: function(win) {
        this.HUSKY_BOOMARK_START_ID_PREFIX = "husky_bookmark_start_";
        this.HUSKY_BOOMARK_END_ID_PREFIX = "husky_bookmark_end_";

        this.sBlockElement = "P|DIV|LI|H[1-6]|PRE";
        this.sBlockContainer = "BODY|TABLE|TH|TR|TD|UL|OL|BLOCKQUOTE|FORM";

        this.rxBlockElement = new RegExp("^(" + this.sBlockElement + ")$");
        this.rxBlockContainer = new RegExp("^(" + this.sBlockContainer + ")$");
        this.rxLineBreaker = new RegExp("^(" + this.sBlockElement + "|" + this.sBlockContainer + ")$");
        this.rxHasBlock = new RegExp("(?:<(?:" + this.sBlockElement + "|" + this.sBlockContainer + ").*?>|style=[\"']?[^>]*?(?:display\s?:\s?block)[^>]*?[\"']?)", "gi");

        this.setWindow(win);
    },

    select: function() {
        try {
            this.oBrowserSelection.selectRange(this);
        } catch (e) {}
    },

    setFromSelection: function(iNum) {
        this.setRange(this.oBrowserSelection.getRangeAt(iNum), true);
    },

    setRange: function(oW3CRange, bSafe) {
        this.reset(this._window);

        this.setStart(oW3CRange.startContainer, oW3CRange.startOffset, bSafe, true);
        this.setEnd(oW3CRange.endContainer, oW3CRange.endOffset, bSafe);
    },

    setEndNodes: function(oSNode, oENode) {
        this.reset(this._window);

        this.setEndAfter(oENode, true);
        this.setStartBefore(oSNode);
    },

    splitTextAtBothEnds: function() {
        this._splitTextEndNodesOfTheRange();
    },

    getStartNode: function() {
        if (this.collapsed) {
            if (this.startContainer.nodeType == 3) {
                if (this.startOffset === 0) {
                    return null;
                }
                if (this.startContainer.nodeValue.length <= this.startOffset) {
                    return null;
                }
                return this.startContainer;
            }
            return null;
        }

        if (this.startContainer.nodeType == 3) {
            if (this.startOffset >= this.startContainer.nodeValue.length) {
                return this._getNextNode(this.startContainer);
            }
            return this.startContainer;
        } else {
            if (this.startOffset >= nhn.DOMFix.childNodes(this.startContainer).length) {
                return this._getNextNode(this.startContainer);
            }
            return nhn.DOMFix.childNodes(this.startContainer)[this.startOffset];
        }
    },

    getEndNode: function() {
        if (this.collapsed) {
            return this.getStartNode();
        }

        if (this.endContainer.nodeType == 3) {
            if (this.endOffset === 0) {
                return this._getPrevNode(this.endContainer);
            }
            return this.endContainer;
        } else {
            if (this.endOffset === 0) {
                return this._getPrevNode(this.endContainer);
            }
            return nhn.DOMFix.childNodes(this.endContainer)[this.endOffset - 1];
        }
    },

    getNodeAroundRange: function(bBefore, bStrict) {
        if (!this.collapsed) {
            return this.getStartNode();
        }

        if (this.startContainer && this.startContainer.nodeType == 3) {
            return this.startContainer;
        }
        //if(this.collapsed && this.startContainer && this.startContainer.nodeType == 3) return this.startContainer;
        //if(!this.collapsed || (this.startContainer && this.startContainer.nodeType == 3)) return this.getStartNode();

        var oBeforeRange, oAfterRange, oResult;

        if (this.startOffset >= nhn.DOMFix.childNodes(this.startContainer).length) {
            oAfterRange = this._getNextNode(this.startContainer);
        } else {
            oAfterRange = nhn.DOMFix.childNodes(this.startContainer)[this.startOffset];
        }

        if (this.endOffset === 0) {
            oBeforeRange = this._getPrevNode(this.endContainer);
        } else {
            oBeforeRange = nhn.DOMFix.childNodes(this.endContainer)[this.endOffset - 1];
        }

        if (bBefore) {
            oResult = oBeforeRange;
            if (!oResult && !bStrict) {
                oResult = oAfterRange;
            }
        } else {
            oResult = oAfterRange;
            if (!oResult && !bStrict) {
                oResult = oBeforeRange;
            }
        }

        return oResult;
    },

    _getXPath: function(elNode) {
        var sXPath = "";

        while (elNode && elNode.nodeType == 1) {
            sXPath = "/" + elNode.tagName + "[" + this._getPosIdx4XPath(elNode) + "]" + sXPath;
            elNode = nhn.DOMFix.parentNode(elNode);
        }

        return sXPath;
    },

    _getPosIdx4XPath: function(refNode) {
        var idx = 0;
        for (var node = refNode.previousSibling; node; node = node.previousSibling) {
            if (node.tagName == refNode.tagName) {
                idx++;
            }
        }

        return idx;
    },

    // this was written specifically for XPath Bookmark and it may not perform correctly for general purposes
    _evaluateXPath: function(sXPath, oDoc) {
        sXPath = sXPath.substring(1, sXPath.length - 1);
        var aXPath = sXPath.split(/\//);
        var elNode = oDoc.body;

        for (var i = 2; i < aXPath.length && elNode; i++) {
            aXPath[i].match(/([^\[]+)\[(\d+)/i);
            var sTagName = RegExp.$1;
            var nIdx = RegExp.$2;

            var aAllNodes = nhn.DOMFix.childNodes(elNode);
            var aNodes = [];
            var nLength = aAllNodes.length;
            var nCount = 0;
            for (var ii = 0; ii < nLength; ii++) {
                if (aAllNodes[ii].tagName == sTagName) {
                    aNodes[nCount++] = aAllNodes[ii];
                }
            }

            if (aNodes.length < nIdx) {
                elNode = null;
            } else {
                elNode = aNodes[nIdx];
            }
        }

        return elNode;
    },

    _evaluateXPathBookmark: function(oBookmark) {
        var sXPath = oBookmark["sXPath"];
        var nTextNodeIdx = oBookmark["nTextNodeIdx"];
        var nOffset = oBookmark["nOffset"];

        var elContainer = this._evaluateXPath(sXPath, this._document);

        if (nTextNodeIdx > -1 && elContainer) {
            var aChildNodes = nhn.DOMFix.childNodes(elContainer);
            var elNode = null;

            var nIdx = nTextNodeIdx;
            var nOffsetLeft = nOffset;

            while ((elNode = aChildNodes[nIdx]) && elNode.nodeType == 3 && elNode.nodeValue.length < nOffsetLeft) {
                nOffsetLeft -= elNode.nodeValue.length;
                nIdx++;
            }

            elContainer = nhn.DOMFix.childNodes(elContainer)[nIdx];
            nOffset = nOffsetLeft;
        }

        if (!elContainer) {
            elContainer = this._document.body;
            nOffset = 0;
        }
        return {
            elContainer: elContainer,
            nOffset: nOffset
        };
    },

    // this was written specifically for XPath Bookmark and it may not perform correctly for general purposes
    getXPathBookmark: function() {
        var nTextNodeIdx1 = -1;
        var htEndPt1 = {
            elContainer: this.startContainer,
            nOffset: this.startOffset
        };
        var elNode1 = this.startContainer;
        if (elNode1.nodeType == 3) {
            htEndPt1 = this._getFixedStartTextNode();
            nTextNodeIdx1 = this._getPosIdx(htEndPt1.elContainer);
            elNode1 = nhn.DOMFix.parentNode(elNode1);
        }
        var sXPathNode1 = this._getXPath(elNode1);
        var oBookmark1 = {
            sXPath: sXPathNode1,
            nTextNodeIdx: nTextNodeIdx1,
            nOffset: htEndPt1.nOffset
        };

        if (this.collapsed) {
            var oBookmark2 = {
                sXPath: sXPathNode1,
                nTextNodeIdx: nTextNodeIdx1,
                nOffset: htEndPt1.nOffset
            };
        } else {
            var nTextNodeIdx2 = -1;
            var htEndPt2 = {
                elContainer: this.endContainer,
                nOffset: this.endOffset
            };
            var elNode2 = this.endContainer;
            if (elNode2.nodeType == 3) {
                htEndPt2 = this._getFixedEndTextNode();
                nTextNodeIdx2 = this._getPosIdx(htEndPt2.elContainer);
                elNode2 = nhn.DOMFix.parentNode(elNode2);
            }
            var sXPathNode2 = this._getXPath(elNode2);
            var oBookmark2 = {
                sXPath: sXPathNode2,
                nTextNodeIdx: nTextNodeIdx2,
                nOffset: htEndPt2.nOffset
            };
        }
        return [oBookmark1, oBookmark2];
    },

    moveToXPathBookmark: function(aBookmark) {
        if (!aBookmark) {
            return false;
        }

        var oBookmarkInfo1 = this._evaluateXPathBookmark(aBookmark[0]);
        var oBookmarkInfo2 = this._evaluateXPathBookmark(aBookmark[1]);

        if (!oBookmarkInfo1["elContainer"] || !oBookmarkInfo2["elContainer"]) {
            return;
        }

        this.startContainer = oBookmarkInfo1["elContainer"];
        this.startOffset = oBookmarkInfo1["nOffset"];

        this.endContainer = oBookmarkInfo2["elContainer"];
        this.endOffset = oBookmarkInfo2["nOffset"];

        return true;
    },

    _getFixedTextContainer: function(elNode, nOffset) {
        while (elNode && elNode.nodeType == 3 && elNode.previousSibling && elNode.previousSibling.nodeType == 3) {
            nOffset += elNode.previousSibling.nodeValue.length;
            elNode = elNode.previousSibling;
        }

        return {
            elContainer: elNode,
            nOffset: nOffset
        };
    },

    _getFixedStartTextNode: function() {
        return this._getFixedTextContainer(this.startContainer, this.startOffset);
    },

    _getFixedEndTextNode: function() {
        return this._getFixedTextContainer(this.endContainer, this.endOffset);
    },

    placeStringBookmark: function() {
        if (this.collapsed || jindo.$Agent().navigator().ie || jindo.$Agent().navigator().firefox) {
            return this.placeStringBookmark_NonWebkit();
        } else {
            return this.placeStringBookmark_Webkit();
        }
    },

    placeStringBookmark_NonWebkit: function() {
        var sTmpId = (new Date()).getTime();

        var oInsertionPoint = this.cloneRange();
        oInsertionPoint.collapseToEnd();
        var oEndMarker = this._document.createElement("SPAN");
        oEndMarker.id = this.HUSKY_BOOMARK_END_ID_PREFIX + sTmpId;
        oInsertionPoint.insertNode(oEndMarker);

        var oInsertionPoint = this.cloneRange();
        oInsertionPoint.collapseToStart();
        var oStartMarker = this._document.createElement("SPAN");
        oStartMarker.id = this.HUSKY_BOOMARK_START_ID_PREFIX + sTmpId;
        oInsertionPoint.insertNode(oStartMarker);

        // IE에서 빈 SPAN의 앞뒤로 커서가 이동하지 않아 문제가 발생 할 수 있어, 보이지 않는 특수 문자를 임시로 넣어 줌.
        if (jindo.$Agent().navigator().ie) {
            // SPAN의 위치가 TD와 TD 사이에 있을 경우, 텍스트 삽입 시 알수 없는 오류가 발생한다.
            // TD와 TD사이에서는 텍스트 삽입이 필요 없음으로 그냥 try/catch로 처리
            try {
                oStartMarker.innerHTML = unescape("%uFEFF");
            } catch (e) {}

            try {
                oEndMarker.innerHTML = unescape("%uFEFF");
            } catch (e) {}
        }
        this.moveToBookmark(sTmpId);

        return sTmpId;
    },

    placeStringBookmark_Webkit: function() {
        var sTmpId = (new Date()).getTime();

        var elInsertBefore, elInsertParent;

        // Do not insert the bookmarks between TDs as it will break the rendering in Chrome/Safari
        // -> modify the insertion position from [<td>abc</td>]<td>abc</td> to <td>[abc]</td><td>abc</td>
        var oInsertionPoint = this.cloneRange();
        oInsertionPoint.collapseToEnd();
        elInsertBefore = this._document.createTextNode("");
        oInsertionPoint.insertNode(elInsertBefore);
        elInsertParent = elInsertBefore.parentNode;
        if (elInsertBefore.previousSibling && elInsertBefore.previousSibling.tagName == "TD") {
            elInsertParent = elInsertBefore.previousSibling;
            elInsertBefore = null;
        }
        var oEndMarker = this._document.createElement("SPAN");
        oEndMarker.id = this.HUSKY_BOOMARK_END_ID_PREFIX + sTmpId;
        elInsertParent.insertBefore(oEndMarker, elInsertBefore);

        var oInsertionPoint = this.cloneRange();
        oInsertionPoint.collapseToStart();
        elInsertBefore = this._document.createTextNode("");
        oInsertionPoint.insertNode(elInsertBefore);
        elInsertParent = elInsertBefore.parentNode;
        if (elInsertBefore.nextSibling && elInsertBefore.nextSibling.tagName == "TD") {
            elInsertParent = elInsertBefore.nextSibling;
            elInsertBefore = elInsertParent.firstChild;
        }
        var oStartMarker = this._document.createElement("SPAN");
        oStartMarker.id = this.HUSKY_BOOMARK_START_ID_PREFIX + sTmpId;
        elInsertParent.insertBefore(oStartMarker, elInsertBefore);

        //elInsertBefore.parentNode.removeChild(elInsertBefore);

        this.moveToBookmark(sTmpId);

        return sTmpId;
    },

    cloneRange: function() {
        return this._copyRange(new nhn.HuskyRange(this._window));
    },

    moveToBookmark: function(vBookmark) {
        if (typeof(vBookmark) != "object") {
            return this.moveToStringBookmark(vBookmark);
        } else {
            return this.moveToXPathBookmark(vBookmark);
        }
    },

    getStringBookmark: function(sBookmarkID, bEndBookmark) {
        if (bEndBookmark) {
            return this._document.getElementById(this.HUSKY_BOOMARK_END_ID_PREFIX + sBookmarkID);
        } else {
            return this._document.getElementById(this.HUSKY_BOOMARK_START_ID_PREFIX + sBookmarkID);
        }
    },

    moveToStringBookmark: function(sBookmarkID, bIncludeBookmark) {
        var oStartMarker = this.getStringBookmark(sBookmarkID);
        var oEndMarker = this.getStringBookmark(sBookmarkID, true);

        if (!oStartMarker || !oEndMarker) {
            return false;
        }

        this.reset(this._window);

        if (bIncludeBookmark) {
            this.setEndAfter(oEndMarker);
            this.setStartBefore(oStartMarker);
        } else {
            this.setEndBefore(oEndMarker);
            this.setStartAfter(oStartMarker);
        }
        return true;
    },

    removeStringBookmark: function(sBookmarkID) {
        /*
            var oStartMarker = this._document.getElementById(this.HUSKY_BOOMARK_START_ID_PREFIX+sBookmarkID);
            var oEndMarker = this._document.getElementById(this.HUSKY_BOOMARK_END_ID_PREFIX+sBookmarkID);

            if(oStartMarker) nhn.DOMFix.parentNode(oStartMarker).removeChild(oStartMarker);
            if(oEndMarker) nhn.DOMFix.parentNode(oEndMarker).removeChild(oEndMarker);
        */
        this._removeAll(this.HUSKY_BOOMARK_START_ID_PREFIX + sBookmarkID);
        this._removeAll(this.HUSKY_BOOMARK_END_ID_PREFIX + sBookmarkID);
    },

    _removeAll: function(sID) {
        var elNode;
        while ((elNode = this._document.getElementById(sID))) {
            nhn.DOMFix.parentNode(elNode).removeChild(elNode);
        }
    },

    collapseToStart: function() {
        this.collapse(true);
    },

    collapseToEnd: function() {
        this.collapse(false);
    },

    createAndInsertNode: function(sTagName) {
        var tmpNode = this._document.createElement(sTagName);
        this.insertNode(tmpNode);
        return tmpNode;
    },

    getNodes: function(bSplitTextEndNodes, fnFilter) {
        if (bSplitTextEndNodes) {
            this._splitTextEndNodesOfTheRange();
        }

        var aAllNodes = this._getNodesInRange();
        var aFilteredNodes = [];

        if (!fnFilter) {
            return aAllNodes;
        }

        for (var i = 0; i < aAllNodes.length; i++) {
            if (fnFilter(aAllNodes[i])) {
                aFilteredNodes[aFilteredNodes.length] = aAllNodes[i];
            }
        }

        return aFilteredNodes;
    },

    getTextNodes: function(bSplitTextEndNodes) {
        var txtFilter = function(oNode) {
            if (oNode.nodeType == 3 && oNode.nodeValue != "\n" && oNode.nodeValue != "") {
                return true;
            } else {
                return false;
            }
        };

        return this.getNodes(bSplitTextEndNodes, txtFilter);
    },

    surroundContentsWithNewNode: function(sTagName) {
        var oNewParent = this._document.createElement(sTagName);
        this.surroundContents(oNewParent);
        return oNewParent;
    },

    isRangeinRange: function(oAnoterRange, bIncludePartlySelected) {
        var startToStart = this.compareBoundaryPoints(this.W3CDOMRange.START_TO_START, oAnoterRange);
        var startToEnd = this.compareBoundaryPoints(this.W3CDOMRange.START_TO_END, oAnoterRange);
        var endToStart = this.compareBoundaryPoints(this.W3CDOMRange.ND_TO_START, oAnoterRange);
        var endToEnd = this.compareBoundaryPoints(this.W3CDOMRange.END_TO_END, oAnoterRange);

        if (startToStart <= 0 && endToEnd >= 0) {
            return true;
        }

        if (bIncludePartlySelected) {
            if (startToEnd == 1) {
                return false;
            }
            if (endToStart == -1) {
                return false;
            }
            return true;
        }

        return false;
    },

    isNodeInRange: function(oNode, bIncludePartlySelected, bContentOnly) {
        var oTmpRange = new nhn.HuskyRange(this._window);

        if (bContentOnly && oNode.firstChild) {
            oTmpRange.setStartBefore(oNode.firstChild);
            oTmpRange.setEndAfter(oNode.lastChild);
        } else {
            oTmpRange.selectNode(oNode);
        }

        return this.isRangeInRange(oTmpRange, bIncludePartlySelected);
    },

    pasteText: function(sText) {
        this.pasteHTML(sText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/ /g, "&nbsp;").replace(/"/g, "&quot;"));
    },

    /**
     * TODO: 왜 clone 으로 조작할까?
     */
    pasteHTML: function(sHTML) {
        var oTmpDiv = this._document.createElement("DIV");
        oTmpDiv.innerHTML = sHTML;

        if (!oTmpDiv.firstChild) {
            this.deleteContents();
            return;
        }

        // getLineInfo 전에 북마크를 삽입하지 않으면 IE에서 oLineBreaker가 P태그 바깥으로 잡히는 경우가 있음(P태그에 아무것도 없을때)
        var clone = this.cloneRange();
        var sBM = clone.placeStringBookmark();

        // [SMARTEDITORSUS-1960] PrivateTag, 템플릿삽입등 p태그안에 block 요소 삽입과 관련된 처리
        // P태그인 경우, block요소가 들어오면 안된다.
        // 때문에 현재 위치의 컨테이너가 P태그이고 컨텐츠 내용이 block 요소인 경우 P태그를 쪼개고 그 사이에 컨텐츠를 div로 감싸서 넣도록 처리한다.
        var oLineInfo = clone.getLineInfo(),
            oStart = oLineInfo.oStart,
            oEnd = oLineInfo.oEnd;
        if (oStart.oLineBreaker && oStart.oLineBreaker.nodeName === "P" && clone.rxHasBlock.test(sHTML)) {
            // 선택영역을 조작해야 하므로 현재 선택된 요소들을 먼저 제거한다.
            clone.deleteContents();

            var oParentNode = oStart.oLineBreaker.parentNode,
                oNextSibling = oStart.oLineBreaker.nextSibling;
            // 동일한 라인에 있으면 뒷부분은 쪼개서 다음 라인으로 삽입한다.
            if (oStart.oLineBreaker === oEnd.oLineBreaker) {
                var elBM = clone.getStringBookmark(sBM);
                clone.setEndNodes(elBM, oEnd.oLineBreaker);
                var oNextContents = clone.extractContents();

                if (oNextSibling) {
                    oParentNode.insertBefore(oNextContents, oNextSibling);
                } else {
                    oParentNode.appendChild(oNextContents);
                }
                oNextSibling = oStart.oLineBreaker.nextSibling;
            }

            // 선택영역 앞쪽이 속한 P태그에서 style과 align 정보를 복사한다.
            // 크롬의 경우 div의 style 에 text-align 이 있으면 align 속성은 무시되는데
            // div 안의 block 요소는 text-align 의 대상이 아니라 정렬되지 않는 문제가 있기 때문에
            // style 복사할 때 text-align 속성은 제외한다.
            oTmpDiv.style.cssText = oStart.oLineBreaker.style.cssText.replace(this._rxTextAlign, ''); // text-align 제외
            oTmpDiv.align = oStart.oLineBreaker.align; // align 복사
            // 컨텐츠 삽입
            if (oNextSibling) {
                oParentNode.insertBefore(oTmpDiv, oNextSibling);
            } else {
                oParentNode.appendChild(oTmpDiv);
            }

            // 컨텐츠 삽입 후에 북마크를 지운다.
            // 컨텐츠 삽입 전에 지우면 컨텐츠 삽입시 oNextSibling 가 북마크로 잡히는 경우 에러가 발생할 수 있음
            clone.removeStringBookmark(sBM);

            // 컨텐츠 삽입 후 윗라인 P태그에 아무런 내용이 없으면 제거한다.
            this._removeEmptyP(this._getPrevElement(oTmpDiv));
            // 아래 라인 P태그에 아무런 내용이 없는 경우는 그 다음 아래 라인이 있을때만 제거한다.
            // 아래 라인이 아예없으면 IE에서 커서가 들어가지 않기 때문에 라인을 추가해준다.
            var elNextLine = this._getNextElement(oTmpDiv);
            if (elNextLine) {
                var elAfterNext = this._getNextElement(elNextLine);
                if (elAfterNext) {
                    this._removeEmptyP(elNextLine);
                    elNextLine = elAfterNext;
                }
            } else {
                // 아래 라인이 없으면 윗 라인 스타일을 복사하여 추가해준다.
                elNextLine = this._document.createElement("P");
                elNextLine.style.cssText = oStart.oLineBreaker.style.cssText;
                elNextLine.align = oStart.oLineBreaker.align;
                elNextLine.innerHTML = '\uFEFF';
                oParentNode.appendChild(elNextLine);
            }
            // 커서를 다음라인으로 위치시킨다. 그렇지 않으면 div태그와 p태그사이에 커서가 위치하게 된다.
            this.selectNodeContents(elNextLine);
            this.collapseToStart();
        } else {
            var oFirstNode = oTmpDiv.firstChild;
            var oLastNode = oTmpDiv.lastChild;

            this.collapseToStart();

            while (oTmpDiv.lastChild) {
                this.insertNode(oTmpDiv.lastChild);
            }

            this.setEndNodes(oFirstNode, oLastNode);

            // delete the content later as deleting it first may mass up the insertion point
            // eg) <p>[A]BCD</p> ---paste O---> O<p>BCD</p>
            clone.moveToBookmark(sBM);
            clone.deleteContents();
            clone.removeStringBookmark(sBM);
        }
    },

    /**
     * 비어있는 P태그이면 제거한다.
     * @param {Element} el 검사할 Element
     */
    _removeEmptyP: function(el) {
        if (el && el.nodeName === "P") {
            var sInner = el.innerHTML;
            if (sInner === "" || this._rxCursorHolder.test(sInner)) {
                el.parentNode.removeChild(el);
            }
        }
    },

    /**
     * 인접한 Element 노드를 찾는다.
     * @param  {Node}    oNode 기준 노드
     * @param  {Boolean} bPrev 앞뒤여부(true면 앞, false면 뒤)
     * @return {Element} 인접한 Element, 없으면 null 반환
     */
    _getSiblingElement: function(oNode, bPrev) {
        if (!oNode) {
            return null;
        }

        var oSibling = oNode[bPrev ? "previousSibling" : "nextSibling"];
        if (oSibling && oSibling.nodeType === 1) {
            return oSibling;
        } else {
            return arguments.callee(oSibling, bPrev);
        }
    },

    /**
     * 앞쪽 인접한 Element 노드를 찾는다.
     * @param  {Node}    oNode 기준 노드
     * @return {Element} 인접한 Element, 없으면 null 반환
     */
    _getPrevElement: function(oNode) {
        return this._getSiblingElement(oNode, true);
    },

    /**
     * 뒤쪽 인접한 Element 노드를 찾는다.
     * @param  {Node}    oNode 기준 노드
     * @return {Element} 인접한 Element, 없으면 null 반환
     */
    _getNextElement: function(oNode) {
        return this._getSiblingElement(oNode, false);
    },

    toString: function() {
        this.toString = nhn.W3CDOMRange.prototype.toString;
        return this.toString();
    },

    toHTMLString: function() {
        var oTmpContainer = this._document.createElement("DIV");
        oTmpContainer.appendChild(this.cloneContents());

        return oTmpContainer.innerHTML;
    },

    findAncestorByTagName: function(sTagName) {
        var oNode = this.commonAncestorContainer;
        while (oNode && oNode.tagName != sTagName) {
            oNode = nhn.DOMFix.parentNode(oNode);
        }

        return oNode;
    },

    selectNodeContents: function(oNode) {
        if (!oNode) {
            return;
        }

        var oFirstNode = oNode.firstChild ? oNode.firstChild : oNode;
        var oLastNode = oNode.lastChild ? oNode.lastChild : oNode;

        this.reset(this._window);
        if (oFirstNode.nodeType == 3) {
            this.setStart(oFirstNode, 0, true);
        } else {
            this.setStartBefore(oFirstNode);
        }

        if (oLastNode.nodeType == 3) {
            this.setEnd(oLastNode, oLastNode.nodeValue.length, true);
        } else {
            this.setEndAfter(oLastNode);
        }
    },

    /**
     * 노드의 취소선/밑줄 정보를 확인한다
     * 관련 BTS [SMARTEDITORSUS-26]
     * @param {Node}    oNode   취소선/밑줄을 확인할 노드
     * @param {String}  sValue  textDecoration 정보
     * @see nhn.HuskyRange#_checkTextDecoration
     */
    _hasTextDecoration: function(oNode, sValue) {
        if (!oNode || !oNode.style) {
            return false;
        }

        if (oNode.style.textDecoration.indexOf(sValue) > -1) {
            return true;
        }

        if (sValue === "underline" && oNode.tagName === "U") {
            return true;
        }

        if (sValue === "line-through" && (oNode.tagName === "S" || oNode.tagName === "STRIKE")) {
            return true;
        }

        return false;
    },

    /**
     * 노드에 취소선/밑줄을 적용한다
     * 관련 BTS [SMARTEDITORSUS-26]
     * [FF] 노드의 Style 에 textDecoration 을 추가한다
     * [FF 외] U/STRIKE 태그를 추가한다
     * @param {Node}    oNode   취소선/밑줄을 적용할 노드
     * @param {String}  sValue  textDecoration 정보
     * @see nhn.HuskyRange#_checkTextDecoration
     */
    _setTextDecoration: function(oNode, sValue) {
        if (jindo.$Agent().navigator().firefox) { // FF
            oNode.style.textDecoration = (oNode.style.textDecoration) ? oNode.style.textDecoration + " " + sValue : sValue;
        } else {
            if (sValue === "underline") {
                oNode.innerHTML = "<U>" + oNode.innerHTML + "</U>"
            } else if (sValue === "line-through") {
                oNode.innerHTML = "<STRIKE>" + oNode.innerHTML + "</STRIKE>"
            }
        }
    },

    /**
     * 인자로 전달받은 노드 상위의 취소선/밑줄 정보를 확인하여 노드에 적용한다
     * 관련 BTS [SMARTEDITORSUS-26]
     * @param {Node} oNode 취소선/밑줄을 적용할 노드
     */
    _checkTextDecoration: function(oNode) {
        if (oNode.tagName !== "SPAN") {
            return;
        }

        var bUnderline = false,
            bLineThrough = false,
            sTextDecoration = "",
            oParentNode = null;
        oChildNode = oNode.firstChild;

        /* check child */
        while (oChildNode) {
            if (oChildNode.nodeType === 1) {
                bUnderline = (bUnderline || oChildNode.tagName === "U");
                bLineThrough = (bLineThrough || oChildNode.tagName === "S" || oChildNode.tagName === "STRIKE");
            }

            if (bUnderline && bLineThrough) {
                return;
            }

            oChildNode = oChildNode.nextSibling;
        }

        oParentNode = nhn.DOMFix.parentNode(oNode);

        /* check parent */
        while (oParentNode && oParentNode.tagName !== "BODY") {
            if (oParentNode.nodeType !== 1) {
                oParentNode = nhn.DOMFix.parentNode(oParentNode);
                continue;
            }

            if (!bUnderline && this._hasTextDecoration(oParentNode, "underline")) {
                bUnderline = true;
                this._setTextDecoration(oNode, "underline"); // set underline
            }

            if (!bLineThrough && this._hasTextDecoration(oParentNode, "line-through")) {
                bLineThrough = true;
                this._setTextDecoration(oNode, "line-through"); // set line-through
            }

            if (bUnderline && bLineThrough) {
                return;
            }

            oParentNode = nhn.DOMFix.parentNode(oParentNode);
        }
    },

    /**
     * Range에 속한 노드들에 스타일을 적용한다
     * @param {Object}  oStyle                  적용할 스타일을 가지는 Object (예) 글꼴 색 적용의 경우 { color : "#0075c8" }
     * @param {Object}  [oAttribute]            적용할 속성을 가지는 Object (예) 맞춤범 검사의 경우 { _sm2_spchk: "강남콩", class: "se2_check_spell" }
     * @param {String}  [sNewSpanMarker]        새로 추가된 SPAN 노드를 나중에 따로 처리해야하는 경우 마킹을 위해 사용하는 문자열
     * @param {Boolean} [bIncludeLI]            LI 도 스타일 적용에 포함할 것인지의 여부 [COM-1051] _getStyleParentNodes 메서드 참고하기
     * @param {Boolean} [bCheckTextDecoration]  취소선/밑줄 처리를 적용할 것인지 여부 [SMARTEDITORSUS-26] _setTextDecoration 메서드 참고하기
     */
    styleRange: function(oStyle, oAttribute, sNewSpanMarker, bIncludeLI, bCheckTextDecoration) {
        var aStyleParents = this.aStyleParents = this._getStyleParentNodes(sNewSpanMarker, bIncludeLI);
        if (aStyleParents.length < 1) {
            return;
        }

        var sName, sValue;

        for (var i = 0; i < aStyleParents.length; i++) {
            for (var x in oStyle) {
                sName = x;
                sValue = oStyle[sName];

                if (typeof sValue != "string") {
                    continue;
                }

                // [SMARTEDITORSUS-26] 글꼴 색을 적용할 때 취소선/밑줄의 색상도 처리되도록 추가
                if (bCheckTextDecoration && oStyle.color) {
                    this._checkTextDecoration(aStyleParents[i]);
                }

                aStyleParents[i].style[sName] = sValue;
            }

            if (!oAttribute) {
                continue;
            }

            for (var x in oAttribute) {
                sName = x;
                sValue = oAttribute[sName];

                if (typeof sValue != "string") {
                    continue;
                }

                if (sName == "class") {
                    jindo.$Element(aStyleParents[i]).addClass(sValue);
                } else {
                    aStyleParents[i].setAttribute(sName, sValue);
                }
            }
        }

        this.reset(this._window);
        this.setStartBefore(aStyleParents[0]);
        this.setEndAfter(aStyleParents[aStyleParents.length - 1]);
    },

    expandBothEnds: function() {
        this.expandStart();
        this.expandEnd();
    },

    expandStart: function() {
        if (this.startContainer.nodeType == 3 && this.startOffset !== 0) {
            return;
        }

        var elActualStartNode = this._getActualStartNode(this.startContainer, this.startOffset);
        elActualStartNode = this._getPrevNode(elActualStartNode);

        if (elActualStartNode.tagName == "BODY") {
            this.setStartBefore(elActualStartNode);
        } else {
            this.setStartAfter(elActualStartNode);
        }
    },

    expandEnd: function() {
        if (this.endContainer.nodeType == 3 && this.endOffset < this.endContainer.nodeValue.length) {
            return;
        }

        var elActualEndNode = this._getActualEndNode(this.endContainer, this.endOffset);
        elActualEndNode = this._getNextNode(elActualEndNode);

        if (elActualEndNode.tagName == "BODY") {
            this.setEndAfter(elActualEndNode);
        } else {
            this.setEndBefore(elActualEndNode);
        }
    },

    /**
     * Style 을 적용할 노드를 가져온다
     * @param {String}  [sNewSpanMarker]    새로 추가하는 SPAN 노드를 마킹을 위해 사용하는 문자열
     * @param {Boolean} [bIncludeLI]        LI 도 스타일 적용에 포함할 것인지의 여부
     * @return {Array}  Style 을 적용할 노드 배열
     */
    _getStyleParentNodes: function(sNewSpanMarker, bIncludeLI) {
        this._splitTextEndNodesOfTheRange();

        var oSNode = this.getStartNode();
        var oENode = this.getEndNode();

        var aAllNodes = this._getNodesInRange();
        var aResult = [];
        var nResult = 0;

        var oNode, oTmpNode, iStartRelPos, iEndRelPos, oSpan;
        var nInitialLength = aAllNodes.length;
        var arAllBottomNodes = jindo.$A(aAllNodes).filter(function(v) {
            return (!v.firstChild || (bIncludeLI && v.tagName == "LI"));
        });

        // [COM-1051] 본문내용을 한 줄만 입력하고 번호 매긴 상태에서 글자크기를 변경하면 번호크기는 변하지 않는 문제
        // 부모 노드 중 LI 가 있고, 해당 LI 의 모든 자식 노드가 선택된 상태라면 LI에도 스타일을 적용하도록 처리함
        // --- Range 에 LI 가 포함되지 않은 경우, LI 를 포함하도록 처리
        var elTmpNode = this.commonAncestorContainer;
        if (bIncludeLI) {
            while (elTmpNode) {
                if (elTmpNode.tagName == "LI") {
                    if (this._isFullyContained(elTmpNode, arAllBottomNodes)) {
                        aResult[nResult++] = elTmpNode;
                    }
                    break;
                }

                elTmpNode = elTmpNode.parentNode;
            }
        }

        for (var i = 0; i < nInitialLength; i++) {
            oNode = aAllNodes[i];

            if (!oNode) {
                continue;
            }

            // --- Range 에 LI 가 포함된 경우에 대한 LI 확인
            if (bIncludeLI && oNode.tagName == "LI" && this._isFullyContained(oNode, arAllBottomNodes)) {
                aResult[nResult++] = oNode;
                continue;
            }

            if (oNode.nodeType != 3) {
                continue;
            }
            if (oNode.nodeValue == "" || oNode.nodeValue.match(/^(\r|\n)+$/)) {
                continue;
            }

            var oParentNode = nhn.DOMFix.parentNode(oNode);

            // 부모 노드가 SPAN 인 경우에는 새로운 SPAN 을 생성하지 않고 SPAN 을 리턴 배열에 추가함
            if (oParentNode.tagName == "SPAN") {
                if (this._isFullyContained(oParentNode, arAllBottomNodes, oNode)) {
                    aResult[nResult++] = oParentNode;
                    continue;
                }
            } else {
                // [SMARTEDITORSUS-1513] 선택된 영역을 single node로 감싸는 상위 span 노드가 있으면 리턴 배열에 추가
                var oParentSingleSpan = this._findParentSingleSpan(oParentNode);
                if (oParentSingleSpan) {
                    aResult[nResult++] = oParentSingleSpan;
                    continue;
                }
            }

            oSpan = this._document.createElement("SPAN");
            oParentNode.insertBefore(oSpan, oNode);
            oSpan.appendChild(oNode);
            aResult[nResult++] = oSpan;

            if (sNewSpanMarker) {
                oSpan.setAttribute(sNewSpanMarker, "true");
            }
        }

        this.setStartBefore(oSNode);
        this.setEndAfter(oENode);

        return aResult;
    },

    /**
     * [SMARTEDITORSUS-1513][SMARTEDITORSUS-1648] 해당노드가 single child로 묶이는 상위 span 노드가 있는지 찾는다.
     * @param {Node} oNode 검사할 노드
     * @return {Element} 상위 span 노드, 없으면 null
     */
    _findParentSingleSpan: function(oNode) {
        if (!oNode) {
            return null;
        }
        // ZWNBSP 문자가 같이 있는 경우도 있기 때문에 실제 노드를 카운팅해야 함
        for (var i = 0, nCnt = 0, sValue, oChild, aChildNodes = oNode.childNodes;
            (oChild = aChildNodes[i]); i++) {
            sValue = oChild.nodeValue;
            if (this._rxCursorHolder.test(sValue)) {
                continue;
            } else {
                nCnt++;
            }
            if (nCnt > 1) { // 싱글노드가 아니면 더이상 찾지 않고 null 반환
                return null;
            }
        }
        if (oNode.nodeName === "SPAN") {
            return oNode;
        } else {
            return this._findParentSingleSpan(oNode.parentNode);
        }
    },

    /**
     * 컨테이너 엘리먼트(elContainer)의 모든 자식노드가 노드 배열(waAllNodes)에 속하는지 확인한다
     * 첫 번째 자식 노드와 마지막 자식 노드가 노드 배열에 속하는지를 확인한다
     * @param {Element}     elContainer 컨테이너 엘리먼트
     * @param {jindo.$A}    waAllNodes  Node 의 $A 배열
     * @param {Node}        [oNode] 성능을 위한 옵션 노드로 컨테이너의 첫 번째 혹은 마지막 자식 노드와 같으면 indexOf 함수 사용을 줄일 수 있음
     * @return {Array}  Style 을 적용할 노드 배열
     */
    // check if all the child nodes of elContainer are in waAllNodes
    _isFullyContained: function(elContainer, waAllNodes, oNode) {
        var nSIdx, nEIdx;
        var oTmpNode = this._getVeryFirstRealChild(elContainer);
        // do quick checks before trying indexOf() because indexOf() function is very slow
        // oNode is optional
        if (oNode && oTmpNode == oNode) {
            nSIdx = 1;
        } else {
            nSIdx = waAllNodes.indexOf(oTmpNode);
        }

        if (nSIdx != -1) {
            oTmpNode = this._getVeryLastRealChild(elContainer);
            if (oNode && oTmpNode == oNode) {
                nEIdx = 1;
            } else {
                nEIdx = waAllNodes.indexOf(oTmpNode);
            }
        }

        return (nSIdx != -1 && nEIdx != -1);
    },

    _getVeryFirstChild: function(oNode) {
        if (oNode.firstChild) {
            return this._getVeryFirstChild(oNode.firstChild);
        }
        return oNode;
    },

    _getVeryLastChild: function(oNode) {
        if (oNode.lastChild) {
            return this._getVeryLastChild(oNode.lastChild);
        }
        return oNode;
    },

    _getFirstRealChild: function(oNode) {
        var oFirstNode = oNode.firstChild;
        while (oFirstNode && oFirstNode.nodeType == 3 && oFirstNode.nodeValue == "") {
            oFirstNode = oFirstNode.nextSibling;
        }

        return oFirstNode;
    },

    _getLastRealChild: function(oNode) {
        var oLastNode = oNode.lastChild;
        while (oLastNode && oLastNode.nodeType == 3 && oLastNode.nodeValue == "") {
            oLastNode = oLastNode.previousSibling;
        }

        return oLastNode;
    },

    _getVeryFirstRealChild: function(oNode) {
        var oFirstNode = this._getFirstRealChild(oNode);
        if (oFirstNode) {
            return this._getVeryFirstRealChild(oFirstNode);
        }
        return oNode;
    },
    _getVeryLastRealChild: function(oNode) {
        var oLastNode = this._getLastRealChild(oNode);
        if (oLastNode) {
            return this._getVeryLastChild(oLastNode);
        }
        return oNode;
    },

    _getLineStartInfo: function(node) {
        var frontEndFinal = null;
        var frontEnd = node;
        var lineBreaker = node;
        var bParentBreak = false;

        var rxLineBreaker = this.rxLineBreaker;

        // vertical(parent) search
        function getLineStart(node) {
            if (!node) {
                return;
            }
            if (frontEndFinal) {
                return;
            }

            if (rxLineBreaker.test(node.tagName)) {
                lineBreaker = node;
                frontEndFinal = frontEnd;

                bParentBreak = true;

                return;
            } else {
                frontEnd = node;
            }

            getFrontEnd(node.previousSibling);

            if (frontEndFinal) {
                return;
            }
            getLineStart(nhn.DOMFix.parentNode(node));
        }

        // horizontal(sibling) search
        function getFrontEnd(node) {
            if (!node) {
                return;
            }
            if (frontEndFinal) {
                return;
            }

            if (rxLineBreaker.test(node.tagName)) {
                lineBreaker = node;
                frontEndFinal = frontEnd;

                bParentBreak = false;
                return;
            }

            if (node.firstChild && node.tagName != "TABLE") {
                var curNode = node.lastChild;
                while (curNode && !frontEndFinal) {
                    getFrontEnd(curNode);

                    curNode = curNode.previousSibling;
                }
            } else {
                frontEnd = node;
            }

            if (!frontEndFinal) {
                getFrontEnd(node.previousSibling);
            }
        }

        if (rxLineBreaker.test(node.tagName)) {
            frontEndFinal = node;
        } else {
            getLineStart(node);
        }

        return {
            oNode: frontEndFinal,
            oLineBreaker: lineBreaker,
            bParentBreak: bParentBreak
        };
    },

    _getLineEndInfo: function(node) {
        var backEndFinal = null;
        var backEnd = node;
        var lineBreaker = node;
        var bParentBreak = false;

        var rxLineBreaker = this.rxLineBreaker;

        // vertical(parent) search
        function getLineEnd(node) {
            if (!node) {
                return;
            }
            if (backEndFinal) {
                return;
            }

            if (rxLineBreaker.test(node.tagName)) {
                lineBreaker = node;
                backEndFinal = backEnd;

                bParentBreak = true;

                return;
            } else {
                backEnd = node;
            }

            getBackEnd(node.nextSibling);
            if (backEndFinal) {
                return;
            }

            getLineEnd(nhn.DOMFix.parentNode(node));
        }

        // horizontal(sibling) search
        function getBackEnd(node) {
            if (!node) {
                return;
            }
            if (backEndFinal) {
                return;
            }

            if (rxLineBreaker.test(node.tagName)) {
                lineBreaker = node;
                backEndFinal = backEnd;

                bParentBreak = false;

                return;
            }

            if (node.firstChild && node.tagName != "TABLE") {
                var curNode = node.firstChild;
                while (curNode && !backEndFinal) {
                    getBackEnd(curNode);

                    curNode = curNode.nextSibling;
                }
            } else {
                backEnd = node;
            }

            if (!backEndFinal) {
                getBackEnd(node.nextSibling);
            }
        }

        if (rxLineBreaker.test(node.tagName)) {
            backEndFinal = node;
        } else {
            getLineEnd(node);
        }

        return {
            oNode: backEndFinal,
            oLineBreaker: lineBreaker,
            bParentBreak: bParentBreak
        };
    },

    getLineInfo: function(bAfter) {
        var bAfter = bAfter || false;

        var oSNode = this.getStartNode();
        var oENode = this.getEndNode();

        // oSNode && oENode will be null if the range is currently collapsed and the cursor is not located in the middle of a text node.
        if (!oSNode) {
            oSNode = this.getNodeAroundRange(!bAfter, true);
        }
        if (!oENode) {
            oENode = this.getNodeAroundRange(!bAfter, true);
        }

        var oStart = this._getLineStartInfo(oSNode);
        var oStartNode = oStart.oNode;
        var oEnd = this._getLineEndInfo(oENode);
        var oEndNode = oEnd.oNode;

        if (oSNode != oStartNode || oENode != oEndNode) {
            // check if the start node is positioned after the range's ending point
            // or
            // if the end node is positioned before the range's starting point
            var iRelativeStartPos = this._compareEndPoint(nhn.DOMFix.parentNode(oStartNode), this._getPosIdx(oStartNode), this.endContainer, this.endOffset);
            var iRelativeEndPos = this._compareEndPoint(nhn.DOMFix.parentNode(oEndNode), this._getPosIdx(oEndNode) + 1, this.startContainer, this.startOffset);

            if (!(iRelativeStartPos <= 0 && iRelativeEndPos >= 0)) {
                oSNode = this.getNodeAroundRange(false, true);
                oENode = this.getNodeAroundRange(false, true);
                oStart = this._getLineStartInfo(oSNode);
                oEnd = this._getLineEndInfo(oENode);
            }
        }

        return {
            oStart: oStart,
            oEnd: oEnd
        };
    },

    /**
     * 커서홀더나 공백을 제외한 child 노드가 하나만 있는 경우만 node 를 반환한다.
     * @param {Node} oNode 확인할 노드
     * @return {Node} single child node를 반환한다. 없거나 두개 이상이면 null 을 반환
     */
    _findSingleChild: function(oNode) {
        if (!oNode) {
            return null;
        }
        var oSingleChild = null;
        // ZWNBSP 문자가 같이 있는 경우도 있기 때문에 실제 노드를 카운팅해야 함
        for (var i = 0, nCnt = 0, sValue, oChild, aChildNodes = oNode.childNodes;
            (oChild = aChildNodes[i]); i++) {
            sValue = oChild.nodeValue;
            if (this._rxCursorHolder.test(sValue)) {
                continue;
            } else {
                oSingleChild = oChild;
                nCnt++;
            }
            if (nCnt > 1) { // 싱글노드가 아니면 더이상 찾지 않고 null 반환
                return null;
            }
        }
        return oSingleChild;
    },

    /**
     * 해당요소의 최하위까지 검색해 커서홀더만 감싸고 있는지 여부를 반환
     * @param {Node} oNode 확인할 노드
     * @return {Boolean} 커서홀더만 있는 경우 true 반환
     */
    _hasCursorHolderOnly: function(oNode) {
        if (!oNode || oNode.nodeType !== 1) {
            return false;
        }
        if (this._rxCursorHolder.test(oNode.innerHTML)) {
            return true;
        } else {
            return this._hasCursorHolderOnly(this._findSingleChild(oNode));
        }
    }
}).extend(nhn.W3CDOMRange);

/**
 * @fileOverview This file contains cross-browser selection function
 * @name BrowserSelection.js
 */
nhn.BrowserSelection = function(win) {
    this.init = function(win) {
        this._window = win || window;
        this._document = this._window.document;
    };

    this.init(win);

    // [SMARTEDITORSUS-888] IE9 이후로 document.createRange 를 지원
    /*  var oAgentInfo = jindo.$Agent().navigator();
        if(oAgentInfo.ie){
            nhn.BrowserSelectionImpl_IE.apply(this);
        }else{
            nhn.BrowserSelectionImpl_FF.apply(this);
        }*/

    if (!!this._document.createRange) {
        nhn.BrowserSelectionImpl_FF.apply(this);
    } else {
        nhn.BrowserSelectionImpl_IE.apply(this);
    }

    this.selectRange = function(oRng) {
        this.selectNone();
        this.addRange(oRng);
    };

    this.selectionLoaded = true;
    if (!this._oSelection) {
        this.selectionLoaded = false;
    }
};

nhn.BrowserSelectionImpl_FF = function() {
    this._oSelection = this._window.getSelection();

    this.getRangeAt = function(iNum) {
        iNum = iNum || 0;

        try {
            var oFFRange = this._oSelection.getRangeAt(iNum);
        } catch (e) {
            return new nhn.W3CDOMRange(this._window);
        }

        return this._FFRange2W3CRange(oFFRange);
    };

    this.addRange = function(oW3CRange) {
        var oFFRange = this._W3CRange2FFRange(oW3CRange);
        this._oSelection.addRange(oFFRange);
    };

    this.selectNone = function() {
        this._oSelection.removeAllRanges();
    };

    this.getCommonAncestorContainer = function(oW3CRange) {
        var oFFRange = this._W3CRange2FFRange(oW3CRange);
        return oFFRange.commonAncestorContainer;
    };

    this.isCollapsed = function(oW3CRange) {
        var oFFRange = this._W3CRange2FFRange(oW3CRange);
        return oFFRange.collapsed;
    };

    this.compareEndPoints = function(elContainerA, nOffsetA, elContainerB, nOffsetB) {
        var oFFRangeA = this._document.createRange();
        var oFFRangeB = this._document.createRange();
        oFFRangeA.setStart(elContainerA, nOffsetA);
        oFFRangeB.setStart(elContainerB, nOffsetB);
        oFFRangeA.collapse(true);
        oFFRangeB.collapse(true);

        try {
            return oFFRangeA.compareBoundaryPoints(1, oFFRangeB);
        } catch (e) {
            return 1;
        }
    };

    this._FFRange2W3CRange = function(oFFRange) {
        var oW3CRange = new nhn.W3CDOMRange(this._window);

        oW3CRange.setStart(oFFRange.startContainer, oFFRange.startOffset, true);
        oW3CRange.setEnd(oFFRange.endContainer, oFFRange.endOffset, true);

        return oW3CRange;
    };

    this._W3CRange2FFRange = function(oW3CRange) {
        var oFFRange = this._document.createRange();
        oFFRange.setStart(oW3CRange.startContainer, oW3CRange.startOffset);
        oFFRange.setEnd(oW3CRange.endContainer, oW3CRange.endOffset);

        return oFFRange;
    };
};

nhn.BrowserSelectionImpl_IE = function() {
    this._oSelection = this._document.selection;
    this.oLastRange = {
        oBrowserRange: null,
        elStartContainer: null,
        nStartOffset: -1,
        elEndContainer: null,
        nEndOffset: -1
    };

    this._updateLastRange = function(oBrowserRange, oW3CRange) {
        this.oLastRange.oBrowserRange = oBrowserRange;
        this.oLastRange.elStartContainer = oW3CRange.startContainer;
        this.oLastRange.nStartOffset = oW3CRange.startOffset;
        this.oLastRange.elEndContainer = oW3CRange.endContainer;
        this.oLastRange.nEndOffset = oW3CRange.endOffset;
    };

    this.getRangeAt = function(iNum) {
        iNum = iNum || 0;

        var oW3CRange, oBrowserRange;
        if (this._oSelection.type == "Control") {
            oW3CRange = new nhn.W3CDOMRange(this._window);

            var oSelectedNode = this._oSelection.createRange().item(iNum);

            // if the selction occurs in a different document, ignore
            if (!oSelectedNode || oSelectedNode.ownerDocument != this._document) {
                return oW3CRange;
            }

            oW3CRange.selectNode(oSelectedNode);

            return oW3CRange;
        } else {
            //oBrowserRange = this._oSelection.createRangeCollection().item(iNum);
            oBrowserRange = this._oSelection.createRange();

            var oSelectedNode = oBrowserRange.parentElement();

            // if the selction occurs in a different document, ignore
            if (!oSelectedNode || oSelectedNode.ownerDocument != this._document) {
                oW3CRange = new nhn.W3CDOMRange(this._window);
                return oW3CRange;
            }
            oW3CRange = this._IERange2W3CRange(oBrowserRange);

            return oW3CRange;
        }
    };

    this.addRange = function(oW3CRange) {
        var oIERange = this._W3CRange2IERange(oW3CRange);
        oIERange.select();
    };

    this.selectNone = function() {
        this._oSelection.empty();
    };

    this.getCommonAncestorContainer = function(oW3CRange) {
        return this._W3CRange2IERange(oW3CRange).parentElement();
    };

    this.isCollapsed = function(oW3CRange) {
        var oRange = this._W3CRange2IERange(oW3CRange);
        var oRange2 = oRange.duplicate();

        oRange2.collapse();

        return oRange.isEqual(oRange2);
    };

    this.compareEndPoints = function(elContainerA, nOffsetA, elContainerB, nOffsetB) {
        var oIERangeA, oIERangeB;

        if (elContainerA === this.oLastRange.elStartContainer && nOffsetA === this.oLastRange.nStartOffset) {
            oIERangeA = this.oLastRange.oBrowserRange.duplicate();
            oIERangeA.collapse(true);
        } else {
            if (elContainerA === this.oLastRange.elEndContainer && nOffsetA === this.oLastRange.nEndOffset) {
                oIERangeA = this.oLastRange.oBrowserRange.duplicate();
                oIERangeA.collapse(false);
            } else {
                oIERangeA = this._getIERangeAt(elContainerA, nOffsetA);
            }
        }

        if (elContainerB === this.oLastRange.elStartContainer && nOffsetB === this.oLastRange.nStartOffset) {
            oIERangeB = this.oLastRange.oBrowserRange.duplicate();
            oIERangeB.collapse(true);
        } else {
            if (elContainerB === this.oLastRange.elEndContainer && nOffsetB === this.oLastRange.nEndOffset) {
                oIERangeB = this.oLastRange.oBrowserRange.duplicate();
                oIERangeB.collapse(false);
            } else {
                oIERangeB = this._getIERangeAt(elContainerB, nOffsetB);
            }
        }

        return oIERangeA.compareEndPoints("StartToStart", oIERangeB);
    };

    this._W3CRange2IERange = function(oW3CRange) {
        if (this.oLastRange.elStartContainer === oW3CRange.startContainer &&
            this.oLastRange.nStartOffset === oW3CRange.startOffset &&
            this.oLastRange.elEndContainer === oW3CRange.endContainer &&
            this.oLastRange.nEndOffset === oW3CRange.endOffset) {
            return this.oLastRange.oBrowserRange;
        }

        var oStartIERange = this._getIERangeAt(oW3CRange.startContainer, oW3CRange.startOffset);
        var oEndIERange = this._getIERangeAt(oW3CRange.endContainer, oW3CRange.endOffset);
        oStartIERange.setEndPoint("EndToEnd", oEndIERange);

        this._updateLastRange(oStartIERange, oW3CRange);

        return oStartIERange;
    };

    this._getIERangeAt = function(oW3CContainer, iW3COffset) {
        var oIERange = this._document.body.createTextRange();

        var oEndPointInfoForIERange = this._getSelectableNodeAndOffsetForIE(oW3CContainer, iW3COffset);

        var oSelectableNode = oEndPointInfoForIERange.oSelectableNodeForIE;
        var iIEOffset = oEndPointInfoForIERange.iOffsetForIE;

        oIERange.moveToElementText(oSelectableNode);

        oIERange.collapse(oEndPointInfoForIERange.bCollapseToStart);
        oIERange.moveStart("character", iIEOffset);

        return oIERange;
    };

    this._getSelectableNodeAndOffsetForIE = function(oW3CContainer, iW3COffset) {
        //      var oIERange = this._document.body.createTextRange();

        var oNonTextNode = null;
        var aChildNodes = null;
        var iNumOfLeftNodesToCount = 0;

        if (oW3CContainer.nodeType == 3) {
            oNonTextNode = nhn.DOMFix.parentNode(oW3CContainer);
            aChildNodes = nhn.DOMFix.childNodes(oNonTextNode);
            iNumOfLeftNodesToCount = aChildNodes.length;
        } else {
            oNonTextNode = oW3CContainer;
            aChildNodes = nhn.DOMFix.childNodes(oNonTextNode);
            //iNumOfLeftNodesToCount = iW3COffset;
            iNumOfLeftNodesToCount = (iW3COffset < aChildNodes.length) ? iW3COffset : aChildNodes.length;
        }
        //@ room 4 improvement
        var oNodeTester = null;
        var iResultOffset = 0;
        var bCollapseToStart = true;

        for (var i = 0; i < iNumOfLeftNodesToCount; i++) {
            oNodeTester = aChildNodes[i];

            if (oNodeTester.nodeType == 3) {
                if (oNodeTester == oW3CContainer) {
                    break;
                }

                iResultOffset += oNodeTester.nodeValue.length;
            } else {
                //              oIERange.moveToElementText(oNodeTester);
                oNonTextNode = oNodeTester;
                iResultOffset = 0;

                bCollapseToStart = false;
            }
        }

        if (oW3CContainer.nodeType == 3) {
            iResultOffset += iW3COffset;
        }

        return {
            oSelectableNodeForIE: oNonTextNode,
            iOffsetForIE: iResultOffset,
            bCollapseToStart: bCollapseToStart
        };
    };

    this._IERange2W3CRange = function(oIERange) {
        var oW3CRange = new nhn.W3CDOMRange(this._window);

        var oIEPointRange = null;
        var oPosition = null;

        oIEPointRange = oIERange.duplicate();
        oIEPointRange.collapse(true);

        oPosition = this._getW3CContainerAndOffset(oIEPointRange, true);

        oW3CRange.setStart(oPosition.oContainer, oPosition.iOffset, true, true);

        var oCollapsedChecker = oIERange.duplicate();
        oCollapsedChecker.collapse(true);
        if (oCollapsedChecker.isEqual(oIERange)) {
            oW3CRange.collapse(true);
        } else {
            oIEPointRange = oIERange.duplicate();
            oIEPointRange.collapse(false);
            oPosition = this._getW3CContainerAndOffset(oIEPointRange);
            oW3CRange.setEnd(oPosition.oContainer, oPosition.iOffset, true);
        }

        this._updateLastRange(oIERange, oW3CRange);

        return oW3CRange;
    };

    this._getW3CContainerAndOffset = function(oIEPointRange, bStartPt) {
        var oRgOrigPoint = oIEPointRange;

        var oContainer = oRgOrigPoint.parentElement();
        var offset = -1;

        var oRgTester = this._document.body.createTextRange();
        var aChildNodes = nhn.DOMFix.childNodes(oContainer);
        var oPrevNonTextNode = null;
        var pointRangeIdx = 0;

        for (var i = 0; i < aChildNodes.length; i++) {
            if (aChildNodes[i].nodeType == 3) {
                continue;
            }

            oRgTester.moveToElementText(aChildNodes[i]);

            if (oRgTester.compareEndPoints("StartToStart", oIEPointRange) >= 0) {
                break;
            }

            oPrevNonTextNode = aChildNodes[i];
        }

        var pointRangeIdx = i;

        if (pointRangeIdx !== 0 && aChildNodes[pointRangeIdx - 1].nodeType == 3) {
            var oRgTextStart = this._document.body.createTextRange();
            var oCurTextNode = null;
            if (oPrevNonTextNode) {
                oRgTextStart.moveToElementText(oPrevNonTextNode);
                oRgTextStart.collapse(false);
                oCurTextNode = oPrevNonTextNode.nextSibling;
            } else {
                oRgTextStart.moveToElementText(oContainer);
                oRgTextStart.collapse(true);
                oCurTextNode = oContainer.firstChild;
            }

            var oRgTextsUpToThePoint = oRgOrigPoint.duplicate();
            oRgTextsUpToThePoint.setEndPoint("StartToStart", oRgTextStart);

            var textCount = oRgTextsUpToThePoint.text.replace(/[\r\n]/g, "").length;

            while (textCount > oCurTextNode.nodeValue.length && oCurTextNode.nextSibling) {
                textCount -= oCurTextNode.nodeValue.length;
                oCurTextNode = oCurTextNode.nextSibling;
            }

            // this will enforce IE to re-reference oCurTextNode
            var oTmp = oCurTextNode.nodeValue;

            if (bStartPt && oCurTextNode.nextSibling && oCurTextNode.nextSibling.nodeType == 3 && textCount == oCurTextNode.nodeValue.length) {
                textCount -= oCurTextNode.nodeValue.length;
                oCurTextNode = oCurTextNode.nextSibling;
            }

            oContainer = oCurTextNode;
            offset = textCount;
        } else {
            oContainer = oRgOrigPoint.parentElement();
            offset = pointRangeIdx;
        }
        return {
            "oContainer": oContainer,
            "iOffset": offset
        };
    };
};

nhn.DOMFix = new(jindo.$Class({
    $init: function() {
        if (jindo.$Agent().navigator().ie || jindo.$Agent().navigator().opera) {
            this.childNodes = this._childNodes_Fix;
            this.parentNode = this._parentNode_Fix;
        } else {
            this.childNodes = this._childNodes_Native;
            this.parentNode = this._parentNode_Native;
        }
    },

    _parentNode_Native: function(elNode) {
        return elNode.parentNode;
    },

    _parentNode_Fix: function(elNode) {
        if (!elNode) {
            return elNode;
        }

        while (elNode.previousSibling) {
            elNode = elNode.previousSibling;
        }

        return elNode.parentNode;
    },

    _childNodes_Native: function(elNode) {
        return elNode.childNodes;
    },

    _childNodes_Fix: function(elNode) {
        var aResult = null;
        var nCount = 0;

        if (elNode) {
            var aResult = [];
            elNode = elNode.firstChild;
            while (elNode) {
                aResult[nCount++] = elNode;
                elNode = elNode.nextSibling;
            }
        }

        return aResult;
    }
}))();
/*[
 * ADD_APP_PROPERTY
 *
 * 주요 오브젝트를 모든 플러그인에서 this.oApp를 통해서 직접 접근 가능 하도록 등록한다.
 *
 * sPropertyName string 등록명
 * oProperty object 등록시킬 오브젝트
 *
---------------------------------------------------------------------------]*/
/*[
 * REGISTER_BROWSER_EVENT
 *
 * 특정 브라우저 이벤트가 발생 했을때 Husky 메시지를 발생 시킨다.
 *
 * obj HTMLElement 브라우저 이벤트를 발생 시킬 HTML 엘리먼트
 * sEvent string 발생 대기 할 브라우저 이벤트
 * sMsg string 발생 할 Husky 메시지
 * aParams array 메시지에 넘길 파라미터
 * nDelay number 브라우저 이벤트 발생 후 Husky 메시지 발생 사이에 딜레이를 주고 싶을 경우 설정. (1/1000초 단위)
 *
---------------------------------------------------------------------------]*/
/*[
 * DISABLE_MESSAGE
 *
 * 특정 메시지를 코어에서 무시하고 라우팅 하지 않도록 비활성화 한다.
 *
 * sMsg string 비활성화 시킬 메시지
 *
---------------------------------------------------------------------------]*/
/*[
 * ENABLE_MESSAGE
 *
 * 무시하도록 설정된 메시지를 무시하지 않도록 활성화 한다.
 *
 * sMsg string 활성화 시킬 메시지
 *
---------------------------------------------------------------------------]*/
/*[
 * EXEC_ON_READY_FUNCTION
 *
 * oApp.run({fnOnAppReady:fnOnAppReady})와 같이 run 호출 시점에 지정된 함수가 있을 경우 이를 MSG_APP_READY 시점에 실행 시킨다.
 * 코어에서 자동으로 발생시키는 메시지로 직접 발생시키지는 않도록 한다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/**
 * @pluginDesc Husky Framework에서 자주 사용되는 메시지를 처리하는 플러그인
 */
nhn.husky.CorePlugin = jindo.$Class({
    name: "CorePlugin",

    // nStatus = 0(request not sent), 1(request sent), 2(response received)
    // sContents = response
    htLazyLoadRequest_plugins: {},
    htLazyLoadRequest_allFiles: {},

    htHTMLLoaded: {},

    $AFTER_MSG_APP_READY: function() {
        this.oApp.exec("EXEC_ON_READY_FUNCTION", []);
    },

    $ON_ADD_APP_PROPERTY: function(sPropertyName, oProperty) {
        this.oApp[sPropertyName] = oProperty;
    },

    $ON_REGISTER_BROWSER_EVENT: function(obj, sEvent, sMsg, aParams, nDelay) {
        this.oApp.registerBrowserEvent(obj, sEvent, sMsg, aParams, nDelay);
    },

    $ON_DISABLE_MESSAGE: function(sMsg) {
        this.oApp.disableMessage(sMsg, true);
    },

    $ON_ENABLE_MESSAGE: function(sMsg) {
        this.oApp.disableMessage(sMsg, false);
    },

    $ON_LOAD_FULL_PLUGIN: function(aFilenames, sClassName, sMsgName, oThisRef, oArguments) {
        var oPluginRef = oThisRef.$this || oThisRef;
        //      var nIdx = _nIdx||0;

        var sFilename = aFilenames[0];

        if (!this.htLazyLoadRequest_plugins[sFilename]) {
            this.htLazyLoadRequest_plugins[sFilename] = {
                nStatus: 1,
                sContents: ""
            };
        }

        if (this.htLazyLoadRequest_plugins[sFilename].nStatus === 2) {
            //this.oApp.delayedExec("MSG_FULL_PLUGIN_LOADED", [sFilename, sClassName, sMsgName, oThisRef, oArguments, false], 0);
            this.oApp.exec("MSG_FULL_PLUGIN_LOADED", [sFilename, sClassName, sMsgName, oThisRef, oArguments, false]);
        } else {
            this._loadFullPlugin(aFilenames, sClassName, sMsgName, oThisRef, oArguments, 0);
        }
    },

    _loadFullPlugin: function(aFilenames, sClassName, sMsgName, oThisRef, oArguments, nIdx) {
        jindo.LazyLoading.load(nhn.husky.SE2M_Configuration.LazyLoad.sJsBaseURI + "/" + aFilenames[nIdx],
            jindo.$Fn(function(aFilenames, sClassName, sMsgName, oThisRef, oArguments, nIdx) {
                var sCurFilename = aFilenames[nIdx];

                // plugin filename
                var sFilename = aFilenames[0];
                if (nIdx == aFilenames.length - 1) {
                    this.htLazyLoadRequest_plugins[sFilename].nStatus = 2;
                    this.oApp.exec("MSG_FULL_PLUGIN_LOADED", [aFilenames, sClassName, sMsgName, oThisRef, oArguments]);
                    return;
                }
                //this.oApp.exec("LOAD_FULL_PLUGIN", [aFilenames, sClassName, sMsgName, oThisRef, oArguments, nIdx+1]);
                this._loadFullPlugin(aFilenames, sClassName, sMsgName, oThisRef, oArguments, nIdx + 1);
            }, this).bind(aFilenames, sClassName, sMsgName, oThisRef, oArgumRangeents, nIdx),

            "utf-8"
        );
    },

    $ON_MSG_FULL_PLUGIN_LOADED: function(aFilenames, sClassName, sMsgName, oThisRef, oArguments, oRes) {
        // oThisRef.$this는 현재 로드되는 플러그인이 parent 인스턴스일 경우 존재 함. oThisRef.$this는 현재 플러그인(oThisRef)를 parent로 삼고 있는 인스턴스
        // oThisRef에 $this 속성이 없다면 parent가 아닌 일반 인스턴스
        // oPluginRef는 결과적으로 상속 관계가 있다면 자식 인스턴스를 아니라면 일반적인 인스턴스를 가짐
        var oPluginRef = oThisRef.$this || oThisRef;

        var sFilename = aFilenames;

        // now the source code is loaded, remove the loader handlers
        for (var i = 0, nLen = oThisRef._huskyFLT.length; i < nLen; i++) {
            var sLoaderHandlerName = "$BEFORE_" + oThisRef._huskyFLT[i];

            // if child class has its own loader function, remove the loader from current instance(parent) only
            var oRemoveFrom = (oThisRef.$this && oThisRef[sLoaderHandlerName]) ? oThisRef : oPluginRef;
            oRemoveFrom[sLoaderHandlerName] = null;
            this.oApp.createMessageMap(sLoaderHandlerName);
        }

        var oPlugin = eval(sClassName + ".prototype");
        //var oPlugin = eval("new "+sClassName+"()");

        var bAcceptLocalBeforeFirstAgain = false;
        // if there were no $LOCAL_BEFORE_FIRST in already-loaded script, set to accept $LOCAL_BEFORE_FIRST next time as the function could be included in the lazy-loaded script.
        if (typeof oPluginRef["$LOCAL_BEFORE_FIRST"] !== "function") {
            this.oApp.acceptLocalBeforeFirstAgain(oPluginRef, true);
        }

        for (var x in oPlugin) {
            // 자식 인스턴스에 parent를 override하는 함수가 없다면 parent 인스턴스에 함수 복사 해 줌. 이때 함수만 복사하고, 나머지 속성들은 현재 인스턴스에 존재 하지 않을 경우에만 복사.
            if (oThisRef.$this && (!oThisRef[x] || (typeof oPlugin[x] === "function" && x != "constructor"))) {
                oThisRef[x] = jindo.$Fn(oPlugin[x], oPluginRef).bind();
            }

            // 현재 인스턴스에 함수 복사 해 줌. 이때 함수만 복사하고, 나머지 속성들은 현재 인스턴스에 존재 하지 않을 경우에만 복사
            if (oPlugin[x] && (!oPluginRef[x] || (typeof oPlugin[x] === "function" && x != "constructor"))) {
                oPluginRef[x] = oPlugin[x];

                // 새로 추가되는 함수가 메시지 핸들러라면 메시지 매핑에 추가 해 줌
                if (x.match(/^\$(LOCAL|BEFORE|ON|AFTER)_/)) {
                    this.oApp.addToMessageMap(x, oPluginRef);
                }
            }
        }

        if (bAcceptLocalBeforeFirstAgain) {
            this.oApp.acceptLocalBeforeFirstAgain(oPluginRef, true);
        }

        // re-send the message after all the jindo.$super handlers are executed
        if (!oThisRef.$this) {
            this.oApp.exec(sMsgName, oArguments);
        }
    },

    $ON_LOAD_HTML: function(sId) {
        if (this.htHTMLLoaded[sId]) return;

        var elTextarea = jindo.$("_llh_" + sId);
        if (!elTextarea) return;

        this.htHTMLLoaded[sId] = true;

        var elTmp = document.createElement("DIV");
        elTmp.innerHTML = elTextarea.value;

        while (elTmp.firstChild) {
            elTextarea.parentNode.insertBefore(elTmp.firstChild, elTextarea);
        }
    },

    $ON_EXEC_ON_READY_FUNCTION: function() {
        if (typeof this.oApp.htRunOptions.fnOnAppReady == "function") {
            this.oApp.htRunOptions.fnOnAppReady();
        }
    }
});
//{
/**
 * @fileOverview This file contains Husky plugin that bridges the HuskyRange function
 * @name hp_HuskyRangeManager.js
 */
nhn.husky.HuskyRangeManager = jindo.$Class({
    name: "HuskyRangeManager",

    oWindow: null,

    $init: function(win) {
        this.oWindow = win || window;
    },

    $BEFORE_MSG_APP_READY: function() {
        if (this.oWindow && this.oWindow.tagName == "IFRAME") {
            this.oWindow = this.oWindow.contentWindow;
            nhn.CurrentSelection.setWindow(this.oWindow);
        }

        this.oApp.exec("ADD_APP_PROPERTY", ["getSelection", jindo.$Fn(this.getSelection, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getEmptySelection", jindo.$Fn(this.getEmptySelection, this).bind()]);
    },

    $ON_SET_EDITING_WINDOW: function(oWindow) {
        this.oWindow = oWindow;
    },

    getEmptySelection: function(oWindow) {
        var oHuskyRange = new nhn.HuskyRange(oWindow || this.oWindow);
        return oHuskyRange;
    },

    getSelection: function(oWindow) {
        this.oApp.exec("RESTORE_IE_SELECTION", []);

        var oHuskyRange = this.getEmptySelection(oWindow);

        // this may throw an exception if the selected is area is not yet shown
        try {
            oHuskyRange.setFromSelection();
        } catch (e) {}

        return oHuskyRange;
    }
});
//}
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to the tool bar UI
 * @name hp_SE2M_Toolbar.js
 */
nhn.husky.SE2M_Toolbar = jindo.$Class({
    name: "SE2M_Toolbar",

    toolbarArea: null,
    toolbarButton: null,
    uiNameTag: "uiName",

    // 0: unknown
    // 1: all enabled
    // 2: all disabled
    nUIStatus: 1,

    sUIClassPrefix: "husky_seditor_ui_",

    aUICmdMap: null,
    elFirstToolbarItem: null,

    _assignHTMLElements: function(oAppContainer) {
        oAppContainer = jindo.$(oAppContainer) || document;
        this.rxUI = new RegExp(this.sUIClassPrefix + "([^ ]+)");

        //@ec[
        this.toolbarArea = jindo.$$.getSingle(".se2_tool", oAppContainer);
        this.aAllUI = jindo.$$("[class*=" + this.sUIClassPrefix + "]", this.toolbarArea);
        this.elTextTool = jindo.$$.getSingle("div.husky_seditor_text_tool", this.toolbarArea); // [SMARTEDITORSUS-1124] 텍스트 툴바 버튼의 라운드 처리
        //@ec]

        this.welToolbarArea = jindo.$Element(this.toolbarArea);
        for (var i = 0, nCount = this.aAllUI.length; i < nCount; i++) {
            if (this.rxUI.test(this.aAllUI[i].className)) {
                var sUIName = RegExp.$1;
                if (this.htUIList[sUIName] !== undefined) {
                    continue;
                }

                this.htUIList[sUIName] = this.aAllUI[i];
                this.htWrappedUIList[sUIName] = jindo.$Element(this.htUIList[sUIName]);
            }
        }

        if (jindo.$$.getSingle("DIV.se2_icon_tool") != null) {
            this.elFirstToolbarItem = jindo.$$.getSingle("DIV.se2_icon_tool UL.se2_itool1>li>button");
        }
    },

    $LOCAL_BEFORE_FIRST: function(sMsg) {
        var aToolItems = jindo.$$(">ul>li[class*=" + this.sUIClassPrefix + "]>button", this.elTextTool);
        var nItemLength = aToolItems.length;

        this.elFirstToolbarItem = this.elFirstToolbarItem || aToolItems[0];
        this.elLastToolbarItem = aToolItems[nItemLength - 1];

        this.oApp.registerBrowserEvent(this.toolbarArea, "keydown", "NAVIGATE_TOOLBAR", []);
    },

    /**
     * @param {Element} oAppContainer
     * @param {Object} htOptions
     * @param {Array} htOptions.aDisabled 비활성화할 버튼명 배열
     */
    $init: function(oAppContainer, htOptions) {
        this._htOptions = htOptions || {};
        this.htUIList = {};
        this.htWrappedUIList = {};

        this.aUICmdMap = {};
        this._assignHTMLElements(oAppContainer);
    },

    $ON_MSG_APP_READY: function() {
        if (this.oApp.bMobile) {
            this.oApp.registerBrowserEvent(this.toolbarArea, "touchstart", "EVENT_TOOLBAR_TOUCHSTART");
        } else {
            this.oApp.registerBrowserEvent(this.toolbarArea, "mouseover", "EVENT_TOOLBAR_MOUSEOVER");
            this.oApp.registerBrowserEvent(this.toolbarArea, "mouseout", "EVENT_TOOLBAR_MOUSEOUT");
        }
        this.oApp.registerBrowserEvent(this.toolbarArea, "mousedown", "EVENT_TOOLBAR_MOUSEDOWN");

        this.oApp.exec("ADD_APP_PROPERTY", ["getToolbarButtonByUIName", jindo.$Fn(this.getToolbarButtonByUIName, this).bind()]);

        //웹접근성
        //이 단계에서 oAppContainer가 정의되지 않은 상태라서 this.toolbarArea변수값을 사용하지 못하고 아래와 같이 다시 정의하였음.
        var elTool = jindo.$$.getSingle(".se2_tool");
        this.oApp.exec("REGISTER_HOTKEY", ["esc", "FOCUS_EDITING_AREA", [], elTool]);

        // [SMARTEDITORSUS-1679] 초기 disabled 처리가 필요한 버튼은 비활성화
        if (this._htOptions.aDisabled) {
            this._htOptions._sDisabled = "," + this._htOptions.aDisabled.toString() + ","; // 버튼을 활성화할때 비교하기 위한 문자열구성
            this.oApp.exec("DISABLE_UI", [this._htOptions.aDisabled]);
        }
    },


    $ON_NAVIGATE_TOOLBAR: function(weEvent) {

        var TAB_KEY_CODE = 9;
        //이벤트가 발생한 엘리먼트가 마지막 아이템이고 TAB 키가 눌려졌다면
        if ((weEvent.element == this.elLastToolbarItem) && (weEvent.key().keyCode == TAB_KEY_CODE)) {


            if (weEvent.key().shift) {
                //do nothing
            } else {
                this.elFirstToolbarItem.focus();
                weEvent.stopDefault();
            }
        }


        //이벤트가 발생한 엘리먼트가 첫번째 아이템이고 TAB 키가 눌려졌다면
        if (weEvent.element == this.elFirstToolbarItem && (weEvent.key().keyCode == TAB_KEY_CODE)) {
            if (weEvent.key().shift) {
                weEvent.stopDefault();
                this.elLastToolbarItem.focus();
            }
        }
    },


    //포커스가 툴바에 있는 상태에서 단축키를 누르면 에디팅 영역으로 다시 포커스가 가도록 하는 함수. (웹접근성)
    $ON_FOCUS_EDITING_AREA: function() {
        this.oApp.exec("FOCUS");
    },

    $ON_TOGGLE_TOOLBAR_ACTIVE_LAYER: function(elLayer, elBtn, sOpenCmd, aOpenArgs, sCloseCmd, aCloseArgs) {
        this.oApp.exec("TOGGLE_ACTIVE_LAYER", [elLayer, "MSG_TOOLBAR_LAYER_SHOWN", [elLayer, elBtn, sOpenCmd, aOpenArgs], sCloseCmd, aCloseArgs]);
    },

    $ON_MSG_TOOLBAR_LAYER_SHOWN: function(elLayer, elBtn, aOpenCmd, aOpenArgs) {
        this.oApp.exec("POSITION_TOOLBAR_LAYER", [elLayer, elBtn]);
        if (aOpenCmd) {
            this.oApp.exec(aOpenCmd, aOpenArgs);
        }
    },

    $ON_SHOW_TOOLBAR_ACTIVE_LAYER: function(elLayer, sCmd, aArgs, elBtn) {
        this.oApp.exec("SHOW_ACTIVE_LAYER", [elLayer, sCmd, aArgs]);
        this.oApp.exec("POSITION_TOOLBAR_LAYER", [elLayer, elBtn]);
    },

    $ON_ENABLE_UI: function(sUIName) {
        this._enableUI(sUIName);
    },

    /**
     * [SMARTEDITORSUS-1679] 여러개의 버튼을 동시에 비활성화 할 수 있도록 수정
     * @param {String|Array} vUIName 비활성화할 버튼명, 배열일 경우 여러개 동시 적용
     */
    $ON_DISABLE_UI: function(sUIName) {
        if (sUIName instanceof Array) {
            for (var i = 0, sName;
                (sName = sUIName[i]); i++) {
                this._disableUI(sName);
            }
        } else {
            this._disableUI(sUIName);
        }
    },

    $ON_SELECT_UI: function(sUIName) {
        var welUI = this.htWrappedUIList[sUIName];
        if (!welUI) {
            return;
        }
        welUI.removeClass("hover");
        welUI.addClass("active");
    },

    $ON_DESELECT_UI: function(sUIName) {
        var welUI = this.htWrappedUIList[sUIName];
        if (!welUI) {
            return;
        }
        welUI.removeClass("active");
    },

    /**
     * [SMARTEDITORSUS-1646] 툴바버튼 선택상태를 토글링한다.
     * @param {String} sUIName 토글링할 툴바버튼 이름
     */
    $ON_TOGGLE_UI_SELECTED: function(sUIName) {
        var welUI = this.htWrappedUIList[sUIName];
        if (!welUI) {
            return;
        }
        if (welUI.hasClass("active")) {
            welUI.removeClass("active");
        } else {
            welUI.removeClass("hover");
            welUI.addClass("active");
        }
    },

    $ON_ENABLE_ALL_UI: function(htOptions) {
        if (this.nUIStatus === 1) {
            return;
        }

        var sUIName, className;
        htOptions = htOptions || {};
        var waExceptions = jindo.$A(htOptions.aExceptions || []);

        for (sUIName in this.htUIList) {
            if (sUIName && !waExceptions.has(sUIName)) {
                this._enableUI(sUIName);
            }
            //          if(sUIName) this.oApp.exec("ENABLE_UI", [sUIName]);
        }
        //      jindo.$Element(this.toolbarArea).removeClass("off");

        this.nUIStatus = 1;
    },

    $ON_DISABLE_ALL_UI: function(htOptions) {
        if (this.nUIStatus === 2) {
            return;
        }

        var sUIName;
        htOptions = htOptions || {};
        var waExceptions = jindo.$A(htOptions.aExceptions || []);
        var bLeavlActiveLayer = htOptions.bLeaveActiveLayer || false;

        if (!bLeavlActiveLayer) {
            this.oApp.exec("HIDE_ACTIVE_LAYER");
        }

        for (sUIName in this.htUIList) {
            if (sUIName && !waExceptions.has(sUIName)) {
                this._disableUI(sUIName);
            }
            //          if(sUIName) this.oApp.exec("DISABLE_UI", [sUIName]);
        }
        //      jindo.$Element(this.toolbarArea).addClass("off");

        this.nUIStatus = 2;
    },

    $ON_MSG_STYLE_CHANGED: function(sAttributeName, attributeValue) {
        if (attributeValue === "@^") {
            this.oApp.exec("SELECT_UI", [sAttributeName]);
        } else {
            this.oApp.exec("DESELECT_UI", [sAttributeName]);
        }
    },

    $ON_POSITION_TOOLBAR_LAYER: function(elLayer, htOption) {
        var nLayerLeft, nLayerRight, nToolbarLeft, nToolbarRight;

        elLayer = jindo.$(elLayer);
        htOption = htOption || {};
        var elBtn = jindo.$(htOption.elBtn);
        var sAlign = htOption.sAlign;

        var nMargin = -1;
        if (!elLayer) {
            return;
        }
        if (elBtn && elBtn.tagName && elBtn.tagName == "BUTTON") {
            elBtn.parentNode.appendChild(elLayer);
        }

        var welLayer = jindo.$Element(elLayer);

        if (sAlign != "right") {
            elLayer.style.left = "0";

            nLayerLeft = welLayer.offset().left;
            nLayerRight = nLayerLeft + elLayer.offsetWidth;

            nToolbarLeft = this.welToolbarArea.offset().left;
            nToolbarRight = nToolbarLeft + this.toolbarArea.offsetWidth;

            if (nLayerRight > nToolbarRight) {
                welLayer.css("left", (nToolbarRight - nLayerRight - nMargin) + "px");
            }

            if (nLayerLeft < nToolbarLeft) {
                welLayer.css("left", (nToolbarLeft - nLayerLeft + nMargin) + "px");
            }
        } else {
            elLayer.style.right = "0";

            nLayerLeft = welLayer.offset().left;
            nLayerRight = nLayerLeft + elLayer.offsetWidth;

            nToolbarLeft = this.welToolbarArea.offset().left;
            nToolbarRight = nToolbarLeft + this.toolbarArea.offsetWidth;

            if (nLayerRight > nToolbarRight) {
                welLayer.css("right", -1 * (nToolbarRight - nLayerRight - nMargin) + "px");
            }

            if (nLayerLeft < nToolbarLeft) {
                welLayer.css("right", -1 * (nToolbarLeft - nLayerLeft + nMargin) + "px");
            }
        }
    },

    $ON_EVENT_TOOLBAR_MOUSEOVER: function(weEvent) {
        if (this.nUIStatus === 2) {
            return;
        }

        var aAffectedElements = this._getAffectedElements(weEvent.element);
        for (var i = 0; i < aAffectedElements.length; i++) {
            if (!aAffectedElements[i].hasClass("active")) {
                aAffectedElements[i].addClass("hover");
            }
        }
    },

    $ON_EVENT_TOOLBAR_MOUSEOUT: function(weEvent) {
        if (this.nUIStatus === 2) {
            return;
        }
        var aAffectedElements = this._getAffectedElements(weEvent.element);
        for (var i = 0; i < aAffectedElements.length; i++) {
            aAffectedElements[i].removeClass("hover");
        }
    },

    $ON_EVENT_TOOLBAR_MOUSEDOWN: function(weEvent) {
        var elTmp = weEvent.element;
        // Check if the button pressed is in active status and has a visible layer i.e. the button had been clicked and its layer is open already. (buttons like font styles-bold, underline-got no sub layer -> childNodes.length<=2)
        // -> In this case, do not close here(mousedown). The layer will be closed on "click". If we close the layer here, the click event will open it again because it toggles the visibility.
        while (elTmp) {
            if (elTmp.className && elTmp.className.match(/active/) && (elTmp.childNodes.length > 2 || elTmp.parentNode.className.match(/se2_pair/))) {
                return;
            }
            elTmp = elTmp.parentNode;
        }
        this.oApp.exec("HIDE_ACTIVE_LAYER_IF_NOT_CHILD", [weEvent.element]);
    },

    _enableUI: function(sUIName) {
        // [SMARTEDITORSUS-1679] 초기 disabled 설정된 버튼은 skip
        if (this._htOptions._sDisabled && this._htOptions._sDisabled.indexOf("," + sUIName + ",") > -1) {
            return;
        }
        var i, nLen;

        this.nUIStatus = 0;

        var welUI = this.htWrappedUIList[sUIName];
        var elUI = this.htUIList[sUIName];
        if (!welUI) {
            return;
        }
        welUI.removeClass("off");

        var aAllBtns = elUI.getElementsByTagName("BUTTON");
        for (i = 0, nLen = aAllBtns.length; i < nLen; i++) {
            aAllBtns[i].disabled = false;
        }

        // enable related commands
        var sCmd = "";
        if (this.aUICmdMap[sUIName]) {
            for (i = 0; i < this.aUICmdMap[sUIName].length; i++) {
                sCmd = this.aUICmdMap[sUIName][i];
                this.oApp.exec("ENABLE_MESSAGE", [sCmd]);
            }
        }
    },

    _disableUI: function(sUIName) {
        var i, nLen;

        this.nUIStatus = 0;

        var welUI = this.htWrappedUIList[sUIName];
        var elUI = this.htUIList[sUIName];
        if (!welUI) {
            return;
        }
        welUI.addClass("off");
        welUI.removeClass("hover");

        var aAllBtns = elUI.getElementsByTagName("BUTTON");
        for (i = 0, nLen = aAllBtns.length; i < nLen; i++) {
            aAllBtns[i].disabled = true;
        }

        // disable related commands
        var sCmd = "";
        if (this.aUICmdMap[sUIName]) {
            for (i = 0; i < this.aUICmdMap[sUIName].length; i++) {
                sCmd = this.aUICmdMap[sUIName][i];
                this.oApp.exec("DISABLE_MESSAGE", [sCmd]);
            }
        }
    },

    _getAffectedElements: function(el) {
        var elLi, welLi;

        // 버튼 클릭시에 return false를 해 주지 않으면 chrome에서 버튼이 포커스 가져가 버림.
        // 에디터 로딩 시에 일괄처리 할 경우 로딩 속도가 느려짐으로 hover시에 하나씩 처리
        if (!el.bSE2_MDCancelled) {
            el.bSE2_MDCancelled = true;
            var aBtns = el.getElementsByTagName("BUTTON");

            for (var i = 0, nLen = aBtns.length; i < nLen; i++) {
                aBtns[i].onmousedown = function() {
                    return false;
                };
            }
        }

        if (!el || !el.tagName) {
            return [];
        }

        if ((elLi = el).tagName == "BUTTON") {
            // typical button
            // <LI>
            //   <BUTTON>
            if ((elLi = elLi.parentNode) && elLi.tagName == "LI" && this.rxUI.test(elLi.className)) {
                return [jindo.$Element(elLi)];
            }

            // button pair
            // <LI>
            //   <SPAN>
            //     <BUTTON>
            //   <SPAN>
            //     <BUTTON>
            elLi = el;
            if ((elLi = elLi.parentNode.parentNode) && elLi.tagName == "LI" && (welLi = jindo.$Element(elLi)).hasClass("se2_pair")) {
                return [welLi, jindo.$Element(el.parentNode)];
            }

            return [];
        }

        // span in a button
        if ((elLi = el).tagName == "SPAN") {
            // <LI>
            //   <BUTTON>
            //     <SPAN>
            if ((elLi = elLi.parentNode.parentNode) && elLi.tagName == "LI" && this.rxUI.test(elLi.className)) {
                return [jindo.$Element(elLi)];
            }

            // <LI>
            //     <SPAN>
            //글감과 글양식
            if ((elLi = elLi.parentNode) && elLi.tagName == "LI" && this.rxUI.test(elLi.className)) {
                return [jindo.$Element(elLi)];
            }
        }

        return [];
    },

    $ON_REGISTER_UI_EVENT: function(sUIName, sEvent, sCmd, aParams) {
        //[SMARTEDITORSUS-966][IE8 표준/IE 10] 호환 모드를 제거하고 사진 첨부 시 에디팅 영역의
        //                      커서 주위에 <sub><sup> 태그가 붙어서 글자가 매우 작게 되는 현상
        //원인 : 아래의 [SMARTEDITORSUS-901] 수정 내용에서 윗첨자 아랫첨자 이벤트 등록 시
        //해당 플러그인이 마크업에 없으면 this.htUIList에 존재하지 않아 getsingle 사용시 사진첨부에 이벤트가 걸렸음
        //해결 : this.htUIList에 존재하지 않으면 이벤트를 등록하지 않음
        if (!this.htUIList[sUIName]) {
            return;
        }
        // map cmd & ui
        var elButton;
        if (!this.aUICmdMap[sUIName]) {
            this.aUICmdMap[sUIName] = [];
        }
        this.aUICmdMap[sUIName][this.aUICmdMap[sUIName].length] = sCmd;
        //[SMARTEDITORSUS-901]플러그인 태그 코드 추가 시 <li>태그와<button>태그 사이에 개행이 있으면 이벤트가 등록되지 않는 현상
        //원인 : IE9, Chrome, FF, Safari 에서는 태그를 개행 시 그 개행을 text node로 인식하여 firstchild가 text 노드가 되어 버튼 이벤트가 할당되지 않음
        //해결 : firstchild에 이벤트를 거는 것이 아니라, child 중 button 인 것에 이벤트를 걸도록 변경
        elButton = jindo.$$.getSingle('button', this.htUIList[sUIName]);

        if (!elButton) {
            return;
        }
        this.oApp.registerBrowserEvent(elButton, sEvent, sCmd, aParams);
    },

    getToolbarButtonByUIName: function(sUIName) {
        return jindo.$$.getSingle("BUTTON", this.htUIList[sUIName]);
    }
});
//}
/**
 * @name nhn.husky.SE2B_Customize_ToolBar
 * @description 메일 전용 커스터마이즈 툴바로 더보기 레이어 관리만을 담당하고 있음.
 * @class
 * @author HyeKyoung,NHN AjaxUI Lab, CMD Division
 * @version 0.1.0
 * @since
 */

nhn.husky.SE2B_Customize_ToolBar = jindo.$Class( /** @lends nhn.husky.SE2B_Customize_ToolBar */ {
    name: "SE2B_Customize_ToolBar",
    /**
     * @constructs
     * @param {Object} oAppContainer 에디터를 구성하는 컨테이너
     */
    $init: function(oAppContainer) {
        this._assignHTMLElements(oAppContainer);
    },
    $BEFORE_MSG_APP_READY: function() {
        this._addEventMoreButton();
    },

    /**
     * @private
     * @description DOM엘리먼트를 수집하는 메소드
     * @param {Object} oAppContainer 툴바 포함 에디터를 감싸고 있는 div 엘리먼트
     */
    _assignHTMLElements: function(oAppContainer) {
        this.oAppContainer = oAppContainer;
        this.elTextToolBarArea = jindo.$$.getSingle("div.se2_tool");
        this.elTextMoreButton = jindo.$$.getSingle("button.se2_text_tool_more", this.elTextToolBarArea);
        this.elTextMoreButtonParent = this.elTextMoreButton.parentNode;
        this.welTextMoreButtonParent = jindo.$Element(this.elTextMoreButtonParent);
        this.elMoreLayer = jindo.$$.getSingle("div.se2_sub_text_tool");
    },

    _addEventMoreButton: function() {
        this.oApp.registerBrowserEvent(this.elTextMoreButton, "click", "EVENT_CLICK_EXPAND_VIEW");
        this.oApp.registerBrowserEvent(this.elMoreLayer, "click", "EVENT_CLICK_EXPAND_VIEW");
    },

    $ON_EVENT_CLICK_EXPAND_VIEW: function(weEvent) {
        this.oApp.exec("TOGGLE_EXPAND_VIEW", [this.elTextMoreButton]);
        weEvent.stop();
    },

    $ON_TOGGLE_EXPAND_VIEW: function() {
        if (!this.welTextMoreButtonParent.hasClass("active")) {
            this.oApp.exec("SHOW_EXPAND_VIEW");
        } else {
            this.oApp.exec("HIDE_EXPAND_VIEW");
        }
    },

    $ON_CHANGE_EDITING_MODE: function(sMode) {
        if (sMode != "WYSIWYG") {
            this.elTextMoreButton.disabled = true;
            this.welTextMoreButtonParent.removeClass("active");
            this.oApp.exec("HIDE_EXPAND_VIEW");
        } else {
            this.elTextMoreButton.disabled = false;
        }
    },

    $AFTER_SHOW_ACTIVE_LAYER: function() {
        this.oApp.exec("HIDE_EXPAND_VIEW");
    },

    $AFTER_SHOW_DIALOG_LAYER: function() {
        this.oApp.exec("HIDE_EXPAND_VIEW");
    },

    $ON_SHOW_EXPAND_VIEW: function() {
        this.welTextMoreButtonParent.addClass("active");
        this.elMoreLayer.style.display = "block";
    },

    $ON_HIDE_EXPAND_VIEW: function() {
        this.welTextMoreButtonParent.removeClass("active");
        this.elMoreLayer.style.display = "none";
    },

    /**
     * CHANGE_EDITING_MODE모드 이후에 호출되어야 함.
     * WYSIWYG 모드가 활성화되기 전에 호출이 되면 APPLY_FONTCOLOR에서 에러 발생.
     */
    $ON_RESET_TOOLBAR: function() {
        if (this.oApp.getEditingMode() !== "WYSIWYG") {
            return;
        }
        //스펠체크 닫기
        this.oApp.exec("END_SPELLCHECK");
        //열린 팝업을 닫기 위해서
        this.oApp.exec("DISABLE_ALL_UI");
        this.oApp.exec("ENABLE_ALL_UI");
        //글자색과 글자 배경색을 제외한 세팅
        this.oApp.exec("RESET_STYLE_STATUS");
        this.oApp.exec("CHECK_STYLE_CHANGE");
        //최근 사용한 글자색 셋팅.
        this.oApp.exec("APPLY_FONTCOLOR", ["#000000"]);
        //더보기 영역 닫기.
        this.oApp.exec("HIDE_EXPAND_VIEW");
    }
});
/*[
 * LOAD_CONTENTS_FIELD
 *
 * 에디터 초기화 시에 넘어온 Contents(DB 저장 값)필드를 읽어 에디터에 설정한다.
 *
 * bDontAddUndo boolean Contents를 설정하면서 UNDO 히스토리는 추가 하지않는다.
 *
---------------------------------------------------------------------------]*/
/*[
 * UPDATE_IR_FIELD
 *
 * 에디터의 IR값을 IR필드에 설정 한다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * CHANGE_EDITING_MODE
 *
 * 에디터의 편집 모드를 변경한다.
 *
 * sMode string 전환 할 모드명
 * bNoFocus boolean 모드 전환 후에 에디터에 포커스를 강제로 할당하지 않는다.
 *
---------------------------------------------------------------------------]*/
/*[
 * FOCUS
 *
 * 에디터 편집 영역에 포커스를 준다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * SET_IR
 *
 * IR값을 에디터에 설정 한다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * REGISTER_EDITING_AREA
 *
 * 편집 영역을 플러그인을 등록 시킨다. 원활한 모드 전환과 IR값 공유등를 위해서 초기화 시에 등록이 필요하다.
 *
 * oEditingAreaPlugin object 편집 영역 플러그인 인스턴스
 *
---------------------------------------------------------------------------]*/
/*[
 * MSG_EDITING_AREA_RESIZE_STARTED
 *
 * 편집 영역 사이즈 조절이 시작 되었음을 알리는 메시지.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * RESIZE_EDITING_AREA
 *
 * 편집 영역 사이즈를 설정 한다. 변경 전후에 MSG_EDITIING_AREA_RESIZE_STARTED/MSG_EDITING_AREA_RESIZE_ENED를 발생 시켜 줘야 된다.
 *
 * ipNewWidth number 새 폭
 * ipNewHeight number 새 높이
 *
---------------------------------------------------------------------------]*/
/*[
 * RESIZE_EDITING_AREA_BY
 *
 * 편집 영역 사이즈를 늘리거나 줄인다. 변경 전후에 MSG_EDITIING_AREA_RESIZE_STARTED/MSG_EDITING_AREA_RESIZE_ENED를 발생 시켜 줘야 된다.
 * 변경치를 입력하면 원래 사이즈에서 변경하여 px로 적용하며, width가 %로 설정된 경우에는 폭 변경치가 입력되어도 적용되지 않는다.
 *
 * ipWidthChange number 폭 변경치
 * ipHeightChange number 높이 변경치
 *
---------------------------------------------------------------------------]*/
/*[
 * MSG_EDITING_AREA_RESIZE_ENDED
 *
 * 편집 영역 사이즈 조절이 끝났음을 알리는 메시지.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/**
 * @pluginDesc IR 값과 복수개의 편집 영역을 관리하는 플러그인
 */
nhn.husky.SE_EditingAreaManager = jindo.$Class({
    name: "SE_EditingAreaManager",

    // Currently active plugin instance(SE_EditingArea_???)
    oActivePlugin: null,

    // Intermediate Representation of the content being edited.
    // This should be a textarea element.
    elContentsField: null,

    bIsDirty: false,
    bAutoResize: false, // [SMARTEDITORSUS-677] 에디터의 자동확장 기능 On/Off 여부

    $init: function(sDefaultEditingMode, elContentsField, oDimension, fOnBeforeUnload, elAppContainer) {
        this.sDefaultEditingMode = sDefaultEditingMode;
        this.elContentsField = jindo.$(elContentsField);
        this._assignHTMLElements(elAppContainer);
        this.fOnBeforeUnload = fOnBeforeUnload;

        this.oEditingMode = {};

        this.elContentsField.style.display = "none";

        this.nMinWidth = parseInt((oDimension.nMinWidth || 60), 10);
        this.nMinHeight = parseInt((oDimension.nMinHeight || 60), 10);

        var oWidth = this._getSize([oDimension.nWidth, oDimension.width, this.elEditingAreaContainer.offsetWidth], this.nMinWidth);
        var oHeight = this._getSize([oDimension.nHeight, oDimension.height, this.elEditingAreaContainer.offsetHeight], this.nMinHeight);

        this.elEditingAreaContainer.style.width = oWidth.nSize + oWidth.sUnit;
        this.elEditingAreaContainer.style.height = oHeight.nSize + oHeight.sUnit;

        if (oWidth.sUnit === "px") {
            elAppContainer.style.width = (oWidth.nSize + 2) + "px";
        } else if (oWidth.sUnit === "%") {
            elAppContainer.style.minWidth = this.nMinWidth + "px";
        }
    },

    _getSize: function(aSize, nMin) {
        var i, nLen, aRxResult, nSize, sUnit, sDefaultUnit = "px";

        nMin = parseInt(nMin, 10);

        for (i = 0, nLen = aSize.length; i < nLen; i++) {
            if (!aSize[i]) {
                continue;
            }

            if (!isNaN(aSize[i])) {
                nSize = parseInt(aSize[i], 10);
                sUnit = sDefaultUnit;
                break;
            }

            aRxResult = /([0-9]+)(.*)/i.exec(aSize[i]);

            if (!aRxResult || aRxResult.length < 2 || aRxResult[1] <= 0) {
                continue;
            }

            nSize = parseInt(aRxResult[1], 10);
            sUnit = aRxResult[2];

            if (!sUnit) {
                sUnit = sDefaultUnit;
            }

            if (nSize < nMin && sUnit === sDefaultUnit) {
                nSize = nMin;
            }

            break;
        }

        if (!sUnit) {
            sUnit = sDefaultUnit;
        }

        if (isNaN(nSize) || (nSize < nMin && sUnit === sDefaultUnit)) {
            nSize = nMin;
        }

        return {
            nSize: nSize,
            sUnit: sUnit
        };
    },

    _assignHTMLElements: function(elAppContainer) {
        //@ec[
        this.elEditingAreaContainer = jindo.$$.getSingle("DIV.husky_seditor_editing_area_container", elAppContainer);
        //@ec]

        // [SMARTEDITORSUS-1585]
        this.toolbarArea = jindo.$$.getSingle(".se2_tool", elAppContainer);
        // --[SMARTEDITORSUS-1585]
    },

    $BEFORE_MSG_APP_READY: function(msg) {
        this.oApp.exec("ADD_APP_PROPERTY", ["version", nhn.husky.SE_EditingAreaManager.version]);
        this.oApp.exec("ADD_APP_PROPERTY", ["elEditingAreaContainer", this.elEditingAreaContainer]);
        this.oApp.exec("ADD_APP_PROPERTY", ["welEditingAreaContainer", jindo.$Element(this.elEditingAreaContainer)]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getEditingAreaHeight", jindo.$Fn(this.getEditingAreaHeight, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getEditingAreaWidth", jindo.$Fn(this.getEditingAreaWidth, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getRawContents", jindo.$Fn(this.getRawContents, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getContents", jindo.$Fn(this.getContents, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getIR", jindo.$Fn(this.getIR, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["setContents", this.setContents]);
        this.oApp.exec("ADD_APP_PROPERTY", ["setIR", this.setIR]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getEditingMode", jindo.$Fn(this.getEditingMode, this).bind()]);
    },

    $ON_MSG_APP_READY: function() {
        this.htOptions = this.oApp.htOptions[this.name] || {};
        this.sDefaultEditingMode = this.htOptions["sDefaultEditingMode"] || this.sDefaultEditingMode;
        this.iframeWindow = this.oApp.getWYSIWYGWindow();
        this.oApp.exec("REGISTER_CONVERTERS", []);
        this.oApp.exec("CHANGE_EDITING_MODE", [this.sDefaultEditingMode, true]);
        this.oApp.exec("LOAD_CONTENTS_FIELD", [false]);

        //[SMARTEDITORSUS-1327] IE 7/8에서 ALT+0으로 팝업 띄우고 esc클릭시 팝업창 닫히게 하려면 아래 부분 꼭 필요함.
        this.oApp.exec("REGISTER_HOTKEY", ["esc", "CLOSE_LAYER_POPUP", [], document]);

        if (!!this.fOnBeforeUnload) {
            window.onbeforeunload = this.fOnBeforeUnload;
        } else {
            window.onbeforeunload = jindo.$Fn(function() {
                // [SMARTEDITORSUS-1028][SMARTEDITORSUS-1517] QuickEditor 설정 API 개선으로, submit 이후 발생하게 되는 beforeunload 이벤트 핸들링 제거
                //this.oApp.exec("MSG_BEFOREUNLOAD_FIRED");
                // --// [SMARTEDITORSUS-1028][SMARTEDITORSUS-1517]
                //if(this.getContents() != this.elContentsField.value || this.bIsDirty){
                if (this.getRawContents() != this.sCurrentRawContents || this.bIsDirty) {
                    return this.oApp.$MSG("SE_EditingAreaManager.onExit");
                }
            }, this).bind();
        }
    },

    $ON_CLOSE_LAYER_POPUP: function() {
        this.oApp.exec("ENABLE_ALL_UI"); // 모든 UI 활성화.
        this.oApp.exec("DESELECT_UI", ["helpPopup"]);
        this.oApp.exec("HIDE_ALL_DIALOG_LAYER", []);
        this.oApp.exec("HIDE_EDITING_AREA_COVER"); // 편집 영역 활성화.

        this.oApp.exec("FOCUS");
    },

    $AFTER_MSG_APP_READY: function() {
        this.oApp.exec("UPDATE_RAW_CONTENTS");

        if (!!this.oApp.htOptions[this.name] && this.oApp.htOptions[this.name].bAutoResize) {
            this.bAutoResize = this.oApp.htOptions[this.name].bAutoResize;
        }
        // [SMARTEDITORSUS-941] 아이패드에서는 자동확장기능이 항상 켜져있도록 한다.
        if (this.oApp.oNavigator.msafari) {
            this.bAutoResize = true;
        }

        this.startAutoResize(); // [SMARTEDITORSUS-677] 편집영역 자동 확장 옵션이 TRUE이면 자동확장 시작
    },

    $ON_LOAD_CONTENTS_FIELD: function(bDontAddUndo) {
        var sContentsFieldValue = this.elContentsField.value;

        // [SMARTEDITORSUS-177] [IE9] 글 쓰기, 수정 시에 elContentsField 에 들어간 공백을 제거
        // [SMARTEDITORSUS-312] [FF4] 인용구 첫번째,두번째 디자인 1회 선택 시 에디터에 적용되지 않음
        sContentsFieldValue = sContentsFieldValue.replace(/^\s+/, "");

        this.oApp.exec("SET_CONTENTS", [sContentsFieldValue, bDontAddUndo]);
    },

    // 현재 contents를 form의 textarea에 세팅 해 줌.
    // form submit 전에 이 부분을 실행시켜야 됨.
    $ON_UPDATE_CONTENTS_FIELD: function() {
        //this.oIRField.value = this.oApp.getIR();
        this.elContentsField.value = this.oApp.getContents();
        this.oApp.exec("UPDATE_RAW_CONTENTS");
        //this.sCurrentRawContents = this.elContentsField.value;
    },

    // 에디터의 현재 상태를 기억해 둠. 페이지를 떠날 때 이 값이 변경 됐는지 확인 해서 내용이 변경 됐다는 경고창을 띄움
    // RawContents 대신 contents를 이용해도 되지만, contents 획득을 위해서는 변환기를 실행해야 되기 때문에 RawContents 이용
    $ON_UPDATE_RAW_CONTENTS: function() {
        this.sCurrentRawContents = this.oApp.getRawContents();
    },

    $BEFORE_CHANGE_EDITING_MODE: function(sMode) {
        if (!this.oEditingMode[sMode]) {
            return false;
        }

        this.stopAutoResize(); // [SMARTEDITORSUS-677] 해당 편집 모드에서의 자동확장을 중지함

        this._oPrevActivePlugin = this.oActivePlugin;
        this.oActivePlugin = this.oEditingMode[sMode];
    },

    $AFTER_CHANGE_EDITING_MODE: function(sMode, bNoFocus) {
        if (this._oPrevActivePlugin) {
            var sIR = this._oPrevActivePlugin.getIR();
            this.oApp.exec("SET_IR", [sIR]);

            //this.oApp.exec("ENABLE_UI", [this._oPrevActivePlugin.sMode]);

            this._setEditingAreaDimension();
        }
        //this.oApp.exec("DISABLE_UI", [this.oActivePlugin.sMode]);

        this.startAutoResize(); // [SMARTEDITORSUS-677] 변경된 편집 모드에서의 자동확장을 시작

        if (!bNoFocus) {
            this.oApp.delayedExec("FOCUS", [], 0);
        }
    },

    /**
     * 페이지를 떠날 때 alert을 표시할지 여부를 셋팅하는 함수.
     */
    $ON_SET_IS_DIRTY: function(bIsDirty) {
        this.bIsDirty = bIsDirty;
    },

    // [SMARTEDITORSUS-1698] 모바일에서 팝업 형태의 첨부가 사용될 때 포커스 이슈가 있음
    $ON_FOCUS: function(isPopupOpening) {
        if (!this.oActivePlugin || typeof this.oActivePlugin.setIR != "function") {
            return;
        }

        // [SMARTEDITORSUS-599] ipad 대응 이슈.
        // ios5에서는 this.iframe.contentWindow focus가 없어서 생긴 이슈.
        // document가 아닌 window에 focus() 주어야만 본문에 focus가 가고 입력이됨.

        //[SMARTEDITORSUS-1017] [iOS5대응] 모드 전환 시 textarea에 포커스가 있어도 글자가 입력이 안되는 현상
        //원인 : WYSIWYG모드가 아닐 때에도 iframe의 contentWindow에 focus가 가면서 focus기능이 작동하지 않음
        //해결 : WYSIWYG모드 일때만 실행 되도록 조건식 추가 및 기존에 blur처리 코드 삭제
        //[SMARTEDITORSUS-1594] 크롬에서 웹접근성용 키로 빠져나간 후 다시 진입시 간혹 포커싱이 안되는 문제가 있어 iframe에 포커싱을 먼저 주도록 수정
        if (!!this.iframeWindow && this.iframeWindow.document.hasFocus && !this.iframeWindow.document.hasFocus() && this.oActivePlugin.sMode == "WYSIWYG") {
            this.iframeWindow.focus();
        } else { // 누락된 [SMARTEDITORSUS-1018] 작업분 반영
            this.oActivePlugin.focus();
        }

        if (isPopupOpening && this.oApp.bMobile) {
            return;
        }

        this.oActivePlugin.focus();
    },
    // --[SMARTEDITORSUS-1698]

    $ON_IE_FOCUS: function() {
        if (!this.oApp.oNavigator.ie) {
            return;
        }
        this.oApp.exec("FOCUS");
    },

    $ON_SET_CONTENTS: function(sContents, bDontAddUndoHistory) {
        this.setContents(sContents, bDontAddUndoHistory);
    },

    $BEFORE_SET_IR: function(sIR, bDontAddUndoHistory) {
        bDontAddUndoHistory = bDontAddUndoHistory || false;
        if (!bDontAddUndoHistory) {
            this.oApp.exec("RECORD_UNDO_ACTION", ["BEFORE SET CONTENTS", {
                sSaveTarget: "BODY"
            }]);
        }
    },

    $ON_SET_IR: function(sIR) {
        if (!this.oActivePlugin || typeof this.oActivePlugin.setIR != "function") {
            return;
        }

        this.oActivePlugin.setIR(sIR);
    },

    $AFTER_SET_IR: function(sIR, bDontAddUndoHistory) {
        bDontAddUndoHistory = bDontAddUndoHistory || false;
        if (!bDontAddUndoHistory) {
            this.oApp.exec("RECORD_UNDO_ACTION", ["AFTER SET CONTENTS", {
                sSaveTarget: "BODY"
            }]);
        }
    },

    $ON_REGISTER_EDITING_AREA: function(oEditingAreaPlugin) {
        this.oEditingMode[oEditingAreaPlugin.sMode] = oEditingAreaPlugin;
        if (oEditingAreaPlugin.sMode == 'WYSIWYG') {
            this.attachDocumentEvents(oEditingAreaPlugin.oEditingArea);
        }
        this._setEditingAreaDimension(oEditingAreaPlugin);
    },

    $ON_MSG_EDITING_AREA_RESIZE_STARTED: function() {
        // [SMARTEDITORSUS-1585] 글감, 글양식, 글장식을 열었을 때 리사이징이 발생하면 커버용 레이어가 사라지는 문제 개선
        this._isLayerReasonablyShown = false;

        var elSelectedUI = jindo.$$.getSingle("ul[class^='se2_itool']>li.active", this.toolbarArea, {
            oneTimeOffCache: true
        });
        if (elSelectedUI) {
            var elSelectedUIParent = elSelectedUI.parentNode;
        }

        // 글감 버튼을 포함한 부모는 ul.se2_itool2, 글장식, 글양식 버튼을 포함한 부모는 ul.se2_itool4
        if (elSelectedUIParent && (elSelectedUIParent.className == "se2_itool2" || elSelectedUIParent.className == "se2_itool4")) {
            this._isLayerReasonablyShown = true;
        }
        // --[SMARTEDITORSUS-1585]

        this._fitElementInEditingArea(this.elEditingAreaContainer);
        this.oApp.exec("STOP_AUTORESIZE_EDITING_AREA"); // [SMARTEDITORSUS-677] 사용자가 편집영역 사이즈를 변경하면 자동확장 기능 중지
        this.oApp.exec("SHOW_EDITING_AREA_COVER");
        this.elEditingAreaContainer.style.overflow = "hidden";
        //      this.elResizingBoard.style.display = "block";

        this.iStartingHeight = parseInt(this.elEditingAreaContainer.style.height, 10);
    },

    /**
     * [SMARTEDITORSUS-677] 편집영역 자동확장 기능을 중지함
     */
    $ON_STOP_AUTORESIZE_EDITING_AREA: function() {
        if (!this.bAutoResize) {
            return;
        }

        this.stopAutoResize();
        this.bAutoResize = false;
    },

    /**
     * [SMARTEDITORSUS-677] 해당 편집 모드에서의 자동확장을 시작함
     */
    startAutoResize: function() {
        if (!this.bAutoResize || !this.oActivePlugin || typeof this.oActivePlugin.startAutoResize != "function") {
            return;
        }

        this.oActivePlugin.startAutoResize();
    },

    /**
     * [SMARTEDITORSUS-677] 해당 편집 모드에서의 자동확장을 중지함
     */
    stopAutoResize: function() {
        if (!this.bAutoResize || !this.oActivePlugin || typeof this.oActivePlugin.stopAutoResize != "function") {
            return;
        }

        this.oActivePlugin.stopAutoResize();
    },

    $ON_RESIZE_EDITING_AREA: function(ipNewWidth, ipNewHeight) {
        if (ipNewWidth !== null && typeof ipNewWidth !== "undefined") {
            this._resizeWidth(ipNewWidth, "px");
        }
        if (ipNewHeight !== null && typeof ipNewHeight !== "undefined") {
            this._resizeHeight(ipNewHeight, "px");
        }

        this._fitElementInEditingArea(this.elResizingBoard);
        this._setEditingAreaDimension();
    },

    _resizeWidth: function(ipNewWidth, sUnit) {
        var iNewWidth = parseInt(ipNewWidth, 10);

        if (iNewWidth < this.nMinWidth) {
            iNewWidth = this.nMinWidth;
        }

        if (ipNewWidth) {
            this.elEditingAreaContainer.style.width = iNewWidth + sUnit;
        }
    },

    _resizeHeight: function(ipNewHeight, sUnit) {
        var iNewHeight = parseInt(ipNewHeight, 10);

        if (iNewHeight < this.nMinHeight) {
            iNewHeight = this.nMinHeight;
        }

        if (ipNewHeight) {
            this.elEditingAreaContainer.style.height = iNewHeight + sUnit;
        }
    },

    $ON_RESIZE_EDITING_AREA_BY: function(ipWidthChange, ipHeightChange) {
        var iWidthChange = parseInt(ipWidthChange, 10);
        var iHeightChange = parseInt(ipHeightChange, 10);
        var iWidth;
        var iHeight;

        if (ipWidthChange !== 0 && this.elEditingAreaContainer.style.width.indexOf("%") === -1) {
            iWidth = this.elEditingAreaContainer.style.width ? parseInt(this.elEditingAreaContainer.style.width, 10) + iWidthChange : null;
        }

        if (iHeightChange !== 0) {
            iHeight = this.elEditingAreaContainer.style.height ? this.iStartingHeight + iHeightChange : null;
        }

        if (!ipWidthChange && !iHeightChange) {
            return;
        }

        this.oApp.exec("RESIZE_EDITING_AREA", [iWidth, iHeight]);
    },

    $ON_MSG_EDITING_AREA_RESIZE_ENDED: function(FnMouseDown, FnMouseMove, FnMouseUp) {
        // [SMARTEDITORSUS-1585] 글감, 글양식, 글장식을 열었을 때 리사이징이 발생하면 커버용 레이어가 사라지는 문제 개선
        if (!this._isLayerReasonablyShown) {
            this.oApp.exec("HIDE_EDITING_AREA_COVER");
        }
        // --[SMARTEDITORSUS-1585]

        this.elEditingAreaContainer.style.overflow = "";
        //      this.elResizingBoard.style.display = "none";
        this._setEditingAreaDimension();
    },

    $ON_SHOW_EDITING_AREA_COVER: function() {
        //      this.elEditingAreaContainer.style.overflow = "hidden";
        if (!this.elResizingBoard) {
            this.createCoverDiv();
        }
        this.elResizingBoard.style.display = "block";
    },

    $ON_HIDE_EDITING_AREA_COVER: function() {
        //      this.elEditingAreaContainer.style.overflow = "";
        if (!this.elResizingBoard) {
            return;
        }
        this.elResizingBoard.style.display = "none";
    },

    $ON_KEEP_WITHIN_EDITINGAREA: function(elLayer, nHeight) {
        var nTop = parseInt(elLayer.style.top, 10);
        if (nTop + elLayer.offsetHeight > this.oApp.elEditingAreaContainer.offsetHeight) {
            if (typeof nHeight == "number") {
                elLayer.style.top = nTop - elLayer.offsetHeight - nHeight + "px";
            } else {
                elLayer.style.top = this.oApp.elEditingAreaContainer.offsetHeight - elLayer.offsetHeight + "px";
            }
        }

        var nLeft = parseInt(elLayer.style.left, 10);
        if (nLeft + elLayer.offsetWidth > this.oApp.elEditingAreaContainer.offsetWidth) {
            elLayer.style.left = this.oApp.elEditingAreaContainer.offsetWidth - elLayer.offsetWidth + "px";
        }
    },

    $ON_EVENT_EDITING_AREA_KEYDOWN: function() {
        this.oApp.exec("HIDE_ACTIVE_LAYER", []);
    },

    $ON_EVENT_EDITING_AREA_MOUSEDOWN: function() {
        this.oApp.exec("HIDE_ACTIVE_LAYER", []);
    },

    $ON_EVENT_EDITING_AREA_SCROLL: function() {
        this.oApp.exec("HIDE_ACTIVE_LAYER", []);
    },

    _setEditingAreaDimension: function(oEditingAreaPlugin) {
        oEditingAreaPlugin = oEditingAreaPlugin || this.oActivePlugin;
        this._fitElementInEditingArea(oEditingAreaPlugin.elEditingArea);
    },

    _fitElementInEditingArea: function(el) {
        el.style.height = this.elEditingAreaContainer.offsetHeight + "px";
        //      el.style.width = this.elEditingAreaContainer.offsetWidth+"px";
        //      el.style.width = this.elEditingAreaContainer.style.width || (this.elEditingAreaContainer.offsetWidth+"px");
    },

    attachDocumentEvents: function(doc) {
        this.oApp.registerBrowserEvent(doc, "click", "EVENT_EDITING_AREA_CLICK");
        this.oApp.registerBrowserEvent(doc, "dblclick", "EVENT_EDITING_AREA_DBLCLICK");
        this.oApp.registerBrowserEvent(doc, "mousedown", "EVENT_EDITING_AREA_MOUSEDOWN");
        this.oApp.registerBrowserEvent(doc, "mousemove", "EVENT_EDITING_AREA_MOUSEMOVE");
        this.oApp.registerBrowserEvent(doc, "mouseup", "EVENT_EDITING_AREA_MOUSEUP");
        this.oApp.registerBrowserEvent(doc, "mouseout", "EVENT_EDITING_AREA_MOUSEOUT");
        this.oApp.registerBrowserEvent(doc, "mousewheel", "EVENT_EDITING_AREA_MOUSEWHEEL");
        this.oApp.registerBrowserEvent(doc, "keydown", "EVENT_EDITING_AREA_KEYDOWN");
        this.oApp.registerBrowserEvent(doc, "keypress", "EVENT_EDITING_AREA_KEYPRESS");
        this.oApp.registerBrowserEvent(doc, "keyup", "EVENT_EDITING_AREA_KEYUP");
        this.oApp.registerBrowserEvent(doc, "scroll", "EVENT_EDITING_AREA_SCROLL");
    },

    createCoverDiv: function() {
        this.elResizingBoard = document.createElement("DIV");

        this.elEditingAreaContainer.insertBefore(this.elResizingBoard, this.elEditingAreaContainer.firstChild);
        this.elResizingBoard.style.position = "absolute";
        this.elResizingBoard.style.background = "#000000";
        this.elResizingBoard.style.zIndex = 100;
        this.elResizingBoard.style.border = 1;

        this.elResizingBoard.style["opacity"] = 0.0;
        this.elResizingBoard.style.filter = "alpha(opacity=0.0)";
        this.elResizingBoard.style["MozOpacity"] = 0.0;
        this.elResizingBoard.style["-moz-opacity"] = 0.0;
        this.elResizingBoard.style["-khtml-opacity"] = 0.0;

        this._fitElementInEditingArea(this.elResizingBoard);
        this.elResizingBoard.style.width = this.elEditingAreaContainer.offsetWidth + "px";

        this.elResizingBoard.style.display = "none";
    },

    $ON_GET_COVER_DIV: function(sAttr, oReturn) {
        if (!!this.elResizingBoard) {
            oReturn[sAttr] = this.elResizingBoard;
        }
    },

    getIR: function() {
        if (!this.oActivePlugin) {
            return "";
        }
        return this.oActivePlugin.getIR();
    },

    setIR: function(sIR, bDontAddUndo) {
        this.oApp.exec("SET_IR", [sIR, bDontAddUndo]);
    },

    getRawContents: function() {
        if (!this.oActivePlugin) {
            return "";
        }
        return this.oActivePlugin.getRawContents();
    },

    getContents: function() {
        var sIR = this.oApp.getIR();
        var sContents;

        if (this.oApp.applyConverter) {
            sContents = this.oApp.applyConverter("IR_TO_DB", sIR, this.oApp.getWYSIWYGDocument());
        } else {
            sContents = sIR;
        }

        sContents = this._cleanContents(sContents);

        return sContents;
    },

    _cleanContents: function(sContents) {
        return sContents.replace(new RegExp("(<img [^>]*>)" + unescape("%uFEFF") + "", "ig"), "$1");
    },

    setContents: function(sContents, bDontAddUndo) {
        var sIR;

        if (this.oApp.applyConverter) {
            sIR = this.oApp.applyConverter("DB_TO_IR", sContents, this.oApp.getWYSIWYGDocument());
        } else {
            sIR = sContents;
        }

        this.oApp.exec("SET_IR", [sIR, bDontAddUndo]);
    },

    getEditingMode: function() {
        return this.oActivePlugin.sMode;
    },

    getEditingAreaWidth: function() {
        return this.elEditingAreaContainer.offsetWidth;
    },

    getEditingAreaHeight: function() {
        return this.elEditingAreaContainer.offsetHeight;
    }
});
var nSE2Version = "11969";
nhn.husky.SE_EditingAreaManager.version = {
    revision: "11969",
    type: "open",
    number: "2.8.2"
};
/*[
 * REFRESH_WYSIWYG
 *
 * (FF전용) WYSIWYG 모드를 비활성화 후 다시 활성화 시킨다. FF에서 WYSIWYG 모드가 일부 비활성화 되는 문제용
 * 주의] REFRESH_WYSIWYG후에는 본문의 selection이 깨져서 커서 제일 앞으로 가는 현상이 있음. (stringbookmark로 처리해야함.)
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * ENABLE_WYSIWYG
 *
 * 비활성화된 WYSIWYG 편집 영역을 활성화 시킨다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * DISABLE_WYSIWYG
 *
 * WYSIWYG 편집 영역을 비활성화 시킨다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * PASTE_HTML
 *
 * HTML을 편집 영역에 삽입한다.
 *
 * sHTML string 삽입할 HTML
 * oPSelection object 붙여 넣기 할 영역, 생략시 현재 커서 위치
 *
---------------------------------------------------------------------------]*/
/*[
 * RESTORE_IE_SELECTION
 *
 * (IE전용) 에디터에서 포커스가 나가는 시점에 기억해둔 포커스를 복구한다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/**
 * @pluginDesc WYSIWYG 모드를 제공하는 플러그인
 */
nhn.husky.SE_EditingArea_WYSIWYG = jindo.$Class({
    name: "SE_EditingArea_WYSIWYG",
    status: nhn.husky.PLUGIN_STATUS.NOT_READY,

    sMode: "WYSIWYG",
    iframe: null,
    doc: null,

    bStopCheckingBodyHeight: false,
    bAutoResize: false, // [SMARTEDITORSUS-677] 해당 편집모드의 자동확장 기능 On/Off 여부

    nBodyMinHeight: 0,
    nScrollbarWidth: 0,

    iLastUndoRecorded: 0,
    //  iMinUndoInterval : 50,

    _nIFrameReadyCount: 50,

    bWYSIWYGEnabled: false,

    $init: function(iframe) {
        this.iframe = jindo.$(iframe);
        var oAgent = jindo.$Agent().navigator();
        // IE에서 에디터 초기화 시에 임의적으로 iframe에 포커스를 반쯤(IME 입력 안되고 커서만 깜박이는 상태) 주는 현상을 막기 위해서 일단 iframe을 숨겨 뒀다가 CHANGE_EDITING_MODE에서 위지윅 전환 시 보여준다.
        // 이런 현상이 다양한 요소에 의해서 발생하며 발견된 몇가지 경우는,
        // - frameset으로 페이지를 구성한 후에 한개의 frame안에 버튼을 두어 에디터로 링크 할 경우
        // - iframe과 동일 페이지에 존재하는 text field에 값을 할당 할 경우
        if (oAgent.ie) {
            this.iframe.style.display = "none";
        }

        // IE8 : 찾기/바꾸기에서 글자 일부에 스타일이 적용된 경우 찾기가 안되는 브라우저 버그로 인해 EmulateIE7 파일을 사용
        // <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
        this.sBlankPageURL = "smart_editor2_inputarea.html";
        this.sBlankPageURL_EmulateIE7 = "smart_editor2_inputarea_ie8.html";
        this.aAddtionalEmulateIE7 = [];

        this.htOptions = this.SE_EditingAreaManager;
        if (this.htOptions) {
            this.sBlankPageURL = this.htOptions.sBlankPageURL || this.sBlankPageURL;
            this.sBlankPageURL_EmulateIE7 = this.htOptions.sBlankPageURL_EmulateIE7 || this.sBlankPageURL_EmulateIE7;
            this.aAddtionalEmulateIE7 = this.htOptions.aAddtionalEmulateIE7 || this.aAddtionalEmulateIE7;
        }

        this.aAddtionalEmulateIE7.push(8); // IE8은 Default 사용

        this.sIFrameSrc = this.sBlankPageURL;
        if (oAgent.ie && jindo.$A(this.aAddtionalEmulateIE7).has(oAgent.nativeVersion)) {
            this.sIFrameSrc = this.sBlankPageURL_EmulateIE7;
        }

        var sIFrameSrc = this.sIFrameSrc,
            iframe = this.iframe,
            fHandlerSuccess = jindo.$Fn(this.initIframe, this).bind(),
            fHandlerFail = jindo.$Fn(function() {
                this.iframe.src = sIFrameSrc;
            }, this).bind();

        if (!oAgent.ie || (oAgent.version >= 9 && !!document.addEventListener)) {
            iframe.addEventListener("load", fHandlerSuccess, false);
            iframe.addEventListener("error", fHandlerFail, false);
        } else {
            iframe.attachEvent("onload", fHandlerSuccess);
            iframe.attachEvent("onerror", fHandlerFail);
        }
        iframe.src = sIFrameSrc;
        this.elEditingArea = iframe;
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oEditingArea = this.iframe.contentWindow.document;
        this.oApp.exec("REGISTER_EDITING_AREA", [this]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getWYSIWYGWindow", jindo.$Fn(this.getWindow, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getWYSIWYGDocument", jindo.$Fn(this.getDocument, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["isWYSIWYGEnabled", jindo.$Fn(this.isWYSIWYGEnabled, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getRawHTMLContents", jindo.$Fn(this.getRawHTMLContents, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["setRawHTMLContents", jindo.$Fn(this.setRawHTMLContents, this).bind()]);

        if (!!this.isWYSIWYGEnabled()) {
            this.oApp.exec('ENABLE_WYSIWYG_RULER');
        }

        this.oApp.registerBrowserEvent(this.getDocument().body, 'paste', 'EVENT_EDITING_AREA_PASTE');
    },

    $ON_MSG_APP_READY: function() {
        if (!this.oApp.hasOwnProperty("saveSnapShot")) {
            this.$ON_EVENT_EDITING_AREA_MOUSEUP = function() {};
            this._recordUndo = function() {};
        }

        // uncomment this line if you wish to use the IE-style cursor in FF
        // this.getDocument().body.style.cursor = "text";

        // Do not update this._oIERange until the document is actually clicked (focus was given by mousedown->mouseup)
        // Without this, iframe cannot be re-selected(by RESTORE_IE_SELECTION) if the document hasn't been clicked
        // mousedown on iframe -> focus goes into the iframe doc -> beforedeactivate is fired -> empty selection is saved by the plugin -> empty selection is recovered in RESTORE_IE_SELECTION
        this._bIERangeReset = true;

        if (this.oApp.oNavigator.ie) {
            jindo.$Fn(
                function(weEvent) {
                    var oSelection = this.iframe.contentWindow.document.selection;
                    if (oSelection && oSelection.type.toLowerCase() === 'control' && weEvent.key().keyCode === 8) {
                        this.oApp.exec("EXECCOMMAND", ['delete', false, false]);
                        weEvent.stop();
                    }

                    this._bIERangeReset = false;
                }, this
            ).attach(this.iframe.contentWindow.document, "keydown");
            jindo.$Fn(
                function(weEvent) {
                    this._oIERange = null;
                    this._bIERangeReset = true;
                }, this
            ).attach(this.iframe.contentWindow.document.body, "mousedown");

            // [SMARTEDITORSUS-1810] document.createRange 가 없는 경우만(IE8이하) beforedeactivate 이벤트 등록
            if (!this.getDocument().createRange) {
                jindo.$Fn(this._onIEBeforeDeactivate, this).attach(this.iframe.contentWindow.document.body, "beforedeactivate");
            }

            jindo.$Fn(
                function(weEvent) {
                    this._bIERangeReset = false;
                }, this
            ).attach(this.iframe.contentWindow.document.body, "mouseup");
        } else if (this.oApp.oNavigator.bGPadBrowser) {
            // [SMARTEDITORSUS-1802] GPad 에서만 툴바 터치시 셀렉션을 저장해둔다.
            this.$ON_EVENT_TOOLBAR_TOUCHSTART = function() {
                this._oIERange = this.oApp.getSelection().cloneRange();
            }
        }

        // DTD가 quirks가 아닐 경우 body 높이 100%가 제대로 동작하지 않아서 타임아웃을 돌며 높이를 수동으로 계속 할당 해 줌
        // body 높이가 제대로 설정 되지 않을 경우, 보기에는 이상없어 보이나 마우스로 텍스트 선택이 잘 안된다든지 하는 이슈가 있음
        this.fnSetBodyHeight = jindo.$Fn(this._setBodyHeight, this).bind();
        this.fnCheckBodyChange = jindo.$Fn(this._checkBodyChange, this).bind();

        this.fnSetBodyHeight();

        this._setScrollbarWidth();
    },

    $ON_IE_CHECK_EXCEPTION_FOR_SELECTION_PRESERVATION: function() {
        // 현재 선택된 앨리먼트가 iframe이라면, 셀렉션을 따로 기억 해 두지 않아도 유지 됨으로 RESTORE_IE_SELECTION을 타지 않도록 this._oIERange을 지워준다.
        // (필요 없을 뿐더러 저장 시 문제 발생)
        var oSelection = this.getDocument().selection;
        if (oSelection && oSelection.type === "Control") {
            this._oIERange = null;
        }
    },

    _onIEBeforeDeactivate: function(wev) {
        this.oApp.delayedExec("IE_CHECK_EXCEPTION_FOR_SELECTION_PRESERVATION", null, 0);

        if (this._oIERange) {
            return;
        }

        // without this, cursor won't make it inside a table.
        // mousedown(_oIERange gets reset) -> beforedeactivate(gets fired for table) -> RESTORE_IE_SELECTION
        if (this._bIERangeReset) {
            return;
        }

        this._oIERange = this.oApp.getSelection().cloneRange();
    },

    $ON_CHANGE_EDITING_MODE: function(sMode, bNoFocus) {
        if (sMode === this.sMode) {
            // --[SMARTEDITORSUS-1213]

            /**
             * [SMARTEDITORSUS-1889]
             * visibility 속성을 사용해서 Editor를 표시하고 숨김
             * 단, 에디터 초기화 시 필요한 display:block 설정은 유지
             *
             * */
            this.iframe.style.visibility = "visible";
            if (this.iframe.style.display != "block") { // 초기화 시 최초 1회
                this.iframe.style.display = "block";
            }
            // Previous below
            //this.iframe.style.display = "block";
            // --[SMARTEDITORSUS-1889]

            this.oApp.exec("REFRESH_WYSIWYG");
            this.oApp.exec("SET_EDITING_WINDOW", [this.getWindow()]);
            this.oApp.exec("START_CHECKING_BODY_HEIGHT");
        } else {
            /**
             * [SMARTEDITORSUS-1889]
             * 모드 전환 시 display:none과 display:block을 사용해서
             * Editor 영역을 표시하고 숨기는 경우,
             * iframe 요소가 그 때마다 다시 로드되는 과정에서
             * 스크립트 오류를 유발시킴 (국내지도)
             *
             * 따라서 visibility 속성을 대신 사용하고,
             * 이 경우 Editor 영역이 공간을 여전히 차지하고 있기 때문에
             * 그 아래 위치하게 될 수밖에 없는
             * HTML 영역이나 Text 영역은
             * position:absolute와 top 속성을 사용하여
             * 위로 끌어올리는 방법을 사용
             * */
            this.iframe.style.visibility = "hidden";
            // previous below
            //this.iframe.style.display = "none";
            // --[SMARTEDITORSUS-1889]
            this.oApp.exec("STOP_CHECKING_BODY_HEIGHT");
        }
    },

    $AFTER_CHANGE_EDITING_MODE: function(sMode, bNoFocus) {
        this._oIERange = null;
    },

    $ON_REFRESH_WYSIWYG: function() {
        if (!jindo.$Agent().navigator().firefox) {
            return;
        }

        this._disableWYSIWYG();
        this._enableWYSIWYG();
    },

    $ON_ENABLE_WYSIWYG: function() {
        this._enableWYSIWYG();
    },

    $ON_DISABLE_WYSIWYG: function() {
        this._disableWYSIWYG();
    },

    $ON_IE_HIDE_CURSOR: function() {
        if (!this.oApp.oNavigator.ie) {
            return;
        }

        this._onIEBeforeDeactivate();

        // De-select the default selection.
        // [SMARTEDITORSUS-978] IE9에서 removeAllRanges로 제거되지 않아
        // 이전 IE와 동일하게 empty 방식을 사용하도록 하였으나 doc.selection.type이 None인 경우 에러
        // Range를 재설정 해주어 selectNone 으로 처리되도록 예외처리
        var oSelection = this.oApp.getWYSIWYGDocument().selection;
        if (oSelection && oSelection.createRange) {
            try {
                oSelection.empty();
            } catch (e) {
                // [SMARTEDITORSUS-1003] IE9 / doc.selection.type === "None"
                oSelection = this.oApp.getSelection();
                oSelection.select();
                oSelection.oBrowserSelection.selectNone();
            }
        } else {
            this.oApp.getEmptySelection().oBrowserSelection.selectNone();
        }
    },

    $AFTER_SHOW_ACTIVE_LAYER: function() {
        this.oApp.exec("IE_HIDE_CURSOR");
        this.bActiveLayerShown = true;
    },

    $BEFORE_EVENT_EDITING_AREA_KEYDOWN: function(oEvent) {
        this._bKeyDown = true;
    },

    $ON_EVENT_EDITING_AREA_KEYDOWN: function(oEvent) {
        if (this.oApp.getEditingMode() !== this.sMode) {
            return;
        }

        var oKeyInfo = oEvent.key();

        if (this.oApp.oNavigator.ie) {
            //var oKeyInfo = oEvent.key();
            switch (oKeyInfo.keyCode) {
                case 33:
                    this._pageUp(oEvent);
                    break;
                case 34:
                    this._pageDown(oEvent);
                    break;
                case 8: // [SMARTEDITORSUS-495][SMARTEDITORSUS-548] IE에서 표가 삭제되지 않는 문제
                    this._backspace(oEvent);
                    break;
                default:
            }
        } else if (this.oApp.oNavigator.firefox) {
            // [SMARTEDITORSUS-151] FF 에서 표가 삭제되지 않는 문제
            if (oKeyInfo.keyCode === 8) { // backspace
                this._backspace(oEvent);
            }
        }

        this._recordUndo(oKeyInfo); // 첫번째 Delete 키 입력 전의 상태가 저장되도록 KEYDOWN 시점에 저장
    },

    /**
     * [SMARTEDITORSUS-1575] 커서홀더 제거
     * [SMARTEDITORSUS-151][SMARTEDITORSUS-495][SMARTEDITORSUS-548] IE와 FF에서 표 삭제
     */
    _backspace: function(weEvent) {
        var oSelection = this.oApp.getSelection(),
            preNode = null;

        if (!oSelection.collapsed) {
            return;
        }

        preNode = oSelection.getNodeAroundRange(true, false);

        if (preNode && preNode.nodeType === 3) {
            if (/^[\n]*$/.test(preNode.nodeValue)) {
                preNode = preNode.previousSibling;
            } else if (preNode.nodeValue === "\u200B" || preNode.nodeValue === "\uFEFF") {
                // [SMARTEDITORSUS-1575] 공백대신 커서홀더 삽입된 상태라서 빈라인에서 백스페이스를 두번 쳐야 윗쪽라인으로 올라가기 때문에 한번 쳐서 올라갈 수 있도록 커서홀더 제거
                preNode.nodeValue = "";
            }
        }

        if (!!preNode && preNode.nodeType === 1 && preNode.tagName === "TABLE") {
            jindo.$Element(preNode).leave();
            weEvent.stop(jindo.$Event.CANCEL_ALL);
        }
    },

    $BEFORE_EVENT_EDITING_AREA_KEYUP: function(oEvent) {
        // IE(6) sometimes fires keyup events when it should not and when it happens the keyup event gets fired without a keydown event
        if (!this._bKeyDown) {
            return false;
        }
        this._bKeyDown = false;
    },

    $ON_EVENT_EDITING_AREA_MOUSEUP: function(oEvent) {
        this.oApp.saveSnapShot();
    },

    $BEFORE_PASTE_HTML: function() {
        if (this.oApp.getEditingMode() !== this.sMode) {
            this.oApp.exec("CHANGE_EDITING_MODE", [this.sMode]);
        }
    },

    $ON_PASTE_HTML: function(sHTML, oPSelection, bNoUndo) {
        var oSelection, oNavigator, sTmpBookmark,
            oStartContainer, aImgChild, elLastImg, elChild, elNextChild;

        if (this.oApp.getEditingMode() !== this.sMode) {
            return;
        }

        if (!bNoUndo) {
            this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", ["PASTE HTML"]);
        }

        oNavigator = jindo.$Agent().navigator();
        oSelection = oPSelection || this.oApp.getSelection();

        //[SMARTEDITORSUS-888] 브라우저 별 테스트 후 아래 부분이 불필요하여 제거함
        //  - [SMARTEDITORSUS-387] IE9 표준모드에서 엘리먼트 뒤에 어떠한 엘리먼트도 없는 상태에서 커서가 안들어가는 현상.
        // if(oNavigator.ie && oNavigator.nativeVersion >= 9 && document.documentMode >= 9){
        //      sHTML = sHTML + unescape("%uFEFF");
        // }
        if (oNavigator.ie && oNavigator.nativeVersion == 8 && document.documentMode == 8) {
            sHTML = sHTML + unescape("%uFEFF");
        }

        oSelection.pasteHTML(sHTML);

        // every browser except for IE may modify the innerHTML when it is inserted
        if (!oNavigator.ie) {
            sTmpBookmark = oSelection.placeStringBookmark();
            this.oApp.getWYSIWYGDocument().body.innerHTML = this.oApp.getWYSIWYGDocument().body.innerHTML;
            oSelection.moveToBookmark(sTmpBookmark);
            oSelection.collapseToEnd();
            oSelection.select();
            oSelection.removeStringBookmark(sTmpBookmark);
            // [SMARTEDITORSUS-56] 사진을 연속으로 첨부할 경우 연이어 삽입되지 않는 현상으로 이슈를 발견하게 되었습니다.
            // 그러나 이는 비단 '다수의 사진을 첨부할 경우'에만 발생하는 문제는 아니었고,
            // 원인 확인 결과 컨텐츠 삽입 후 기존 Bookmark 삭제 시 갱신된 Selection 이 제대로 반영되지 않는 점이 있었습니다.
            // 이에, Selection 을 갱신하는 코드를 추가하였습니다.
            oSelection = this.oApp.getSelection();

            //[SMARTEDITORSUS-831] 비IE 계열 브라우저에서 스크롤바가 생기게 문자입력 후 엔터 클릭하지 않은 상태에서
            //이미지 하나 삽입 시 이미지에 포커싱이 놓이지 않습니다.
            //원인 : parameter로 넘겨 받은 oPSelecion에 변경된 값을 복사해 주지 않아서 발생
            //해결 : parameter로 넘겨 받은 oPSelecion에 변경된 값을 복사해준다
            //       call by reference로 넘겨 받았으므로 직접 객체 안의 인자 값을 바꿔주는 setRange 함수 사용
            if (!!oPSelection) {
                oPSelection.setRange(oSelection);
            }
        } else {
            // [SMARTEDITORSUS-428] [IE9.0] IE9에서 포스트 쓰기에 접근하여 맨위에 임의의 글감 첨부 후 엔터를 클릭 시 글감이 사라짐
            // PASTE_HTML 후에 IFRAME 부분이 선택된 상태여서 Enter 시 내용이 제거되어 발생한 문제
            oSelection.collapseToEnd();
            oSelection.select();

            this._oIERange = null;
            this._bIERangeReset = false;
        }

        // [SMARTEDITORSUS-639] 사진 첨부 후 이미지 뒤의 공백으로 인해 스크롤이 생기는 문제
        if (sHTML.indexOf("<img") > -1) {
            oStartContainer = oSelection.startContainer;

            if (oStartContainer.nodeType === 1 && oStartContainer.tagName === "P") {
                aImgChild = jindo.$Element(oStartContainer).child(function(v) {
                    return (v.$value().nodeType === 1 && v.$value().tagName === "IMG");
                }, 1);

                if (aImgChild.length > 0) {
                    elLastImg = aImgChild[aImgChild.length - 1].$value();
                    elChild = elLastImg.nextSibling;

                    while (elChild) {
                        elNextChild = elChild.nextSibling;

                        if (elChild.nodeType === 3 && (elChild.nodeValue === "&nbsp;" || elChild.nodeValue === unescape("%u00A0"))) {
                            oStartContainer.removeChild(elChild);
                        }

                        elChild = elNextChild;
                    }
                }
            }
        }

        if (!bNoUndo) {
            this.oApp.exec("RECORD_UNDO_AFTER_ACTION", ["PASTE HTML"]);
        }
    },

    /**
     * [SMARTEDITORSUS-344]사진/동영상/지도 연속첨부시 포커싱 개선이슈로 추가되 함수.
     */
    $ON_FOCUS_N_CURSOR: function(bEndCursor, sId) {
        var el, oSelection;
        if (sId && (el = jindo.$(sId, this.getDocument()))) {
            // ID가 지정된 경우, 무조건 해당 부분으로 커서 이동
            clearTimeout(this._nTimerFocus); // 연속 삽입될 경우, 미완료 타이머는 취소한다.
            this._nTimerFocus = setTimeout(jindo.$Fn(function(el) {
                this._scrollIntoView(el);
                this.oApp.exec("FOCUS");
            }, this).bind(el), 300);
            return;
        }

        oSelection = this.oApp.getSelection();
        if (!oSelection.collapsed) { // select 영역이 있는 경우
            if (bEndCursor) {
                oSelection.collapseToEnd();
            } else {
                oSelection.collapseToStart();
            }
            oSelection.select();
        } else if (bEndCursor) { // select 영역이 없는 상태에서 bEndCursor 이면 body 맨 뒤로 이동시킨다.
            this.oApp.exec("FOCUS");
            el = this.getDocument().body;
            oSelection.selectNode(el);
            oSelection.collapseToEnd();
            oSelection.select();
            this._scrollIntoView(el);
        } else { // select 영역이 없는 상태라면 focus만 준다.
            this.oApp.exec("FOCUS");
        }
    },

    /*
     * 엘리먼트의 top, bottom 값을 반환
     */
    _getElementVerticalPosition: function(el) {
        var nTop = 0,
            elParent = el,
            htPos = {
                nTop: 0,
                nBottom: 0
            };

        if (!el) {
            return htPos;
        }

        // 테스트코드를 실행하면 IE8 이하에서 offsetParent 접근시 다음과 같이 알 수 없는 exception 이 발생함
        // "SCRIPT16389: 지정되지 않은 오류입니다."
        // TODO: 해결방법이 없어서 일단 try/catch 처리했지만 추후 정확한 이유를 파악할 필요가 있음
        try {
            while (elParent) {
                nTop += elParent.offsetTop;
                elParent = elParent.offsetParent;
            }
        } catch (e) {}

        htPos.nTop = nTop;
        htPos.nBottom = nTop + jindo.$Element(el).height();

        return htPos;
    },

    /*
     * Window에서 현재 보여지는 영역의 top, bottom 값을 반환
     */
    _getVisibleVerticalPosition: function() {
        var oWindow, oDocument, nVisibleHeight,
            htPos = {
                nTop: 0,
                nBottom: 0
            };

        oWindow = this.getWindow();
        oDocument = this.getDocument();
        nVisibleHeight = oWindow.innerHeight ? oWindow.innerHeight : oDocument.documentElement.clientHeight || oDocument.body.clientHeight;

        htPos.nTop = oWindow.pageYOffset || oDocument.documentElement.scrollTop;
        htPos.nBottom = htPos.nTop + nVisibleHeight;

        return htPos;
    },

    /*
     * 엘리먼트가 WYSIWYG Window의 Visible 부분에서 완전히 보이는 상태인지 확인 (일부만 보이면 false)
     */
    _isElementVisible: function(htElementPos, htVisiblePos) {
        return (htElementPos.nTop >= htVisiblePos.nTop && htElementPos.nBottom <= htVisiblePos.nBottom);
    },

    /*
     * [SMARTEDITORSUS-824] [SMARTEDITORSUS-828] 자동 스크롤 처리
     */
    _scrollIntoView: function(el) {
        var htElementPos = this._getElementVerticalPosition(el),
            htVisiblePos = this._getVisibleVerticalPosition(),
            nScroll = 0;

        if (this._isElementVisible(htElementPos, htVisiblePos)) {
            return;
        }

        if ((nScroll = htElementPos.nBottom - htVisiblePos.nBottom) > 0) {
            this.getWindow().scrollTo(0, htVisiblePos.nTop + nScroll); // Scroll Down
            return;
        }

        this.getWindow().scrollTo(0, htElementPos.nTop); // Scroll Up
    },

    $BEFORE_MSG_EDITING_AREA_RESIZE_STARTED: function() {
        // FF에서 Height조정 시에 본문의 _fitElementInEditingArea()함수 부분에서 selection이 깨지는 현상을 잡기 위해서
        // StringBookmark를 사용해서 위치를 저장해둠. (step1)
        if (!jindo.$Agent().navigator().ie) {
            var oSelection = null;
            oSelection = this.oApp.getSelection();
            this.sBM = oSelection.placeStringBookmark();
        }
    },

    $AFTER_MSG_EDITING_AREA_RESIZE_ENDED: function(FnMouseDown, FnMouseMove, FnMouseUp) {
        if (this.oApp.getEditingMode() !== this.sMode) {
            return;
        }

        this.oApp.exec("REFRESH_WYSIWYG");
        // bts.nhncorp.com/nhnbts/browse/COM-1042
        // $BEFORE_MSG_EDITING_AREA_RESIZE_STARTED에서 저장한 StringBookmark를 셋팅해주고 삭제함.(step2)
        if (!jindo.$Agent().navigator().ie) {
            var oSelection = this.oApp.getEmptySelection();
            oSelection.moveToBookmark(this.sBM);
            oSelection.select();
            oSelection.removeStringBookmark(this.sBM);
        }
    },

    $ON_CLEAR_IE_BACKUP_SELECTION: function() {
        this._oIERange = null;
    },

    $ON_RESTORE_IE_SELECTION: function() {
        if (this._oIERange) {
            // changing the visibility of the iframe can cause an exception
            try {
                this._oIERange.select();

                this._oPrevIERange = this._oIERange;
                this._oIERange = null;
            } catch (e) {}
        }
    },

    /**
     * EVENT_EDITING_AREA_PASTE 의 ON 메시지 핸들러
     *     위지윅 모드에서 에디터 본문의 paste 이벤트에 대한 메시지를 처리한다.
     *     paste 시에 내용이 붙여진 본문의 내용을 바로 가져올 수 없어 delay 를 준다.
     */
    $ON_EVENT_EDITING_AREA_PASTE: function(oEvent) {
        this.oApp.delayedExec('EVENT_EDITING_AREA_PASTE_DELAY', [oEvent], 0);
    },

    $ON_EVENT_EDITING_AREA_PASTE_DELAY: function(weEvent) {
        this._replaceBlankToNbsp(weEvent.element);
    },

    // [SMARTEDITORSUS-855] IE에서 특정 블로그 글을 복사하여 붙여넣기 했을 때 개행이 제거되는 문제
    _replaceBlankToNbsp: function(el) {
        var oNavigator = this.oApp.oNavigator;

        if (!oNavigator.ie) {
            return;
        }

        if (oNavigator.nativeVersion !== 9 || document.documentMode !== 7) { // IE9 호환모드에서만 발생
            return;
        }

        if (el.nodeType !== 1) {
            return;
        }

        if (el.tagName === "BR") {
            return;
        }

        var aEl = jindo.$$("p:empty()", this.oApp.getWYSIWYGDocument().body, {
            oneTimeOffCache: true
        });

        jindo.$A(aEl).forEach(function(value, index, array) {
            value.innerHTML = "&nbsp;";
        });
    },

    _pageUp: function(we) {
        var nEditorHeight = this._getEditorHeight(),
            htPos = jindo.$Document(this.oApp.getWYSIWYGDocument()).scrollPosition(),
            nNewTop;

        if (htPos.top <= nEditorHeight) {
            nNewTop = 0;
        } else {
            nNewTop = htPos.top - nEditorHeight;
        }
        this.oApp.getWYSIWYGWindow().scrollTo(0, nNewTop);
        we.stop();
    },

    _pageDown: function(we) {
        var nEditorHeight = this._getEditorHeight(),
            htPos = jindo.$Document(this.oApp.getWYSIWYGDocument()).scrollPosition(),
            nBodyHeight = this._getBodyHeight(),
            nNewTop;

        if (htPos.top + nEditorHeight >= nBodyHeight) {
            nNewTop = nBodyHeight - nEditorHeight;
        } else {
            nNewTop = htPos.top + nEditorHeight;
        }
        this.oApp.getWYSIWYGWindow().scrollTo(0, nNewTop);
        we.stop();
    },

    _getEditorHeight: function() {
        return this.oApp.elEditingAreaContainer.offsetHeight - this.nTopBottomMargin;
    },

    _getBodyHeight: function() {
        return parseInt(this.getDocument().body.scrollHeight, 10);
    },

    initIframe: function() {
        try {
            if (!this.iframe.contentWindow.document || !this.iframe.contentWindow.document.body || this.iframe.contentWindow.document.location.href === 'about:blank') {
                throw new Error('Access denied');
            }

            var sCSSBaseURI = (!!nhn.husky.SE2M_Configuration.SE2M_CSSLoader && nhn.husky.SE2M_Configuration.SE2M_CSSLoader.sCSSBaseURI) ?
                nhn.husky.SE2M_Configuration.SE2M_CSSLoader.sCSSBaseURI : "";

            if (!!nhn.husky.SE2M_Configuration.SE_EditingAreaManager.sCSSBaseURI) {
                sCSSBaseURI = nhn.husky.SE2M_Configuration.SE_EditingAreaManager.sCSSBaseURI;
            }

            // add link tag
            if (sCSSBaseURI) {
                var doc = this.getDocument();
                var headNode = doc.getElementsByTagName("head")[0];
                var linkNode = doc.createElement('link');
                linkNode.type = 'text/css';
                linkNode.rel = 'stylesheet';
                linkNode.href = sCSSBaseURI + '/smart_editor2_in.css';
                linkNode.onload = jindo.$Fn(function() {
                    // [SMARTEDITORSUS-1853] IE의 경우 css가 로드되어 반영되는데 시간이 걸려서 브라우저 기본폰트가 세팅되는 경우가 있음
                    // 때문에 css가 로드되면 SE_WYSIWYGStylerGetter 플러그인의 스타일정보를 RESET 해준다.
                    // 주의: 크롬의 경우, css 로딩이 더 먼저 발생해서 SE_WYSIWYGStylerGetter 플러그인에서 오류가 발생할 수 있기 때문에 RESET_STYLE_STATUS 메시지 호출이 가능한 상태인지 체크함
                    if (this.oApp && this.oApp.getEditingMode && this.oApp.getEditingMode() === this.sMode) {
                        this.oApp.exec("RESET_STYLE_STATUS");
                    }
                }, this).bind();
                headNode.appendChild(linkNode);
            }

            this._enableWYSIWYG();

            this.status = nhn.husky.PLUGIN_STATUS.READY;
        } catch (e) {
            if (this._nIFrameReadyCount-- > 0) {
                setTimeout(jindo.$Fn(this.initIframe, this).bind(), 100);
            } else {
                throw ("iframe for WYSIWYG editing mode can't be initialized. Please check if the iframe document exists and is also accessable(cross-domain issues). ");
            }
        }
    },

    getIR: function() {
        var sContent = this.iframe.contentWindow.document.body.innerHTML,
            sIR;

        if (this.oApp.applyConverter) {
            sIR = this.oApp.applyConverter(this.sMode + "_TO_IR", sContent, this.oApp.getWYSIWYGDocument());
        } else {
            sIR = sContent;
        }

        return sIR;
    },

    setIR: function(sIR) {
        // [SMARTEDITORSUS-875] HTML 모드의 beautify에서 추가된 공백을 다시 제거
        //sIR = sIR.replace(/(>)([\n\r\t\s]*)([^<]?)/g, "$1$3").replace(/([\n\r\t\s]*)(<)/g, "$2")
        // --[SMARTEDITORSUS-875]

        var sContent,
            oNavigator = this.oApp.oNavigator,
            bUnderIE11 = oNavigator.ie && document.documentMode < 11, // IE11미만
            sCursorHolder = bUnderIE11 ? "" : "<br>";

        if (this.oApp.applyConverter) {
            sContent = this.oApp.applyConverter("IR_TO_" + this.sMode, sIR, this.oApp.getWYSIWYGDocument());
        } else {
            sContent = sIR;
        }

        // [SMARTEDITORSUS-1279] [IE9/10] pre 태그 아래에 \n이 포함되면 개행이 되지 않는 이슈
        /*if(oNavigator.ie && oNavigator.nativeVersion >= 9 && document.documentMode >= 9){
            // [SMARTEDITORSUS-704] \r\n이 있는 경우 IE9 표준모드에서 정렬 시 브라우저가 <p>를 추가하는 문제
            sContent = sContent.replace(/[\r\n]/g,"");
        }*/

        // 편집내용이 없는 경우 커서홀더로 대체
        if (sContent.replace(/[\r\n\t\s]*/, "") === "") {
            if (this.oApp.sLineBreaker !== "BR") {
                sCursorHolder = "<p>" + sCursorHolder + "</p>";
            }
            sContent = sCursorHolder;
        }
        this.iframe.contentWindow.document.body.innerHTML = sContent;

        // [COM-1142] IE의 경우 <p>&nbsp;</p> 를 <p></p> 로 변환
        // [SMARTEDITORSUS-1623] IE11은 <p></p>로 변환하면 라인이 붙어버리기 때문에 IE10만 적용하도록 수정
        if (bUnderIE11 && this.oApp.getEditingMode() === this.sMode) {
            var pNodes = this.oApp.getWYSIWYGDocument().body.getElementsByTagName("P");

            for (var i = 0, nMax = pNodes.length; i < nMax; i++) {
                if (pNodes[i].childNodes.length === 1 && pNodes[i].innerHTML === "&nbsp;") {
                    pNodes[i].innerHTML = '';
                }
            }
        }
    },

    getRawContents: function() {
        return this.iframe.contentWindow.document.body.innerHTML;
    },

    getRawHTMLContents: function() {
        return this.getRawContents();
    },

    setRawHTMLContents: function(sContents) {
        this.iframe.contentWindow.document.body.innerHTML = sContents;
    },

    getWindow: function() {
        return this.iframe.contentWindow;
    },

    getDocument: function() {
        return this.iframe.contentWindow.document;
    },

    focus: function() {
        //this.getWindow().focus();
        this.getDocument().body.focus();
        this.oApp.exec("RESTORE_IE_SELECTION");
    },

    _recordUndo: function(oKeyInfo) {
        /**
         * 229: Korean/Eng
         * 16: shift
         * 33,34: page up/down
         * 35,36: end/home
         * 37,38,39,40: left, up, right, down
         * 32: space
         * 46: delete
         * 8: bksp
         */
        if (oKeyInfo.keyCode >= 33 && oKeyInfo.keyCode <= 40) { // record snapshot
            this.oApp.saveSnapShot();
            return;
        }

        if (oKeyInfo.alt || oKeyInfo.ctrl || oKeyInfo.keyCode === 16) {
            return;
        }

        if (this.oApp.getLastKey() === oKeyInfo.keyCode) {
            return;
        }

        this.oApp.setLastKey(oKeyInfo.keyCode);

        // && oKeyInfo.keyCode != 32        // 속도 문제로 인하여 Space 는 제외함
        if (!oKeyInfo.enter && oKeyInfo.keyCode !== 46 && oKeyInfo.keyCode !== 8) {
            return;
        }

        this.oApp.exec("RECORD_UNDO_ACTION", ["KEYPRESS(" + oKeyInfo.keyCode + ")", {
            bMustBlockContainer: true
        }]);
    },

    _enableWYSIWYG: function() {
        //if (this.iframe.contentWindow.document.body.hasOwnProperty("contentEditable")){
        if (this.iframe.contentWindow.document.body.contentEditable !== null) {
            this.iframe.contentWindow.document.body.contentEditable = true;
        } else {
            this.iframe.contentWindow.document.designMode = "on";
        }

        this.bWYSIWYGEnabled = true;
        if (jindo.$Agent().navigator().firefox) {
            setTimeout(jindo.$Fn(function() {
                //enableInlineTableEditing : Enables or disables the table row and column insertion and deletion controls.
                this.iframe.contentWindow.document.execCommand('enableInlineTableEditing', false, false);
            }, this).bind(), 0);
        }
    },

    _disableWYSIWYG: function() {
        //if (this.iframe.contentWindow.document.body.hasOwnProperty("contentEditable")){
        if (this.iframe.contentWindow.document.body.contentEditable !== null) {
            this.iframe.contentWindow.document.body.contentEditable = false;
        } else {
            this.iframe.contentWindow.document.designMode = "off";
        }
        this.bWYSIWYGEnabled = false;
    },

    isWYSIWYGEnabled: function() {
        return this.bWYSIWYGEnabled;
    }
});
//}
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations directly related to editing the HTML source code using Textarea element
 * @name hp_SE_EditingArea_HTMLSrc.js
 * @required SE_EditingAreaManager
 */
nhn.husky.SE_EditingArea_HTMLSrc = jindo.$Class({
    name: "SE_EditingArea_HTMLSrc",
    sMode: "HTMLSrc",
    bAutoResize: false, // [SMARTEDITORSUS-677] 해당 편집모드의 자동확장 기능 On/Off 여부
    nMinHeight: null, // [SMARTEDITORSUS-677] 편집 영역의 최소 높이

    $init: function(sTextArea) {
        this.elEditingArea = jindo.$(sTextArea);
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oNavigator = jindo.$Agent().navigator();
        this.oApp.exec("REGISTER_EDITING_AREA", [this]);
    },

    $ON_MSG_APP_READY: function() {
        if (!!this.oApp.getEditingAreaHeight) {
            this.nMinHeight = this.oApp.getEditingAreaHeight(); // [SMARTEDITORSUS-677] 편집 영역의 최소 높이를 가져와 자동 확장 처리를 할 때 사용
        }
    },

    $ON_CHANGE_EDITING_MODE: function(sMode) {
        if (sMode == this.sMode) {
            this.elEditingArea.style.display = "block";
            /**
             * [SMARTEDITORSUS-1889] Editor 영역을 표시하고 숨기는 데 있어서
             * display 속성 대신 visibility 속성을 사용하게 되면서,
             * Editor 영역이 화면에서 사라지지만
             * 공간을 차지하게 되므로
             * 그 아래로 위치하는 HTML 영역을 끌어올려 준다.
             *
             * @see hp_SE_EditingArea_WYSIWYG.js
             * */
            this.elEditingArea.style.position = "absolute";
            this.elEditingArea.style.top = "0px";
            // --[SMARTEDITORSUS-1889]
        } else {
            this.elEditingArea.style.display = "none";
            // [SMARTEDITORSUS-1889]
            this.elEditingArea.style.position = "";
            this.elEditingArea.style.top = "";
            // --[SMARTEDITORSUS-1889]
        }
    },

    $AFTER_CHANGE_EDITING_MODE: function(sMode, bNoFocus) {
        if (sMode == this.sMode && !bNoFocus) {
            var o = new TextRange(this.elEditingArea);
            o.setSelection(0, 0);

            //[SMARTEDITORSUS-1017] [iOS5대응] 모드 전환 시 textarea에 포커스가 있어도 글자가 입력이 안되는 현상
            //원인 : WYSIWYG모드가 아닐 때에도 iframe의 contentWindow에 focus가 가면서 focus기능이 작동하지 않음
            //해결 : WYSIWYG모드 일때만 실행 되도록 조건식 추가 및 기존에 blur처리 코드 삭제
            //모바일 textarea에서는 직접 클릭을해야만 키보드가 먹히기 때문에 우선은 커서가 안보이게 해서 사용자가 직접 클릭을 유도.
            // if(!!this.oNavigator.msafari){
            // this.elEditingArea.blur();
            // }
        }
    },

    /**
     * [SMARTEDITORSUS-677] HTML 편집 영역 자동 확장 처리 시작
     */
    startAutoResize: function() {
        var htOption = {
            nMinHeight: this.nMinHeight,
            wfnCallback: jindo.$Fn(this.oApp.checkResizeGripPosition, this).bind()
        };
        //[SMARTEDITORSUS-941][iOS5대응]아이패드의 자동 확장 기능이 동작하지 않을 때 에디터 창보다 긴 내용을 작성하면 에디터를 뚫고 나오는 현상
        //원인 : 자동확장 기능이 정지 될 경우 iframe에 스크롤이 생기지 않고, 창을 뚫고 나옴
        //해결 : 항상 자동확장 기능이 켜져있도록 변경. 자동 확장 기능 관련한 이벤트 코드도 모바일 사파리에서 예외 처리
        if (this.oNavigator.msafari) {
            htOption.wfnCallback = function() {};
        }

        this.bAutoResize = true;
        this.AutoResizer = new nhn.husky.AutoResizer(this.elEditingArea, htOption);
        this.AutoResizer.bind();
    },

    /**
     * [SMARTEDITORSUS-677] HTML 편집 영역 자동 확장 처리 종료
     */
    stopAutoResize: function() {
        this.AutoResizer.unbind();
    },

    getIR: function() {
        var sIR = this.getRawContents();
        if (this.oApp.applyConverter) {
            sIR = this.oApp.applyConverter(this.sMode + "_TO_IR", sIR, this.oApp.getWYSIWYGDocument());
        }

        return sIR;
    },

    setIR: function(sIR) {
        if (sIR.toLowerCase() === "<br>" || sIR.toLowerCase() === "<p>&nbsp;</p>" || sIR.toLowerCase() === "<p><br></p>" || sIR.toLowerCase() === "<p></p>") {
            sIR = "";
        }

        // [SMARTEDITORSUS-1589] 문서 모드가 Edge인 IE11에서 WYSIWYG 모드와 HTML 모드 전환 시, 문말에 무의미한 <br> 두 개가 첨가되는 현상으로 필터링 추가
        var htBrowser = jindo.$Agent().navigator();
        if (htBrowser.ie && htBrowser.nativeVersion == 11 && document.documentMode == 11) { // Edge 모드의 documentMode 값은 11
            sIR = sIR.replace(/(<br><br>$)/, "");
        }
        // --[SMARTEDITORSUS-1589]

        var sContent = sIR;
        if (this.oApp.applyConverter) {
            sContent = this.oApp.applyConverter("IR_TO_" + this.sMode, sContent, this.oApp.getWYSIWYGDocument());
        }

        this.setRawContents(sContent);
    },

    setRawContents: function(sContent) {
        if (typeof sContent !== 'undefined') {
            this.elEditingArea.value = sContent;
        }
    },

    getRawContents: function() {
        return this.elEditingArea.value;
    },

    focus: function() {
        this.elEditingArea.focus();
    }
});

/**
 * Selection for textfield
 * @author hooriza
 */
if (typeof window.TextRange == 'undefined') {
    window.TextRange = {};
}
TextRange = function(oEl, oDoc) {
    this._o = oEl;
    this._oDoc = (oDoc || document);
};

TextRange.prototype.getSelection = function() {
    var obj = this._o;
    var ret = [-1, -1];

    if (isNaN(this._o.selectionStart)) {
        obj.focus();

        // textarea support added by nagoon97
        var range = this._oDoc.body.createTextRange();
        var rangeField = null;

        rangeField = this._oDoc.selection.createRange().duplicate();
        range.moveToElementText(obj);
        rangeField.collapse(true);
        range.setEndPoint("EndToEnd", rangeField);
        ret[0] = range.text.length;

        rangeField = this._oDoc.selection.createRange().duplicate();
        range.moveToElementText(obj);
        rangeField.collapse(false);
        range.setEndPoint("EndToEnd", rangeField);
        ret[1] = range.text.length;

        obj.blur();
    } else {
        ret[0] = obj.selectionStart;
        ret[1] = obj.selectionEnd;
    }

    return ret;
};

TextRange.prototype.setSelection = function(start, end) {
    var obj = this._o;
    if (typeof end == 'undefined') {
        end = start;
    }

    if (obj.setSelectionRange) {
        obj.setSelectionRange(start, end);
    } else if (obj.createTextRange) {
        var range = obj.createTextRange();
        range.collapse(true);
        range.moveStart("character", start);
        range.moveEnd("character", end - start);
        range.select();
        obj.blur();
    }
};

TextRange.prototype.copy = function() {
    var r = this.getSelection();
    return this._o.value.substring(r[0], r[1]);
};

TextRange.prototype.paste = function(sStr) {
    var obj = this._o;
    var sel = this.getSelection();
    var value = obj.value;
    var pre = value.substr(0, sel[0]);
    var post = value.substr(sel[1]);

    value = pre + sStr + post;
    obj.value = value;

    var n = 0;
    if (typeof this._oDoc.body.style.maxHeight == "undefined") {
        var a = pre.match(/\n/gi);
        n = (a !== null ? a.length : 0);
    }

    this.setSelection(sel[0] + sStr.length - n);
};

TextRange.prototype.cut = function() {
    var r = this.copy();
    this.paste('');
    return r;
};
//}
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations directly related to editing the HTML source code using Textarea element
 * @name hp_SE_EditingArea_TEXT.js
 * @required SE_EditingAreaManager
 */
nhn.husky.SE_EditingArea_TEXT = jindo.$Class({
    name: "SE_EditingArea_TEXT",
    sMode: "TEXT",
    sRxConverter: '@[0-9]+@',
    bAutoResize: false, // [SMARTEDITORSUS-677] 해당 편집모드의 자동확장 기능 On/Off 여부
    nMinHeight: null, // [SMARTEDITORSUS-677] 편집 영역의 최소 높이

    $init: function(sTextArea) {
        this.elEditingArea = jindo.$(sTextArea);
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oNavigator = jindo.$Agent().navigator();
        this.oApp.exec("REGISTER_EDITING_AREA", [this]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getTextAreaContents", jindo.$Fn(this.getRawContents, this).bind()]);
    },

    $ON_MSG_APP_READY: function() {
        if (!!this.oApp.getEditingAreaHeight) {
            this.nMinHeight = this.oApp.getEditingAreaHeight(); // [SMARTEDITORSUS-677] 편집 영역의 최소 높이를 가져와 자동 확장 처리를 할 때 사용
        }
    },

    $ON_REGISTER_CONVERTERS: function() {
        this.oApp.exec("ADD_CONVERTER", ["IR_TO_TEXT", jindo.$Fn(this.irToText, this).bind()]);
        this.oApp.exec("ADD_CONVERTER", ["TEXT_TO_IR", jindo.$Fn(this.textToIr, this).bind()]);
    },

    $ON_CHANGE_EDITING_MODE: function(sMode) {
        if (sMode == this.sMode) {
            this.elEditingArea.style.display = "block";
            /**
             * [SMARTEDITORSUS-1889] Editor 영역을 표시하고 숨기는 데 있어서
             * display 속성 대신 visibility 속성을 사용하게 되면서,
             * Editor 영역이 화면에서 사라지지만
             * 공간을 차지하게 되므로
             * 그 아래로 위치하는 Text 영역을 끌어올려 준다.
             *
             * @see hp_SE_EditingArea_WYSIWYG.js
             * */
            this.elEditingArea.style.position = "absolute";
            this.elEditingArea.style.top = "0px";
            // --[SMARTEDITORSUS-1889]
        } else {
            this.elEditingArea.style.display = "none";
            // [SMARTEDITORSUS-1889]
            this.elEditingArea.style.position = "";
            this.elEditingArea.style.top = "";
            // --[SMARTEDITORSUS-1889]
        }
    },

    $AFTER_CHANGE_EDITING_MODE: function(sMode, bNoFocus) {
        if (sMode == this.sMode && !bNoFocus) {
            var o = new TextRange(this.elEditingArea);
            o.setSelection(0, 0);
        }

        //[SMARTEDITORSUS-1017] [iOS5대응] 모드 전환 시 textarea에 포커스가 있어도 글자가 입력이 안되는 현상
        //원인 : WYSIWYG모드가 아닐 때에도 iframe의 contentWindow에 focus가 가면서 focus기능이 작동하지 않음
        //해결 : WYSIWYG모드 일때만 실행 되도록 조건식 추가 및 기존에 blur처리 코드 삭제
        //모바일 textarea에서는 직접 클릭을해야만 키보드가 먹히기 때문에 우선은 커서가 안보이게 해서 사용자가 직접 클릭을 유도.
        // if(!!this.oNavigator.msafari){
        // this.elEditingArea.blur();
        // }
    },

    irToText: function(sHtml) {
        var sContent = sHtml,
            nIdx = 0;
        var aTemp = sContent.match(new RegExp(this.sRxConverter)); // applyConverter에서 추가한 sTmpStr를 잠시 제거해준다.
        if (aTemp !== null) {
            sContent = sContent.replace(new RegExp(this.sRxConverter), "");
        }

        //0.안보이는 값들에 대한 정리. (에디터 모드에 view와 text모드의 view를 동일하게 해주기 위해서)
        sContent = sContent.replace(/\r/g, ''); // MS엑셀 테이블에서 tr별로 분리해주는 역할이\r이기 때문에  text모드로 변경시에 가독성을 위해 \r 제거하는 것은 임시 보류. - 11.01.28 by cielo
        sContent = sContent.replace(/[\n|\t]/g, ''); // 개행문자, 안보이는 공백 제거
        sContent = sContent.replace(/[\v|\f]/g, ''); // 개행문자, 안보이는 공백 제거
        //1. 먼저, 빈 라인 처리 .
        sContent = sContent.replace(/<p><br><\/p>/gi, '\n');
        sContent = sContent.replace(/<P>&nbsp;<\/P>/gi, '\n');

        //2. 빈 라인 이외에 linebreak 처리.
        sContent = sContent.replace(/<br(\s)*\/?>/gi, '\n'); // br 태그를 개행문자로
        sContent = sContent.replace(/<br(\s[^\/]*)?>/gi, '\n'); // br 태그를 개행문자로
        sContent = sContent.replace(/<\/p(\s[^\/]*)?>/gi, '\n'); // p 태그를 개행문자로

        sContent = sContent.replace(/<\/li(\s[^\/]*)?>/gi, '\n'); // li 태그를 개행문자로 [SMARTEDITORSUS-107]개행 추가
        sContent = sContent.replace(/<\/tr(\s[^\/]*)?>/gi, '\n'); // tr 태그를 개행문자로 [SMARTEDITORSUS-107]개행 추가

        // 마지막 \n은 로직상 불필요한 linebreak를 제공하므로 제거해준다.
        nIdx = sContent.lastIndexOf('\n');
        if (nIdx > -1 && sContent.substring(nIdx) == '\n') {
            sContent = sContent.substring(0, nIdx);
        }

        sContent = jindo.$S(sContent).stripTags().toString();
        sContent = this.unhtmlSpecialChars(sContent);
        if (aTemp !== null) { // 제거했던sTmpStr를 추가해준다.
            sContent = aTemp[0] + sContent;
        }

        return sContent;
    },

    textToIr: function(sHtml) {
        if (!sHtml) {
            return;
        }

        var sContent = sHtml,
            aTemp = null;

        // applyConverter에서 추가한 sTmpStr를 잠시 제거해준다. sTmpStr도 하나의 string으로 인식하는 경우가 있기 때문.
        aTemp = sContent.match(new RegExp(this.sRxConverter));
        if (aTemp !== null) {
            sContent = sContent.replace(aTemp[0], "");
        }

        sContent = this.htmlSpecialChars(sContent);
        sContent = this._addLineBreaker(sContent);

        if (aTemp !== null) {
            sContent = aTemp[0] + sContent;
        }

        return sContent;
    },

    _addLineBreaker: function(sContent) {
        if (this.oApp.sLineBreaker === "BR") {
            return sContent.replace(/\r?\n/g, "<BR>");
        }

        var oContent = new StringBuffer(),
            aContent = sContent.split('\n'), // \n을 기준으로 블럭을 나눈다.
            aContentLng = aContent.length,
            sTemp = "";

        for (var i = 0; i < aContentLng; i++) {
            sTemp = jindo.$S(aContent[i]).trim().$value();
            if (i === aContentLng - 1 && sTemp === "") {
                break;
            }

            if (sTemp !== null && sTemp !== "") {
                oContent.append('<P>');
                oContent.append(aContent[i]);
                oContent.append('</P>');
            } else {
                if (!jindo.$Agent().navigator().ie) {
                    oContent.append('<P><BR></P>');
                } else {
                    oContent.append('<P>&nbsp;<\/P>');
                }
            }
        }

        return oContent.toString();
    },

    /**
     * [SMARTEDITORSUS-677] HTML 편집 영역 자동 확장 처리 시작
     */
    startAutoResize: function() {
        var htOption = {
            nMinHeight: this.nMinHeight,
            wfnCallback: jindo.$Fn(this.oApp.checkResizeGripPosition, this).bind()
        };

        //[SMARTEDITORSUS-941][iOS5대응]아이패드의 자동 확장 기능이 동작하지 않을 때 에디터 창보다 긴 내용을 작성하면 에디터를 뚫고 나오는 현상
        //원인 : 자동확장 기능이 정지 될 경우 iframe에 스크롤이 생기지 않고, 창을 뚫고 나옴
        //해결 : 항상 자동확장 기능이 켜져있도록 변경. 자동 확장 기능 관련한 이벤트 코드도 모바일 사파리에서 예외 처리
        if (this.oNavigator.msafari) {
            htOption.wfnCallback = function() {};
        }

        this.bAutoResize = true;
        this.AutoResizer = new nhn.husky.AutoResizer(this.elEditingArea, htOption);
        this.AutoResizer.bind();
    },

    /**
     * [SMARTEDITORSUS-677] HTML 편집 영역 자동 확장 처리 종료
     */
    stopAutoResize: function() {
        this.AutoResizer.unbind();
    },

    getIR: function() {
        var sIR = this.getRawContents();
        if (this.oApp.applyConverter) {
            sIR = this.oApp.applyConverter(this.sMode + "_TO_IR", sIR, this.oApp.getWYSIWYGDocument());
        }
        return sIR;
    },

    setIR: function(sIR) {
        var sContent = sIR;
        if (this.oApp.applyConverter) {
            sContent = this.oApp.applyConverter("IR_TO_" + this.sMode, sContent, this.oApp.getWYSIWYGDocument());
        }

        this.setRawContents(sContent);
    },

    setRawContents: function(sContent) {
        if (typeof sContent !== 'undefined') {
            this.elEditingArea.value = sContent;
        }
    },

    getRawContents: function() {
        return this.elEditingArea.value;
    },

    focus: function() {
        this.elEditingArea.focus();
    },

    /**
     * HTML 태그에 해당하는 글자가 먹히지 않도록 바꿔주기
     *
     * 동작) & 를 &amp; 로, < 를 &lt; 로, > 를 &gt; 로 바꿔준다
     *
     * @param {String} sText
     * @return {String}
     */
    htmlSpecialChars: function(sText) {
        return sText.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/ /g, '&nbsp;');
    },

    /**
     * htmlSpecialChars 의 반대 기능의 함수
     *
     * 동작) &amp, &lt, &gt, &nbsp 를 각각 &, <, >, 빈칸으로 바꿔준다
     *
     * @param {String} sText
     * @return {String}
     */
    unhtmlSpecialChars: function(sText) {
        return sText.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&');
    }
});
/**
 * @name SE2M_EditingAreaRuler
 * @description
 * @class nhn.husky.SE2M_EditingAreaRuler
 * @author
 * @version
 */
nhn.husky.SE2M_EditingAreaRuler = jindo.$Class({
    name: 'SE2M_EditingAreaRuler',

    $init: function(elAppContainer) {
        this._assignHTMLElements(elAppContainer);
        this.htConfig = (nhn.husky.SE2M_Configuration.SE2M_EditingAreaRuler || {});
    },

    _assignHTMLElements: function(elAppContainer) {
        //@ec[
        this.elEditingAreaRuler = jindo.$$.getSingle('DIV.se2_editor_mark', elAppContainer);
        //@ec]
    },

    _adjustWysiwygWidth: function() {
        var welWysiwygBody = jindo.$Element(this.oApp.getWYSIWYGDocument().body);
        if (!welWysiwygBody || !this.htConfig[this.nRulerWidth]) {
            return;
        }

        var sStyle = '{' + this.htConfig[this.nRulerWidth].sStyle.replace(/;/ig, ',').replace(/\"/ig, '') + '}';
        var oStyle = jindo.$Json(sStyle.replace(/(\w+)\s?:\s?([\w\s]*[^,}])/ig, '$1:"$2"'));
        welWysiwygBody.css(oStyle.toObject());

        var welEditingAreaRuler = jindo.$Element(this.elEditingAreaRuler);
        var oRulerStyle = {
            "width": welWysiwygBody.css('width'),
            "marginLeft": welWysiwygBody.css('marginLeft'),
            "top": welWysiwygBody.css('marginTop')
        };
        welEditingAreaRuler.css(oRulerStyle);

        if (!!this.bUse) {
            welEditingAreaRuler.show();
        } else {
            welEditingAreaRuler.hide();
        }
    },

    $ON_ENABLE_WYSIWYG_RULER: function() {
        if (!!this.oApp.htOptions[this.name]) {
            this.bUse = (this.oApp.htOptions[this.name].bUse || false);
            this.nRulerWidth = (this.oApp.htOptions[this.name].nRulerWidth || 0);
        }

        if (!this.elEditingAreaRuler || 0 >= this.nRulerWidth) {
            return;
        }
        this._adjustWysiwygWidth();
    },

    $ON_CHANGE_EDITING_MODE: function(sMode) {
        if (!this.elEditingAreaRuler) {
            return;
        }
        if ('WYSIWYG' === sMode && !!this.bUse && !!this.htConfig[this.nRulerWidth]) {
            jindo.$Element(this.elEditingAreaRuler).show();
        } else {
            jindo.$Element(this.elEditingAreaRuler).hide();
        }
    }
});
//}
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to resizing the editing area vertically
 * @name hp_SE_EditingAreaVerticalResizer.js
 */
nhn.husky.SE_EditingAreaVerticalResizer = jindo.$Class({
    name: "SE_EditingAreaVerticalResizer",

    oResizeGrip: null,
    sCookieNotice: "bHideResizeNotice",

    nEditingAreaMinHeight: null, // [SMARTEDITORSUS-677] 편집 영역의 최소 높이
    htConversionMode: null,

    $init: function(elAppContainer, htConversionMode) {
        this.htConversionMode = htConversionMode;
        this._assignHTMLElements(elAppContainer);
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["isUseVerticalResizer", jindo.$Fn(this.isUseVerticalResizer, this).bind()]);
    },

    $ON_MSG_APP_READY: function() {
        if (this.oApp.bMobile) {
            // [SMARTEDITORSUS-941] 모바일에서는 자동확장기능이 항상 켜져있도록 한다.
            // [SMARTEDITORSUS-1679] 하지만 사용자가 조절하지는 못하도록 버튼은 비활성화 한다.
            this.oResizeGrip.disabled = true;
            this.oResizeGrip.style.height = '0'; // 버튼의 문구를 가림. display:none을 하면 안드로이드에서 높이 계산오류 발생
        } else {
            this.oApp.exec("REGISTER_HOTKEY", ["shift+esc", "FOCUS_RESIZER"]);

            // [SMARTEDITORSUS-906][SMARTEDITORSUS-1433] Resizbar 사용 여부 처리 (true:사용함/ false:사용하지 않음)
            if (this.isUseVerticalResizer()) {
                this.oResizeGrip.style.display = 'block';
                if (!!this.welNoticeLayer && !Number(jindo.$Cookie().get(this.sCookieNotice))) {
                    this.welNoticeLayer.delegate("click", "BUTTON.bt_clse", jindo.$Fn(this._closeNotice, this).bind());
                    this.welNoticeLayer.show();
                }
                this.$FnMouseDown = jindo.$Fn(this._mousedown, this);
                this.$FnMouseMove = jindo.$Fn(this._mousemove, this);
                this.$FnMouseUp = jindo.$Fn(this._mouseup, this);
                this.$FnMouseOver = jindo.$Fn(this._mouseover, this);
                this.$FnMouseOut = jindo.$Fn(this._mouseout, this);

                this.$FnMouseDown.attach(this.oResizeGrip, "mousedown");
                this.$FnMouseOver.attach(this.oResizeGrip, "mouseover");
                this.$FnMouseOut.attach(this.oResizeGrip, "mouseout");

            } else {
                this.oResizeGrip.style.display = 'none';
                if (!this.oApp.isUseModeChanger()) {
                    this.elModeToolbar.style.display = "none";
                }
            }
        }

        this.oApp.exec("ADD_APP_PROPERTY", ["checkResizeGripPosition", jindo.$Fn(this.checkResizeGripPosition, this).bind()]); // [SMARTEDITORSUS-677]

        if (!!this.oApp.getEditingAreaHeight) {
            this.nEditingAreaMinHeight = this.oApp.getEditingAreaHeight(); // [SMARTEDITORSUS-677] 편집 영역의 최소 높이를 가져와 Gap 처리 시 사용
        }
    },

    isUseVerticalResizer: function() {
        return (typeof(this.htConversionMode) === 'undefined' || typeof(this.htConversionMode.bUseVerticalResizer) === 'undefined' || this.htConversionMode.bUseVerticalResizer === true) ? true : false;
    },

    /**
     * [SMARTEDITORSUS-677] [에디터 자동확장 ON인 경우]
     * 입력창 크기 조절 바의 위치를 확인하여 브라우저 하단에 위치한 경우 자동확장을 멈춤
     */
    checkResizeGripPosition: function(bExpand) {
        var oDocument = jindo.$Document();
        var nGap = (jindo.$Element(this.oResizeGrip).offset().top - oDocument.scrollPosition().top + 25) - oDocument.clientSize().height;

        if (nGap <= 0) {
            return;
        }

        if (bExpand) {
            if (this.nEditingAreaMinHeight > this.oApp.getEditingAreaHeight() - nGap) { // [SMARTEDITORSUS-822] 수정 모드인 경우에 대비
                nGap = (-1) * (this.nEditingAreaMinHeight - this.oApp.getEditingAreaHeight());
            }

            // Gap 만큼 편집영역 사이즈를 조절하여
            // 사진 첨부나 붙여넣기 등의 사이즈가 큰 내용 추가가 있었을 때 입력창 크기 조절 바가 숨겨지지 않도록 함
            this.oApp.exec("MSG_EDITING_AREA_RESIZE_STARTED");
            this.oApp.exec("RESIZE_EDITING_AREA_BY", [0, (-1) * nGap]);
            this.oApp.exec("MSG_EDITING_AREA_RESIZE_ENDED");
        }

        this.oApp.exec("STOP_AUTORESIZE_EDITING_AREA");
    },

    $ON_FOCUS_RESIZER: function() {
        this.oApp.exec("IE_HIDE_CURSOR");
        this.oResizeGrip.focus();
    },

    _assignHTMLElements: function(elAppContainer, htConversionMode) {
        //@ec[
        this.oResizeGrip = jindo.$$.getSingle("BUTTON.husky_seditor_editingArea_verticalResizer", elAppContainer);
        this.elModeToolbar = jindo.$$.getSingle("DIV.se2_conversion_mode", elAppContainer);
        //@ec]

        this.welNoticeLayer = jindo.$Element(jindo.$$.getSingle("DIV.husky_seditor_resize_notice", elAppContainer));
        this.welConversionMode = jindo.$Element(this.oResizeGrip.parentNode);
    },

    _mouseover: function(oEvent) {
        oEvent.stopBubble();
        this.welConversionMode.addClass("controller_on");
    },

    _mouseout: function(oEvent) {
        oEvent.stopBubble();
        this.welConversionMode.removeClass("controller_on");
    },

    _mousedown: function(oEvent) {
        this.iStartHeight = oEvent.pos().clientY;
        this.iStartHeightOffset = oEvent.pos().layerY;

        this.$FnMouseMove.attach(document, "mousemove");
        this.$FnMouseUp.attach(document, "mouseup");

        this.iStartHeight = oEvent.pos().clientY;

        this.oApp.exec("HIDE_ACTIVE_LAYER");
        this.oApp.exec("HIDE_ALL_DIALOG_LAYER");

        this.oApp.exec("MSG_EDITING_AREA_RESIZE_STARTED", [this.$FnMouseDown, this.$FnMouseMove, this.$FnMouseUp]);
    },

    _mousemove: function(oEvent) {
        var iHeightChange = oEvent.pos().clientY - this.iStartHeight;

        this.oApp.exec("RESIZE_EDITING_AREA_BY", [0, iHeightChange]);
    },

    _mouseup: function(oEvent) {
        this.$FnMouseMove.detach(document, "mousemove");
        this.$FnMouseUp.detach(document, "mouseup");

        this.oApp.exec("MSG_EDITING_AREA_RESIZE_ENDED", [this.$FnMouseDown, this.$FnMouseMove, this.$FnMouseUp]);
    },

    _closeNotice: function() {
        this.welNoticeLayer.hide();
        jindo.$Cookie().set(this.sCookieNotice, 1, 365 * 10);
    }
});
//}
/**
 * @pluginDesc Enter키 입력시에 현재 줄을 P 태그로 감거나 <br> 태그를 삽입한다.
 */
nhn.husky.SE_WYSIWYGEnterKey = jindo.$Class({
    name: "SE_WYSIWYGEnterKey",

    $init: function(sLineBreaker) {
        if (sLineBreaker == "BR") {
            this.sLineBreaker = "BR";
        } else {
            this.sLineBreaker = "P";
        }

        this.htBrowser = jindo.$Agent().navigator();

        // [SMARTEDITORSUS-227] IE 인 경우에도 에디터 Enter 처리 로직을 사용하도록 수정
        if (this.htBrowser.opera && this.sLineBreaker == "P") {
            this.$ON_MSG_APP_READY = function() {};
        }

        /**
         *  [SMARTEDITORSUS-230] 밑줄+색상변경 후, 엔터치면 스크립트 오류
         *  [SMARTEDITORSUS-180] [IE9] 배경색 적용 후, 엔터키 2회이상 입력시 커서위치가 다음 라인으로 이동하지 않음
         *      오류 현상 :     IE9 에서 엔터 후 생성된 P 태그가 "빈 SPAN 태그만 가지는 경우" P 태그 영역이 보이지 않거나 포커스가 위로 올라가 보임
         *      해결 방법 :     커서 홀더로 IE 이외에서는 <br> 을 사용
         *                      - IE 에서는 렌더링 시 <br> 부분에서 비정상적인 P 태그가 생성되어 [SMARTEDITORSUS-230] 오류 발생
         *                      unescape("%uFEFF") (BOM) 을 추가
         *                      - IE9 표준모드에서 [SMARTEDITORSUS-180] 의 문제가 발생함
         *                      (unescape("%u2028") (Line separator) 를 사용하면 P 가 보여지나 사이드이펙트가 우려되어 사용하지 않음)
         *  IE 브라우저에서 Enter 처리 시, &nbsp; 를 넣어주므로 해당 방식을 그대로 사용하도록 수정함
         */
        if (this.htBrowser.ie) {
            this._addCursorHolder = this._addCursorHolderSpace;

            //[SMARTEDITORSUS-1652] 글자크기 지정후 엔터를 치면 빈SPAN으로 감싸지는데 IE에서 빈SPAN은 높이값을 갖지 않아 커서가 올라가 보이게 됨
            // 따라서, IE의 경우 브라우저모드와 상관없이 다음라인의 SPAN에 무조건 ExtraCursorHolder 를 넣어주도록 코멘트처리함
            //          if(this.htBrowser.nativeVersion < 9 || document.documentMode < 9){
            //              this._addExtraCursorHolder = function(){};
            //          }
        } else {
            this._addExtraCursorHolder = function() {};
            this._addBlankText = function() {};
        }
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["sLineBreaker", this.sLineBreaker]);

        this.oSelection = this.oApp.getEmptySelection();
        this.tmpTextNode = this.oSelection._document.createTextNode(unescape("%u00A0")); // 공백(&nbsp;) 추가 시 사용할 노드
        jindo.$Fn(this._onKeyDown, this).attach(this.oApp.getWYSIWYGDocument(), "keydown");
    },

    _onKeyDown: function(oEvent) {
        var oKeyInfo = oEvent.key();

        if (oKeyInfo.shift) {
            return;
        }

        if (oKeyInfo.enter) {
            if (this.sLineBreaker == "BR") {
                this._insertBR(oEvent);
            } else {
                this._wrapBlock(oEvent);
            }
        }
    },

    /**
     * [SMARTEDITORSUS-950] 에디터 적용 페이지의 Compatible meta IE=edge 설정 시 줄간격 벌어짐 이슈 (<BR>)
     */
    $ON_REGISTER_CONVERTERS: function() {
        this.oApp.exec("ADD_CONVERTER", ["IR_TO_DB", jindo.$Fn(this.onIrToDB, this).bind()]);
    },

    /**
     * IR_TO_DB 변환기 처리
     *  Chrome, FireFox인 경우에만 아래와 같은 처리를 합니다.
     *  : 저장 시 본문 영역에서 P 아래의 모든 하위 태그 중 가장 마지막 childNode가 BR인 경우를 탐색하여 이를 &nbsp;로 변경해 줍니다.
     */
    onIrToDB: function(sHTML) {
        var sContents = sHTML,
            rxRegEx = /<br(\s[^>]*)?\/?>((?:<\/span>)?<\/p>)/gi,
            rxRegExWhitespace = /(<p[^>]*>)(?:[\s^>]*)(<\/p>)/gi;

        sContents = sContents.replace(rxRegEx, "&nbsp;$2");
        sContents = sContents.replace(rxRegExWhitespace, "$1&nbsp;$2");

        return sContents;
    },

    // [IE] Selection 내의 노드를 가져와 빈 노드에 unescape("%uFEFF") (BOM) 을 추가
    _addBlankText: function(oSelection) {
        var oNodes = oSelection.getNodes(),
            i, nLen, oNode, oNodeChild, tmpTextNode;

        for (i = 0, nLen = oNodes.length; i < nLen; i++) {
            oNode = oNodes[i];

            if (oNode.nodeType !== 1 || oNode.tagName !== "SPAN") {
                continue;
            }

            if (oNode.id.indexOf(oSelection.HUSKY_BOOMARK_START_ID_PREFIX) > -1 ||
                oNode.id.indexOf(oSelection.HUSKY_BOOMARK_END_ID_PREFIX) > -1) {
                continue;
            }

            oNodeChild = oNode.firstChild;

            if (!oNodeChild ||
                (oNodeChild.nodeType == 3 && nhn.husky.SE2M_Utils.isBlankTextNode(oNodeChild)) ||
                (oNodeChild.nodeType == 1 && oNode.childNodes.length == 1 &&
                    (oNodeChild.id.indexOf(oSelection.HUSKY_BOOMARK_START_ID_PREFIX) > -1 || oNodeChild.id.indexOf(oSelection.HUSKY_BOOMARK_END_ID_PREFIX) > -1))) {
                tmpTextNode = oSelection._document.createTextNode(unescape("%uFEFF"));
                oNode.appendChild(tmpTextNode);
            }
        }
    },

    // [IE 이외] 빈 노드 내에 커서를 표시하기 위한 처리
    _addCursorHolder: function(elWrapper) {
        var elStyleOnlyNode = elWrapper;

        if (elWrapper.innerHTML == "" || (elStyleOnlyNode = this._getStyleOnlyNode(elWrapper))) {
            elStyleOnlyNode.innerHTML = "<br>";
        }
        if (!elStyleOnlyNode) {
            elStyleOnlyNode = this._getStyleNode(elWrapper);
        }

        return elStyleOnlyNode;
    },

    // [IE] 빈 노드 내에 커서를 표시하기 위한 처리 (_addSpace 사용)
    _addCursorHolderSpace: function(elWrapper) {
        var elNode;

        this._addSpace(elWrapper);

        elNode = this._getStyleNode(elWrapper);

        if (elNode.innerHTML == "" && elNode.nodeName.toLowerCase() != "param") {
            try {
                elNode.innerHTML = unescape("%uFEFF");
            } catch (e) {}
        }

        return elNode;
    },

    /**
     * [SMARTEDITORSUS-1513] 시작노드와 끝노드 사이에 첫번째 BR을 찾는다. BR이 없는 경우 끝노드를 반환한다.
     * @param {Node} oStart 검사할 시작노드
     * @param {Node} oEnd 검사할 끝노드
     * @return {Node} 첫번째 BR 혹은 끝노드를 반환한다.
     */
    _getBlockEndNode: function(oStart, oEnd) {
        if (!oStart) {
            return oEnd;
        } else if (oStart.nodeName === "BR") {
            return oStart;
        } else if (oStart === oEnd) {
            return oEnd;
        } else {
            return this._getBlockEndNode(oStart.nextSibling, oEnd);
        }
    },

    /**
     * [SMARTEDITORSUS-1797] 북마크 다음노드가가 텍스트노드인 경우, 문자열 앞쪽의 공백문자(\u0020)를 &nbsp;(\u00A0) 문자로 변환한다.
     */
    _convertHeadSpace: function(elBookmark) {
        var elNext;
        if (elBookmark && (elNext = elBookmark.nextSibling) && elNext.nodeType === 3) {
            var sText = elNext.nodeValue,
                sSpaces = "";
            for (var i = 0, ch;
                (ch = sText[i]); i++) {
                if (ch !== "\u0020") {
                    break;
                }
                sSpaces += "\u00A0";
            }
            if (i > 0) {
                elNext.nodeValue = sSpaces + sText.substring(i);
            }
        }
    },

    _wrapBlock: function(oEvent, sWrapperTagName) {
        var oSelection = this.oApp.getSelection(),
            sBM = oSelection.placeStringBookmark(),
            oLineInfo = oSelection.getLineInfo(),
            oStart = oLineInfo.oStart,
            oEnd = oLineInfo.oEnd,
            oSWrapper,
            oEWrapper,
            elStyleOnlyNode;

        // line broke by sibling
        // or
        // the parent line breaker is just a block container
        if (!oStart.bParentBreak || oSelection.rxBlockContainer.test(oStart.oLineBreaker.tagName)) {
            oEvent.stop();

            //  선택된 내용은 삭제
            oSelection.deleteContents();
            if (!!oStart.oNode.parentNode && oStart.oNode.parentNode.nodeType !== 11) {
                //  LineBreaker 로 감싸서 분리
                oSWrapper = this.oApp.getWYSIWYGDocument().createElement(this.sLineBreaker);
                oSelection.moveToBookmark(sBM); //oSelection.moveToStringBookmark(sBM, true);
                oSelection.setStartBefore(oStart.oNode);
                this._addBlankText(oSelection);
                oSelection.surroundContents(oSWrapper);
                oSelection.collapseToEnd();

                oEWrapper = this.oApp.getWYSIWYGDocument().createElement(this.sLineBreaker);
                // [SMARTEDITORSUS-1513] oStart.oNode와 oEnd.oNode 사이에 BR이 있는 경우, 다음 엔터시 스타일이 비정상으로 복사되기 때문에 중간에 BR이 있으면 BR까지만 잘라서 세팅한다.
                var oEndNode = this._getBlockEndNode(oStart.oNode, oEnd.oNode);
                // [SMARTEDITORSUS-1743] oStart.oNode가 BR인 경우, setStartBefore와 setEndAfter에 모두 oStart.oNode로 세팅을 시도하기 때문에 스크립트 오류가 발생한다.
                // 따라서, _getBlockEndNode 메서드를 통해 찾은 BR이 oStart.oNode인 경우, oEnd.oNode 를 세팅한다.
                if (oEndNode === oStart.oNode) {
                    oEndNode = oEnd.oNode;
                }
                oSelection.setEndAfter(oEndNode);
                this._addBlankText(oSelection);
                oSelection.surroundContents(oEWrapper);
                oSelection.moveToStringBookmark(sBM, true); // [SMARTEDITORSUS-180] 포커스 리셋
                oSelection.collapseToEnd(); // [SMARTEDITORSUS-180] 포커스 리셋
                oSelection.removeStringBookmark(sBM);
                oSelection.select();

                // P로 분리했기 때문에 BR이 들어있으면 제거한다.
                if (oEWrapper.lastChild !== null && oEWrapper.lastChild.tagName == "BR") {
                    oEWrapper.removeChild(oEWrapper.lastChild);
                }

                //  Cursor Holder 추가
                // insert a cursor holder(br) if there's an empty-styling-only-tag surrounding current cursor
                elStyleOnlyNode = this._addCursorHolder(oEWrapper);

                if (oEWrapper.nextSibling && oEWrapper.nextSibling.tagName == "BR") {
                    oEWrapper.parentNode.removeChild(oEWrapper.nextSibling);
                }

                oSelection.selectNodeContents(elStyleOnlyNode);
                oSelection.collapseToStart();
                oSelection.select();

                this.oApp.exec("CHECK_STYLE_CHANGE");

                sBM = oSelection.placeStringBookmark();
                setTimeout(jindo.$Fn(function(sBM) {
                    var elBookmark = oSelection.getStringBookmark(sBM);
                    if (!elBookmark) {
                        return;
                    }

                    oSelection.moveToStringBookmark(sBM);
                    oSelection.select();
                    oSelection.removeStringBookmark(sBM);
                }, this).bind(sBM), 0);

                return;
            }
        }

        var elBookmark = oSelection.getStringBookmark(sBM, true);

        // 아래는 기본적으로 브라우저 기본 기능에 맡겨서 처리함
        if (this.htBrowser.firefox) {
            if (elBookmark && elBookmark.nextSibling && elBookmark.nextSibling.tagName == "IFRAME") {
                // [WOEDITOR-1603] FF에서 본문에 글감 삽입 후 엔터키 입력하면 글감이 복사되는 문제
                setTimeout(jindo.$Fn(function(sBM) {
                    var elBookmark = oSelection.getStringBookmark(sBM);
                    if (!elBookmark) {
                        return;
                    }

                    oSelection.moveToStringBookmark(sBM);
                    oSelection.select();
                    oSelection.removeStringBookmark(sBM);
                }, this).bind(sBM), 0);
            } else {
                // [SMARTEDITORSUS-1797] 엔터시 공백문자를 &nbsp; 로 변환
                // FF의 경우 2번이상 엔터치면 앞쪽공백이 사라져서 setTimeout으로 처리
                setTimeout(jindo.$Fn(function(sBM) {
                    var elBookmark = oSelection.getStringBookmark(sBM, true);
                    if (!elBookmark) {
                        return;
                    }

                    this._convertHeadSpace(elBookmark);
                    oSelection.removeStringBookmark(sBM);
                }, this).bind(sBM), 0);
            }
        } else if (this.htBrowser.ie) {
            var elParentNode = elBookmark.parentNode,
                bAddUnderline = false,
                bAddLineThrough = false;

            if (!elBookmark || !elParentNode) { // || elBookmark.nextSibling){
                oSelection.removeStringBookmark(sBM);
                return;
            }

            oSelection.removeStringBookmark(sBM);

            // [SMARTEDITORSUS-1575] 이슈 처리에 따라 아래 부분은 불필요해졌음 (일단 코멘트처리)
            //          // -- [SMARTEDITORSUS-1452]
            //          var bAddCursorHolder = (elParentNode.tagName === "DIV" && elParentNode.parentNode.tagName === "LI");
            //          if (elParentNode.innerHTML !== "" && elParentNode.innerHTML !== unescape("%uFEFF")) {
            //              if (bAddCursorHolder) {
            //
            //                  setTimeout(jindo.$Fn(function() {
            //                      var oSelection = this.oApp.getSelection();
            //                      oSelection.fixCommonAncestorContainer();
            //                      var elLowerNode = oSelection.commonAncestorContainer;
            //                      elLowerNode = oSelection._getVeryFirstRealChild(elLowerNode);
            //
            //                      if (elLowerNode && (elLowerNode.innerHTML === "" || elLowerNode.innerHTML === unescape("%uFEFF"))) {
            //                          elLowerNode.innerHTML = unescape("%uFEFF");
            //                      }
            //                  }, this).bind(elParentNode), 0);
            //              }
            //          } else {
            //              if (bAddCursorHolder) {
            //                  var oSelection = this.oApp.getSelection();
            //                  oSelection.fixCommonAncestorContainer();
            //                  var elLowerNode = oSelection.commonAncestorContainer;
            //                  elLowerNode = oSelection._getVeryFirstRealChild(elLowerNode);
            //                  jindo.$Element(elLowerNode).leave();
            //
            //                  setTimeout(jindo.$Fn(function() {
            //                      var oSelection = this.oApp.getSelection();
            //                      oSelection.fixCommonAncestorContainer();
            //                      var elLowerNode = oSelection.commonAncestorContainer;
            //                      elLowerNode = oSelection._getVeryFirstRealChild(elLowerNode);
            //
            //                      if (elLowerNode && (elLowerNode.innerHTML === "" || elLowerNode.innerHTML === unescape("%uFEFF"))) {
            //                          elLowerNode.innerHTML = unescape("%uFEFF");
            //                      }
            //                  }, this).bind(elParentNode), 0);
            //              }
            //          }
            //          // -- [SMARTEDITORSUS-1452]



            bAddUnderline = (elParentNode.tagName === "U" || nhn.husky.SE2M_Utils.findAncestorByTagName("U", elParentNode) !== null);
            bAddLineThrough = (elParentNode.tagName === "S" || elParentNode.tagName === "STRIKE" ||
                (nhn.husky.SE2M_Utils.findAncestorByTagName("S", elParentNode) !== null && nhn.husky.SE2M_Utils.findAncestorByTagName("STRIKE", elParentNode) !== null));

            // [SMARTEDITORSUS-26] Enter 후에 밑줄/취소선이 복사되지 않는 문제를 처리 (브라우저 Enter 처리 후 실행되도록 setTimeout 사용)
            if (bAddUnderline || bAddLineThrough) {
                setTimeout(jindo.$Fn(this._addTextDecorationTag, this).bind(bAddUnderline, bAddLineThrough), 0);

                return;
            }

            // [SMARTEDITORSUS-180] 빈 SPAN 태그에 의해 엔터 후 엔터가 되지 않은 것으로 보이는 문제 (브라우저 Enter 처리 후 실행되도록 setTimeout 사용)
            setTimeout(jindo.$Fn(this._addExtraCursorHolder, this).bind(elParentNode), 0);
        } else {
            // [SMARTEDITORSUS-1797] 엔터시 공백문자를 &nbsp; 로 변환
            this._convertHeadSpace(elBookmark);
            oSelection.removeStringBookmark(sBM);
        }
    },


    // [IE9 standard mode] 엔터 후의 상/하단 P 태그를 확인하여 BOM, 공백(&nbsp;) 추가
    _addExtraCursorHolder: function(elUpperNode) {
        var oNodeChild,
            oPrevChild,
            elHtml;

        elUpperNode = this._getStyleOnlyNode(elUpperNode);

        // 엔터 후의 상단 SPAN 노드에 BOM 추가
        //if(!!elUpperNode && /^(B|EM|I|LABEL|SPAN|STRONG|SUB|SUP|U|STRIKE)$/.test(elUpperNode.tagName) === false){
        if (!!elUpperNode && elUpperNode.tagName === "SPAN") { // SPAN 인 경우에만 발생함
            oNodeChild = elUpperNode.lastChild;

            while (!!oNodeChild) { // 빈 Text 제거
                oPrevChild = oNodeChild.previousSibling;

                if (oNodeChild.nodeType !== 3) {
                    oNodeChild = oPrevChild;
                    continue;
                }

                if (nhn.husky.SE2M_Utils.isBlankTextNode(oNodeChild)) {
                    oNodeChild.parentNode.removeChild(oNodeChild);
                }

                oNodeChild = oPrevChild;
            }

            elHtml = elUpperNode.innerHTML;

            if (elHtml.replace("\u200B", "").replace("\uFEFF", "") === "") {
                elUpperNode.innerHTML = "\u200B";
            }
        }

        // 엔터 후에 비어있는 하단 SPAN 노드에 BOM 추가
        var oSelection = this.oApp.getSelection(),
            sBM,
            elLowerNode,
            elParent;

        if (!oSelection.collapsed) {
            return;
        }

        oSelection.fixCommonAncestorContainer();
        elLowerNode = oSelection.commonAncestorContainer;

        if (!elLowerNode) {
            return;
        }

        elLowerNode = oSelection._getVeryFirstRealChild(elLowerNode);

        if (elLowerNode.nodeType === 3) {
            elLowerNode = elLowerNode.parentNode;
        }

        if (!elLowerNode || elLowerNode.tagName !== "SPAN") {
            return;
        }

        elHtml = elLowerNode.innerHTML;

        if (elHtml.replace("\u200B", "").replace("\uFEFF", "") === "") {
            elLowerNode.innerHTML = "\u200B";
        }

        // 백스페이스시 커서가 움직이지 않도록 커서를 커서홀더 앞쪽으로 옮긴다.
        oSelection.selectNodeContents(elLowerNode);
        oSelection.collapseToStart();
        oSelection.select();
    },

    // [IE] P 태그 가장 뒤 자식노드로 공백(&nbsp;)을 값으로 하는 텍스트 노드를 추가
    _addSpace: function(elNode) {
        var tmpTextNode, elChild, elNextChild, bHasNBSP, aImgChild, elLastImg;

        if (!elNode) {
            return;
        }

        if (elNode.nodeType === 3) {
            return elNode.parentNode;
        }

        if (elNode.tagName !== "P") {
            return elNode;
        }

        aImgChild = jindo.$Element(elNode).child(function(v) {
            return (v.$value().nodeType === 1 && v.$value().tagName === "IMG");
        }, 1);

        if (aImgChild.length > 0) {
            elLastImg = aImgChild[aImgChild.length - 1].$value();
            elChild = elLastImg.nextSibling;

            while (elChild) {
                elNextChild = elChild.nextSibling;

                if (elChild.nodeType === 3 && (elChild.nodeValue === "&nbsp;" || elChild.nodeValue === unescape("%u00A0") || elChild.nodeValue === "\u200B")) {
                    elNode.removeChild(elChild);
                }

                elChild = elNextChild;
            }
            return elNode;
        }

        elChild = elNode.firstChild;
        elNextChild = elChild;
        bHasNBSP = false;

        while (elChild) { // &nbsp;를 붙일꺼니까 P 바로 아래의 "%uFEFF"는 제거함
            elNextChild = elChild.nextSibling;

            if (elChild.nodeType === 3) {
                if (elChild.nodeValue === unescape("%uFEFF")) {
                    elNode.removeChild(elChild);
                }

                if (!bHasNBSP && (elChild.nodeValue === "&nbsp;" || elChild.nodeValue === unescape("%u00A0") || elChild.nodeValue === "\u200B")) {
                    bHasNBSP = true;
                }
            }

            elChild = elNextChild;
        }

        if (!bHasNBSP) {
            tmpTextNode = this.tmpTextNode.cloneNode();
            elNode.appendChild(tmpTextNode);
        }

        return elNode; // [SMARTEDITORSUS-418] return 엘리먼트 추가
    },

    // [IE] 엔터 후에 취소선/밑줄 태그를 임의로 추가 (취소선/밑줄에 색상을 표시하기 위함)
    _addTextDecorationTag: function(bAddUnderline, bAddLineThrough) {
        var oTargetNode, oNewNode,
            oSelection = this.oApp.getSelection();

        if (!oSelection.collapsed) {
            return;
        }

        oTargetNode = oSelection.startContainer;

        while (oTargetNode) {
            if (oTargetNode.nodeType === 3) {
                oTargetNode = nhn.DOMFix.parentNode(oTargetNode);
                break;
            }

            if (!oTargetNode.childNodes || oTargetNode.childNodes.length === 0) {
                //              oTargetNode.innerHTML = "\u200B";
                break;
            }

            oTargetNode = oTargetNode.firstChild;
        }

        if (!oTargetNode) {
            return;
        }

        if (oTargetNode.tagName === "U" || oTargetNode.tagName === "S" || oTargetNode.tagName === "STRIKE") {
            return;
        }

        if (bAddUnderline) {
            oNewNode = oSelection._document.createElement("U");
            oTargetNode.appendChild(oNewNode);
            oTargetNode = oNewNode;
        }

        if (bAddLineThrough) {
            oNewNode = oSelection._document.createElement("STRIKE");
            oTargetNode.appendChild(oNewNode);
        }

        oNewNode.innerHTML = "\u200B";
        oSelection.selectNodeContents(oNewNode);
        oSelection.collapseToEnd(); // End 로 해야 새로 생성된 노드 안으로 Selection 이 들어감
        oSelection.select();
    },

    // returns inner-most styling node
    // -> returns span3 from <span1><span2><span3>aaa</span></span></span>
    _getStyleNode: function(elNode) {
        while (elNode.firstChild && this.oSelection._isBlankTextNode(elNode.firstChild)) {
            elNode.removeChild(elNode.firstChild);
        }

        var elFirstChild = elNode.firstChild;

        if (!elFirstChild) {
            return elNode;
        }

        if (elFirstChild.nodeType === 3 ||
            (elFirstChild.nodeType === 1 &&
                (elFirstChild.tagName == "IMG" || elFirstChild.tagName == "BR" || elFirstChild.tagName == "HR" || elFirstChild.tagName == "IFRAME"))) {
            return elNode;
        }

        return this._getStyleNode(elNode.firstChild);
    },

    // returns inner-most styling only node if there's any.
    // -> returns span3 from <span1><span2><span3></span></span></span>
    _getStyleOnlyNode: function(elNode) {
        if (!elNode) {
            return null;
        }

        // the final styling node must allow appending children
        // -> this doesn't seem to work for FF
        if (!elNode.insertBefore) {
            return null;
        }

        if (elNode.tagName == "IMG" || elNode.tagName == "BR" || elNode.tagName == "HR" || elNode.tagName == "IFRAME") {
            return null;
        }

        while (elNode.firstChild && this.oSelection._isBlankTextNode(elNode.firstChild)) {
            elNode.removeChild(elNode.firstChild);
        }

        if (elNode.childNodes.length > 1) {
            return null;
        }

        if (!elNode.firstChild) {
            return elNode;
        }

        // [SMARTEDITORSUS-227] TEXT_NODE 가 return 되는 문제를 수정함. IE 에서 TEXT_NODE 의 innrHTML 에 접근하면 오류 발생
        if (elNode.firstChild.nodeType === 3) {
            return nhn.husky.SE2M_Utils.isBlankTextNode(elNode.firstChild) ? elNode : null;
            //return (elNode.firstChild.textContents === null || elNode.firstChild.textContents === "") ? elNode : null;
        }

        return this._getStyleOnlyNode(elNode.firstChild);
    },

    _insertBR: function(oEvent) {
        oEvent.stop();

        var oSelection = this.oApp.getSelection();

        var elBR = this.oApp.getWYSIWYGDocument().createElement("BR");
        oSelection.insertNode(elBR);
        oSelection.selectNode(elBR);
        oSelection.collapseToEnd();

        if (!this.htBrowser.ie) {
            var oLineInfo = oSelection.getLineInfo();
            var oEnd = oLineInfo.oEnd;

            // line break by Parent
            // <div> 1234<br></div>인경우, FF에서는 다음 라인으로 커서 이동이 안 일어남.
            // 그래서  <div> 1234<br><br type='_moz'/></div> 이와 같이 생성해주어야 에디터 상에 2줄로 되어 보임.
            if (oEnd.bParentBreak) {
                while (oEnd.oNode && oEnd.oNode.nodeType == 3 && oEnd.oNode.nodeValue == "") {
                    oEnd.oNode = oEnd.oNode.previousSibling;
                }

                var nTmp = 1;
                if (oEnd.oNode == elBR || oEnd.oNode.nextSibling == elBR) {
                    nTmp = 0;
                }

                if (nTmp === 0) {
                    oSelection.pasteHTML("<br type='_moz'/>");
                    oSelection.collapseToEnd();
                }
            }
        }

        // the text cursor won't move to the next line without this
        oSelection.insertNode(this.oApp.getWYSIWYGDocument().createTextNode(""));
        oSelection.select();
    }
});
//}
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to changing the editing mode using a Button element
 * @name hp_SE2M_EditingModeChanger.js
 */
nhn.husky.SE2M_EditingModeChanger = jindo.$Class({
    name: "SE2M_EditingModeChanger",
    htConversionMode: null,

    $init: function(elAppContainer, htConversionMode) {
        this.htConversionMode = htConversionMode;
        this._assignHTMLElements(elAppContainer);
    },

    _assignHTMLElements: function(elAppContainer) {
        elAppContainer = jindo.$(elAppContainer) || document;

        //@ec[
        this.elWYSIWYGButton = jindo.$$.getSingle("BUTTON.se2_to_editor", elAppContainer);
        this.elHTMLSrcButton = jindo.$$.getSingle("BUTTON.se2_to_html", elAppContainer);
        this.elTEXTButton = jindo.$$.getSingle("BUTTON.se2_to_text", elAppContainer);
        this.elModeToolbar = jindo.$$.getSingle("DIV.se2_conversion_mode", elAppContainer);
        //@ec]

        this.welWYSIWYGButtonLi = jindo.$Element(this.elWYSIWYGButton.parentNode);
        this.welHTMLSrcButtonLi = jindo.$Element(this.elHTMLSrcButton.parentNode);
        this.welTEXTButtonLi = jindo.$Element(this.elTEXTButton.parentNode);
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["isUseModeChanger", jindo.$Fn(this.isUseModeChanger, this).bind()]);
    },

    $ON_MSG_APP_READY: function() {
        if (this.oApp.htOptions.bOnlyTextMode) {
            this.elWYSIWYGButton.style.display = 'none';
            this.elHTMLSrcButton.style.display = 'none';
            this.elTEXTButton.style.display = 'block';
            this.oApp.exec("CHANGE_EDITING_MODE", ["TEXT"]);
        } else {
            this.oApp.registerBrowserEvent(this.elWYSIWYGButton, "click", "EVENT_CHANGE_EDITING_MODE_CLICKED", ["WYSIWYG"]);
            this.oApp.registerBrowserEvent(this.elHTMLSrcButton, "click", "EVENT_CHANGE_EDITING_MODE_CLICKED", ["HTMLSrc"]);
            this.oApp.registerBrowserEvent(this.elTEXTButton, "click", "EVENT_CHANGE_EDITING_MODE_CLICKED", ["TEXT", false]);

            this.showModeChanger();

            if (this.isUseModeChanger() === false && this.oApp.isUseVerticalResizer() === false) {
                this.elModeToolbar.style.display = "none";
            }
        }
    },

    // [SMARTEDITORSUS-906][SMARTEDITORSUS-1433] Editing Mode 사용 여부 처리 (true:사용함/ false:사용하지 않음)
    showModeChanger: function() {
        if (this.isUseModeChanger()) {
            this.elWYSIWYGButton.style.display = 'block';
            this.elHTMLSrcButton.style.display = 'block';
            this.elTEXTButton.style.display = 'block';
        } else {
            this.elWYSIWYGButton.style.display = 'none';
            this.elHTMLSrcButton.style.display = 'none';
            this.elTEXTButton.style.display = 'none';
        }
    },

    isUseModeChanger: function() {
        return (typeof(this.htConversionMode) === 'undefined' || typeof(this.htConversionMode.bUseModeChanger) === 'undefined' || this.htConversionMode.bUseModeChanger === true) ? true : false;
    },

    $ON_EVENT_CHANGE_EDITING_MODE_CLICKED: function(sMode, bNoAlertMsg) {
        if (sMode == 'TEXT') {
            //에디터 영역 내에 모든 내용 가져옴.
            var sContent = this.oApp.getIR();

            // 내용이 있으면 경고창 띄우기
            if (sContent.length > 0 && !bNoAlertMsg) {
                if (!confirm(this.oApp.$MSG("SE2M_EditingModeChanger.confirmTextMode"))) {
                    return false;
                }
            }
            this.oApp.exec("CHANGE_EDITING_MODE", [sMode]);
        } else {
            this.oApp.exec("CHANGE_EDITING_MODE", [sMode]);
        }

        if ('HTMLSrc' == sMode) {
            this.oApp.exec('MSG_NOTIFY_CLICKCR', ['htmlmode']);
        } else if ('TEXT' == sMode) {
            this.oApp.exec('MSG_NOTIFY_CLICKCR', ['textmode']);
        } else {
            this.oApp.exec('MSG_NOTIFY_CLICKCR', ['editormode']);
        }
    },

    $ON_DISABLE_ALL_UI: function(htOptions) {
        htOptions = htOptions || {};
        var waExceptions = jindo.$A(htOptions.aExceptions || []);

        if (waExceptions.has("mode_switcher")) {
            return;
        }
        if (this.oApp.getEditingMode() == "WYSIWYG") {
            this.welWYSIWYGButtonLi.removeClass("active");
            this.elHTMLSrcButton.disabled = true;
            this.elTEXTButton.disabled = true;
        } else if (this.oApp.getEditingMode() == 'TEXT') {
            this.welTEXTButtonLi.removeClass("active");
            this.elWYSIWYGButton.disabled = true;
            this.elHTMLSrcButton.disabled = true;
        } else {
            this.welHTMLSrcButtonLi.removeClass("active");
            this.elWYSIWYGButton.disabled = true;
            this.elTEXTButton.disabled = true;
        }
    },

    $ON_ENABLE_ALL_UI: function() {
        if (this.oApp.getEditingMode() == "WYSIWYG") {
            this.welWYSIWYGButtonLi.addClass("active");
            this.elHTMLSrcButton.disabled = false;
            this.elTEXTButton.disabled = false;
        } else if (this.oApp.getEditingMode() == 'TEXT') {
            this.welTEXTButtonLi.addClass("active");
            this.elWYSIWYGButton.disabled = false;
            this.elHTMLSrcButton.disabled = false;
        } else {
            this.welHTMLSrcButtonLi.addClass("active");
            this.elWYSIWYGButton.disabled = false;
            this.elTEXTButton.disabled = false;
        }
    },

    $ON_CHANGE_EDITING_MODE: function(sMode) {
        if (sMode == "HTMLSrc") {
            this.welWYSIWYGButtonLi.removeClass("active");
            this.welHTMLSrcButtonLi.addClass("active");
            this.welTEXTButtonLi.removeClass("active");

            this.elWYSIWYGButton.disabled = false;
            this.elHTMLSrcButton.disabled = true;
            this.elTEXTButton.disabled = false;
            this.oApp.exec("HIDE_ALL_DIALOG_LAYER");

            this.oApp.exec("DISABLE_ALL_UI", [{
                aExceptions: ["mode_switcher"]
            }]);
        } else if (sMode == 'TEXT') {
            this.welWYSIWYGButtonLi.removeClass("active");
            this.welHTMLSrcButtonLi.removeClass("active");
            this.welTEXTButtonLi.addClass("active");

            this.elWYSIWYGButton.disabled = false;
            this.elHTMLSrcButton.disabled = false;
            this.elTEXTButton.disabled = true;
            this.oApp.exec("HIDE_ALL_DIALOG_LAYER");
            this.oApp.exec("DISABLE_ALL_UI", [{
                aExceptions: ["mode_switcher"]
            }]);
        } else {
            this.welWYSIWYGButtonLi.addClass("active");
            this.welHTMLSrcButtonLi.removeClass("active");
            this.welTEXTButtonLi.removeClass("active");

            this.elWYSIWYGButton.disabled = true;
            this.elHTMLSrcButton.disabled = false;
            this.elTEXTButton.disabled = false;

            this.oApp.exec("RESET_STYLE_STATUS");
            this.oApp.exec("ENABLE_ALL_UI", []);
        }
    }
});
//}
/**
 * @pluginDesc WYSIWYG 영역에 붙여넣어지는 외부 컨텐츠를 정제하는 플러그인
 */
nhn.husky.SE_PasteHandler = jindo.$Class({
    name: "SE_PasteHandler",

    $init: function(sParagraphContainer) {
        // 문단 단위
        this.sParagraphContainer = sParagraphContainer || "P";

        /**
         * 본문에 붙여넣어진 컨텐츠 중,
         * 이 플러그인에서 정제한 컨텐츠로 치환할 대상 태그 이름의 목록
         * */
        this.aConversionTarget = ["TABLE"];

        this.htBrowser = jindo.$Agent().navigator();
    },

    /**
     * [SMARTEDITORSUS-1862] [IE] IR_TO_DB converter 작동 시점에 한해 컨텐츠 정제를 수행하도록 한다.
     *
     * Editor 영역에 붙여넣을 때 이미 스타일이 유지되고 있는 데다가,
     *
     * IE 8이 최신 버전인 Win 7의 경우,
     * MS Excel의 표를 붙여넣는 중
     * APPCRASH가 발생하는 현상이 있기 때문에
     * paste 시점의 이벤트 핸들러를 사용하는 것이 어렵다.
     * */
    $ON_REGISTER_CONVERTERS: function() {
        if (this.htBrowser.ie) {
            this.oApp.exec('ADD_CONVERTER', ['IR_TO_DB', jindo.$Fn(this.irToDb, this).bind()]);
        }
    },
    irToDb: function(sHtml) {
        if ('undefined' == typeof(sHtml) || !sHtml) {
            return sHtml;
        }
        // [SMARTEDITORSUS-1870]
        var sFilteredHtml = this._filterPastedContents(sHtml, true);
        return sFilteredHtml;
        // previous below
        //return this._filterPastedContents(sHtml);
        // --[SMARTEDITORSUS-1870]
    },
    // --[SMARTEDITORSUS-1862]

    $ON_MSG_APP_READY: function() {
        // 붙여넣기 작업 공간 생성을 위한 할당
        this.elEditingAreaContainer = jindo.$$.getSingle("DIV.husky_seditor_editing_area_container", null, {
            oneTimeOffCache: true
        });
        this.elBody = this.oApp.getWYSIWYGDocument().body;

        // 필터링이 정상적으로 진행되는지 외부에서 확인하기 위한 용도
        this.oApp.exec("ADD_APP_PROPERTY", ["filterPastedContents", jindo.$Fn(this._filterPastedContents, this).bind()]);

        // [SMARTEDITORSUS-1862] [IE] paste handler 대신 IR_TO_DB converter 사용
        if (!(this.htBrowser.safari || this.htBrowser.ie) || (this.htBrowser.safari && this.htBrowser.version >= 6)) {
            this.wfnPasteHandler = jindo.$Fn(this._handlePaste, this);
            this.wfnPasteHandler.attach(this.elBody, "paste");
        }
        // --[SMARTEDITORSUS-1862]
    },

    _handlePaste: function(we) {
        if (this.htBrowser.chrome || (this.htBrowser.safari && this.htBrowser.version >= 6)) {
            /**
             * [Chrome, Safari6+] clipboard에서 넘어온 style 정의를 저장해 둔 뒤,
             * 특정 열린 태그에 style을 적용해야 할 경우 활용한다.
             *
             * MS Excel 2010 기준으로
             * <td>에 담긴 class 명을 획득한 뒤,
             * 저장해 둔 style에서 매칭하는 값이 있으면
             * style을 해당 태그에 적용한다.
             *
             * [IE] Text 형태로만 값을 가져올 수 있기 때문에 style 정의 저장 불가
             * */
            var sClipboardData = we._event.clipboardData.getData("text/html");
            var elTmp = document.createElement("DIV");
            elTmp.innerHTML = sClipboardData;
            var elStyle = jindo.$$.getSingle("style", elTmp, {
                oneTimeOffCache: true
            });
            if (elStyle) {
                var sStyleFromClipboard = elStyle.innerHTML;

                // style="" 내부에 삽입되는 경우, 조화를 이루어야 하기 때문에 쌍따옴표를 따옴표로 치환
                sStyleFromClipboard = sStyleFromClipboard.replace(/"/g, "'");

                this._sStyleFromClipboard = sStyleFromClipboard;
            }
        }

        this._preparePaste();

        // 브라우저의 고유 붙여넣기 동작으로 컨텐츠가 본문 영역에 붙여넣어진다.
        setTimeout(jindo.$Fn(function() {
            // [SMARTEDITORSUS-1676]
            /**
             * 컨텐츠가 붙여넣어지는 과정에서
             * 컨텐츠의 앞 부분 텍스트 일부는
             * 앞쪽 zero-width space 텍스트 노드에 병합되는 경우가 있다.
             *
             * 따라서 이 텍스트 노드 전체를 들어내는 것은 어렵고,
             * 시작 부분에 남아 있는 zero-width space 문자만 제거할 수밖에 없다.
             * */
            var rxZwspStart = new RegExp("^[\u200b]");
            if (this.zwspStart) {
                /**
                 * [SMARTEDITORSUS-1676] [IE 8~10] 선택 영역이 없는 상태에서 붙여넣기를 수행한 경우,
                 * [Object Text]의 nodeValue 프로퍼티는 unknown 타입
                 * */
                if (typeof(this.zwspStart.nodeValue) == "unknown") {
                    if (typeof(this.zwspStart.parentNode) != "unknown" && this.zwspStart.parentNode) { // null
                        this.zwspStart.parentNode.removeChild(this.zwspStart);
                    }
                } else { // [SMARTEDITORSUS-1676] 이전 로직
                    if (this.zwspStart.nodeValue) {
                        this.zwspStart.nodeValue = this.zwspStart.nodeValue.replace(rxZwspStart, "");
                    }

                    /**
                     * 제거 후 빈 값이라면, 인위적으로 삽입해 준 뒤쪽 zwsp 텍스트 노드를 유지할 필요가 없다.
                     *
                     * [Chrome, Safari 6+] 두 번째 조건식이 필요하다.
                     * 붙여넣어지는 컨텐츠가 line-height 속성을 가진 span 태그인 경우,
                     * this.zwspEnd.parentNode가 사라지는 문제가 있다.
                     * 이와는 직접적으로 관련되어 있지 않으나,
                     * Husky 끝 북마크에 이 line-height 속성이 붙는 문제도 있다.
                     * */
                    if (this.zwspStart.nodeValue == "" && this.zwspStart.parentNode) {
                        this.zwspStart.parentNode.removeChild(this.zwspStart);
                    }
                }
            }

            /**
             * 뒤쪽 zero-width space가 포함된 텍스트 노드 마지막의
             * zero-width space 문자를 제거한다.
             * */
            var rxZwspEnd = new RegExp("[\u200b]$");
            if (this.zwspEnd) {
                /**
                 * [SMARTEDITORSUS-1676] [IE 8~10] 선택 영역이 없는 상태에서 붙여넣기를 수행한 경우,
                 * [Object Text]의 nodeValue 프로퍼티는 unknown 타입
                 * */
                if (typeof(this.zwspEnd.nodeValue) == "unknown") {
                    if (typeof(this.zwspEnd.parentNode) != "unknown" && this.zwspEnd.parentNode) { // null
                        this.zwspEnd.parentNode.removeChild(this.zwspEnd);
                    }
                } else { // [SMARTEDITORSUS-1676] 이전 로직
                    if (this.zwspEnd.nodeValue) {
                        this.zwspEnd.nodeValue = this.zwspEnd.nodeValue.replace(rxZwspEnd, "");
                    }

                    /**
                     * 제거 후 빈 값이라면, 인위적으로 삽입해 준 뒤쪽 zwsp 텍스트 노드를 유지할 필요가 없다.
                     *
                     * [Chrome, Safari 6+] 두 번째 조건식이 필요하다.
                     * 붙여넣어지는 컨텐츠가 line-height 속성을 가진 span 태그인 경우,
                     * this.zwspEnd.parentNode가 사라지는 문제가 있다.
                     * 이와는 직접적으로 관련되어 있지 않으나,
                     * Husky 끝 북마크에 이 line-height 속성이 붙는 문제도 있다.
                     * */
                    if (this.zwspEnd.nodeValue == "" && this.zwspEnd.parentNode) {
                        this.zwspEnd.parentNode.removeChild(this.zwspEnd);
                    }
                }
            }
            // --[SMARTEDITORSUS-1676]

            // [SMARTEDITORSUS-1661]
            var oSelection = this.oApp.getSelection();

            // [SMARTEDITORSUS-1676]
            // 붙여넣어진 컨텐츠를 복사해 두고, SmartEditor에 맞는 컨텐츠로 가공한다.
            this.oSelectionClone = null;
            // --[SMARTEDITORSUS-1676]

            if (oSelection.getStringBookmark(this._sBM)) {
                // [SMARTEDITORSUS-1676]
                var bCursorRestored = false;
                // --[SMARTEDITORSUS-1676]

                try {
                    this._processPaste();
                } catch (e) {
                    // [SMARTEDITORSUS-1676]
                    oSelection.moveToStringBookmark(this._sBM);
                    oSelection.collapseToEnd();
                    oSelection.select();
                    bCursorRestored = true;
                    // --[SMARTEDITORSUS-1676]

                    // [SMARTEDITORSUS-1673] [SMARTEDITORSUS-1661]의 복원 기능을 제거
                    // JEagleEye 객체가 존재하면 오류 전송(BLOG)
                    if (typeof(JEagleEyeClient) != "undefined") {
                        var el = "http://blog.naver.com/hp_SE_PasteHandler.js/_handlePaste";

                        var line = e.lineNumber;
                        if (!line) {
                            line = 0;
                        }

                        JEagleEyeClient.sendError(e, el, line);
                    }
                    // --[SMARTEDITORSUS-1661][SMARTEDITORSUS-1673]
                }
                // [SMARTEDITORSUS-1687] 북마크 제거
                if (!bCursorRestored) { // catch 절에서 커서 원복이 이루어지지 않았을 때
                    oSelection.moveToStringBookmark(this._sBM);
                    oSelection.collapseToEnd();
                    oSelection.select();
                }
                // --[SMARTEDITORSUS-1687]

                if (oSelection.getStringBookmark(this._sBM)) {
                    oSelection.removeStringBookmark(this._sBM);
                }
            }
        }, this).bind(), 0);
    },

    /**
     * 붙여넣어지는 외부 프로그램의 컨텐츠를 조작하기 위한 준비를 한다.
     * */
    _preparePaste: function() {
        this._securePasteArea();
    },

    /**
     * 붙여넣기가 발생하는 지점을 확보하기 위하여,
     * 붙여넣기가 발생한 selection에 북마크를 삽입하고
     * 시작 북마크와 끝 북마크 사이를 잘 막아서
     * 컨텐츠가 북마크 사이로 붙여넣어지도록 한다.
     * */
    _securePasteArea: function() {
        var oSelection = this.oApp.getSelection();

        // [SMARTEDITORSUS-1905] UI selection의 변경으로 인하여, startContainer를 시작 북마크 대신 사용해야 붙여넣어진 컨텐츠 획득 가능
        this._elStartContainer_init = oSelection.startContainer;
        // --[SMARTEDITORSUS-1905]

        // [SMARTEDITORSUS-1676]
        this._sBM = oSelection.placeStringBookmark();
        var elEndBookmark = oSelection.getStringBookmark(this._sBM, true);
        var elStartBookmark = oSelection.getStringBookmark(this._sBM);

        /**
         * 붙여넣을 때 레이아웃 상에서 공간을 차지하고 있어야
         * 컨텐츠가 의도한 위치에 붙여넣어지는데,
         *
         * HuskyRange에서 북마크 용도로 삽입하는 빈 <span>으로는 이를 충족할 수 없다.
         * (붙여넣어진 컨텐츠가 <span> 북마크를 잠식)
         *
         * 시작 북마크의 뒤와, 끝 북마크의 앞에
         * zero-width space 문자인 \u200b를 담고 있는
         * 텍스트 노드를 삽입해 둔다.
         * */
        var emptyTextNode = document.createTextNode("");
        this.zwspStart = emptyTextNode.cloneNode(true);
        this.zwspStart.nodeValue = "\u200b";
        this.zwspEnd = this.zwspStart.cloneNode(true);

        // zwsp 시작 부분을 elStartBookmark 뒤에 삽입하되, 빈 텍스트 노드가 존재하면(IE 9~10) 그 뒤로 삽입
        var elNextToStartBookmark = elStartBookmark.nextSibling;
        if (elNextToStartBookmark) {
            if (this._isEmptyTextNode(elNextToStartBookmark)) {
                elNextToStartBookmark = elNextToStartBookmark.nextSibling;
            }
            elStartBookmark.parentNode.insertBefore(this.zwspStart, elNextToStartBookmark);
        } else {
            elStartBookmark.parentNode.appendChild(this.zwspStart);
        }

        elEndBookmark.parentNode.insertBefore(this.zwspEnd, elEndBookmark);

        /**
         * [Chrome, Firefox] 이 부분을 생략하면 붙여넣어진 후 시작 북마크가 잠식된다.
         * [Safari 6+] 이 부분을 생략하면 붙여넣어진 후 시작 북마크가 잠식되고,
         * 선택된 영역의 컨텐츠가 지워지지 않는다.
         *
         * <시작 북마크 /><\u200b>[목표 커서 위치]<\u200b><끝 북마크 />
         * */
        // [SMARTEDITORSUS-1905] 시작 북마크의 잠식을 방지하는 조치 내에서 UI selection을 한 칸 앞으로 변경
        elStartBookmark.innerHTML = "\u200b";
        oSelection.setStartAfter(elStartBookmark);
        // Previous below
        //oSelection.setStartAfter(this.zwspStart);
        // --[SMARTEDITORSUS-1905]
        oSelection.setEndBefore(this.zwspEnd);
        oSelection.select();
        // --[SMARTEDITORSUS-1676]
    },

    /**
     * 외부 프로그램의 컨텐츠가 원문에 붙여넣어지는 과정을 진행한다.
     * */
    _processPaste: function() {
        this._savePastedContents();

        /**
         * [SMARTEDITORSUS-1870]
         * this._savePastedContents()를 거쳐 정제된 컨텐츠가 this._sTarget에 저장되며,
         * 경우에 따라 빈 값이 할당되면 try/catch 블록에서 예외를 던지게 됨
         * */
        if (this._sTarget) {
            // [SMARTEDITORSUS-1673]
            try {
                if (!!this.elPasteHelper) {
                    this._clearPasteHelper();
                    this._showPasteHelper();
                } else {
                    // 붙여넣기 작업 공간 생성(최초 1회)
                    this._createPasteHelper();
                }
                // 작업 공간에 붙여넣기
                this._loadToPasteHelper();
                // 붙여넣기 작업 공간에 붙여넣은 컨텐츠를 떠서 본문의 해당 영역 교체
                this._loadToBody();

                this._hidePasteHelper();
            } catch (e) {
                this._hidePasteHelper();

                throw e;
            }
            // --[SMARTEDITORSUS-1673]
        }
        // --[SMARTEDITORSUS-1870]
    },

    /**
     * 본문 영역에 외부 프로그램의 컨텐츠가 붙여넣어지면 이를 저장하고,
     * SmartEditor에 맞게 필터링한다.
     * */
    _savePastedContents: function() {
        /**
         * [SMARTEDITORSUS-1673]
         * 삽입된 북마크를 기준으로 하여
         * 붙여넣어진 컨텐츠를 복사해 두고,
         * 이후 이를 활용하여 별도의 공간에서 작업
         * */
        var oSelection = this.oApp.getSelection();
        oSelection.moveToStringBookmark(this._sBM);
        // [SMARTEDITORSUS-1905] startContainer를 시작 북마크 대신 사용
        oSelection.setStart(this._elStartContainer_init, 0);
        // --[SMARTEDITORSUS-1905]
        oSelection.select();
        this.oSelectionClone = oSelection.cloneContents();

        // 컨텐츠 복사가 끝났으므로 선택 해제
        oSelection.collapseToEnd();
        oSelection.select();

        var sTarget = this._outerHTML(this.oSelectionClone);
        // --[SMARTEDITORSUS-1673]

        this._isPastedContentsEmpty = true; // 붙여넣어진 내용이 없는지 확인

        if (sTarget != "") {
            this._isPastedContentsEmpty = false;

            /**
             * [FireFox, Safari6+] clipboard에서 style 정의를 저장할 수는 없지만,
             * 본문에 붙여넣어진 뒤 획득하여 저장 가능
             *
             * iWork Pages의 경우, 이전 시점에서 들어온 스타일 정보가 이미 존재할 수도 있기 때문에
             * 기존 변수에 값을 더해넣는 방식 사용
             *
             * @XXX [Firefox] 27.0 업데이트 이후 style 정보가 넘어오지 않아 값을 저장할 수 없음
             * */
            if (this.htBrowser.firefox || (this.htBrowser.safari && this.htBrowser.version >= 6)) {
                var aStyleFromClipboard = sTarget.match(/<style>([^<>]+)<\/style>/i);
                if (aStyleFromClipboard) {
                    var sStyleFromClipboard = aStyleFromClipboard[1];
                    // style="" 내부에 삽입되는 경우, 조화를 이루어야 하기 때문에 쌍따옴표를 따옴표로 치환
                    sStyleFromClipboard = sStyleFromClipboard.replace(/"/g, "'");

                    if (this._sStyleFromClipboard) {
                        this._sStyleFromClipboard += sStyleFromClipboard;
                    } else {
                        this._sStyleFromClipboard = sStyleFromClipboard;
                    }
                }
            }

            // 붙여넣어진 컨텐츠를 정제
            // [SMARTEDITORSUS-1870]
            this._sTarget = this._filterPastedContents(sTarget, true);
            // Previous below
            //this._sTarget = this._filterPastedContents(sTarget);
            // --[SMARTEDITORSUS-1870]
        }
    },

    /**
     * [SMARTEDITORSUS-1673] X-Browsing 비호환 프로퍼티인 outerHTML fix
     * */
    _outerHTML: function(el) {
        var sOuterHTML = "";
        if (el.outerHTML) {
            sOuterHTML = el.outerHTML;
        } else {
            var elTmp = document.createElement("DIV");
            elTmp.appendChild(el.cloneNode(true));
            sOuterHTML = elTmp.innerHTML;
        }

        return sOuterHTML;
    },

    /**
     * SmartEditor에 맞는 필터링을 거친 컨텐츠를 반환한다.
     * @param {String} 필터링 대상이 될 HTML
     * @param {Boolean} [SMARTEDITORSUS-1870] 전체 컨텐츠 중 table만 뽑아내서 필터링할지 결정
     * */
    _filterPastedContents: function(sOriginalContent, bUseTableFilter) {
        // 문단 교체와 관련된 변수
        this._isPastedMultiParagraph = false; // 붙여넣어지는 컨텐츠가 여러 문단으로 구성되어 있는지 확인
        this._aGoesPreviousParagraph = []; // 문단의 분리가 있는 경우, 원래의 북마크가 있는 문단으로 넘어갈 inline 요소들의 집합
        var bParagraphChangeStart = false,
            bParagraphChangeEnd = false,
            nParagraphHierarchy = 0, // 몇 중으로 열려 있는지 확인
            nParagraphChangeCount = 0, // 문단 교체 횟수
            bParagraphIsOpen = false; // 현재 문단이 열려 있는지 확인

        var sMatch, // 판별식과 일치하는 부분
            sResult, // 판별식과 일치하는 부분이 필터링을 거쳐 최종적으로 반환되는 형태
            aResult = [], // 최종적으로 반환되는 정제된 컨텐츠들이 담긴 배열
            nPreviousIndex = -1, // 직전 작업 부분이 결과 배열에서 차지하는 index
            sTagName, // 판별식과 일치하는 부분의 태그명
            sPreviousContent = "", // 직전 삽입된 컨텐츠
            aMultiParagraphIndicator = ["BLOCKQUOTE", "DD", "DIV", "DL", "FORM", "H1", "H2", "H3", "H4", "H5", "H6",
                "HR", "OL", "P", "TABLE", "UL", "IFRAME"
            ], // white list로 여러 문단으로 처리해야 하는 경우를 구별 (https://developer.mozilla.org/ko/docs/HTML/Block-level_elements)
            rxMultiParagraphIndicator = new RegExp("^(" + aMultiParagraphIndicator.join("|") + ")$", "i"),
            // 현재 작업이 테이블 내부에서 이루어지고 있는지 확인. tr, td에 style이 명시되어 있지 않은 경우 사용
            isInTableContext = false,
            nTdIndex = 0, // tr, td의 style 캐싱 중에 현재 몇 번째 td인지 확인을 위함
            nTdLength = 0, // 캐싱 시점에 총 td의 수를 구함
            aColumnWidth = [], // col의 width를 저장하는 배열
            nRowHeight = 0; // tr의 height 저장용
        // [SMARTEDITORSUS-1671] 다중 테이블의 col 캐싱
        var nTableDepth = 0;
        var aaColumnWidth = []; // 이차원 배열
        // --[SMARTEDITORSUS-1671]

        // 패턴
        var rxOpeningTag = /^<[^!?\/\s>]+(([\s]{0})|([\s]+[^>]+))>/, // 열린 태그
            rxTagName = /^<[\/]?([^\s]+)(([\s]{0})|([\s]+[^>]+))>/, // 태그명
            rxClosingTag = /^<\/[A-Za-z]+>/, // 닫힌 태그
            rxOpeningAndClosingTag = /^<[^>]+\/>/, // 자체로 열고 닫는 태그
            rxCommentTag = /^<!--[^<>]+-->/, // 주석이나 커스텀 태그
            rxOpeningCommentTag = /^<!--[^->]+>/, // 열린 주석 태그
            rxClosingCommentTag = /^<![^->]+-->/, // 닫힌 주석 태그
            rxWhiteSpace = /^[\s]+/, // 공백
            rxNonTag = /^[^<\n]+/, // 태그 아닌 요소
            rxExceptedOpeningTag = /^<[^<>]+>/, // 어느 조건도 통과하지 않은, 열린 예외 태그들

            // MS 프로그램의 테이블에서 특히 사용하는 패턴
            rxMsoStyle = /(mso-[^:]+[\s]*:[\s]*)([^;"]*)([;]?)/gi, // Mso-로 시작하는 스타일이 있는지 검사
            // [SMARTEDITORSUS-1673]
            rxStyle = /(style[\s]*=[\s]*)(["'])([^"']*)(["'])/i, // style 속성 획득
            rxClass = /class[\s]*=[\s]*(?:(?:["']([^"']*)["'])|([^\s>]+))/i,
            // --[SMARTEDITORSUS-1673]
            rxTableClassAdd = /(^<table)/gi,

            rxApplied; // 결과 문자열 작업시 적용하는 패턴

        // [SMARTEDITORSUS-1870]
        // 열린 태그에서 &quot;를 " 로 변환
        var rxQuot = /&quot;/gi;

        // clipboard로부터 스타일 정의 획득에 사용
        var sClassAttr = "";
        var aClass = [];
        var sClass, sRx, rxClassForStyle;
        var sMatchedStyle = "";

        // width, height attribute 변환
        var sMatchTmp = ""; // width, height attribute가 있을 때만 사용

        // __se_tbl_ext 클래스 부여
        var rxClass_rest = /(class[\s]*=[\s]*["'])([^"']*)(["'])/i;
        var rxSingleClass_underIE8 = /(class[\s]*=[\s]*)([^"'\s>]+)/i;

        // <colgroup> 작업
        var _nSpan = 0;
        var nColumnWidth;
        //var nColumnWidth = aColumnWidth[nTdIndex];

        // border 치환
        var rxBorderWidth = /(border)([-]?[^:]*)(:[\s]*)([^;'"]+)/gi;
        // 0pt 와 0.6pt 사이의 값이면 1px로 변환
        var rxBorderWidth_pointFive = /([^:\d])([0]?.[0-5][0-9]*pt)/gi;
        var rxBorderWidth_pointFive_veryFirst = /(:)([\s]*([0]?.[0-5][0-9]*pt))/gi;

        var _widthAttribute = "",
            _heightAttribute = "",
            _widthStyle = "",
            _heightStyle = "",
            _nWidth = "",
            _nHeight = "",
            _bWidthStyleReplaceNeed = false,
            _bHeightStyleReplaceNeed = false, // width, style 이 attribute로 존재한다면, 이를 style로 변환해 줘야 할 필요가 있음
            // [SMARTEDITORSUS-1671]
            rxWidthAttribute = /([^\w-])(width[\s]*=[\s]*)(["']?)([A-Za-z0-9.]+[%]?)([;]?["']?)/i,
            rxHeightAttribute = /([^\w-])(height[\s]*=[\s]*)(["']?)([A-Za-z0-9.]+[%]?)([;]?["']?)/i,
            rxWidthStyle = /(["';\s])(width[\s]*:[\s]*)([A-Za-z0-9.]+[%]?)([;]?)/i,
            rxHeightStyle = /(["';\s])(height[\s]*:[\s]*)([A-Za-z0-9.]+[%]?)([;]?)/i;
        // --[SMARTEDITORSUS-1671]

        var rxOpeningTag_endPart = /([\s]*)(>)/g;
        // [SMARTEDITORSUS-1871]
        var rxSpan = /span[\s]*=[\s]*"([\d]+)"/i;
        // Previous below
        //var rxSpan = /span[\s]*=[\s]*"([\d]+)"/;
        // --[SMARTEDITORSUS-1871]
        // --[SMARTEDITORSUS-1870]

        // [SMARTEDITORSUS-1871]
        var rxColspan = /colspan[\s]*=[\s]*"([\d]+)"/i;
        var rxRowspan = /rowspan[\s]*=[\s]*"([\d]+)"/i;
        // --[SMARTEDITORSUS-1871]

        /**
         * [SMARTEDITORSUS-1870] 컨텐츠를 받아 필터링 수행
         * */
        this._doFilter = jindo.$Fn(function(sOriginalContent) {
            /**
             * 변수 초기화
             * */
            this._isPastedMultiParagraph = false;
            this._aGoesPreviousParagraph = [];
            var bParagraphChangeStart = false,
                bParagraphChangeEnd = false,
                nParagraphHierarchy = 0,
                nParagraphChangeCount = 0,
                bParagraphIsOpen = false;

            sMatch,
            sResult,
            aResult = [],
                nPreviousIndex = -1,
                sTagName,
                sPreviousContent = "",

                isInTableContext = false,
                nTdIndex = 0,
                nTdLength = 0,
                aColumnWidth = [],
                nRowHeight = 0;
            nTableDepth = 0;
            aaColumnWidth = [];

            rxApplied;

            sClassAttr = "";
            aClass = [];
            sClass, sRx, rxClassForStyle;
            sMatchedStyle = "";

            sMatchTmp = "";

            _nSpan = 0;
            nColumnWidth;

            _widthAttribute = "", _heightAttribute = "", _widthStyle = "", _heightStyle = "", _nWidth = "", _nHeight = "",
                _bWidthStyleReplaceNeed = false, _bHeightStyleReplaceNeed = false; // width, style 이 attribute로 존재한다면, 이를 style로 변환해 줘야 할 필요가 있음
            // --변수 초기화

            /**
             * 원본 String의 앞에서부터 읽어 나가며
             * 패턴과 일치하는 부분을 하나씩 처리하고,
             * 작업이 끝난 대상은
             * 결과 배열로 보냄과 동시에
             * 원래의 String에서 제거한다.
             * 더 이상 처리할 String이 없을 때 종료.
             * */
            while (sOriginalContent != "") {
                sResult = "",
                    sMatch = "";

                /**
                 * 원본 String의 가장 앞 부분은 아래의 패턴 분기 중 하나와 일치하며,
                 * sMatch, sResult, rxApplied의 3가지 변수로 작업한다.
                 *
                 * sMatch : 패턴과 일치하는 부분을 우선 획득. 작업 대상이다.
                 * sResult : sMatch에서 정제가 이루어진 결과물. 이들의 집합이자, 반환값과 연결된 aResult에 저장된다.
                 * rxApplied : 원본 String에서 작업이 끝난 부분을 지울 때 재활용
                 * */
                if (rxOpeningAndClosingTag.test(sOriginalContent)) { // <tagName />
                    sMatch = sOriginalContent.match(rxOpeningAndClosingTag)[0];

                    sResult = sMatch;

                    rxApplied = rxOpeningAndClosingTag;
                } else if (rxOpeningTag.test(sOriginalContent)) { // <tagName>
                    sMatch = sOriginalContent.match(rxOpeningTag)[0];

                    sTagName = sMatch.match(rxTagName)[1].toUpperCase();

                    // class attribute의 값 획득
                    sClassAttr = "";
                    if (rxClass.test(sMatch)) {
                        // [SMARTEDITORSUS-1673]
                        sClassAttr = sMatch.match(rxClass)[1] || sMatch.match(rxClass)[2];
                        // --[SMARTEDITORSUS-1673]
                    }

                    // 실질적으로 스타일이나 클래스 조작이 이루어지는 쪽은 열린 태그 부분
                    // &quot; 를 ' 로 치환
                    sMatch = sMatch.replace(rxQuot, "'");

                    /**
                     * 모든 열린 태그 공통처리사항.
                     *
                     * width, height가 attribute에 할당되어 있거나, 그 단위가 px가 아닌 pt인 경우에
                     * px 단위로 style 안으로 바꿔넣는 보정이 이루어진다.
                     * __se_tbl 클래스를 가진 SmartEditor의 표는
                     * width, height의 리사이징이 발생할 때
                     * 실시간 변화가 적용되는 style에 그 결과값을 px로 저장하기 때문이다.
                     * @see hp_SE2M_TableEditor.js
                     * */
                    /**
                     * [Chrome, FireFox 26-, Safari6+] 저장해 둔 style 정의로부터,
                     * class 명으로 적용해야 할 style이 있는 경우 추가해 준다.
                     * */
                    if (this.htBrowser.chrome || this.htBrowser.firefox || (this.htBrowser.safari && this.htBrowser.version >= 6)) {
                        aClass = [];
                        if (sClassAttr && (sClassAttr.indexOf('mso') === -1)) {
                            // MsoTableGrid 클래스가 포함된 경우(MS Word)는 제외 : style 정의를 불러와서 적용하면 오히려 레이아웃 비정상

                            aClass = sClassAttr.split(" ");
                        }

                        if (aClass && aClass.length > 0) {
                            for (var i = 0, len = aClass.length; i < len; i++) {
                                sClass = "", sRx = "", rxClassForStyle = null, sMatchedStyle = "";

                                sClass = aClass[i];
                                sRx = sClass + "[\\n\\r\\t\\s]*{([^}]*)}";
                                rxClassForStyle = new RegExp(sRx);

                                if (rxClassForStyle.test(this._sStyleFromClipboard)) {
                                    sMatchedStyle = this._sStyleFromClipboard.match(rxClassForStyle)[1];
                                }

                                if (sMatchedStyle) {
                                    // 위에서 매치되는 style을 태그 안에 추가해 준다.
                                    if (!!rxStyle.test(sMatch)) {
                                        sMatch = sMatch.replace(rxStyle, "$1$2" + sMatchedStyle + " $3$4");
                                    } else { // style마저 없다면 새로 만들어 준다.
                                        sMatch = sMatch.replace(rxOpeningTag_endPart, ' style="' + sMatchedStyle + '"$2');
                                    }
                                }
                            }
                        }
                    }

                    /**
                     * 각 태그에 맞는 처리가 추가수행되는 부분.
                     *
                     * 태그명을 확인한 뒤 분기처리
                     * */
                    sTagName = sMatch.match(rxTagName)[1].toUpperCase();

                    if (sTagName === 'TABLE') {
                        /**
                         * [SMARTEDITORSUS-1673] 외부에서 붙여넣은 테이블에 대하여 __se_tbl_ext 클래스 부여
                         * */
                        if (nTableDepth === 0) {
                            if (sClassAttr) {
                                if (sClassAttr.indexOf('__se_tbl') === -1) {
                                    if (rxClass_rest.test(sMatch)) { // class="className [className2...]"
                                        sMatch = sMatch.replace(rxClass_rest, "$1$2 __se_tbl_ext$3");
                                    } else if (rxSingleClass_underIE8.test(sMatch)) { // [IE8-] class=className
                                        sMatch = sMatch.replace(rxSingleClass_underIE8, '$1"$2 __se_tbl_ext"');
                                    }
                                }
                            } else {
                                sMatch = sMatch.replace(rxTableClassAdd, '$1 class="__se_tbl_ext"');
                            }
                        }
                        // --[SMARTEDITORSUS-1673]

                        // </table> 태그가 등장하기 전까지 작업은 table 맥락에서 이루어진다.
                        isInTableContext = true;

                        // [SMARTEDITORSUS-1671] 테이블을 다중으로 관리
                        nTableDepth++;
                        // --[SMARTEDITORSUS-1671]
                    }

                    /**
                     * 모든 셀이 동일한 너비와 높이가 아닌 경우,
                     * <colgroup> 이하 <col>에 같은 열에 해당하는 셀의 너비가 정의되어 있으며,
                     * 같은 행에 해당하는 셀의 높이는 <tr>에 정의되어 있다.
                     * 이를 저장해 두고 각 <td>의 너비와 높이에 적용하는 데 사용한다.
                     *
                     * [SMARTEDITORSUS-1870]
                     * colgroup에서 저장해야 할 사이즈 정보가
                     * 붙여넣을 때 이미 적용된 IE에서는
                     * colgroup 캐싱 불필요
                     *
                     * @XXX [SMARTEDITORSUS-1613] [NON-IE]
                     * MS Excel 2010 기준으로, 1회 이상 병합된 표가 삽입될 때는
                     * class, width, height를 제외한 정보는 거의 넘어오지 않는다.
                     * */
                    //
                    else if (!this.htBrowser.ie && (sTagName === 'COL')) {
                        // Previous below
                        //else if(/^COL$/i.test(sTagName)){
                        // --[SMARTEDITORSUS-1870]
                        // <col>에 포함된 width style 정보 저장
                        // [SMARTEDITORSUS-1676]
                        if (rxWidthStyle.test(sMatch)) {
                            _widthStyle = sMatch.match(rxWidthStyle)[3];
                        } else { // style이 없는 <col>
                            _widthStyle = "";
                        }
                        // --[SMARTEDITORSUS-1676]

                        // span 갯수를 세서 row 수인  nTdLength 업데이트
                        _nSpan = 0;

                        if (rxSpan.test(sMatch)) {
                            _nSpan = sMatch.match(rxSpan)[1];
                        }

                        // [SMARTEDITORSUS-1671] 다중 테이블의 col 캐싱
                        if (!!aaColumnWidth[nTableDepth] && typeof(aaColumnWidth[nTableDepth].length) === "number") {
                            aColumnWidth = aaColumnWidth[nTableDepth];
                        } else {
                            aColumnWidth = [];
                        }

                        if (_nSpan) {
                            _nSpan = parseInt(_nSpan, 10);
                            for (var i = 0; i < _nSpan; i++) {
                                aColumnWidth.push(_widthStyle);
                                nTdLength++;
                            }
                        } else {
                            nTdLength++;
                            aColumnWidth.push(_widthStyle);
                        }

                        aaColumnWidth[nTableDepth] = aColumnWidth;
                        // --[SMARTEDITORSUS-1671]
                    }
                    /**
                     * [SMARTEDITORSUS-1870]
                     * colgroup에서 저장해야 할 사이즈 정보가
                     * 붙여넣을 때 이미 적용된 IE에서는
                     * colgroup 캐싱 불필요
                     * */
                    else if (!this.htBrowser.ie && (sTagName === 'TR')) {
                        // Previous below
                        //}else if(/^TR$/i.test(sTagName)){
                        // --[SMARTEDITORSUS-1870]
                        // height 값 적용
                        if (!(rxHeightStyle.test(sMatch))) {
                            nRowHeight = null;
                        } else { // 존재하면 td에 적용하기 위해 저장
                            _heightStyle = sMatch.match(rxHeightStyle)[3];

                            nRowHeight = _heightStyle;
                        }
                    } else if ((sTagName === 'TD') || (sTagName === 'TH')) {
                        /**
                         * border 처리
                         *
                         * MS Excel 2010 기준으로,
                         * 0.5pt 두께로 넘어온 border는 100% 배율에서 표현되지 않기에
                         * 일괄 1px로 변환한다.
                         *
                         * 통상 0.84px 이상이면 100% 배율에서 표현된다.
                         * */
                        sMatch = sMatch.replace(rxBorderWidth, function() {
                            return arguments[0].replace(rxBorderWidth_pointFive_veryFirst, "$11px").replace(rxBorderWidth_pointFive, " 1px");
                        });

                        nColumnWidth = undefined, aColumnWidth = undefined, _nSpan = undefined;

                        // [SMARTEDITORSUS-1870] colgroup에서 저장한 값이 붙여넣을 때 이미 적용된 IE에서는 처리 불필요
                        if (!this.htBrowser.ie) {
                            // 스타일 값이 없을 때, colgroup에서 저장한 값이 있으면 이를 적용함
                            // [SMARTEDITORSUS-1671]
                            aColumnWidth = aaColumnWidth[nTableDepth];
                            if (!!aColumnWidth && aColumnWidth.length > 0) { // 저장한 값 있음
                                // [SMARTEDITORSUS-1871]
                                if (rxColspan.test(sMatch)) {
                                    _nSpan = sMatch.match(rxColspan)[1];
                                    _nSpan = parseInt(_nSpan, 10);
                                }

                                nColumnWidth = aColumnWidth[nTdIndex];
                                if (!rxWidthStyle.test(sMatch) && nColumnWidth) {
                                    if (_nSpan) {
                                        if (nColumnWidth.indexOf('pt') != -1) {
                                            nColumnWidth = parseFloat(nColumnWidth, 10) * _nSpan + 'pt';
                                        } else if (nColumnWidth.indexOf('px') != -1) {
                                            nColumnWidth = parseFloat(nColumnWidth, 10) * _nSpan + 'px';
                                        }
                                    }
                                    if (!!rxStyle.test(sMatch)) {
                                        sMatch = sMatch.replace(rxStyle, "$1$2width:" + nColumnWidth + "; $3$4");
                                    } else { // style마저 없다면 새로 만들어 준다.
                                        sMatch = sMatch.replace(rxOpeningTag_endPart, ' style="width:' + nColumnWidth + ';"$2');
                                    }
                                }
                                // Previous below
                                /*nColumnWidth = aColumnWidth[nTdIndex];

                                if(nColumnWidth){
                                    if(!!rxStyle.test(sMatch)){
                                        sMatch = sMatch.replace(rxStyle, "$1$2width:" + aColumnWidth[nTdIndex] + "; $3$4");
                                    }else{ // style마저 없다면 새로 만들어 준다.
                                        sMatch = sMatch.replace(rxOpeningTag_endPart, ' style="width:' + aColumnWidth[nTdIndex] + ';"$2');
                                    }
                                }*/
                                // --[SMARTEDITORSUS-1871]
                            }
                            // --[SMARTEDITORSUS-1671]

                            if (!rxHeightStyle.test(sMatch) && !!nRowHeight) {
                                // 스타일 값이 없을 때, tr에서 저장한 값이 있으면 이를 적용함
                                // [SMARTEDITORSUS-1671]
                                if (!!rxStyle.test(sMatch)) {
                                    sMatch = sMatch.replace(rxStyle, "$1$2height:" + nRowHeight + "; $3$4");
                                } else { // style마저 없다면 새로 만들어 준다.
                                    sMatch = sMatch.replace(rxOpeningTag_endPart, ' style="height:' + nRowHeight + ';"$2');
                                }
                                // --[SMARTEDITORSUS-1671]
                            }
                        }
                        // --[SMARTEDITORSUS-1870]

                        // 적용할 때마다 nTdIndex 증가
                        // [SMARTEDITORSUS-1871]
                        if (_nSpan) {
                            nTdIndex += _nSpan;
                        } else {
                            nTdIndex++;
                        }
                        // Previous below
                        //nTdIndex++;
                        // --[SMARTEDITORSUS-1871]
                    }

                    // 문단 교체가 발생하는지를 기록하는 flag
                    if (rxMultiParagraphIndicator.test(sTagName)) {
                        this._isPastedMultiParagraph = true; // 붙여넣어진 컨텐츠가 여러 문단으로 구성되어 있는지 확인
                        bParagraphChangeStart = true; // 새로운 문단이 열렸음을 표시
                    }

                    sResult += sMatch;

                    rxApplied = rxOpeningTag;
                } else if (rxWhiteSpace.test(sOriginalContent)) { // 공백문자는 일단 그대로 통과시킴. 차후 처리 방안이 있을지 논의 필요
                    sMatch = sOriginalContent.match(rxWhiteSpace)[0];

                    sResult = sMatch;

                    rxApplied = rxWhiteSpace;
                } else if (rxNonTag.test(sOriginalContent)) { // 태그 아님
                    sMatch = sOriginalContent.match(rxNonTag)[0];

                    sResult = sMatch;

                    rxApplied = rxNonTag;
                } else if (rxClosingTag.test(sOriginalContent)) { // </tagName>
                    sMatch = sOriginalContent.match(rxClosingTag)[0];

                    // 태그별 분기처리
                    sTagName = sMatch.match(rxTagName)[1].toUpperCase();

                    /**
                     * 모든 셀이 동일한 너비와 높이가 아닌 경우,
                     * 각 <td>의 너비와 높이에 적용하는 데 사용했던
                     * 저장값들을 초기화한다.
                     * */
                    if (sTagName === 'TABLE') {
                        // [SMARTEDITORSUS-1671] 다중 테이블의 col 캐싱
                        aaColumnWidth[nTableDepth] = null;
                        nTableDepth--;
                        // --[SMARTEDITORSUS-1671]
                        isInTableContext = false;
                        nTdLength = 0;
                        nTdIndex = 0;
                    } else if (sTagName === 'TR') {
                        nTdIndex = 0;
                    }

                    if (rxMultiParagraphIndicator.test(sTagName)) { // p, div, table, iframe
                        bParagraphChangeEnd = true; // 새로운 문단이 막 닫혔음을 표시
                    }

                    // 빈 <td>였다면 &npsp;가 추가되어 있기 때문에 연산자가 다른 경우와는 다름
                    sResult += sMatch;

                    rxApplied = rxClosingTag;
                }
                // 지금까지의 조건에 부합하지 않는 모든 태그는 예외 태그로 처리한다. MS Word의 <o:p> 등이 해당.
                else if (rxExceptedOpeningTag.test(sOriginalContent)) { // <*unknown*> : similar to rxOpeningCommentTag case
                    sMatch = sOriginalContent.match(rxExceptedOpeningTag)[0];

                    sResult = sMatch;

                    rxApplied = rxExceptedOpeningTag;
                } else { // unreachable point
                    throw new Error("Unknown Node : If the content isn't invalid, please let us know.");
                }
                // sResult로 작업

                // 직전 값 비교에 사용하기 위해 정보 갱신
                if (sResult != "") {
                    sPreviousContent = sResult; // 현재 sResult는, 다음 작업 때 직전 값을 참조해야 할 필요가 있는 경우 사용된다.
                    nPreviousIndex++;

                    // 원본 String의 맨 앞부터 첫 문단 교체가 일어나기까지의 모든 inline 요소들을 저장해 두고 활용
                    var sGoesPreviousParagraph = "";
                    if (!this._isPastedMultiParagraph) { // 원본 String의 맨 앞부터 첫 문단 교체가 일어나기까지의 모든 inline 요소들을 저장해 두고 활용
                        sGoesPreviousParagraph = sResult;
                    }

                    if (!bParagraphChangeStart) { // 문맥 교체가 아직 시작되지 않았음
                        // [SMARTEDITORSUS-1870]
                        if (!bParagraphIsOpen && (nParagraphHierarchy == 0)) {
                            // text_content -> <p>text_content
                            // 최상위 depth
                            // [SMARTEDITORSUS-1862]
                            if (!this.htBrowser.ie) {
                                sResult = "<" + this.sParagraphContainer + ">" + sResult;
                            }
                            // --[SMARTEDITORSUS-1862]
                            bParagraphIsOpen = true;
                        }
                        // --[SMARTEDITORSUS-1870]
                    } else { // 문맥 교체가 시작됨
                        // <p>text_content + <table> -> <p>text_content + </p> <table>
                        if (bParagraphIsOpen) { // 문맥이 열림
                            // [SMARTEDITORSUS-1862]
                            if (!this.htBrowser.ie) {
                                sResult = "</" + this.sParagraphContainer + ">" + sResult;
                            }
                            // --[SMARTEDITORSUS-1862]
                            bParagraphIsOpen = false;
                        }

                        nParagraphChangeCount++;
                        nParagraphHierarchy++;
                    }

                    // 문맥 교체가 끝났다면 문단 교체 flag 초기화
                    if (bParagraphChangeEnd) {
                        bParagraphChangeStart = false;
                        bParagraphChangeEnd = false;

                        nParagraphHierarchy--;
                    }

                    if (!this._isPastedMultiParagraph) {
                        this._aGoesPreviousParagraph.push(sGoesPreviousParagraph);
                    }

                    // 최종적으로 반환되는 정체된 컨텐츠들이 담긴 배열
                    aResult.push(sResult);
                }

                // 정제가 끝난 컨텐츠는 원래 컨텐츠에서 제거
                sOriginalContent = sOriginalContent.replace(rxApplied, "");
            };
            // --while

            // 최종 결과 한 번도 문단 교체가 없었다면 앞에 달린 문맥 교체 태그를 제거하고, inline으로 삽입 준비
            // [SMARTEDITORSUS-1862]
            if (!this.htBrowser.ie && nParagraphChangeCount == 0) {
                // --[SMARTEDITORSUS-1862]
                var rxParagraphContainer = new RegExp("^<" + this.sParagraphContainer + ">");

                if (aResult[0]) {
                    aResult[0] = aResult[0].replace(rxParagraphContainer, "");
                }
            }

            return aResult.join("");
        }, this).bind();

        // _doFilter에 전체 내용을 그대로 전달하면 전체 내용을 필터링하게 됨
        var sFilteredContents = bUseTableFilter ? this._filterTableContents(sOriginalContent) : this._doFilter(sOriginalContent);
        return sFilteredContents;
    },

    /**
     * [SMARTEDITORSUS-1870]
     * 전달받은 컨텐츠 중 <table> 부분만 뽑아내서 필터를 거치며,
     * 반환 결과는 브라우저에 따라 다름
     * -[IE] 필터링이 저장 시점에 수행되기 때문에, <table> 부분과 그 나머지 부분을 다시 조립해서 반환
     * -[Chrome, Safari 6+] 필터링이 붙여넣기 시점에 수행되고, <table> 부분만 교체하는 것이 목적이기 때문에 <table> 부분만 반환
     * */
    _filterTableContents: function(sOriginalContents) {
        var _sTarget = sOriginalContents;

        var _rxTable_start = new RegExp('<table(([\\s]{0})|([\\s]+[^>]+))>', 'ig');
        var _rxTable_end = new RegExp('</table>', 'ig');

        var _res, // 정규식 탐색 결과
            _nStartIndex, // <table/> 맥락이 시작되는 <table> 문자열의 위치
            _nEndIndex, // <table/> 맥락이 끝나는 </table> 문자열의 위치
            _aStartIndex = [ /* _nStartIndex */ ],
            _aEndIndex = [ /* _nEndIndex */ ],
            _nLastIndex_tmp_start,
            _nLastIndex_tmp_end,

            _aTable = [], // 대상 컨텐츠에서 뽑아낸 <table/>
            _nTable_start = 0, // 현재 <table/> 맥락에서 <table> 문자열 갯수
            _nTable_end = 0; // 현재 <table/> 맥락에서 </table> 문자열 갯수

        // 대상 컨텐츠에서 <table>을 찾아서 추출
        function _findAndMarkTable() {
            // 현재 위치 기록
            _nLastIndex_tmp_start = _rxTable_start.lastIndex;
            _nLastIndex_tmp_end = _rxTable_end.lastIndex;

            // 다음 문자열 비교를 위한 임시 탐색
            var res_tmp_start = _rxTable_start.exec(_sTarget); // 현재 위치에서 다음 <table> 문자열 탐색
            var res_tmp_end = _rxTable_end.exec(_sTarget); // 현재 위치에서 다음 </table> 문자열 탐색

            var nLastIndex_start = _rxTable_start.lastIndex; // 다음 <table> 문자열 위치 기록
            var nLastIndex_end = _rxTable_end.lastIndex; // 다음 </table> 문자열 위치 기록

            // 기록해 둔 위치로 원복
            _rxTable_start.lastIndex = _nLastIndex_tmp_start;
            _rxTable_end.lastIndex = _nLastIndex_tmp_end;

            if (res_tmp_start === null) {
                if (res_tmp_end !== null) {
                    _doRxTable_end();
                }
            } else if (res_tmp_end === null) {
                if (res_tmp_start !== null) {
                    _doRxTable_start();
                }
            } else {
                if (nLastIndex_start < nLastIndex_end) { // <table> ... </table> 순으로 탐색된 경우
                    _doRxTable_start();
                } else if (nLastIndex_start > nLastIndex_end) { // </table> ... <table> 순으로 탐색된 경우
                    _doRxTable_end();
                }
            }
            // 더 이상 탐색이 불가능하면 종료
        }

        // <table> 문자열 탐색
        function _doRxTable_start() {
            _res = _rxTable_start.exec(_sTarget);
            _rxTable_end.lastIndex = _rxTable_start.lastIndex;

            if (_res !== null) {
                _nTable_start++;
            }
            if (_nTable_start == 1) {
                _nStartIndex = _res.index; // 현재 <table> 문자열의 위치
                _aStartIndex.push(_nStartIndex);
            }

            _findAndMarkTable(); // 재귀호출
        }

        // </table> 문자열 탐색
        function _doRxTable_end() {
            _res = _rxTable_end.exec(_sTarget);
            _rxTable_start.lastIndex = _rxTable_end.lastIndex;

            if (_res !== null) {
                _nTable_end++;
            }

            // <table/>이 완전하게 열리고 닫히는 시점에, 이 <table/>을 대상 컨텐츠에서 뽑아낸다.
            if ((_nTable_start !== 0) && (_nTable_end !== 0) && (_nTable_start == _nTable_end)) {
                _nEndIndex = _res.index; // 현재 </table> 문자열의 위치
                _aEndIndex.push(_nEndIndex + 8); // '</table>'의 length인 8을 더함
                _aTable.push(_sliceTable());
                _initVar();
            }

            _findAndMarkTable(); // 재귀호출
        }

        // 대상 컨텐츠에서 <table/>을 뽑아낸다.
        var _sliceTable = function() {
            return _sTarget.slice(_nStartIndex, _nEndIndex + 8); // '</table>'의 length인 8을 더함
        };

        var _initVar = function() {
            _nStartIndex = undefined;
            _nEndIndex = undefined;
            _nTable_start = 0;
            _nTable_end = 0;
        };

        _findAndMarkTable();

        for (var i = 0, len = _aTable.length; i < len; i++) {
            var sTable = _aTable[i];

            // 개별 <table/>에 대한 필터링
            sTable = this._doFilter(sTable);
            _aTable[i] = sTable;
        }

        if (this.htBrowser.ie) {
            var _aResult = [];
            var _sResult = '';

            var _nStartIndexLength = _aStartIndex.length;
            var _nEndIndexLength = _aEndIndex.length;
            var __nStartIndex, __nEndIndex;

            if ((_nStartIndexLength > 0) && (_nStartIndexLength == _nEndIndexLength)) { // <table/> 쌍이 정상적으로 편성되었다면, 열린 횟수와 닫힌 횟수가 같아야 함
                /**
                 * string의 index 정보를 기반으로 대상 컨텐츠를 재조립한다.
                 *
                 * -대상 컨텐츠
                 * [1. Non-Table][2. <table>original_1</table>][3. Non-Table][4. <table>original_2</table>][5. Non-Table]
                 *
                 * -필터링을 거친 컨텐츠
                 * [<table>filtered_1</table>][<table>filtered_2</table>]
                 *
                 * -대상 컨텐츠를 분해한 뒤,
                 * <table> 부분 대신 필터링을 거친 컨텐츠를 조립한다.
                 * [1. Non-Table][<table>filtered_1</table>][3. Non-Table][<table>filtered_2</table>][5. Non-Table]
                 * */
                __nStartIndex = _aStartIndex[0];
                _aResult.push(_sTarget.slice(0, __nStartIndex));
                for (var i = 0, len = _aStartIndex.length; i < len; i++) {
                    if ((_nStartIndexLength > 1) && (i > 0)) {
                        __nStartIndex = _aEndIndex[i - 1];
                        __nEndIndex = _aStartIndex[i];
                        _aResult.push(_sTarget.slice(__nStartIndex, __nEndIndex));
                    }
                    __nStartIndex = _aStartIndex[i];
                    __nEndIndex = _aEndIndex[i];
                    _aResult.push(_aTable[i]);
                }
                __nEndIndex = _aEndIndex[_nEndIndexLength - 1];
                _aResult.push(_sTarget.slice(__nEndIndex, _sTarget.length + 1));
                return _aResult.join('');
            } else {
                // table이 하나도 없는 컨텐츠일 때는 기존 컨텐츠 그대로 반환
                return _sTarget;
            }
        } else {
            return _aTable.join('');
        }
    },

    /**
     * [SMARTEDITORSUS-1673] 붙여넣기 작업 공간(div.husky_seditor_paste_helper)을 생성
     * */
    _createPasteHelper: function() {
        if (!this.elPasteHelper) {
            this.elPasteHelper = document.createElement("DIV");
            this.elPasteHelper.className = "husky_seditor_paste_helper";
            this.elPasteHelper.style.width = "0px";
            this.elPasteHelper.style.height = "0px";
            this.elPasteHelper.style.overflow = "hidden";
            this.elPasteHelper.contentEditable = "true";
            this.elPasteHelper.style.position = "absolute";
            this.elPasteHelper.style.top = "9999px";
            this.elPasteHelper.style.left = "9999px";

            this.elEditingAreaContainer.appendChild(this.elPasteHelper);
        }
    },

    _showPasteHelper: function() {
        if (!!this.elPasteHelper && this.elPasteHelper.style.display == "none") {
            this.elPasteHelper.style.display = "block";
        }
    },

    _hidePasteHelper: function() {
        if (!!this.elPasteHelper && this.elPasteHelper.style.display != "none") {
            this.elPasteHelper.style.display = "none";
        }
    },

    /**
     * [SMARTEDITORSUS-1673] 붙여넣기 작업 공간의 내용을 비우고,
     * 새로운 컨텐츠 작업을 준비한다.
     * */
    _clearPasteHelper: function() {
        if (!!this.elPasteHelper) {
            this.elPasteHelper.innerHTML = "";
        }
    },

    /**
     * [SMARTEDITORSUS-1673] 붙여넣어진 컨텐츠가 가공되고 나면, 이를 붙여넣기 작업 공간에 붙여넣는다.
     * */
    _loadToPasteHelper: function() {
        // 붙여넣을 컨텐츠가 여러 문단일 경우 저장하는 배열
        var aParagraph = [];

        var elTmp, sGoesPreviousParagraph, _aGoesPreviousParagraph, waParagraph;

        if (this._isPastedMultiParagraph) {
            // 본문에 붙여넣을 때는 Node 형태로 변환
            elTmp = document.createElement("DIV");
            elTmp.innerHTML = this._sTarget;
            aParagraph = elTmp.childNodes;
        }

        try {
            if (this._aGoesPreviousParagraph && this._aGoesPreviousParagraph.length > 0) {
                sGoesPreviousParagraph = this._aGoesPreviousParagraph.join("");
                elTmp = document.createElement("DIV");
                elTmp.innerHTML = sGoesPreviousParagraph;

                _aGoesPreviousParagraph = elTmp.childNodes;

                // _aGoesPreviousParagraph 삽입
                for (var i = 0, len = _aGoesPreviousParagraph.length; i < len; i++) {
                    this.elPasteHelper.appendChild(_aGoesPreviousParagraph[i].cloneNode(true));
                }

                /**
                 * inline 요소들은 aParagraph[0]에 문단 태그로 감싸져 들어 있었다.
                 * 이를 앞으로 본문에 삽입될 요소들인 aParagraph에서 제거해야 함
                 * */
                // aParagraph의 0번 인덱스 제거
                if (aParagraph.length > 0) {
                    if (!!aParagraph.splice) {
                        aParagraph.splice(0, 1);
                    } else { // [IE8-]
                        waParagraph = jindo.$A(aParagraph);
                        waParagraph.splice(0, 1);
                        aParagraph = waParagraph.$value();
                    }
                }
            }

            // aParagraph 삽입
            if (aParagraph.length > 0) {
                for (var i = 0, len = aParagraph.length; i < len; i++) {
                    this.elPasteHelper.appendChild(aParagraph[i].cloneNode(true));
                }
            }
        } catch (e) {
            throw e;
        }

        return;
    },

    /**
     * [SMARTEDITORSUS-1673] 붙여넣기 작업 공간에 붙여넣은 컨텐츠 중,
     * 본문 영역에 붙여넣을 컨텐츠를 선별하여
     * 브라우저 고유의 붙여넣기 기능으로 본문 영역에 붙여넣은 기존 컨텐츠와 교체한다.
     * */
    _loadToBody: function() {
        var oSelection = this.oApp.getSelection();

        // 본문 영역에 붙여넣기
        try {
            /**
             * As-Is 컨텐츠
             *
             * 본문 영역에 붙여넣어진 컨텐츠 중 가공된 컨텐츠로 치환될 대상 목록을 획득
             * */
            oSelection.moveToStringBookmark(this._sBM);
            // [SMARTEDITORSUS-1905] startContainer를 시작 북마크 대신 사용
            oSelection.setStart(this._elStartContainer_init, 0);
            // --[SMARTEDITORSUS-1905]
            oSelection.select();
            var aSelectedNode_original = oSelection.getNodes();
            var aConversionIndex_original = this._markMatchedElementIndex(aSelectedNode_original, this.aConversionTarget);

            /**
             * To-Be 컨텐츠
             *
             * 붙여넣기 작업 공간에 붙여넣어진 컨텐츠를 selection으로 잡아서
             * 선택된 부분의 모든 node를 획득할 필요가 있다.
             *
             * 기존의 this.oApp.getSelection()은
             * iframe#se2_iframe 의 window를 기준으로 한 selection을 사용한다.
             * 따라서 해당 엘리먼트 하위에 속한 요소들만 selection 으로 획득할 수 있다.
             *
             * 붙여넣기 작업 공간으로 사용되는 div.husky_seditor_paste_helper 는
             * iframe#se2_iframe의 형제이기 때문에
             * this.oApp.getSelection()으로는 helper 안의 컨텐츠를 선택하지 못한다.
             *
             * 따라서 iframe#se2_iframe과 div.husky_seditor_paste_helper를 아우르는
             * 부모 window를 기준으로 한 selection을 생성하여
             * div.husky_seditor_paste_helper 내부의 컨텐츠를 선택해야 한다.
             * */
            var oSelection_parent = this.oApp.getSelection(this.oApp.getWYSIWYGWindow().parent);
            oSelection_parent.setStartBefore(this.elPasteHelper.firstChild);
            oSelection_parent.setEndAfter(this.elPasteHelper.lastChild);
            oSelection_parent.select();
            var aSelectedNode_filtered = oSelection_parent.getNodes();
            var aConversionIndex_filtered = this._markMatchedElementIndex(aSelectedNode_filtered, this.aConversionTarget);
            var nDiff_original_filtered = aConversionIndex_original.length - aConversionIndex_filtered.length;

            // As-Is 컨텐츠를 To-Be 컨텐츠로 교체
            if (aConversionIndex_original.length > 0 && aConversionIndex_original.length == aConversionIndex_filtered.length) {
                var nConversionIndex_original, nConversionIndex_filtered, elConversion_as_is, elConversion_to_be;

                for (var i = 0, len = aConversionIndex_filtered.length; i < len; i++) {
                    nConversionIndex_original = aConversionIndex_original[i + nDiff_original_filtered];
                    nConversionIndex_filtered = aConversionIndex_filtered[i];

                    elConversion_as_is = aSelectedNode_original[nConversionIndex_original];
                    elConversion_to_be = aSelectedNode_filtered[nConversionIndex_filtered];
                    if (!/__se_tbl/.test(elConversion_as_is.className)) {
                        elConversion_as_is.parentNode.replaceChild(elConversion_to_be.cloneNode(true), elConversion_as_is);
                    }
                }
            }

            // 붙여넣어진 컨텐츠의 마지막으로 커서를 위치
            oSelection.moveToStringBookmark(this._sBM);
            oSelection.collapseToEnd();
            oSelection.select();
        } catch (e) {
            /**
             * processPaste()에서 조작된 컨텐츠가 본문에 이미 삽입된 경우
             * oSelectionClone을 기반으로 브라우저 고유 기능으로 붙여넣었던 컨텐츠를 복원한다.
             * */
            // 삽입된 컨텐츠 제거
            oSelection.moveToStringBookmark(this._sBM);
            oSelection.select();
            oSelection.deleteContents();

            // oSelectionClone 복원
            var elEndBookmark = oSelection.getStringBookmark(this._sBM, true);
            elEndBookmark.parentNode.insertBefore(this.oSelectionClone.cloneNode(true), elEndBookmark);

            // 커서 원위치
            oSelection.moveToStringBookmark(this._sBM);
            oSelection.collapseToEnd();
            oSelection.select();

            throw e;
        }
    },

    /**
     * [SMARTEDITORSUS-1673] NodeList의 요소 중
     * 주어진 태그명과 일치하는 요소가
     * NodeList에서 위치하는 index를 기록해 둔다.
     *
     * @param {Array} 탐색할 노드가 담긴 배열
     * @param {Array} 탐색 태그명이 담긴 배열
     * [SMARTEDITORSUS-1676]
     * @param {Array} true, false 중 하나를 반환하는 필터 함수들이 담긴 배열 (선택)
     * @paran {String} "OR" 또는 "AND". 필터 함수들을 어떠한 조건으로 처리할지 지정 (선택)
     * --[SMARTEDITORSUS-1676]
     * */
    _markMatchedElementIndex: function(aNodeList, aTagName, aFilter, sFilterLogic) {
        var aMatchedElementIndex = [];
        var sPattern = aTagName.join("|");
        var rxTagName = new RegExp("^(" + sPattern + ")$", "i"); // ex) new RegExp("^(p|table|div)$", "i")
        var elNode, fFilter, isFilteringSuccess;

        if (aFilter) {
            sFilterLogic = sFilterLogic || "OR";

            if (sFilterLogic.toUpperCase() === "AND") {
                isFilteringSuccess = true;
            } else if (sFilterLogic.toUpperCase() === "OR") {
                isFilteringSuccess = false;
            }
        }

        for (var i = 0, len = aNodeList.length; i < len; i++) {
            elNode = aNodeList[i];
            if (rxTagName.test(elNode.nodeName)) {
                if (aFilter) {
                    for (var ii = aFilter.length; ii--;) {
                        fFilter = aFilter[ii];

                        if (sFilterLogic.toUpperCase() === "AND") {
                            if (!fFilter.apply(elNode)) {
                                isFilteringSuccess = false;
                                break;
                            }
                        } else if (sFilterLogic.toUpperCase() === "OR") {
                            if (fFilter.apply(elNode)) {
                                isFilteringSuccess = true;
                                break;
                            }
                        }
                    }
                    if (isFilteringSuccess) {
                        aMatchedElementIndex.push(i);
                    }
                } else {
                    aMatchedElementIndex.push(i);
                }
            }
        }

        return aMatchedElementIndex;
    },

    // 대상 노드가 빈 텍스트 노드인지 확인한다.
    _isEmptyTextNode: function(node) {
        return node.nodeType == 3 && !/\S/.test(node.nodeValue);
    }
});
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the basic editor commands
 * @name hp_SE_ExecCommand.js
 */
nhn.husky.SE2M_ExecCommand = jindo.$Class({
    name: "SE2M_ExecCommand",
    oEditingArea: null,
    oUndoOption: null,
    _rxTable: /^(?:TBODY|TR|TD)$/i,
    _rxCmdInline: /^(?:bold|underline|italic|strikethrough|superscript|subscript)$/i, // inline element 가 생성되는 command

    $init: function(oEditingArea) {
        this.oEditingArea = oEditingArea;
        this.nIndentSpacing = 40;

        this.rxClickCr = new RegExp('^bold|underline|italic|strikethrough|justifyleft|justifycenter|justifyright|justifyfull|insertorderedlist|insertunorderedlist|outdent|indent$', 'i');
    },

    $BEFORE_MSG_APP_READY: function() {
        // the right document will be available only when the src is completely loaded
        if (this.oEditingArea && this.oEditingArea.tagName == "IFRAME") {
            this.oEditingArea = this.oEditingArea.contentWindow.document;
        }
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+b", "EXECCOMMAND", ["bold", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+u", "EXECCOMMAND", ["underline", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+i", "EXECCOMMAND", ["italic", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+d", "EXECCOMMAND", ["strikethrough", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["meta+b", "EXECCOMMAND", ["bold", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["meta+u", "EXECCOMMAND", ["underline", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["meta+i", "EXECCOMMAND", ["italic", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["meta+d", "EXECCOMMAND", ["strikethrough", false, false]]);
        this.oApp.exec("REGISTER_HOTKEY", ["tab", "INDENT"]);
        this.oApp.exec("REGISTER_HOTKEY", ["shift+tab", "OUTDENT"]);
        //this.oApp.exec("REGISTER_HOTKEY", ["tab", "EXECCOMMAND", ["indent", false, false]]);
        //this.oApp.exec("REGISTER_HOTKEY", ["shift+tab", "EXECCOMMAND", ["outdent", false, false]]);

        this.oApp.exec("REGISTER_UI_EVENT", ["bold", "click", "EXECCOMMAND", ["bold", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["underline", "click", "EXECCOMMAND", ["underline", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["italic", "click", "EXECCOMMAND", ["italic", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["lineThrough", "click", "EXECCOMMAND", ["strikethrough", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["superscript", "click", "EXECCOMMAND", ["superscript", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["subscript", "click", "EXECCOMMAND", ["subscript", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["justifyleft", "click", "EXECCOMMAND", ["justifyleft", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["justifycenter", "click", "EXECCOMMAND", ["justifycenter", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["justifyright", "click", "EXECCOMMAND", ["justifyright", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["justifyfull", "click", "EXECCOMMAND", ["justifyfull", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["orderedlist", "click", "EXECCOMMAND", ["insertorderedlist", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["unorderedlist", "click", "EXECCOMMAND", ["insertunorderedlist", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["outdent", "click", "EXECCOMMAND", ["outdent", false, false]]);
        this.oApp.exec("REGISTER_UI_EVENT", ["indent", "click", "EXECCOMMAND", ["indent", false, false]]);

        //      this.oApp.exec("REGISTER_UI_EVENT", ["styleRemover", "click", "EXECCOMMAND", ["RemoveFormat", false, false]]);

        this.oNavigator = jindo.$Agent().navigator();

        if (!this.oNavigator.safari && !this.oNavigator.chrome) {
            this._getDocumentBR = function() {};
            this._fixDocumentBR = function() {};
        }

        if (!this.oNavigator.ie) {
            this._fixCorruptedBlockQuote = function() {};

            if (!this.oNavigator.safari && !this.oNavigator.chrome) {
                this._insertBlankLine = function() {};
            }
        }

        if (!this.oNavigator.firefox) {
            this._extendBlock = function() {};
        }
    },

    $ON_INDENT: function() {
        this.oApp.delayedExec("EXECCOMMAND", ["indent", false, false], 0);
    },

    $ON_OUTDENT: function() {
        this.oApp.delayedExec("EXECCOMMAND", ["outdent", false, false], 0);
    },

    /**
     * TBODY, TR, TD 사이에 있는 텍스트노드인지 판별한다.
     * @param oNode {Node} 검사할 노드
     * @return {Boolean} TBODY, TR, TD 사이에 있는 텍스트노드인지 여부
     */
    _isTextBetweenTable: function(oNode) {
        var oTmpNode;
        if (oNode && oNode.nodeType === 3) { // 텍스트 노드
            if ((oTmpNode = oNode.previousSibling) && this._rxTable.test(oTmpNode.nodeName)) {
                return true;
            }
            if ((oTmpNode = oNode.nextSibling) && this._rxTable.test(oTmpNode.nodeName)) {
                return true;
            }
        }
        return false;
    },

    $BEFORE_EXECCOMMAND: function(sCommand, bUserInterface, vValue, htOptions) {
        var elTmp, oSelection;

        //본문에 전혀 클릭이 한번도 안 일어난 상태에서 크롬과 IE에서 EXECCOMMAND가 정상적으로 안 먹히는 현상.
        this.oApp.exec("FOCUS");
        this._bOnlyCursorChanged = false;
        oSelection = this.oApp.getSelection();
        // [SMARTEDITORSUS-1584] IE에서 테이블관련 태그 사이의 텍스트노드가 포함된 채로 execCommand 가 실행되면
        // 테이블 태그들 사이에 더미 P 태그가 추가된다.
        // 테이블관련 태그 사이에 태그가 있으면 문법에 어긋나기 때문에 getContents 시 이 더미 P 태그들이 밖으로 빠져나가게 된다.
        // 때문에 execCommand 실행되기 전에 셀렉션에 테이블관련 태그 사이의 텍스트노드를 찾아내 지워준다.
        for (var i = 0, aNodes = oSelection.getNodes(), oNode;
            (oNode = aNodes[i]); i++) {
            if (this._isTextBetweenTable(oNode)) {
                // TODO: 노드를 삭제하지 않고 Selection 에서만 뺄수 있는 방법은 없을까?
                oNode.parentNode.removeChild(oNode);
            }
        }

        if (/^insertorderedlist|insertunorderedlist$/i.test(sCommand)) {
            this._getDocumentBR();

            // [SMARTEDITORSUS-985][SMARTEDITORSUS-1740]
            this._checkBlockQuoteCondition_IE();
            // --[SMARTEDITORSUS-985][SMARTEDITORSUS-1740]
        }

        if (/^justify*/i.test(sCommand)) {
            this._removeSpanAlign();
        }

        if (this._rxCmdInline.test(sCommand)) {
            this.oUndoOption = {
                bMustBlockElement: true
            };

            if (nhn.CurrentSelection.isCollapsed()) {
                this._bOnlyCursorChanged = true;
            }
        }

        if (sCommand == "indent" || sCommand == "outdent") {
            if (!htOptions) {
                htOptions = {};
            }
            htOptions["bDontAddUndoHistory"] = true;
        }
        if ((!htOptions || !htOptions["bDontAddUndoHistory"]) && !this._bOnlyCursorChanged) {
            if (/^justify*/i.test(sCommand)) {
                this.oUndoOption = {
                    sSaveTarget: "BODY"
                };
            } else if (sCommand === "insertorderedlist" || sCommand === "insertunorderedlist") {
                this.oUndoOption = {
                    bMustBlockContainer: true
                };
            }

            this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", [sCommand, this.oUndoOption]);
        }
        if (this.oNavigator.ie && this.oApp.getWYSIWYGDocument().selection) {
            if (this.oApp.getWYSIWYGDocument().selection.type === "Control") {
                oSelection = this.oApp.getSelection();
                oSelection.select();
            }
        }

        if (sCommand == "insertorderedlist" || sCommand == "insertunorderedlist") {
            this._insertBlankLine();
        }
    },

    /**
     * [SMARTEDITORSUS-985][SMARTEDITORSUS-1740][SMARTEDITORSUS-1798]
     * [Win XP - IE 8][IE 9~11] 인용구 안에서 번호매기기, 글머리기호를 적용할 때 필요한 조치이다.
     *
     * 인용구 안의 선택한 영역을 기준으로,
     *
     * 선택한 영역이 없는 경우에는 해당 줄을 제외했을 때,
     * 선택한 영역이 있는 경우에는 선택한 줄을 제외했을 때
     *
     * 더 이상의 <P>가 없는 경우
     * execCommand("insertorderedlist"), execCommand("insertunorderedlist")가 오동작한다.
     *
     * 이러한 오동작을 방지하기 위해
     * 인용구 안에서 번호매기기, 글머리기호를 삽입할 때는
     * execCommand() 실행 전에 빈 <P>를 삽입해 주고,
     * execCommand() 실행 후 빈 <P>를 제거해 준다.
     * */
    _checkBlockQuoteCondition_IE: function() {
        var htBrowser = jindo.$Agent().navigator();
        var bProcess = false;
        var elBlockquote;

        if ((htBrowser.ie && (htBrowser.nativeVersion >= 9 && htBrowser.nativeVersion <= 11) && (htBrowser.version >= 9 && htBrowser.version <= 11)) || (this.oApp.oAgent.os().winxp && htBrowser.ie && htBrowser.nativeVersion <= 8)) {
            var oSelection = this.oApp.getSelection();
            var elCommonAncestorContainer = oSelection.commonAncestorContainer;
            var htAncestor_blockquote = nhn.husky.SE2M_Utils.findAncestorByTagNameWithCount("BLOCKQUOTE", elCommonAncestorContainer);
            elBlockquote = htAncestor_blockquote.elNode;

            if (elBlockquote) {
                var htAncestor_cell = nhn.husky.SE2M_Utils.findClosestAncestorAmongTagNamesWithCount(["td", "th"], elCommonAncestorContainer);
                if (htAncestor_cell.elNode) {
                    if (htAncestor_cell.nRecursiveCount > htAncestor_blockquote.nRecursiveCount) {
                        // blockquote가 cell 안에서 생성된 경우
                        bProcess = true;
                    }
                } else {
                    // blockquote가 cell 안에서 생성되지 않은 경우
                    bProcess = true;
                }
            }
        }

        if (bProcess) {
            this._insertDummyParagraph_IE(elBlockquote);
        }
    },

    /**
     * [SMARTEDITORSUS-985][SMARTEDITORSUS-1740]
     * [IE 9~10] 대상 엘리먼트에 빈 <P>를 삽입
     * */
    _insertDummyParagraph_IE: function(el) {
        this._elDummyParagraph = document.createElement("P");
        el.appendChild(this._elDummyParagraph);
    },

    /**
     * [SMARTEDITORSUS-985][SMARTEDITORSUS-1740]
     * [IE 9~10] 빈 <P>를 제거
     * */
    _removeDummyParagraph_IE: function() {
        if (this._elDummyParagraph && this._elDummyParagraph.parentNode) {
            this._elDummyParagraph.parentNode.removeChild(this._elDummyParagraph);
        }
    },

    $ON_EXECCOMMAND: function(sCommand, bUserInterface, vValue) {
        var bSelectedBlock = false;
        var htSelectedTDs = {};
        var oSelection = this.oApp.getSelection();

        bUserInterface = (bUserInterface == "" || bUserInterface) ? bUserInterface : false;
        vValue = (vValue == "" || vValue) ? vValue : false;

        this.oApp.exec("IS_SELECTED_TD_BLOCK", ['bIsSelectedTd', htSelectedTDs]);
        bSelectedBlock = htSelectedTDs.bIsSelectedTd;

        if (bSelectedBlock) {
            if (sCommand == "indent") {
                this.oApp.exec("SET_LINE_BLOCK_STYLE", [null, jindo.$Fn(this._indentMargin, this).bind()]);
            } else if (sCommand == "outdent") {
                this.oApp.exec("SET_LINE_BLOCK_STYLE", [null, jindo.$Fn(this._outdentMargin, this).bind()]);
            } else {
                this._setBlockExecCommand(sCommand, bUserInterface, vValue);
            }
        } else {
            switch (sCommand) {
                case "indent":
                case "outdent":
                    this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", [sCommand]);

                    // bookmark 설정
                    var sBookmark = oSelection.placeStringBookmark();

                    if (sCommand === "indent") {
                        this.oApp.exec("SET_LINE_STYLE", [null, jindo.$Fn(this._indentMargin, this).bind(), {
                            bDoNotSelect: true,
                            bDontAddUndoHistory: true
                        }]);
                    } else {
                        this.oApp.exec("SET_LINE_STYLE", [null, jindo.$Fn(this._outdentMargin, this).bind(), {
                            bDoNotSelect: true,
                            bDontAddUndoHistory: true
                        }]);
                    }

                    oSelection.moveToStringBookmark(sBookmark);
                    oSelection.select();
                    oSelection.removeStringBookmark(sBookmark); //bookmark 삭제

                    setTimeout(jindo.$Fn(function(sCommand) {
                        this.oApp.exec("RECORD_UNDO_AFTER_ACTION", [sCommand]);
                    }, this).bind(sCommand), 25);

                    break;

                case "justifyleft":
                case "justifycenter":
                case "justifyright":
                case "justifyfull":
                    var oSelectionClone = this._extendBlock(); // FF

                    this.oEditingArea.execCommand(sCommand, bUserInterface, vValue);

                    if (!!oSelectionClone) {
                        oSelectionClone.select();
                    }

                    break;

                default:
                    //if(this.oNavigator.firefox){
                    //this.oEditingArea.execCommand("styleWithCSS", bUserInterface, false);
                    //}
                    // [SMARTEDITORSUS-1646] [SMARTEDITORSUS-1653] collapsed 상태이면 execCommand 가 실행되기 전에 ZWSP를 넣어준다.
                    // [SMARTEDITORSUS-1702] ul, ol 처럼 block element 가 바로 생성되는 경우는 ZWSP 삽입 제외
                    if (oSelection.collapsed && this._rxCmdInline.test(sCommand)) {
                        // collapsed 인 경우
                        var sBM = oSelection.placeStringBookmark(),
                            oBM = oSelection.getStringBookmark(sBM),
                            oHolderNode = oBM.previousSibling;

                        // execCommand를 실행할때마다 ZWSP가 포함된 더미 태그가 자꾸 생길 수 있기 때문에 이미 있으면 있는 걸로 사용한다.
                        if (!oHolderNode || oHolderNode.nodeValue !== "\u200B") {
                            oHolderNode = this.oApp.getWYSIWYGDocument().createTextNode("\u200B");
                            oSelection.insertNode(oHolderNode);
                        }
                        oSelection.removeStringBookmark(sBM); // 미리 지워주지 않으면 더미 태그가 생길 수 있다.
                        oSelection.selectNodeContents(oHolderNode);
                        oSelection.select();
                        this.oEditingArea.execCommand(sCommand, bUserInterface, vValue);
                        oSelection.collapseToEnd();
                        oSelection.select();

                        // [SMARTEDITORSUS-1658] 뒤쪽에 더미태그가 있으면 제거해준다.
                        var oSingleNode = this._findSingleNode(oHolderNode);
                        if (oSingleNode && oSelection._hasCursorHolderOnly(oSingleNode.nextSibling)) {
                            oSingleNode.parentNode.removeChild(oSingleNode.nextSibling);
                        }
                    } else {
                        this.oEditingArea.execCommand(sCommand, bUserInterface, vValue);
                    }
            }
        }

        this._countClickCr(sCommand);
    },

    /**
     * [SMARTEDITORSUS-1658] 해당노드의 상위로 검색해 single child 만 갖는 최상위 노드를 찾는다.
     * @param {Node} oNode 확인할 노드
     * @return {Node} single child 만 감싸고 있는 최상위 노드를 반환한다. 없으면 입력한 노드 반환
     */
    _findSingleNode: function(oNode) {
        if (!oNode) {
            return null;
        }
        if (oNode.parentNode.childNodes.length === 1) {
            return this._findSingleNode(oNode.parentNode);
        } else {
            return oNode;
        }
    },

    $AFTER_EXECCOMMAND: function(sCommand, bUserInterface, vValue, htOptions) {
        if (this.elP1 && this.elP1.parentNode) {
            this.elP1.parentNode.removeChild(this.elP1);
        }

        if (this.elP2 && this.elP2.parentNode) {
            this.elP2.parentNode.removeChild(this.elP2);
        }

        if (/^insertorderedlist|insertunorderedlist$/i.test(sCommand)) {
            // this._fixDocumentBR();   // Chrome/Safari
            // [SMARTEDITORSUS-985][SMARTEDITORSUS-1740]
            this._removeDummyParagraph_IE();
            // --[SMARTEDITORSUS-985][SMARTEDITORSUS-1740]
            this._fixCorruptedBlockQuote(sCommand === "insertorderedlist" ? "OL" : "UL"); // IE
            // [SMARTEDITORSUS-1795] 갤럭시노트_Android4.1.2 기본브라우저일 경우 내부에 생성된 BLOCKQUOTE 제거
            if (this.oNavigator.bGalaxyBrowser) {
                this._removeBlockQuote();
            }
        }

        if ((/^justify*/i.test(sCommand))) {
            this._fixAlign(sCommand === "justifyfull" ? "justify" : sCommand.substring(7));
        }

        if (sCommand == "indent" || sCommand == "outdent") {
            if (!htOptions) {
                htOptions = {};
            }
            htOptions["bDontAddUndoHistory"] = true;
        }

        if ((!htOptions || !htOptions["bDontAddUndoHistory"]) && !this._bOnlyCursorChanged) {
            this.oApp.exec("RECORD_UNDO_AFTER_ACTION", [sCommand, this.oUndoOption]);
        }

        this.oApp.exec("CHECK_STYLE_CHANGE", []);
    },

    _removeSpanAlign: function() {
        var oSelection = this.oApp.getSelection(),
            aNodes = oSelection.getNodes(),
            elNode = null;

        for (var i = 0, nLen = aNodes.length; i < nLen; i++) {
            elNode = aNodes[i];

            // [SMARTEDITORSUS-704] SPAN에서 적용된 Align을 제거
            if (elNode.tagName && elNode.tagName === "SPAN") {
                elNode.style.textAlign = "";
                elNode.removeAttribute("align");
            }
        }
    },

    // [SMARTEDITORSUS-851] align, text-align을 fix해야 할 대상 노드를 찾음
    _getAlignNode: function(elNode) {
        if (elNode.tagName && (elNode.tagName === "P" || elNode.tagName === "DIV")) {
            return elNode;
        }

        elNode = elNode.parentNode;

        while (elNode && elNode.tagName) {
            if (elNode.tagName === "P" || elNode.tagName === "DIV") {
                return elNode;
            }

            elNode = elNode.parentNode;
        }
    },

    _fixAlign: function(sAlign) {
        var oSelection = this.oApp.getSelection(),
            aNodes = [],
            elNode = null,
            elParentNode = null;

        var removeTableAlign = !this.oNavigator.ie ? function() {} : function(elNode) {
            if (elNode.tagName && elNode.tagName === "TABLE") {
                elNode.removeAttribute("align");

                return true;
            }

            return false;
        };

        if (oSelection.collapsed) {
            aNodes[0] = oSelection.startContainer; // collapsed인 경우에는 getNodes의 결과는 []
        } else {
            aNodes = oSelection.getNodes();
        }

        for (var i = 0, nLen = aNodes.length; i < nLen; i++) {
            elNode = aNodes[i];

            if (elNode.nodeType === 3) {
                elNode = elNode.parentNode;
            }

            if (elParentNode && (elNode === elParentNode || jindo.$Element(elNode).isChildOf(elParentNode))) {
                continue;
            }

            elParentNode = this._getAlignNode(elNode);

            if (elParentNode && elParentNode.align !== elParentNode.style.textAlign) { // [SMARTEDITORSUS-704] align 속성과 text-align 속성의 값을 맞춰줌
                elParentNode.style.textAlign = sAlign;
                elParentNode.setAttribute("align", sAlign);
            }
        }
    },

    _getDocumentBR: function() {
        var i, nLen;

        // [COM-715] <Chrome/Safari> 요약글 삽입 > 더보기 영역에서 기호매기기, 번호매기기 설정할때마다 요약글 박스가 아래로 이동됨
        // ExecCommand를 처리하기 전에 현재의 BR을 저장

        this.aBRs = this.oApp.getWYSIWYGDocument().getElementsByTagName("BR");
        this.aBeforeBRs = [];

        for (i = 0, nLen = this.aBRs.length; i < nLen; i++) {
            this.aBeforeBRs[i] = this.aBRs[i];
        }
    },

    _fixDocumentBR: function() {
        // [COM-715] ExecCommand가 처리된 후에 업데이트된 BR을 처리 전에 저장한 BR과 비교하여 생성된 BR을 제거

        if (this.aBeforeBRs.length === this.aBRs.length) { // this.aBRs gets updated automatically when the document is updated
            return;
        }

        var waBeforeBRs = jindo.$A(this.aBeforeBRs),
            i, iLen = this.aBRs.length;

        for (i = iLen - 1; i >= 0; i--) {
            if (waBeforeBRs.indexOf(this.aBRs[i]) < 0) {
                this.aBRs[i].parentNode.removeChild(this.aBRs[i]);
            }
        }
    },

    _setBlockExecCommand: function(sCommand, bUserInterface, vValue) {
        var aNodes, aChildrenNode, htSelectedTDs = {};
        this.oSelection = this.oApp.getSelection();
        this.oApp.exec("GET_SELECTED_TD_BLOCK", ['aTdCells', htSelectedTDs]);
        aNodes = htSelectedTDs.aTdCells;

        for (var j = 0; j < aNodes.length; j++) {

            this.oSelection.selectNodeContents(aNodes[j]);
            this.oSelection.select();

            if (this.oNavigator.firefox) {
                this.oEditingArea.execCommand("styleWithCSS", bUserInterface, false); //styleWithCSS는 ff전용임.
            }

            aChildrenNode = this.oSelection.getNodes();
            for (var k = 0; k < aChildrenNode.length; k++) {
                if (aChildrenNode[k].tagName == "UL" || aChildrenNode[k].tagName == "OL") {
                    jindo.$Element(aChildrenNode[k]).css("color", vValue);
                }
            }
            this.oEditingArea.execCommand(sCommand, bUserInterface, vValue);
        }
    },

    _indentMargin: function(elDiv) {
        var elTmp = elDiv,
            aAppend, i, nLen, elInsertTarget, elDeleteTarget, nCurMarginLeft;

        while (elTmp) {
            if (elTmp.tagName && elTmp.tagName === "LI") {
                elDiv = elTmp;
                break;
            }
            elTmp = elTmp.parentNode;
        }

        if (elDiv.tagName === "LI") {
            //<OL>
            //  <OL>
            //      <LI>22</LI>
            //  </OL>
            //  <LI>33</LI>
            //</OL>
            //와 같은 형태라면 33을 들여쓰기 했을 때, 상단의 silbling OL과 합쳐서 아래와 같이 만들어 줌.
            //<OL>
            //  <OL>
            //      <LI>22</LI>
            //      <LI>33</LI>
            //  </OL>
            //</OL>
            if (elDiv.previousSibling && elDiv.previousSibling.tagName && elDiv.previousSibling.tagName === elDiv.parentNode.tagName) {
                // 하단에 또다른 OL이 있어 아래와 같은 형태라면,
                //<OL>
                //  <OL>
                //      <LI>22</LI>
                //  </OL>
                //  <LI>33</LI>
                //  <OL>
                //      <LI>44</LI>
                //  </OL>
                //</OL>
                //22,33,44를 합쳐서 아래와 같이 만들어 줌.
                //<OL>
                //  <OL>
                //      <LI>22</LI>
                //      <LI>33</LI>
                //      <LI>44</LI>
                //  </OL>
                //</OL>
                if (elDiv.nextSibling && elDiv.nextSibling.tagName && elDiv.nextSibling.tagName === elDiv.parentNode.tagName) {
                    aAppend = [elDiv];

                    for (i = 0, nLen = elDiv.nextSibling.childNodes.length; i < nLen; i++) {
                        aAppend.push(elDiv.nextSibling.childNodes[i]);
                    }

                    elInsertTarget = elDiv.previousSibling;
                    elDeleteTarget = elDiv.nextSibling;

                    for (i = 0, nLen = aAppend.length; i < nLen; i++) {
                        elInsertTarget.insertBefore(aAppend[i], null);
                    }

                    elDeleteTarget.parentNode.removeChild(elDeleteTarget);
                } else {
                    elDiv.previousSibling.insertBefore(elDiv, null);
                }

                return;
            }

            //<OL>
            //  <LI>22</LI>
            //  <OL>
            //      <LI>33</LI>
            //  </OL>
            //</OL>
            //와 같은 형태라면 22을 들여쓰기 했을 때, 하단의 silbling OL과 합친다.
            if (elDiv.nextSibling && elDiv.nextSibling.tagName && elDiv.nextSibling.tagName === elDiv.parentNode.tagName) {
                elDiv.nextSibling.insertBefore(elDiv, elDiv.nextSibling.firstChild);
                return;
            }

            elTmp = elDiv.parentNode.cloneNode(false);
            elDiv.parentNode.insertBefore(elTmp, elDiv);
            elTmp.appendChild(elDiv);
            return;
        }

        nCurMarginLeft = parseInt(elDiv.style.marginLeft, 10);

        if (!nCurMarginLeft) {
            nCurMarginLeft = 0;
        }

        nCurMarginLeft += this.nIndentSpacing;
        elDiv.style.marginLeft = nCurMarginLeft + "px";
    },

    _outdentMargin: function(elDiv) {
        var elTmp = elDiv,
            elParentNode, elInsertBefore, elNewParent, elInsertParent, oDoc, nCurMarginLeft;

        while (elTmp) {
            if (elTmp.tagName && elTmp.tagName === "LI") {
                elDiv = elTmp;
                break;
            }
            elTmp = elTmp.parentNode;
        }

        if (elDiv.tagName === "LI") {
            elParentNode = elDiv.parentNode;
            elInsertBefore = elDiv.parentNode;

            // LI를 적절 위치로 이동.
            // 위에 다른 li/ol/ul가 있는가?
            if (elDiv.previousSibling && elDiv.previousSibling.tagName && elDiv.previousSibling.tagName.match(/LI|UL|OL/)) {
                // 위아래로 sibling li/ol/ul가 있다면 ol/ul를 2개로 나누어야됨
                if (elDiv.nextSibling && elDiv.nextSibling.tagName && elDiv.nextSibling.tagName.match(/LI|UL|OL/)) {
                    elNewParent = elParentNode.cloneNode(false);

                    while (elDiv.nextSibling) {
                        elNewParent.insertBefore(elDiv.nextSibling, null);
                    }

                    elParentNode.parentNode.insertBefore(elNewParent, elParentNode.nextSibling);
                    elInsertBefore = elNewParent;
                    // 현재 LI가 마지막 LI라면 부모 OL/UL 하단에 삽입
                } else {
                    elInsertBefore = elParentNode.nextSibling;
                }
            }
            elParentNode.parentNode.insertBefore(elDiv, elInsertBefore);

            // 내어쓰기 한 LI 외에 다른 LI가 존재 하지 않을 경우 부모 노드 지워줌
            if (!elParentNode.innerHTML.match(/LI/i)) {
                elParentNode.parentNode.removeChild(elParentNode);
            }

            // OL이나 UL 위로까지 내어쓰기가 된 상태라면 LI를 벗겨냄
            if (!elDiv.parentNode.tagName.match(/OL|UL/)) {
                elInsertParent = elDiv.parentNode;
                elInsertBefore = elDiv;

                // 내용물을 P로 감싸기
                oDoc = this.oApp.getWYSIWYGDocument();
                elInsertParent = oDoc.createElement("P");
                elInsertBefore = null;

                elDiv.parentNode.insertBefore(elInsertParent, elDiv);

                while (elDiv.firstChild) {
                    elInsertParent.insertBefore(elDiv.firstChild, elInsertBefore);
                }
                elDiv.parentNode.removeChild(elDiv);
            }
            return;
        }
        nCurMarginLeft = parseInt(elDiv.style.marginLeft, 10);

        if (!nCurMarginLeft) {
            nCurMarginLeft = 0;
        }

        nCurMarginLeft -= this.nIndentSpacing;

        if (nCurMarginLeft < 0) {
            nCurMarginLeft = 0;
        }

        elDiv.style.marginLeft = nCurMarginLeft + "px";
    },

    // Fix IE's execcommand bug
    // When insertorderedlist/insertunorderedlist is executed on a blockquote, the blockquote will "suck in" directly neighboring OL, UL's if there's any.
    // To prevent this, insert empty P tags right before and after the blockquote and remove them after the execution.
    // [SMARTEDITORSUS-793] Chrome 에서 동일한 이슈 발생, Chrome 은 빈 P 태그로는 처리되지 않으 &nbsp; 추가
    // [SMARTEDITORSUS-1857] 인용구내에 UL/OL이 있고 바깥에서 UL/OL을 실행하는 경우도 동일한 문제가 발생하여 동일한 방식으로 해결하도록 해당 케이스 추가
    _insertBlankLine: function() {
        var oSelection = this.oApp.getSelection();
        var elNode = oSelection.commonAncestorContainer;
        this.elP1 = null;
        this.elP2 = null;

        // [SMARTEDITORSUS-793] 인용구 안에서 글머리기호/번호매기기하는 경우에 대한 처리
        while (elNode) {
            if (elNode.tagName == "BLOCKQUOTE") {
                this.elP1 = jindo.$("<p>&nbsp;</p>", this.oApp.getWYSIWYGDocument());
                elNode.parentNode.insertBefore(this.elP1, elNode);

                this.elP2 = jindo.$("<p>&nbsp;</p>", this.oApp.getWYSIWYGDocument());
                elNode.parentNode.insertBefore(this.elP2, elNode.nextSibling);

                break;
            }
            elNode = elNode.parentNode;
        }

        // [SMARTEDITORSUS-1857] 인용구 바깥에서 글머리기호/번호매기기하는 경우에 대한 처리
        if (!this.elP1 && !this.elP2) {
            elNode = oSelection.commonAncestorContainer;
            elNode = (elNode.nodeType !== 1) ? elNode.parentNode : elNode;
            var elPrev = elNode.previousSibling,
                elNext = elNode.nextSibling;

            if (elPrev && elPrev.tagName === "BLOCKQUOTE") {
                this.elP1 = jindo.$("<p>&nbsp;</p>", this.oApp.getWYSIWYGDocument());
                elPrev.parentNode.insertBefore(this.elP1, elPrev.nextSibling);
            }
            if (elNext && elNext.tagName === "BLOCKQUOTE") {
                this.elP1 = jindo.$("<p>&nbsp;</p>", this.oApp.getWYSIWYGDocument());
                elNext.parentNode.insertBefore(this.elP1, elNext);
            }
        }
    },

    // Fix IE's execcommand bug
    // When insertorderedlist/insertunorderedlist is executed on a blockquote with all the child nodes selected,
    // eg:<blockquote>[selection starts here]blah...[selection ends here]</blockquote>
    // , IE will change the blockquote with the list tag and create <OL><OL><LI>blah...</LI></OL></OL>.
    // (two OL's or two UL's depending on which command was executed)
    //
    // It can also happen when the cursor is located at bogus positions like
    // * below blockquote when the blockquote is the last element in the document
    //
    // [IE] 인용구 안에서 글머리 기호를 적용했을 때, 인용구 밖에 적용된 번호매기기/글머리 기호가 인용구 안으로 빨려 들어가는 문제 처리
    _fixCorruptedBlockQuote: function(sTagName) {
        var aNodes = this.oApp.getWYSIWYGDocument().getElementsByTagName(sTagName),
            elCorruptedBlockQuote, elTmpParent, elNewNode, aLists,
            i, nLen, nPos, el, oSelection;

        for (i = 0, nLen = aNodes.length; i < nLen; i++) {
            if (aNodes[i].firstChild && aNodes[i].firstChild.tagName == sTagName) {
                elCorruptedBlockQuote = aNodes[i];
                break;
            }
        }

        if (!elCorruptedBlockQuote) {
            return;
        }

        elTmpParent = elCorruptedBlockQuote.parentNode;

        // (1) changing outerHTML will cause loss of the reference to the node, so remember the idx position here
        nPos = this._getPosIdx(elCorruptedBlockQuote);
        el = this.oApp.getWYSIWYGDocument().createElement("DIV");
        el.innerHTML = elCorruptedBlockQuote.outerHTML.replace("<" + sTagName, "<BLOCKQUOTE");
        elCorruptedBlockQuote.parentNode.insertBefore(el.firstChild, elCorruptedBlockQuote);
        elCorruptedBlockQuote.parentNode.removeChild(elCorruptedBlockQuote);

        // (2) and retrieve the new node here
        elNewNode = elTmpParent.childNodes[nPos];

        // garbage <OL></OL> or <UL></UL> will be left over after setting the outerHTML, so remove it here.
        aLists = elNewNode.getElementsByTagName(sTagName);

        for (i = 0, nLen = aLists.length; i < nLen; i++) {
            if (aLists[i].childNodes.length < 1) {
                aLists[i].parentNode.removeChild(aLists[i]);
            }
        }

        oSelection = this.oApp.getEmptySelection();
        oSelection.selectNodeContents(elNewNode);
        oSelection.collapseToEnd();
        oSelection.select();
    },

    /**
     * [SMARTEDITORSUS-1795] UL/OL 삽입시 LI 하위에 BLOCKQUOTE 가 있으면 제거한다.
     * <blockquote><p><ul><li><span class="Apple-style-span"><blockquote><p style="display: inline !important;">선택영역</p></blockquote></span></li></ul></p><blockquote>
     * 삭제될때도 복사됨
     * <blockquote><p><span class="Apple-style-span"><blockquote><p style="display: inline !important;">선택영역</p></blockquote></span></p><blockquote>
     */
    _removeBlockQuote: function() {
        var sVendorSpanClass = "Apple-style-span",
            elVendorSpan,
            aelVendorSpanDummy,
            oSelection = this.oApp.getSelection(),
            elNode = oSelection.commonAncestorContainer,
            elChild = elNode,
            elLi;

        // LI 와 SPAN.Apple-style-span 를 찾는다.
        while (elNode) {
            if (elNode.tagName === "LI") {
                elLi = elNode;
                elNode = (elNode.style.cssText === "display: inline !important; ") ? elNode.parentNode : null;
            } else if (elNode.tagName === "SPAN" && elNode.className === sVendorSpanClass) {
                elVendorSpan = elNode;
                elNode = (!elLi) ? elNode.parentNode : null;
            } else {
                elNode = elNode.parentNode;
            }
        }
        // SPAN.Apple-style-span 을 selection 된 텍스트로 교체한 후 다시 selection을 준다.
        if (elLi && elVendorSpan) {
            elNode = elVendorSpan.parentNode;
            elNode.replaceChild(elChild, elVendorSpan);
            oSelection.selectNodeContents(elNode);
            oSelection.collapseToEnd();
            oSelection.select();
        }
        // BLOCKQUOTE 내에 남아있는 SPAN.Apple-style-span 을 제거한다.(UL과 OL 교체시 남게되는 더미 SPAN 제거용)
        while (elNode) {
            if (elNode.tagName === "BLOCKQUOTE") {
                aelVendorSpanDummy = elNode.getElementsByClassName(sVendorSpanClass);
                for (var i = 0;
                    (elVendorSpan = aelVendorSpanDummy[i]); i++) {
                    elVendorSpan.parentNode.removeChild(elVendorSpan);
                }
                elNode = null;
            } else {
                elNode = elNode.parentNode;
            }
        }
    },

    _getPosIdx: function(refNode) {
        var idx = 0;
        for (var node = refNode.previousSibling; node; node = node.previousSibling) {
            idx++;
        }

        return idx;
    },

    _countClickCr: function(sCommand) {
        if (!sCommand.match(this.rxClickCr)) {
            return;
        }

        this.oApp.exec('MSG_NOTIFY_CLICKCR', [sCommand.replace(/^insert/i, '')]);
    },

    _extendBlock: function() {
        // [SMARTEDITORSUS-663] [FF] block단위로 확장하여 Range를 새로 지정해주는것이 원래 스펙이므로
        // 해결을 위해서는 현재 선택된 부분을 Block으로 extend하여 execCommand API가 처리될 수 있도록 함

        var oSelection = this.oApp.getSelection(),
            oStartContainer = oSelection.startContainer,
            oEndContainer = oSelection.endContainer,
            aChildImg = [],
            aSelectedImg = [],
            oSelectionClone = oSelection.cloneRange();

        // <p><img><br/><img><br/><img></p> 일 때 이미지가 일부만 선택되면 발생
        // - container 노드는 P 이고 container 노드의 자식노드 중 이미지가 여러개인데 선택된 이미지가 그 중 일부인 경우

        if (!(oStartContainer === oEndContainer && oStartContainer.nodeType === 1 && oStartContainer.tagName === "P")) {
            return;
        }

        aChildImg = jindo.$A(oStartContainer.childNodes).filter(function(value, index, array) {
            return (value.nodeType === 1 && value.tagName === "IMG");
        }).$value();

        aSelectedImg = jindo.$A(oSelection.getNodes()).filter(function(value, index, array) {
            return (value.nodeType === 1 && value.tagName === "IMG");
        }).$value();

        if (aChildImg.length <= aSelectedImg.length) {
            return;
        }

        oSelection.selectNode(oStartContainer);
        oSelection.select();

        return oSelectionClone;
    }
});
//}
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to styling the font
 * @name hp_SE_WYSIWYGStyler.js
 * @required SE_EditingArea_WYSIWYG, HuskyRangeManager
 */
nhn.husky.SE_WYSIWYGStyler = jindo.$Class({
    name: "SE_WYSIWYGStyler",
    _sCursorHolder: "\uFEFF",

    $init: function() {
        var htBrowser = jindo.$Agent().navigator();

        if (htBrowser.ie && htBrowser.version > 8) {
            // [SMARTEDITORSUS-178] ZWNBSP(\uFEFF) 를 사용하면 IE9 이상의 경우 높이값을 갖지 못해 커서위치가 이상함
            // [SMARTEDITORSUS-1704] ZWSP(\u200B) 를 사용할 경우 줄바꿈이 됨
            // 기본적으로 \uFEFF 를 사용하고 IE9 이상만 \u2060 사용 (\u2060 은 \uFEFF 와 동일한 역할을 하지만 크롬에서는 깨짐)
            // *주의* 작성자가 IE9이상에서 작성하고 독자가 크롬에서 볼 경우 \u2060 가 깨진 문자로 보여질 수 있기 때문에 컨버터를 통해 \u2060 를 \uFEFF 로 변환한다.
            // FIXME: 단, \u2060 를 \uFEFF 변환으로 인해 SPAN태그만 들어있는 상태에서 모드를 변환하면 커서 위치가 다시 이상해질 수 있음
            // 참고:
            // http://en.wikipedia.org/wiki/Universal_Character_Set_characters#Word_joiners_and_separators
            // http://en.wikipedia.org/wiki/Zero-width_no-break_space
            // https://www.cs.tut.fi/~jkorpela/chars/spaces.html
            this._sCursorHolder = "\u2060";
            this.$ON_REGISTER_CONVERTERS = function() {
                var rx2060 = /\u2060/g;
                this.oApp.exec("ADD_CONVERTER", ["WYSIWYG_TO_IR", jindo.$Fn(function(sContents) {
                    return sContents.replace(rx2060, "\uFEFF");
                }, this).bind()]);
            };
        }
    },

    $PRECONDITION: function(sFullCommand, aArgs) {
        return (this.oApp.getEditingMode() == "WYSIWYG");
    },

    $ON_SET_WYSIWYG_STYLE: function(oStyles) {
        var oSelection = this.oApp.getSelection();
        var htSelectedTDs = {};
        this.oApp.exec("IS_SELECTED_TD_BLOCK", ['bIsSelectedTd', htSelectedTDs]);
        var bSelectedBlock = htSelectedTDs.bIsSelectedTd;

        // style cursor or !(selected block)
        if (oSelection.collapsed && !bSelectedBlock) {
            this.oApp.exec("RECORD_UNDO_ACTION", ["FONT STYLE", {
                bMustBlockElement: true
            }]);

            var oSpan, bNewSpan = false;
            var elCAC = oSelection.commonAncestorContainer;
            //var elCAC = nhn.CurrentSelection.getCommonAncestorContainer();
            if (elCAC.nodeType == 3) {
                elCAC = elCAC.parentNode;
            }

            // [SMARTEDITORSUS-1648] SPAN > 굵게/밑줄/기울림/취소선이 있는 경우, 상위 SPAN을 찾는다.
            if (elCAC && oSelection._rxCursorHolder.test(elCAC.innerHTML)) {
                oSpan = oSelection._findParentSingleSpan(elCAC);
            }
            // 스타일을 적용할 SPAN이 없으면 새로 생성
            if (!oSpan) {
                oSpan = this.oApp.getWYSIWYGDocument().createElement("SPAN");
                oSpan.innerHTML = this._sCursorHolder;
                bNewSpan = true;
            } else if (oSpan.innerHTML == "") { // 내용이 아예 없으면 크롬에서 커서가 위치하지 못함
                oSpan.innerHTML = this._sCursorHolder;
            }

            var sValue;
            for (var sName in oStyles) {
                sValue = oStyles[sName];

                if (typeof sValue != "string") {
                    continue;
                }

                oSpan.style[sName] = sValue;
            }

            if (bNewSpan) {
                if (oSelection.startContainer.tagName == "BODY" && oSelection.startOffset === 0) {
                    var oVeryFirstNode = oSelection._getVeryFirstRealChild(this.oApp.getWYSIWYGDocument().body);

                    var bAppendable = true;
                    var elTmp = oVeryFirstNode.cloneNode(false);
                    // some browsers may throw an exception for trying to set the innerHTML of BR/IMG tags
                    try {
                        elTmp.innerHTML = "test";

                        if (elTmp.innerHTML != "test") {
                            bAppendable = false;
                        }
                    } catch (e) {
                        bAppendable = false;
                    }

                    if (bAppendable && elTmp.nodeType == 1 && elTmp.tagName == "BR") { // [SMARTEDITORSUS-311] [FF4] Cursor Holder 인 BR 의 하위노드로 SPAN 을 추가하여 발생하는 문제
                        oSelection.selectNode(oVeryFirstNode);
                        oSelection.collapseToStart();
                        oSelection.insertNode(oSpan);
                    } else if (bAppendable && oVeryFirstNode.tagName != "IFRAME" && oVeryFirstNode.appendChild && typeof oVeryFirstNode.innerHTML == "string") {
                        oVeryFirstNode.appendChild(oSpan);
                    } else {
                        oSelection.selectNode(oVeryFirstNode);
                        oSelection.collapseToStart();
                        oSelection.insertNode(oSpan);
                    }
                } else {
                    oSelection.collapseToStart();
                    oSelection.insertNode(oSpan);
                }
            } else {
                oSelection = this.oApp.getEmptySelection();
            }

            // [SMARTEDITORSUS-229] 새로 생성되는 SPAN 에도 취소선/밑줄 처리 추가
            if (!!oStyles.color) {
                oSelection._checkTextDecoration(oSpan);
            }

            // [SMARTEDITORSUS-1648] oSpan이 굵게//밑줄/기울임/취소선태그보다 상위인 경우, IE에서 굵게//밑줄/기울임/취소선태그 밖으로 나가게 된다. 때문에 SPAN을 새로 만든 경우 oSpan을, 그렇지 않은 경우 elCAC를 잡는다.
            oSelection.selectNodeContents(bNewSpan ? oSpan : elCAC);
            oSelection.collapseToEnd();
            // TODO: focus 는 왜 있는 것일까? => IE에서 style 적용후 포커스가 날아가서 글작성이 안됨???
            oSelection._window.focus();
            oSelection._window.document.body.focus();
            oSelection.select();

            // 영역으로 스타일이 잡혀 있는 경우(예:현재 커서가 B블럭 안에 존재) 해당 영역이 사라져 버리는 오류 발생해서 제거
            // http://bts.nhncorp.com/nhnbts/browse/COM-912
            /*
                        var oCursorStyle = this.oApp.getCurrentStyle();
                        if(oCursorStyle.bold == "@^"){
                            this.oApp.delayedExec("EXECCOMMAND", ["bold"], 0);
                        }
                        if(oCursorStyle.underline == "@^"){
                            this.oApp.delayedExec("EXECCOMMAND", ["underline"], 0);
                        }
                        if(oCursorStyle.italic == "@^"){
                            this.oApp.delayedExec("EXECCOMMAND", ["italic"], 0);
                        }
                        if(oCursorStyle.lineThrough == "@^"){
                            this.oApp.delayedExec("EXECCOMMAND", ["strikethrough"], 0);
                        }
            */
            // FF3 will actually display %uFEFF when it is followed by a number AND certain font-family is used(like Gulim), so remove the character for FF3
            //if(jindo.$Agent().navigator().firefox && jindo.$Agent().navigator().version == 3){
            // FF4+ may have similar problems, so ignore the version number
            // [SMARTEDITORSUS-416] 커서가 올라가지 않도록 BR 을 살려둠
            // if(jindo.$Agent().navigator().firefox){
            // oSpan.innerHTML = "";
            // }
            return;
        }

        this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", ["FONT STYLE", {
            bMustBlockElement: true
        }]);

        if (bSelectedBlock) {
            var aNodes;

            this.oApp.exec("GET_SELECTED_TD_BLOCK", ['aTdCells', htSelectedTDs]);
            aNodes = htSelectedTDs.aTdCells;

            for (var j = 0; j < aNodes.length; j++) {
                oSelection.selectNodeContents(aNodes[j]);
                oSelection.styleRange(oStyles);
                oSelection.select();
            }
        } else {
            var bCheckTextDecoration = !!oStyles.color; // [SMARTEDITORSUS-26] 취소선/밑줄 색상 적용 처리
            var bIncludeLI = oStyles.fontSize || oStyles.fontFamily;
            oSelection.styleRange(oStyles, null, null, bIncludeLI, bCheckTextDecoration);

            // http://bts.nhncorp.com/nhnbts/browse/COM-964
            //
            // In FF when,
            // 1) Some text was wrapped with a styling SPAN and a bogus BR is followed
            //  eg: <span style="XXX">TEST</span><br>
            // 2) And some place outside the span is clicked.
            //
            // The text cursor will be located outside the SPAN like the following,
            // <span style="XXX">TEST</span>[CURSOR]<br>
            //
            // which is not what the user would expect
            // Desired result: <span style="XXX">TEST[CURSOR]</span><br>
            //
            // To make the cursor go inside the styling SPAN, remove the bogus BR when the styling SPAN is created.
            //  -> Style TEST<br> as <span style="XXX">TEST</span> (remove unnecessary BR)
            //  -> Cannot monitor clicks/cursor position real-time so make the contents error-proof instead.
            if (jindo.$Agent().navigator().firefox) {
                var aStyleParents = oSelection.aStyleParents;
                for (var i = 0, nLen = aStyleParents.length; i < nLen; i++) {
                    var elNode = aStyleParents[i];
                    if (elNode.nextSibling && elNode.nextSibling.tagName == "BR" && !elNode.nextSibling.nextSibling) {
                        elNode.parentNode.removeChild(elNode.nextSibling);
                    }
                }
            }

            oSelection._window.focus();
            oSelection.select();
        }

        this.oApp.exec("RECORD_UNDO_AFTER_ACTION", ["FONT STYLE", {
            bMustBlockElement: true
        }]);
    }
});
//}
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to detecting the style change
 * @name hp_SE_WYSIWYGStyleGetter.js
 */
nhn.husky.SE_WYSIWYGStyleGetter = jindo.$Class({
    name: "SE_WYSIWYGStyleGetter",

    hKeyUp: null,

    getStyleInterval: 200,

    oStyleMap: {
        fontFamily: {
            type: "Value",
            css: "fontFamily"
        },
        fontSize: {
            type: "Value",
            css: "fontSize"
        },
        lineHeight: {
            type: "Value",
            css: "lineHeight",
            converter: function(sValue, oStyle) {
                if (!sValue.match(/px$/)) {
                    return sValue;
                }

                return Math.ceil((parseInt(sValue, 10) / parseInt(oStyle.fontSize, 10)) * 10) / 10;
            }
        },
        bold: {
            command: "bold"
        },
        underline: {
            command: "underline"
        },
        italic: {
            command: "italic"
        },
        lineThrough: {
            command: "strikethrough"
        },
        superscript: {
            command: "superscript"
        },
        subscript: {
            command: "subscript"
        },
        justifyleft: {
            command: "justifyleft"
        },
        justifycenter: {
            command: "justifycenter"
        },
        justifyright: {
            command: "justifyright"
        },
        justifyfull: {
            command: "justifyfull"
        },
        orderedlist: {
            command: "insertorderedlist"
        },
        unorderedlist: {
            command: "insertunorderedlist"
        }
    },

    $init: function() {
        this.oStyle = this._getBlankStyle();
    },

    $LOCAL_BEFORE_ALL: function() {
        return (this.oApp.getEditingMode() == "WYSIWYG");
    },

    $ON_MSG_APP_READY: function() {
        this.oDocument = this.oApp.getWYSIWYGDocument();
        this.oApp.exec("ADD_APP_PROPERTY", ["getCurrentStyle", jindo.$Fn(this.getCurrentStyle, this).bind()]);

        if (jindo.$Agent().navigator().safari || jindo.$Agent().navigator().chrome || jindo.$Agent().navigator().ie) {
            this.oStyleMap.textAlign = {
                type: "Value",
                css: "textAlign"
            };
        }
    },

    $ON_EVENT_EDITING_AREA_MOUSEUP: function(oEvnet) {
        /*
        if(this.hKeyUp){
            clearTimeout(this.hKeyUp);
        }
        this.oApp.delayedExec("CHECK_STYLE_CHANGE", [], 100);
        */
        this.oApp.exec("CHECK_STYLE_CHANGE");
    },

    $ON_EVENT_EDITING_AREA_KEYPRESS: function(oEvent) {
        // ctrl+a in FF triggers keypress event with keyCode 97, other browsers don't throw keypress event for ctrl+a
        var oKeyInfo;
        if (this.oApp.oNavigator.firefox) {
            oKeyInfo = oEvent.key();
            if (oKeyInfo.ctrl && oKeyInfo.keyCode == 97) {
                return;
            }
        }

        if (this.bAllSelected) {
            this.bAllSelected = false;
            return;
        }

        /*
        // queryCommandState often fails to return correct result for Korean/Enter. So just ignore them.
        if(this.oApp.oNavigator.firefox && (oKeyInfo.keyCode == 229 || oKeyInfo.keyCode == 13)){
            return;
        }
        */

        this.oApp.exec("CHECK_STYLE_CHANGE");
        //this.oApp.delayedExec("CHECK_STYLE_CHANGE", [], 0);
    },

    $ON_EVENT_EDITING_AREA_KEYDOWN: function(oEvent) {
        var oKeyInfo = oEvent.key();

        // ctrl+a
        if ((this.oApp.oNavigator.ie || this.oApp.oNavigator.firefox) && oKeyInfo.ctrl && oKeyInfo.keyCode == 65) {
            this.oApp.exec("RESET_STYLE_STATUS");
            this.bAllSelected = true;
            return;
        }

        /*
        backspace 8
        enter 13
        page up 33
        page down 34
        end 35
        home 36
        left arrow 37
        up arrow 38
        right arrow 39
        down arrow 40
        insert 45
        delete 46
        */
        // other key strokes are taken care by keypress event
        if (!(oKeyInfo.keyCode == 8 || (oKeyInfo.keyCode >= 33 && oKeyInfo.keyCode <= 40) || oKeyInfo.keyCode == 45 || oKeyInfo.keyCode == 46)) return;

        // [SMARTEDITORSUS-1841] IE11에서 테이블 첫번째 셀에서 shift+end 를 두번 실행하면 오류 발생
        // ctrl+a 를 다루는 방식대로 RESET_STYLE_STATUS 를 수행하고 CHECK_STYLE_CHANGE 는 수행하지 않도록 처리
        if (oKeyInfo.shift && oKeyInfo.keyCode === 35) {
            this.oApp.exec("RESET_STYLE_STATUS");
            this.bAllSelected = true;
            return;
        }

        // take care of ctrl+a -> delete/bksp sequence
        if (this.bAllSelected) {
            // firefox will throw both keydown and keypress events for those input(keydown first), so let keypress take care of them
            if (this.oApp.oNavigator.firefox) {
                return;
            }

            this.bAllSelected = false;
            return;
        }

        this.oApp.exec("CHECK_STYLE_CHANGE");
    },

    $ON_CHECK_STYLE_CHANGE: function() {
        this._getStyle();
    },

    $ON_RESET_STYLE_STATUS: function() {
        this.oStyle = this._getBlankStyle();
        var oBodyStyle = this._getStyleOf(this.oApp.getWYSIWYGDocument().body);
        this.oStyle.fontFamily = oBodyStyle.fontFamily;
        this.oStyle.fontSize = oBodyStyle.fontSize;
        this.oStyle["justifyleft"] = "@^";
        for (var sAttributeName in this.oStyle) {
            //this.oApp.exec("SET_STYLE_STATUS", [sAttributeName, this.oStyle[sAttributeName]]);
            this.oApp.exec("MSG_STYLE_CHANGED", [sAttributeName, this.oStyle[sAttributeName]]);
        }
    },

    getCurrentStyle: function() {
        return this.oStyle;
    },

    _check_style_change: function() {
        this.oApp.exec("CHECK_STYLE_CHANGE", []);
    },

    _getBlankStyle: function() {
        var oBlankStyle = {};
        for (var attributeName in this.oStyleMap) {
            if (this.oStyleMap[attributeName].type == "Value") {
                oBlankStyle[attributeName] = "";
            } else {
                oBlankStyle[attributeName] = 0;
            }
        }

        return oBlankStyle;
    },

    _getStyle: function() {
        var oStyle;
        if (nhn.CurrentSelection.isCollapsed()) {
            oStyle = this._getStyleOf(nhn.CurrentSelection.getCommonAncestorContainer());
        } else {
            var oSelection = this.oApp.getSelection();

            var funcFilter = function(oNode) {
                if (!oNode.childNodes || oNode.childNodes.length == 0)
                    return true;
                else
                    return false;
            }

            var aBottomNodes = oSelection.getNodes(false, funcFilter);

            if (aBottomNodes.length == 0) {
                oStyle = this._getStyleOf(oSelection.commonAncestorContainer);
            } else {
                oStyle = this._getStyleOf(aBottomNodes[0]);
            }
        }

        for (attributeName in oStyle) {
            if (this.oStyleMap[attributeName].converter) {
                oStyle[attributeName] = this.oStyleMap[attributeName].converter(oStyle[attributeName], oStyle);
            }

            if (this.oStyle[attributeName] != oStyle[attributeName]) {
                /**
                 * [SMARTEDITORSUS-1803] 글꼴을 변경할 때는 글자크기 변경사항은 반영되지 않도록 함 - getComputedStyle() 버그
                 *
                 * 글꼴이나 글자 크기를 변경할 때마다,
                 * this.oApp.exec("CHECK_STYLE_CHANGE")가 호출되는데,
                 * 이 때 대상 스타일 뿐 아니라 모든 요소의 변화를 확인하게 된다.
                 *
                 * 글꼴만 변경하는 경우에도
                 * getComputedStyle() 반올림 방식으로 인한 오차로 인해
                 * pt 단위의 글자크기가 px로 바뀌게 되는데,
                 *
                 * 스타일 변화 확인에는 jindo.$Element().css()를 사용하는데,
                 * el.currentStyle - getComputedStyle(el)의 순위로 존재여부를 확인하여 사용한다.
                 *
                 * getComputedStyle(el)을 사용하는 경우,
                 * 대상 엘리먼트에 pt 단위의 값이 지정되어 있었다면
                 * 다음의 순서를 거친다.
                 * - pt 단위를 px 단위로 변환
                 * - 소수점 이하 값을 반올림
                 *
                 * 글자 크기의 경우 이 영향으로
                 * 산술적인 pt-px 변환이 아닌 값으로 변경되어
                 * 툴바에 노출되는 값 계산에 사용될 수 있다.
                 * */
                if ((typeof(document.body.currentStyle) != "object") && (typeof(getComputedStyle) == "function")) {
                    if ((attributeName == "fontSize") && (this.oStyle["fontFamily"] != oStyle["fontFamily"])) {
                        continue;
                    }
                }
                // --[SMARTEDITORSUS-1803]
                this.oApp.exec("MSG_STYLE_CHANGED", [attributeName, oStyle[attributeName]]);
            }
        }

        this.oStyle = oStyle;
    },

    _getStyleOf: function(oNode) {
        var oStyle = this._getBlankStyle();

        // this must not happen
        if (!oNode) {
            return oStyle;
        }

        if (oNode.nodeType == 3) {
            oNode = oNode.parentNode;
        } else if (oNode.nodeType == 9) {
            //document에는 css를 적용할 수 없음.
            oNode = oNode.body;
        }

        var welNode = jindo.$Element(oNode);
        var attribute, cssName;

        for (var styleName in this.oStyle) {
            attribute = this.oStyleMap[styleName];
            if (attribute.type && attribute.type == "Value") {
                try {
                    if (attribute.css) {
                        var sValue = welNode.css(attribute.css);
                        if (styleName == "fontFamily") {
                            sValue = sValue.split(/,/)[0];
                        }

                        oStyle[styleName] = sValue;
                    } else if (attribute.command) {
                        oStyle[styleName] = this.oDocument.queryCommandState(attribute.command);
                    } else {
                        // todo
                    }
                } catch (e) {}
            } else {
                if (attribute.command) {
                    try {
                        if (this.oDocument.queryCommandState(attribute.command)) {
                            oStyle[styleName] = "@^";
                        } else {
                            oStyle[styleName] = "@-";
                        }
                    } catch (e) {}
                } else {
                    // todo
                }
            }
        }

        switch (oStyle["textAlign"]) {
            case "left":
                oStyle["justifyleft"] = "@^";
                break;
            case "center":
                oStyle["justifycenter"] = "@^";
                break;
            case "right":
                oStyle["justifyright"] = "@^";
                break;
            case "justify":
                oStyle["justifyfull"] = "@^";
                break;
        }

        // IE에서는 기본 정렬이 queryCommandState로 넘어오지 않아서 정렬이 없다면, 왼쪽 정렬로 가정함
        if (oStyle["justifyleft"] == "@-" && oStyle["justifycenter"] == "@-" && oStyle["justifyright"] == "@-" && oStyle["justifyfull"] == "@-") {
            oStyle["justifyleft"] = "@^";
        }

        return oStyle;
    }
});
//}
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to changing the font size using Select element
 * @name SE2M_FontSizeWithLayerUI.js
 */
nhn.husky.SE2M_FontSizeWithLayerUI = jindo.$Class({
    name: "SE2M_FontSizeWithLayerUI",

    $init: function(elAppContainer) {
        this._assignHTMLElements(elAppContainer);
    },

    _assignHTMLElements: function(elAppContainer) {
        //@ec
        this.oDropdownLayer = jindo.$$.getSingle("DIV.husky_se_fontSize_layer", elAppContainer);

        //@ec[
        this.elFontSizeLabel = jindo.$$.getSingle("SPAN.husky_se2m_current_fontSize", elAppContainer);
        this.aLIFontSizes = jindo.$A(jindo.$$("LI", this.oDropdownLayer)).filter(function(v, i, a) {
            return (v.firstChild != null);
        })._array;
        //@ec]

        this.sDefaultText = this.elFontSizeLabel.innerHTML;
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_UI_EVENT", ["fontSize", "click", "SE2M_TOGGLE_FONTSIZE_LAYER"]);
        this.oApp.exec("SE2_ATTACH_HOVER_EVENTS", [this.aLIFontSizes]);

        for (var i = 0; i < this.aLIFontSizes.length; i++) {
            this.oApp.registerBrowserEvent(this.aLIFontSizes[i], "click", "SET_FONTSIZE", [this._getFontSizeFromLI(this.aLIFontSizes[i])]);
        }
    },

    $ON_SE2M_TOGGLE_FONTSIZE_LAYER: function() {
        this.oApp.exec("TOGGLE_TOOLBAR_ACTIVE_LAYER", [this.oDropdownLayer, null, "SELECT_UI", ["fontSize"], "DESELECT_UI", ["fontSize"]]);
        this.oApp.exec('MSG_NOTIFY_CLICKCR', ['size']);
    },

    _rxPX: /px$/i,
    _rxPT: /pt$/i,

    $ON_MSG_STYLE_CHANGED: function(sAttributeName, sAttributeValue) {
        if (sAttributeName == "fontSize") {
            // [SMARTEDITORSUS-1600]
            if (this._rxPX.test(sAttributeValue)) {
                // as-is
                /*
                if(sAttributeValue.match(/px$/)){
                    var num = parseFloat(sAttributeValue.replace("px", "")).toFixed(0);
                    if(this.mapPX2PT[num]){
                        sAttributeValue = this.mapPX2PT[num] + "pt";
                    }else{
                        if(sAttributeValue > 0){
                            sAttributeValue = num + "px";
                        }else{
                            sAttributeValue = this.sDefaultText;
                        }
                    }*/

                /**
                 * Chrome의 경우,
                 * jindo.$Element().css()에서 대상 Element에 구하고자 하는 style 값이 명시되어 있지 않다면,
                 * 실제 수행되는 메서드는 window.getComputedStyle()이다.
                 *
                 * 이 메서드를 거치면 px 단위로 값을 가져오게 되는데,
                 * WYSIWYGDocument.body에 pt 단위로 값이 설정되어 있었다면
                 * px : pt = 72 : 96 의 비례에 의해
                 * 변환된 px 값을 획득하게 되며,
                 *
                 * 아래 parseFloat()의 특성 상
                 * 소수점 16자리부터는 버려질 수 있으며,
                 *
                 * 이 경우 발생할 수 있는 오차는
                 * pt 기준으로 3.75E-16 pt이다.
                 *
                 * 0.5pt 크기로 구간을 설정하였기 때문에
                 * 이 오차는 설정에 지장을 주지 않는다.
                 *
                 * 위의 기존 방식은 계산을 거치지 않을 뿐 아니라,
                 * 소수점 첫째 자리부터 무조건 반올림하기 때문에
                 * 결과에 따라 0.375 pt의 오차가 발생할 수 있었다.
                 * */
                var num = parseFloat(sAttributeValue.replace(this._rxPX, ""));
                if (num > 0) {
                    // px : pt = 72 : 96
                    sAttributeValue = num * 72 / 96 + "pt";
                } else {
                    sAttributeValue = this.sDefaultText;
                }
                // --[SMARTEDITORSUS-1600]
            }

            // [SMARTEDITORSUS-1600]
            // 산술 계산을 통해 일차적으로 pt로 변환된 값을 0.5pt 구간을 적용하여 보정하되, 보다 가까운 쪽으로 설정한다.
            if (this._rxPT.test(sAttributeValue)) {
                var num = parseFloat(sAttributeValue.replace(this._rxPT, ""));
                var integerPart = Math.floor(num); // 정수 부분
                var decimalPart = num - integerPart; // 소수 부분

                // 보정 기준은 소수 부분이며, 반올림 단위는 0.25pt
                if (decimalPart >= 0 && decimalPart < 0.25) {
                    num = integerPart + 0;
                } else if (decimalPart >= 0.25 && decimalPart < 0.75) {
                    num = integerPart + 0.5;
                } else {
                    num = integerPart + 1;
                }

                // 보정된 pt
                sAttributeValue = num + "pt";
            }
            // --[SMARTEDITORSUS-1600]

            if (!sAttributeValue) {
                sAttributeValue = this.sDefaultText;
            }
            var elLi = this._getMatchingLI(sAttributeValue);
            this._clearFontSizeSelection();
            if (elLi) {
                this.elFontSizeLabel.innerHTML = sAttributeValue;
                jindo.$Element(elLi).addClass("active");
            } else {
                this.elFontSizeLabel.innerHTML = sAttributeValue;
            }
        }
    },

    $ON_SET_FONTSIZE: function(sFontSize) {
        if (!sFontSize) {
            return;
        }

        this.oApp.exec("SET_WYSIWYG_STYLE", [{
            "fontSize": sFontSize
        }]);
        this.oApp.exec("HIDE_ACTIVE_LAYER", []);

        this.oApp.exec("CHECK_STYLE_CHANGE", []);
    },

    _getMatchingLI: function(sFontSize) {
        var elLi;

        sFontSize = sFontSize.toLowerCase();
        for (var i = 0; i < this.aLIFontSizes.length; i++) {
            elLi = this.aLIFontSizes[i];
            if (this._getFontSizeFromLI(elLi).toLowerCase() == sFontSize) {
                return elLi;
            }
        }

        return null;
    },

    _getFontSizeFromLI: function(elLi) {
        return elLi.firstChild.firstChild.style.fontSize;
    },

    _clearFontSizeSelection: function(elLi) {
        for (var i = 0; i < this.aLIFontSizes.length; i++) {
            jindo.$Element(this.aLIFontSizes[i]).removeClass("active");
        }
    }
});
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to setting/changing the line style
 * @name hp_SE_LineStyler.js
 */
nhn.husky.SE2M_LineStyler = jindo.$Class({
    name: "SE2M_LineStyler",

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["getLineStyle", jindo.$Fn(this.getLineStyle, this).bind()]);
    },

    $ON_SET_LINE_STYLE: function(sStyleName, styleValue, htOptions) {
        this.oSelection = this.oApp.getSelection();
        var nodes = this._getSelectedNodes(false);
        this.setLineStyle(sStyleName, styleValue, htOptions, nodes);

        this.oApp.exec("CHECK_STYLE_CHANGE", []);
    },

    $ON_SET_LINE_BLOCK_STYLE: function(sStyleName, styleValue, htOptions) {
        this.oSelection = this.oApp.getSelection();
        this.setLineBlockStyle(sStyleName, styleValue, htOptions);

        this.oApp.exec("CHECK_STYLE_CHANGE", []);
    },

    /**
     * SE2M_TableEditor 플러그인에 의해 선택된 TD를 SE2M_TableBlockStyler 플러그인을 통해 가져온다.
     * 선택된 TD가 없으면 Empty Array 를 반환한다.
     * @returns {Array} SE2M_TableEditor 플러그인에 의해 선택된 TD 요소 배열
     */
    _getSelectedTDs: function() {
        var htSelectedTDs = {};
        this.oApp.exec("GET_SELECTED_TD_BLOCK", ['aTdCells', htSelectedTDs]);
        return htSelectedTDs.aTdCells || [];
    },

    getLineStyle: function(sStyle) {
        var nodes = this._getSelectedNodes(false);

        var curWrapper, prevWrapper;
        var sCurStyle, sStyleValue;

        if (nodes.length === 0) {
            return null;
        }

        var iLength = nodes.length;

        if (iLength === 0) {
            sStyleValue = null;
        } else {
            prevWrapper = this._getLineWrapper(nodes[0]);
            sStyleValue = this._getWrapperLineStyle(sStyle, prevWrapper);
        }

        var firstNode = this.oSelection.getStartNode();

        if (sStyleValue != null) {
            for (var i = 1; i < iLength; i++) {
                if (this._isChildOf(nodes[i], curWrapper)) {
                    continue;
                }
                if (!nodes[i]) {
                    continue;
                }

                curWrapper = this._getLineWrapper(nodes[i]);
                if (curWrapper == prevWrapper) {
                    continue;
                }

                sCurStyle = this._getWrapperLineStyle(sStyle, curWrapper);

                if (sCurStyle != sStyleValue) {
                    sStyleValue = null;
                    break;
                }

                prevWrapper = curWrapper;
            }
        }

        curWrapper = this._getLineWrapper(nodes[iLength - 1]);

        var lastNode = this.oSelection.getEndNode();

        setTimeout(jindo.$Fn(function(firstNode, lastNode) {
            // [SMARTEDITORSUS-1606] 테이블 셀 일부가 선택되었는지 확인
            var aNodes = this._getSelectedTDs();
            if (aNodes.length > 0) {
                // [SMARTEDITORSUS-1822] 테이블 셀이 일부가 선택되었다면
                // 현재 Selection의 fisrtNode 와 lastNode 가 셀 내부에 있는지 확인하고
                // 셀 내부에 있으면 노드를 선택된 테이블 셀 노드로 교체한다.
                var elFirstTD = nhn.husky.SE2M_Utils.findAncestorByTagName("TD", firstNode);
                var elLastTD = nhn.husky.SE2M_Utils.findAncestorByTagName("TD", lastNode);
                firstNode = (elFirstTD || !firstNode) ? aNodes[0].firstChild : firstNode;
                lastNode = (elLastTD || !lastNode) ? aNodes[aNodes.length - 1].lastChild : lastNode;
            }

            this.oSelection.setEndNodes(firstNode, lastNode);
            this.oSelection.select();

            this.oApp.exec("CHECK_STYLE_CHANGE", []);
        }, this).bind(firstNode, lastNode), 0);

        return sStyleValue;
    },

    // height in percentage. For example pass 1 to set the line height to 100% and 1.5 to set it to 150%
    setLineStyle: function(sStyleName, styleValue, htOptions, nodes) {
        thisRef = this;

        var bWrapperCreated = false;

        function _setLineStyle(div, sStyleName, styleValue) {
            if (!div) {
                bWrapperCreated = true;

                // try to wrap with P first
                try {
                    div = thisRef.oSelection.surroundContentsWithNewNode("P");
                    // if the range contains a block-level tag, wrap it with a DIV
                } catch (e) {
                    div = thisRef.oSelection.surroundContentsWithNewNode("DIV");
                }
            }

            if (typeof styleValue == "function") {
                styleValue(div);
            } else {
                div.style[sStyleName] = styleValue;
            }

            if (div.childNodes.length === 0) {
                div.innerHTML = "&nbsp;";
            }

            return div;
        }

        function isInBody(node) {
            while (node && node.tagName != "BODY") {
                node = nhn.DOMFix.parentNode(node);
            }
            if (!node) {
                return false;
            }

            return true;
        }

        if (nodes.length === 0) {
            return;
        }

        var curWrapper, prevWrapper;
        var iLength = nodes.length;

        if ((!htOptions || !htOptions["bDontAddUndoHistory"])) {
            this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", ["LINE STYLE"]);
        }

        prevWrapper = this._getLineWrapper(nodes[0]);
        prevWrapper = _setLineStyle(prevWrapper, sStyleName, styleValue);

        var startNode = prevWrapper;
        var endNode = prevWrapper;

        for (var i = 1; i < iLength; i++) {
            // Skip the node if a copy of the node were wrapped and the actual node no longer exists within the document.
            try {
                if (!isInBody(nhn.DOMFix.parentNode(nodes[i]))) {
                    continue;
                }
            } catch (e) {
                continue;
            }

            if (this._isChildOf(nodes[i], curWrapper)) {
                continue;
            }

            curWrapper = this._getLineWrapper(nodes[i]);

            if (curWrapper == prevWrapper) {
                continue;
            }

            curWrapper = _setLineStyle(curWrapper, sStyleName, styleValue);

            prevWrapper = curWrapper;
        }

        endNode = curWrapper || startNode;

        if (bWrapperCreated && (!htOptions || !htOptions.bDoNotSelect)) {
            setTimeout(jindo.$Fn(function(startNode, endNode, htOptions) {
                if (startNode == endNode) {
                    this.oSelection.selectNodeContents(startNode);

                    if (startNode.childNodes.length == 1 && startNode.firstChild.tagName == "BR") {
                        this.oSelection.collapseToStart();
                    }
                } else {
                    this.oSelection.setEndNodes(startNode, endNode);
                }

                this.oSelection.select();

                if ((!htOptions || !htOptions["bDontAddUndoHistory"])) {
                    this.oApp.exec("RECORD_UNDO_AFTER_ACTION", ["LINE STYLE"]);
                }
            }, this).bind(startNode, endNode, htOptions), 0);
        }
    },

    /**
     * Block Style 적용
     */
    setLineBlockStyle: function(sStyleName, styleValue, htOptions) {
        //var aTempNodes = aTextnodes = [];
        var aTempNodes = [];
        var aTextnodes = [];
        var aNodes = this._getSelectedTDs();

        for (var j = 0; j < aNodes.length; j++) {
            this.oSelection.selectNode(aNodes[j]);
            aTempNodes = this.oSelection.getNodes();

            for (var k = 0, m = 0; k < aTempNodes.length; k++) {
                if (aTempNodes[k].nodeType == 3 || (aTempNodes[k].tagName == "BR" && k == 0)) {
                    aTextnodes[m] = aTempNodes[k];
                    m++;
                }
            }
            this.setLineStyle(sStyleName, styleValue, htOptions, aTextnodes);
            aTempNodes = aTextnodes = [];
        }
    },

    getTextNodes: function(bSplitTextEndNodes, oSelection) {
        var txtFilter = function(oNode) {
            // 편집 중에 생겨난 빈 LI/P에도 스타일 먹이도록 포함함
            // [SMARTEDITORSUS-1861] 커서홀더용 BOM문자 제외하도록 함
            if ((oNode.nodeType == 3 && oNode.nodeValue != "\n" && oNode.nodeValue != "" && oNode.nodeValue != "\uFEFF") || (oNode.tagName == "LI" && oNode.innerHTML == "") || (oNode.tagName == "P" && oNode.innerHTML == "")) {
                return true;
            } else {
                return false;
            }
        };

        return oSelection.getNodes(bSplitTextEndNodes, txtFilter);
    },

    _getSelectedNodes: function(bDontUpdate) {
        if (!bDontUpdate) {
            this.oSelection = this.oApp.getSelection();
        }

        // 페이지 최하단에 빈 LI 있을 경우 해당 LI 포함하도록 expand
        if (this.oSelection.endContainer.tagName == "LI" && this.oSelection.endOffset == 0 && this.oSelection.endContainer.innerHTML == "") {
            this.oSelection.setEndAfter(this.oSelection.endContainer);
        }

        if (this.oSelection.collapsed) {
            // [SMARTEDITORSUS-1822] SE2M_TableEditor 플러그인에 의해 선택된 TD가 없는지 확인
            // IE의 경우 SE2M_TableEditor 플러그인에 의해 TD가 선택되면 기존 selection 영역을 리셋해버리기 때문에 TD 내의 노드를 반환한다.
            var aNodes = this._getSelectedTDs();
            if (aNodes.length > 0) {
                return [aNodes[0].firstChild, aNodes[aNodes.length - 1].lastChild];
            }
            this.oSelection.selectNode(this.oSelection.commonAncestorContainer);
        }

        //var nodes = this.oSelection.getTextNodes();
        var nodes = this.getTextNodes(false, this.oSelection);

        if (nodes.length === 0) {
            var tmp = this.oSelection.getStartNode();
            if (tmp) {
                nodes[0] = tmp;
            } else {
                var elTmp = this.oSelection._document.createTextNode("\u00A0");
                this.oSelection.insertNode(elTmp);
                nodes = [elTmp];
            }
        }
        return nodes;
    },

    _getWrapperLineStyle: function(sStyle, div) {
        var sStyleValue = null;
        if (div && div.style[sStyle]) {
            sStyleValue = div.style[sStyle];
        } else {
            div = this.oSelection.commonAncesterContainer;
            while (div && !this.oSelection.rxLineBreaker.test(div.tagName)) {
                if (div && div.style[sStyle]) {
                    sStyleValue = div.style[sStyle];
                    break;
                }
                div = nhn.DOMFix.parentNode(div);
            }
        }

        return sStyleValue;
    },

    _isChildOf: function(node, container) {
        while (node && node.tagName != "BODY") {
            if (node == container) {
                return true;
            }
            node = nhn.DOMFix.parentNode(node);
        }

        return false;
    },
    _getLineWrapper: function(node) {
        var oTmpSelection = this.oApp.getEmptySelection();
        oTmpSelection.selectNode(node);
        var oLineInfo = oTmpSelection.getLineInfo();
        var oStart = oLineInfo.oStart;
        var oEnd = oLineInfo.oEnd;

        var a, b;
        var breakerA, breakerB;
        var div = null;

        a = oStart.oNode;
        breakerA = oStart.oLineBreaker;
        b = oEnd.oNode;
        breakerB = oEnd.oLineBreaker;

        this.oSelection.setEndNodes(a, b);

        if (breakerA == breakerB) {
            if (breakerA.tagName == "P" || breakerA.tagName == "DIV" || breakerA.tagName == "LI") {
                //          if(breakerA.tagName == "P" || breakerA.tagName == "DIV"){
                div = breakerA;
            } else {
                this.oSelection.setEndNodes(breakerA.firstChild, breakerA.lastChild);
            }
        }

        return div;
    }
});
//}
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to changing the lineheight using layer
 * @name hp_SE2M_LineHeightWithLayerUI.js
 */
nhn.husky.SE2M_LineHeightWithLayerUI = jindo.$Class({
    name: "SE2M_LineHeightWithLayerUI",
    MIN_LINE_HEIGHT: 50,

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_UI_EVENT", ["lineHeight", "click", "SE2M_TOGGLE_LINEHEIGHT_LAYER"]);
        this.oApp.registerLazyMessage(["SE2M_TOGGLE_LINEHEIGHT_LAYER"], ["hp_SE2M_LineHeightWithLayerUI$Lazy.js"]);
    }
});
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to changing the font color
 * @name hp_SE_FontColor.js
 */
nhn.husky.SE2M_FontColor = jindo.$Class({
    name: "SE2M_FontColor",
    rxColorPattern: /^#?[0-9a-fA-F]{6}$|^rgb\(\d+, ?\d+, ?\d+\)$/i,

    $init: function(elAppContainer) {
        this._assignHTMLElements(elAppContainer);
    },

    _assignHTMLElements: function(elAppContainer) {
        //@ec[
        this.elLastUsed = jindo.$$.getSingle("SPAN.husky_se2m_fontColor_lastUsed", elAppContainer);

        this.elDropdownLayer = jindo.$$.getSingle("DIV.husky_se2m_fontcolor_layer", elAppContainer);
        this.elPaletteHolder = jindo.$$.getSingle("DIV.husky_se2m_fontcolor_paletteHolder", this.elDropdownLayer);
        //@ec]

        this._setLastUsedFontColor("#000000");
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["getLastUsedFontColor", jindo.$Fn(this.getLastUsedFontColor, this).bind()]);
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_UI_EVENT", ["fontColorA", "click", "APPLY_LAST_USED_FONTCOLOR"]);
        this.oApp.exec("REGISTER_UI_EVENT", ["fontColorB", "click", "TOGGLE_FONTCOLOR_LAYER"]);
        this.oApp.registerLazyMessage(["APPLY_LAST_USED_FONTCOLOR", "TOGGLE_FONTCOLOR_LAYER"], ["hp_SE2M_FontColor$Lazy.js"]);
    },

    _setLastUsedFontColor: function(sFontColor) {
        this.sLastUsedColor = sFontColor;
        this.elLastUsed.style.backgroundColor = this.sLastUsedColor;
    },

    getLastUsedFontColor: function() {
        return (!!this.sLastUsedColor) ? this.sLastUsedColor : '#000000';
    }
});
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of changing the background color
 * @name hp_SE2M_BGColor.js
 */
nhn.husky.SE2M_BGColor = jindo.$Class({
    name: "SE2M_BGColor",
    rxColorPattern: /^#?[0-9a-fA-F]{6}$|^rgb\(\d+, ?\d+, ?\d+\)$/i,

    $init: function(elAppContainer) {
        this._assignHTMLElements(elAppContainer);
    },

    _assignHTMLElements: function(elAppContainer) {
        //@ec[
        this.elLastUsed = jindo.$$.getSingle("SPAN.husky_se2m_BGColor_lastUsed", elAppContainer);

        this.elDropdownLayer = jindo.$$.getSingle("DIV.husky_se2m_BGColor_layer", elAppContainer);
        this.elBGColorList = jindo.$$.getSingle("UL.husky_se2m_bgcolor_list", elAppContainer);
        this.elPaletteHolder = jindo.$$.getSingle("DIV.husky_se2m_BGColor_paletteHolder", this.elDropdownLayer);
        //@ec]

        this._setLastUsedBGColor("#777777");
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["getLastUsedBackgroundColor", jindo.$Fn(this.getLastUsedBGColor, this).bind()]);
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_UI_EVENT", ["BGColorA", "click", "APPLY_LAST_USED_BGCOLOR"]);
        this.oApp.exec("REGISTER_UI_EVENT", ["BGColorB", "click", "TOGGLE_BGCOLOR_LAYER"]);

        this.oApp.registerBrowserEvent(this.elBGColorList, "click", "EVENT_APPLY_BGCOLOR", []);
        this.oApp.registerLazyMessage(["APPLY_LAST_USED_BGCOLOR", "TOGGLE_BGCOLOR_LAYER"], ["hp_SE2M_BGColor$Lazy.js"]);
    },

    _setLastUsedBGColor: function(sBGColor) {
        this.sLastUsedColor = sBGColor;
        this.elLastUsed.style.backgroundColor = this.sLastUsedColor;
    },

    getLastUsedBGColor: function() {
        return (!!this.sLastUsedColor) ? this.sLastUsedColor : '#777777';
    }
});
//}
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to hyperlink
 * @name hp_SE_Hyperlink.js
 */
nhn.husky.SE2M_Hyperlink = jindo.$Class({
    name: "SE2M_Hyperlink",
    sATagMarker: "HTTP://HUSKY_TMP.MARKER/",

    _assignHTMLElements: function(elAppContainer) {
        this.oHyperlinkButton = jindo.$$.getSingle("li.husky_seditor_ui_hyperlink", elAppContainer);
        this.oHyperlinkLayer = jindo.$$.getSingle("div.se2_layer", this.oHyperlinkButton);
        this.oLinkInput = jindo.$$.getSingle("INPUT[type=text]", this.oHyperlinkLayer);

        this.oBtnConfirm = jindo.$$.getSingle("button.se2_apply", this.oHyperlinkLayer);
        this.oBtnCancel = jindo.$$.getSingle("button.se2_cancel", this.oHyperlinkLayer);

        //this.oCbNewWin = jindo.$$.getSingle("INPUT[type=checkbox]", this.oHyperlinkLayer) || null;
    },

    _generateAutoLink: function(sAll, sBreaker, sURL, sWWWURL, sHTTPURL) {
        sBreaker = sBreaker || "";

        var sResult;
        if (sWWWURL) {
            sResult = '<a href="http://' + sWWWURL + '">' + sURL + '</a>';
        } else {
            sResult = '<a href="' + sHTTPURL + '">' + sURL + '</a>';
        }

        return sBreaker + sResult;
    },

    /**
     * [SMARTEDITORSUS-1405] 자동링크 비활성화 옵션을 체크해서 처리한다.
     * $ON_REGISTER_CONVERTERS 메시지가 SE_EditingAreaManager.$ON_MSG_APP_READY 에서 수행되므로 먼저 처리한다.
     */
    $BEFORE_MSG_APP_READY: function() {
        var htOptions = nhn.husky.SE2M_Configuration.SE2M_Hyperlink;
        if (htOptions && htOptions.bAutolink === false) {
            // 자동링크 컨버터 비활성화
            this.$ON_REGISTER_CONVERTERS = null;
            // UI enable/disable 처리 제외
            this.$ON_DISABLE_MESSAGE = null;
            this.$ON_ENABLE_MESSAGE = null;
            // 브라우저의 자동링크기능 비활성화
            try {
                this.oApp.getWYSIWYGDocument().execCommand("AutoUrlDetect", false, false);
            } catch (e) {}
        }
    },

    $ON_MSG_APP_READY: function() {
        this.bLayerShown = false;

        this.oApp.exec("REGISTER_UI_EVENT", ["hyperlink", "click", "TOGGLE_HYPERLINK_LAYER"]);
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+k", "TOGGLE_HYPERLINK_LAYER", []]);
        this.oApp.registerLazyMessage(["TOGGLE_HYPERLINK_LAYER", "APPLY_HYPERLINK"], ["hp_SE2M_Hyperlink$Lazy.js"]);
    },

    $ON_REGISTER_CONVERTERS: function() {
        this.oApp.exec("ADD_CONVERTER_DOM", ["IR_TO_DB", jindo.$Fn(this.irToDb, this).bind()]);
    },

    $LOCAL_BEFORE_FIRST: function(sMsg) {
        if (!!sMsg.match(/(REGISTER_CONVERTERS)/)) {
            this.oApp.acceptLocalBeforeFirstAgain(this, true);
            return true;
        }

        this._assignHTMLElements(this.oApp.htOptions.elAppContainer);
        this.sRXATagMarker = this.sATagMarker.replace(/\//g, "\\/").replace(/\./g, "\\.");
        this.oApp.registerBrowserEvent(this.oBtnConfirm, "click", "APPLY_HYPERLINK");
        this.oApp.registerBrowserEvent(this.oBtnCancel, "click", "HIDE_ACTIVE_LAYER");
        this.oApp.registerBrowserEvent(this.oLinkInput, "keydown", "EVENT_HYPERLINK_KEYDOWN");
    },

    $ON_EVENT_HYPERLINK_KEYDOWN: function(oEvent) {
        if (oEvent.key().enter) {
            this.oApp.exec("APPLY_HYPERLINK");
            oEvent.stop();
        }
    },

    /**
     * [MUG-1265] 버튼이 사용불가 상태이면 자동변환기능을 막는다.
     * @see http://stackoverflow.com/questions/7556007/avoid-transformation-text-to-link-ie-contenteditable-mode
     * IE9 이전 버전은 AutoURlDetect을 사용할 수 없어 오류 발생되기 때문에, try catch로 블럭 처리(http://msdn.microsoft.com/en-us/library/aa769893%28VS.85%29.aspx)
     */
    $ON_DISABLE_MESSAGE: function(sCmd) {
        if (sCmd !== "TOGGLE_HYPERLINK_LAYER") {
            return;
        }
        try {
            this.oApp.getWYSIWYGDocument().execCommand("AutoUrlDetect", false, false);
        } catch (e) {}
        this._bDisabled = true;
    },

    /**
     * [MUG-1265] 버튼이 사용가능 상태이면 자동변환기능을 복원해준다.
     */
    $ON_ENABLE_MESSAGE: function(sCmd) {
        if (sCmd !== "TOGGLE_HYPERLINK_LAYER") {
            return;
        }
        try {
            this.oApp.getWYSIWYGDocument().execCommand("AutoUrlDetect", false, true);
        } catch (e) {}
        this._bDisabled = false;
    },

    irToDb: function(oTmpNode) {
        if (this._bDisabled) { // [MUG-1265] 버튼이 사용불가 상태이면 자동변환하지 않는다.
            return;
        }
        //저장 시점에 자동 링크를 위한 함수.
        //[SMARTEDITORSUS-1207][IE][메일] object 삽입 후 글을 저장하면 IE 브라우저가 죽어버리는 현상
        //원인 : 확인 불가. IE 저작권 관련 이슈로 추정
        //해결 : contents를 가지고 있는 div 태그를 이 함수 내부에서 복사하여 수정 후 call by reference로 넘어온 변수의 innerHTML을 변경
        var oCopyNode = oTmpNode.cloneNode(true);
        try {
            oCopyNode.innerHTML;
        } catch (e) {
            oCopyNode = jindo.$(oTmpNode.outerHTML);
        }

        var oTmpRange = this.oApp.getEmptySelection();
        var elFirstNode = oTmpRange._getFirstRealChild(oCopyNode);
        var elLastNode = oTmpRange._getLastRealChild(oCopyNode);
        var waAllNodes = jindo.$A(oTmpRange._getNodesBetween(elFirstNode, elLastNode));
        var aAllTextNodes = waAllNodes.filter(function(elNode) {
            return (elNode && elNode.nodeType === 3);
        }).$value();
        var a = aAllTextNodes;

        /*
        // 텍스트 검색이 용이 하도록 끊어진 텍스트 노드가 있으면 합쳐줌. (화면상으로 ABC라고 보이나 상황에 따라 실제 2개의 텍스트 A, BC로 이루어져 있을 수 있음. 이를 ABC 하나의 노드로 만들어 줌.)
        // 문제 발생 가능성에 비해서 퍼포먼스나 사이드 이펙트 가능성 높아 일단 주석
        var aCleanTextNodes = [];
        for(var i=0, nLen=aAllTextNodes.length; i<nLen; i++){
            if(a[i].nextSibling && a[i].nextSibling.nodeType === 3){
                a[i].nextSibling.nodeValue += a[i].nodeValue;
                a[i].parentNode.removeChild(a[i]);
            }else{
                aCleanTextNodes[aCleanTextNodes.length] = a[i];
            }
        }
        */
        var aCleanTextNodes = aAllTextNodes;

        // IE에서 PRE를 제외한 다른 태그 하위에 있는 텍스트 노드는 줄바꿈 등의 값을 변질시킴
        var elTmpDiv = this.oApp.getWYSIWYGDocument().createElement("DIV");
        var elParent, bAnchorFound;
        var sTmpStr = "@" + (new Date()).getTime() + "@";
        var rxTmpStr = new RegExp(sTmpStr, "g");
        for (var i = 0, nLen = aAllTextNodes.length; i < nLen; i++) {
            // Anchor가 이미 걸려 있는 텍스트이면 링크를 다시 걸지 않음.
            elParent = a[i].parentNode;
            bAnchorFound = false;
            while (elParent) {
                if (elParent.tagName === "A" || elParent.tagName === "PRE") {
                    bAnchorFound = true;
                    break;
                }
                elParent = elParent.parentNode;
            }
            if (bAnchorFound) {
                continue;
            }
            // www.또는 http://으로 시작하는 텍스트에 링크 걸어 줌
            // IE에서 텍스트 노드 앞쪽의 스페이스나 주석등이 사라지는 현상이 있어 sTmpStr을 앞에 붙여줌.
            elTmpDiv.innerHTML = "";

            try {
                elTmpDiv.appendChild(a[i].cloneNode(true));

                // IE에서 innerHTML를 이용 해 직접 텍스트 노드 값을 할당 할 경우 줄바꿈등이 깨질 수 있어, 텍스트 노드로 만들어서 이를 바로 append 시켜줌
                // [SMARTEDITORSUS-1649] https:// URL을 입력한 경우에도 자동링크 지원
                //elTmpDiv.innerHTML = (sTmpStr+elTmpDiv.innerHTML).replace(/(&nbsp|\s)?(((?!http:\/\/)www\.(?:(?!\&nbsp;|\s|"|').)+)|(http:\/\/(?:(?!&nbsp;|\s|"|').)+))/ig, this._generateAutoLink);
                elTmpDiv.innerHTML = (sTmpStr + elTmpDiv.innerHTML).replace(/(&nbsp|\s)?(((?!http[s]?:\/\/)www\.(?:(?!\&nbsp;|\s|"|').)+)|(http[s]?:\/\/(?:(?!&nbsp;|\s|"|').)+))/ig, this._generateAutoLink);
                // --[SMARTEDITORSUS-1649]

                // innerHTML 내에 텍스트가 있을 경우 insert 시에 주변 텍스트 노드와 합쳐지는 현상이 있어 div로 위치를 먼저 잡고 하나씩 삽입
                a[i].parentNode.insertBefore(elTmpDiv, a[i]);
                a[i].parentNode.removeChild(a[i]);
            } catch (e1) {

            }

            while (elTmpDiv.firstChild) {
                elTmpDiv.parentNode.insertBefore(elTmpDiv.firstChild, elTmpDiv);
            }
            elTmpDiv.parentNode.removeChild(elTmpDiv);
            //          alert(a[i].nodeValue);
        }
        elTmpDiv = oTmpRange = elFirstNode = elLastNode = waAllNodes = aAllTextNodes = a = aCleanTextNodes = elParent = null;
        oCopyNode.innerHTML = oCopyNode.innerHTML.replace(rxTmpStr, "");
        oTmpNode.innerHTML = oCopyNode.innerHTML;
        oCopyNode = null;
        //alert(oTmpNode.innerHTML);
    }
});
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to changing the font name using Select element
 * @name SE2M_FontNameWithLayerUI.js
 * @trigger MSG_STYLE_CHANGED,SE2M_TOGGLE_FONTNAME_LAYER
 */
nhn.husky.SE2M_FontNameWithLayerUI = jindo.$Class({
    name: "SE2M_FontNameWithLayerUI",

    $init: function(elAppContainer, aAdditionalFontList) {
        this.elLastHover = null;
        this._assignHTMLElements(elAppContainer);

        this.htBrowser = jindo.$Agent().navigator();
        this.aAdditionalFontList = aAdditionalFontList || [];
    },

    addAllFonts: function() {
        var aDefaultFontList, aFontList, htMainFont, aFontInUse, i;

        // family name -> display name 매핑 (웹폰트는 두개가 다름)
        this.htFamilyName2DisplayName = {};
        this.htAllFonts = {};

        this.aBaseFontList = [];
        this.aDefaultFontList = [];
        this.aTempSavedFontList = [];

        this.htOptions = this.oApp.htOptions.SE2M_FontName;

        if (this.htOptions) {
            aDefaultFontList = this.htOptions.aDefaultFontList || [];
            aFontList = this.htOptions.aFontList;
            htMainFont = this.htOptions.htMainFont;
            aFontInUse = this.htOptions.aFontInUse;

            //add Font
            if (this.htBrowser.ie && aFontList) {
                for (i = 0; i < aFontList.length; i++) {
                    this.addFont(aFontList[i].id, aFontList[i].name, aFontList[i].size, aFontList[i].url, aFontList[i].cssUrl);
                }
            }

            for (i = 0; i < aDefaultFontList.length; i++) {
                this.addFont(aDefaultFontList[i][0], aDefaultFontList[i][1], 0, "", "", 1);
            }

            //set Main Font
            //if(mainFontSelected=='true') {
            if (htMainFont && htMainFont.id) {
                //this.setMainFont(mainFontId, mainFontName, mainFontSize, mainFontUrl, mainFontCssUrl);
                this.setMainFont(htMainFont.id, htMainFont.name, htMainFont.size, htMainFont.url, htMainFont.cssUrl);
            }
            // add font in use
            if (this.htBrowser.ie && aFontInUse) {
                for (i = 0; i < aFontInUse.length; i++) {
                    this.addFontInUse(aFontInUse[i].id, aFontInUse[i].name, aFontInUse[i].size, aFontInUse[i].url, aFontInUse[i].cssUrl);
                }
            }
        }

        // [SMARTEDITORSUS-245] 서비스 적용 시 글꼴정보를 넘기지 않으면 기본 글꼴 목록이 보이지 않는 오류
        if (!this.htOptions || !this.htOptions.aDefaultFontList || this.htOptions.aDefaultFontList.length === 0) {
            this.addFont("돋움,Dotum", "돋움", 0, "", "", 1, null, true);
            this.addFont("돋움체,DotumChe,AppleGothic", "돋움체", 0, "", "", 1, null, true);
            this.addFont("굴림,Gulim", "굴림", 0, "", "", 1, null, true);
            this.addFont("굴림체,GulimChe", "굴림체", 0, "", "", 1, null, true);
            this.addFont("바탕,Batang,AppleMyungjo", "바탕", 0, "", "", 1, null, true);
            this.addFont("바탕체,BatangChe", "바탕체", 0, "", "", 1, null, true);
            this.addFont("궁서,Gungsuh,GungSeo", "궁서", 0, "", "", 1, null, true);
            this.addFont('Arial', 'Arial', 0, "", "", 1, "abcd", true);
            this.addFont('Tahoma', 'Tahoma', 0, "", "", 1, "abcd", true);
            this.addFont('Times New Roman', 'Times New Roman', 0, "", "", 1, "abcd", true);
            this.addFont('Verdana', 'Verdana', 0, "", "", 1, "abcd", true);
            this.addFont('Courier New', 'Courier New', 0, "", "", 1, "abcd", true);
        }

        // [SMARTEDITORSUS-1436] 글꼴 리스트에 글꼴 종류 추가하기 기능
        if (!!this.aAdditionalFontList && this.aAdditionalFontList.length > 0) {
            for (i = 0, nLen = this.aAdditionalFontList.length; i < nLen; i++) {
                this.addFont(this.aAdditionalFontList[i][0], this.aAdditionalFontList[i][1], 0, "", "", 1);
            }
        }
    },

    $ON_MSG_APP_READY: function() {
        this.bDoNotRecordUndo = false;

        this.oApp.exec("ADD_APP_PROPERTY", ["addFont", jindo.$Fn(this.addFont, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["addFontInUse", jindo.$Fn(this.addFontInUse, this).bind()]);
        // 블로그등 팩토리 폰트 포함 용
        this.oApp.exec("ADD_APP_PROPERTY", ["setMainFont", jindo.$Fn(this.setMainFont, this).bind()]);
        // 메일등 단순 폰트 지정 용
        this.oApp.exec("ADD_APP_PROPERTY", ["setDefaultFont", jindo.$Fn(this.setDefaultFont, this).bind()]);

        this.oApp.exec("REGISTER_UI_EVENT", ["fontName", "click", "SE2M_TOGGLE_FONTNAME_LAYER"]);
    },

    $AFTER_MSG_APP_READY: function() {
        this._initFontName();
        this._attachIEEvent();
    },

    _assignHTMLElements: function(elAppContainer) {
        //@ec[
        this.oDropdownLayer = jindo.$$.getSingle("DIV.husky_se_fontName_layer", elAppContainer);

        this.elFontNameLabel = jindo.$$.getSingle("SPAN.husky_se2m_current_fontName", elAppContainer);

        this.elFontNameList = jindo.$$.getSingle("UL", this.oDropdownLayer);
        this.elInnerLayer = this.elFontNameList.parentNode;
        this.elFontItemTemplate = jindo.$$.getSingle("LI", this.oDropdownLayer);
        this.aLIFontNames = jindo.$A(jindo.$$("LI", this.oDropdownLayer)).filter(function(v, i, a) {
            return (v.firstChild !== null);
        })._array;

        this.elSeparator = jindo.$$.getSingle("LI.husky_seditor_font_separator", this.oDropdownLayer);
        this.elNanumgothic = jindo.$$.getSingle("LI.husky_seditor_font_nanumgothic", this.oDropdownLayer);
        this.elNanummyeongjo = jindo.$$.getSingle("LI.husky_seditor_font_nanummyeongjo", this.oDropdownLayer);
        this.elNanumgothiccoding = jindo.$$.getSingle("LI.husky_seditor_font_nanumgothiccoding", this.oDropdownLayer);
        //@ec]

        this.sDefaultText = this.elFontNameLabel.innerHTML;
    },

    //$LOCAL_BEFORE_FIRST : function(){
    _initFontName: function() {
        this._addNanumFont();

        this.addAllFonts();

        // [SMARTEDITORSUS-1853] 폰트가 초기화되면 현재 스타일정보를 가져와서 툴바에 반영해준다.
        var oStyle;
        if (this.oApp.getCurrentStyle && (oStyle = this.oApp.getCurrentStyle())) {
            this.$ON_MSG_STYLE_CHANGED("fontFamily", oStyle.fontFamily);
        }

        this.oApp.registerBrowserEvent(this.oDropdownLayer, "mouseover", "EVENT_FONTNAME_LAYER_MOUSEOVER", []);
        this.oApp.registerBrowserEvent(this.oDropdownLayer, "click", "EVENT_FONTNAME_LAYER_CLICKED", []);
    },

    /**
     * 해당 글꼴이 존재하면 LI 요소를 보여주고 true 를 반환한다.
     * @param {Element} el 글꼴리스트의 LI 요소
     * @param {String} sFontName 확인할 글꼴이름
     * @return {Boolean} LI 요소가 있고 글꼴이 OS에 존재하면 true 반환
     */
    _checkFontLI: function(el, sFontName) {
        if (!el) {
            return false;
        }

        var bInstalled = IsInstalledFont(sFontName);
        el.style.display = bInstalled ? "block" : "none";
        return bInstalled;
    },

    _addNanumFont: function() {
        var bUseSeparator = false;

        // MacOS 에서는 한글명으로 확인이 안되므로 영문명도 같이 확인
        bUseSeparator |= this._checkFontLI(this.elNanumgothic, unescape("%uB098%uB214%uACE0%uB515") + ",NanumGothic");
        bUseSeparator |= this._checkFontLI(this.elNanummyeongjo, unescape("%uB098%uB214%uBA85%uC870") + ",NanumMyeongjo");
        bUseSeparator |= this._checkFontLI(this.elNanumgothiccoding, unescape("%uB098%uB214%uACE0%uB515%uCF54%uB529") + ",NanumGothicCoding");

        if (!!this.elSeparator) {
            this.elSeparator.style.display = bUseSeparator ? "block" : "none";
        }
    },

    _attachIEEvent: function() {
        if (!this.htBrowser.ie) {
            return;
        }

        if (this.htBrowser.nativeVersion < 9) { // [SMARTEDITORSUS-187] [< IE9] 최초 paste 시점에 웹폰트 파일을 로드
            this._wfOnPasteWYSIWYGBody = jindo.$Fn(this._onPasteWYSIWYGBody, this);
            this._wfOnPasteWYSIWYGBody.attach(this.oApp.getWYSIWYGDocument().body, "paste");

            return;
        }

        if (document.documentMode < 9) { // [SMARTEDITORSUS-169] [>= IE9] 최초 포커스 시점에 웹폰트 로드
            this._wfOnFocusWYSIWYGBody = jindo.$Fn(this._onFocusWYSIWYGBody, this);
            this._wfOnFocusWYSIWYGBody.attach(this.oApp.getWYSIWYGDocument().body, "focus");

            return;
        }

        // documentMode === 9
        // http://blogs.msdn.com/b/ie/archive/2010/08/17/ie9-opacity-and-alpha.aspx // opacity:0.0;
        this.welEditingAreaCover = jindo.$Element('<DIV style="width:100%; height:100%; position:absolute; top:0px; left:0px; z-index:1000;"></DIV>');

        this.oApp.welEditingAreaContainer.prepend(this.welEditingAreaCover);
        jindo.$Fn(this._onMouseupCover, this).attach(this.welEditingAreaCover.$value(), "mouseup");
    },

    _onFocusWYSIWYGBody: function(e) {
        this._wfOnFocusWYSIWYGBody.detach(this.oApp.getWYSIWYGDocument().body, "focus");
        this._loadAllBaseFont();
    },

    _onPasteWYSIWYGBody: function(e) {
        this._wfOnPasteWYSIWYGBody.detach(this.oApp.getWYSIWYGDocument().body, "paste");
        this._loadAllBaseFont();
    },

    _onMouseupCover: function(e) {
        e.stop();

        // [SMARTEDITORSUS-1632] 문서 모드가 9 이상일 때, 경우에 따라 this.welEditingAreaContainer가 없을 때 스크립트 오류 발생
        if (this.welEditingAreaCover) {
            this.welEditingAreaCover.leave();
        }
        //this.welEditingAreaCover.leave();
        // --[SMARTEDITORSUS-1632]

        var oMouse = e.mouse(),
            elBody = this.oApp.getWYSIWYGDocument().body,
            welBody = jindo.$Element(elBody),
            oSelection = this.oApp.getEmptySelection();

        // [SMARTEDITORSUS-363] 강제로 Selection 을 주도록 처리함
        oSelection.selectNode(elBody);
        oSelection.collapseToStart();
        oSelection.select();

        welBody.fireEvent("mousedown", {
            left: oMouse.left,
            middle: oMouse.middle,
            right: oMouse.right
        });
        welBody.fireEvent("mouseup", {
            left: oMouse.left,
            middle: oMouse.middle,
            right: oMouse.right
        });

        /**
         * [SMARTEDITORSUS-1691]
         * [IE 10-] 에디터가 초기화되고 나서 <p></p>로만 innerHTML을 설정하는데,
         * 이 경우 실제 커서는 <p></p> 내부에 있는 것이 아니라 그 앞에 위치한다.
         * 따라서 임시 북마크를 사용해서 <p></p> 내부로 커서를 이동시켜 준다.
         *
         * [SMARTEDITORSUS-1781]
         * [IE 11] 문서 모드가 Edge인 경우에 한하여
         * <p><br></p>로 innerHTML을 설정하는데,
         * 실제 커서는 <p><br></p> 앞에 위치한다.
         * 이 경우에는 임시 북마크를 삽입할 필요 없이 <br> 앞에 커서를 위치시켜 준다.
         * */
        if (this.oApp.oNavigator.ie && document.documentMode < 11 && this.oApp.getEditingMode() === "WYSIWYG") {
            if (this.oApp.getWYSIWYGDocument().body.innerHTML == "<p></p>") {
                this.oApp.getWYSIWYGDocument().body.innerHTML = '<p><span id="husky_bookmark_start_INIT"></span><span id="husky_bookmark_end_INIT"></span></p>';
                var oSelection = this.oApp.getSelection();
                oSelection.moveToStringBookmark("INIT");
                oSelection.select();
                oSelection.removeStringBookmark("INIT");
            }
        } else if (this.oApp.oNavigator.ie && this.oApp.oNavigator.nativeVersion == 11 && document.documentMode == 11 && this.oApp.getEditingMode() === "WYSIWYG") {
            if (this.oApp.getWYSIWYGDocument().body.innerHTML == "<p><br></p>") {
                var elCursorHolder_br = jindo.$$.getSingle("br", elBody);
                oSelection.setStartBefore(elCursorHolder_br);
                oSelection.setEndBefore(elCursorHolder_br);
                oSelection.select();
            }
        }
        // --[SMARTEDITORSUS-1781][SMARTEDITORSUS-1691]
    },

    $ON_EVENT_TOOLBAR_MOUSEDOWN: function() {
        if (this.htBrowser.nativeVersion < 9 || document.documentMode < 9) {
            return;
        }

        // [SMARTEDITORSUS-1632] 문서 모드가 9 이상일 때, 경우에 따라 this.welEditingAreaContainer가 없을 때 스크립트 오류 발생
        if (this.welEditingAreaCover) {
            this.welEditingAreaCover.leave();
        }
        //this.welEditingAreaCover.leave();
        // --[SMARTEDITORSUS-1632]
    },

    _loadAllBaseFont: function() {
        var i, nFontLen;

        if (!this.htBrowser.ie) {
            return;
        }

        if (this.htBrowser.nativeVersion < 9) {
            for (i = 0, nFontLen = this.aBaseFontList.length; i < nFontLen; i++) {
                this.aBaseFontList[i].loadCSS(this.oApp.getWYSIWYGDocument());
            }
        } else if (document.documentMode < 9) {
            for (i = 0, nFontLen = this.aBaseFontList.length; i < nFontLen; i++) {
                this.aBaseFontList[i].loadCSSToMenu();
            }
        }

        this._loadAllBaseFont = function() {};
    },

    _addFontToMenu: function(sDisplayName, sFontFamily, sSampleText) {
        var elItem = document.createElement("LI");
        elItem.innerHTML = this.elFontItemTemplate.innerHTML.replace("@DisplayName@", sDisplayName).replace("FontFamily", sFontFamily).replace("@SampleText@", sSampleText);
        this.elFontNameList.insertBefore(elItem, this.elFontItemTemplate);

        this.aLIFontNames[this.aLIFontNames.length] = elItem;

        if (this.aLIFontNames.length > 20) {
            this.oDropdownLayer.style.overflowX = 'hidden';
            this.oDropdownLayer.style.overflowY = 'auto';
            this.oDropdownLayer.style.height = '400px';
            this.oDropdownLayer.style.width = '204px'; // [SMARTEDITORSUS-155] 스크롤을 포함하여 206px 이 되도록 처리
        }
    },

    $ON_EVENT_FONTNAME_LAYER_MOUSEOVER: function(wev) {
        var elTmp = this._findLI(wev.element);
        if (!elTmp) {
            return;
        }

        this._clearLastHover();

        elTmp.className = "hover";
        this.elLastHover = elTmp;
    },

    $ON_EVENT_FONTNAME_LAYER_CLICKED: function(wev) {
        var elTmp = this._findLI(wev.element);
        if (!elTmp) {
            return;
        }

        var sFontFamily = this._getFontFamilyFromLI(elTmp);
        // [SMARTEDITORSUS-169] 웹폰트의 경우 fontFamily 에 ' 을 붙여주는 처리를 함
        var htFontInfo = this.htAllFonts[sFontFamily.replace(/\"/g, nhn.husky.SE2M_FontNameWithLayerUI.CUSTOM_FONT_MARKS)];
        var nDefaultFontSize;
        if (htFontInfo) {
            nDefaultFontSize = htFontInfo.defaultSize + "pt";
        } else {
            nDefaultFontSize = 0;
        }
        this.oApp.exec("SET_FONTFAMILY", [sFontFamily, nDefaultFontSize]);
    },

    _findLI: function(elTmp) {
        while (elTmp.tagName != "LI") {
            if (!elTmp || elTmp === this.oDropdownLayer) {
                return null;
            }
            elTmp = elTmp.parentNode;
        }
        if (/husky_seditor_font_separator/.test(elTmp.className)) {
            return null;
        }
        return elTmp;
    },

    _clearLastHover: function() {
        if (this.elLastHover) {
            this.elLastHover.className = "";
        }
    },

    $ON_SE2M_TOGGLE_FONTNAME_LAYER: function() {
        this.oApp.exec("TOGGLE_TOOLBAR_ACTIVE_LAYER", [this.oDropdownLayer, null, "MSG_FONTNAME_LAYER_OPENED", [], "MSG_FONTNAME_LAYER_CLOSED", []]);
        this.oApp.exec('MSG_NOTIFY_CLICKCR', ['font']);
    },

    $ON_MSG_FONTNAME_LAYER_OPENED: function() {
        this.oApp.exec("SELECT_UI", ["fontName"]);
    },

    $ON_MSG_FONTNAME_LAYER_CLOSED: function() {
        this._clearLastHover();
        this.oApp.exec("DESELECT_UI", ["fontName"]);
    },

    $ON_MSG_STYLE_CHANGED: function(sAttributeName, sAttributeValue) {
        if (sAttributeName == "fontFamily") {
            sAttributeValue = sAttributeValue.replace(/["']/g, "");
            var elLi = this._getMatchingLI(sAttributeValue);
            this._clearFontNameSelection();
            if (elLi) {
                this.elFontNameLabel.innerHTML = this._getFontNameLabelFromLI(elLi);
                jindo.$Element(elLi).addClass("active");
            } else {
                //var sDisplayName = this.htFamilyName2DisplayName[sAttributeValue] || sAttributeValue;
                var sDisplayName = this.sDefaultText;
                this.elFontNameLabel.innerHTML = sDisplayName;
            }
        }
    },

    $BEFORE_RECORD_UNDO_BEFORE_ACTION: function() {
        return !this.bDoNotRecordUndo;
    },
    $BEFORE_RECORD_UNDO_AFTER_ACTION: function() {
        return !this.bDoNotRecordUndo;
    },
    $BEFORE_RECORD_UNDO_ACTION: function() {
        return !this.bDoNotRecordUndo;
    },

    $ON_SET_FONTFAMILY: function(sFontFamily, sDefaultSize) {
        if (!sFontFamily) {
            return;
        }

        // [SMARTEDITORSUS-169] 웹폰트의 경우 fontFamily 에 ' 을 붙여주는 처리를 함
        var oFontInfo = this.htAllFonts[sFontFamily.replace(/\"/g, nhn.husky.SE2M_FontNameWithLayerUI.CUSTOM_FONT_MARKS)];
        if (!!oFontInfo) {
            oFontInfo.loadCSS(this.oApp.getWYSIWYGDocument());
        }

        // fontFamily와 fontSize 두개의 액션을 하나로 묶어서 undo history 저장
        this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", ["SET FONTFAMILY", {
            bMustBlockElement: true
        }]);
        this.bDoNotRecordUndo = true;

        if (parseInt(sDefaultSize, 10) > 0) {
            this.oApp.exec("SET_WYSIWYG_STYLE", [{
                "fontSize": sDefaultSize
            }]);
        }
        this.oApp.exec("SET_WYSIWYG_STYLE", [{
            "fontFamily": sFontFamily
        }]);

        this.bDoNotRecordUndo = false;
        this.oApp.exec("RECORD_UNDO_AFTER_ACTION", ["SET FONTFAMILY", {
            bMustBlockElement: true
        }]);

        this.oApp.exec("HIDE_ACTIVE_LAYER", []);

        this.oApp.exec("CHECK_STYLE_CHANGE", []);
    },

    _getMatchingLI: function(sFontName) {
        sFontName = sFontName.toLowerCase();
        var elLi, aFontFamily;
        for (var i = 0; i < this.aLIFontNames.length; i++) {
            elLi = this.aLIFontNames[i];
            aFontFamily = this._getFontFamilyFromLI(elLi).toLowerCase().split(",");
            for (var h = 0; h < aFontFamily.length; h++) {
                if (!!aFontFamily[h] && jindo.$S(aFontFamily[h].replace(/['"]/ig, "")).trim().$value() == sFontName) {
                    return elLi;
                }
            }
        }
        return null;
    },

    _getFontFamilyFromLI: function(elLi) {
        //return elLi.childNodes[1].innerHTML.toLowerCase();
        // <li><button type="button"><span>돋음</span>(</span><em style="font-family:'돋음',Dotum,'굴림',Gulim,Helvetica,Sans-serif;">돋음</em><span>)</span></span></button></li>
        return (elLi.getElementsByTagName("EM")[0]).style.fontFamily;
    },

    _getFontNameLabelFromLI: function(elLi) {
        return elLi.firstChild.firstChild.firstChild.nodeValue;
    },

    _clearFontNameSelection: function(elLi) {
        for (var i = 0; i < this.aLIFontNames.length; i++) {
            jindo.$Element(this.aLIFontNames[i]).removeClass("active");
        }
    },

    /**
     * Add the font to the list
     * @param fontId {String} value of font-family in style
     * @param fontName {String} name of font list in editor
     * @param defaultSize
     * @param fontURL
     * @param fontCSSURL
     * @param fontType fontType == null, custom font (sent from the server)
     *                 fontType == 1, default font
     *                 fontType == 2, tempSavedFont
     * @param sSampleText {String} sample text of font list in editor
     * @param bCheck {Boolean}
     */
    addFont: function(fontId, fontName, defaultSize, fontURL, fontCSSURL, fontType, sSampleText, bCheck) {
        // custom font feature only available in IE
        if (!this.htBrowser.ie && fontCSSURL) {
            return null;
        }

        // OS에 해당 폰트가 존재하는지 여부를 확인한다.
        if (bCheck && !IsInstalledFont(fontId)) {
            return null;
        }

        fontId = fontId.toLowerCase();

        var newFont = new fontProperty(fontId, fontName, defaultSize, fontURL, fontCSSURL);

        var sFontFamily;
        var sDisplayName;
        if (defaultSize > 0) {
            sFontFamily = fontId + "_" + defaultSize;
            sDisplayName = fontName + "_" + defaultSize;
        } else {
            sFontFamily = fontId;
            sDisplayName = fontName;
        }

        if (!fontType) {
            sFontFamily = nhn.husky.SE2M_FontNameWithLayerUI.CUSTOM_FONT_MARKS + sFontFamily + nhn.husky.SE2M_FontNameWithLayerUI.CUSTOM_FONT_MARKS;
        }

        if (this.htAllFonts[sFontFamily]) {
            return this.htAllFonts[sFontFamily];
        }
        this.htAllFonts[sFontFamily] = newFont;
        /*
                // do not add again, if the font is already in the list
                for(var i=0; i<this._allFontList.length; i++){
                    if(newFont.fontFamily == this._allFontList[i].fontFamily){
                        return this._allFontList[i];
                    }
                }

                this._allFontList[this._allFontList.length] = newFont;
        */
        // [SMARTEDITORSUS-169] [IE9] 웹폰트A 선택>웹폰트B 선택>웹폰트 A를 다시 선택하면 웹폰트 A가 적용되지 않는 문제가 발생
        //
        // [원인]
        //      - IE9의 웹폰트 로드/언로드 시점
        //          웹폰트 로드 시점: StyleSheet 의 @font-face 구문이 해석된 이후, DOM Tree 상에서 해당 웹폰트가 최초로 사용된 시점
        //          웹폰트 언로드 시점: StyleSheet 의 @font-face 구문이 해석된 이후, DOM Tree 상에서 해당 웬폰트가 더이상 사용되지 않는 시점
        //      - 메뉴 리스트에 적용되는 스타일은 @font-face 이전에 처리되는 것이어서 언로드에 영향을 미치지 않음
        //
        //      스마트에디터의 경우, 웹폰트를 선택할 때마다 SPAN 이 새로 추가되는 것이 아닌 선택된 SPAN 의 fontFamily 를 변경하여 처리하므로
        //      fontFamily 변경 후 DOM Tree 상에서 더이상 사용되지 않는 것으로 브라우저 판단하여 언로드 해버림.
        // [해결]
        //      언로드가 발생하지 않도록 메뉴 리스트에 스타일을 적용하는 것을 @font-face 이후로 하도록 처리하여 DOM Tree 상에 항상 적용될 수 있도록 함
        //
        // [SMARTEDITORSUS-969] [IE10] 웹폰트를 사용하여 글을 등록하고, 수정모드로 들어갔을 때 웹폰트가 적용되지 않는 문제
        //      - IE10에서도 웹폰트 언로드가 발생하지 않도록 조건을 수정함
        //           -> 기존 : nativeVersion === 9 && documentMode === 9
        //           -> 수정 : nativeVersion >= 9 && documentMode >= 9
        if (this.htBrowser.ie && this.htBrowser.nativeVersion >= 9 && document.documentMode >= 9) {
            newFont.loadCSSToMenu();
        }

        this.htFamilyName2DisplayName[sFontFamily] = fontName;

        sSampleText = sSampleText || this.oApp.$MSG('SE2M_FontNameWithLayerUI.sSampleText');
        this._addFontToMenu(sDisplayName, sFontFamily, sSampleText);

        if (!fontType) {
            this.aBaseFontList[this.aBaseFontList.length] = newFont;
        } else {
            if (fontType == 1) {
                this.aDefaultFontList[this.aDefaultFontList.length] = newFont;
            } else {
                this.aTempSavedFontList[this.aTempSavedFontList.length] = newFont;
            }
        }

        return newFont;
    },
    // Add the font AND load it right away
    addFontInUse: function(fontId, fontName, defaultSize, fontURL, fontCSSURL, fontType) {
        var newFont = this.addFont(fontId, fontName, defaultSize, fontURL, fontCSSURL, fontType);
        if (!newFont) {
            return null;
        }

        newFont.loadCSS(this.oApp.getWYSIWYGDocument());

        return newFont;
    },
    // Add the font AND load it right away AND THEN set it as the default font
    setMainFont: function(fontId, fontName, defaultSize, fontURL, fontCSSURL, fontType) {
        var newFont = this.addFontInUse(fontId, fontName, defaultSize, fontURL, fontCSSURL, fontType);
        if (!newFont) {
            return null;
        }

        this.setDefaultFont(newFont.fontFamily, defaultSize);

        return newFont;
    },

    setDefaultFont: function(sFontFamily, nFontSize) {
        var elBody = this.oApp.getWYSIWYGDocument().body;
        elBody.style.fontFamily = sFontFamily;
        if (nFontSize > 0) {
            elBody.style.fontSize = nFontSize + 'pt';
        }
    }
});

nhn.husky.SE2M_FontNameWithLayerUI.CUSTOM_FONT_MARKS = "'"; // [SMARTEDITORSUS-169] 웹폰트의 경우 fontFamily 에 ' 을 붙여주는 처리를 함

// property function for all fonts - including the default fonts and the custom fonts
// non-custom fonts will have the defaultSize of 0 and empty string for fontURL/fontCSSURL
function fontProperty(fontId, fontName, defaultSize, fontURL, fontCSSURL) {
        this.fontId = fontId;
        this.fontName = fontName;
        this.defaultSize = defaultSize;
        this.fontURL = fontURL;
        this.fontCSSURL = fontCSSURL;

        this.displayName = fontName;
        this.isLoaded = true;
        this.fontFamily = this.fontId;

        // it is custom font
        if (this.fontCSSURL != "") {
            this.displayName += '' + defaultSize;
            this.fontFamily += '_' + defaultSize;
            // custom fonts requires css loading
            this.isLoaded = false;

            // load the css that loads the custom font
            this.loadCSS = function(doc) {
                // if the font is loaded already, return
                if (this.isLoaded) {
                    return;
                }

                this._importCSS(doc);
                this.isLoaded = true;
            };

            // [SMARTEDITORSUS-169] [IE9]
            // addImport 후에 처음 적용된 DOM-Tree 가 iframe 내부인 경우 (setMainFont || addFontInUse 에서 호출된 경우)
            // 해당 폰트에 대한 언로드 문제가 계속 발생하여 IE9에서 addFont 에서 호출하는 loadCSS 의 경우에는 isLoaded를 true 로 변경하지 않음.
            this.loadCSSToMenu = function() {
                this._importCSS(document);
            };

            this._importCSS = function(doc) {
                var nStyleSheet = doc.styleSheets.length;
                var oStyleSheet = doc.styleSheets[nStyleSheet - 1];

                if (nStyleSheet === 0 || oStyleSheet.imports.length == 30) { // imports limit
                    // [SMARTEDITORSUS-1828] IE11에서 document.createStyleSheet API가 제거되어 createStyleSheet API 존재여부에 따라 분기처리
                    // 참고1 : http://msdn.microsoft.com/en-us/library/ie/bg182625(v=vs.85).aspx#legacyapis
                    // 참고2 : http://msdn.microsoft.com/en-us/library/ie/ms531194(v=vs.85).aspx
                    if (doc.createStyleSheet) {
                        oStyleSheet = doc.createStyleSheet();
                    } else {
                        oStyleSheet = doc.createElement("style");
                        doc.documentElement.firstChild.appendChild(oStyleSheet);
                        oStyleSheet = oStyleSheet.sheet;
                    }
                }

                oStyleSheet.addImport(this.fontCSSURL);
            };
        } else {
            this.loadCSS = function() {};
            this.loadCSSToMenu = function() {};
        }

        this.toStruct = function() {
            return {
                fontId: this.fontId,
                fontName: this.fontName,
                defaultSize: this.defaultSize,
                fontURL: this.fontURL,
                fontCSSURL: this.fontCSSURL
            };
        };
    }
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of Accessibility about SmartEditor2.
 * @name hp_SE2M_Accessibility.js
 */
nhn.husky.SE2M_Accessibility = jindo.$Class({
    name: "SE2M_Accessibility",

    /*
     * elAppContainer : mandatory
     * sLocale, sEditorType : optional
     */
    $init: function(elAppContainer, sLocale, sEditorType) {
        this._assignHTMLElements(elAppContainer);

        if (!!sLocale) {
            this.sLang = sLocale;
        }

        if (!!sEditorType) {
            this.sType = sEditorType;
        }
    },

    _assignHTMLElements: function(elAppContainer) {
        this.elHelpPopupLayer = jindo.$$.getSingle("DIV.se2_accessibility", elAppContainer);
        this.welHelpPopupLayer = jindo.$Element(this.elHelpPopupLayer);

        //close buttons
        this.oCloseButton = jindo.$$.getSingle("BUTTON.se2_close", this.elHelpPopupLayer);
        this.oCloseButton2 = jindo.$$.getSingle("BUTTON.se2_close2", this.elHelpPopupLayer);

        this.nDefaultTop = 150;

        // [SMARTEDITORSUS-1594] 포커스 탐색에 사용하기 위해 할당
        this.elAppContainer = elAppContainer;
        // --[SMARTEDITORSUS-1594]
    },

    $ON_MSG_APP_READY: function() {
        this.htAccessOption = nhn.husky.SE2M_Configuration.SE2M_Accessibility || {};
        this.oApp.exec("REGISTER_HOTKEY", ["alt+F10", "FOCUS_TOOLBAR_AREA", []]);
        this.oApp.exec("REGISTER_HOTKEY", ["alt+COMMA", "FOCUS_BEFORE_ELEMENT", []]);
        this.oApp.exec("REGISTER_HOTKEY", ["alt+PERIOD", "FOCUS_NEXT_ELEMENT", []]);

        if ((this.sType == 'basic' || this.sType == 'light') && (this.sLang != 'ko_KR')) {
            //do nothing
            return;
        } else {
            this.oApp.exec("REGISTER_HOTKEY", ["alt+0", "OPEN_HELP_POPUP", []]);

            //[SMARTEDITORSUS-1327] IE 7/8에서 ALT+0으로 팝업 띄우고 esc클릭시 팝업창 닫히게 하려면 아래 부분 꼭 필요함. (target은 document가 되어야 함!)
            this.oApp.exec("REGISTER_HOTKEY", ["esc", "CLOSE_HELP_POPUP", [], document]);
        }

        //[SMARTEDITORSUS-1353]
        if (this.htAccessOption.sTitleElementId) {
            this.oApp.registerBrowserEvent(document.getElementById(this.htAccessOption.sTitleElementId), "keydown", "MOVE_TO_EDITAREA", []);
        }
    },

    $ON_MOVE_TO_EDITAREA: function(weEvent) {
        var TAB_KEY_CODE = 9;
        if (weEvent.key().keyCode == TAB_KEY_CODE) {
            if (weEvent.key().shift) {
                return;
            }
            this.oApp.delayedExec("FOCUS", [], 0);
        }
    },

    $LOCAL_BEFORE_FIRST: function(sMsg) {
        jindo.$Fn(jindo.$Fn(this.oApp.exec, this.oApp).bind("CLOSE_HELP_POPUP", [this.oCloseButton]), this).attach(this.oCloseButton, "click");
        jindo.$Fn(jindo.$Fn(this.oApp.exec, this.oApp).bind("CLOSE_HELP_POPUP", [this.oCloseButton2]), this).attach(this.oCloseButton2, "click");

        //레이어의 이동 범위 설정.
        var elIframe = this.oApp.getWYSIWYGWindow().frameElement;
        this.htOffsetPos = jindo.$Element(elIframe).offset();
        this.nEditorWidth = elIframe.offsetWidth;

        this.htInitialPos = this.welHelpPopupLayer.offset();
        var htScrollXY = this.oApp.oUtils.getScrollXY();

        this.nLayerWidth = 590;
        this.nLayerHeight = 480;

        this.htTopLeftCorner = {
            x: parseInt(this.htOffsetPos.left, 10),
            y: parseInt(this.htOffsetPos.top, 10)
        };
        //[css markup] left:11 top:74로 되어 있음
    },

    /**
     * [SMARTEDITORSUS-1594]
     * SE2M_Configuration_General에서 포커스를 이동할 에디터 영역 이후의 엘레먼트를 지정해 두었다면, 설정값을 따른다.
     * 지정하지 않았거나 빈 String이라면, elAppContainer를 기준으로 자동 탐색한다.
     * */
    $ON_FOCUS_NEXT_ELEMENT: function() {
        // 포커스 캐싱
        this._currentNextFocusElement = null; // 새로운 포커스 이동이 발생할 때마다 캐싱 초기화

        if (this.htAccessOption.sNextElementId) {
            this._currentNextFocusElement = document.getElementById(this.htAccessOption.sNextElementId);
        } else {
            this._currentNextFocusElement = this._findNextFocusElement(this.elAppContainer);
        }

        if (this._currentNextFocusElement) {
            window.focus(); // [SMARTEDITORSUS-1360] IE7에서는 element에 대한 focus를 주기 위해 선행되어야 한다.
            this._currentNextFocusElement.focus();
        } else if (parent && parent.nhn && parent.nhn.husky && parent.nhn.husky.EZCreator && parent.nhn.husky.EZCreator.elIFrame) {
            parent.focus();
            if (this._currentNextFocusElement = this._findNextFocusElement(parent.nhn.husky.EZCreator.elIFrame)) {
                this._currentNextFocusElement.focus();
            }
        }
    },

    /**
     * [SMARTEDITORSUS-1594] DIV#smart_editor2 다음 요소에서 가장 가까운 포커스용 태그를 탐색
     * */
    _findNextFocusElement: function(targetElement) {
        var target = null;

        var el = targetElement.nextSibling;

        while (el) {
            if (el.nodeType !== 1) { // Element Node만을 대상으로 한다.
                // 대상 노드 대신 nextSibling을 찾되, 부모를 거슬러 올라갈 수도 있다.
                // document.body까지 거슬러 올라가게 되면 탐색 종료
                el = this._switchToSiblingOrNothing(el);
                if (!el) {
                    break;
                } else {
                    continue;
                }
            }

            // 대상 노드를 기준으로, 전위순회로 조건에 부합하는 노드 탐색
            this._recursivePreorderTraversalFilter(el, this._isFocusTag);

            if (this._nextFocusElement) {
                target = this._nextFocusElement;

                // 탐색에 사용했던 변수 초기화
                this._bStopFindingNextElement = false;
                this._nextFocusElement = null;

                break;
            } else {
                // 대상 노드 대신 nextSibling을 찾되, 부모를 거슬러 올라갈 수도 있다.
                // document.body까지 거슬러 올라가게 되면 탐색 종료
                el = this._switchToSiblingOrNothing(el);
                if (!el) {
                    break;
                }
            }
        }

        // target이 존재하지 않으면 null 반환
        return target;
    },

    /**
     * [SMARTEDITORSUS-1594] 대상 노드를 기준으로 하여, nextSibling 또는 previousSibling을 찾는다.
     * nextSibling 또는 previousSibling이 없다면,
     * 부모를 거슬러 올라가면서 첫 nextSibling 또는 previousSibling을 찾는다.
     * document의 body까지 올라가도 nextSibling 또는 previousSibling이 나타나지 않는다면
     * 탐색 대상으로 null을 반환한다.
     * @param {NodeElement} 대상 노드 (주의:NodeElement에 대한 null 체크 안함)
     * @param {Boolean} 생략하거나 false이면 nextSibling을 찾고, true이면 previousSibling을 찾는다.
     * */
    _switchToSiblingOrNothing: function(targetElement, isPreviousOrdered) {
        var el = targetElement;

        if (isPreviousOrdered) {
            if (el.previousSibling) {
                el = el.previousSibling;
            } else {
                // 형제가 없다면 부모를 거슬러 올라가면서 탐색

                // 이 루프의 종료 조건
                // 1. 부모를 거슬러 올라가다가 el이 document.body가 되는 시점
                // - 더 이상 previousSibling을 탐색할 수 없음
                // 2. el이 부모로 대체된 뒤 previousSibling이 존재하는 경우
                while (el.nodeName.toUpperCase() != "BODY" && !el.previousSibling) {
                    el = el.parentNode;
                }

                if (el.nodeName.toUpperCase() == "BODY") {
                    el = null;
                } else {
                    el = el.previousSibling;
                }
            }
        } else {
            if (el.nextSibling) {
                el = el.nextSibling;
            } else {
                // 형제가 없다면 부모를 거슬러 올라가면서 탐색

                // 이 루프의 종료 조건
                // 1. 부모를 거슬러 올라가다가 el이 document.body가 되는 시점
                // - 더 이상 nextSibling을 탐색할 수 없음
                // 2. el이 부모로 대체된 뒤 nextSibling이 존재하는 경우
                while (el.nodeName.toUpperCase() != "BODY" && !el.nextSibling) {
                    el = el.parentNode;
                }

                if (el.nodeName.toUpperCase() == "BODY") {
                    el = null;
                } else {
                    el = el.nextSibling;
                }
            }
        }

        return el;
    },

    /**
     * [SMARTEDITORSUS-1594] 대상 노드를 기준으로 하는 트리를 전위순회를 거쳐, 필터 조건에 부합하는 첫 노드를 찾는다.
     * @param {NodeElement} 탐색하려는 트리의 루트 노드
     * @param {Function} 필터 조건으로 사용할 함수
     * @param {Boolean} 생략하거나 false이면 순수 전위순회(루트 - 좌측 - 우측 순)로 탐색하고, true이면 반대 방향의 전위순회(루트 - 우측 - 좌측)로 탐색한다.
     * */
    _recursivePreorderTraversalFilter: function(node, filterFunction, isReversed) {
        var self = this;

        // 현재 노드를 기준으로 필터링
        var _bStopFindingNextElement = filterFunction.apply(node);

        if (_bStopFindingNextElement) {
            // 최초로 포커스 태그를 찾는다면 탐색 중단용 flag 변경
            self._bStopFindingNextElement = true;

            if (isReversed) {
                self._previousFocusElement = node;
            } else {
                self._nextFocusElement = node;
            }

            return;
        } else {
            // 필터링 조건에 부합하지 않는다면, 자식들을 기준으로 반복하게 된다.
            if (isReversed) {
                for (var len = node.childNodes.length, i = len - 1; i >= 0; i--) {
                    self._recursivePreorderTraversalFilter(node.childNodes[i], filterFunction, true);
                    if (!!this._bStopFindingNextElement) {
                        break;
                    }
                }
            } else {
                for (var i = 0, len = node.childNodes.length; i < len; i++) {
                    self._recursivePreorderTraversalFilter(node.childNodes[i], filterFunction);
                    if (!!this._bStopFindingNextElement) {
                        break;
                    }
                }
            }
        }
    },

    /**
     * [SMARTEDITORSUS-1594] 필터 함수로, 이 노드가 tab 키로 포커스를 이동하는 태그에 해당하는지 확인한다.
     * */
    _isFocusTag: function() {
        var self = this;

        // tab 키로 포커스를 잡아주는 태그 목록
        var aFocusTagViaTabKey = ["A", "BUTTON", "INPUT", "TEXTAREA"];

        // 포커스 태그가 현재 노드에 존재하는지 확인하기 위한 flag
        var bFocusTagExists = false;

        for (var i = 0, len = aFocusTagViaTabKey.length; i < len; i++) {
            if (self.nodeType === 1 && self.nodeName && self.nodeName.toUpperCase() == aFocusTagViaTabKey[i] && !self.disabled && jindo.$Element(self).visible()) {
                bFocusTagExists = true;
                break;
            }
        }

        return bFocusTagExists;
    },

    /**
     * [SMARTEDITORSUS-1594]
     * SE2M_Configuration_General에서 포커스를 이동할 에디터 영역 이전의 엘레먼트를 지정해 두었다면, 설정값을 따른다.
     * 지정하지 않았거나 빈 String이라면, elAppContainer를 기준으로 자동 탐색한다.
     * */
    $ON_FOCUS_BEFORE_ELEMENT: function() {
        // 포커스 캐싱
        this._currentPreviousFocusElement = null; // 새로운 포커스 이동이 발생할 때마다 캐싱 초기화

        if (this.htAccessOption.sBeforeElementId) {
            this._currentPreviousFocusElement = document.getElementById(this.htAccessOption.sBeforeElementId);
        } else {
            this._currentPreviousFocusElement = this._findPreviousFocusElement(this.elAppContainer); // 삽입될 대상
        }

        if (this._currentPreviousFocusElement) {
            window.focus(); // [SMARTEDITORSUS-1360] IE7에서는 element에 대한 focus를 주기 위해 선행되어야 한다.
            this._currentPreviousFocusElement.focus();
        } else if (parent && parent.nhn && parent.nhn.husky && parent.nhn.husky.EZCreator && parent.nhn.husky.EZCreator.elIFrame) {
            parent.focus();
            if (this._currentPreviousFocusElement = this._findPreviousFocusElement(parent.nhn.husky.EZCreator.elIFrame)) {
                this._currentPreviousFocusElement.focus();
            }
        }
    },

    /**
     * [SMARTEDITORSUS-1594] DIV#smart_editor2 이전 요소에서 가장 가까운 포커스용 태그를 탐색
     * */
    _findPreviousFocusElement: function(targetElement) {
        var target = null;

        var el = targetElement.previousSibling;

        while (el) {
            if (el.nodeType !== 1) { // Element Node만을 대상으로 한다.
                // 대상 노드 대신 previousSibling을 찾되, 부모를 거슬러 올라갈 수도 있다.
                // document.body까지 거슬러 올라가게 되면 탐색 종료
                el = this._switchToSiblingOrNothing(el, /*isReversed*/ true);
                if (!el) {
                    break;
                } else {
                    continue;
                }
            }

            // 대상 노드를 기준으로, 역 전위순회로 조건에 부합하는 노드 탐색
            this._recursivePreorderTraversalFilter(el, this._isFocusTag, true);

            if (this._previousFocusElement) {
                target = this._previousFocusElement;

                // 탐색에 사용했던 변수 초기화
                this._bStopFindingNextElement = false;
                this._previousFocusElement = null;

                break;
            } else {
                // 대상 노드 대신 previousSibling을 찾되, 부모를 거슬러 올라갈 수도 있다.
                // document.body까지 거슬러 올라가게 되면 탐색 종료
                el = this._switchToSiblingOrNothing(el, /*isReversed*/ true);
                if (!el) {
                    break;
                }
            }
        }

        // target이 존재하지 않으면 null 반환
        return target;
    },

    $ON_FOCUS_TOOLBAR_AREA: function() {
        this.oButton = jindo.$$.getSingle("BUTTON.se2_font_family", this.elAppContainer);
        if (this.oButton && !this.oButton.disabled) { // [SMARTEDITORSUS-1369] IE9이하에서 disabled 요소에 포커스를 주면 오류 발생
            window.focus();
            this.oButton.focus();
        }
    },

    $ON_OPEN_HELP_POPUP: function() {
        this.oApp.exec("DISABLE_ALL_UI", [{
            aExceptions: ["se2_accessibility"]
        }]);
        this.oApp.exec("SHOW_EDITING_AREA_COVER");
        this.oApp.exec("SELECT_UI", ["se2_accessibility"]);

        //아래 코드 없어야 블로그에서도 동일한 위치에 팝업 뜸..
        //this.elHelpPopupLayer.style.top = this.nDefaultTop+"px";

        this.nCalcX = this.htTopLeftCorner.x + this.oApp.getEditingAreaWidth() - this.nLayerWidth;
        this.nCalcY = this.htTopLeftCorner.y - 30; // 블로그버전이 아닌 경우 에디터영역을 벗어나는 문제가 있기 때문에 기본툴바(30px) 크기만큼 올려줌

        this.oApp.exec("SHOW_DIALOG_LAYER", [this.elHelpPopupLayer, {
            elHandle: this.elTitle,
            nMinX: this.htTopLeftCorner.x + 0,
            nMinY: this.nDefaultTop + 77,
            nMaxX: this.nCalcX,
            nMaxY: this.nCalcY
        }]);

        // offset (nTop:Numeric,  nLeft:Numeric)
        this.welHelpPopupLayer.offset(this.nCalcY, (this.nCalcX) / 2);

        //[SMARTEDITORSUS-1327] IE에서 포커스 이슈로 IE에 대해서만 window.focus실행함.
        if (jindo.$Agent().navigator().ie) {
            window.focus();
        }

        var self = this;
        setTimeout(function() {
            try {
                self.oCloseButton2.focus();
            } catch (e) {}
        }, 200);
    },

    $ON_CLOSE_HELP_POPUP: function() {
        this.oApp.exec("ENABLE_ALL_UI"); // 모든 UI 활성화.
        this.oApp.exec("DESELECT_UI", ["helpPopup"]);
        this.oApp.exec("HIDE_ALL_DIALOG_LAYER", []);
        this.oApp.exec("HIDE_EDITING_AREA_COVER"); // 편집 영역 활성화.

        this.oApp.exec("FOCUS");
    }
});
//}
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to inserting special characters
 * @name hp_SE2M_SCharacter.js
 * @required HuskyRangeManager
 */
nhn.husky.SE2M_SCharacter = jindo.$Class({
    name: "SE2M_SCharacter",

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_UI_EVENT", ["sCharacter", "click", "TOGGLE_SCHARACTER_LAYER"]);
        this.oApp.registerLazyMessage(["TOGGLE_SCHARACTER_LAYER"], ["hp_SE2M_SCharacter$Lazy.js"]);
    }
});
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to Find/Replace
 * @name hp_SE2M_FindReplacePlugin.js
 */
nhn.husky.SE2M_FindReplacePlugin = jindo.$Class({
    name: "SE2M_FindReplacePlugin",
    oEditingWindow: null,
    oFindReplace: null,
    bFindMode: true,
    bLayerShown: false,

    $init: function() {
        this.nDefaultTop = 20;
    },

    $ON_MSG_APP_READY: function() {
        // the right document will be available only when the src is completely loaded
        this.oEditingWindow = this.oApp.getWYSIWYGWindow();
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+f", "SHOW_FIND_LAYER", []]);
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+h", "SHOW_REPLACE_LAYER", []]);

        this.oApp.exec("REGISTER_UI_EVENT", ["findAndReplace", "click", "TOGGLE_FIND_REPLACE_LAYER"]);
        this.oApp.registerLazyMessage(["TOGGLE_FIND_REPLACE_LAYER", "SHOW_FIND_LAYER", "SHOW_REPLACE_LAYER", "SHOW_FIND_REPLACE_LAYER"], ["hp_SE2M_FindReplacePlugin$Lazy.js", "N_FindReplace.js"]);
    },

    $ON_SHOW_ACTIVE_LAYER: function() {
        this.oApp.exec("HIDE_DIALOG_LAYER", [this.elDropdownLayer]);
    }
});
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to quote
 * @name hp_SE_Quote.js
 * @required SE_EditingArea_WYSIWYG
 */
nhn.husky.SE2M_Quote = jindo.$Class({
    name: "SE2M_Quote",

    htQuoteStyles_view: null,

    $init: function() {
        var htConfig = nhn.husky.SE2M_Configuration.Quote || {};
        var sImageBaseURL = htConfig.sImageBaseURL;

        this.nMaxLevel = htConfig.nMaxLevel || 14;

        this.htQuoteStyles_view = {};
        this.htQuoteStyles_view["se2_quote1"] = "_zoom:1;padding:0 8px; margin:0 0 30px 20px; margin-right:15px; border-left:2px solid #cccccc;color:#888888;";
        this.htQuoteStyles_view["se2_quote2"] = "_zoom:1;margin:0 0 30px 13px;padding:0 8px 0 16px;background:url(" + sImageBaseURL + "/bg_quote2.gif) 0 3px no-repeat;color:#888888;";
        this.htQuoteStyles_view["se2_quote3"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:1px dashed #cccccc;color:#888888;";
        this.htQuoteStyles_view["se2_quote4"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:1px dashed #66b246;color:#888888;";
        this.htQuoteStyles_view["se2_quote5"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:1px dashed #cccccc;background:url(" + sImageBaseURL + "/bg_b1.png) repeat;_background:none;_filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + sImageBaseURL + "/bg_b1.png',sizingMethod='scale');color:#888888;";
        this.htQuoteStyles_view["se2_quote6"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:1px solid #e5e5e5;color:#888888;";
        this.htQuoteStyles_view["se2_quote7"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:1px solid #66b246;color:#888888;";
        this.htQuoteStyles_view["se2_quote8"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:1px solid #e5e5e5;background:url(" + sImageBaseURL + "/bg_b1.png) repeat;_background:none;_filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + sImageBaseURL + "/bg_b1.png',sizingMethod='scale');color:#888888;";
        this.htQuoteStyles_view["se2_quote9"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:2px solid #e5e5e5;color:#888888;";
        this.htQuoteStyles_view["se2_quote10"] = "_zoom:1;margin:0 0 30px 0;padding:10px;border:2px solid #e5e5e5;background:url(" + sImageBaseURL + "/bg_b1.png) repeat;_background:none;_filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + sImageBaseURL + "/bg_b1.png',sizingMethod='scale');color:#888888;";
    },

    _assignHTMLElements: function() {
        //@ec
        this.elDropdownLayer = jindo.$$.getSingle("DIV.husky_seditor_blockquote_layer", this.oApp.htOptions.elAppContainer);
        this.aLI = jindo.$$("LI", this.elDropdownLayer);
    },

    $ON_REGISTER_CONVERTERS: function() {
        this.oApp.exec("ADD_CONVERTER", ["DB_TO_IR", jindo.$Fn(function(sContents) {
            sContents = sContents.replace(/<(blockquote)[^>]*class=['"]?(se2_quote[0-9]+)['"]?[^>]*>/gi, "<$1 class=$2>");
            return sContents;
        }, this).bind()]);

        this.oApp.exec("ADD_CONVERTER", ["IR_TO_DB", jindo.$Fn(function(sContents) {
            var htQuoteStyles_view = this.htQuoteStyles_view;
            sContents = sContents.replace(/<(blockquote)[^>]*class=['"]?(se2_quote[0-9]+)['"]?[^>]*>/gi, function(sAll, sTag, sClassName) {
                return '<' + sTag + ' class=' + sClassName + ' style="' + htQuoteStyles_view[sClassName] + '">';
            });
            return sContents;
        }, this).bind()]);

        this.htSE1toSE2Map = {
            "01": "1",
            "02": "2",
            "03": "6",
            "04": "8",
            "05": "9",
            "07": "3",
            "08": "5"
        };
        // convert SE1's quotes to SE2's
        // -> 블로그 개발 쪽에서 처리 하기로 함.
        /*
        this.oApp.exec("ADD_CONVERTER", ["DB_TO_IR", jindo.$Fn(function(sContents){
            return sContents.replace(/<blockquote[^>]* class="?vview_quote([0-9]+)"?[^>]*>((?:\s|.)*?)<\/blockquote>/ig, jindo.$Fn(function(m0,sQuoteType,sQuoteContents){
                if (/<!--quote_txt-->((?:\s|.)*?)<!--\/quote_txt-->/ig.test(sQuoteContents)){
                    if(!this.htSE1toSE2Map[sQuoteType]){
                        return m0;
                    }

                    return '<blockquote class="se2_quote'+this.htSE1toSE2Map[sQuoteType]+'">'+RegExp.$1+'</blockquote>';
                }else{
                    return '';
                }
            }, this).bind());
        }, this).bind()]);
        */
    },

    $LOCAL_BEFORE_FIRST: function() {
        this._assignHTMLElements();

        this.oApp.registerBrowserEvent(this.elDropdownLayer, "click", "EVENT_SE2_BLOCKQUOTE_LAYER_CLICK", []);
        this.oApp.delayedExec("SE2_ATTACH_HOVER_EVENTS", [this.aLI], 0);
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_UI_EVENT", ["quote", "click", "TOGGLE_BLOCKQUOTE_LAYER"]);
        this.oApp.registerLazyMessage(["TOGGLE_BLOCKQUOTE_LAYER"], ["hp_SE2M_Quote$Lazy.js"]);
    },

    // [SMARTEDITORSUS-209] 인용구 내에 내용이 없을 때 Backspace 로 인용구가 삭제되도록 처리
    $ON_EVENT_EDITING_AREA_KEYDOWN: function(weEvent) {
        var oSelection,
            elParentQuote;

        if ('WYSIWYG' !== this.oApp.getEditingMode()) {
            return;
        }

        if (8 !== weEvent.key().keyCode) {
            return;
        }

        oSelection = this.oApp.getSelection();
        oSelection.fixCommonAncestorContainer();
        elParentQuote = this._findParentQuote(oSelection.commonAncestorContainer);

        if (!elParentQuote) {
            return;
        }

        if (this._isBlankQuote(elParentQuote)) {
            weEvent.stop(jindo.$Event.CANCEL_DEFAULT);

            oSelection.selectNode(elParentQuote);
            oSelection.collapseToStart();

            jindo.$Element(elParentQuote).leave();

            oSelection.select();
        }
    },

    // [SMARTEDITORSUS-215] Delete 로 인용구 뒤의 P 가 제거되지 않도록 처리
    $ON_EVENT_EDITING_AREA_KEYUP: function(weEvent) {
        var oSelection,
            elParentQuote,
            oP;

        if ('WYSIWYG' !== this.oApp.getEditingMode()) {
            return;
        }

        if (46 !== weEvent.key().keyCode) {
            return;
        }

        oSelection = this.oApp.getSelection();
        oSelection.fixCommonAncestorContainer();
        elParentQuote = this._findParentQuote(oSelection.commonAncestorContainer);

        if (!elParentQuote) {
            return false;
        }

        if (!elParentQuote.nextSibling) {
            weEvent.stop(jindo.$Event.CANCEL_DEFAULT);

            oP = oSelection._document.createElement("P");
            oP.innerHTML = "&nbsp;";

            jindo.$Element(elParentQuote).after(oP);

            setTimeout(jindo.$Fn(function(oSelection) {
                var sBookmarkID = oSelection.placeStringBookmark();

                oSelection.select();
                oSelection.removeStringBookmark(sBookmarkID);
            }, this).bind(oSelection), 0);
        }
    },

    _isBlankQuote: function(elParentQuote) {
        var elChild,
            aChildNodes,
            i, nLen,
            bChrome = this.oApp.oNavigator.chrome,
            bSafari = this.oApp.oNavigator.safari,
            isBlankText = function(sText) {
                sText = sText.replace(/[\r\n]/ig, '').replace(unescape("%uFEFF"), '');

                if (sText === "") {
                    return true;
                }

                if (sText === "&nbsp;" || sText === " ") { // [SMARTEDITORSUS-479]
                    return true;
                }

                return false;
            },
            isBlank = function(oNode) {
                if (oNode.nodeType === 3 && isBlankText(oNode.nodeValue)) {
                    return true;
                }

                if ((oNode.tagName === "P" || oNode.tagName === "SPAN") &&
                    (isBlankText(oNode.innerHTML) || oNode.innerHTML === "<br>")) {
                    return true;
                }

                return false;
            },
            isBlankTable = function(oNode) {
                if ((jindo.$$("tr", oNode)).length === 0) {
                    return true;
                }

                return false;
            };

        if (isBlankText(elParentQuote.innerHTML) || elParentQuote.innerHTML === "<br>") {
            return true;
        }

        if (bChrome || bSafari) { // [SMARTEDITORSUS-352], [SMARTEDITORSUS-502]
            var aTable = jindo.$$("TABLE", elParentQuote),
                nTable = aTable.length,
                elTable;

            for (i = 0; i < nTable; i++) {
                elTable = aTable[i];

                if (isBlankTable(elTable)) {
                    jindo.$Element(elTable).leave();
                }
            }
        }

        aChildNodes = elParentQuote.childNodes;

        for (i = 0, nLen = aChildNodes.length; i < nLen; i++) {
            elChild = aChildNodes[i];

            if (!isBlank(elChild)) {
                return false;
            }
        }

        return true;
    },

    _findParentQuote: function(el) {
        return this._findAncestor(jindo.$Fn(function(elNode) {
            if (!elNode) {
                return false;
            }
            if (elNode.tagName !== "BLOCKQUOTE") {
                return false;
            }
            if (!elNode.className) {
                return false;
            }

            var sClassName = elNode.className;
            if (!this.htQuoteStyles_view[sClassName]) {
                return false;
            }

            return true;
        }, this).bind(), el);
    },

    _findAncestor: function(fnCondition, elNode) {
        while (elNode && !fnCondition(elNode)) {
            elNode = elNode.parentNode;
        }

        return elNode;
    }
});
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to table creation
 * @name hp_SE_Table.js
 */
nhn.husky.SE2M_TableCreator = jindo.$Class({
    name: "SE2M_TableCreator",

    _sSETblClass: "__se_tbl",

    nRows: 3,
    nColumns: 4,
    nBorderSize: 1,
    sBorderColor: "#000000",
    sBGColor: "#000000",

    nBorderStyleIdx: 3,
    nTableStyleIdx: 1,

    nMinRows: 1,
    nMaxRows: 20,
    nMinColumns: 1,
    nMaxColumns: 20,
    nMinBorderWidth: 1,
    nMaxBorderWidth: 10,

    rxLastDigits: null,
    sReEditGuideMsg_table: null,

    // 테두리 스타일 목록
    // 표 스타일 스타일 목록
    oSelection: null,

    $ON_MSG_APP_READY: function() {
        this.sReEditGuideMsg_table = this.oApp.$MSG(nhn.husky.SE2M_Configuration.SE2M_ReEditAction.aReEditGuideMsg[3]);
        this.oApp.exec("REGISTER_UI_EVENT", ["table", "click", "TOGGLE_TABLE_LAYER"]);
        this.oApp.registerLazyMessage(["TOGGLE_TABLE_LAYER"], ["hp_SE2M_TableCreator$Lazy.js"]);
    },

    // [SMARTEDITORSUS-365] 테이블퀵에디터 > 속성 직접입력 > 테두리 스타일
    //      - 테두리 없음을 선택하는 경우 본문에 삽입하는 표에 가이드 라인을 표시해 줍니다. 보기 시에는 테두리가 보이지 않습니다.
    $ON_REGISTER_CONVERTERS: function() {
        this.oApp.exec("ADD_CONVERTER_DOM", ["IR_TO_DB", jindo.$Fn(this.irToDbDOM, this).bind()]);
        this.oApp.exec("ADD_CONVERTER_DOM", ["DB_TO_IR", jindo.$Fn(this.dbToIrDOM, this).bind()]);
    },

    irToDbDOM: function(oTmpNode) {
        /**
         *  저장을 위한 Table Tag 는 아래와 같이 변경됩니다.
         *  (1) <TABLE>
         *          <table border="1" cellpadding="0" cellspacing="0" style="border:1px dashed #c7c7c7; border-left:0; border-bottom:0;" attr_no_border_tbl="1" class="__se_tbl">
         *      --> <table border="0" cellpadding="1" cellspacing="0" attr_no_border_tbl="1" class="__se_tbl">
         *  (2) <TD>
         *          <td style="border:1px dashed #c7c7c7; border-top:0; border-right:0; background-color:#ffef00" width="245"><p>&nbsp;</p></td>
         *      --> <td style="background-color:#ffef00" width="245">&nbsp;</td>
         */
        var aNoBorderTable = [];
        var aTables = jindo.$$('table[class=__se_tbl]', oTmpNode, {
            oneTimeOffCache: true
        });

        // 테두리가 없음 속성의 table (임의로 추가한 attr_no_border_tbl 속성이 있는 table 을 찾음)
        jindo.$A(aTables).forEach(function(oValue, nIdx, oArray) {
            if (jindo.$Element(oValue).attr("attr_no_border_tbl")) {
                aNoBorderTable.push(oValue);
            }
        }, this);

        if (aNoBorderTable.length < 1) {
            return;
        }

        // [SMARTEDITORSUS-410] 글 저장 시, 테두리 없음 속성을 선택할 때 임의로 표시한 가이드 라인 property 만 style 에서 제거해 준다.
        // <TABLE> 과 <TD> 의 속성값을 변경 및 제거
        var aTDs = [],
            oTable;
        for (var i = 0, nCount = aNoBorderTable.length; i < nCount; i++) {
            oTable = aNoBorderTable[i];

            // <TABLE> 에서 border, cellpadding 속성값 변경, style property 제거
            jindo.$Element(oTable).css({
                "border": "",
                "borderLeft": "",
                "borderBottom": ""
            });
            jindo.$Element(oTable).attr({
                "border": 0,
                "cellpadding": 1
            });

            // <TD> 에서는 background-color 를 제외한 style 을 모두 제거
            aTDs = jindo.$$('tbody>tr>td', oTable);
            jindo.$A(aTDs).forEach(function(oTD, nIdx, oTDArray) {
                jindo.$Element(oTD).css({
                    "border": "",
                    "borderTop": "",
                    "borderRight": ""
                });
            });
        }
    },

    dbToIrDOM: function(oTmpNode) {
        /**
         *  수정을 위한 Table Tag 는 아래와 같이 변경됩니다.
         *  (1) <TABLE>
         *          <table border="0" cellpadding="1" cellspacing="0" attr_no_border_tbl="1" class="__se_tbl">
         *      --> <table border="1" cellpadding="0" cellspacing="0" style="border:1px dashed #c7c7c7; border-left:0; border-bottom:0;" attr_no_border_tbl="1" class="__se_tbl">
         *  (2) <TD>
         *          <td style="background-color:#ffef00" width="245">&nbsp;</td>
         *      --> <td style="border:1px dashed #c7c7c7; border-top:0; border-right:0; background-color:#ffef00" width="245"><p>&nbsp;</p></td>
         */
        var aNoBorderTable = [];
        var aTables = jindo.$$('table[class=__se_tbl]', oTmpNode, {
            oneTimeOffCache: true
        });

        // 테두리가 없음 속성의 table (임의로 추가한 attr_no_border_tbl 속성이 있는 table 을 찾음)
        jindo.$A(aTables).forEach(function(oValue, nIdx, oArray) {
            if (jindo.$Element(oValue).attr("attr_no_border_tbl")) {
                aNoBorderTable.push(oValue);
            }
        }, this);

        if (aNoBorderTable.length < 1) {
            return;
        }

        // <TABLE> 과 <TD> 의 속성값을 변경/추가
        var aTDs = [],
            oTable;
        for (var i = 0, nCount = aNoBorderTable.length; i < nCount; i++) {
            oTable = aNoBorderTable[i];

            // <TABLE> 에서 border, cellpadding 속성값 변경/ style 속성 추가
            jindo.$Element(oTable).css({
                "border": "1px dashed #c7c7c7",
                "borderLeft": 0,
                "borderBottom": 0
            });
            jindo.$Element(oTable).attr({
                "border": 1,
                "cellpadding": 0
            });

            // <TD> 에서 style 속성값 추가
            aTDs = jindo.$$('tbody>tr>td', oTable);
            jindo.$A(aTDs).forEach(function(oTD, nIdx, oTDArray) {
                jindo.$Element(oTD).css({
                    "border": "1px dashed #c7c7c7",
                    "borderTop": 0,
                    "borderRight": 0
                });
            });
        }
    }
});
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to changing the font style in the table.
 * @requires SE2M_TableEditor.js
 * @name SE2M_TableBlockManager
 */
nhn.husky.SE2M_TableBlockStyler = jindo.$Class({
    name: "SE2M_TableBlockStyler",
    nSelectedTD: 0,
    htSelectedTD: {},
    aTdRange: [],

    $init: function() {},

    $LOCAL_BEFORE_ALL: function() {
        return (this.oApp.getEditingMode() == "WYSIWYG");
    },

    $ON_MSG_APP_READY: function() {
        this.oDocument = this.oApp.getWYSIWYGDocument();
    },

    $ON_EVENT_EDITING_AREA_MOUSEUP: function(wevE) {
        if (this.oApp.getEditingMode() != "WYSIWYG") return;
        this.setTdBlock();
    },

    /**
     * selected Area가 td block인지 체크하는 함수.
     */
    $ON_IS_SELECTED_TD_BLOCK: function(sAttr, oReturn) {
        if (this.nSelectedTD > 0) {
            oReturn[sAttr] = true;
            return oReturn[sAttr];
        } else {
            oReturn[sAttr] = false;
            return oReturn[sAttr];
        }
    },

    /**
     *
     */
    $ON_GET_SELECTED_TD_BLOCK: function(sAttr, oReturn) {
        //use : this.oApp.exec("GET_SELECTED_TD_BLOCK",['aCells',this.htSelectedTD]);
        oReturn[sAttr] = this.htSelectedTD.aTdCells;
    },

    setTdBlock: function() {
        this.oApp.exec("GET_SELECTED_CELLS", ['aTdCells', this.htSelectedTD]); //tableEditor로 부터 얻어온다.
        var aNodes = this.htSelectedTD.aTdCells;
        if (aNodes) {
            this.nSelectedTD = aNodes.length;
        }
    },

    $ON_DELETE_BLOCK_CONTENTS: function() {
        var self = this,
            welParent, oBlockNode, oChildNode;

        this.setTdBlock();
        for (var j = 0; j < this.nSelectedTD; j++) {
            jindo.$Element(this.htSelectedTD.aTdCells[j]).child(function(elChild) {

                welParent = jindo.$Element(elChild._element.parentNode);
                welParent.remove(elChild);

                oBlockNode = self.oDocument.createElement('P');

                if (jindo.$Agent().navigator().firefox) {
                    oChildNode = self.oDocument.createElement('BR');
                } else {
                    oChildNode = self.oDocument.createTextNode('\u00A0');
                }

                oBlockNode.appendChild(oChildNode);
                welParent.append(oBlockNode);
            }, 1);
        }
    }

});
//{
/**
 * @fileOverview This file contains Husky plugin with test handlers
 * @name hp_SE2M_StyleRemover.js
 */
nhn.husky.SE2M_StyleRemover = jindo.$Class({
    name: "SE2M_StyleRemover",

    $ON_MSG_APP_READY: function() {
        this.oApp.exec("REGISTER_UI_EVENT", ["styleRemover", "click", "CHOOSE_REMOVE_STYLE", []]);
    },

    $LOCAL_BEFORE_FIRST: function() {
        // The plugin may be used in view and when it is used there, EditingAreaManager plugin is not loaded.
        // So, get the document from the selection instead of EditingAreaManager.
        this.oHuskyRange = this.oApp.getEmptySelection();
        this._document = this.oHuskyRange._document;
    },

    $ON_CHOOSE_REMOVE_STYLE: function(oSelection) {
        var bSelectedBlock = false;
        var htSelectedTDs = {};
        this.oApp.exec("IS_SELECTED_TD_BLOCK", ['bIsSelectedTd', htSelectedTDs]);
        bSelectedBlock = htSelectedTDs.bIsSelectedTd;

        this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", ["REMOVE STYLE", {
            bMustBlockElement: true
        }]);

        if (bSelectedBlock) {
            this.oApp.exec("REMOVE_STYLE_IN_BLOCK", []);
        } else {
            this.oApp.exec("REMOVE_STYLE", []);
        }

        this.oApp.exec("RECORD_UNDO_AFTER_ACTION", ["REMOVE STYLE", {
            bMustBlockElement: true
        }]);

        this.oApp.exec('MSG_NOTIFY_CLICKCR', ['noeffect']);
    },

    $ON_REMOVE_STYLE_IN_BLOCK: function(oSelection) {
        var htSelectedTDs = {};
        this.oSelection = this.oApp.getSelection();
        this.oApp.exec("GET_SELECTED_TD_BLOCK", ['aTdCells', htSelectedTDs]);
        var aNodes = htSelectedTDs.aTdCells;

        for (var j = 0; j < aNodes.length; j++) {
            this.oSelection.selectNodeContents(aNodes[j]);
            this.oSelection.select();
            this.oApp.exec("REMOVE_STYLE", []);
        }
    },

    $ON_REMOVE_STYLE: function(oSelection) {
        if (!oSelection || !oSelection.commonAncestorContainer) {
            oSelection = this.oApp.getSelection();
        }

        if (oSelection.collapsed) {
            return;
        }

        oSelection.expandBothEnds();

        var sBookmarkID = oSelection.placeStringBookmark();
        var aNodes = oSelection.getNodes(true);

        this._removeStyle(aNodes);
        oSelection.moveToBookmark(sBookmarkID);

        var aNodes = oSelection.getNodes(true);
        for (var i = 0; i < aNodes.length; i++) {
            var oNode = aNodes[i];

            if (oNode.style && oNode.tagName != "BR" && oNode.tagName != "TD" && oNode.tagName != "TR" && oNode.tagName != "TBODY" && oNode.tagName != "TABLE") {
                oNode.removeAttribute("align");
                oNode.removeAttribute("style");
                if ((jindo.$Element(oNode).css("display") == "inline" && oNode.tagName != "IMG" && oNode.tagName != "IFRAME") && (!oNode.firstChild || oSelection._isBlankTextNode(oNode.firstChild))) {
                    oNode.parentNode.removeChild(oNode);
                }
            }
        }

        oSelection.moveToBookmark(sBookmarkID);

        // [SMARTEDITORSUS-1750] 스타일제거를 위해 selection을 확장(oSelection.expandBothEnds)하면 TR까지 확장되는데 IE10에서만 execCommand 가 제대로 동작하지 않는 문제가 발생하기 때문에 확장전 selection으로 복원하도록 수정
        // [SMARTEDITORSUS-1893] 테이블밖에서는 마지막라인이 풀리는 이슈가 발생하여 commonAncestorContainer가 TBODY 인 경우에만 selection을 복원하도록 제한
        if (oSelection.commonAncestorContainer.tagName === "TBODY") {
            oSelection = this.oApp.getSelection();
        }
        oSelection.select();

        // use a custom removeStringBookmark here as the string bookmark could've been cloned and there are some additional cases that need to be considered

        // remove start marker
        var oMarker = this._document.getElementById(oSelection.HUSKY_BOOMARK_START_ID_PREFIX + sBookmarkID);
        while (oMarker) {
            oParent = nhn.DOMFix.parentNode(oMarker);
            oParent.removeChild(oMarker);
            while (oParent && (!oParent.firstChild || (!oParent.firstChild.nextSibling && oSelection._isBlankTextNode(oParent.firstChild)))) {
                var oNextParent = oParent.parentNode;
                oParent.parentNode.removeChild(oParent);
                oParent = oNextParent;
            }
            oMarker = this._document.getElementById(oSelection.HUSKY_BOOMARK_START_ID_PREFIX + sBookmarkID);
        }

        // remove end marker
        var oMarker = this._document.getElementById(oSelection.HUSKY_BOOMARK_END_ID_PREFIX + sBookmarkID);
        while (oMarker) {
            oParent = nhn.DOMFix.parentNode(oMarker);
            oParent.removeChild(oMarker);
            while (oParent && (!oParent.firstChild || (!oParent.firstChild.nextSibling && oSelection._isBlankTextNode(oParent.firstChild)))) {
                var oNextParent = oParent.parentNode;
                oParent.parentNode.removeChild(oParent);
                oParent = oNextParent;
            }
            oMarker = this._document.getElementById(oSelection.HUSKY_BOOMARK_END_ID_PREFIX + sBookmarkID);
        }

        this.oApp.exec("CHECK_STYLE_CHANGE");
    },

    $ON_REMOVE_STYLE2: function(aNodes) {
        this._removeStyle(aNodes);
    },

    $ON_REMOVE_STYLE_AND_PASTE_HTML: function(sHtml, bNoUndo) {
        var htBrowser,
            elDivHolder,
            elFirstTD,
            aNodesInSelection,
            oSelection;

        htBrowser = jindo.$Agent().navigator();

        if (!sHtml) {
            return false;
        }
        if (this.oApp.getEditingMode() != "WYSIWYG") {
            this.oApp.exec("CHANGE_EDITING_MODE", ["WYSIWYG"]);
        }

        if (!bNoUndo) {
            this.oApp.exec("RECORD_UNDO_BEFORE_ACTION", ["REMOVE STYLE AND PASTE HTML"]);
        }

        oSelection = this.oApp.getSelection();
        oSelection.deleteContents(); // remove select node - for dummy image, reedit object

        // If the table were inserted within a styled(strikethough & etc) paragraph, the table may inherit the style in IE.
        elDivHolder = this.oApp.getWYSIWYGDocument().createElement("DIV");
        oSelection.insertNode(elDivHolder);

        if (!!htBrowser.webkit) {
            elDivHolder.innerHTML = "&nbsp;"; // for browser bug! - summary reiteration
        }

        oSelection.selectNode(elDivHolder);
        this.oApp.exec("REMOVE_STYLE", [oSelection]);

        //[SMARTEDITORSUS-181][IE9] 표나 요약글 등의 테이블에서 > 테이블 외부로 커서 이동 불가
        if (htBrowser.ie) {
            sHtml += "<p>&nbsp;</p>";
        } else if (htBrowser.firefox) {
            //[SMARTEDITORSUS-477][개별블로그](파폭특정)포스트쓰기>요약글을 삽입 후 요약글 아래 임의의 본문영역에 마우스 클릭 시 커서가 요약안에 노출됩니다.
            // 본문에 table만 있는 경우, 커서가 밖으로 못나오는 현상이 있음.FF버그임.
            sHtml += "<p>﻿<br></p>";
        }

        oSelection.selectNode(elDivHolder);
        oSelection.pasteHTML(sHtml);

        //Table인경우, 커서를 테이블 첫 TD에 넣기 위한 작업.
        aNodesInSelection = oSelection.getNodes() || [];
        for (var i = 0; i < aNodesInSelection.length; i++) {
            if (!!aNodesInSelection[i].tagName && aNodesInSelection[i].tagName.toLowerCase() == 'td') {
                elFirstTD = aNodesInSelection[i];
                oSelection.selectNodeContents(elFirstTD.firstChild || elFirstTD);
                oSelection.collapseToStart();
                oSelection.select();
                break;
            }
        }

        oSelection.collapseToEnd(); //파란색 커버 제거.
        oSelection.select();
        this.oApp.exec("FOCUS");
        if (!elDivHolder) { // 임시 div 삭제.
            elDivHolder.parentNode.removeChild(elDivHolder);
        }

        if (!bNoUndo) {
            this.oApp.exec("RECORD_UNDO_AFTER_ACTION", ["REMOVE STYLE AND PASTE HTML"]);
        }
    },

    _removeStyle: function(aNodes) {
        var arNodes = jindo.$A(aNodes);
        for (var i = 0; i < aNodes.length; i++) {
            var oNode = aNodes[i];

            // oNode had been removed from the document already
            if (!oNode || !oNode.parentNode || !oNode.parentNode.tagName) {
                continue;
            }

            var bDontSplit = false;
            // If oNode is direct child of a block level node, don't do anything. (should not move up the hierarchy anymore)
            if (jindo.$Element(oNode.parentNode).css("display") != "inline") {
                continue;
            }

            var parent = oNode.parentNode;

            // do not proceed if oNode is not completely selected
            if (oNode.firstChild) {
                if (arNodes.indexOf(this.oHuskyRange._getVeryLastRealChild(oNode)) == -1) {
                    continue;
                }
                if (arNodes.indexOf(this.oHuskyRange._getVeryFirstRealChild(oNode)) == -1) {
                    continue;
                }
            }

            // Case 1: oNode is the right most node
            //
            // If oNode were C(right most node) from
            //   H
            //   |
            //   P
            // / | \
            // A B C
            //
            // and B and C were selected, bring up all the (selected) left siblings to the right of the parent and and make it
            //   H
            // / | \
            // P B C
            // |
            // A
            // ===========================================================
            // If A, B and C were selected from
            //   H
            //   |
            //   P
            // / | \
            // A B C
            //
            // append them to the right of the parent and make it
            //    H
            // / | | \
            // P A B C
            //
            // and then remove P as it's got no child and make it
            //   H
            // / | \
            // A B C
            if (!oNode.nextSibling) {
                i--;
                var tmp = oNode;
                // bring up left siblings
                while (tmp) {
                    var prevNode = tmp.previousSibling;
                    parent.parentNode.insertBefore(tmp, parent.nextSibling);
                    if (!prevNode) {
                        break;
                    }
                    if (arNodes.indexOf(this._getVeryFirst(prevNode)) == -1) {
                        break;
                    }
                    tmp = prevNode;
                }

                // remove the parent if it's got no child now
                if (parent.childNodes.length === 0) {
                    parent.parentNode.removeChild(parent);
                }

                continue;
            }

            // Case 2: oNode's got a right sibling that is included in the selection
            //
            // if the next sibling is included in the selection, stop current iteration
            // -> current node will be handled in the next iteration
            if (arNodes.indexOf(this._getVeryLast(oNode.nextSibling)) != -1) {
                continue;
            }

            // Since the case
            // 1. oNode is the right most node
            // 2. oNode's got a right sibling that is included in the selection
            // were all taken care of above, so from here we just need take care of the case when oNode is NOT the right most node and oNode's right sibling is NOT included in the selection

            // Case 3: the rest
            // When all of the left siblings were selected, take all the left siblings and current node and append them to the left of the parent node.
            //    H
            //    |
            //    P
            // / | | \
            // A B C D
            // -> if A, B and C were selected, then make it
            //    H
            // / | | \
            // A B C P
            //         |
            //         D
            i--;
            // bring up selected prev siblings
            if (arNodes.indexOf(this._getVeryFirst(oNode.parentNode)) != -1) {
                // move
                var tmp = oNode;
                var lastInserted = parent;
                while (tmp) {
                    var prevNode = tmp.previousSibling;
                    parent.parentNode.insertBefore(tmp, lastInserted);
                    lastInserted = tmp;

                    if (!prevNode) {
                        break;
                    }
                    tmp = prevNode;
                }
                if (parent.childNodes.length === 0) {
                    parent.parentNode.removeChild(parent);
                }
                // When NOT all of the left siblings were selected, split the parent node and insert the selected nodes in between.
                //    H
                //    |
                //    P
                // / | | \
                // A B C D
                // -> if B and C were selected, then make it
                //    H
                // / | | \
                // P B C P
                // |      |
                // A      D
            } else {
                //split
                if (bDontSplit) {
                    i++;
                    continue;
                }

                var oContainer = this._document.createElement("SPAN");
                var tmp = oNode;
                parent.insertBefore(oContainer, tmp.nextSibling);
                while (tmp) {
                    var prevNode = tmp.previousSibling;
                    oContainer.insertBefore(tmp, oContainer.firstChild);

                    if (!prevNode) {
                        break;
                    }
                    if (arNodes.indexOf(this._getVeryFirst(prevNode)) == -1) {
                        break;
                    }
                    tmp = prevNode;
                }

                this._splitAndAppendAtTop(oContainer);
                while (oContainer.firstChild) {
                    oContainer.parentNode.insertBefore(oContainer.firstChild, oContainer);
                }
                oContainer.parentNode.removeChild(oContainer);
            }
        }
    },

    _splitAndAppendAtTop: function(oSpliter) {
        var targetNode = oSpliter;
        var oTmp = targetNode;
        var oCopy = oTmp;

        while (jindo.$Element(oTmp.parentNode).css("display") == "inline") {
            var oNode = oTmp.parentNode.cloneNode(false);

            while (oTmp.nextSibling) {
                oNode.appendChild(oTmp.nextSibling);
            }

            oTmp = oTmp.parentNode;

            oNode.insertBefore(oCopy, oNode.firstChild);
            oCopy = oNode;
        }

        oTop = oTmp.parentNode;
        oTop.insertBefore(targetNode, oTmp.nextSibling);
        oTop.insertBefore(oCopy, targetNode.nextSibling);
    },

    _getVeryFirst: function(oNode) {
        if (!oNode) {
            return null;
        }

        if (oNode.firstChild) {
            return this.oHuskyRange._getVeryFirstRealChild(oNode);
        } else {
            return oNode;
        }
    },

    _getVeryLast: function(oNode) {
        if (!oNode) {
            return null;
        }

        if (oNode.lastChild) {
            return this.oHuskyRange._getVeryLastRealChild(oNode);
        } else {
            return oNode;
        }
    }
});
//}
nhn.husky.SE2M_TableEditor = jindo.$Class({
    name: "SE2M_TableEditor",

    _sSETblClass: "__se_tbl",
    _sSEReviewTblClass: "__se_tbl_review",

    STATUS: {
        S_0: 1, // neither cell selection nor cell resizing is active
        MOUSEDOWN_CELL: 2, // mouse down on a table cell
        CELL_SELECTING: 3, // cell selection is in progress
        CELL_SELECTED: 4, // cell selection was (completely) made
        MOUSEOVER_BORDER: 5, // mouse is over a table/cell border and the cell resizing grip is shown
        MOUSEDOWN_BORDER: 6 // mouse down on the cell resizing grip (cell resizing is in progress)
    },

    CELL_SELECTION_CLASS: "se2_te_selection",

    MIN_CELL_WIDTH: 5,
    MIN_CELL_HEIGHT: 5,

    TMP_BGC_ATTR: "_se2_tmp_te_bgc",
    TMP_BGIMG_ATTR: "_se2_tmp_te_bg_img",
    ATTR_TBL_TEMPLATE: "_se2_tbl_template",

    nStatus: 1,
    nMouseEventsStatus: 0,

    aSelectedCells: [],

    $ON_REGISTER_CONVERTERS: function() {
        // remove the cell selection class
        this.oApp.exec("ADD_CONVERTER_DOM", ["WYSIWYG_TO_IR", jindo.$Fn(function(elTmpNode) {
            if (this.aSelectedCells.length < 1) {
                //return sContents;
                return;
            }

            var aCells;
            var aCellType = ["TD", "TH"];

            for (var n = 0; n < aCellType.length; n++) {
                aCells = elTmpNode.getElementsByTagName(aCellType[n]);
                for (var i = 0, nLen = aCells.length; i < nLen; i++) {
                    if (aCells[i].className) {
                        aCells[i].className = aCells[i].className.replace(this.CELL_SELECTION_CLASS, "");
                        if (aCells[i].getAttribute(this.TMP_BGC_ATTR)) {
                            aCells[i].style.backgroundColor = aCells[i].getAttribute(this.TMP_BGC_ATTR);
                            aCells[i].removeAttribute(this.TMP_BGC_ATTR);
                        } else if (aCells[i].getAttribute(this.TMP_BGIMG_ATTR)) {
                            jindo.$Element(this.aCells[i]).css("backgroundImage", aCells[i].getAttribute(this.TMP_BGIMG_ATTR));
                            aCells[i].removeAttribute(this.TMP_BGIMG_ATTR);
                        }
                    }
                }
            }

            //          this.wfnMouseDown.attach(this.elResizeCover, "mousedown");

            //          return elTmpNode.innerHTML;
            //          var rxSelectionColor = new RegExp("<(TH|TD)[^>]*)("+this.TMP_BGC_ATTR+"=[^> ]*)([^>]*>)", "gi");
        }, this).bind()]);
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.registerLazyMessage(["EVENT_EDITING_AREA_MOUSEMOVE", "STYLE_TABLE"], ["hp_SE2M_TableEditor$Lazy.js", "SE2M_TableTemplate.js"]);
    }
});
/**
 * @name SE2M_QuickEditor_Common
 * @class
 * @description Quick Editor Common function Class
 * @author NHN AjaxUI Lab - mixed
 * @version 1.0
 * @since 2009.09.29
 * */
nhn.husky.SE2M_QuickEditor_Common = jindo.$Class({
    /**
     * class 이름
     * @type {String}
     */
    name: "SE2M_QuickEditor_Common",
    /**
     * 환경 정보.
     * @type {Object}
     */
    _environmentData: "",
    /**
     * 현재 타입 (table|img)
     * @type {String}
     */
    _currentType: "",
    /**
     * 이벤트가 레이어 안에서 호출되었는지 알기 위한 변수
     * @type {Boolean}
     */
    _in_event: false,
    /**
     * Ajax처리를 하지 않음
     * @type {Boolean}
     */
    _bUseConfig: true,

    /**
     * 공통 서버에서 개인 설정 받아오는 AjaxUrl
     * @See SE2M_Configuration.js
     */
    _sBaseAjaxUrl: "",
    _sAddTextAjaxUrl: "",

    /**
     * 초기 인스턴스 생성 실행되는 함수.
     */
    $init: function() {
        this.waHotkeys = new jindo.$A([]);
        this.waHotkeyLayers = new jindo.$A([]);
    },

    $ON_MSG_APP_READY: function() {
        var htConfiguration = nhn.husky.SE2M_Configuration.QuickEditor;

        if (htConfiguration) {
            this._bUseConfig = (!!htConfiguration.common && typeof htConfiguration.common.bUseConfig !== "undefined") ? htConfiguration.common.bUseConfig : true;
        }

        if (!this._bUseConfig) {
            this.setData("{table:'full',img:'full',review:'full'}");
        } else {
            this._sBaseAjaxUrl = htConfiguration.common.sBaseAjaxUrl;
            this._sAddTextAjaxUrl = htConfiguration.common.sAddTextAjaxUrl;

            this.getData();
        }
        this.oApp.registerLazyMessage(["OPEN_QE_LAYER"], ["hp_SE2M_QuickEditor_Common$Lazy.js"]);
    },

    //삭제 시에 qe layer close
    $ON_EVENT_EDITING_AREA_KEYDOWN: function(oEvent) {
        var oKeyInfo = oEvent.key();
        //Backspace : 8, Delete :46
        if (oKeyInfo.keyCode == 8 || oKeyInfo.keyCode == 46) {
            // [SMARTEDITORSUS-1213][IE9, 10] 사진 삭제 후 zindex 1000인 div가 잔존하는데, 그 위로 썸네일 drag를 시도하다 보니 drop이 불가능.
            var htBrowser = jindo.$Agent().navigator();
            if (htBrowser.ie && htBrowser.nativeVersion > 8) {
                var elFirstChild = jindo.$$.getSingle("DIV.husky_seditor_editing_area_container").childNodes[0];
                if ((elFirstChild.tagName == "DIV") && (elFirstChild.style.zIndex == 1000)) {
                    elFirstChild.parentNode.removeChild(elFirstChild);
                }
            }
            // --[SMARTEDITORSUS-1213]
            this.oApp.exec("CLOSE_QE_LAYER", [oEvent]);
        }
    },

    getData: function() {
        var self = this;
        jindo.$Ajax(self._sBaseAjaxUrl, {
            type: "jsonp",
            timeout: 1,
            onload: function(rp) {
                var result = rp.json().result;
                // [SMARTEDITORSUS-1028][SMARTEDITORSUS-1517] QuickEditor 설정 API 개선
                //if (!!result && !!result.length) {
                if (!!result && !!result.text_data) {
                    //self.setData(result[result.length - 1]);
                    self.setData(result.text_data);
                } else {
                    self.setData("{table:'full',img:'full',review:'full'}");
                }
                // --[SMARTEDITORSUS-1028][SMARTEDITORSUS-1517]
            },

            onerror: function() {
                self.setData("{table:'full',img:'full',review:'full'}");
            },

            ontimeout: function() {
                self.setData("{table:'full',img:'full',review:'full'}");
            }
        }).request({
            text_key: "qeditor_fold"
        });
    },

    setData: function(sResult) {
        var oResult = {
            table: "full",
            img: "full",
            review: "full"
        };

        if (sResult) {
            oResult = eval("(" + sResult + ")");
        }

        this._environmentData = {
            table: {
                isOpen: false,
                type: oResult["table"], //full,fold,
                isFixed: false,
                position: []
            },
            img: {
                isOpen: false,
                type: oResult["img"], //full,fold
                isFixed: false
            },
            review: {
                isOpen: false,
                type: oResult["review"], //full,fold
                isFixed: false,
                position: []
            }
        };


        this.waTableTagNames = jindo.$A(["table", "tbody", "td", "tfoot", "th", "thead", "tr"]);
    },

    /**
     * 위지윅 영역에 단축키가 등록될 때,
     * tab 과 shift+tab (들여쓰기 / 내어쓰기 ) 를 제외한 단축키 리스트를 저장한다.
     */
    $ON_REGISTER_HOTKEY: function(sHotkey, sCMD, aArgs) {
        if (sHotkey != "tab" && sHotkey != "shift+tab") {
            this.waHotkeys.push([sHotkey, sCMD, aArgs]);
        }
    }
});
/**
 * @classDescription shortcut
 * @author AjaxUI Lab - mixed
 */

function Shortcut(sKey, sId) {
    var sKey = sKey.replace(/\s+/g, "");
    var store = Shortcut.Store;
    var action = Shortcut.Action;
    if (typeof sId === "undefined" && sKey.constructor == String) {
        store.set("document", sKey, document);
        return action.init(store.get("document"), sKey);
    } else if (sId.constructor == String && sKey.constructor == String) {
        store.set(sId, sKey, jindo.$(sId));
        return action.init(store.get(sId), sKey);
    } else if (sId.constructor != String && sKey.constructor == String) {
        var fakeId = "nonID" + new Date().getTime();
        fakeId = Shortcut.Store.searchId(fakeId, sId);
        store.set(fakeId, sKey, sId);
        return action.init(store.get(fakeId), sKey);
    }
    alert(sId + "는 반드시 string이거나  없어야 됩니다.");
};


Shortcut.Store = {
    anthorKeyHash: {},
    datas: {},
    currentId: "",
    currentKey: "",
    searchId: function(sId, oElement) {
        jindo.$H(this.datas).forEach(function(oValue, sKey) {
            if (oElement == oValue.element) {
                sId = sKey;
                jindo.$H.Break();
            }
        });
        return sId;
    },
    set: function(sId, sKey, oElement) {
        this.currentId = sId;
        this.currentKey = sKey;
        var idData = this.get(sId);
        this.datas[sId] = idData ? idData.createKey(sKey) : new Shortcut.Data(sId, sKey, oElement);
    },
    get: function(sId, sKey) {
        if (sKey) {
            return this.datas[sId].keys[sKey];
        } else {
            return this.datas[sId];
        }
    },
    reset: function(sId) {
        var data = this.datas[sId];
        Shortcut.Helper.bind(data.func, data.element, "detach");

        delete this.datas[sId];
    },
    allReset: function() {
        jindo.$H(this.datas).forEach(jindo.$Fn(function(value, key) {
            this.reset(key);
        }, this).bind());
    }
};

Shortcut.Data = jindo.$Class({
    $init: function(sId, sKey, oElement) {
        this.id = sId;
        this.element = oElement;
        this.func = jindo.$Fn(this.fire, this).bind();

        Shortcut.Helper.bind(this.func, oElement, "attach");
        this.keys = {};
        this.keyStemp = {};
        this.createKey(sKey);
    },
    createKey: function(sKey) {
        this.keyStemp[Shortcut.Helper.keyInterpretor(sKey)] = sKey;
        this.keys[sKey] = {};
        var data = this.keys[sKey];
        data.key = sKey;
        data.events = [];
        data.commonExceptions = [];
        //      data.keyAnalysis = Shortcut.Helper.keyInterpretor(sKey);
        data.stopDefalutBehavior = true;

        return this;
    },
    getKeyStamp: function(eEvent) {


        var sKey = eEvent.keyCode || eEvent.charCode;
        var returnVal = "";

        returnVal += eEvent.altKey ? "1" : "0";
        returnVal += eEvent.ctrlKey ? "1" : "0";
        returnVal += eEvent.metaKey ? "1" : "0";
        returnVal += eEvent.shiftKey ? "1" : "0";
        returnVal += sKey;
        return returnVal;
    },
    fire: function(eEvent) {
        eEvent = eEvent || window.eEvent;

        var oMatchKeyData = this.keyStemp[this.getKeyStamp(eEvent)];

        if (oMatchKeyData) {
            this.excute(new jindo.$Event(eEvent), oMatchKeyData);
        }

    },
    excute: function(weEvent, sRawKey) {
        var isExcute = true;
        var staticFun = Shortcut.Helper;
        var data = this.keys[sRawKey];

        if (staticFun.notCommonException(weEvent, data.commonExceptions)) {
            jindo.$A(data.events).forEach(function(v) {
                if (data.stopDefalutBehavior) {
                    var leng = v.exceptions.length;
                    if (leng) {
                        for (var i = 0; i < leng; i++) {
                            if (!v.exception[i](weEvent)) {
                                isExcute = false;
                                break;
                            }
                        }
                        if (isExcute) {
                            v.event(weEvent);
                            if (jindo.$Agent().navigator().ie) {
                                var e = weEvent._event;
                                e.keyCode = "";
                                e.charCode = "";
                            }
                            weEvent.stop();
                        } else {
                            jindo.$A.Break();
                        }
                    } else {
                        v.event(weEvent);
                        if (jindo.$Agent().navigator().ie) {
                            var e = weEvent._event;
                            e.keyCode = "";
                            e.charCode = "";
                        }
                        weEvent.stop();
                    }
                }
            });
        }
    },
    addEvent: function(fpEvent, sRawKey) {
        var events = this.keys[sRawKey].events;
        if (!Shortcut.Helper.hasEvent(fpEvent, events)) {
            events.push({
                event: fpEvent,
                exceptions: []
            });
        };
    },
    addException: function(fpException, sRawKey) {
        var commonExceptions = this.keys[sRawKey].commonExceptions;
        if (!Shortcut.Helper.hasException(fpException, commonExceptions)) {
            commonExceptions.push(fpException);
        };
    },
    removeException: function(fpException, sRawKey) {
        var commonExceptions = this.keys[sRawKey].commonExceptions;
        commonExceptions = jindo.$A(commonExceptions).filter(function(exception) {
            return exception != fpException;
        }).$value();
    },
    removeEvent: function(fpEvent, sRawKey) {
        var events = this.keys[sRawKey].events;
        events = jindo.$A(events).filter(function(event) {
            return event != fpEvent;
        }).$value();
        this.unRegister(sRawKey);
    },
    unRegister: function(sRawKey) {
        var aEvents = this.keys[sRawKey].events;

        if (aEvents.length)
            delete this.keys[sRawKey];

        var hasNotKey = true;
        for (var i in this.keys) {
            hasNotKey = false;
            break;
        }

        if (hasNotKey) {
            Shortcut.Helper.bind(this.func, this.element, "detach");
            delete Shortcut.Store.datas[this.id];
        }

    },
    startDefalutBehavior: function(sRawKey) {
        this._setDefalutBehavior(sRawKey, false);
    },
    stopDefalutBehavior: function(sRawKey) {
        this._setDefalutBehavior(sRawKey, true);
    },
    _setDefalutBehavior: function(sRawKey, bType) {
        this.keys[sRawKey].stopDefalutBehavior = bType;
    }
});

Shortcut.Helper = {
    keyInterpretor: function(sKey) {
        var keyArray = sKey.split("+");
        var wKeyArray = jindo.$A(keyArray);

        var returnVal = "";

        returnVal += wKeyArray.has("alt") ? "1" : "0";
        returnVal += wKeyArray.has("ctrl") ? "1" : "0";
        returnVal += wKeyArray.has("meta") ? "1" : "0";
        returnVal += wKeyArray.has("shift") ? "1" : "0";

        var wKeyArray = wKeyArray.filter(function(v) {
            return !(v == "alt" || v == "ctrl" || v == "meta" || v == "shift")
        });
        var key = wKeyArray.$value()[0];

        if (key) {

            var sKey = Shortcut.Store.anthorKeyHash[key.toUpperCase()] || key.toUpperCase().charCodeAt(0);
            returnVal += sKey;
        }

        return returnVal;
    },
    notCommonException: function(e, exceptions) {
        var leng = exceptions.length;
        for (var i = 0; i < leng; i++) {
            if (!exceptions[i](e))
                return false;
        }
        return true;
    },
    hasEvent: function(fpEvent, aEvents) {
        var nLength = aEvents.length;
        for (var i = 0; i < nLength; ++i) {
            if (aEvents.event == fpEvent) {
                return true;
            }
        };
        return false;
    },
    hasException: function(fpException, commonExceptions) {
        var nLength = commonExceptions.length;
        for (var i = 0; i < nLength; ++i) {
            if (commonExceptions[i] == fpException) {
                return true;
            }
        };
        return false;
    },
    bind: function(wfFunc, oElement, sType) {
        if (sType == "attach") {
            domAttach(oElement, "keydown", wfFunc);
        } else {
            domDetach(oElement, "keydown", wfFunc);
        }
    }

};

(function domAttach() {
    if (document.addEventListener) {
        window.domAttach = function(dom, ev, fn) {
            dom.addEventListener(ev, fn, false);
        }
    } else {
        window.domAttach = function(dom, ev, fn) {
            dom.attachEvent("on" + ev, fn);
        }
    }
})();

(function domDetach() {
    if (document.removeEventListener) {
        window.domDetach = function(dom, ev, fn) {
            dom.removeEventListener(ev, fn, false);
        }
    } else {
        window.domDetach = function(dom, ev, fn) {
            dom.detachEvent("on" + ev, fn);
        }
    }
})();



Shortcut.Action = {
    init: function(oData, sRawKey) {
        this.dataInstance = oData;
        this.rawKey = sRawKey;
        return this;
    },
    addEvent: function(fpEvent) {
        this.dataInstance.addEvent(fpEvent, this.rawKey);
        return this;
    },
    removeEvent: function(fpEvent) {
        this.dataInstance.removeEvent(fpEvent, this.rawKey);
        return this;
    },
    addException: function(fpException) {
        this.dataInstance.addException(fpException, this.rawKey);
        return this;
    },
    removeException: function(fpException) {
        this.dataInstance.removeException(fpException, this.rawKey);
        return this;
    },
    //  addCommonException : function(fpException){
    //      return this;
    //  },
    //  removeCommonEexception : function(fpException){
    //      return this;
    //  },
    startDefalutBehavior: function() {
        this.dataInstance.startDefalutBehavior(this.rawKey);
        return this;
    },
    stopDefalutBehavior: function() {
        this.dataInstance.stopDefalutBehavior(this.rawKey);
        return this;
    },
    resetElement: function() {
        Shortcut.Store.reset(this.dataInstance.id);
        return this;
    },
    resetAll: function() {
        Shortcut.Store.allReset();
        return this;
    }
};

(function() {
    Shortcut.Store.anthorKeyHash = {
        BACKSPACE: 8,
        TAB: 9,
        ENTER: 13,
        ESC: 27,
        SPACE: 32,
        PAGEUP: 33,
        PAGEDOWN: 34,
        END: 35,
        HOME: 36,
        LEFT: 37,
        UP: 38,
        RIGHT: 39,
        DOWN: 40,
        DEL: 46,
        COMMA: 188, //(,)
        PERIOD: 190, //(.)
        SLASH: 191 //(/),
    };
    var hash = Shortcut.Store.anthorKeyHash;
    for (var i = 1; i < 13; i++) {
        Shortcut.Store.anthorKeyHash["F" + i] = i + 111;
    }
    var agent = jindo.$Agent().navigator();
    if (agent.ie || agent.safari || agent.chrome) {
        hash.HYPHEN = 189; //(-)
        hash.EQUAL = 187; //(=)
    } else {
        hash.HYPHEN = 109;
        hash.EQUAL = 61;
    }
})();
var shortcut = Shortcut;
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the hotkey feature
 * @name hp_Hotkey.js
 */
nhn.husky.Hotkey = jindo.$Class({
    name: "Hotkey",

    $init: function() {
        this.oShortcut = shortcut;
    },

    $ON_ADD_HOTKEY: function(sHotkey, sCMD, aArgs, elTarget) {
        if (!aArgs) {
            aArgs = [];
        }

        var func = jindo.$Fn(this.oApp.exec, this.oApp).bind(sCMD, aArgs);
        this.oShortcut(sHotkey, elTarget).addEvent(func);
    }
});
//}
/*[
 * UNDO
 *
 * UNDO 히스토리에 저장되어 있는 이전 IR을 복구한다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * REDO
 *
 * UNDO 히스토리에 저장되어 있는 다음 IR을 복구한다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * RECORD_UNDO_ACTION
 *
 * 현재 IR을 UNDO 히스토리에 추가한다.
 *
 * sAction string 실행 할 액션(어떤 이유로 IR에 변경이 있었는지 참고용)
 * oSaveOption object 저장 옵션(htRecordOption 참고)
 *
---------------------------------------------------------------------------]*/
/*[
 * RECORD_UNDO_BEFORE_ACTION
 *
 * 현재 IR을 UNDO 히스토리에 추가한다. 액션 전후 따로 저장 할 경우 전 단계.
 *
 * sAction string 실행 할 액션(어떤 이유로 IR에 변경이 있었는지 참고용)
 * oSaveOption object 저장 옵션(htRecordOption 참고)
 *
---------------------------------------------------------------------------]*/
/*[
 * RECORD_UNDO_AFTER_ACTION
 *
 * 현재 IR을 UNDO 히스토리에 추가한다. 액션 전후 따로 저장 할 경우 후 단계.
 *
 * sAction string 실행 할 액션(어떤 이유로 IR에 변경이 있었는지 참고용)
 * oSaveOption object 저장 옵션(htRecordOption 참고)
 *
---------------------------------------------------------------------------]*/
/*[
 * RESTORE_UNDO_HISTORY
 *
 * UNDO 히스토리에 저장되어 있는 IR을 복구한다.
 *
 * nUndoIdx number 몇번째 히스토리를 복구할지
 * nUndoStateStep number 히스토리 내에 몇번째 스텝을 복구 할지. (before:0, after:1)
 *
---------------------------------------------------------------------------]*/
/*[
 * DO_RECORD_UNDO_HISTORY
 *
 * 현재 IR을 UNDO 히스토리에 추가한다.
 *
 * sAction string 실행 할 액션(어떤 이유로 IR에 변경이 있었는지 참고용)
 * htRecordOption object 저장 옵션
 *      nStep (number) 0 | 1                    액션의 스텝 인덱스 (보통 1단계이나 Selection 의 저장이 필요한 경우 1, 2단계로 나누어짐)
 *      bSkipIfEqual (bool) false | true        변경이 없다면 히스토리에 추가하지 않음 (현재 길이로 판단하여 저장함)
 *      bTwoStepAction (bool) false | true      2단계 액션인 경우
 *      sSaveTarget (string) [TAG] | null       저장 타겟을 지정하는 경우 사용 (해당 태그를 포함하여 저장)
 *      elSaveTarget : [Element] | null         저장 타겟을 지정하는 경우 사용 (해당 엘리먼트의 innerHTML을 저장)
 *      bDontSaveSelection : false | true       Selection을 추가하지 않는 경우 (예, 표 편집)
 *      bMustBlockElement : false | true        반드시 Block 엘리먼트에 대해서만 저장함, 없으면 BODY 영역 (예, 글자 스타일 편집)
 *      bMustBlockContainer : false | true      반드시 Block 엘리먼트(그 중 컨테이너로 사용되는)에 대해서만 저장함, 없으면 BODY 영역 (예, 엔터)
 *      oUndoCallback : null | [Object]         Undo 처리할 때 호출해야할 콜백 메시지 정보
 *      oRedoCallback : null | [Object]         Redo 처리할 때 호출해야할 콜백 메시지 정보
 *
---------------------------------------------------------------------------]*/
/*[
 * DO_RECORD_UNDO_HISTORY_AT
 *
 * 현재 IR을 UNDO 히스토리의 지정된 위치에 추가한다.
 *
 * oInsertionIdx object 삽입할 위치({nIdx:히스토리 번호, nStep: 히스토리 내에 액션 번호})
 * sAction string 실행 할 액션(어떤 이유로 IR에 변경이 있었는지 참고용)
 * sContent string 저장할 내용
 * oBookmark object oSelection.getXPathBookmark()를 통해 얻어진 북마크
 *
---------------------------------------------------------------------------]*/
/**
 * @pluginDesc Husky Framework에서 자주 사용되는 메시지를 처리하는 플러그인
 * @fileOverview This file contains Husky plugin that takes care of the operations related to Undo/Redo
 * @name hp_SE_UndoRedo.js
 * @required SE_EditingAreaManager, HuskyRangeManager
 */
nhn.husky.SE_UndoRedo = jindo.$Class({
    name: "SE_UndoRedo",

    oCurStateIdx: null,
    iMinimumSizeChange: 1,

    // limit = nMaxUndoCount + nAfterMaxDeleteBuffer. When the limit is reached delete [0...nAfterMaxDeleteBuffer] so only nMaxUndoCount histories will be left
    nMaxUndoCount: 20, // 1000
    nAfterMaxDeleteBuffer: 100,

    sBlankContentsForFF: "<br>",
    sDefaultXPath: "/HTML[0]/BODY[0]",

    $init: function() {
        this.aUndoHistory = [];
        this.oCurStateIdx = {
            nIdx: 0,
            nStep: 0
        };
        this.nHardLimit = this.nMaxUndoCount + this.nAfterMaxDeleteBuffer;
    },

    $LOCAL_BEFORE_ALL: function(sCmd) {
        if (sCmd.match(/_DO_RECORD_UNDO_HISTORY_AT$/)) {
            return true;
        }

        try {
            if (this.oApp.getEditingMode() != "WYSIWYG") {
                return false;
            }
        } catch (e) {
            return false;
        }

        return true;
    },

    $BEFORE_MSG_APP_READY: function() {
        this._historyLength = 0;
        this.oApp.exec("ADD_APP_PROPERTY", ["getUndoHistory", jindo.$Fn(this._getUndoHistory, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getUndoStateIdx", jindo.$Fn(this._getUndoStateIdx, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["saveSnapShot", jindo.$Fn(this._saveSnapShot, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["getLastKey", jindo.$Fn(this._getLastKey, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["setLastKey", jindo.$Fn(this._setLastKey, this).bind()]);

        this._saveSnapShot();

        this.oApp.exec("DO_RECORD_UNDO_HISTORY_AT", [this.oCurStateIdx, "", "", "", null, this.sDefaultXPath]);
    },

    _getLastKey: function() {
        return this.sLastKey;
    },

    _setLastKey: function(sLastKey) {
        this.sLastKey = sLastKey;
    },

    $ON_MSG_APP_READY: function() {
        var oNavigator = jindo.$Agent().navigator();
        this.bIE = oNavigator.ie;
        this.bFF = oNavigator.firefox;
        //this.bChrome = oNavigator.chrome;
        //this.bSafari = oNavigator.safari;

        this.oApp.exec("REGISTER_UI_EVENT", ["undo", "click", "UNDO"]);
        this.oApp.exec("REGISTER_UI_EVENT", ["redo", "click", "REDO"]);

        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+z", "UNDO"]);
        this.oApp.exec("REGISTER_HOTKEY", ["ctrl+y", "REDO"]);

        // this.htOptions =  this.oApp.htOptions["SE_UndoRedo"] || {};
    },

    $ON_UNDO: function() {
        this._doRecordUndoHistory("UNDO", {
            nStep: 0,
            bSkipIfEqual: true,
            bMustBlockContainer: true
        });

        if (this.oCurStateIdx.nIdx <= 0) {
            return;
        }

        // 현재의 상태에서 Undo 했을 때 처리해야 할 메시지 호출
        var oUndoCallback = this.aUndoHistory[this.oCurStateIdx.nIdx].oUndoCallback[this.oCurStateIdx.nStep];
        var sCurrentPath = this.aUndoHistory[this.oCurStateIdx.nIdx].sParentXPath[this.oCurStateIdx.nStep];

        if (oUndoCallback) {
            this.oApp.exec(oUndoCallback.sMsg, oUndoCallback.aParams);
        }

        if (this.oCurStateIdx.nStep > 0) {
            this.oCurStateIdx.nStep--;
        } else {
            var oTmpHistory = this.aUndoHistory[this.oCurStateIdx.nIdx];

            this.oCurStateIdx.nIdx--;

            if (oTmpHistory.nTotalSteps > 1) {
                this.oCurStateIdx.nStep = 0;
            } else {
                oTmpHistory = this.aUndoHistory[this.oCurStateIdx.nIdx];
                this.oCurStateIdx.nStep = oTmpHistory.nTotalSteps - 1;
            }
        }

        var sUndoHistoryPath = this.aUndoHistory[this.oCurStateIdx.nIdx].sParentXPath[this.oCurStateIdx.nStep];
        var bUseDefault = false;

        if (sUndoHistoryPath !== sCurrentPath && sUndoHistoryPath.indexOf(sCurrentPath) === 0) { // 현재의 Path가 Undo의 Path보다 범위가 큰 경우
            bUseDefault = true;
        }

        this.oApp.exec("RESTORE_UNDO_HISTORY", [this.oCurStateIdx.nIdx, this.oCurStateIdx.nStep, bUseDefault]);
        this.oApp.exec("CHECK_STYLE_CHANGE", []);

        this.sLastKey = null;
    },


    $ON_REDO: function() {
        if (this.oCurStateIdx.nIdx >= this.aUndoHistory.length) {
            return;
        }

        var oCurHistory = this.aUndoHistory[this.oCurStateIdx.nIdx];

        if (this.oCurStateIdx.nIdx == this.aUndoHistory.length - 1 && this.oCurStateIdx.nStep >= oCurHistory.nTotalSteps - 1) {
            return;
        }

        if (this.oCurStateIdx.nStep < oCurHistory.nTotalSteps - 1) {
            this.oCurStateIdx.nStep++;
        } else {
            this.oCurStateIdx.nIdx++;
            oCurHistory = this.aUndoHistory[this.oCurStateIdx.nIdx];
            this.oCurStateIdx.nStep = oCurHistory.nTotalSteps - 1;
        }

        // 원복될 상태에서 Redo 했을 때 처리해야 할 메시지 호출
        var oRedoCallback = this.aUndoHistory[this.oCurStateIdx.nIdx].oRedoCallback[this.oCurStateIdx.nStep];

        if (oRedoCallback) {
            this.oApp.exec(oRedoCallback.sMsg, oRedoCallback.aParams);
        }

        this.oApp.exec("RESTORE_UNDO_HISTORY", [this.oCurStateIdx.nIdx, this.oCurStateIdx.nStep]);
        this.oApp.exec("CHECK_STYLE_CHANGE", []);

        this.sLastKey = null;
    },

    $ON_RECORD_UNDO_ACTION: function(sAction, oSaveOption) {
        oSaveOption = oSaveOption || {
            sSaveTarget: null,
            elSaveTarget: null,
            bMustBlockElement: false,
            bMustBlockContainer: false,
            bDontSaveSelection: false
        };
        oSaveOption.nStep = 0;
        oSaveOption.bSkipIfEqual = false;
        oSaveOption.bTwoStepAction = false;

        this._doRecordUndoHistory(sAction, oSaveOption);
    },

    $ON_RECORD_UNDO_BEFORE_ACTION: function(sAction, oSaveOption) {
        oSaveOption = oSaveOption || {
            sSaveTarget: null,
            elSaveTarget: null,
            bMustBlockElement: false,
            bMustBlockContainer: false,
            bDontSaveSelection: false
        };
        oSaveOption.nStep = 0;
        oSaveOption.bSkipIfEqual = false;
        oSaveOption.bTwoStepAction = true;

        this._doRecordUndoHistory(sAction, oSaveOption);
    },

    $ON_RECORD_UNDO_AFTER_ACTION: function(sAction, oSaveOption) {
        oSaveOption = oSaveOption || {
            sSaveTarget: null,
            elSaveTarget: null,
            bMustBlockElement: false,
            bMustBlockContainer: false,
            bDontSaveSelection: false
        };
        oSaveOption.nStep = 1;
        oSaveOption.bSkipIfEqual = false;
        oSaveOption.bTwoStepAction = true;

        this._doRecordUndoHistory(sAction, oSaveOption);
    },

    $ON_RESTORE_UNDO_HISTORY: function(nUndoIdx, nUndoStateStep, bUseDefault) {
        this.oApp.exec("HIDE_ACTIVE_LAYER");

        this.oCurStateIdx.nIdx = nUndoIdx;
        this.oCurStateIdx.nStep = nUndoStateStep;

        var oCurHistory = this.aUndoHistory[this.oCurStateIdx.nIdx],
            sContent = oCurHistory.sContent[this.oCurStateIdx.nStep],
            sFullContents = oCurHistory.sFullContents[this.oCurStateIdx.nStep],
            oBookmark = oCurHistory.oBookmark[this.oCurStateIdx.nStep],
            sParentXPath = oCurHistory.sParentXPath[this.oCurStateIdx.nStep],
            oParent = null,
            sCurContent = "",
            oSelection = this.oApp.getEmptySelection();

        this.oApp.exec("RESTORE_IE_SELECTION"); // this is done to null the ie selection

        if (bUseDefault) {
            this.oApp.getWYSIWYGDocument().body.innerHTML = sFullContents;
            sFullContents = this.oApp.getWYSIWYGDocument().body.innerHTML;
            sCurContent = sFullContents;
            sParentXPath = this.sDefaultXPath;
        } else {
            oParent = oSelection._evaluateXPath(sParentXPath, oSelection._document);
            try {
                oParent.innerHTML = sContent;
                sCurContent = oParent.innerHTML;
            } catch (e) { // Path 노드를 찾지 못하는 경우
                this.oApp.getWYSIWYGDocument().body.innerHTML = sFullContents;
                sFullContents = this.oApp.getWYSIWYGDocument().body.innerHTML; // setting the innerHTML may change the internal DOM structure, so save the value again.
                sCurContent = sFullContents;
                sParentXPath = this.sDefaultXPath;
            }
        }

        if (this.bFF && sCurContent == this.sBlankContentsForFF) {
            sCurContent = "";
        }

        oCurHistory.sFullContents[this.oCurStateIdx.nStep] = sFullContents;
        oCurHistory.sContent[this.oCurStateIdx.nStep] = sCurContent;
        oCurHistory.sParentXPath[this.oCurStateIdx.nStep] = sParentXPath;

        if (oBookmark && oBookmark.sType == "scroll") {
            setTimeout(jindo.$Fn(function() {
                this.oApp.getWYSIWYGDocument().documentElement.scrollTop = oBookmark.nScrollTop;
            }, this).bind(), 0);
        } else {
            oSelection = this.oApp.getEmptySelection();
            if (oSelection.selectionLoaded) {
                if (oBookmark) {
                    oSelection.moveToXPathBookmark(oBookmark);
                } else {
                    oSelection = this.oApp.getEmptySelection();
                }

                oSelection.select();
            }
        }
    },

    _doRecordUndoHistory: function(sAction, htRecordOption) {
        /*
            htRecordOption = {
                nStep : 0 | 1,
                bSkipIfEqual : false | true,
                bTwoStepAction : false | true,
                sSaveTarget : [TAG] | null
                elSaveTarget : [Element] | null
                bDontSaveSelection : false | true
                bMustBlockElement : false | true
                bMustBlockContainer : false | true
            };
         */

        htRecordOption = htRecordOption || {};

        var nStep = htRecordOption.nStep || 0,
            bSkipIfEqual = htRecordOption.bSkipIfEqual || false,
            bTwoStepAction = htRecordOption.bTwoStepAction || false,
            sSaveTarget = htRecordOption.sSaveTarget || null,
            elSaveTarget = htRecordOption.elSaveTarget || null,
            bDontSaveSelection = htRecordOption.bDontSaveSelection || false,
            bMustBlockElement = htRecordOption.bMustBlockElement || false,
            bMustBlockContainer = htRecordOption.bMustBlockContainer || false,
            oUndoCallback = htRecordOption.oUndoCallback,
            oRedoCallback = htRecordOption.oRedoCallback;

        // if we're in the middle of some action history,
        // remove everything after current idx if any "little" change is made
        this._historyLength = this.aUndoHistory.length;

        if (this.oCurStateIdx.nIdx !== this._historyLength - 1) {
            bSkipIfEqual = true;
        }

        var oCurHistory = this.aUndoHistory[this.oCurStateIdx.nIdx],
            sHistoryFullContents = oCurHistory.sFullContents[this.oCurStateIdx.nStep],
            sCurContent = "",
            sFullContents = "",
            sParentXPath = "",
            oBookmark = null,
            oSelection = null,
            oInsertionIdx = {
                nIdx: this.oCurStateIdx.nIdx,
                nStep: this.oCurStateIdx.nStep
            }; // 히스토리를 저장할 위치

        oSelection = this.oApp.getSelection();

        if (oSelection.selectionLoaded) {
            oBookmark = oSelection.getXPathBookmark();
        }

        if (elSaveTarget) {
            sParentXPath = oSelection._getXPath(elSaveTarget);
        } else if (sSaveTarget) {
            sParentXPath = this._getTargetXPath(oBookmark, sSaveTarget);
        } else {
            sParentXPath = this._getParentXPath(oBookmark, bMustBlockElement, bMustBlockContainer);
        }

        sFullContents = this.oApp.getWYSIWYGDocument().body.innerHTML;
        // sCurContent = this.oApp.getWYSIWYGDocument().body.innerHTML.replace(/ *_cssquery_UID="[^"]+" */g, "");

        if (sParentXPath === this.sDefaultXPath) {
            sCurContent = sFullContents;
        } else {
            sCurContent = oSelection._evaluateXPath(sParentXPath, oSelection._document).innerHTML;
        }

        if (this.bFF && sCurContent == this.sBlankContentsForFF) {
            sCurContent = "";
        }

        // every TwoStepAction needs to be recorded
        if (!bTwoStepAction && bSkipIfEqual) {
            if (sHistoryFullContents.length === sFullContents.length) {
                return;
            }

            // 저장된 데이터와 같음에도 다르다고 처리되는 경우에 대한 처리
            // (예, P안에 Block엘리먼트가 추가된 경우 P를 분리)
            //if(this.bChrome || this.bSafari){
            var elCurrentDiv = document.createElement("div");
            var elHistoryDiv = document.createElement("div");

            elCurrentDiv.innerHTML = sFullContents;
            elHistoryDiv.innerHTML = sHistoryFullContents;

            var elDocFragment = document.createDocumentFragment();
            elDocFragment.appendChild(elCurrentDiv);
            elDocFragment.appendChild(elHistoryDiv);

            sFullContents = elCurrentDiv.innerHTML;
            sHistoryFullContents = elHistoryDiv.innerHTML;

            elCurrentDiv = null;
            elHistoryDiv = null;
            elDocFragment = null;

            if (sHistoryFullContents.length === sFullContents.length) {
                return;
            }
            //}
        }

        if (bDontSaveSelection) {
            oBookmark = {
                sType: "scroll",
                nScrollTop: this.oApp.getWYSIWYGDocument().documentElement.scrollTop
            };
        }

        oInsertionIdx.nStep = nStep;

        if (oInsertionIdx.nStep === 0 && this.oCurStateIdx.nStep === oCurHistory.nTotalSteps - 1) {
            oInsertionIdx.nIdx = this.oCurStateIdx.nIdx + 1;
        }

        this._doRecordUndoHistoryAt(oInsertionIdx, sAction, sCurContent, sFullContents, oBookmark, sParentXPath, oUndoCallback, oRedoCallback);
    },

    $ON_DO_RECORD_UNDO_HISTORY_AT: function(oInsertionIdx, sAction, sContent, sFullContents, oBookmark, sParentXPath) {
        this._doRecordUndoHistoryAt(oInsertionIdx, sAction, sContent, sFullContents, oBookmark, sParentXPath);
    },

    _doRecordUndoHistoryAt: function(oInsertionIdx, sAction, sContent, sFullContents, oBookmark, sParentXPath, oUndoCallback, oRedoCallback) {
        if (oInsertionIdx.nStep !== 0) {
            this.aUndoHistory[oInsertionIdx.nIdx].nTotalSteps = oInsertionIdx.nStep + 1;
            this.aUndoHistory[oInsertionIdx.nIdx].sContent[oInsertionIdx.nStep] = sContent;
            this.aUndoHistory[oInsertionIdx.nIdx].sFullContents[oInsertionIdx.nStep] = sFullContents;
            this.aUndoHistory[oInsertionIdx.nIdx].oBookmark[oInsertionIdx.nStep] = oBookmark;
            this.aUndoHistory[oInsertionIdx.nIdx].sParentXPath[oInsertionIdx.nStep] = sParentXPath;
            this.aUndoHistory[oInsertionIdx.nIdx].oUndoCallback[oInsertionIdx.nStep] = oUndoCallback;
            this.aUndoHistory[oInsertionIdx.nIdx].oRedoCallback[oInsertionIdx.nStep] = oRedoCallback;
        } else {
            var oNewHistory = {
                sAction: sAction,
                nTotalSteps: 1
            };
            oNewHistory.sContent = [];
            oNewHistory.sContent[0] = sContent;

            oNewHistory.sFullContents = [];
            oNewHistory.sFullContents[0] = sFullContents;

            oNewHistory.oBookmark = [];
            oNewHistory.oBookmark[0] = oBookmark;

            oNewHistory.sParentXPath = [];
            oNewHistory.sParentXPath[0] = sParentXPath;

            oNewHistory.oUndoCallback = [];
            oNewHistory.oUndoCallback[0] = oUndoCallback;

            oNewHistory.oRedoCallback = [];
            oNewHistory.oRedoCallback[0] = oRedoCallback;

            this.aUndoHistory.splice(oInsertionIdx.nIdx, this._historyLength - oInsertionIdx.nIdx, oNewHistory);
            this._historyLength = this.aUndoHistory.length;
        }

        if (this._historyLength > this.nHardLimit) {
            this.aUndoHistory.splice(0, this.nAfterMaxDeleteBuffer);
            oInsertionIdx.nIdx -= this.nAfterMaxDeleteBuffer;
        }
        this.oCurStateIdx.nIdx = oInsertionIdx.nIdx;
        this.oCurStateIdx.nStep = oInsertionIdx.nStep;
    },

    _saveSnapShot: function() {
        this.oSnapShot = {
            oBookmark: this.oApp.getSelection().getXPathBookmark()
        };
    },

    _getTargetXPath: function(oBookmark, sSaveTarget) { // ex. A, TABLE ...
        var sParentXPath = this.sDefaultXPath,
            aStartXPath = oBookmark[0].sXPath.split("/"),
            aEndXPath = oBookmark[1].sXPath.split("/"),
            aParentPath = [],
            nPathLen = aStartXPath.length < aEndXPath.length ? aStartXPath.length : aEndXPath.length,
            nPathIdx = 0,
            nTargetIdx = -1;

        if (sSaveTarget === "BODY") {
            return sParentXPath;
        }

        for (nPathIdx = 0; nPathIdx < nPathLen; nPathIdx++) {
            if (aStartXPath[nPathIdx] !== aEndXPath[nPathIdx]) {
                break;
            }

            aParentPath.push(aStartXPath[nPathIdx]);

            if (aStartXPath[nPathIdx] === "" || aStartXPath[nPathIdx] === "HTML" || aStartXPath[nPathIdx] === "BODY") {
                continue;
            }

            if (aStartXPath[nPathIdx].indexOf(sSaveTarget) > -1) {
                nTargetIdx = nPathIdx;
            }
        }

        if (nTargetIdx > -1) {
            aParentPath.length = nTargetIdx; // Target 의 상위 노드까지 지정
        }

        sParentXPath = aParentPath.join("/");

        if (sParentXPath.length < this.sDefaultXPath.length) {
            sParentXPath = this.sDefaultXPath;
        }

        return sParentXPath;
    },

    _getParentXPath: function(oBookmark, bMustBlockElement, bMustBlockContainer) {
        var sParentXPath = this.sDefaultXPath,
            aStartXPath, aEndXPath,
            aSnapShotStart, aSnapShotEnd,
            nSnapShotLen, nPathLen,
            aParentPath = ["", "HTML[0]", "BODY[0]"],
            nPathIdx = 0,
            nBlockIdx = -1,
            // rxBlockContainer = /\bUL|OL|TD|TR|TABLE|BLOCKQUOTE\[/i,  // DL
            // rxBlockElement = /\bP|LI|DIV|UL|OL|TD|TR|TABLE|BLOCKQUOTE\[/i,   // H[1-6]|DD|DT|DL|PRE
            // rxBlock,
            sPath, sTag;

        if (!oBookmark) {
            return sParentXPath;
        }

        // 가능한 중복되는 Parent 를 검색
        if (oBookmark[0].sXPath === sParentXPath || oBookmark[1].sXPath === sParentXPath) {
            return sParentXPath;
        }

        aStartXPath = oBookmark[0].sXPath.split("/");
        aEndXPath = oBookmark[1].sXPath.split("/");
        aSnapShotStart = this.oSnapShot.oBookmark[0].sXPath.split("/");
        aSnapShotEnd = this.oSnapShot.oBookmark[1].sXPath.split("/");

        nSnapShotLen = aSnapShotStart.length < aSnapShotEnd.length ? aSnapShotStart.length : aSnapShotEnd.length;
        nPathLen = aStartXPath.length < aEndXPath.length ? aStartXPath.length : aEndXPath.length;
        nPathLen = nPathLen < nSnapShotLen ? nPathLen : nSnapShotLen;

        if (nPathLen < 3) { // BODY
            return sParentXPath;
        }

        bMustBlockElement = bMustBlockElement || false;
        bMustBlockContainer = bMustBlockContainer || false;
        // rxBlock = bMustBlockElement ? rxBlockElement : rxBlockContainer;

        for (nPathIdx = 3; nPathIdx < nPathLen; nPathIdx++) {
            sPath = aStartXPath[nPathIdx];

            if (sPath !== aEndXPath[nPathIdx] ||
                sPath !== aSnapShotStart[nPathIdx] ||
                sPath !== aSnapShotEnd[nPathIdx] ||
                aEndXPath[nPathIdx] !== aSnapShotStart[nPathIdx] ||
                aEndXPath[nPathIdx] !== aSnapShotEnd[nPathIdx] ||
                aSnapShotStart[nPathIdx] !== aSnapShotEnd[nPathIdx]) {

                break;
            }

            aParentPath.push(sPath);

            sTag = sPath.substring(0, sPath.indexOf("["));

            if (bMustBlockElement && (sTag === "P" || sTag === "LI" || sTag === "DIV")) {
                nBlockIdx = nPathIdx;
            } else if (sTag === "UL" || sTag === "OL" || sTag === "TD" || sTag === "TR" || sTag === "TABLE" || sTag === "BLOCKQUOTE") {
                nBlockIdx = nPathIdx;
            }

            // if(rxBlock.test(sPath)){
            // nBlockIdx = nPathIdx;
            // }
        }

        if (nBlockIdx > -1) {
            aParentPath.length = nBlockIdx + 1;
        } else if (bMustBlockElement || bMustBlockContainer) {
            return sParentXPath;
        }

        return aParentPath.join("/");
    },

    _getUndoHistory: function() {
        return this.aUndoHistory;
    },

    _getUndoStateIdx: function() {
        return this.oCurStateIdx;
    }
});
/*[
 * ATTACH_HOVER_EVENTS
 *
 * 주어진 HTML엘리먼트에 Hover 이벤트 발생시 특정 클래스가 할당 되도록 설정
 *
 * aElms array Hover 이벤트를 걸 HTML Element 목록
 * sHoverClass string Hover 시에 할당 할 클래스
 *
---------------------------------------------------------------------------]*/
/**
 * @pluginDesc Husky Framework에서 자주 사용되는 유틸성 메시지를 처리하는 플러그인
 */
nhn.husky.Utils = jindo.$Class({
    name: "Utils",

    $init: function() {
        var oAgentInfo = jindo.$Agent();
        var oNavigatorInfo = oAgentInfo.navigator();

        if (oNavigatorInfo.ie && oNavigatorInfo.version == 6) {
            try {
                document.execCommand('BackgroundImageCache', false, true);
            } catch (e) {}
        }
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["htBrowser", jindo.$Agent().navigator()]);
    },

    $ON_ATTACH_HOVER_EVENTS: function(aElms, htOptions) {
        htOptions = htOptions || [];
        var sHoverClass = htOptions.sHoverClass || "hover";
        var fnElmToSrc = htOptions.fnElmToSrc || function(el) {
            return el
        };
        var fnElmToTarget = htOptions.fnElmToTarget || function(el) {
            return el
        };

        if (!aElms) return;

        var wfAddClass = jindo.$Fn(function(wev) {
            jindo.$Element(fnElmToTarget(wev.currentElement)).addClass(sHoverClass);
        }, this);

        var wfRemoveClass = jindo.$Fn(function(wev) {
            jindo.$Element(fnElmToTarget(wev.currentElement)).removeClass(sHoverClass);
        }, this);

        for (var i = 0, len = aElms.length; i < len; i++) {
            var elSource = fnElmToSrc(aElms[i]);

            wfAddClass.attach(elSource, "mouseover");
            wfRemoveClass.attach(elSource, "mouseout");

            wfAddClass.attach(elSource, "focus");
            wfRemoveClass.attach(elSource, "blur");
        }
    }
});
/*[
 * SHOW_DIALOG_LAYER
 *
 * 다이얼로그 레이어를 화면에 보여준다.
 *
 * oLayer HTMLElement 다이얼로그 레이어로 사용 할 HTML 엘리먼트
 *
---------------------------------------------------------------------------]*/
/*[
 * HIDE_DIALOG_LAYER
 *
 * 다이얼로그 레이어를 화면에 숨긴다.
 *
 * oLayer HTMLElement 숨길 다이얼로그 레이어에 해당 하는 HTML 엘리먼트
 *
---------------------------------------------------------------------------]*/
/*[
 * HIDE_LAST_DIALOG_LAYER
 *
 * 마지막으로 화면에 표시한 다이얼로그 레이어를 숨긴다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/*[
 * HIDE_ALL_DIALOG_LAYER
 *
 * 표시 중인 모든 다이얼로그 레이어를 숨긴다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/**
 * @pluginDesc 드래그가 가능한 레이어를 컨트롤 하는 플러그인
 */
nhn.husky.DialogLayerManager = jindo.$Class({
    name: "DialogLayerManager",
    aMadeDraggable: null,
    aOpenedLayers: null,

    $init: function() {
        this.aMadeDraggable = [];
        this.aDraggableLayer = [];
        this.aOpenedLayers = [];
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oNavigator = jindo.$Agent().navigator();
    },

    $ON_MSG_APP_READY: function() {
        this.oApp.registerLazyMessage(["SHOW_DIALOG_LAYER", "TOGGLE_DIALOG_LAYER"], ["hp_DialogLayerManager$Lazy.js", "N_DraggableLayer.js"]);
    }
});
/*[
 * TOGGLE_ACTIVE_LAYER
 *
 * 액티브 레이어가 화면에 보이는 여부를 토글 한다.
 *
 * oLayer HTMLElement 레이어로 사용할 HTML Element
 * sOnOpenCmd string 화면에 보이는 경우 발생 할 메시지(옵션)
 * aOnOpenParam array sOnOpenCmd와 함께 넘겨줄 파라미터(옵션)
 * sOnCloseCmd string 해당 레이어가 화면에서 숨겨질 때 발생 할 메시지(옵션)
 * aOnCloseParam array sOnCloseCmd와 함께 넘겨줄 파라미터(옵션)
 *
---------------------------------------------------------------------------]*/
/*[
 * SHOW_ACTIVE_LAYER
 *
 * 액티브 레이어가 화면에 보이는 여부를 토글 한다.
 *
 * oLayer HTMLElement 레이어로 사용할 HTML Element
 * sOnCloseCmd string 해당 레이어가 화면에서 숨겨질 때 발생 할 메시지(옵션)
 * aOnCloseParam array sOnCloseCmd와 함께 넘겨줄 파라미터(옵션)
 *
---------------------------------------------------------------------------]*/
/*[
 *  HIDE_ACTIVE_LAYER
 *
 * 현재 화면에 보이는 액티브 레이어를 화면에서 숨긴다.
 *
 * none
 *
---------------------------------------------------------------------------]*/
/**
 * @pluginDesc 한번에 한개만 화면에 보여야 하는 레이어를 관리하는 플러그인
 */
nhn.husky.ActiveLayerManager = jindo.$Class({
    name: "ActiveLayerManager",
    oCurrentLayer: null,

    $BEFORE_MSG_APP_READY: function() {
        this.oNavigator = jindo.$Agent().navigator();
    },

    $ON_TOGGLE_ACTIVE_LAYER: function(oLayer, sOnOpenCmd, aOnOpenParam, sOnCloseCmd, aOnCloseParam) {
        if (oLayer == this.oCurrentLayer) {
            this.oApp.exec("HIDE_ACTIVE_LAYER", []);
        } else {
            this.oApp.exec("SHOW_ACTIVE_LAYER", [oLayer, sOnCloseCmd, aOnCloseParam]);
            if (sOnOpenCmd) {
                this.oApp.exec(sOnOpenCmd, aOnOpenParam);
            }
        }
    },

    $ON_SHOW_ACTIVE_LAYER: function(oLayer, sOnCloseCmd, aOnCloseParam) {
        oLayer = jindo.$(oLayer);

        var oPrevLayer = this.oCurrentLayer;
        if (oLayer == oPrevLayer) {
            return;
        }

        this.oApp.exec("HIDE_ACTIVE_LAYER", []);

        this.sOnCloseCmd = sOnCloseCmd;
        this.aOnCloseParam = aOnCloseParam;

        oLayer.style.display = "block";
        this.oCurrentLayer = oLayer;
        this.oApp.exec("ADD_APP_PROPERTY", ["oToolBarLayer", this.oCurrentLayer]);
    },

    $ON_HIDE_ACTIVE_LAYER: function() {
        var oLayer = this.oCurrentLayer;
        if (!oLayer) {
            return;
        }
        oLayer.style.display = "none";
        this.oCurrentLayer = null;
        if (this.sOnCloseCmd) {
            this.oApp.exec(this.sOnCloseCmd, this.aOnCloseParam);
        }
    },

    $ON_HIDE_ACTIVE_LAYER_IF_NOT_CHILD: function(el) {
        var elTmp = el;
        while (elTmp) {
            if (elTmp == this.oCurrentLayer) {
                return;
            }
            elTmp = elTmp.parentNode;
        }
        this.oApp.exec("HIDE_ACTIVE_LAYER");
    },

    // for backward compatibility only.
    // use HIDE_ACTIVE_LAYER instead!
    $ON_HIDE_CURRENT_ACTIVE_LAYER: function() {
        this.oApp.exec("HIDE_ACTIVE_LAYER", []);
    }
});
//{
/**
 * @fileOverview This file contains Husky plugin that takes care of the operations related to string conversion. Ususally used to convert the IR value.
 * @name hp_StringConverterManager.js
 */
nhn.husky.StringConverterManager = jindo.$Class({
    name: "StringConverterManager",

    oConverters: null,

    $init: function() {
        this.oConverters = {};
        this.oConverters_DOM = {};
        this.oAgent = jindo.$Agent().navigator();
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["applyConverter", jindo.$Fn(this.applyConverter, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["addConverter", jindo.$Fn(this.addConverter, this).bind()]);
        this.oApp.exec("ADD_APP_PROPERTY", ["addConverter_DOM", jindo.$Fn(this.addConverter_DOM, this).bind()]);
    },

    applyConverter: function(sRuleName, sContents, oDocument) {
        //string을 넣는 이유:IE의 경우,본문 앞에 있는 html 주석이 삭제되는 경우가 있기때문에 임시 string을 추가해준것임.
        var sTmpStr = "@" + (new Date()).getTime() + "@";
        var rxTmpStr = new RegExp(sTmpStr, "g");

        var oRes = {
            sContents: sTmpStr + sContents
        };

        oDocument = oDocument || document;

        this.oApp.exec("MSG_STRING_CONVERTER_STARTED", [sRuleName, oRes]);
        //      this.oApp.exec("MSG_STRING_CONVERTER_STARTED_"+sRuleName, [oRes]);

        var aConverters;
        sContents = oRes.sContents;
        aConverters = this.oConverters_DOM[sRuleName];
        if (aConverters) {
            var elContentsHolder = oDocument.createElement("DIV");
            elContentsHolder.innerHTML = sContents;

            for (var i = 0; i < aConverters.length; i++) {
                aConverters[i](elContentsHolder);
            }
            sContents = elContentsHolder.innerHTML;
            // 내용물에 EMBED등이 있을 경우 IE에서 페이지 나갈 때 권한 오류 발생 할 수 있어 명시적으로 노드 삭제.

            if (!!elContentsHolder.parentNode) {
                elContentsHolder.parentNode.removeChild(elContentsHolder);
            }
            elContentsHolder = null;


            //IE의 경우, sContents를 innerHTML로 넣는 경우 string과 <p>tag 사이에 '\n\'개행문자를 넣어준다.
            if (jindo.$Agent().navigator().ie) {
                sTmpStr = sTmpStr + '(\r\n)?'; //ie+win에서는 개행이 \r\n로 들어감.
                rxTmpStr = new RegExp(sTmpStr, "g");
            }
        }

        aConverters = this.oConverters[sRuleName];
        if (aConverters) {
            for (var i = 0; i < aConverters.length; i++) {
                var sTmpContents = aConverters[i](sContents);
                if (typeof sTmpContents != "undefined") {
                    sContents = sTmpContents;
                }
            }
        }

        oRes = {
            sContents: sContents
        };
        this.oApp.exec("MSG_STRING_CONVERTER_ENDED", [sRuleName, oRes]);

        oRes.sContents = oRes.sContents.replace(rxTmpStr, "");
        return oRes.sContents;
    },

    $ON_ADD_CONVERTER: function(sRuleName, funcConverter) {
        var aCallerStack = this.oApp.aCallerStack;
        funcConverter.sPluginName = aCallerStack[aCallerStack.length - 2].name;
        this.addConverter(sRuleName, funcConverter);
    },

    $ON_ADD_CONVERTER_DOM: function(sRuleName, funcConverter) {
        var aCallerStack = this.oApp.aCallerStack;
        funcConverter.sPluginName = aCallerStack[aCallerStack.length - 2].name;
        this.addConverter_DOM(sRuleName, funcConverter);
    },

    addConverter: function(sRuleName, funcConverter) {
        var aConverters = this.oConverters[sRuleName];
        if (!aConverters) {
            this.oConverters[sRuleName] = [];
        }

        this.oConverters[sRuleName][this.oConverters[sRuleName].length] = funcConverter;
    },

    addConverter_DOM: function(sRuleName, funcConverter) {
        var aConverters = this.oConverters_DOM[sRuleName];
        if (!aConverters) {
            this.oConverters_DOM[sRuleName] = [];
        }

        this.oConverters_DOM[sRuleName][this.oConverters_DOM[sRuleName].length] = funcConverter;
    }
});
//}




//{
/**
 * @fileOverview This file contains Husky plugin that maps a message code to the actual message
 * @name hp_MessageManager.js
 */
nhn.husky.MessageManager = jindo.$Class({
    name: "MessageManager",

    oMessageMap: null,
    sLocale: "ko_KR",

    $init: function(oMessageMap, sLocale) {
        switch (sLocale) {
            case "ja_JP":
                this.oMessageMap = oMessageMap_ja_JP;
                break;
            case "en_US":
                this.oMessageMap = oMessageMap_en_US;
                break;
            case "zh_CN":
                this.oMessageMap = oMessageMap_zh_CN;
                break;
            default: // Korean
                this.oMessageMap = oMessageMap;
                break;
        }
    },

    $BEFORE_MSG_APP_READY: function() {
        this.oApp.exec("ADD_APP_PROPERTY", ["$MSG", jindo.$Fn(this.getMessage, this).bind()]);
    },

    getMessage: function(sMsg) {
        if (this.oMessageMap[sMsg]) {
            return unescape(this.oMessageMap[sMsg]);
        }
        return sMsg;
    }
});
//}





/**
 * 문자를 연결하는 '+' 대신에 java와 유사하게 처리하도록 문자열 처리하도록 만드는 object
 * @author nox
 * @example
 var sTmp1 = new StringBuffer();
 sTmp1.append('1').append('2').append('3');

 var sTmp2 = new StringBuffer('1');
 sTmp2.append('2').append('3');

 var sTmp3 = new StringBuffer('1').append('2').append('3');
 */
if ('undefined' != typeof(StringBuffer)) {
    StringBuffer = {};
}

StringBuffer = function(str) {
    this._aString = [];
    if ('undefined' != typeof(str)) {
        this.append(str);
    }
};

StringBuffer.prototype.append = function(str) {
    this._aString.push(str);
    return this;
};

StringBuffer.prototype.toString = function() {
    return this._aString.join('');
};

StringBuffer.prototype.setLength = function(nLen) {
    if ('undefined' == typeof(nLen) || 0 >= nLen) {
        this._aString.length = 0;
    } else {
        this._aString.length = nLen;
    }
};
if (typeof window.nhn == 'undefined') {
    window.nhn = {};
}
/**
 * @fileOverview This file contains a message mapping(Korean), which is used to map the message code to the actual message
 * @name husky_SE2B_Lang_ko_KR.js
 * @ unescape
 */
var oMessageMap = {
    'SE_EditingAreaManager.onExit': '내용이 변경되었습니다.',
    'SE_Color.invalidColorCode': '색상 코드를 올바르게 입력해 주세요. \n\n 예) #000000, #FF0000, #FFFFFF, #ffffff, ffffff',
    'SE_Hyperlink.invalidURL': '입력하신 URL이 올바르지 않습니다.',
    'SE_FindReplace.keywordMissing': '찾으실 단어를 입력해 주세요.',
    'SE_FindReplace.keywordNotFound': '찾으실 단어가 없습니다.',
    'SE_FindReplace.replaceAllResultP1': '일치하는 내용이 총 ',
    'SE_FindReplace.replaceAllResultP2': '건 바뀌었습니다.',
    'SE_FindReplace.notSupportedBrowser': '현재 사용하고 계신 브라우저에서는 사용하실수 없는 기능입니다.\n\n이용에 불편을 드려 죄송합니다.',
    'SE_FindReplace.replaceKeywordNotFound': '바뀔 단어가 없습니다',
    'SE_LineHeight.invalidLineHeight': '잘못된 값입니다.',
    'SE_Footnote.defaultText': '각주내용을 입력해 주세요',
    'SE.failedToLoadFlash': '플래시가 차단되어 있어 해당 기능을 사용할 수 없습니다.',
    'SE2M_EditingModeChanger.confirmTextMode': '텍스트 모드로 전환하면 작성된 내용은 유지되나, \n\n글꼴 등의 편집효과와 이미지 등의 첨부내용이 모두 사라지게 됩니다.\n\n전환하시겠습니까?',
    'SE2M_FontNameWithLayerUI.sSampleText': '가나다라'
};
