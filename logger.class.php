<?php
class Logger{
  private $request_uri;
  private $logfile;
  private $dbg_level;
  private $hwid;
  private $username;
  private $tab;
    
  public function __construct(){
    $this->request_uri = $_SERVER['REQUEST_URI'];
    $this->logfile = "log.txt";
    $this->dbg_level = 10;
    $this->hwid = "";
    $this->username = "";
    $this->tab = 0;
  }

  public function log($txt,$dbg_level){
    
    //filter log temporary
    //if($this->hwid != "IP200-03-06-93") return;

    // check debug level
    if ($dbg_level > $this->dbg_level) return;

    // parse log text for begin-end-fromatting
    strtok($txt," ");
    $log_begin_end = strtok(" ");

    // remove tab
    if ($log_begin_end == "end") $this->tab --;

    // insert datetime
    $log_output = "[".date("d.m.Y H:i:s",time())."] ";

    // insert request uri
    $log_output .= $this->request_uri." ";

    // insert log souce (HWID or Username)
    if ($this->hwid != ""){
      $source = $this->hwid;
    } else {
      $source = $this->username;
    }
    $log_output .= $source." ";
    
    // insert tabs for begin-end-fromatting
    $tabs = $this->tab;
    while ($tabs > 0){
      $log_output .= "   ";
      $tabs --;
    }
    
    // insert log text
    $log_output .= $txt;

    if (!file_exists($this->logfile)) {
        // write log stert to file and set chmod
        $log_handle = fopen($this->logfile, "a");
        fputs($log_handle,"Start Logging ".$source."\r\n");
        fclose($log_handle);

        chmod($this->logfile, 0666);
    }

    // write to file
    $log_handle = fopen($this->logfile, "a");
    fputs($log_handle,$log_output . "\r\n");
    fclose($log_handle);
    
    // add tab
    if ($log_begin_end == "begin") $this->tab ++;
  }

}
?>
