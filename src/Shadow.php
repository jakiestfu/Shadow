<?PHP

class Shadow {

    private $config;
    private $directory;
    private $database;
    private $core;

    private $build;

    function __construct($namespace) {

        $this->directory = realpath(dirname(__FILE__)) . '/';

        $this->build = (object) array();
        $this->build->namespace = $namespace;

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

    /*
     * Check if enough parameters are set to get or set
     */
    private function enoughParams($method) {

        $enough = true;

        if ($method == 'track') {
            if ($this->build->relation) {

                $enough = $this->build->relation->type && $this->build->relation->user !== null && $this->build->relation->value !== null;
            } else {
                $enough = $this->build->namespace && $this->build->type && $this->build->attrKey && $this->build->objectID;

                if ($this->build->complex) {
                    $enough = $enough && $this->build->attrVal;
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
    public function attribute($key) {
        $this->build->relation = false;
        $this->build->complex = stripos($key, '/') !== FALSE;

        if ($this->build->complex) {
            $attrData = explode('/', $key);

            $this->build->attrKey = $attrData[0];
            $this->build->attrVal = $attrData[1];
            $this->build->attrType = (is_numeric($attrData[1])) ? 'numeric' : 'string';
        } else {
            $this->build->attrKey = $key;
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
            return $this->core->route($this->build, 'get');
        }

    }

    public function clearDataByType($type) {
        $this->core->clearDataByType($type);
    }

}