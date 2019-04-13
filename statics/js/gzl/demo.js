
//node class
function node(op) {
    //位置
    this.left = 50;
    this.top = 50;
    this.nodeIndex = 0;//-1表示最后一个节点
    this.action = 0;//0正常、已存在；1新增；2修改；3删除

    this.id = "";
    this.shape = "Rectangle";
    this.name = "";
    this.status = "";//?
    this.type = "";//节点类型：0任务节点，1合并，2分流?
    this.descp = "";
    this.nodeaction = "";

    var me = this;
    me = $.extend(me, op);
}

//connection class
function connection(op) {
    //连线
    this.sourceId = "";
    this.targetId = "";
    this.id = "";
    this.action = 0;//0正常、已存在；1新增；2修改；3删除
    //权限
    this.roleId = "";//角色id
    this.timeLimit = "";//处理时限(天)
    this.formIdList = [];//关联表单
    this.isAllowRollback = false;//是否允许回退
    this.isAllowExist = true;//是否允许退出流程
    this.isCreate = true;//是否允许发起
    this.isRecycling = true;//是否允许回收
    this.isCheck = true;//是否允许审核
    this.isAuth = true;//是否允许授权
    this.isReject = true;//是否允许驳回
    this.process_id = '';

    var me = this;
    me = $.extend(me, op);
}
//diagram class
function diagram() {
    this._nlist = [];
    this._clist = [];
    this._deleteNlist = [];
    this._deleteClist = [];
}
//节点操作
diagram.prototype.resetNodeIndex = function (currentId) {
    var me = this;
    var currrentIndex = 99999;
    for (var i = 0; i < me._nlist.length; i++) {
        var item = me._nlist[i];
        if (currentId == item.id) {
            currrentIndex = i;
        }
        if (i > currrentIndex) {
            item.nodeIndex--;
        }
    }
}
diagram.prototype.addNode = function (node) {
    var me = this;
    me._nlist.push(node);
}
diagram.prototype.deleteNodeById = function (id) {
    var me = this;
    for (var i = 0; i < me._nlist.length; i++) {
        var item = me._nlist[i];
        var logicId = item.id;//.split(/temp/)[0];
        if (id == logicId) {
            me.resetNodeIndex(id);
            var item2 = me._nlist.splice(i, 1);//删除
            if (item2.length >= 1) {
                //新增的不要放入此列表
                if (item2[0].action == 3) {
                    me._deleteNlist.push(item2[0]);
                }
            }
            return item2;
        }
    }
    return null;
}
diagram.prototype.getAction = function (srcAction, newAction) {
    var x = newAction;
    //如果srcAction新增，newAction为修改/删除，结果也为新增；
    if (srcAction == 1) {
        x = 1;
    }
    return x;
}
diagram.prototype.setNodeAction = function (id,newAction) {
    var item = this.getNodeById(id);
    item.action = this.getAction(item.action, newAction);
}
diagram.prototype.getNodeById = function (id) {
    var me = this;
    for (var i = 0; i < me._nlist.length; i++) {
        var item = me._nlist[i];
        var logicId = item.id;//.split(/temp/)[0];
        if (id == logicId) {
            return item;
        }
    }
    return null;
}
diagram.prototype.setNode = function (newNode) {
    var currentNode = this.getNodeById(newNode.id);
    for (var p in newNode) {
        currentNode[p] = newNode[p];
        //eval("currentNode." + p + "= newNode[p]");
    }
}
diagram.prototype.getNodeList = function () {
    return this._nlist;
}
diagram.prototype.getNewIndex = function () {
    var me = this;
    var hasFixLast = false;
    for (var i = 0; i < me._nlist.length; i++) {
        var item = me._nlist[i];
        if (item.nodeIndex == -1) {
            hasFixLast = true;
            break
        }
    }
    var newIndex = me._nlist.length;
    if (hasFixLast) newIndex--;
    return newIndex;
}
//连线操作
diagram.prototype.setConnAction = function (sourceId,targetId,newAction) {
    var item = this.getConn(sourceId, targetId);
    if(item) item.action = this.getAction(item.action, newAction);
}
diagram.prototype.addConn = function (conn) {
    var me = this;
    me._clist.push(conn);
}
diagram.prototype.existDeleteConn = function (sourceId, targetId) {
    var me = this, x = null;
    for (var i = 0; i < me._deleteClist.length; i++) {
        var item = me._deleteClist[i];
        if (sourceId == item.sourceId && targetId == item.targetId) {
            x = item;
            break;
        }
    }
    return !!x;
}
//isLogicDelete是否逻辑上的删除（修改节点时，是把连线先删除再新增）
diagram.prototype.deleteConn = function (sourceId, targetId) {
    var me = this;
    for (var i = 0; i < me._clist.length; i++) {
        var item = me._clist[i];
        if (sourceId == item.sourceId && targetId == item.targetId) {
            var item2 = me._clist.splice(i, 1);//删除
            if (item2.length >= 1) {
                //新增的不要放入此列表
                if (item2[0].action == 3) {//(isLogicDelete == undefined || isLogicDelete === true) &&
                    if (!me.existDeleteConn(sourceId, targetId)) {
                        me._deleteClist.push(item2[0]);
                    }
                }
            }
            return item2;
        }
    }
    return null;
}
diagram.prototype.getConn = function (sourceId, targetId) {
    var me = this;
    for (var i = 0; i < me._clist.length; i++) {
        var item = me._clist[i];
        if (sourceId == item.sourceId && targetId == item.targetId) {
            return item;
        }
    }
    return null;
}
diagram.prototype.setConn = function (newConn) {
    var currentConn = this.getNodeById(newConn.sourceId, newConn.targetId);
    for (var p in newConn) {
        currentConn[p] = newConn[p];
        //eval("currentNode." + p + "= newNode[p]");
    }
}
diagram.prototype.getConnList = function () {
    return this._clist;
}
diagram.prototype.changeId = function (srcId, newId) {
    var me = this;
    for (var i = 0; i < me._clist.length; i++) {
        var item = me._clist[i];
        if (srcId == item.sourceId) {
            item.sourceId = newId;
        } if (srcId == item.targetId) {
            item.targetId = newId;
        }
    }
}

diagram.prototype.getFullData = function () {
    var me = this;
    var obj = {
        node:  me._nlist.concat(me._deleteNlist),
        connection:me._clist.concat(me._deleteClist)
    }
    function clone(obj) {
        var o;
        switch (typeof obj) {
            case 'undefined': break;
            case 'string': o = obj + ''; break;
            case 'number': o = obj - 0; break;
            case 'boolean': o = obj; break;
            case 'object':
                if (obj === null) {
                    o = null;
                } else {
                    if (obj instanceof Array) {
                        o = [];
                        for (var i = 0, len = obj.length; i < len; i++) {
                            o.push(clone(obj[i]));
                        }
                    } else {
                        o = {};
                        for (var k in obj) {
                            o[k] = clone(obj[k]);
                        }
                    }
                }
                break;
            default:
                o = obj; break;
        }
        return o;
    }
    obj = clone(obj);
    for (var i = 0; i < obj.node.length; i++) {
        var item = obj.node[i];
        item.id = item.id.split(/temp/)[0];
    }
    for (var i = 0; i < obj.connection.length; i++) {
        var item = obj.connection[i];
        item.targetId = item.targetId.split(/temp/)[0];
        item.sourceId = item.sourceId.split(/temp/)[0];
    }
    return obj;  
}
diagram.prototype.clear = function () {
    this._nlist = [];
    this._clist = [];
    this._deleteNlist = [];
    this._deleteClist = [];
}

//流程图类
function flowDiagram(opt) {//flowaction,pc_hash
    this.loadUrl=opt.loadUrl;
    if (!this.loadUrl){
        this.loadUrl = "index.php?m=workflow&c=workflow&a=node_lists&ajax=1&flowaction="+flowaction+"&pc_hash="+pc_hash;
    }
    this.IsOnlyView=opt.IsOnlyView;
    //modify by PSHQ 2015.4.8
    //添加连线时，connection事件里用新的连线来替换
    this.IS_ADDNEW_CONNECTION = false;
    this.IS_DELTET_CONNECTION = true;
    //修改节点时候，节点id需要改变，它的临时序号
    this.TEMP_MODIFY_INDEX = 0;
    //新增节点时的临时ID
    this.TEMP_NODE_INDEX = 0;
    this._diagram = new diagram();
    this._instance = null;
    this._currentConn = null;//当前的连线
    this._isFixLastNode = false;//是否有结束节点
    this.load();
}
flowDiagram.prototype.Suspendedinit = function ($window) {
    var me = this;
    var instance = me._instance;
    instance.doWhileSuspended(function () {

        var isFilterSupported = instance.isDragFilterSupported();

        if (isFilterSupported) {
            instance.makeSource($window, {
                filter: ".ep",
                anchor: "Continuous",
                connector: ["StateMachine", { curviness: 0 }], // 设置为0度的贝塞尔曲线
                //connector : "Straight", //设置为直线
                connectorStyle: { strokeStyle: "#5c96bc", lineWidth: 2, outlineColor: "transparent", outlineWidth: 4 },
                maxConnections: 5,
                //enabled:false,
                isTarget: true,
                //链接超过未捕捉到
                onMaxConnections: function (info, e) {
                    hiAlert("超过最大连接数 (" + info.maxConnections + ") ", '提示');
                },
                //链接的事件未捕捉到
                beforeDrop: function (params) {
                    return confirm("确定要链接 " + params.sourceId + " 到 " + params.targetId + "的任务吗?");
                }
            });
        }
        else {
            if ($window == windows) {
                var eps = jsPlumb.getSelector(".ep");
            } else {
                var eps = jsPlumb.getSelector($window + " .ep");
            }
            for (var i = 0; i < eps.length; i++) {
                var e = eps[i], p = e.parentNode;
                instance.makeSource(e, {
                    parent: p,
                    anchor: "Continuous",
                    connector: ["StateMachine", { curviness: 0 }],
                    connectorStyle: { strokeStyle: "#5c96bc", lineWidth: 2, outlineColor: "transparent", outlineWidth: 4 },
                    isTarget: true,
                    //链接超过未捕捉到
                    onMaxConnections: function (info, e) {
                        alert("超过最大连接数 (" + info.maxConnections + ") ");
                    },
                    //链接的事件未捕捉到
                    beforeDrop: function (params) {
                        return confirm("确定要链接 " + params.sourceId + " 到 " + params.targetId + "的任务吗?");
                    }
                });
            }
        }
    });

    // initialise all '.w' elements as connection targets.
    instance.makeTarget($window, {
        dropOptions: { hoverClass: "dragHover" },
        anchor: "Continuous",
        allowLoopback: true
    });
}
//连线部分 流程表初始化
flowDiagram.prototype.jsPlumbReady = function (data) {
    var me = this;
    // setup some defaults for jsPlumb.
    var instance = jsPlumb.getInstance({
        Endpoint: ["Dot", { radius: 2 }], //设置连接点的形状为圆形，大小为2
        HoverPaintStyle: { strokeStyle: "#1e8151", lineWidth: 2 }, //鼠标经过连接点和元素的颜色
        ConnectionOverlays: [
            ["Arrow", {
                location: 1,
                id: "arrow",
                length: 14,
                foldback: 0.8
            }]
        ],
        Container: "statemachine-demo"
    });
    me._instance = instance;
    window.jsp = instance;
    var windows = jsPlumb.getSelector(".statemachine-demo .w");
    //拖动元素的初始化
//    instance.draggable(windows);
    instance.draggable(windows, {
        stop: function (op) {
            if (!!op.el) {
                var id = $(op.el).attr("id");
                var node = me._diagram.getNodeById(id);
                me._diagram.setNodeAction(id, 2);//节点位置移动后，标识为修改
            } 
        }
    });
    
    //初始化事件
    me._eventinit();
    me._eventinit2();
    me.Suspendedinit(windows);
    //创建连线
    var clist = data.connection;
    _.each(clist, function (item, i) {
        me.createConnection(item.sourceId, item.targetId);
        item.action = 0;
        me._diagram.addConn(item);
    });
    //modify by PSHQ 2015.4.8
    me.IS_ADDNEW_CONNECTION = true;
    jsPlumb.fire("jsPlumbDemoLoaded", instance);
}
//nodeListLen可以为空，表示不考虑nodeIndex==-1
flowDiagram.prototype._createNodeStr = function (item, nodeListLen) {
    var me = this;
    var templateResolver = Handlebars.compile($("#node-template").html());
    var isLastNode = item.nodeIndex == -1 && nodeListLen >= 1;
    if (isLastNode) item.nodeIndex = nodeListLen - 1;
    var nodeStr = templateResolver(item);
    if (isLastNode) item.nodeIndex = -1;
    return nodeStr;
}
flowDiagram.prototype._refreshNodeIndex = function () {
    var me = this;
    var len= me._diagram.getNodeList().length;
    for (var i = 0; i < len; i++) {
        var item = me._diagram.getNodeList()[i];
        var index = item.nodeIndex;
        if (index == -1) index = len - 1;
        $("#" + item.id).find(".ep").html(index);
    }
}
//modify by PSHQ 2015.4.8
//创建连线
flowDiagram.prototype.createConnection = function (sourceId, targetId) {
    var me = this;
    var instance = me._instance;
    var shape1= $("#" + sourceId).attr("data-shape");
    var shape2 = $("#" + targetId).attr("data-shape");
    var paramObj = { source: sourceId, target: targetId, anchors: [] };

    if (shape1 == "Circle") {
        paramObj.anchors.push(["Perimeter", { shape: shape1, rotation: null }])
    } else {
        paramObj.anchors.push("Continuous");
    }if (shape2 == "Circle") {
        paramObj.anchors.push(["Perimeter", { shape: shape2, rotation: null }])
    } else {
        paramObj.anchors.push("Continuous");
    }
    instance.connect(paramObj);
}
flowDiagram.prototype._getNodeInfo = function (data) {

}
//节点事件初始化
flowDiagram.prototype._eventinit = function () {
    var me = this;
    //绑定上事件节点选择切换
    $(".statemachine-demo .w").off("click").on("click", function (e) {
        //------modify by PSHQ 2015.5.11 单击连线时改变颜色
        if (me._currentConn) me._currentConn.setPaintStyle({ strokeStyle: "#5c96bc" });
        //-----End--------
        me._currentConn = null;
        var $w = $(".statemachine-demo .w");
        if ($w.hasClass("selectred")) {
            $w.removeClass("selectred");
        } else {
            //$("#nodeintro").slideUp("slow");
            //$("#nodeinfo").slideDown();
        }
        $("#nodeinfo").show();
        $("#conninfo").hide();
        $("#nodeintro").hide();
        $(this).addClass("selectred");

        var templateResolver = Handlebars.compile($("#nodeInfo-template").html());
        var node = me._diagram.getNodeById($(this).attr("id"));
        var htmlStr = templateResolver(node);

        $("#taskinfo").html(htmlStr);
        //modify by PSHQ 2015.2.13
        if ($(this).attr("Iscomplete") == "1") {
            $("#nodecontrol").hide()
        } else {
            $("#nodecontrol").show();
            if ($(this).attr("drag") == "1") {
                $("#dragnode").html("恢复拖动");
            } else {
                $("#dragnode").html("禁止拖动");
            }
        }
        e.stopPropagation();
        //jsPlumbUtil.consume(e);
    });
}
//其他事件初始化
flowDiagram.prototype._eventinit2 = function () {
    var me = this;
    var instance = me._instance;
    //添加节点事件
    $("#addnode").off("click").click(function (e) {
        var templateResolver = Handlebars.compile($("#nodeedit-template").html());
        var _body = templateResolver(null);
        hiBox(_body, '新增节点信息', 500, 350, '', '', function (r) {
            if (r == true) {
                me.addNode();
            }
        });
        e.stopPropagation();
    });

    //绑定上事件修改节点信息
    $("#updatenode").off("click").click(function (e) {
        var selectnode = jsPlumb.getSelector(".statemachine-demo .w.selectred");
        if (!!selectnode.length) {
            var id = $(selectnode).attr("id");
            var node = me._diagram.getNodeById(id);
            var templateResolver = Handlebars.compile($("#nodeedit-template").html());
            var _body = templateResolver(node);
            hiBox(_body, '修改节点信息', 500, 380, '', '', function (r) {
                if (r == true) {
                    me.modifyNode(node);
                }
            });
        } else {
            hiAlert("请选择要操作的节点", '提示');
        }
        e.stopPropagation();
    });

    //绑定上事件节点是否拖动
    $("#dragnode").off("click").click(function (e) {
        var selectnode = jsPlumb.getSelector(".statemachine-demo .w.selectred");
        if (!!selectnode.length) {
            if (!!parseInt($(selectnode).attr("drag"))) {
                $(selectnode).attr("drag", "0");
            } else {
                $(selectnode).attr("drag", "1");
            }
            var s = instance.toggleDraggable(selectnode);
            this.innerHTML = (s ? '禁止拖动' : '恢复拖动');
        } else {
            hiAlert("请选择要操作的节点", '提示');
        }
        e.stopPropagation();
    });
    //绑定上事件删除节点
    $("#deletenode").off("click").click(function (e) {
        var selectnode = jsPlumb.getSelector(".statemachine-demo .w.selectred");
        if (!!selectnode.length) {
            hiConfirm("确定是否要删除\" " + $(selectnode).find("span").text() + " \"节点吗?", '删除节点', function (r) {
                if (r == true) {
                    me.deleteNode();
                }
            });
        } else {
            hiAlert("请选择要操作的节点", '提示');
        }
        e.stopPropagation();
    });
    //绑定上事件删除节点连接
    $("#detachnode").off("click").click(function (e) {
        var selectnode = jsPlumb.getSelector(".statemachine-demo .w.selectred");
        if (!!selectnode.length) {
            hiConfirm("确定是否要删除\" " + $(selectnode).find("span").text() + " \"节点上的所有链接线吗?", '删除节点链接线', function (r) {
                if (r == true) {
                    instance.detachAllConnections(selectnode);
                }
            });
        } else {
            hiAlert("请选择要操作的节点", '提示');
        }
        e.stopPropagation();
    });
    $("#addprocedure").off("click").click(function (e) {
        hiConfirm("新建流程将覆盖当前数据，是否继续", '提示', function (r) {
            if (r == true) {
                me._diagram.clear();
                var nodeList = [
                    new node({ id: "NODE" + (me.TEMP_NODE_INDEX++), name: "开始", descp: "开始", left: 40, top: 20, action: 1, nodeIndex: 0, shape: "Circle" }),
                    new node({ id: "NODE" + (me.TEMP_NODE_INDEX++), name: "结束", descp: "结束", left: 40, top: 220, action: 1, nodeIndex: -1, shape: "Circle" })
                ];
                //创建节点
                var constr = "";
                for (var i = 0; i < nodeList.length; i++) {
                    var item = nodeList[i];
                    me._diagram.addNode(item);
                    constr += me._createNodeStr(item, nodeList.length);
                }
                $("#statemachine-demo").html(constr);
                me.jsPlumbReady({});
            }
        });
    });
   
    //绑定上事件导出节点以及关系链
    $("#updateconnect").off("click").click(function () {
        me.save("index.php?m=workflow&c=workflow&a=node_lists&ajax=1&flowaction="+flowaction+"&pc_hash="+pc_hash);
    })

    //绑定上事件导出节点以及关系链
    $("#instantiation").off("click").click(function () {
        hiConfirm("实例化前，请先保存工作流！实例化完成后，新发起的工作流将按当前配置流转！", '提示', function (r) {
            if (r == true) {
                me.instantiation();
            }
        });
    })

    //点击连线添加是否删除事件
    $("#deleteconn").off("click").click(function () {
        var conn = me._currentConn;
        if (conn == null) {
            hiAlert("请选择要操作的连线", '提示');
            return;
        }
        hiConfirm("确定是否要删除 \"" + $("#" + conn.sourceId).find("span").text() + "\" 到 \" " + $("#" + conn.targetId).find("span").text() + "\"节点的链接线吗?", '删除链接线', function (r) {
            if (r == true) {
                jsPlumb.detach(conn);
		me._currentConn = null;
            }
        });
    });
    //显示连线信息
    instance.bind("click", function (conn, originalEvent) {
        //modify by PSHQ 2015.5.11 单击连线时改变颜色
        if (me._currentConn) me._currentConn.setPaintStyle({ strokeStyle: "#5c96bc" });
        me._currentConn = conn;
        conn.setPaintStyle({ strokeStyle: "red" });
        //-----End--------
        var obj = me._diagram.getConn(conn.sourceId, conn.targetId);
        var templateResolver = Handlebars.compile($("#permission-template").html());
        var htmlstr = templateResolver(null);
        $("#permission").html(htmlstr);
        me.getOption(obj);
        $("#nodeinfo").hide();
        $("#conninfo").show();
        $("#nodeintro").hide();
        $(".statemachine-demo .w").removeClass("selectred");
    });

    instance.bind("connection", function (info) {
        //modify by PSHQ 2015.4.8 初始化时候为false，只有拖拽后生成连线时要执行，防止无限循环；因为拖拽生成的连线节点不会贴近图形边缘
        if (me.IS_ADDNEW_CONNECTION) {
            me.IS_DELTET_CONNECTION = false;
            instance.detach(info);
            me.IS_DELTET_CONNECTION = true;
            $("div._jsPlumb_endpoint._jsPlumb_endpoint_anchor_").remove();//_jsPlumb_hover
            me.IS_ADDNEW_CONNECTION = false;
            me.createConnection(info.sourceId, info.targetId);
            var conn = new connection({ sourceId: info.sourceId, targetId: info.targetId, action: 1 });
            me._diagram.addConn(conn);
            me.IS_ADDNEW_CONNECTION = true;
        }
    });
    instance.bind("connectionDetached", function (info, originalEvent) {
        if (me.IS_DELTET_CONNECTION) {
            me._diagram.setConnAction(info.sourceId, info.targetId, 3);
            me._diagram.deleteConn(info.sourceId, info.targetId);
        }
    });
    instance.bind("connectionMoved", function (info, originalEvent) {
        if (me.IS_DELTET_CONNECTION) {
            me._diagram.setConnAction(info.sourceId, info.targetId, 3);
            me._diagram.deleteConn(info.sourceId, info.targetId);
        }
    });
}
flowDiagram.prototype.getNodeFormUI = function () {
    var obj = {};
    var id = $.trim($("#nodeid").val());
    obj.id = id;
    obj.name = $.trim($("#nodeName").val());
    obj.descp = $.trim($("#nodeDescp").val());
    obj.nodeaction = $.trim($("#nodeAction").val());
    obj.shape = $('input:radio[name=shape]:checked').val();
    var s = new String();
    var temp = $("#" + id).css("left");
    obj.left = temp ? temp.substr(0, temp.length - 2) : 100;
    temp = $("#" + id).css("top");
    obj.top = temp ? temp.substr(0, temp.length - 2) : 20;
    return obj;
}

//加载
flowDiagram.prototype.load = function () {
    var me = this;
    $.ajax({
        type: "post",
        url: me.loadUrl,//"index.php?m=workflow&c=workflow&a=node_lists&ajax=1&flowaction="+flowaction+"&pc_hash="+pc_hash,
        dataType: 'json',
        success: function (data) {
            var obj=data;
            // 对返回的数据JSON化处理
            if (typeof r === "string") {
                var obj = JSON.parse(data);// toJson(data); //JSON.parse(data);    
            }
            var nodeList = obj.node;
            //创建节点
            var constr = "";
            for (var i = 0; i < nodeList.length; i++) {
                var item = nodeList[i];
                item.nodeIndex = i;
                item.action = 0;
                me._diagram.addNode(item);
                constr += me._createNodeStr(item, nodeList.length);
            }
            $("#statemachine-demo").html(constr);

            me.jsPlumbReady(obj);

        }, error: function () {
            alert("加载失败！");
        }
    });
}

//实例化流程
flowDiagram.prototype.instantiation = function () {
    var me = this;
    $.ajax({
        type: "get",
        url: "index.php?m=workflow&c=workflow&a=public_instantiation&flowaction="+flowaction+"&pc_hash="+pc_hash,
        dataType: 'html',
        async: false,
        success: function (data) {
            if(data == '1'){
                alert('实例化成功，新发起的工作流将按当前配置流转！');
            }
        }, error: function () {
            alert("实例化失败！");
        }
    });
}

//新增节点
flowDiagram.prototype.addNode = function () {
    var me = this, instance = me._instance;
    var uinode = me.getNodeFormUI();

    if (!!uinode.name && !!uinode.nodeaction) {
        hiAlertSu("创建节点成功", '提示', function () {
            var id = "NODE" + me.TEMP_NODE_INDEX++; //data.id;
            var item = new node(uinode);
            item.id = id;
            item.nodeIndex = me._diagram.getNewIndex();
            me._diagram.addNode(item);
            me._diagram.setNodeAction(id, 1);//标志为新增
            var htmlStr = me._createNodeStr(item);
            $("#statemachine-demo").prepend(htmlStr);
            me._refreshNodeIndex();
            //拖动元素的初始化
            var newid = jsPlumb.getSelector("#" + id);
            instance.draggable(newid);
            me.Suspendedinit(newid);
            me._eventinit();
        });
    } else {
        alert("请完善信息后提交")
    }
}
//修改节点
flowDiagram.prototype.deleteNode = function () {
    var me = this, instance = me._instance;

    var selectnode = jsPlumb.getSelector(".statemachine-demo .w.selectred");
    instance.detachAllConnections(selectnode);
    $(selectnode).remove();
    var id = $(selectnode).attr("id");
    me._diagram.setNodeAction(id, 3);//标志为删除
    me._diagram.deleteNodeById(id);
    me._refreshNodeIndex();
}
//删除节点
flowDiagram.prototype.modifyNode = function () {
    var me = this, instance = me._instance;
    var uinode = me.getNodeFormUI();

    //修改确认后，发起ajax 成功后修改页面节点信息
    //node.name = $.trim($("#update_name").val());
    me._diagram.setNode(uinode);
    me._diagram.setNodeAction(uinode.id, 2);//标志为修改
    hiAlertSu("修改节点信息成功", '提示', function () {
        //modify by PSHQ 2015.4.8
        //删除连线、节点然后重新创建，另外id与之前多了temp0（要不修改图形后连线不会贴近图形）（保存后再把temp0移除）
        //--Start--
        var id = uinode.id;// $(selectnode).attr("id");
        var thisConnections = [];//有关联的连线
        var connections = me._diagram.getConnList();
        for (var i = 0; i < connections.length; i++) {
            var item = connections[i];
            if (id == item.targetId) {
                thisConnections.push(item);
            } else if (id == item.sourceId) {
                thisConnections.push(item);
            }
        }

        me.IS_DELTET_CONNECTION = false;
        var selectnode = jsPlumb.getSelector(".statemachine-demo .w.selectred");
        instance.detachAllConnections(selectnode);
        $(selectnode).remove();
        me.IS_DELTET_CONNECTION = true;

        me.IS_ADDNEW_CONNECTION = false;
        var tempId = "temp" + (me.TEMP_MODIFY_INDEX++);
        tempId = id.split(/temp/)[0] + tempId;
        var node = me._diagram.getNodeById(id);
        node.id = tempId;
        $(selectnode).unbind();
        $(selectnode).remove();
        var nodehtml = me._createNodeStr(node);
        $("#statemachine-demo").prepend(nodehtml);

        //拖动元素的初始化
        var newid = $("#" + node.id).get(0);
        instance.draggable(newid);
        me.Suspendedinit(newid);
        me._eventinit();
        for (var i = 0; i < thisConnections.length; i++) {
            var item = thisConnections[i];
            if (id == item.targetId) {
                me.createConnection(item.sourceId, tempId);
            } else if (id == item.sourceId) {
                me.createConnection(tempId, item.targetId);
            }
        }
        //由于节点id修改了，连线的sourceId,targetId也要修改
        me._diagram.changeId(id, tempId);
        me.IS_ADDNEW_CONNECTION = true;
        //--End--
        $("#statemachine-demo").trigger("click");
        me._refreshNodeIndex();
    });
}
//保存连线、权限、位置信息
flowDiagram.prototype.save = function () {
    var me = this;
    var nodes = me._diagram.getNodeList();
    for (var i = 0; i < nodes.length; i++) {
        var item = nodes[i];
        var $item = $("#" + item.id);
        item.left = parseInt($item.css("left"));
        item.top = parseInt($item.css("top")); 
    }
    var out = me._diagram.getFullData();
    console.log(out);

    var postDataStr = JSON.stringify(out);
    $.ajax({
        type: "post",
        url: "index.php?m=workflow&c=workflow&a=node_lists&ajax=1&flowaction="+flowaction+"&pc_hash="+pc_hash,
        dataType: 'json',
        data: { "datastr": postDataStr },
        success: function (data) {
            var obj = data;
            if(data == '-1'){
            	alert('工作流不存在');
            }else if(data == '-2'){
            	alert('结点数错误');
            }else if(data == '-3'){
            	alert('结点信息错误');
            }else if(data == '-4'){
            	alert('结点action不唯一');
            }else if(data == '-5'){
            	alert('结点没有连线');
            }else if(data == '-6'){
            	alert('连线指向自身');
            }else if(data == '-7'){
            	alert('有回流');
            }else if(data == '-8'){
            	alert('起点，终点不唯一');
            }else if(data == '0'){
	            // 对返回的数据JSON化处理
	            
	            alert("保存成功！");
				window.location.reload();
            }else if (typeof data === "object") {
            	var str = '';
				for(var i = 0; i < data.length; i++){
					var node = me._diagram.getNodeById(data[i].sourceId);
					str += '开始节点：“'+node.name+'”';
					node = me._diagram.getNodeById(data[i].targetId);
					str += '，结束节点：“'+node.name+'”，';
					str += '请选择角色\r\n';
				}
				alert(str);
			}else{
	            alert("保存失败，请重试");
			}

        }, error: function () {
            alert("加载失败！");
        }
    });
}

flowDiagram.prototype.getOption = function (obj) {
    var me = this;
    var str = "";
    for (var i = 0; i < rolelist.length; i++) {
        var item=rolelist[i];
        str += "<option value='" + item.value + "' >" + item.name + "</>";
    }
    $("#roleIdlist").html(str);


    str = "";
    for (var i = 0; i < tablelist.length; i++) {
        var item = tablelist[i];
        str += "<option value='" + item.value + "' >" + item.name + "</>";
    }
    $("#formIdList").html(str);

    function selectValue(elId, values) {
        $("#" + elId).find("option").each(function () {
            var op = this;
            for (var i = 0; i < values.length; i++) {
                var v = values[i];
                if (op.value == v) {
                    op.selected = true;
                }
            }
        });
    }
    function getValues(elId) {
        var list = [];
        $("#" + elId).find("option").each(function () {
            var op = this;
            if (op.selected) {
                list.push(op.value);
            }
        });
        return list;
    }
    selectValue("roleIdlist", obj.roleId);
    selectValue("formIdList", obj.formIdList);
    var temp = obj.isAllowRollback;
    $("#isAllowRollback").attr("checked",temp);
    temp = obj.isAllowExist;
    $("#isAllowExist").attr("checked",temp);
    temp = obj.isCreate;
    $("#isCreate").attr("checked",temp);
    temp = obj.isRecycling;
    $("#isRecycling").attr("checked",temp);
    temp = obj.isCheck;
    $("#isCheck").attr("checked",temp);
    temp = obj.isAuth;
    $("#isAuth").attr("checked",temp);
    temp = obj.isReject;
    $("#isReject").attr("checked",temp);
    temp = obj.process_id;
    $("#process_id").val(temp);

    $("#roleIdlist,#formIdList,#isAllowRollback,#isAllowExist,#isCreate,#isRecycling,#isCheck,#isAuth,#isReject,#process_id").change(function () {
        me._diagram.setConnAction(obj.sourceId, obj.targetId, 2);
        obj.roleId = getValues("roleIdlist");
        obj.formIdList = getValues("formIdList");
        obj.isAllowRollback = $("#isAllowRollback").is(':checked');
        obj.isAllowExist = $("#isAllowExist").is(':checked');
        obj.isCreate = $("#isCreate").is(':checked');
        obj.isRecycling = $("#isRecycling").is(':checked');
        obj.isCheck = $("#isCheck").is(':checked');
        obj.isAuth = $("#isAuth").is(':checked');
        obj.isReject = $("#isReject").is(':checked');
        obj.process_id = $("#process_id").val();
    });
}


