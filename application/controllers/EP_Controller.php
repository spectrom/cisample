<?php
(defined('BASEPATH')) OR exit('No direct script access allowed');

// todo: set up custom error pages for account not found, account disabled, account cancelled, and maintenance mode
// todo: create unit tests for the core controller
// todo: inactive accounts should be checked on the login instead of everywhere so that a SuperAdmin or Admin could still login

/**
 * EP_Controller
 *
 * @package Core
 * @version 0.17
 * @access public
 *
 * @description This class is the core controller that houses all of the functionality needed by every (most) controllers in all of our applications
 *      This controller may be extended further but in doing so you have to be careful that the extending class is included preferrably by adding it
 *      in through a library or similar method of loading that sticks to the standards instead of using hand built file paths. Please be aware of what
 *      this controller is doing fully before using as it is shared among all applications
 */
function errorsHandler($errorNumber, $errorString, $errorFile, $errorLine, $errorContext)
{
	switch ($errorNumber)
	{
	default:
		echo $errorString;
		echo PHP_EOL, $errorNumber, PHP_EOL, PHP_EOL;
		break;
	}
	echo PHP_EOL, 'Restore backup and try again.', PHP_EOL;
	exit;
}

function exceptionsHandler($exception)
{
	echo $exception->getMessage();
	echo PHP_EOL, 'Restore backup and try again.', PHP_EOL;
	exit;
}

class EP_Controller extends MX_Controller
{
    private static $_instance = NULL; // instance to current EP_Controller

    private $sEnvironment = 'production'; // holds the environment variable, defaults to production in case we forget to set the variable
    private $bDbLoaded = FALSE; // this is used to determine if the dbutil class has been loaded which we must connect to ep_master first for this to be true
    private $aDbConfig = FALSE; // this holds the database config so that we can save some overhead when we need to switch databases
    public $nAccount = NULL; // this is the current account that the EMR is being run for
    private $sClientDb = NULL; // this is the current (or previously) connected client database
    private $sPrefix = NULL; // used to store the database prefix if it exists
    private $currentModel = NULL;

    public  $nUserId = NULL; // used to store the session id for the user
    public  $sUserName = NULL; // used to store the username for the currently logged in user
    public $title = ''; // used to store the title for the header

    public $aDBs = array(); // used to store the various databases connections that we load up when old connections die out??

	private static $environment_flag = false;

    /**
     * EP_Controller::__construct()
     *
     * @return - No Return Value
     */
    public function __construct()
    {
    	parent::__construct();

		if ('CLI' === APPLICATION)
		{
			set_error_handler('errorsHandler', E_ALL);
			set_exception_handler('exceptionsHandler');
		}

        // this is loaded at this point so we can use it to determine what EMR we're accessing
        $this->load->helper('url');

        if ('EMR' === APPLICATION || 'ADMIN' === APPLICATION)
        {
        	// this returns the domain from the url only
	        //$sDomain = $_SERVER['HTTP_HOST'];
			$sDomain = parse_url(base_url(), PHP_URL_HOST);
	        //die($sDomain);

			if (isset($_SERVER['HOSTIGNORE']))
			{
				$sDomain = str_replace($_SERVER['HOSTIGNORE'].'.', '', $sDomain);
			}

	        $aDomainPieces = explode('.', $sDomain);

	        $aDomainPieces = array_reverse($aDomainPieces);

	        // remove the last two pieces of the domain the host and the extension
	        $aDomainPieces = array_slice($aDomainPieces, 2);

	        // determine the environment
	        $this->sEnvironment = ENVIRONMENT;

	        if (count($aDomainPieces) > 1)
	        {
	            $aDomainPieces = array_slice($aDomainPieces, 1);
	        }

	        // if the server environment variable has been set use it to override the environment
	        if (isset($_SERVER['ENVIRONMENT']))
	        {
	            $this->sEnvironment = $_SERVER['ENVIRONMENT'];
	        }

	        if (isset($_SERVER['PREFIX']))
	        {
	            $this->sPrefix = $_SERVER['PREFIX'] . '_';
	        }

	        // make sure there's at least one domain piece working so that we can attempt to find an account
	        // also make sure the array element isn't an empty string or something similar
	        if (count($aDomainPieces) > 0 && !empty($aDomainPieces[0]))
	        {
	            $sSubdomain = $aDomainPieces[0];
	        } else {
	            exit("<tt style=\"color: red; font-weight: bold\">Account Not Found</tt>.");
	        }
        }

        // build up the configuration information here
        $this->load->database();
        //$this->load->config('database', FALSE, TRUE);
        //$this->load->config($this->getEnvironment(), FALSE, TRUE);

		if ('PORTAL' !== APPLICATION)
		{
        	// setup the connection to ep_master
        	$this->switchDatabase('ep_master');
        }

        if ('EMR' === APPLICATION)
        {
        	// query the database for the correct account information
	        $this->db->from('subdomain');
	        $this->db->join('account', 'subdomain.account_id = account.id', 'left');
	        $this->db->where('value', $sSubdomain);
	        $oQuery = $this->db->get();

        	// query the database for the maintenance_mode information
	        $this->db->from('maintenance_mode');
	        $pQuery = $this->db->get();
	        $pRow = $pQuery->row();

	        // make sure that only one result is found
	        if (1 !== $oQuery->num_rows())
	        {
	            exit("<tt style=\"color: red; font-weight: bold\">Account Not Found</tt>.");
	        }

	        // actually get the result
	        $oRow = $oQuery->row();

	        $this->nAccount = $oRow->id;

			if ($oRow->maintenance_mode || $pRow->maintenance_mode)
			{
				while(ob_get_level())
					ob_end_clean();
				include(APPPATH . '/errors/maintenance.php');
				exit(0);

			}
			if ($oRow->is_disabled)
			{
				while(ob_get_level())
					ob_end_clean();
				include(APPPATH . '/errors/account_canceled.php');
				exit(0);
			}
/*
			if ($row->is_cancelled)
			{
				exit("<tt style=\"color: red; font-weight: bold\">This account is currently unavailable.  Please contact support.</tt>.");
			}
*/

			// switch to the client's database for future accesses
        	$this->switchDatabase($oRow->db_name);
        }

        // set the current instance of the object to this if it's not already set
        if (!isset(self::$_instance))
        {
        	self::$_instance =& $this;
        }

        // load any remaining libraries that are necessary
        $this->loadLibraries();
		$this->load->model('UserSettings');

		if ('EMR' === APPLICATION)
		{
			// if there is no session id, then it means we aren't logged in and should redirect to the login page
/*	        if (!$this->input->is_ajax_request())
			{
				if (!isset($_SESSION['id']) || (isset($_SESSION['id']) && 0 == $_SESSION['id']))
				{
					header("Location: /user/login");
				}
			}
*/
			$sUri = substr($_SERVER['REQUEST_URI'], 0, 35);
			if (!isset($_SESSION['id']) || (isset($_SESSION['id']) && 0 == $_SESSION['id']))
			{
				// allow ajax and hijack requests to go through, otherwise redirect to login
				if ($this->input->is_ajax_request() || '/user/hijack' === substr($sUri, 0, 12))
				{
					// do nothing
				} else if('/user/' !== substr($sUri, 0, 6)) {
					// redirect to the login page
					redirect('/user/login');
				}
			}

	        // set the public instance of the user id that is stored in the session
	        if (isset($_SESSION['id']))
	        {
				$this->nUserId = $_SESSION['id'];
			}

	        // set the public instance of the user name that is stored in the session
	        if (isset($_SESSION['uname']))
	        {
	            $this->sUserName = $_SESSION['uname'];
	        }

			// if it's an AJAX POST or an application page request, reset the session time
			if (($this->input->is_ajax_request() && 'POST' === $_SERVER['REQUEST_METHOD']) ||
				!$this->input->is_ajax_request())
			{
				EP_Session::extendSession();
			}

			if (FALSE === self::$environment_flag)
			{
				$this->js->addJsCode('var environment_flag = "'.ENVIRONMENT.'";');
				$this->js->addJsCode('var show_prerelease = '.($this->config->item('show_prerelease') ? 1: 0).';');
				$emr_version = $this->config->item('emr_version');
				$this->js->addJsCode('var emr_version = '.((!empty($emr_version)) ? $emr_version : 1.4));
				self::$environment_flag = true;
			}
		}
		else if ('ADMIN' === APPLICATION)
		{
			$ip_address = explode('.', $this->input->ip_address());

			$ip_whitelist = new ip_whitelist();
			$ip = '';
			for ($i = 0; $i < count($ip_address); $i++)
			{
				if (0 !== $i)
				{
					$ip .= '.';
				}
				$ip .= $ip_address[$i];
				$ip_whitelist->orWhere('value', $ip);
			}
			$ip_whitelist->result();

			if (0 === $ip_whitelist->count())
			{
				log_message('error', 'User Failed IP Check with IP of ' . $this->input->ip_address());
				die('You don\'t have permission to access this application please contact the administrator.');
			}

	        if (isset($this->session->userdata))
	        {
	        	$this->nUserId = intval($this->session->userdata('user_id'));
			}
		}

		// set up any library path chaining for specific applications
		switch (APPLICATION)
		{
		case 'CLI':
			$this->load->_ci_library_paths[] = SHAREPATH . '/library/';
			// this is supposed to fall through
		case 'ADMIN':
//		case 'PORTAL':
//log_message('error', 'adding [' . dirname(SHAREPATH) . '/application/] to model paths');
			$this->load->_ci_model_paths[] = dirname(SHAREPATH) . '/application/';
//log_message('error', '  paths=' . var_export($this->load->_ci_model_paths, TRUE));
			break;
		case 'EMR':
			$this->load->_ci_library_paths[] = SHAREPATH . '/library/';
			break;
		}
    }

    /**
     * EP_Controller::switchDatabase()
     *
     * @description - allows us to switch databases or setup a new database connection from within our controllers
     * @param string $db - this is the name of the database to connect to
     * @param optional bool $return - this is whether you want the connection returned so you can have more than one connection open
     * @return - returns the database if $return was TRUE
     */
    public function switchDatabase($sDb, $oReturn = FALSE)
    {
        // only set the client database to be the connected to database if it's not a shared db, this allows us to reconnect to the client database
        if ('ep_master' !== $sDb && 'ep_api' !== $sDb && 'ep_portal' !== $sDb)
        {
            $this->sClientDb = $sDb;
            $sDb = 'emr_' . $sDb;
        }

		// see if the dbutil class is loaded, if so we can make sure a db exists prior to connecting
        if ($this->bDbLoaded && !$this->dbutil->database_exists($this->sPrefix . $sDb))
        {
            exit("<tt style=\"color: red; font-weight: bold\">The database couldn\'t be found.</tt>.");
        }

        // get the database configuration array
        if (!$this->aDbConfig)
        {
            $this->aDbConfig = $this->config->item('database');
        }

        // override which database we're connecting to
        $this->aDbConfig['database'] = $this->sPrefix . $sDb;

        $oDb = $this->load->database($this->aDbConfig, TRUE, TRUE);
        $this->aDBs[] =& $oDb;

        // if we're not returning set the CI db instance to the generated database
        if (!$oReturn)
        {
            CI::$APP->db = $oDb;
        }

        // if the database utility library hasn't been loaded before load it here since we now have a connection
        if (!$this->bDbLoaded)
        {
            $this->load->dbutil();
            $this->bDbLoaded = TRUE;
        }

        // return the requested db if needed
        if ($oReturn)
        {
            return $oDb;
        }
    }

    /**
     * EP_Controller::switchDatabase()
     *
     * @description - allows us to switch back to the original client database
     * @return - NULL
     */
     public function revertToClientDatabase()
     {
        $this->switchDatabase($this->sClientDb);
     }

    /**
     * EP_Controller::getEnvironment()
     *
     * @description - returns the given environment for the server
     * @return string
     */
    public function getEnvironment()
    {
        return $this->sEnvironment;
    }

    /**
     * EP_Controller::loadLibraries()
     *
     * @description - loads any needed libraries
     * @return - NULL
     */
    private function loadLibraries()
    {
		if ('EMT' === APPLICATION)
        {
			$this->load->helper('usage');
		}
        $this->load->helper('form', 'inflector');
        $this->load->library(array('form_validation', 'dataFormat', 'session', 'ajax', 'css', 'js', 'nagilum', 'permissions', 'feature'));
        $this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));

        $this->form_validation->CI =& $this; // set the CI instance with form validation to the current controller

        // if the environment is not production and it's not an ajax request enable the profiler
        if ('production' === ENVIRONMENT)
        {
        	$this->output->enable_profiler(FALSE);
        } else {
        	if (!$this->input->is_ajax_request())
            {
                $this->output->enable_profiler($this->config->item('enable_profiler'));
            } else {
                $this->output->enable_profiler(FALSE);
            }

        }

		// do not initialize sessions when in command line mode or admin area
		if ('EMR' === APPLICATION)
		{
			// initialize session; informing Session class if it's an Ajax GET request to ignore resetting TTL
			$fSetTTL = TRUE;
			if ('/dashboard/updateBadge' === uri_string())
				$fSetTTL = FALSE;
			EP_Session::init($this->db, $fSetTTL);
			$this->load->library('patientList');
		}
    }

	/**
     * EP_Controller::getInstance()
     *
     * @description - This is used to help setup the singleton design pattern
     * @return - NULL
     */
    public static function getInstance()
	{
		return (self::$_instance);
	}

	/**
     * EP_Controller::_output()
     *
     * @description - This does some automatic replacement on the output to include the js and css
     * @param - $output - the passed in output for the page
     * @return - NULL
     */
	public function _output($output, $printMode = FALSE)
	{
    	$this->css->printMode = $printMode;
    	$this->js->printMode = $printMode;

		$output = str_replace('{*JS*}', $this->js->output(), $output);
		$output = str_replace('{*CSS*}', $this->css->output(), $output);

	    if ($printMode)
	    {
	    	return $output;
	    }

	    echo $output;
	}
}

//EOF
