<?PHP

class ShadowCore {

    private $database;

    function __construct($db) {
        $this->database = $db;
    }

    /*
     * ------------------------------------------------------
     *  Determine what type of action based off of params
     * ------------------------------------------------------
     *
     * This function takes the build object and constructs a
     * function name out of its properties. The action parameter
     * is also included so we know what specifically we are
     * trying to do.
     */
    public function route($build, $action) {

        $func = array();

        if (!$build->relation) {
            $func[] = ($build->complex) ? 'complex' : 'simple';
        } else {
            $func[] = ($build->relation->type);
        }

        $func[] = ($build->relation) ? 'relation' : 'meta';
        $func[] = $action;

        $func = implode('_', $func);

    if (method_exists($this, $func)) {
            return call_user_func(array($this, $func), $build);
        } else {
            echo 'Invalid! Function: ' . $func;
        }
    }

    /*
     * ------------------------------------------------------
     *  Convert our build values to match Database fields
     * ------------------------------------------------------
     */
    private function buildToParams($build) {
        $data = array(
            'namespace' => $build->namespace, 
            'type' => $build->type, 
            'object_key' => $build->metaKey, 
            'object_id' => $build->objectID
        );
        if ($build->relation) {

            unset($data['object_key']);
            $data['operation'] = $build->relation->type;
            $data['user_id'] = $build->relation->user;
            $data['value'] = $build->relation->value;
            if ($build->objectCreation) {
                $data['timestamp'] = $build->objectCreation;
            }

        }
        return $data;
    }

    /*
     * ------------------------------------------------------
     *  Meta Tracking
     * ------------------------------------------------------
     *
     * Meta tracking refers to linear counts, ways to
     * track meta data about other objects within your app.
     *
     *
     * Simple Meta Tracking
     */
    private function simple_meta_track($build) {

	    $params = $this->buildToParams($build);

        $exists = $this->database->get('*', 'shadow_meta', $params);
        $exists = count($exists) == 1 ? $exists[0] : $exists;

        $theUpdate = 'count = count+1';
        if($build->metaValue){
            $theUpdate = 'object_value = :object_value';
        }

        if ($exists) {
            if($build->metaValue){
                if($build->metaValue != $exists->object_value){
                    $this->database->update($theUpdate, 'shadow_meta', $params, array('object_value'=>$build->metaValue));
                }
            } else {
                $this->database->update($theUpdate, 'shadow_meta', $params);
            }
        } else {
            if(!$build->metaValue){
            	$params['count'] = 1;
            } else {
            	$params['object_value'] = $build->metaValue;
            }
            if($build->expires){
                $params['expires'] = $build->expires;
            }
            
            $this->database->create('shadow_meta', $params);
        }

    }

    /*
     * ------------------------------------------------------
     *  Simple Meta Retrieving
     * ------------------------------------------------------
     */
    private function simple_meta_get($build) {

        $params = $this->buildToParams($build);

        $exists = $this->database->get('*', 'shadow_meta', $params);
        $exists = count($exists) == 1 ? $exists[0] : $exists;

        if($exists->object_value){
            return $exists->object_value;
        }

        if (intval($exists->count) === 0) {

            $params['parent'] = $exists->id;
            unset($params['object_key']);

            $children = $this->database->get('*', 'shadow_meta', $params);
            if ($children) {
                $temp = array();
                foreach ($children as $obj) {
                    $temp[$obj->object_key] = $obj->object_value ? $obj->object_value : $obj->count;
                }
                return $temp;
            }
        }
        return intval($exists->count);
    }

    /*
     * ------------------------------------------------------
     *  Complex Meta Tracking
     * ------------------------------------------------------
     *
     * Complex builds refer to tracking metadata that has a
     * parent. For isntance, you might want to track "gender",
     * but "gender" is useless to you. "Male" or "Female" is
     * what you'd want. Your build meta would look like
     * gender/male or gender/female
     */
    private function complex_meta_track($build) {

        $params = $this->buildToParams($build);

        $exists = $this->database->get('*', 'shadow_meta', $params);
        $exists = count($exists) == 1 ? $exists[0] : $exists;

        if ($exists) {
            $params['object_key'] = $build->metaComplexKey;

            $subExists = $this->database->get('*', 'shadow_meta', $params);

            if ($subExists) {
                $params['parent'] = $exists->id;

                if($build->metaValue){
                    if($build->metaValue != $subExists[0]->object_value){
                        $this->database->update('object_value = :object_value', 'shadow_meta', $params, array('object_value'=>$build->metaValue) );
                    }
                } else {
                    $this->database->update('count = count+1', 'shadow_meta', $params);
                }
            } else {
                $params['parent'] = $exists->id;
                $params['count'] = 1;
                $this->database->create('shadow_meta', $params);
            }

        } else {
	        
	        if($build->expires){
                $params['expires'] = $build->expires;
            }

            $this->database->create('shadow_meta', $params);

            unset($params['expires']);

            $lastID = $this->database->lastID();

            $params['object_key'] = $build->metaComplexKey;
            $params['parent'] = $lastID;

            if($build->metaValue){
                $params['object_value'] = $build->metaValue;
            } else {
                $params['count'] = 1;
            }
            $this->database->create('shadow_meta', $params);
        }
    }

    /*
     * ------------------------------------------------------
     *  Complex Meta Retrieving
     * ------------------------------------------------------
     */
    private function complex_meta_get($build) {

        $sql = 'SELECT tabTwo.* FROM `shadow_meta` AS tabOne, `shadow_meta` AS tabTwo WHERE tabOne.object_key = :object_key_one AND tabTwo.object_key = :object_key_two AND tabOne.id = tabTwo.parent AND tabTwo.namespace = :namespace AND tabTwo.type=:type GROUP BY tabTwo.id';

        $exists = $this->database->query($sql, array('object_key_one' => $build->metaKey, 'object_key_two' => $build->metaComplexKey, 'namespace' => $build->namespace, 'type' => $build->type, ));
        $exists = count($exists) == 1 ? $exists[0] : $exists;

        if ($exists) {
        	return $exists->object_value ? $exists->object_value : intval($exists->count);
        }
        return 0;

    }

    /*
     * ------------------------------------------------------
     *  Relation Tracking
     * ------------------------------------------------------
     *
     * Use these operations to track internal social relations
     * between users and objects on your site. Each type of
     * operation comes with ways to check what the users state
     * is relative to the object (likes, downvoted, rated 3, etc).
     * In addition, each operation uses it's own algorithm to
     * pull the "popular" objects.
     *
     * 1. Unary Operations: Unary operations are actions that
     * may have one additional state (excluding it's default
     * state). Unary actions are commonly represented in sites
     * with a "like" button or in YC HN, a single upvote.
     *
     * 2. Binary Operations: Binary operations are actions
     * that may have two additional states (excluding the it's
     * default state). This is commonly represented in sites
     * with "upvote" AND "downvotes", "like" AND "dislike", etc.
     *
     * 3. Multary Operations: Multary operations are actions
     * that may have multiple states that vary. Multary
     * actions are commonly represented as rating systems
     * in websites. Maybe rate an object 1-5 stars, give
     * a rating between 1-10, it varies.
     */

    private function unary_relation_track($build) {

        $params = $this->buildToParams($build);

        $theVal = $params['value'];
        $theUser = $params['user_id'];

        unset($params['value']);
        unset($params['user_id']);

        // Get Object
        $objectExists = $this->database->get('*', 'shadow_objects', $params);
        $objectExists = count($objectExists) == 1 ? $objectExists[0] : $objectExists;

        

        if (!$objectExists) {

            // Create Object
            $params['count'] = 0;
            $this->database->create('shadow_objects', $params);
            $theID = $this->database->lastID();
        } else {

            // Retain Object
            $theID = $objectExists->id;
        }

        // User Has a Relationship?
        $usersRelationship = $this->database->get('*', 'shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $theID));

        // If Liking
        if ($theVal) {

            // If No Relationship
            if (!$usersRelationship) {

                // Add relationship and Update Object
                $this->database->update('count = count+1', 'shadow_objects', $params);
                $this->database->create('shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $theID, 'value' => 1));
            }

        } elseif (!$theVal && $usersRelationship) {

            // Remove relationship and Update Object
            $this->database->update('count = count-1', 'shadow_objects', $params);
            $this->database->remove('shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $theID, 'value' => 1));

        } else {
            // Unliking something you never liked
        }

    }

    /*
     * ------------------------------------------------------
     *  Unary Relation Retrieving
     * ------------------------------------------------------
     *
     * If there is a user ID, this will retrieve the users
     * relationship with the object. If there is no User ID,
     * this will retrieve the social value of the object.
     * If there is no object ID, a list of "popular" items
     * will be retrieved per the item type.
     */
    private function unary_relation_get($build) {
        $params = $this->buildToParams($build);
        unset($params['value']);

        // Targeting an object
        if (!empty($build->objectID)) {

            // Need users state?
            if (!empty($build->relation->user)) {

                $sql = 'select `shadow_object_relations`.* from `shadow_object_relations`, `shadow_objects` WHERE `shadow_object_relations`.user_id = :user_id AND `shadow_objects`.object_id = :object_id AND `shadow_objects`.id = `shadow_object_relations`.real_object_id AND `shadow_objects`.type = :type AND `shadow_objects`.namespace = :namespace';
                $exists = $this->database->query($sql, array('namespace' => $params['namespace'], 'type' => $params['type'], 'user_id' => $build->relation->user, 'object_id' => $build->objectID));

                return $exists ? true : false;
            } else {

                // Fetch Objects Likes
                $sql = 'select `shadow_objects`.* FROM `shadow_objects` WHERE `shadow_objects`.object_id = :object_id AND `shadow_objects`.type = :type AND `shadow_objects`.namespace = :namespace';
                $users = $this->database->query($sql, array('namespace' => $params['namespace'], 'type' => $params['type'], 'object_id' => $build->objectID));
                return intval($users[0]->count);

            }
        } else {

            /*
             * ------------------------------------------------------
             *  Unary Popular Algorithm
             * ------------------------------------------------------
             *
             * This is one algorithm of I'm sure many to calculate
             * "popularity", but this is one that is used in
             * Y Combinator's Hacker news as well as various other
             * sites.
             *
             *
             * Popularity = (points – 1) / (time_in_hrs + 2)^1.5
             *
             */

            $limit = ($build->limit) ? ' LIMIT ' . $build->limit : '';

            $sql = 'select *, ((`shadow_objects`.count - 1) / POWER( (HOUR(TIMEDIFF(NOW(),`shadow_objects`.timestamp))+2), 1.5) ) as rank from `shadow_objects` WHERE type=:type AND namespace = :namespace ORDER BY rank DESC' . $limit;

            $objects = $this->database->query($sql, array('type' => $build->type, 'namespace' => $build->namespace));
            $temp = array();

            $temp['count'] = count($objects);
            $temp['type'] = $build->type;

            if ($objects) {
                foreach ($objects as $obj) {
                    $temp['objects'][] = array('id' => $obj->object_id, 'count' => $obj->count, 'rank' => $obj->rank);
                }
            }
            return $temp;
        }
    }

    /*
     * ------------------------------------------------------
     *  Binary Relation Tracking
     * ------------------------------------------------------
     */
    private function binary_relation_track($build) {

        $params = $this->buildToParams($build);

        $theVal = $params['value'];
        $theUser = $params['user_id'];

        unset($params['value']);
        unset($params['user_id']);

        // Get Object
        $objectExists = $this->database->get('*', 'shadow_objects', $params);
        $objectExists = count($objectExists) == 1 ? $objectExists[0] : $objectExists;
        
        // Determine if Users currently negative or positive
        $countKey = $theVal === true ? 'positive' : ($theVal === false ? 'negative' : '');

        // No Object
        if (!$objectExists) {

            // Create Object
            $this->database->create('shadow_objects', $params);
            $shadowObjectID = $this->database->lastID();

        } else {

            // Retain Object
            $shadowObjectID = $objectExists->id;
            $curVal = $objectExists->id;
        }

        // Does user have relationship?
        $usersRelationship = $this->database->get('*', 'shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $shadowObjectID));
        $usersRelationship = $usersRelationship ? $usersRelationship[0] : $usersRelationship;

        $createRelationship = false;

        if (!$usersRelationship) {

            // User has never voted
            $updatedVotes = ($theVal === true) ? 'positive = positive+1' : 'negative = negative+1';
            $createRelationship = true;

        } elseif ($usersRelationship->value == 1) {

            // User has voted up before, update only if voting down
            $updatedVotes = ($theVal === true) ? false : 'positive = positive-1, negative = negative+1';

        } elseif ($usersRelationship->value == -1) {

            // User has boted down before, update only if voting up
            $updatedVotes = ($theVal === true) ? 'positive = positive+1, negative = negative - 1' : false;

        }

        if ($createRelationship) {

            // Create users relationship if needed
            $this->database->create('shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $shadowObjectID, 'value' => ($theVal === true) ? '1' : '-1'));

            // Update Object
            $this->database->update($updatedVotes, 'shadow_objects', $params);
        } else {

            // If vote is different than the users last vote
            if ($updatedVotes) {

                // Update Object
                $this->database->update($updatedVotes, 'shadow_objects', $params);

                // Update Relationship
                $this->database->update('value=' . (($theVal === true) ? '1' : '-1'), 'shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $shadowObjectID));
            } else {
                // No Change
            }
        }

    }

    /*
     * ------------------------------------------------------
     *  Binary Relation Retrieving
     * ------------------------------------------------------
     */
    private function binary_relation_get($build) {
        $params = $this->buildToParams($build);
        unset($params['value']);

        // Targeting an object
        if (!empty($build->objectID)) {

            // Need users state?
            if (!empty($build->relation->user)) {

                $sql = 'select `shadow_object_relations`.* from `shadow_object_relations`, `shadow_objects` WHERE `shadow_objects`.operation = "binary" AND `shadow_object_relations`.user_id = :user_id AND `shadow_objects`.object_id = :object_id AND `shadow_objects`.id = `shadow_object_relations`.real_object_id AND `shadow_objects`.type = :type AND `shadow_objects`.namespace = :namespace';
                $exists = $this->database->query($sql, array('namespace' => $params['namespace'], 'type' => $params['type'], 'user_id' => $build->relation->user, 'object_id' => $build->objectID));

                return $exists[0]->value == -1 ? false : ($exists[0]->value == 1 ? true : null);
            } else {

                // Fetch Objects Up/Downvotes
                $sql = 'select `shadow_objects`.* FROM `shadow_objects` WHERE `shadow_objects`.operation = "binary" AND `shadow_objects`.object_id = :object_id AND `shadow_objects`.type = :type AND `shadow_objects`.namespace = :namespace';
                $users = $this->database->query($sql, array('namespace' => $params['namespace'], 'type' => $params['type'], 'object_id' => $build->objectID));

                return array('positive' => $users[0]->positive, 'negative' => $users[0]->negative);

            }
        } else {

            /*
             * ------------------------------------------------------
             *  Binary Popular Algorithm
             * ------------------------------------------------------
             *
             * Lower bound of Wilson score confidence interval for a
             * Bernoulli parameter
             *
             * http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
             */

            $limit = ($build->limit) ? ' LIMIT ' . $build->limit : '';

            $sql = 'SELECT `shadow_objects`.*, ((positive + 1.9208) / (positive + negative) - 
                   1.96 * SQRT((positive * negative) / (positive + negative) + 0.9604) / 
                          (positive + negative)) / (1 + 3.8416 / (positive + negative)) 
       AS rank FROM `shadow_objects` WHERE `shadow_objects`.operation = "binary" AND positive + negative > 0 AND namespace=:namespace AND type=:type
       ORDER BY rank DESC' . $limit;

            $objects = $this->database->query($sql, array('type' => $build->type, 'namespace' => $build->namespace));
            $temp = array();

            $temp['count'] = count($objects);
            $temp['type'] = $build->type;

            if ($objects) {
                foreach ($objects as $obj) {
                    $temp['objects'][] = array('id' => $obj->object_id, 'positive' => $obj->positive, 'negative' => $obj->negative, 'rank' => $obj->rank);
                }
            }
            return $temp;
        }
    }

    /*
     * ------------------------------------------------------
     *  Multary Relation Tracking
     * ------------------------------------------------------
     */
    private function multary_relation_track($build) {

        $params = $this->buildToParams($build);

        $theVal = $params['value'];
        $theUser = $params['user_id'];

        unset($params['value']);
        unset($params['user_id']);

        // Get Object
        $objectExists = $this->database->get('*', 'shadow_objects', $params);
        $objectExists = count($objectExists) == 1 ? $objectExists[0] : $objectExists;

        // No Object
        if (!$objectExists) {

            // Create Object
            $curVal = 0;
            $this->database->create('shadow_objects', $params);
            $theID = $this->database->lastID();

        } else {

            // Retain Object
            $theID = $objectExists->id;

        }

        // User rated?
        $usersRelationship = $this->database->get('*', 'shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $theID));
        $usersRelationship = $usersRelationship ? $usersRelationship[0] : $usersRelationship;

        $curVal = $usersRelationship->value;
        $createState = false;

        if (!$usersRelationship) {

            // User has never voted
            $count = 'positive = positive + ' . $theVal;
            $createState = true;

        } elseif ($theVal < $curVal) {

            // User is rating lower than last vote
            $count = 'positive = positive + ' . ($theVal - $curVal);

        } elseif ($theVal > $curVal) {

            // User is rating higher than last vote
            $count = 'positive = positive - ' . ($curVal - $theVal);

        }

        if ($createState) {

            // Create users relationship if needed
            $this->database->create('shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $theID, 'value' => $theVal));

            // Update object
            $this->database->update($count . ', count = count+1', 'shadow_objects', $params);
        } else {

            // If vote is different than the users last vote
            if ($count) {

                // Update object
                $this->database->update($count, 'shadow_objects', $params);

                // Update Relationship
                $this->database->update('value=' . $theVal, 'shadow_object_relations', array('user_id' => $theUser, 'real_object_id' => $theID));
            } else {
                // Same Vote, Yo
            }
        }
    }

    /*
     * ------------------------------------------------------
     *  Multary Relation Retrieving
     * ------------------------------------------------------
     */
    private function multary_relation_get($build) {
        $params = $this->buildToParams($build);
        unset($params['value']);

        // Targeting an object
        if (!empty($build->objectID)) {

            // Need users state?
            if (!empty($build->relation->user)) {

                $sql = 'select `shadow_object_relations`.* from `shadow_object_relations`, `shadow_objects` WHERE `shadow_objects`.operation="multary" AND `shadow_object_relations`.user_id = :user_id AND `shadow_objects`.object_id = :object_id AND `shadow_objects`.id = `shadow_object_relations`.real_object_id AND `shadow_objects`.type = :type AND `shadow_objects`.namespace = :namespace';
                $exists = $this->database->query($sql, array('namespace' => $params['namespace'], 'type' => $params['type'], 'user_id' => $build->relation->user, 'object_id' => $build->objectID));

                return $exists ? $exists[0]->value : null;
            } else {

                // Fetch Objects Users
                $sql = 'select *, (count/positive) as average from `shadow_objects` WHERE `shadow_objects`.operation="multary" AND `shadow_objects`.object_id = :object_id AND `shadow_objects`.type = :type AND `shadow_objects`.namespace = :namespace';
                $users = $this->database->query($sql, array('namespace' => $params['namespace'], 'type' => $params['type'], 'object_id' => $build->objectID));

                return array('num_votes' => $users[0]->count, 'total_votes_count' => $users[0]->positive, 'avg_vote' => $users[0]->average);

            }
        } else {

            /*
             * ------------------------------------------------------
             *  Multary Popular Algorithm
             * ------------------------------------------------------
             *
             * Determines the Bayesian Average, the mean of a
             * poptulation with data from the populations being used
             * as a way to minimize deviations/randomness
             *
             * http://en.wikipedia.org/wiki/Bayesian_average
             */

            $limit = ($build->limit) ? ' LIMIT ' . $build->limit : '';

            $sql = 'SELECT AVG(count) AS avg_num_votes, count/positive AS avg_rating FROM shadow_objects WHERE `shadow_objects`.operation="multary" AND count > 0 AND type=:type AND namespace=:namespace';

            $averages = $this->database->query($sql, array('type' => $build->type, 'namespace' => $build->namespace));

            if ($averages) {

                $objectSql = 'SELECT *, ((:avg_num_votes * :avg_rating) + (count * (count/positive) )) / (:avg_num_votes + count) as rank FROM shadow_objects WHERE operation = "multary" AND type=:type AND namespace=:namespace ORDER BY rank DESC'.$limit;

                $objects = $this->database->query($objectSql, array('avg_rating' => $averages[0]->avg_rating, 'avg_num_votes' => $averages[0]->avg_num_votes, 'type' => $build->type, 'namespace' => $build->namespace));

                $temp = array();

                $temp['count'] = count($objects);
                $temp['type'] = $build->type;

                if ($objects) {
                    foreach ($objects as $obj) {
                        $temp['objects'][] = array('id' => $obj->object_id, 'num_votes' => $obj->count, 'total_votes_count' => $obj->positive, 'avg_vote' => ($obj->count / $obj->positive), 'rank' => $obj->rank);
                    }
                }
                return $temp;

            }
        }
    }
    

    /*
     * Clear Data by Object Type
     */
    public function clearDataByType($type) {
        $sql = 'DELETE shadow_objects.*, shadow_object_relations.* FROM shadow_object_relations LEFT JOIN shadow_objects ON shadow_object_relations.real_object_id = shadow_objects.id WHERE shadow_objects.type = ?';
        $this->database->execute($sql, array($type));

        $sql = 'DELETE from `shadow_meta` WHERE type = ?';
        $this->database->execute($sql, array($type));
    }

    /*
     * Delete expired meta
     * Auto clean on script end (after HTTP headers sent)
     */
    function __destruct() {
        $sql = 'DELETE FROM `shadow_meta` WHERE `expires` > '.date("Y-m-d H:i:s");
        $this->database->execute($sql, array());
    }
}