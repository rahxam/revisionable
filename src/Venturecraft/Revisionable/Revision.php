<?php

namespace Venturecraft\Revisionable;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Revision.
 *
 * Base model to allow for revision history on
 * any model that extends this model
 *
 * (c) Venture Craft <http://www.venturecraft.com.au>
 */
class Revision extends Eloquent
{
    /**
     * @var string
     */
    public $table = 'revision';

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

    /**
     * Revisionable.
     *
     * Grab the revision history for the model that is calling
     *
     * @return array revision history
     */
    public function revisionable()
    {
        return $this->morphTo();
    }

    /**
     * Field Name
     *
     * Returns the field that was updated, in the case that it's a foreign key
     * denoted by a suffix of "Id", then "Id" is simply stripped
     *
     * @return string field
     */
    public function fieldName()
    {
        if (strpos($this->key, 'Id')) {
            return str_replace('Id', '', $this->key);
        } else {
            return $this->key;
        }
    }


    /**
     * Old Value.
     *
     * Grab the old value of the field, if it was a foreign key
     * attempt to get an identifying name for the model.
     *
     * @return string old value
     */
    public function oldValue()
    {
        return $this->getValue('old');
    }


    /**
     * New Value.
     *
     * Grab the new value of the field, if it was a foreign key
     * attempt to get an identifying name for the model.
     *
     * @return string old value
     */
    public function newValue()
    {
        return $this->getValue('new');
    }

    private static function camel_case($string)
    {
		// underscored to lower-camelcase 
		// e.g. "this_method_name" -> "thisMethodName" 
		return preg_replace('/_(.?)/e',"strtoupper('$1')",$string); 
    }

    private static function studly_case($string)
    {
    	// e.g. "this_method_name" -> "ThisMethodName" 
		return preg_replace('/(?:^|_)(.?)/e',"strtoupper('$1')",$string); 
    }


    /**
     * Responsible for actually doing the grunt work for getting the
     * old or new value for the revision.
     *
     * @param  string $which old or new
     *
     * @return string value
     */
    private function getValue($which = 'new')
    {
        $whichValue = $which . 'Value';

        // First find the main model that was updated
        $mainModel = $this->revisionableType;
        // Load it, WITH the related model
        if (class_exists($mainModel)) {
            $mainModel = new $mainModel;

            try {
                if ($this->isRelated()) {
                    $relatedModel = $this->getRelatedModel();

                    // Now we can find out the namespace of of related model
                    if (!method_exists($mainModel, $relatedModel)) {
                        $relatedModel = self::camel_case($relatedModel); // for cases like published_statusId
                        if (!method_exists($mainModel, $relatedModel)) {
                            throw new \Exception('Relation ' . $relatedModel . ' does not exist for ' . $mainModel);
                        }
                    }
                    $relatedClass = $mainModel->$relatedModel()->getRelated();

                    // Finally, now that we know the namespace of the related model
                    // we can load it, to find the information we so desire
                    $item = $relatedClass::find($this->$whichValue);

                    if (is_null($this->$whichValue) || $this->$whichValue == '') {
                        $item = new $relatedClass;

                        return $item->getRevisionNullString();
                    }
                    if (!$item) {
                        $item = new $relatedClass;

                        return $this->format($this->key, $item->getRevisionUnknownString());
                    }

                    // Check if model use RevisionableTrait
                    if(method_exists($item, 'identifiableName')) {
                        // see if there's an available mutator
                        $mutator = 'get' . self::studly_case($this->key) . 'Attribute';
                        if (method_exists($item, $mutator)) {
                            return $this->format($item->$mutator($this->key), $item->identifiableName());
                        }

                        return $this->format($this->key, $item->identifiableName());
                    }
                }
            } catch (\Exception $e) {
                echo "Exception";
            }

            // if there was an issue
            // or, if it's a normal value

            $mutator = 'get' . self::studly_case($this->key) . 'Attribute';
            if (method_exists($mainModel, $mutator)) {
                return $this->format($this->key, $mainModel->$mutator($this->$whichValue));
            }
        }

        return $this->format($this->key, $this->$whichValue);
    }

    /**
     * Return true if the key is for a related model.
     *
     * @return bool
     */
    private function isRelated()
    {
        $isRelated = false;
        $idSuffix = 'Id';
        $pos = strrpos($this->key, $idSuffix);

        if ($pos !== false
            && strlen($this->key) - strlen($idSuffix) === $pos
        ) {
            $isRelated = true;
        }

        return $isRelated;
    }

    /**
     * Return the name of the related model.
     *
     * @return string
     */
    private function getRelatedModel()
    {
        $idSuffix = 'Id';

        return substr($this->key, 0, strlen($this->key) - strlen($idSuffix));
    }

    /**
     * User Responsible.
     *
     * @return User user responsible for the change
     */
    public function userResponsible()
    {
        if (empty($this->userId)) { return false; }
        $userModel = app('config')->get('auth.model');

        if (empty($userModel)) {
            $userModel = app('config')->get('auth.providers.users.model');
            if (empty($userModel)) {
                return false;
            }
        }
        if (!class_exists($userModel)) {
            return false;
        }
        return $userModel::find($this->userId);
    }

    /**
     * Returns the object we have the history of
     *
     * @return Object|false
     */
    public function historyOf()
    {
        if (class_exists($class = $this->revisionableType)) {
            return $class::find($this->revisionableId);
        }

        return false;
    }

    /*
     * Examples:
    array(
        'public' => 'boolean:Yes|No',
        'minimum'  => 'string:Min: %s'
    )
     */
    /**
     * Format the value according to the $revisionFormattedFields array.
     *
     * @param  $key
     * @param  $value
     *
     * @return string formatted value
     */
    public function format($key, $value)
    {
        $relatedModel = $this->revisionableType;
        $relatedModel = new $relatedModel;
        return $value;
    }
}
