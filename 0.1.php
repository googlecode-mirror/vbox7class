<?php
	/*
	Version: 0.1
	Author: Milen Ivanov / criobot<at>gmail<dot>com
	*/
	
	define("VBOX7_TITLE", 1);
	define("VBOX7_SUBTITLES", 2);

	class vbox7{
		public $url = null;
		public $code = null;
		public 	$title = null;
		public	$thumbnail = null;
		public	$flvUrl = null;
		public	$subtitles = null;
		public	$subtitlesExtension = 'srt';
		public function __construct($url, $flags = VBOX7_FLVURL){
			if(preg_match("/.*play:([a-fA-F0-9]{8})/",$url, $match)){
				$this->url = $match[0];
				$this->code = $match[1];
				
				if( ($FLAGS & VBOX7_TITLE) == true ){
					$src = file_get_contents($this->url);
					
					if(preg_match("/<title>(.*) \/ VBOX7<\/title>/", $src, $found)){
						$this->title = $found[1];
					}
				}
				
				$src = file_get_contents("http://www.vbox7.com/etc/ext.do?key=".$this->code."&antiCacheStamp=1234");
				
				if(preg_match("/&flv_addr=(.*?)&jpg_addr=(.*?)&subsEnabled=(?:true&subsData=)?(.*?)&related=[0-1]$/", $src, $parts)){
					$this->flvUrl = "http://".$parts[1];
					$this->thumbnail = "http://".$parts[2];
					if(($flags & VBOX7_SUBTITLES) == true){
						if($parts[3]){
							if($subs == 'srt'){
								$subs = new SRTFile(json_decode($parts[3]));
							}else{
								$subs = new SUBFile(json_decode($parts[3]));
								$this->subtitlesExtension = 'sub';
							}
							$this->subtitles = $subs->parse();
						}
					}
				}
			}else{
				throw new Exception("Невалиден линк");
			}
		}
	}
	/**
	 * SRT File Class
	 *	1\r\n
	 *  00:00:04,680 --> 00:00:08,500\r\n
	 *  Кой даде идеята?\r\n
	 *  - Ти.\r\n
	 *  \r\n
	 * 
	 */
	class SRTFile{
		private $source = null;
		public function __construct($src){
			if($src)
				$this->source = $src;
		}
		
		public function parse(){
			$number = 1;
			$return = "";
			for($i=0; $i<sizeof($this->source);$i++){
				$return .= ($i+1) . "\r\n";
				$return .= $this->formatTime($this->source[$i]->f) . " --> " . $this->formatTime($this->source[$i]->t) . "\r\n";
				$return .= str_replace("<br>", "\r\n", $this->source[$i]->s . "\r\n");
				$return .= "\r\n";
			}
			return $return;
		}
		private function leadingNull($number){
			if($number < 10) return "0".$number;
			return $number;
		}
		
		private function formatTime($seconds){
			$hours = $this->leadingNull((int)($seconds/360));
			$minutes =$this->leadingNull((int)($seconds/60));
			$seconds = $this->leadingNull($seconds - (((int)($seconds/360))*360 + ((int)($seconds/60))*60));
			return $hours.":".$minutes.":".$seconds.",000";
		}
	}
	
	/**
	 * SUB File Class
	 * {from*fps}{to*fps}Content(nl as |)\r\n
	 *
	 */
	class SUBFile{
		private $source = NULL;
		public function __construct($src){
			if($src)
				$this->source = $src;
		}
		
		public function parse($fps = 30){
			//Свалих 2-3 клипа от vbox7 и всичките бяха с FPS 30.0 така, че го оставям така по подразбиране.
			$return = "";
			for($i=0; $i<sizeof($this->source);$i++){
				$return .= "{". $fps * $this->source[$i]->f ."}{" . $fps * $this->source[$i]->t . "}" . $this->newLineFix($this->source[$i]->s) . "\r\n";
			}
			return $return;
		}
		
		public function newLineFix($string){
			$nl = array("\r\n", "\r", "\n", "<br>");
			return str_replace($nl, "|", $string);
		}
	}
	
	if(isset($_POST['link'])){
		$link = $_POST['link'];
		$format = $_POST['type'];
		if(preg_match("/[?:http:\/\/](?:www\.)?(vbox7.com|zazz.bg)\/play:[0-9a-fA-F]{8}.*?/i", $link)){
			if($_POST['download'] == 'subs'){
				$clip = new vbox7($link, $format==0? "srt": "sub");
				if($clip->subtitles){
					header('Content-type: text/plain');
					header('Content-Disposition: attachment; filename="'.$clip->code.'.'.$clip->subtitlesExtension.'"');
					echo $clip->subtitles;
				}else{
					echo 'No subtitles found.';
				}
			}else{
				$clip = new vbox7($link);
				if($clip->flvUrl){
					header('Location: '.$clip->flvUrl);
				}else{
					echo 'Error gettin\' FLV\'s URL';
				}
			}
		}else{
			echo 'Invalid link';
		}
	}else{
		header("Location: index.html");	
	}
?>