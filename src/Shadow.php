<?PHP

class Shadow {

    private $config;
    private $directory;
    private $database;
    private $core;
    private $namespace;

    private $build;

    function __construct($namespace, $runGlobalTracks = false) {

        $this->directory = realpath(dirname(__FILE__)) . '/';
        
        $this->namespace = $namespace;
        $this->initNamespace();

        $this->loadAssets();

        /*
         * Global tracks are located at the
         * bottom of this class file.
         * 
         * If @param runGlobalTracks is true, local
         * function runGlobalTracks will be called.
         * If the param is a function, it will be 
         * called instead.
         */
        if($runGlobalTracks===true){
            $this->globalTracks();
        } elseif(is_callable($runGlobalTracks)){
            $runGlobalTracks($this);
        }
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
            if( is_array($value) ){
                $value = json_encode($value);
            }
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
     * Expiration Date
     */
    public function expires($when){
	    
	    $english = array(
	    	'second', 'minute', 'hour', 'day', 'week', 'month', 'year'
	    );
	    
	    $expires = $when;
	    preg_match_all("/([0-9]+\s)(second|minute|hour|day|week|month|year)/", strtolower($when), $matches);
	    if($matches[1][0] && $matches[2][0]){
		   $expires = strtotime("+".trim($matches[1][0])." ".trim($matches[2][0])."s");
	    } elseif(is_string($when)){
		    $expires = strtotime($when);
	    }
	    
	    $this->build->expires = date("Y-m-d H:i:s", $expires);
	    
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
            
            if(!is_array($toReturn)){
                $potentialJson = json_decode($toReturn, true);
                if( json_last_error() == JSON_ERROR_NONE && is_array($potentialJson)){
                    return $potentialJson;
                } else {
                    return $toReturn;
                }
            }
            
            return $toReturn;
        }

    }

    public function clearDataByType($type) {
        $this->core->clearDataByType($type);
    }


    /*
     * Global Tracks
     * 
     * Usage is the same.
     * 
     * $this->type()->item()->meta()->track();
     *
     * Defaults are client details providing quick tracking of popular links by client
     *  - IPv4/IPv6 or hostname when available
     *  - Browser agent string
     *  - Timestamp of request
     *  - Query string
     * 
     */
    private function globalTracks(){

        $toTrack = array(
            'Visitor' => (empty($_SERVER['REMOTE_HOST'])) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['REMOTE_HOST'],
            'Agent'   => $_SERVER['HTTP_USER_AGENT'],
            'Time'    => $_SERVER['REQUEST_TIME'],
            'Query'   => $_SERVER['QUERY_STRING']
        );

        $this->type( "global" )
             ->item( "global" )
             ->meta( "impressions", $toTrack)
             ->track();
    }
}