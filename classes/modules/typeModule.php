<?php

require_once('abstractModule.php');
class TypeModule extends abstractModule{
  //Class module
  
  public function match($uri){
  	global $conf;
  	global $localUri;
  	global $uri;
  	global $acceptContentType;
  	global $endpoints;
  	global $lodspk;
  	
  	require_once($conf['home'].'classes/MetaDb.php');
  	$metaDb = new MetaDb($conf['metadata']['db']['location']);
  	
  	$pair = Queries::getMetadata($localUri, $acceptContentType, $metaDb);

  	if($pair == NULL){ // Original URI is not in metadata
  	  if(Queries::uriExist($uri, $endpoints['local'])){
  	  	$page = Queries::createPage($uri, $localUri, $acceptContentType, $metaDb);
  	  	if($page == NULL){
  	  	  HTTPStatus::send500("Can't write sqlite database.");
  	  	}
  	  	if($conf['debug']){
  	  	  Logging::log("URI found, redirecting to ".$page);
  	  	}
  	  	HTTPStatus::send303($page, $acceptContentType);
  	  	exit(0);
  	  }else{
  	  	return false;
  	  }
  	}
  	return $pair;
  }
  
  public function execute($pair){
  	global $conf;
  	global $localUri;
  	global $uri;
  	global $acceptContentType;
  	global $endpoints;
  	global $lodspk;
  	global $results;
  	global $firstResults;
  	list($res, $page, $format) = $pair;
  	//If resource is not the page, send a 303 to the document
  	if($res == $localUri){
  	  HTTPStatus::send303($page, $acceptContentType);
  	}
  	
  	$uri = $res;
  	if($conf['mirror_external_uris'] != false){
  	  $localUri = preg_replace("|^".$conf['ns']['local']."|", $conf['basedir'], $res);
  	}
  	
  	$extension = Utils::getExtension($format); 
  	
  	/*Redefine Content type based on the
  	* dcterms:format for this page
  	*/
  	$acceptContentType = $format;
  	//Check if files for model and view exist
  	$t=Queries::getClass($uri, $endpoints['local']);
  	
  	$obj = $this->getModelandView($t, $extension); 
  	$modelFile = $obj['modelFile'];
  	$lodspk['model'] = $conf['model']['directory'];  	
  	$viewFile = $obj['viewFile'];
  	$lodspk['view'] = $obj['view']['directory'];
  	if($viewFile == null){
  	  $lodspk['transform_select_query'] = true;
  	}
  	
  	$lodspk['type'] = $modelFile;
  	$lodspk['home'] = $conf['basedir'];
  	$lodspk['baseUrl'] = $conf['basedir'];
  	$lodspk['module'] = 'type';
  	$lodspk['root'] = $conf['root'];
  	$lodspk['contentType'] = $acceptContentType;
  	$lodspk['ns'] = $conf['ns'];
  	$lodspk['endpoint'] = $conf['endpoint'];
  	$lodspk['view'] = $conf['view']['directory'];
  	
  	$lodspk['add_mirrored_uris'] = true;
  	$lodspk['this']['value'] = $uri;
  	$lodspk['this']['curie'] = Utils::uri2curie($uri);
  	$lodspk['local']['value'] = $localUri;
  	$lodspk['local']['curie'] = Utils::uri2curie($localUri);
   	$lodspk['this']['extension'] = $extension;
  	//chdir($conf['home'].$conf['model']['directory']);
  	
  	Utils::queryFile($modelFile, $endpoints['local'], $results, $firstResults);
    if(!$lodspk['resultRdf']){
  	  $results = Utils::internalize($results); 
  	  $firstAux = Utils::getfirstResults($results);
  	  
  	  //chdir($conf['home']);
  	  if(is_array($results)){
  	  	$resultsObj = Convert::array_to_object($results);
  	  	$results = $resultsObj;
  	  }else{
  	  	$resultsObj = $results;
  	  }
  	  $lodspk['firstResults'] = Convert::array_to_object($firstAux);
  	}else{
  	  $resultsObj = $results;
  	}
  	//chdir($conf['home'].$conf['model']['directory']);
  	Utils::processDocument($viewFile, $lodspk, $resultsObj);
  	
  }
  
  private static function getModelandView($t, $extension){  	
  	global $conf;
  	global $results;
  	global $rPointer;
  	global $lodspk;
  	$objResult = array('modelFile' => null, 'viewFile' => null);
  	//Defining default views and models
  	$curieType="";
 	//Get the firstResults type available
  	$typesAndValues = array('rdfs:Resource' => -1);
  	$typesAndValues = array('rdfs__Resource' => -1);
  	if(!isset($conf['disableComponents']) || $conf['disableComponents'] != true){
  	  foreach($t as $v){
  	  	$curie = Utils::uri2curie($v);
  	  	$typesAndValues[$curie] = 0;
  	  	$typesAndValues[str_replace(":", "__", $curie)] = 0;
  	  	if(isset($conf['type']['priority'][$curie]) && $conf['type']['priority'][$curie] >= 0){
  	  	  $typesAndValues[$curie] = $conf['type']['priority'][$curie];
  	  	  $typesAndValues[str_replace(":", "__", $curie)] = $conf['type']['priority'][$curie];
  	  	}
  	  }
  	}
  	arsort($typesAndValues);
  	$extensionView = $extension.".";
  	$extensionModel = '';
  	if($extension != 'html'){
  	  $extensionModel = $extension.'.';
  	}
  	foreach($typesAndValues as $v => $w){
      $auxViewFile  = $conf['home'].$conf['view']['directory'].'/'.$conf['type']['prefix'].'/'.$v.'/'.$extension.'.template';
  	  $auxModelFile = $conf['home'].$conf['model']['directory'].'/'.$conf['type']['prefix'].'/'.$v.'/'.$extension.'.queries';
  	  if($v == null){continue;}
  	  $lodspk['componentName'] = $v;
  	  if(file_exists($auxModelFile)){
  	  	$objResult['modelFile'] = $auxModelFile;//$conf['type']['prefix'].'/'.$v.'/'.$extensionModel.'queries';
  	  	if(file_exists($auxViewFile)){
  	  	  $objResult['viewFile'] = $auxViewFile;//$conf['type']['prefix'].'/'.$v.'/'.$extensionView.'template';
  	  	}elseif($extension != 'html'){ //View doesn't exists (and is not HTML)
  	  	  $objResult['viewFile'] = null;
  	  	}
  	  	break;//return $objResult;
  	  }elseif(file_exists($conf['home'].$conf['model']['directory'].'/'.$conf['type']['prefix'].'/'.$v.'/queries')){
  	  	$objResult['modelFile'] = $conf['home'].$conf['model']['directory'].'/'.$conf['type']['prefix'].'/'.$v.'/queries';
  	  	if(file_exists($auxViewFile) ){
  	  	  $objResult['viewFile'] = $auxViewFile;
  	  	}else{
  	  	  $lodspk['transform_select_query'] = true;
  	  	  $objResult['viewFile'] = null;
  	  	}
  	  	if($conf['debug']){
  	  	  Logging::log("LODSPeaKr can't find the proper query. Using HTML query instead.", E_USER_NOTICE);
  	  	}
  	  	break;
  	  }else{
  	    $found = false;
  	    if(sizeof($conf['components']['types'])>0){
  	      foreach($conf['components']['types'] as $type){
  	        $typeArray = explode("/", $type);
  	        $typeName = end($typeArray);
  	        if($v == $typeName && file_exists($type)){
  	          array_pop($typeArray);
  	          $conf['type']['prefix'] = array_pop($typeArray);
  	          $conf['model']['directory'] = join("/", $typeArray);
  	          $conf['view']['directory'] = $conf['model']['directory'];
  	          $lodspk['model'] = $conf['model']['directory'].'/'.$conf['type']['prefix'].'/'.$typeName.'/queries';
  	          $lodspk['view'] = $conf['view']['directory'].'/'.$conf['type']['prefix'].'/'.$typeName.'/'.$extension.'.template';
  	          $objResult['viewFile'] = $lodspk['view'];
  	          $objResult['modelFile'] = $lodspk['model'];
  	          $found = true;
  	          if(!file_exists($objResult['viewFile']) ){
  	            $lodspk['transform_select_query'] = true;
  	            $objResult['viewFile'] = null;
  	          }
  	          return $objResult;
  	        }
  	      }
  	    }
  	    if($found){break;}
  	  }
  	  /*if($objResult['viewFile'] == null && $extensionView == 'html'){
  	  $objResult['viewFile'] = 'html.template';
  	  }*/
  	}
  	return $objResult;
  }
  
}
?>
