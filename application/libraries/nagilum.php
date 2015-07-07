<?php

/**
 * Nagilum
 *
 * @package - Nagilum - An ORM base class that all Models can extend.
 * @version 0.87
 * @access public
 */
class Nagilum extends ArrayObject implements IteratorAggregate
{
    protected $CI = NULL; // holds a reference to the CI object so that the model can access loaded libraries
    	// this is set on construction and won't change
    protected $db = NULL; // holds a reference to the db object attached to CI so that we can use $this->db instead of $this->CI->db
    	// this is set on construction and won't change
    private $inflector = NULL; // holds a refernce to the inflector helper just so we can be sure it's loaded
    	// this is set on construction and won't change
    protected $form_validation = NULL; // holds a reference to the form validation library used to validate the models
    	// this is set on constructtion and won't change
   	protected $dataFormat = NULL; // holda a reference to the data format library
	protected $input = NULL;

   	protected $table = NULL; // holds the current table name
   		// this is set at instantiation and won't change
    protected $model = NULL; // The singular name for this model used for references on joins
    	// this is set at instantiation and will only change in the case of relationships when they'll be stored as a relationship that doesn't match
   		// the default model name
    protected $parent = NULL; // Semi-private field used to track the parent model/id if there is one. For use with saving/deleting relationships
    	// this is only set during relationships
    protected $primaryKey = 'id'; // the primary key that is used in the constructor to automatically create the default record with that id ORM style
    	// this is set only at instantiation and will not change
    protected $tableFields = array(); // holds the fields for this table
    	// this is set at instantiation and won't change I want to build a cache for these so that they can be grabbed from there if they exist
    protected $tableMetaData = array(); // holds the metadata for the fields of this table
    	// this is set at instantiation and won't change I want to build a cache for these so that they can be grabbed from there if they exist to
   		// cut down on the number of queries when instatiating a lot of the same object
    protected $cType = 'data'; // this can be either a container or a data set based upon whether the query was a single or a multiple result call
							// if the object is a container you can't set or unset data directly on it as it's only a collection of objects
							// this field will also prevent you from calling save, and delete methods on the object
							// I have to remember to check on the type in all instances where I need to manipulate the children

	protected $stored = array(); // Used to keep track of the original values from the database, to prevent unecessarily changing fields
							// this is used to determine what needs to be saved
							// this should be set whenever a query is run
							// changeable only from within the class
    protected $data = array(); // holds the data for the results
    						// this can change at any point in time
    protected $rels = array(); // holds the relationship objects for this object
    						// this can change at any point in time
    protected $dataChanged = FALSE; // holds whether the object has changed or not, this will be set whenever a data element is set on the object
    						// changeable only from within the class
    protected $savable = TRUE; // this is used on direct queries to keep the object from saving so things don't get screwed up by saving something
    						// that shouldn't be savable
    						// changeable only from within the class
	protected $formId = 'Nagilum';	// form post id
	protected $preSaveHooks = array();  // used to store callback methods set from outside the class
							// can be changed at any time
    protected $postSaveHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
    protected $preResultHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
    protected $postResultHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
	protected $preDeleteHooks = array(); // used to store callback methods set from outside the class
							// can be changed at any time

    protected $initPreSaveHooks = array();  // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
    protected $initPostSaveHooks = array(); // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
    protected $initPreResultHooks = array(); // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
    protected $initPostResultHooks = array(); // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
	protected $initPreDeleteHooks = array();

    protected $initHasMany = array(); // holds the original has many relationships for use with clear
    						// setup at instantiation and can't be changed
    protected $initHasOne = array(); // holds the original has one relationships for use with clear
    						// setup at instantiation and can't be changed

    public $hasMany = array(); // holds the has many relationships
    						// this can be set at any point but should only be set at runtime unless there's a specific reason to do otherwise
    public $hasOne = array(); // holds the has one relationships
    						// this can be set at any point but should only be set at runtime unless there's a specific reason to do otherwise

    protected $errors = array(); // Contains any errors that occur during validation, saving, or other database access.
    						// this is set only at runtime from within the class
    protected $valid = FALSE; // The result of validate is tored here
    						// this is set only at runtime from within the class
    protected $initValidationRules = array(); // used by clear to restore the validation rules to their default state
    						// this is set only at instantiation
    public $validationRules = array(); // array holds the validation rules that inserts, updates, and saves will be check against.
				// See the form_validation class for details. I need to figure out how to handle validation of child classes
	protected $initSkipValidation = TRUE; // used by clear to restore the skip validation to its default value
							// set at instantiation
    public $skipValidation = TRUE;  // This tells whether to skip validation or not on inserts, updates and saves
    						// may be set at any time
	protected $validated = FALSE; // tracks whether or not the object has already been validated
							// set only from within the class

    protected $softDeleteField = 'is_deleted'; // this is the name of the soft delete field that is called automatically if it exists
    						// set only at instantiation
    protected $createdAtField = 'created_at'; // this is the name of the created at field which is set automatically if it exists
    						// set only at instantiation
    protected $createdByField = 'created_by'; // this is the name of the created by field which is set automatically if it exists
    						// set only at instantiation
    protected $updatedAtField = 'updated_at'; // this is the name of the udpated at field which is set automatically if it exists
    						// set only at instantiation
    protected $updatedByField = 'updated_by'; // this is the name of the updated by field which is set automatically if it exists
    						// set only at instantiation
    protected $useOldStyleAutoFields = FALSE; // this determines whether to use ints or DateTime objects for created at and udpate at fields
    						// set only at instantiation
    protected $resultFilters = array(); // this is for removing rows from a result set.
							// set only at instantiation

    public $autoTransaction = FALSE; // if TRUE automatically wraps every save and delete in a transaction
    						// this may be set at any time
	protected $initAutoTransaction = FALSE; // this is set only at instantiation

    public $autoPopulateHasMany = TRUE; // if TRUE will automatically populate has many fields
    						// this may be set at any time but will only have an effect when queries are run
	protected $initAutoPopulateHasMany = TRUE; // only set at instantiation
    public $autoPopulateHasOne = TRUE; // if TRUE will automatically populate has one fields
    						// this may be set at any time but will only have an effect when queries are run
	protected $initAutoPopulateHasOne = TRUE; // only set at instantiation

	protected $initFormat = array(); // used by clear to restore format to it's orignial value
							// this is only set at instantiation
    public $format = array(); // this array holds any fields that need to be formatted as well as the method to run them through, see the dataFormat library
    						// this may be changed at any time
	protected $initFormId = '';	// Initial state of form Id

    public $logQueries = TRUE; // allows the enabling of query logging on a per model basis
    						// this may be changed at anytime but is setup in the constructor
	protected $initLogQueries = NULL; // this is set only at instantiation
    protected $lastRunQuery = ''; // this will store the last run query from an object
    						// this will be set anytime a query is run

    protected $defaultOrderBy = array(); // This can be specified as an array of fields to sort by if no other sorting or selection has occurred.
    							// this should default to ascending order
   								// this can be set only at runtime, if you need something different you can simply pass in an order by clause

    protected $resultRowCount = 0; // this is only set when a query is run
    protected $resultFieldCount = 0; // this is only set when a query is run
    protected $resultInsertId = NULL; // this is only set when an insert has occured
    protected $resultAffectedRows = 0; // this is only set when an update or delete has occured

    public $paged = array(); // this will hold the information returned by getPage()
							// this needs to be immutable from outside of the class
							// this needs to be set back to an empty array on non-paged queries

	protected $whereGroupStarted = FALSE; // If true, the next where statement will not be prefixed with an AND or OR. Used for query grouping
							// this is only set by calling one of the group methods

	protected $saveSuccess = FALSE; // this saves whether the last save for this object was successful or not
							// this will only be set on updates and inserts

	public static $tableFieldCache = array(); // this acts as a global cache to store table meta data in to reduce load on the database
	public static $transactionStarted = FALSE; // stores whether there's currently a transaction started or not

	/**
	 * Nagilum::__construct()
	 *
	 * @description - the constructor for the object, responsible for setting up the object and retrieving it if an id is passed back in
	 * @param mixed $id - the id of the primary key of this model
	 * @return - if id is passed in it will return the object with a record of the data for that id
	 */
	public function __construct($id = NULL)
	{
		// load the core components and libraries
		$this->CI =& EP_Controller::getInstance();
        $this->db =& $this->CI->db;
        $this->CI->aDBs['model'] =& $this->db;

        $this->inflector = $this->CI->load->helper('inflector');
        $this->form_validation = $this->CI->load->library('form_validation');
        $this->form_validation->CI =& $this->CI;
        $this->dataFormat = $this->CI->load->library('dataFormat');
		$this->input = $this->CI->input;

        // if the table isn't overriden and set by the class determine what table this model uses
        if (NULL === $this->table)
        {
        	$this->table = get_called_class();
        	$this->table = plural($this->table);
        }

        // setup the default model name if it isn't overriden by the model itself
        if (NULL === $this->model)
        {
        	$this->model = get_called_class();
        }

        // setup log queries if it hasn't been overridden by the class
        // first we check the environment
        if (NULL === $this->logQueries)
        {
        	if (isset($_SERVER['LOGQUERIES']))
        	{
        		$this->logQueries = $_SERVER['LOGQUERIES'];
        	}
        }
		// if the environment or the model don't set logquerires we'll set it to true only for development
        if (NULL === $this->logQueries)
        {
        	if ('development' === $this->CI->getEnvironment())
        	{
        		$this->logQueries = TRUE;
        	} else {
        		$this->logQueries = FALSE;
        	}
        }

       	// get a list of the table's fields and store them for use when saving the object
        // we only want to do this for child classes though as the table for nagilum will never exist
        if ('nagila' !== $this->table)
        {
        	// first we see if we have the table information cached in the global cache. If it is we can assume the table exists and retrieve the cached
       		// information instead of adding extra queries to the database

       		$cachedTableData = Nagilum::getCachedTableData($this->table);

       		if ($cachedTableData)
       		{
       			$this->tableMetaData = $cachedTableData;
       		} else {
       			// make sure that the table exists otherwise throw an error
       			if ($this->tableExists())
       			{
       				// get the tables metadata, and store it in the object and in the global Cache
       				$this->tableMetaData = $this->fieldData();
       				Nagilum::setCachedTableData($this->table, $this->tableMetaData);
       			} else {
        			// the table doesn't exist so we need to error out
        			throw new Exception('Table ' . $this->table . ' does not exist');
        		}
       		}

		   $fields = $this->tableMetaData;
if (is_array($this->tableMetaData)) {
			// since the table exists we store the fields so that we know what to update/insert on saves
    		foreach ($fields as $field)
			{
				// Populate the model's field array
				$this->tableFields[] = $field->name;
			}
}
			// ensure that the has one and has many arrays don't conflict with the tables fields
	   		foreach ($this->hasOne as $rel => $data)
	   		{
	   			if (FALSE !== array_search($rel, $this->tableFields))
	   			{
	   				throw new Exception('Relationship name can\'t be the same as field in the table');
	   			}
	   		}

	   		foreach ($this->hasMany as $rel => $data)
	   		{
	   			if (FALSE !== array_search($rel, $this->tableFields))
	   			{
	   				throw new Exception('Relationship name can\'t be the same as field in the table');
	   			}
	   		}

	        // store the initial has one and has many values for use with the clear method
	        $this->initHasMany = $this->hasMany;
	        $this->initHasOne = $this->hasOne;

	        // store the needed initial values of validation for use with the clear method
	        $this->initValidationRules = $this->validationRules;
	        $this->initSkipValidation = $this->skipValidation;

	        // store the initial values of the format and hooks for use with the clear method
	        $this->initFormat = $this->format;
	        $this->initPreSaveHooks = $this->preSaveHooks;
	        $this->initPostSaveHooks = $this->postSaveHooks;
	        $this->initPreResultHooks = $this->preResultHooks;
	        $this->initPostResultHooks = $this->postResultHooks;
	        $this->initPreDeleteHooks = $this->preDeleteHooks;

	        // store the initial values for autoinstantation of transactions and relationships
	        $this->initAutoPopulateHasMany = $this->autoPopulateHasMany;
	        $this->initAutoPopulateHasOne = $this->autoPopulateHasOne;
	        $this->initAutoTransaction = $this->autoTransaction;

	        // store the initial value of logQueries
	        $this->initLogQueries = $this->logQueries;

			//
			$this->initFormId = $this->formId;

			// if id was passed in we get the results for that row
	        if (!empty($id))
	        {
	        	$this->getBy($this->primaryKey, $id);
	        }
$this->fieldData();
			// return this so that we can ensure that we get the updated query if an id was passed into the constructor
	        return $this;
        }
	}

	/**
	 * Nagilum::__get()
	 *
	 * @description - dynamic getter that handles accessing properties of the models data and rels array
	 * @param mixed $name - the name of the property that you're trying to access. This shouldn't ever be called directly
	 * @return mixed $result - the value of the property that you're trying to access or NULL if it doesn't exist
	 */
	public function &__get($name)
	{
		// First we check the data array for speed
		// see if the item exists within this objects data
		if (array_key_exists($name, $this->data))
		{
			$return = $this->data[$name];
			return $return;
		}

		// next we check the relationships since it's faster to check those than the table fields
		if (array_key_exists($name, $this->rels))
		{
			$return = $this->rels[$name];
			return $return; // may return an array of objects or an object depending on whether it's a has one or has many
		}

		// next we see if the object has been queried by checking it's id, we also make sure it's not a custom query
		if (!isset($this->data[$this->primaryKey]) || !$this->savable)
		{
			// this object doesn't have an id so we can't get the information from the database so we return NULL
			$return = NULL;
			return $return;
		}

		// the item has been queried so now we'll see if the data exists within the tables fields
		if (FALSE !== array_search($name, $this->tableFields))
		{
			// the field exists within the table data so we can see if it was retrieved previously so we can avoid the extra database query
			if (array_key_exists($name, $this->stored))
			{
				$this->data[$name] = $this->stored[$name];
				return $this->data[$name];
			}

			// retrieve the data from the database
			$return = $this->retrieveDataField($name);
			return $return;
		}

		// next we need to see if the item exists in the hasOne or hasMany arrays and if so retrieve the item(s)
		if (isset($this->hasOne[$name]))
		{
			$this->getHasOne($name, TRUE);
			$return = $this->rels[$name];
			return $return;
		}

		if (isset($this->hasMany[$name]))
		{
			$this->getHasMany($name, TRUE);
			$return = $this->rels[$name];
			return $return;
		}

		// the item doesn't exist anywhere so return NULL using a variable so we can return it by reference
		$return = NULL;
		return $return;
	}

	/**
	 * Nagilum::__set()
	 *
	 * @description - dynamic setter that handles setting the objects data and rels
	 * @param mixed $name - the name of the property that you want to set value to
	 * @param mixed $value - the value that you want to set the property to
	 * @return void
	 */
	public function __set($name, $value)
    {
    	// is the object type a container or a data set
    	if ('container' === $this->cType
				&& !((is_int($name) || empty($name))
					&& is_object($value)
					&& ($this->model === $value->getModelName())
				)
			)
    	{
    		throw new Exception('You can\'t set data directly on this object as it\'s a container of objects');
    	}

    	if ('container' === $this->cType)
    	{
    		$this->rels[] = $value;
    		return;
    	}

    	// if the value passed in is an object then we need to treat it as a relationship object
    	if (is_object($value))
    	{
    		if (empty($name))
    		{
    			throw new Exception('All models must be referenced by a relationship key');
    		}

			// ensure that the objects key isn't in the data array or in the tables fields so there's no conflicts when saving
			if (array_key_exists($name, $this->data) || FALSE !== array_search($name, $this->tableFields))
			{
				throw new Exception('This key exists in the data of this object you can\'t set an object reference to a data value');
			}

			// ensure that the child is being passed in correctly
    		$this->validateSetChild($name, $value);

    		$this->rels[$name] = $value;
    		return;
    	}

		// the passed in value is a data item so we assign it to the data array as long as it doesn't conflict with a relationship name
		if (array_key_exists($name, $this->rels) || isset($this->hasOne[$name]) || isset($this->hasMany[$name]))
		{
			throw new Exception('This key exists in the relationships of this object. You can\'t set a data value to an object reference');
		}

		if (empty($name))
		{
			$this->data[] = $value;
			$this->dataChanged = TRUE;
			return;
		}

		// does the data item element already exist? If so we want to be sure that it's actually changed
		if (array_key_exists($name, $this->data))
		{
			if ($this->data[$name] !== $value)
			{
				$this->data[$name] = $value;
				$this->dataChanged = TRUE;
			}
			return;
		}

		// the data item doesn't exist anywhere so we're going to add it to the model and set changed to true
		$this->data[$name] = $value;
		$this->dataChanged = TRUE;
    }

    /**
     * Nagilum::__isset()
     *
	 * @description - dynamic isset that allows determining if an item is set in the data or rels arrays
     * @param mixed $name - the name of the property that you want to see if its set
     * @return bool $isset - returns whether the property is set on this object or not
     */
    public function __isset($name)
    {
		// we check the data array first for speed as it will be accessed most frequently
		// see if the requested variable is set in the data array
    	if (array_key_exists($name, $this->data))
    	{
    		return TRUE;
    	}

		// the data wasn't stored in the objects data array but it may be stored in the stored array from being in the database
    	if (array_key_exists($name, $this->stored))
    	{
    		return TRUE;
    	}

    	// the field wasn't in the stored array but it could have been a custom select so lets see if the object has an id (there was a query)
    	if (isset($this->data[$this->primaryKey]))
    	{
    		// the object has an id so we can assume that a query was run on it
    		if (array_search($name, $this->tableFields))
    		{
    			// the field was found so retrieve it from the database and return TRUE
    			if (NULL !== $this->retrieveDataField($name))
    			{
	    			return TRUE;
    			}
    		}
    	}

    	// next we need to check the relationships
		// see if the requested variable is set in the relationships array
    	if (array_key_exists($name, $this->rels))
    	{
    		return TRUE;
    	}

    	// see if the requested variable is set in the has one or has many and hasn't been loaded in yet using lazy loading
    	if (isset($this->hasOne[$name]))
    	{
    		$this->getHasOne($name, TRUE);
    		return TRUE;
    	}

		// see if the requested variable is set in the has many and hasn't been loaded in yet using lazy loading
    	if (isset($this->hasMany[$name]))
    	{
    		$this->getHasMany($name, TRUE);
    		return TRUE;
    	}

    	// the requested variable wasn't found so return false
    	return FALSE;
    }

    /**
     * Nagilum::__unset()
     *
	 * @description - dynamic unset that allows unsetting of the models data and rels
     * @param mixed $name - the name of the property that you want to unset
     * @return void
     */
    public function __unset($name)
    {
    	// next we check the data array for speed
    	if (array_key_exists($name, $this->data))
    	{
    		unset($this->data[$name]);
    		$this->recalculateHasChanged();
    		return;
    	}

    	// the object wasn't found in the data array so we'll next use the rels array
    	if (array_key_exists($name, $this->rels))
    	{
    		unset($this->rels[$name]);
    		return;
    	}
    }

    /**
     * Nagilum::__toString()
     *
	 * @description - allows direct printing of the object in a usable format
     * @return string $return - the print_r output of this object turned into an array
     */
    public function __toString()
    {
        $return = print_r($this->toArray(), TRUE);
        return $return;
    }

    /**
     * Nagilum::toArray()
     *
     * @description - returns the current object as an array
     * @return array $array - the array format of this object
     */
    public function toArray()
    {
    	// the array that will be returned
        $return = array();

        // loop through the data elements and set the keys accordingly
		foreach ($this->data as $key => $value)
        {
        	$return[$key] = $value;
        }

        // loop through the set relationship objects and add them to the array as arrays
        foreach ($this->rels as $key => $value)
        {
        	if (NULL !== $value)
        	{
        		// the value is a Nagilum object so we need to convert it to an array as well
	        	$return[$key] = $value->toArray();
        	} else {
        		$return[$key] = NULL;
        	}
        }

        return $return;
    }

    /**
     * Nagilum::getIterator()
     *
     * @description - This is required by the arrayIterator interface to allow PHP to loop over the object
     * @return ArrayIterator $iterator - this object is used for looping over the object
     */
    public function getIterator()
    {
    	// returns an iterator that will be used in a foreach to loop through all data and objects
    	// we need to merge the data and relationship arrays so that everything is passed through to the foreach loop
    	$array = array_merge($this->data, $this->rels);

    	return new ArrayIterator($array);
    }

    /**
     * Nagilum::count()
     *
     * @description - this returns the total of the relationships and data elements of this model
     * @return int $count - this is the total number of data and relationship objects of this model
     */
    public function count()
    {
    	// initialize count to 0;
    	$count = 0;

		// data and rels are always arrays so we can safely use only count
    	$count += count($this->data);
    	$count += count($this->rels);

    	return $count;
    }

    /**
     * Nagilum::offsetExists()
     *
     * @description - this allows you to determine if an offset exists on a given model
     * @param mixed $name - the index that you want to see if it exists or not
     * @return bool $exists - TRUE if the object exists false otherwise
     */
    public function offsetExists($name)
    {
    	// call the isset method
    	return $this->__isset($name);
    }

    /**
     * Nagilum::offsetGet()
     *
     * @description - this returns a given offset on a model
     * @param mixed $name - the name of the index that you want to retrieve on the object
     * @return mixed $value - the value of the array at index $name that you're retrieving
     */
    public function offsetGet($name)
    {
    	// call the get method
    	return $this->__get($name);
    }

    /**
     * Nagilum::offsetSet()
     *
     * @description - this allows you to set a value to an offset on the model
     * @param mixed $name - the name of the index that you want to set
     * @param mixed $value - the value that you want to set index to
     * @return void
     */
    public function offsetSet($name, $value)
    {
    	// call the set method
    	$this->__set($name, $value);
    }

    /**
     * Nagilum::offsetUnset()
     *
     * @description - this allows you to remove an index from the model
     * @param mixed $name - the name of the index that you want to unset on this object
     * @return void
     */
    public function offsetUnset($name)
    {
    	// call the unset method
    	$this->__unset($name);
    }

    /**
     * Nagilum::toJson()
     *
     * @description - this returns the object as a json encoded array
     * @return string $json - the json encoded string representation of this object
     */
    public function toJson()
	{
		// return the object and all of it's children as a json encoded array
		$data = $this->toArray();
		$utf8Data = $this->utf8_encode_all($data);
		$json = json_encode($data);
		return $json;
	}

	/**
	 * Nagilum::utf8_encode_all()
	 *
	 * @description - this encodes all values of the array representation of this class so that they're propery JSON data elements
	 * @param mixed $data - the data to be utf8 encoded
	 * @return mixed $data - the utf8 encoded version of the data passed in
	 */
	protected function utf8_encode_all($data)
	{
		if (is_string($data))
		{
			return utf8_encode($data);
		}

		if (!is_array($data))
		{
			return $data;
		}

		$return = array();

		foreach ($data as $key => $value)
		{
			$return[$key] = $this->utf8_encode_all($value);
		}

		return $return;
	}

	/**
	 * Nagilum::tableExists()
	 *
	 * @description - This determines whether a table exists in the given database. Due to the way CI caches tables it's necessary to call switch
	 * 				database if the table is on another database.
	 * @param optional string $table - this is the name of the table you want to see if it exists by default it's the table of the current model
	 * @return bool $exists - TRUE/FALSE depending on whether the table exists or not
	 */
	public function tableExists($table = NULL)
    {
    	if (NULL === $table)
		{
			$table = $this->table;
		}

 		if (FALSE !== strpos($table, '.'))
 		{
 			$parts = explode('.', $table);
 			$this->db = $this->CI->switchDatabase($parts[0], TRUE);
 			$table = $parts[1];
 		}

    	return $this->db->table_exists($table);
    }

    /**
     * Nagilum::listFields()
     *
     * @description - This gets the list of fields for the given table
     * @param optional string $table - this is the table you want the fields for. By default it is the models table.
     * @return array $fields - an array of the fields in the table
     */
    protected function listFields($table = NULL)
	{
//echo "inside Nagilum::listFields()\r\n";
		if (NULL === $table)
		{
			$table = $this->table;
		}

//		return $this->db->list_fields($table);
		$x = $this->db->list_fields($table);
//var_export($x);
//echo "\r\n\r\nquery: " . $this->db->last_query() . "\r\n";
//echo 'query: ' . $this->lastRunQuery . "\r\n";
//die;
		return $x;
	}

	/**
	 * Nagilum::fieldData()
	 *
	 * @description - This returns the field and its metaData for the current model
	 * @param optional string $tableName - the table you want the field data for. By default it is the models table
	 * @return array $fieldData - an array of the metadata for the table
	 */
	protected function fieldData($tableName = NULL)
    {
    	if (NULL === $tableName)
    	{
    		$tableName = $this->table;
    	}

//   	return $this->db->field_data($tableName);
		$x = $this->db->field_data($tableName);
//var_export($x);
//echo "\r\n\r\nquery: " . $this->db->last_query() . "\r\n";
//echo 'query: ' . $this->lastRunQuery . "\r\n";
//die;
    }

    /**
     * Nagilum::getFieldList()
     *
     * @description - This gets the existing fields for this table
     * @return array $fields - this returns an array of the models tableFields
     */
    public function getFieldList()
	{
		// returns the fields for this model
		return $this->tableFields;
	}

	/**
	 * Nagilum::getFieldMetaData()
	 *
	 * @description - This returns the metadata for the current models fields
	 * @return array $metaData - an array containing objects with this models fields and their metadata
	 */
	public function getFieldMetaData()
	{
		return $this->tableMetaData;
	}

	/**
	 * Nagilum::fieldExists()
	 *
	 * @description - This checks to see if a table exists on the given table
	 * @param string $fieldName - The field that you want to see if it exists
	 * @param optional string $tableName - The table that you're checking the fields on
	 * @return bool $exists - TRUE/FALSE depending on whether the field exists
	 */
	public function fieldExists($fieldName, $tableName = NULL)
    {
    	// this should use the current field list if tableName is null to reduce the queries
    	if (NULL === $tableName)
    	{
    		$tableName = $this->table;
    	}

    	return $this->db->field_exists($fieldName, $tableName);
    }

    /**
     * Nagilum::getTableName()
     *
     * @description - Returns the table name that is set for this model
     * @return string $table - the table name of the current model
     */
    public function getTableName()
	{
		return $this->table;
	}

	/**
	 * Nagilum::getModelName()
	 *
	 * @description - returns the current model name of this object
	 * @return string $model - the model name that is set on this object
	 */
	public function getModelName()
	{
		return $this->model;
	}

	/**
	 * Nagilum::setModelName()
	 *
	 * @description - allows you to change the model name of the current object. This is necessary in some relationships
	 * @param string $name - the name that you wish to set the object's model property to
	 * @return void
	 */
	public function setModelName($name)
	{
		$this->model = $name;
	}

	/**
	 * Nagilum::getParent()
	 *
	 * @description - Retrieves the parent model of this model if it exists
	 * @return Nagilum $parent - the parent of the current object
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Nagilum::setParent()
	 *
	 * @description - allows for setting of a models parent
	 * @param Nagilum $obj
	 * @return void
	 */
	public function setParent($obj)
	{
		$this->parent =& $obj;
	}

	/**
	 * Nagilum::getType()
	 *
	 * @description - Returns whether the current object is a data or container object
	 * @return string $type - the type of the current model
	 */
	public function getType()
	{
		return $this->cType;
	}

    /**
     * Nagilum::setAutoTransaction()
     *
     * @description - Sets whether to automatically wrap saves, updates, and deletes in a transaction
     * @param boolean $bool - whether to enable (TRUE) or disable (FALSE) automatic transaction handling
     * @return void
     */
    public function setAutoTransaction($bool)
    {
    	$this->autoTransaction = $bool;
    }

    /**
     * Nagilum::setAutoPopulateHasOne()
     *
     * @description - allows you to set whether to automatically populate hasOne relationships at run time
     * @param boolean $bool - whether to automatically populate the has one relationships
     * @return void
     */
    public function setAutoPopulateHasOne($bool)
    {
    	$this->autoPopulateHasOne = $bool;
    }

    /**
     * Nagilum::setAutoPopulateHasMany()
     *
     * @description - allows you to set whether to automatically populate hasMany relationships at run time
     * @param boolean $bool - whether to automatically populate the hasMany relationships
     * @return void
     */
    public function setAutoPopulateHasMany($bool)
    {
    	$this->autoPopulateHasMany = $bool;
    }

    /**
     * Nagilum::skipValidation()
     *
     * @description - allows you to set whether to skip validation or not at runtime
     * @param boolean $bool - Whether to skip validation or run it (TRUE = skip)
     * @return void
     */
    public function skipValidation($bool)
	{
		$this->skipValidation = $bool;
	}

    /**
     * Nagilum::retrieveDataField()
     *
     * @description - This retrieves a data field that exists but wasn't retrieved previously (usually because of a custom select)
     * @param string $name - the field to be retrieve
     * @return $mixed $value - the value of the field from the database or NULL if the field couldn't be retrieved
     */
    protected function retrieveDataField($name)
    {
    	// first we need to ensure that there are no build up query parts so that there's no conflicts
    	$this->db->_reset_select();

		// next we build up the query to retrieve the record
		$query = $this->db->select($name);
    	$query = $this->db->from($this->table);
    	$query = $this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
    	$query = $this->db->get();

    	// ensure that we have at least one result otherwise return NULL
    	if ($query->num_rows() > 0)
    	{
    		$result = $query->row_array();
    		// just in case there's any oddities ensure that the field is set in the result otherwise return NULL
    		if (array_key_exists($name, $result))
    		{
    			$this->stored[$name] = $result[$name];
    			$this->data[$name] = $result[$name];
	    		return $result[$name];
    		}
    	}

    	return NULL;
    }

    /**
     * Nagilum::getSoftDeleteField()
     *
     * @description - returns the name of the field used for handling of soft deletion
     * @return string $field - the name of the field used for soft deleting on this table
     */
    public function getSoftDeleteField()
    {
    	return $this->softDeleteField;
    }

    /**
     * Nagilum::getHasOne()
     *
     * @description - This method is used to retrieve a hasOne relationship
     * @param string $name - the name of the relationship to retrieve
     * @param boolean $useAuto - whether to use the built in autoPopulateHasOne or to force a retrieval of this objects children
     * @return void
     */
    private function getHasOne($name, $useAuto)
    {
    	// see if the relationship exists otherwise throw an exception
    	if (!isset($this->hasOne[$name]))
    	{
    		throw new Exception('The child that is being instantiated: ' . $name . ' doesn\'t have an existing relationship with this object.');
    	}

    	// since this is a hasOne if the relationship is already in existence we return so we don't overwrite the existing data
    	if (isset($this->rels[$name]))
    	{
    		return;
    	}

    	$relationship = $this->hasOne[$name];

    	// by default we assume the class name is the same as the relationship name
    	$class = $name;
    	$relation = $name; // the key we're going to store the relationship as

		// relationship overridden?
    	if (isset($relationship['class']))
    	{
    		$class = $relationship['class'];
    	}

		// create an instance of the child class so we can determine its details
    	$temp = new $class();
    	$childTable = $temp->getTableName();
    	$childPK = $temp->getPrimaryKey();

    	// by default we assume we're not using a join table but are using this models table since we're in a has one relationship
    	$joinTable = FALSE;
    	$table = $this->table;

		// joinTable overridden?
    	if (isset($relationship['joinTable']))
    	{
    		$table = $relationship['joinTable'];
    		if ($table !== $this->table)
    		{
    			$joinTable = TRUE;
    		}
    	}

    	// by default we assume that the joinField is the relationship name followed by _id
    	$joinField = $name . '_id';

		// joinField overridden?
    	if (isset($relationship['joinField']))
    	{
    		$joinField = $relationship['joinField'];
    	}


    	// by default we assume that the childsJoinField is it's primary key
		$childJoinField = $childPK;

		// childJoinField overridden?
		if (isset($relationship['childJoinField']))
		{
			$childJoinField = $relationship['childJoinField'];
		}

		//TODO Shouldn't this be using getSoftDeleteField()?
		$isDeletedExists = $temp->fieldExists('is_deleted');

		if (isset($relationship['ignore_deleted']))
		{
			$ignoreDeleted = $relationship['ignore_deleted'];
		} else {
			$ignoreDeleted = TRUE;
		}

		// next we need to build up the query
		if (FALSE === $joinTable)
		{
			// only used in the case of self joins and only allowed on Has One relationships
			if ($table == $childTable)
			{
				$asTableName = 'ParentTable';
				$query = $this->db->from($table . ' AS ' . $asTableName);
			} else {
				$asTableName = $table;
				$query = $this->db->from($table);
			}
			$query = $this->db->select($childTable . '.*', FALSE);
			$query = $this->db->where($asTableName . '.' . $this->primaryKey, $this->data[$this->primaryKey]);
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($childTable . '.' . $temp->getSoftDeleteField(), 0);
			}
			if (isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach ($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				$query = $this->db->order_by($childTable . "." . $temp->getPrimaryKey(), 'ASC');
			}
			$query = $this->db->join($childTable, $asTableName . '.' . $joinField . ' = ' . $childTable . '.' . $temp->getPrimaryKey(), 'left outer');
		} else {
			$query = $this->db->select($childTable . '.*', FALSE);
			$query = $this->db->from($table);
			$query = $this->db->where($table . '.' . $joinField, $this->data[$this->primaryKey]);
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($childTable . '.' . $temp->getSoftDeleteField(), 0);
			}
			if (isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach ($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				$query = $this->db->order_by($childTable . "." . $temp->getPrimaryKey(), 'ASC');
			}
			$query = $this->db->join($childTable, $table . '.' . $childJoinField . ' = ' . $childTable . '.' . $temp->getPrimaryKey(), 'left outer');
		}

		if (isset($relationship['whereClause']))
		{
			$whereClause = $relationship['whereClause'];
			$whereBool = TRUE;
			if ($'NULL' === whereClause['clause'])
			{
				$whereBool = FALSE;
			}
			$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
		}
		if (isset($relationship['limitClause']))
		{
			$limitClause = $relationship['limitClause'];
			$this->db->limit($limitClause['rows'], $limitClause['offset']);
		}

//log_message('error', ' Nagilum::getHasOne() executing: ' . $this->db->_compile_select());
		$query = $this->db->get();

		if (0 === $query->num_rows())
		{
			log_message('debug', 'There Was No Has One Relationship Results Found');
			$this->rels[$relation] = NULL;
			return FALSE;
		}

		if ($query->num_rows() > 1)
		{
			log_message('debug', 'More than one child class was found');
		}

		$result = $query->row_array();

		$child = new $class();
		$child->callPreResultHooks();
		$result = $child->formatFields($result);
		$child->buildFromResultArray($result);
		$child->callPostResultHooks();
		$child->setModelName($relation);
		$child->setParent($this);
		$child->getChildrenAll($useAuto);
		$this->rels[$relation] = $child;
    }

    /**
     * Nagilum::getPrimaryKey()
     *
     * @description - retrieves the primary key used for this model
     * @return string $primaryKey - the primary key setup on this model
     */
    public function getPrimaryKey()
    {
    	return $this->primaryKey;
    }

    /**
     * Nagilum::makeContainer()
     *
     * @description - sets the current object as a container (used in result sets)
     * @return void
     */
    public function makeContainer()
    {
    	$this->cType = 'container';
    }

    /**
     * Nagilum::getHasMany()
     *
     * @description - This method is used to retrieve a hasMany relationship
     * @param string $name - the name of the relationship to retrieve
     * @param boolean $useAuto - whether to use the built in autoPopulateHasMany or to force a retrieval of this objects children
     * @return void
     */
    private function getHasMany($name, $useAuto)
    {
    	// see if the relationship exists otherwise throw an exception
    	if (!isset($this->hasMany[$name]))
    	{
    		throw new Exception('The child that is being instantiated: ' . $name . ' doesn\'t have an existing relationship with this object.');
    	}

    	$relationship = $this->hasMany[$name];

    	// by default we assume the class name is the same as the relationship name
    	$class = $name;
    	$relation = $name; // the key we're going to store the relationship as

		// relationship overridden?
    	if (isset($relationship['class']))
    	{
    		$class = $relationship['class'];
    	}

		// create an instance of the child class so we can determine its details
    	$temp = new $class();

    	// by default we assume we're not using a join table but are using this child models table since we're in a has many relationship
    	$joinTable = FALSE;
    	$table = $temp->getTableName();
    	$childTable = $table; // in case there is a join table

		// joinTable overridden?
    	if (isset($relationship['joinTable']))
    	{
    		$table = $relationship['joinTable'];
    		if ($table !== $temp->getTableName())
    		{
    			$joinTable = TRUE;
    		}
    	}

    	// by default we assume that the joinField is the current class's model name followed by _id
    	$joinField = $this->model . '_id';

		// joinField overridden?
    	if (isset($relationship['joinField']))
    	{
    		$joinField = $relationship['joinField'];
    	}

    	// by default we assume that the childsJoinField is it's model name followed by _id
		$childJoinField = $temp->getModelName() . '_id';

		// childJoinField overridden?
		if (isset($relationship['childJoinField']))
		{
			$childJoinField = $relationship['childJoinField'];
		}

		$isDeletedExists = $temp->fieldExists('is_deleted');

		if (isset($relationship['ignore_deleted']))
		{
			$ignoreDeleted = $relationship['ignore_deleted'];
		} else {
			$ignoreDeleted = TRUE;
		}

		// next we need to build up the query
		if (FALSE === $joinTable)
		{
			$query = $this->db->select($table . '.*', FALSE);
			$query = $this->db->from($table);
			$query = $this->db->where($table . '.' . $joinField, $this->data[$this->primaryKey]);
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($table . '.' . $temp->getSoftDeleteField(), 0);
			}
			if (isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach ($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				$query = $this->db->order_by($table . "." . $temp->getPrimaryKey(), 'ASC');
			}
			if (isset($relationship['group_by']))
			{
				$group_by = $relationship['group_by'];
				foreach ($group_by as $gField)
				{
					$query = $this->db->group_by($gField);
				}
			}
		} else {
			$query = $this->db->select($childTable . '.*', FALSE);
			$query = $this->db->from($table);
			$query = $this->db->where($table . '.' . $joinField, $this->data[$this->primaryKey]);
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($childTable . '.' . $temp->getSoftDeleteField(), 0);
			}
			if (isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach ($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				$query = $this->db->order_by($childTable . "." . $temp->getPrimaryKey(), 'ASC');
			}
			if (isset($relationship['group_by']))
			{
				$group_by = $relationship['group_by'];
				foreach ($group_by as $gField)
				{
					$query = $this->db->group_by($gField);
				}
			}
			$query = $this->db->join($childTable, $table . '.' . $childJoinField . ' = ' . $childTable . '.' . $temp->getPrimaryKey(), 'left outer');
		}

		if (isset($relationship['whereClause']))
		{
			$whereClause = $relationship['whereClause'];
			$whereBool = TRUE;
			if ('NULL' === $whereClause['clause'])
			{
				$whereBool = FALSE;
			}
			$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
		}
		if (isset($relationship['whereClause2']))
		{
			$whereClause = $relationship['whereClause2'];
			$whereBool = TRUE;
			if ('NULL' === $whereClause['clause'])
			{
				$whereBool = FALSE;
			}
			$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
		}
		if (isset($relationship['limitClause']))
		{
			$limitClause = $relationship['limitClause'];
			$this->db->limit($limitClause['rows'], $limitClause['offset']);
		}

		$query = $this->db->get();

		if (0 === $query->num_rows())
		{
			log_message('debug', 'There Was No Has Many Relationship Results Found');
			$this->rels[$relation] = NULL;
			return FALSE;
		}

		$result = $query->result_array();

		$primary = $temp->getPrimaryKey();
		if (!isset($this->rels[$relation]))
		{
			$all = TRUE;
		} else {
			$all = FALSE;
		}

		$container = new $class();
		$container->setModelName($relation);
		$container->makeContainer();
		$this->rels[$relation] = $container;

		foreach ($result as $row)
		{
			$current = $row[$primary];
			if ($all)
			{
				$child = new $class();
				$child->callPreResultHooks();
				$row = $child->formatFields($row);
				$child->buildFromResultArray($row);
				$child->callPostResultHooks();
				$child->setModelName($relation);
				$child->setParent($this);
				$child->getChildrenAll($useAuto);
				$this->rels[$relation][] = $child;
				continue;
			}

			$found = FALSE;

			foreach ($this->relations[$relation] as $obj)
			{
				if ($current == $obj->id)
				{
					$found = TRUE;
				}
			}

			if (!$found)
			{
				$child = new $class();
				$child->callPreResultHooks();
				$row = $child->formatFields($row);
				$child->buildFromResultArray($row);
				$child->callPostResultHooks();
				$child->setModelName($relation);
				$child->setParent($this);
				$child->getChildrenAll($useAuto);
				$this->rels[$relation][] = $child;
			}
		}
    }

    /**
     * Nagilum::validateSetChild()
     *
     * @description - this is used to ensure that a model added to a collection is of the appropriate type
     * @param string $name - the name of the Nagilum object you're adding
     * @param Nagilum $value - the object you're adding
     * @return void
     */
    private function validateSetChild($name, $value)
    {
		// be sure that the object is a model
		if (!$value instanceof Nagilum)
		{
			throw new Exception('Any objects set on a model must be an instance of a model');
		}

    	// ensure that the model is being set to the correct key so we know how to handle it
		if ($value->getModelName() !== $name)
		{
			throw new Exception('You\'re trying to assign a reference to a Model to a key that doesn\'t match that model\'s model property');
		}

		// be sure that there is a relationship to the model that is being saved into this class so that we know how to handle it
		if (!isset($this->hasOne[$name]) && !isset($this->hasMany[$name]))
		{
			throw new Exception('There is no relationship to the model that you\'re trying to add to this class');
		}
    }

    /**
     * Nagilum::clear()
     *
     * @description - resets an object completely to its initial state - like you instantiated a new object
     * @return void
     */
    public function clear()
    {
    	// clear the primary data and relationships
    	$this->data = array();
    	$this->rels = array();
    	$this->stored = array();

		// reset the stored metadata to it's default values
    	$this->dataChanged = FALSE;
    	$this->savable = TRUE;
    	$this->resultFieldCount = 0;
    	$this->resultRowCount = 0;
    	$this->resultAffectedRows = 0;
    	$this->resultInsertId = NULL;

    	// clear the data related to validation
    	$this->errors = array();
    	$this->valid = FALSE;
    	$this->skipValidation = $this->initSkipValidation;
    	$this->validated = FALSE;
    	$this->validationRules = $this->initValidationRules;

    	// clear the pagination array
    	$this->paged = array();

    	// clear the relationships
    	$this->hasOne = $this->initHasOne;
    	$this->hasMany = $this->initHasMany;

    	// clear the hooks and format arrays
    	$this->format = $this->initFormat;
    	$this->preSaveHooks = $this->initPreSaveHooks;
    	$this->postSaveHooks = $this->initPostSaveHooks;
    	$this->preResultHooks = $this->initPreResultHooks;
    	$this->postResultHooks = $this->initPostResultHooks;
    	$this->preDeleteHooks = $this->initPreDeleteHooks;

		//
		$this->formId = $this->initFormId;

    	// clear the autoTransaction and autoRelationship fields
    	$this->autoTransaction = $this->initAutoTransaction;
    	$this->autoPopulateHasOne = $this->initAutoPopulateHasOne;
    	$this->autoPopulateHasMany = $this->initAutoPopulateHasMany;

    	// clear the logQueries and other db items
    	$this->logQueries = $this->initLogQueries;
    	$this->lastRunQuery = '';
    	$this->whereGroupStarted = FALSE;
    	$this->saveSuccess = FALSE;
    	$this->db->_reset_select();

    	// reset the objects type
    	$this->cType = 'data';
    }

    /**
     * Nagilum::resetObject()
     *
     * @description - resets the object to a base state as if the query alone hadn't been run
     * @return void
     */
    public function resetObject()
	{
		// clear the primary data and relationships
    	$this->data = array();
    	$this->rels = array();
    	$this->stored = array();

    	// reset the stored metadata to it's default values
    	$this->dataChanged = FALSE;
    	$this->savable = TRUE;
    	$this->resultFieldCount = 0;
    	$this->resultRowCount = 0;
    	$this->resultAffectedRows = 0;
    	$this->resultInsertId = NULL;

    	// clear the data related to validation
    	$this->errors = array();
    	$this->valid = FALSE;
    	$this->validated = FALSE;

    	// clear the pagination object
    	$this->paged = array();

    	// clear the saveSuccess and other db items
    	$this->saveSuccess = FALSE;

    	// reset the objects type
    	$this->cType = 'data';
	}

	/**
	 * Nagilum::hasChanged()
	 *
	 * @description - returns whether the current object has changed from it's initial state
	 * @return boolean $hasChanged - whether the object has changed
	 */
	public function hasChanged()
    {
    	return $this->dataChanged;
    }

    /**
     * Nagilum::hasChangedAll()
     *
     * @description - returns whether the current object or any of it's children have changed from their initial states
     * @return boolean $hasChanged - whether the object or it's children have changed
     */
    public function hasChangedAll()
    {
    	if ($this->hasChanged())
    	{
    		return TRUE;
    	}
    	foreach ($this->rels as $obj)
    	{
    		if ($obj->hasChangedAll())
    		{
    			return TRUE;
    		}
    	}

    	return FALSE;
    }

    /**
     * Nagilum::getChangedFields()
     *
     * @description - Returns an array of the fields that have changed from their initial state
     * @return array $fields - an array of the fields that have changed
     */
    public function getChangedFields()
    {
    	$changed = array();

    	if (FALSE === $this->dataChanged)
    	{
    		return $changed;
    	}

    	foreach ($this->data as $key => $value)
    	{
    		if (!array_key_exists($key, $this->stored))
    		{
    			$changed[] = array('key' => $key, 'old' => NULL, 'new' => $this->data[$key]);
    			continue;
    		}
    		if ($value != $this->stored[$key])
    		{
    			$changed[] = array('key' => $key, 'old' => $this->stored[$key], 'new' => $this->data[$key]);
    		}
    	}

    	return $changed;
    }

    /**
     * Nagilum::getChangedFieldsAll()
     *
     * @description - gets an array of the fields that have changed from their initial state for the current object and it's children
     * @return array $fields - an array of the fields that have changed from this object and it's children
     */
    public function getChangedFieldsAll()
    {
    	$changed = $this->getChangedFields();

    	foreach ($this->rels as $key => $obj)
    	{
    		$objChanged = $obj->getChangedFieldsAll();
    		if (count($objChanged) > 0)
    		{
    			$changed[$key] = $objChanged;
    		}
    	}

    	return $changed;
    }

    /**
     * Nagilum::recalculateHasChanged()
     *
     * @description - recalculates whether the object has changed or not
     * @return boolean $return - returns true if the object has changed from its initial state
     */
    protected function recalculateHasChanged()
    {
    	foreach ($this->data as $key => $value)
    	{
    		if (!array_key_exists($key, $this->stored))
    		{
    			continue;
    		}
    		if ($value !== $this->stored[$key])
    		{
    			return TRUE;
    		}
    	}
    }

    /**
     * Nagilum::setPreSaveHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPreSaveHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->preSaveHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPreSaveHooks()
     *
     * @description - calls the presave hooks for this object
     * @return void
     */
    protected function callPreSaveHooks()
    {
    	$this->preSaveHook();

        foreach ($this->preSaveHooks as $callBack)
        {
            if (NULL !== $callBack['params'])
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::preSaveHook()
     *
     * @description - the base presave hook method for this model
     * @return void
     */
    protected function preSaveHook()
    {
        return;
    }

    /**
     * Nagilum::setPostSaveHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPostSaveHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postSaveHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPostSaveHooks()
     *
     * @description - calls the postsave hooks for this object
     * @return void
     */
    protected function callPostSaveHooks()
    {
    	$this->postSaveHook();

        foreach ($this->postSaveHooks as $callBack)
        {
            if (NULL !== $callBack['params'])
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::postSaveHook()
     *
     * @description - the base postSave hook method for this model
     * @return void
     */
    protected function postSaveHook()
    {
        return;
    }

    /**
     * Nagilum::setPreResultHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPreResultHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->preResultHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPreResultHooks()
     *
     * @description - calls the preResult hooks for this object
     * @return void
     */
    protected function callPreResultHooks()
    {
    	$this->preResultHook();

        foreach ($this->preResultHooks as $callBack)
        {
            if (NULL !== $callBack['params'])
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::preResultHook()
     *
     * @description - the base preResult hook method for this model
     * @return void
     */
    protected function preResultHook()
    {
        return;
    }

    /**
     * Nagilum::setPostResultHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPostResultHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postResultHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPostResultHooks()
     *
     * @description - calls the postResult hooks for this object
     * @return void
     */
    protected function callPostResultHooks()
    {
    	$this->postResultHook();

        foreach ($this->postResultHooks as $callBack)
        {
            if (NULL !== $callBack['params'])
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::postResultHook()
     *
     * @description - the base postResult hook method for this model
     * @return void
     */
    protected function postResultHook()
    {
        return;
    }

    /**
     * Nagilum::setPreDeleteHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPreDeleteHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postSaveHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPreDeleteHooks()
     *
     * @description - calls the preDelete hooks for this object
     * @return void
     */
    protected function callPreDeleteHooks()
    {
    	$this->preDeleteHook();

        foreach ($this->preDeleteHooks as $callBack)
        {
            if (NULL !== $callBack['params'])
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::preDeleteHook()
     *
     * @description - the base preDelete hook method for this model
     * @return void
     */
    protected function preDeleteHook()
    {
        return;
    }

    /**
     * Nagilum::hasChild()
     *
     * @descrption - Determines whether the data model exists within this models hasOne or hasMany arrays
     * @param Nagilum $obj - the object you want to see whether it's a child of this object or not
     * @return boolean $isChild - TRUE if the model name exists as a key in the hasOne or hasMany relationships
     */
    public function hasChild(Nagilum $obj)
	{
		// sees if there is a relationship defined between the passed in item and the current object
		$model = $obj->getModelName();
		if ($this->hasOne[$model])
		{
			return TRUE;
		}

		if ($this->hasMany[$model])
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Nagilum::autoTransactionBegin()
	 *
	 * @description - Responsible for handling the automatic starting of transactions
	 * @return void
	 */
	public function autoTransactionBegin()
	{
		if ($this->autoTransaction && FALSE === Nagilum::$transactionStarted)
		{
			$this->transactionStart();
			Nagilum::$transactionStarted = TRUE;
		}
	}

	/**
	 * Nagilum::autoTransactionComplete()
	 *
	 * @description - responsible for automatically ending the transaction and returning success or failure
	 * @return boolean $success - whether the transaction completed successfully or not
	 */
	public function autoTransactionComplete()
	{
		if ($this->autoTransaction && TRUE === Nagilum::$transactionStarted)
		{
			$this->transactionComplete();
			Nagilum::$transactionStarted = FALSE;

			// if the query wasn't successful throw an exception
			if (FALSE === $this->db->trans_status())
			{
			    return FALSE;
			} else {
				return TRUE;
			}
		}
	}

	/**
	 * Nagilum::transactionBegin()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @param bool $testMode - whether to set the transaction to test mode
	 * @return bool $result - whether the transaction was started or not
	 */
	public function transactionBegin($testMode = FALSE)
	{
		return $this->db->trans_begin($testMode);
	}

	/**
	 * Nagilum::transactionStart()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @param bool $testMode - whether to set the transaction to test mode
	 * @return
	 */
	public function transactionStart($testMode = FALSE)
	{
		$this->db->trans_start($testMode);
	}

	/**
	 * Nagilum::transactionComplete()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool
	 */
	public function transactionComplete()
	{
		return $this->db->trans_complete();
	}

	/**
	 * Nagilum::transactionStrict()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @param bool $mode - whether or not to turn transactions to strict mode
	 * @return void
	 */
	public function transactionStrict($mode = TRUE)
	{
		$this->db->trans_strict($mode);
	}

	/**
	 * Nagilum::transactionStatus()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool $status - whether the transaction was successful or not
	 */
	public function transactionStatus()
	{
		return $this->db->_trans_status;
	}

	/**
	 * Nagilum::transactionOff()
	 *
	 * @description - allows you to turn transactions off
	 * @return void
	 */
	public function transactionOff()
	{
		$this->db->trans_enabled = FALSE;
	}

	/**
	 * Nagilum::transactionRollback()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool $result - whether the transaction was rolled back or not
	 */
	public function transactionRollback()
	{
		return $this->db->trans_rollback();
	}

	/**
	 * Nagilum::transactionCommit()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool $result - whether the commit was successful or not
	 */
	public function transactionCommit()
	{
		return $this->db->trans_commit();
	}

	/**
	 * Nagilum::query()
	 *
	 * @description - This allows you to run a manual query with or without binds
	 * @param string $sql - The sql you want to execute
	 * @param $mixed $binds - an array of data that you want bound to the ?'s within the query
	 * @return Nagilum $row - returns a single row of data as a Nagilum object
	 */
	public function query($sql, $binds = FALSE)
	{
		$this->resetObject();

		$query = $this->db->query($sql, $binds);

		// store the last run query and log it if needed
		$this->logQuery();

		if (is_bool($query))
		{
			$this->resultInsertId = $this->db->insert_id();
			$this->resultAffectedRows = $this->db->affected_rows();
			return $query;
		}

		$num_rows = $query->num_rows();

		$this->resultRowCount = $num_rows;
		$this->resultFieldCount = $query->num_fields();

		if ($query->num_rows > 0)
		{
			$this->data = $query->row_array();
			$this->stored = $this->data;
			$this->savable = FALSE;
		}

		return $this;
	}

	/**
	 * Nagilum::queryAll()
	 *
	 * @description - This allows you to run a manual query with or without binds
	 * @param string $sql - The sql you want to execute
	 * @param $mixed $binds - an array of data that you want bound to the ?'s within the query
	 * @return Nagilum $resul - returns the result data as a Nagilum object
	 */
	public function queryAll($sql, $binds = FALSE)
	{
		$this->resetObject();

		$query = $this->db->query($sql, $binds);

		// store the last run query and log it if needed
		$this->logQuery();

		if (is_bool($query))
		{
			$this->resultInsertId = $this->db->insert_id();
			$this->resultAffectedRows = $this->db->affected_rows();
			return $query;
		}

		$num_rows = $query->num_rows();

		$this->resultRowCount = $num_rows;
		$this->resultFieldCount = $query->num_fields();

		if ($query->num_rows > 0)
		{

			$this->cType = 'data';
			$this->savable = FALSE;

			foreach ($query->result_array() as $row)
			{
				$obj = $this->getCopy();
				$obj->resetObject();
				$obj->buildFromResultArray($row);

				$this->rels[] = $obj;
			}
			$this->cType = 'container';
		}

		return $this;
	}

	/**
	 * Nagilum::numRows()
	 *
	 * @description - returns the number of results from the last query on this object
	 * @return int $rows - the number of rows from the last query
	 */
	public function numRows()
	{
		return $this->resultRowCount;
	}

	/**
	 * Nagilum::numFields()
	 *
	 * @description - returns the number of fields from the last query
	 * @return int $fields - the number of fields in the last queries result set
	 */
	public function numFields()
	{
		return $this->resultFieldCount;
	}

	/**
	 * Nagilum::protectIdentifiers()
	 *
	 * @description - protects mysql identifiers such as table names
	 * @param mixed $item - the item to be protected
	 * @return string $protected - the protected string of the identifiers passed in
	 */
	public function protectIdentifiers($item)
	{
		return $this->db->protect_identifiers($item);
	}

	/**
	 * Nagilum::escape()
	 *
	 * @description - escapes a value for prevention of SQL injections
	 * @param mixed $str - the value to be escaped
	 * @return mixed $str - the escaped value
	 */
	public function escape($str)
	{
		return $this->db->escape($str);
	}

	/**
	 * Nagilum::escapeStr()
	 *
	 * @description - escapes a string for prevention of SQL injections
	 * @param string $str - the string to be escaped
	 * @return string $str - the escaped string
	 */
	public function escapeStr($str)
	{
		return $this->db->escape_string($str);
	}

	/**
	 * Nagilum::escapeLikeStr()
	 *
	 * @description - escapes a string for prevention of SQL injections (when the string is to be used in a like)
	 * @param string $str - the string to be escaped
	 * @return string $str - the escaped string
	 */
	public function escapeLikeStr($str)
	{
		return $this->db->escape_like_str($str);
	}

	/**
	 * Nagilum::insertID()
	 *
	 * @description - returns the insert id of a new record
	 * @return int $insertID - the id of the last insert
	 */
	public function insertID()
	{
		return $this->resultInsertId;
	}

	/**
	 * Nagilum::affectedRows()
	 *
	 * @description - returns the affected rows of the query
	 * @return int $affectedRows - the number of rows affected by the last update / delete
	 */
	public function affectedRows()
	{
		return $this->resultAffectedRows;
	}

	/**
	 * Nagilum::countAll()
	 *
	 * @description - returns the total number of records in a table
	 * @param optional string $table - the table that you want the number of rows from
	 * @return int $rows - the number of rows in the specified table (this models table by default)
	 */
	public function countAll($table = NULL)
	{
		if (NULL === $table)
		{
			$table = $this->table;
		}

		$this->db->count_all($table);
	}

	/**
	 * Nagilum::limit()
	 *
	 * @description - allows you to set a limit clause on a query
	 * @param mixed $value - the number of rows to return
	 * @param string $offset - the offset of the first row
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function limit($value, $offset = '')
	{
		$this->db->limit($value, $offset);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::addTableName()
	 *
	 * @description - This adds the table name to the fields in AR methods
	 * @param string $field - The field to have the table name added to
	 * @return string $field - The field with the table name added
	 */
	public function addTableName($field)
	{
		// only add table if the field doesn't contain an open parentheses
		if (0 === preg_match('/[\.\(]/', $field))
		{
			// split string into parts, add field
			$field_parts = explode(',', $field);
			$field = '';
			foreach ($field_parts as $part)
			{
				if ( ! empty($field))
				{
					$field .= ', ';
				}
				$part = ltrim($part);
				// handle comparison operators on where
				$subparts = explode(' ', $part, 2);
				if ('*' === $subparts[0] || in_array($subparts[0], $this->tableFields))
				{
					$field .= $this->table  . '.' . $part;
				} else {
					$field .= $part;
				}
			}
		}
		return $field;
	}

	/**
	 * Nagilum::select()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function select($select = '*', $escape = TRUE)
	{
		if ($escape !== FALSE) {
			if (!is_array($select)) {
				$select = $this->addTableName($select);
			} else {
				$updated = array();
				foreach ($select as $sel) {
					$updated = $this->addTableName($sel);
				}
				$select = $updated;
			}
		}
		$this->db->select($select, $escape);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectMax()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectMax($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select max on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_max($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectMin()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectMin($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select min on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_min($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectAvg()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectAvg($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select avg on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_avg($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectSum()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectSum($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select sum on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_sum($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::buildWhere()
	 *
	 * @description - Builds the active record where clause
	 * @param mixed $key - the where clauses
	 * @param mixed $value - the value to match with the key(s)
	 * @param string $type - whether this is an and or an or
	 * @param mixed $escape - whether to escape the passed in key(s) and value
	 * @return Nagilum $this - the current object for method chaining
	 */
	protected function buildWhere($key, $value = NULL, $type = 'AND ', $escape = NULL)
	{
		if (!is_array($key))
		{
			$key = array($key => $value);
		}
		foreach ($key as $k => $v)
		{
			$new_k = $this->addTableName($k);
			if ($new_k != $k)
			{
				$key[$new_k] = $v;
				unset($key[$k]);
			}
		}

		$type = $this->getPrependType($type);

		$this->db->_where($key, $value, $type, $escape);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::where()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param mixed $value
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function where($key, $value = NULL, $escape = TRUE)
	{
		return $this->buildWhere($key, $value, 'AND ', $escape);
	}

	/**
	 * Nagilum::orWhere()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param mixed $value
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orWhere($key, $value = NULL, $escape = TRUE)
	{
		return $this->buildWhere($key, $value, 'OR ', $escape);
	}

	/**
	 * Nagilum::buildWhereIn()
	 *
	 * @description - This method builds up the whereIn methods for active record
	 * @param mixed $key
	 * @param mixed $values
	 * @param bool $not
	 * @param string $type
	 * @return Nagilum $this - the current object for method chaining
	 */
	protected function buildWhereIn($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ')
	{
		$type = $this->getPrependType($type);

	 	$this->db->_where_in($key, $values, $not, $type);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::whereIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param mixed $values
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function whereIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values);
	}

	/**
	 * Nagilum::orWhereIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orWhereIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values, FALSE, 'OR ');
	}

	/**
	 * Nagilum::whereNotIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function whereNotIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values, TRUE);
	}

	/**
	 * Nagilum::orWhereNotIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orWhereNotIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values, TRUE, 'OR ');
	}


	/**
	 * The remainder of the code has been deleted.
	 */
	// .....
}

// EOF
