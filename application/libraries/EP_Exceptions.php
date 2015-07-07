<?php

/**
 * EP_Exceptions
 *
 * @package Shared
 * @author Dave Jesch
 * @version 0.9
 * @access public
 *
 * @description This class handles capturing and logging of errors and exception.
ALTER TABLE `error_log` ADD `backtrace_array` TEXT DEFAULT NULL AFTER BACKTRACE;
SELECT `id`, DATE_FORMAT(FROM_UNIXTIME(`created_at`), '%m/%d/%y') AS `occured` FROM `error_log`;
SELECT `id`, `msg`, DATE_FORMAT(FROM_UNIXTIME(`created_at`), '%m/%d/%y') AS `occured`, `backtrace_array`
	FROM `error_log` ORDER BY `id` DESC LIMIT 1;
 */

class EP_Exceptions extends CI_Exceptions
{
	const	MAX_MEM = 25;				// max memory size to allow var dumps for

	private $aErrInfo = NULL;

    public function __construct()
    {
        parent::__construct();
    }

	// log exception info
	public function logException($Exception)
	{
		// called from shared/system/ep_exception_handlder

		$this->storeError('Exception', $Exception->getMessage(),
			$Exception->getFile(), $Exception->getLine(), $Exception->getTrace(), 2);
	}


	// override the CI handler to log PHP runtime error info
	function log_exception($severity, $message, $filepath, $line)
	{
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		log_message('error', 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line, TRUE);

		/////

		$this->storeError($severity, $message, $filepath, $line, debug_backtrace(), 2);

		die($this->show_error($severity, $message));
	}


	// displays the error page to the user
	function show_error($heading, $message, $template = 'error_general', $status_code = 200)
	{
		if (NULL === $this->aErrInfo)
		{
			// when called from CI core, we need to call storeError() to get $aErrInfo populated
			$aErrData = error_get_last();
			$sMsg = implode(' ', (! is_array($message)) ? array($message) : $message);
			$this->storeError(intval($aErrData['type']), $sMsg,
				$aErrData['file'], $aErrData['line'], debug_backtrace(), 1);
		}

		$message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

		while (ob_get_level())				// clear any current output buffering
			ob_end_clean();

		if (class_exists('EP_Controller'))
			$ctrl = EP_Controller::getInstance();
		else
			$ctrl = get_instance();

		// check if running in command line mode
		if ($ctrl->input->is_cli_request())
		{
			echo 'An error was encountered: ' . $this->aErrInfo['msg'] . "\r\n\r\n";
			var_dump($this->aErrInfo);
			//print_r($message);
			die;

/*
			debug_print_backtrace();
unset($this->aErrInfo['backtrace']);
print_r($this->aErrInfo);
//echo "\r\nthis:";
//print_r($this);
$ciinst = get_instance();
echo "\r\nCI " . get_class($ciinst) . ":";
//print_r($ciinst);
			die("\r\nexecution halted.\r\n");
*/
		}

		// restart output buffering
		ob_start();

		// check for ajax requests and output ajax friendly content
		if (is_subclass_of($ctrl, 'EP_Controller') && $ctrl->input->is_ajax_request())
		{
			// turn off profiling
			$ctrl->config->set_item('enable_profiler', FALSE);
			$ctrl->ajax->resetData();
			if (ENVIRONMENT === 'production')
				$ctrl->ajax->addError(new AjaxError('An error was encountered'));
			else
				$ctrl->ajax->addError(new AjaxError($message));
			$ctrl->ajax->output();
		}
		set_status_header($status_code);

		// display appropriate production/non-production error page
		if (ENVIRONMENT === 'production')
		{
			include(APPPATH . 'errors/error.html');
		}
		else
		{
			// not sure if globalizing aErrorInfo is needed - maybe load the view?
			global $aErrorInfo;
			$aErrorInfo = $this->aErrInfo;
			include(APPPATH . 'errors/error_display' . EXT);
		}
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}


	// stores error/exception information into our database
	private function storeError($sSeverity, $sMessage, $sFile, $nLine, $aBacktrace = NULL, $nSkip = 0)
	{
		// get the controller instance so we can get session data
		if (class_exists('EP_Controller'))
			$ctrl = EP_Controller::getInstance();
		else
			$ctrl = get_instance();

//log_message('error', '***Error occured: ' . print_r($sMessage, TRUE) . ' in file ' . $sFile . ':' . $nLine);

		// collect information on current request
		$aErrData = array();
		$aErrData['msg'] = $sMessage;
		$aErrData['backtrace'] = $this->getErrorHtml($sMessage, $aBacktrace);	// 'old style' stuff from Jared...ew
//		$aErrData['backtrace'] = $this->formatBacktrace($aBacktrace, $nSkip);	// 'new style' stores serialized version of backtrace array

		$aErrData['account_id'] = NULL;
		$aErrData['user_id'] = NULL;
		$aErrData['user_login'] = NULL;
		if (is_a($ctrl, 'EP_Controller'))
		{
			$aErrData['account_id'] = $ctrl->nAccount;
			$aErrData['user_id'] = $ctrl->nUserId;
			$aErrData['user_login'] = $ctrl->sUserName;
		}
		$aErrData['environment'] = ENVIRONMENT;
		$aErrData['http_host'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
		$aErrData['uri'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
		$aErrData['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$aErrData['remote_addr'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
		$aErrData['get_array'] = serialize(isset($_GET) ? $_GET : null);
		$aErrData['post_array'] = serialize(isset($_POST) ? $_POST : null);
		$aErrData['session_array'] = serialize(isset($_SESSION) ? $_SESSION : null);
		$aErrData['session_key'] = session_id();
		$aErrData['created_at'] = time();
//		$aErrData['ci'] = 1;
/*
select id,msg,environment,date_format(from_unixtime(created_at), '%m/%d/%y') as date from error_log order by id desc limit 1;
delete from error_log where ci=1;
*/

		if ('The page you requested was not found.' !== $sMessage)
		{
			$db = $ctrl->db;
			// TODO: implement in a model
			$db->insert('ep_master.error_log', $aErrData);
		}

		$this->aErrInfo = $aErrData;
	}


	// formats error information for database insertion
	private function getErrorHtml($sErrorMsg, $aBacktrace, $nErrorId = NULL)
	{
		// TODO: remove HTML; should store this as a serialized array

		$sBaseDir = dirname(BASEPATH);

		if (preg_match('@^(.*) in ([^:]*):(\d*)$@', $sErrorMsg, $aMatches))
			$sErrorMsg = "{$aMatches[1]} in <strong>{$aMatches[2]}</strong> on line <strong>{$aMatches[3]}</strong>";

        $str = '<div class="backtrace">' . "\n" . '<div class="backtrace-inner">' . "\n";
        $str .= "\t<div class=\"backtrace-head-mesg\">" .
			($nErrorId ? 'Error #' . $nErrorId . ':' : 'Oops! An error occurred:') . "</div>\n\n";
        $str .= "\t<div class=\"error-msg\">$sErrorMsg</div>\n\n";
        $str .= "\t<div class=\"backtrace-head-mesg\">Here is the backtrace:</div>\n\n";
//echo '<pre>' . print_r($aBacktrace, TRUE) . '</pre>';
		$str .= '<section class="sub-bt nonbt">';
		$str .= 'Backtrace Information:<br/>';
        for ($i = count($aBacktrace) - 1; $i >= 0; $i--)
        {
            $call = $aBacktrace[$i];

			// remove excessive path information from file names
			$sFile = (isset($call['file']) ? $call['file'] : '[NO FILE]');
			$sFile = str_replace('\\', '/', $sFile);
			if (substr($sFile, 0, strlen($sBaseDir)) == $sBaseDir)
				$sFile = substr($sFile, strlen($sBaseDir) + 1);
			$call['file'] = $sFile;

            $str .= "\t" . '<div class="call">' . "\n";
            $str .= "\t\t" . (isset($call['class']) ? '<span class="class">' . $call['class'] .
					'</span><span class="type">' . $call['type'] . '</span>' : '');
			$str .= '<span class="function"><span class="name">' . $call['function'] . '</span>';
			$str .= '<span class="prnths">(</span>';
			$str .= (isset($call['args']) ? '<a href="#" onclick="document.getElementById(\'args_call_' .
					$i . '\').style.display=\'block\'; return false">' . count($call['args']) .
					' args</a>' : '');
			$str .= '<span class="prnths">)</span></span> in <span class="file">' . $call['file'] .
					'</span> line <span class="line">';
			$str .= (isset($call['line']) ? $call['line'] : '[NO LINE]') . "</span>\n";

			if (isset($call['class']) && strlen($call['class']) > 0)
				$call['function'] = $call['class'] . '::' . $call['function'];
log_message('error', '#' . $i . ' ' . $call['function'] . '() called from ' . $sFile . (isset($call['line']) ? ':' . $call['line'] : ''));

            if (! empty($call['args']))
            {
                $str .= '<div class="call-args" id="args_call_' . $i . '" style="display: none"><pre>';

                if (memory_get_usage() > 1024 * 1024 * self::MAX_MEM) // suppress if mem usage > 40mb
                    $str .= 'data export suppressed due to memory usage (&gt;40mb)';
                else
                    $str .= htmlspecialchars(print_r($call['args'], TRUE));

                $str .= '</pre></div>';
            }

            $str .= "\t</div>\n";
        }
        $str .= '</section>'; // #sub-bt

        $str .= "</div>\n</div>\n\n";

		return ($str);
    }


/*
	// returns backtrace information as json encoded data
	private function formatBacktrace($aTrace, $nSkip)
	{
		// TODO: use this function instead of getErrorHtml()

		$sBaseDir = dirname(BASEPATH);
//log_message('debug', 'basepath=' . $sBaseDir);

		// remove the first nSkip elements from the trace
		$aTrace = array_splice($aTrace, $nSkip);

		foreach ($aTrace as &$err)
		{
			if (!isset($err['line']))
				$err['line'] = '';
			if (!isset($err['file']))
				$err['file'] = '';
			if (!isset($err['function']))
				$err['function'] = '';
			if (isset($err['class']))
			{
				$err['function'] = $err['class'] = $err['function'];
				unset($err['class']);
			}
			if (!isset($err['type']))
				$err['type'] = '';

			// remove application directory prefix
			$sFile = str_replace('\\', '/', $err['file']);
			if (substr($sFile, 0, strlen($sBaseDir)) == $sBaseDir)
				$sFile = substr($sFile, strlen($sBaseDir) + 1);
//log_message('debug', 'file=[' . $sFile . ']');
			$err['file'] = $sFile;
		}

		$sRet = serialize($aTrace);
log_message('debug', 'traceback information: ' . $sRet);
		return ($sRet);
	}
*/


	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line)
	{
		// overriding base class's functionality - we don't want to do anything
	}
}

// EOF
