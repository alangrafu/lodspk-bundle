<?php

require_once('abstractModule.php');
class AdminModule extends abstractModule{
  //Service module
     private $head = "<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='utf-8'>
    <title>LODSPeaKr Basic Menu</title>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='description' content=''>
    <meta name='author' content=''>
    <link href='../css/bootstrap.min.css' rel='stylesheet' type='text/css' media='screen' />
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
      .wait{
        background-image:url('img/wait.gif');
        background-repeat:no-repeat;
        padding-right:20px;
        background-position: right;
      }
    </style>
    <link href='../css/bootstrap-responsive.min.css' rel='stylesheet' type='text/css' media='screen' />
    <script type='text/javascript' src='../js/jquery.js'></script>
    <script type='text/javascript' src='../js/bootstrap.min.js'></script>
    <script type='text/javascript' src='../js/bootstrap-typeahead.js'></script>
    <script type='text/javascript'>
    $(document).ready(function(){
        $('.typeahead').typeahead({
            source: function (typeahead, query) {
              $('.typeahead').addClass('wait');[]
              return $.get('search/'+encodeURIComponent(query), { }, function (data) {
                  $('.typeahead').removeClass('wait');[]
                  return typeahead.process(data);
              }, 'json');
            },
            onselect: function (obj) {
              $('.typeahead').attr('disabled', true);
              window.location = obj.uri;
            }
        });
    });
    </script>
  </head>
  <body>
 <div class='navbar navbar-fixed-top'>
      <div class='navbar-inner'>
        <div class='container'>
          <a class='btn btn-navbar' data-toggle='collapse' data-target='.nav-collapse'>
            <span class='icon-bar'></span>
            <span class='icon-bar'></span>
            <span class='icon-bar'></span>
          </a>
          <a class='brand' href='../admin'>LODSPeaKr Admin Menu</a>
          <div class='nav-collapse'>
            <ul class='nav'>
              <li class='active'><a href='../admin'>Home</a></li>
            </ul>
            <form class='navbar-search pull-left' action=''>
              <input type='text' data-provide='typeahead' class='typeahead search-query span2' placeholder='Search'/>
            </form>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class='container'>
      <img src='img/lodspeakr_logotype.png' style='opacity: 0.1; position: absolute; right:0px; top:60%'/>
";
  private $foot ="    </div>
  </body>
</html>
";


  public function match($uri){
    global $localUri;
    global $conf;
  	$q = preg_replace('|^'.$conf['basedir'].'|', '', $localUri);
  	$qArr = explode('/', $q);
  	if(sizeof($qArr)==0){
  	  return FALSE;
  	}
  	if($qArr[0] == "admin"){
  	  return $qArr;
  	}  	
  	return FALSE;  
  }
  
  public function execute($params){
  	global $conf;
  	global $localUri;
  	global $uri;
  	global $acceptContentType;
  	global $endpoints;
  	global $lodspk;
  	global $firstResults;
  	if(!$this->auth()){
  	  HTTPStatus::send401("Forbidden\n");
  	  exit(0);
  	}
  	if(sizeof($params) == 1){
  	  header( 'Location: admin/menu' ) ;
  	  exit(0);
  	}
  	if($params[1] == ""){
  	  header( 'Location: menu' ) ;
  	  exit(0);
  	}
  	if(sizeof($params) == 2){
  	  if($params[1] == "menu"){
  	    $this->homeMenu();
  	  }
  	  if($params[1] == "start"){
  	    $this->startEndpoint();
  	  }
  	  if($params[1] == "stop"){
  	    $this->stopEndpoint();
  	  }
  	  if($params[1] == "load"){
  	    $this->loadRDF();
  	  }
  	  if($params[1] == "remove"){
  	    $this->deleteRDF();
  	  }
  	  HTTPStatus::send404($params[1]);
  	}  	
  }
  
  protected function loadRDF(){
    global $conf;
    if($_SERVER['REQUEST_METHOD'] == 'GET'){
      echo $this->head."
      <form action='load' method='post'
      enctype='multipart/form-data'>
      <legend>Load RDF into the endpoint</legend>
      <label for='file'>RDF file</label>
      <input type='file' name='file' id='file' />
      <span class='help-block'>LODSPeaKr accepts RDF/XML, Turtle and N-Triples files</span>
      <label for='file'>Named graph</label>
      <input type='text' name='namedgraph' id='namedgraph' />
      <span class='help-block'>The named graph where the RDF will be stored (optional).</span>
      <br />
      <button type='submit' class='btn'>Submit</button></form>
      ".$this->foot;
    }elseif($_SERVER['REQUEST_METHOD'] == 'POST'){
      if ($_FILES["file"]["error"] > 0){
        HTTPStatus::send409("No file was included in the request");
      }else{
        $ng = (isset($_POST['namedgraph']))?$_POST['namedgraph']:'default';
        
        require_once($conf['home'].'lib/arc2/ARC2.php');          	        
        $parser = ARC2::getRDFParser();
        $parser->parse($_FILES["file"]["tmp_name"]);
        $triples = $parser->getTriples();
        if(sizeof($triples) > 0){
          $c = curl_init();          
          $body = $parser->toTurtle($triples);
          $fp = fopen('php://temp/maxmemory:256000', 'w');
          if (!$fp) {
            die('could not open temp memory data');
          }
          fwrite($fp, $body);
          fseek($fp, 0); 
          
          curl_setopt($c, CURLOPT_URL, $conf['updateendpoint']['local']."?graph=".$ng);
          curl_setopt($c, CURLOPT_CUSTOMREQUEST, "PUT");
          curl_setopt($c, CURLOPT_PUT, 1);
          curl_setopt($c, CURLOPT_BINARYTRANSFER, true);
          curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($c, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT',"Content-Type: text/turtle"));
          curl_setopt($c, CURLOPT_USERAGENT, "LODSPeaKr version ".$conf['version']);
          curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($c, CURLOPT_INFILE, $fp); // file pointer
          curl_setopt($c, CURLOPT_INFILESIZE, strlen($body)); 
          curl_exec($c); // execute the curl command 
          $http_status = curl_getinfo($c, CURLINFO_HTTP_CODE);
          if(intval($http_status)>=200 && intval($http_status) <300){
             echo $this->head."<h2>Success!!</h2><div class='alert alert-success'>The file ".$_FILES["file"]["name"]." (".$_FILES["file"]["size"]." bytes, ".sizeof($triples)." triples) was stored successfully on $ng.</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot;
          }else{
            HTTPStatus::send502($this->head."<h2>Error!!</h2><div class='alert alert-success'>The file ".$_FILES["file"]["name"]." couldn't be loaded into the triples store. The server was acting as a gateway or proxy and received an invalid response (".$http_status.") from the upstream server</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot);
          }
          curl_close($c);
          fclose($fp);

        }else{
          HTTPStatus::send409($this->head."<h2>Error!!</h2><div class='alert alert-error'>The file was not a valid RDF document.</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot);
        }
      }
    }else{
      HTTPStatus::send405($_SERVER['REQUEST_METHOD']);
    }
    exit(0);
  }

  protected function deleteRDF(){
    global $conf;
    if($_SERVER['REQUEST_METHOD'] == 'GET'){
      echo $this->head."
      <form action='remove' method='post'
      enctype='multipart/form-data'>
      <legend>Remove a Named Graph containing RDF from the endpoint</legend>
      <label for='file'>Named graph</label>
      <input type='text' name='namedgraph' id='namedgraph' />
      <span class='help-block'>The named graph where the RDF is stored.</span>
      <br />
      <button type='submit' class='btn'>Submit</button></form>
      ".$this->foot;
    }elseif($_SERVER['REQUEST_METHOD'] == 'POST'){
      $ng = (isset($_POST['namedgraph']))?$_POST['namedgraph']:'default';
      $c = curl_init();          
      curl_setopt($c, CURLOPT_URL, $conf['updateendpoint']['local']."?graph=".$ng);
      curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
      curl_setopt($c, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($c, CURLOPT_USERAGENT, "LODSPeaKr version ".$conf['version']);
      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
      curl_exec($c); // execute the curl command 
      $http_status = curl_getinfo($c, CURLINFO_HTTP_CODE);
      if(intval($http_status)>=200 && intval($http_status) <300){
        echo $this->head."<h2>Success!!</h2><div class='alert alert-success'>The named graph $ng was removed successfully</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot;
      }else{
        HTTPStatus::send502($this->head."<h2>Error!!</h2><div class='alert alert-error'>The named graph $ng couldn't be removed from the endpoint. The server was acting as a gateway or proxy and received an invalid response (".$http_status.") from the upstream server</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot);
      }
      curl_close($c);
      
    }else{
      HTTPStatus::send405($_SERVER['REQUEST_METHOD']);
    }
    exit(0);
  }

  
  protected function startEndpoint(){
    $return_var = 0;
    exec ("utils/modules/start-endpoint.sh", &$output, $return_var);  
    if($return_var == 0){
      echo $this->head ."<div class='alert alert-success'>Endpoint starter successfully</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot;
    }else{
      echo $this->head ."<div class='alert alert-error'>Error: /tmp/fusekiPid already exists. This probably means Fuseki is already running. You could also try to <a href='stop'>stop</a> the endpoint first.</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot;
    }
  }

  protected function stopEndpoint(){
    $return_var = 0;
    exec ("utils/modules/stop-endpoint.sh", &$output, $return_var);  
    if($return_var == 0){
      echo $this->head ."<div class='alert alert-success'>Endpoint stopped successfully</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot;
    }else{
      echo $this->head ."<div class='alert alert-error'>Error: Something went wrong. Are you sure the endpoint is running?</div><div class='alert'>You can now return to the <a href='menu'>home menu</a>.</div>".$this->foot;
    }
  }
  
  protected function homeMenu(){
    $output = array();
    exec ("utils/modules/test-endpoint.sh", $output, $return_var);
    $msg = "<div class='alert alert-success'>Endpoint running</div>";
    if($return_var != 0)
      $msg = "<div class='alert alert-error'>Endpoint stopped</div>";
    echo $this->head."
      <div class='span6 well'>
      <h2>Triplestore (Fuseki)</h2>
      <p>You can do several operations on this page:</p>
      <ul>
        <li>You can <a href='start'>start</a> and <a href='stop'>stop</a> the triple store.</li>
        <li>You can <a href='load'>load</a> the triple store using an existing RDF document.</li>
        <li>You can <a href='remove'>remove</a> RDF data from a named graph.</li>    
      </ul>
      </div>
      <div class='span4 well'>
      <h2>Endpoint status</h2>
      ".$msg."      </div>".$this->foot;

  }
  
  protected function auth(){    
    $realm = 'Restricted area';    
    //user => password
    $users = array('admin' => 'admin', 'guest' => 'guest');
    if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
      header('HTTP/1.1 401 Unauthorized');
      header('WWW-Authenticate: Digest realm="'.$realm.
        '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
      
      die('Text to send if user hits Cancel button');
    }
    // analyze the PHP_AUTH_DIGEST variable
    if (!($data = $this->http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) ||
      !isset($users[$data['username']]))
      return FALSE;
    //die('Wrong Credentials!');
    // generate the valid response
    $A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
    $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
    $valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
    
    if ($data['response'] != $valid_response)
      return FALSE;
//      die('Wrong Credentials!');
    
    // ok, valid username & password
    //echo 'You are logged in as: ' . $data['username'];
    return TRUE;
    
  }
  
  // function to parse the http auth header
  protected function http_digest_parse($txt)
  {
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));
    
    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $m) {
      $data[$m[1]] = $m[3] ? $m[3] : $m[4];
      unset($needed_parts[$m[1]]);
    }
    
    return $needed_parts ? false : $data;
  }
  
}
?>
