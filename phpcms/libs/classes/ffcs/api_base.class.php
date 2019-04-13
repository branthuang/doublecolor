<?php
abstract class api_base{
    protected $config;
    protected $act_config;


    public  function __construct(){
        $this->config=pc_base::load_config('appbase','oldapi');
    }
    
    //获取非转义的GET、POST
    protected static function getParam($name,$m='post'){
        return trim(stripcslashes($m=='post' ? $_POST[$name] : $_GET[$name]));
    }
    
    public function vailduser($user){
        $info=$this->getuser($user); 
        
        if(!is_array($info) ) return false;
        
        //TODO 动作权限判断，暂时没空实现，默认都有权限
        return true;
    }
    
    public function getpwd($user){
        $info=$this->getuser($user);        
        return is_array($info) ? $info['pwd'] : false;
    }
    
    private function getuser($user){ 
        if(empty($user) || !isset($this->config['users'][$user])) return false;
            
        return $this->config['users'][$user];
    }
    
    public function execute(){
        $act=strtolower(trim($_GET['a']));
        if(!empty($act) && method_exists($this, $act)){
            $this->act_config=$this->config['config'][substr(get_class($this),0,-4).'.'.$act];
            $this->$act();
        }
    }
    
    
    protected function xml2string($key,$node){
        if(is_array($node)){
            $xml='<'.$key;
            $str='';
            foreach ($node as $k => $leaf) { 
                if($k{0}=='@'){                    
                    $str.=$this->xml2string(substr($k,1), $leaf);
                }else if(substr($k,0,2)=='__'){
                    $xml.=' '.substr($k,2).'="'.$leaf.'"';
                }else if(is_numeric($k)){
                    $xml='';
                    $str.=$this->xml2string($key, $leaf);
                }else{
                    $str.=$this->xml2string($k, $leaf);
                }
            }
            return $xml ? $xml.'>'.$str.'</'.$key.'>' : $str;
            
        }else{
            return $key ? '<'.$key.'>'.(is_numeric($node) || empty($node)?$node:'<![CDATA['.$node.']]>').'</'.$key.'>' : $node;
        }
    }
    protected function send_response($nodes)
    {
        header('Content-type: application/xhtml+xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo $this->xml2string('ResponseBody', $nodes);
        exit;
    }
	
	protected function jump_response($url,$nodes,$key='ResponseBody',$param_name='ResponseValue'){
		$returnurl = $url . (strpos($url, '?') === false ? '?' : '&');
		$returnurl .= $param_name.'=' . urlencode( $this->xml2string($key, $nodes));
		header('location:' . $returnurl);
		exit;
	}
}
?>