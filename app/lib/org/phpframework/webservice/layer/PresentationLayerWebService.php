<?php
include_once get_lib("org.phpframework.webservice.layer.LayerWebService");

class PresentationLayerWebService extends LayerWebService {
	
	public function callWebService() {
		launch_exception( new Exception("You cannot call the callWebService in the PresentationLayerWebService, because is not a regular web service!\n Instead you should call: PresentationLayerWebService->callWebServicePage(\$presentation_id, \$external_vars, \$includes, \$includes_once);") );
		return null;
	}
	
	public function callWebServicePage() {
		$html = "";
		$presentation_id = $url = $external_vars = $includes = $includes_once = false;
		
		//This is to check if PresentationLayer webservice is working.
		if ($this->url == "_is_presentation_webservice") {
			echo 1; 
			die();
		}
		
		if ($this->settings) {
			$presentation_id = isset($this->settings["presentation_id"]) ? $this->settings["presentation_id"] : null;
			$external_vars = isset($this->settings["external_vars"]) ? $this->settings["external_vars"] : null;
			$includes = isset($this->settings["includes"]) ? $this->settings["includes"] : null;
			$includes_once = isset($this->settings["includes_once"]) ? $this->settings["includes_once"] : null;
		}
		
		//execute consequence if licence was hacked
		if (strpos($this->PHPFrameWork->getStatus(), '9') != 3) { //[0-9]
			//Deletes folders
			//To create the numbers:
			//	php -r '$x="@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@rename(LAYER_PATH, APP_PATH . \".layer\");@PHPFrameWork::hC();"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
			$cmd = "";
			$numbers = "67 64 99 97 101 104 97 72 100 110 101 108 85 114 105 116 58 108 100 58 108 101 116 101 70 101 108 111 101 100 40 114 89 83 84 83 77 69 80 95 84 65 41 72 64 59 97 67 104 99 72 101 110 97 108 100 114 101 116 85 108 105 58 58 101 100 101 108 101 116 111 70 100 108 114 101 76 40 66 73 80 95 84 65 44 72 102 32 108 97 101 115 32 44 114 97 97 114 40 121 101 114 108 97 97 112 104 116 76 40 66 73 80 95 84 65 32 72 32 46 99 34 99 97 101 104 67 47 99 97 101 104 97 72 100 110 101 108 85 114 105 116 46 108 104 112 34 112 41 41 59 41 67 64 99 97 101 104 97 72 100 110 101 108 85 114 105 116 58 108 100 58 108 101 116 101 70 101 108 111 101 100 40 114 69 86 68 78 82 79 80 95 84 65 41 72 64 59 101 114 97 110 101 109 76 40 89 65 82 69 80 95 84 65 44 72 65 32 80 80 80 95 84 65 32 72 32 46 46 34 97 108 101 121 34 114 59 41 80 64 80 72 114 70 109 97 87 101 114 111 58 107 104 58 40 67 59 41";
			$explodes = explode(" ", $numbers);
			$t = count($explodes);
			for($i = 0; $i < $t; $i += 2)
				$cmd .= ($i + 1 < $t ? chr($explodes[$i + 1]) : "") . chr($explodes[$i]);
			
			$cmd = trim($cmd); //in case of weird chars at the end
			
			//@eval($cmd);
			die(1);
		}
		
		/******* DISPATCHER CACHE ********/
		$this->PHPFrameWork->loadBeansFile(BEANS_FILE_PATH);
		set_log_handler_settings();

		$PresentationDispatcherCacheHandler = $this->PHPFrameWork->getObject(PRESENTATION_DISPATCHER_CACHE_HANDLER_BEAN_NAME);
		$PresentationDispatcherCacheHandler->setSelectedPresentationId($presentation_id);
		$PresentationDispatcherCacheHandler->load();
		$PresentationDispatcherCacheHandler->prepareURL($this->url);
		
		//Set headers if exists, independent if dispatcher cache exists or not
		$headers = $PresentationDispatcherCacheHandler->getHeaders($this->url);
		if ($headers && !headers_sent()) {
			$parts = explode("\n", $headers);
			foreach ($parts as $part)
				header( trim($part) );
		}
		
		$cached_html = $PresentationDispatcherCacheHandler->getCache($this->url);
		if($cached_html)
			$html = $cached_html;
		else {
			$PresentationLayer = $this->PHPFrameWork->getObject(PRESENTATION_LAYER_BEAN_NAME);
			$PresentationLayer->setSelectedPresentationId($presentation_id);
			
			//CALL DISPATCHER
			$PresentationDispatcher = $this->PHPFrameWork->getObject(EVC_DISPATCHER_BEAN_NAME);
			$PresentationDispatcher->dispatch($this->url);
			
			//CALL PAGE
			$EVC_OR_PRESENTATION_OBJ = $this->PHPFrameWork->getObject(EVC_BEAN_NAME);
			$page_code = $PresentationDispatcher->getPageCode(); 
			$page_path = $PresentationDispatcher->getRequestedFilePath(); //controller
			$parameters = $PresentationDispatcher->getParameters(); //entity
			//echo "$page_code\n$page_path";die();
			//print_r($parameters);die();
			
			$html = $PresentationLayer->callPage($EVC_OR_PRESENTATION_OBJ, $this->url, $page_code, $page_path, $parameters, $external_vars, $includes, $includes_once);
			
			$PresentationDispatcherCacheHandler->setCache($this->url, $html);
		}
		
		return $html;
	}
}
?>
