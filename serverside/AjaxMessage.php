<?php
	class AjaxMessage {
		const TYPE_ERROR    = "ERROR";
		const TYPE_NONE     = "NONE";
		const TYPE_LIST_RECIPE     = "LIST-RECIPE";
		const TYPE_LIST     = "LIST";
		const TYPE_ITEM     = "ITEM";
		const TYPE_EDIT     = "EDIT";
		const TYPE_MESSAGE  = "MESSAGE";
		const TYPE_DIALOG   = "DIALOG";
		const TYPE_CALLBACK = "CALLBACK";
		const TYPE_REFRESH  = "REFRESH";
		const TYPE_IMAGE	= "IMAGE";
		const TYPE_TEXT     = "TEXT";
		const TYPE_DATA		= "DATA";
		const TYPE_NOTHING  = "NOTHING";
		
		var $msgType = TYPE_NONE;
		var $msgContent = '""';
		var $msgURL = '';
		
		public function setMsgType($type){
			$this->msgType = $type;
		}
		
		public function setData($msg){
			$this->msgContent = json_encode($msg); 
		}
		
		public function setURL($url){
			$this->msgURL = $url;
		}
		
		/**
		 * This will setup the response to return an error message
		 * @param $msg
		 */
		public function returnError($msg){
			$this->setMsgType(AjaxMessage::TYPE_ERROR);
			$data = array();
			$data['Message'] = $msg;
			$this->setData($data);
		}
		
		public function returnMessage($msg){
			$this->setMsgType(AjaxMessage::TYPE_MESSAGE);
			$data = array();
			$data['Message'] = $msg;
			$this->setData($data);
		}
		
		public function returnText($ID, $msg){
			$this->setMsgType(AjaxMessage::TYPE_TEXT);
			$data = array();
			$data['ID'] = $ID;
			$data['Message'] = $msg;
			$this->setData($data);
		}
		
		public function returnDialog($OkCmd, $msg){
			$this->setMsgType(AjaxMessage::TYPE_DIALOG);
			$data = array();
			$data['Message'] = $msg;
			$data['OkCmd'] = $OkCmd;
			$this->setData($data);
		}
		
		public function echoMessage(){
			echo '{"Display":"' . $this->msgType . '","Data":' . $this->msgContent . ',"URL":"' . $this->msgURL . '"}';
		}
	}
?>