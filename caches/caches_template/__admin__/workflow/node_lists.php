<?php defined('IN_PHPCMS') or exit('No permission resources.'); ?><?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header', 'admin');
?>
<style>
#demo {
  margin-top:5em;
}

.w {  
  padding:10px 24px 10px 16px;
  position:absolute;
  border: 1px solid black;
  z-index:4;
  border-radius:1em;
  border:1px solid #2e6f9a;
  box-shadow: 2px 2px 19px #e0e0e0;
  -o-box-shadow: 2px 2px 19px #e0e0e0;
  -webkit-box-shadow: 2px 2px 19px #e0e0e0;
  -moz-box-shadow: 2px 2px 19px #e0e0e0;
  -moz-border-radius:8px;
  border-radius:8px;
  opacity:0.8;
  filter:alpha(opacity=80);
  cursor:move;
  background-color:white;
  font-size:12px;
  -webkit-transition:background-color 0.25s ease-in;
  -moz-transition:background-color 0.25s ease-in;
  transition:background-color 0.25s ease-in;  
  color:black;
}

.w:hover {
  background-color: #5c96bc;
  color:white;

}

.aLabel {
  -webkit-transition:background-color 0.25s ease-in;
  -moz-transition:background-color 0.25s ease-in;
  transition:background-color 0.25s ease-in;
}

.aLabel._jsPlumb_hover, ._jsPlumb_source_hover, ._jsPlumb_target_hover {
  background-color:#1e8151;
  color:white;
}

.aLabel {
  background-color:white;
  opacity:0.8;
  padding:0.3em;        
  border-radius:0.5em;
  border:1px solid #346789;
  cursor:pointer;
}

.ep {
  position:absolute;
  bottom: 37%;
  right: 5px;
  width:1em;
  height:1em;
  background-color:orange;
  cursor:pointer;
  box-shadow: 0px 0px 2px black;
  -webkit-transition:-webkit-box-shadow 0.25s ease-in;
  -moz-transition:-moz-box-shadow 0.25s ease-in;
  transition:box-shadow 0.25s ease-in;
}

.ep:hover {
  box-shadow: 0px 0px 6px black;
}

.statemachine-demo ._jsPlumb_endpoint {
  z-index:3;
}
.dragHover { border:2px solid orange; }
path { cursor:pointer; }

.wt1{background:#bfd1eb}
.wt2{background:#8CBF26;}
.wt4{background:#d3d3d3;}
.wt8{background:#F09609;}
</style>
        <script src="<?php echo JS_PATH?>jsplumb/external/jquery-1.9.0-min.js"></script>
        <script src="<?php echo JS_PATH?>jsplumb/external/jquery-ui-1.9.2.min.js"></script>
        <script src="<?php echo JS_PATH?>jsplumb/external/jquery.ui.touch-punch-0.2.2.min.js"></script>

    <!-- JS -->    
    <!-- support lib for bezier stuff -->    
    <script src="<?php echo JS_PATH?>jsplumb/lib/jsBezier-0.6.js"></script>
    <!-- geom functions -->    
    <script src="<?php echo JS_PATH?>jsplumb/lib/biltong-0.2.js"></script>
    <!-- jsplumb util -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/util.js"></script>
    <script src="<?php echo JS_PATH?>jsplumb/src/browser-util.js"></script>
    <!-- base DOM adapter -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/dom-adapter.js"></script>
    <!-- main jsplumb engine -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/jsPlumb.js"></script>
    <!-- endpoint -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/endpoint.js"></script>
    <!-- connection -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/connection.js"></script>
    <!-- anchors -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/anchors.js"></script>
    <!-- connectors, endpoint and overlays  -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/defaults.js"></script>
    <!-- bezier connectors -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/connectors-bezier.js"></script>
    <!-- state machine connectors -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/connectors-statemachine.js"></script>
    <!-- SVG renderer -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/renderers-svg.js"></script>
    <!-- vml renderer -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/renderers-vml.js"></script>
    <!-- jquery jsPlumb adapter -->    
    <script src="<?php echo JS_PATH?>jsplumb/src/jquery.jsPlumb.js"></script>
    <!-- /JS -->

<div class="content-menu  ib-a blue" style="position:absolute;top:50px;right:20px;text-align:right">
<a class="add fb" href="javascript:">
<em>保存修改</em>
</a>
</div>

<div class="demo statemachine" id="statemachine">
<!--
<?php $n=1;if(is_array($nodes)) foreach($nodes AS $node) { ?>
    <div class="w wt<?php echo $node['nodetype'];?>" id="node_<?php echo $node['id'];?>" style="left:<?php echo $n%2?10:30;?>em;top:<?php echo $n*6;?>em;"><?php echo $node['nodename'];?><div class="ep"></div></div>
<?php $n++;}unset($n); ?>
-->
</div>

<script>
var routes=<?php echo json_encode($routes)?>;
var nodes=<?php echo json_encode($nodes)?>;

jsPlumb.ready(function() {
  var i=1;
  for(var n in nodes){
    html='<div class="w wt'+nodes[n]['nodetype']+'" id="node_'+nodes[n]['id']+'" style="left:'+(i%2?10:30)+'em;top:'+(i*6)+'em;">'+nodes[n]['nodename']+'<div class="ep"></div></div>';
    $('#statemachine').append(html);
    ++i;
  }

  // setup some defaults for jsPlumb.
  var instance = jsPlumb.getInstance({
    Endpoint : ["Dot", {radius:2}],
    HoverPaintStyle : {strokeStyle:"#1e8151", lineWidth:2 },
    ConnectionOverlays : [
      [ "Arrow", {location:1,id:"arrow",length:14,foldback:0.8} ],
      [ "Label", { label:"路由", id:"label", cssClass:"aLabel" }]
    ],
    Container:"statemachine"
  });

  var windows = jsPlumb.getSelector(".statemachine .w");

    // initialise draggable elements.
  instance.draggable(windows);

  instance.bind("click", function(c) {
    //instance.detach(c); 
    var sid=c.sourceId.substring(5);
    var tid=c.targetId.substring(5);
    var route=0;
    for(var r in routes){
      if(routes[r]['nodeid']==sid && routes[r]['next_nodeid']==tid){
        route=routes[r];
        break;
      }
    }
    route_edit(route['id'],route['nodeid'],route['next_nodeid']);
  });

  
  instance.bind("connection", function(info) { 
    var title='';
    var sid=info.sourceId.substring(5);
    var tid=info.targetId.substring(5);
    if(sid==tid){
      alert('路由起始节点不正确');
      instance.detach(info);
      return;
    } 
    for(var r in routes){
      if(routes[r]['nodeid']==sid && routes[r]['next_nodeid']==tid){
        var route=routes[r];
        if(route['routename']!=null && route['routename']!=''){
          title=route['routename'];
        }else{
          title='路由'+route['id'];
        }
        break;
      }
    }
    if(title==''){//标题为空时即为刚添加
      route_edit(0,info.sourceId.substring(5),info.targetId.substring(5));
    }else{
      info.connection.getOverlay("label").setLabel(title); 
    }
  });


  // suspend drawing and initialise.
  instance.doWhileSuspended(function() {
    var isFilterSupported = instance.isDragFilterSupported();
   
    if (isFilterSupported) {
      instance.makeSource(windows, {
        filter:".ep",
        anchor:"Continuous",
        connector:[ "StateMachine", { curviness:20 } ],
        connectorStyle:{ strokeStyle:"#5c96bc", lineWidth:2, outlineColor:"transparent", outlineWidth:4 },
        maxConnections:5,
        onMaxConnections:function(info, e) {
          alert("Maximum connections (" + info.maxConnections + ") reached");
        }
      });
    }else {
      var eps = jsPlumb.getSelector(".ep");
      for (var i = 0; i < eps.length; i++) {
        var e = eps[i], p = e.parentNode;
        instance.makeSource(e, {
          parent:p,
          anchor:"Continuous",
          connector:[ "StateMachine", { curviness:20 } ],
          connectorStyle:{ strokeStyle:"#5c96bc",lineWidth:2, outlineColor:"transparent", outlineWidth:4 },
          maxConnections:5,
          onMaxConnections:function(info, e) {
            alert("Maximum connections (" + info.maxConnections + ") reached");
          }
        });
      }
    }
  });

  // initialise all '.w' elements as connection targets.
  instance.makeTarget(windows, {
    dropOptions:{ hoverClass:"dragHover" },
    anchor:"Continuous",
    allowLoopback:false,
    anchor:"Continuous"
  });


  for(var r in routes){
    instance.connect({ source:"node_"+routes[r]['nodeid'], target:"node_"+routes[r]['next_nodeid'] });
  }

  jsPlumb.fire("jsPlumbDemoLoaded", instance);

});


function route_edit(id,sid,tid){
  window.top.art.dialog({id:'route_edit'}).close();
  window.top.art.dialog(
    {title:'路由编辑',id:'route_edit',iframe:'?m=workflow&c=workflow&a=route_edit&id='+id+'&sid='+sid+'&tid='+tid,width:'500',height:'300'}, 
    function(){var d = window.top.art.dialog({id:'route_edit'}).data.iframe;d.document.getElementById('dosubmit').click();return false;}, 
    function(){window.top.art.dialog({id:'route_edit'}).close()
  });
}
function node_add(action,name,type){
  _node_add({'action':action,'nodename':name,'nodetype':type,'id':'t'+Math.random()});
}

function _node_add(node){
  $('#statemachine').append('<div class="w wt'+node['nodetype']+'" id="node_'+node['id']+'" style="left:50em;top:10em;">'+node['nodename']+'<div class="ep"></div></div>');

}
    </script>

    
</body>
</html>