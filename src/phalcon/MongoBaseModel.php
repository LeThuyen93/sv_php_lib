<?php
/**
 * Created by PhpStorm.
 * User: thuyenlv
 * Date: 6/7/18
 * Time: 11:38 PM
 */

namespace CodeBase;


use MongoDB\BSON\ObjectID;
use Phalcon\Mvc\Collection\Exception;
use Phalcon\Mvc\MongoCollection;

class MongoBaseModel extends MongoCollection
{
    public $id;
    public $code;
    public $active_status;
    public $created_source;
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;

    /**
     * Required const foreach inheritance
     * @required
     */
    const COLLECTION = '';

    /**
     * Define that this collection has auto increment id field
     */
    const AUTO_INC_ID = false;

    /**
     *  Define that this collection has auto generate code field
     */
    const AUTO_GEN_CODE = false;

    /**
     * Mongo const
     */
    const ACTIVE_STATUS = 1;
    const INACTIVE_STATUS = 0;

    const PROJECTION_ENABLE = 1;
    const PROJECTION_DISABLE = 0;

    public function onConstruct()
    {
        if (static::AUTO_GEN_CODE) {
            $this->code = $this->getAutoGenerateCode();
        }
    }

    /**
     * @return string
     */
    public function getAutoGenerateCode()
    {
        $object_id = new ObjectID();
        $random_id_length = 10;
        $rnd_id = crypt(uniqid((string)$object_id, 1), '');
        $rnd_id = strip_tags(stripslashes($rnd_id));
        $rnd_id = str_replace(".","",$rnd_id);
        $rnd_id = strrev(str_replace("/","",$rnd_id));
        $rnd_id = substr($rnd_id,0,$random_id_length);
        return $rnd_id;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     *
     * @throws Exception
     */
    public function save()
    {
        $dependencyInjector = $this->_dependencyInjector;

        if (!is_object($dependencyInjector)) {
            throw new Exception(
                "A dependency injector container is required to obtain the services related to the ODM"
            );
        }

        $source = $this->getSource();

        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }

        $connection = $this->getConnection();

        $collection = $connection->selectCollection($source);

        $exists = $this->_exists($collection);

        if (false === $exists) {
            $this->_operationMade = self::OP_CREATE;
        } else {
            $this->_operationMade = self::OP_UPDATE;
        }

        /**
         * The messages added to the validator are reset here
         */
        $this->_errorMessages = [];

        $disableEvents = self::$_disableEvents;

        /**
         * Execute the preSave hook
         */
        if (false === $this->_preSave($dependencyInjector, $disableEvents, $exists)) {
            return false;
        }

        if (static::AUTO_INC_ID) {
            $this->id = (int)$connection->command(['eval' => 'autoId("' . $this->getSource() . '")'])->toArray()[0]['retval'];
        }

        $data = $this->toArray();
        $data = array_filter($data);

        /**
         * We always use safe stores to get the success state
         * Save the document
         */
        switch ($this->_operationMade) {
            case self::OP_CREATE:
                $status = $collection->insertOne($data);
                break;

            case self::OP_UPDATE:
                unset($data['_id']);
                $status = $collection->updateOne(['_id' => $this->_id], ['$set' => $data]);
                break;

            default:
                throw new Exception('Invalid operation requested for ' . __METHOD__);
        }

        $success = false;

        if ($status->isAcknowledged()) {
            $success = true;

            if (false === $exists) {
                $this->_id = $status->getInsertedId();
                $this->_dirtyState = self::DIRTY_STATE_PERSISTENT;
            }
        }

        /**
         * Call the postSave hooks
         */
        return $this->_postSave($disableEvents, $success, $exists);
    }

    /**
     * @param $data
     * @return $this
     */
    public static function rebuild($data)
    {
        return $data;
    }

    /**
     * Returns collection name mapped in the model
     * @return string
     */
    public function getSource()
    {
        // Define collection's name
        return static::COLLECTION;
    }

    /**
     * @param string $code
     * @return $this
     */
    public static function findByCode($code)
    {
        $result = self::findFirst([
            [
                'code' => $code
            ]
        ]);
        return self::rebuild($result);
    }
}