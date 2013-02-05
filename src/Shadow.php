<?PHP

class Shadow {

    private $config;
    private $directory;
    private $database;
    private $core;
	private $namespace;

    private $build;

    function __construct($namespace) {

        $this->directory = realpath(dirname(__FILE__)) . '/';
		
		$this->namespace = $namespace;
		$this->initNamespace();

        $this->loadAssets();

    }

    /*
     * Utils
     */
    private function loadAssets() {

        require( $this->directory . 'database.php' );
        require( $this->directory . 'core.php' );
		require( $this->directory . 'config.php' );

		$this->config = $config;

        $this->core = new ShadowCore(new ShadowDB($this->config['database']));
    }
	
	private function initNamespace(){
		$this->build = (object) array();
        $this->build->namespace = $this->namespace;
	}


    /*
     * Check if enough parameters are set to get or set
     */
    private function enoughParams($method) {

        $enough = true;

        if ($method == 'track') {
            if ($this->build->relation) {

                $enough = $this->build->relation->type && $this->build->relation->user !== null && $this->build->relation->value !== null;
            } else {
                $enough = $this->build->namespace && $this->build->type && $this->build->metaKey && $this->build->objectID;

                if ($this->build->complex) {
                    $enough = $enough && $this->build->metaComplexKey;
                }
            }
        }
		
        return $enough;
    }

    /*
     * Set Item Type
     */
    public function type($itemType) {
    	//print_r($this->build);
        $this->build->type = $itemType;
        return $this;
    }

    /*
     * Define build Complexity
     */
    public function meta($key, $value=false) {
        $this->build->relation = false;
        $this->build->complex = stripos($key, '/') !== FALSE;

        if ($this->build->complex) {
            $metaData = explode('/', $key);

            $this->build->metaKey = $metaData[0];
            $this->build->metaComplexKey = $metaData[1];
        } else {
            $this->build->metaKey = $key;
        }
		
		if($value){
        	$this->build->metaValue = $value;
        }
		
        return $this;
    }

    /*
     * Define Social Build
     */
    public function relation($type, $user = false, $value = false) {

        $this->build->relation->isRelational = true;

        $this->build->relation->type = $type;
        $this->build->relation->user = $user;
        $this->build->relation->value = $value;

        return $this;
    }

    /*
     * Assign Object
     */
    public function item($itemID, $timestamp = false) {

        $this->build->objectID = $itemID;
        $this->build->objectCreation = is_numeric($timestamp) ? date("Y-m-d H:i:s", $timestamp) : false;
        return $this;
    }

    /*
     * Execute Actions
     */
    public function track() {
		
        if ($this->enoughParams('track')) {
            $this->core->route($this->build, 'track');
			$this->initNamespace();
        }
    }

    public function get($start=false, $amount = false) {

	    if($start){
		    $limit = $start;
		    if($amount){
               $limit .= ','.$amount;
		    }
	    } else {
		    $limit = false;
	    }

	    $this->build->limit = $limit;

        if ($this->enoughParams('get')) {
            $toReturn = $this->core->route($this->build, 'get');
			$this->initNamespace();
			return $toReturn;
        }

    }

    public function clearDataByType($type) {
        $this->core->clearDataByType($type);
    }

}