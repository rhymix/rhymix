<?php
    /**
     * @class  refererController
     * @author haneul (haneul0318@gmail.com) 
     * @brief  referer 모듈의 controller class
     **/

    class refererController extends referer {
        /**
         * @brief initialization
         **/
        function init() {
        }

	function procRefererExecute() {
	    if(empty($_SERVER["HTTP_REFERER"])) return;

	    // Log only from different hosts
	    $referer = parse_url($_SERVER["HTTP_REFERER"]);
	    if($referer['host'] == $_SERVER['HTTP_HOST']) return;

	    $oDB = &DB::getInstance();
	    $oDB -> begin();
	    $this->insertRefererLog($referer['host'], removeHackTag($_SERVER["HTTP_REFERER"]));
	    $this->deleteOlddatedRefererLogs();
	    $this->updateRefererStatistics($referer['host']);
	    $oDB -> commit();
	}

	function updateRefererStatistics($host)
	{
	    $oRefererModel = &getModel('referer');
	    $args->host = $host;
	    if($oRefererModel->isInsertedHost($host))
	    {
		$output = executeQuery('referer.updateRefererStatistics', $args);
	    }
	    else
	    {
		$output = executeQuery('referer.insertRefererStatistics', $args);
	    }

	    return $output;
	}

	function insertRefererLog($host, $url)
	{
	    $args->regdate = date("YmdHis");
	    $args->host = $host;
	    $args->url = $url;
	    return executeQuery('referer.insertRefererLog', $args);
	}

	function deleteOlddatedRefererLogs()
	{
	    $args->regdate = date("YmdHis", strtotime("-1 week"));
	    return executeQuery('referer.deleteOlddatedLogs', $args);
	}
    }
?>
