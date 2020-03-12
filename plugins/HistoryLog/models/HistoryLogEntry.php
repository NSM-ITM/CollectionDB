<?php
/**
 * A History Log entry
 *
 * @package Historylog
 *
 */
class HistoryLogEntry extends Omeka_Record_AbstractRecord
{
    const OPERATION_CREATE = 'create';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    const OPERATION_IMPORT = 'import';
    const OPERATION_EXPORT = 'export';

    private $_validRecordTypes = array(
        'Item',
        'Collection',
        'File',
    );

    /**
     * @var int The record ID.
     */
    public $id;

    /**
     * @var string The type of the record associated with this log entry.
     */
    public $record_type;

    /**
     * @var int The id of the record associated with this log entry.
     */
    public $record_id;

    /**
     * @var int The id of the Collection record in which the associated Item
     * record was stored at the time of log entry, or the id of the Item record
     * in which the associated File record was stored.
     */
    public $part_of;

    /**
     * @var int The id of the User record who performed the logged action.
     */
    public $user_id;

    /**
     * @var string The limited list of type of operation being logged:
     * "create", "update", "delete", "import", "export".
     * @internal Because of Zend, the reserved word "action" cannot be used.
     */
    public $operation;

    /**
     * @var string The UTF formatted date and time when the log took place.
     */
    public $added;

    /**
     * Records related to an Item.
     *
     * @var array
     */
    protected $_related = array(
        'Record' => 'getRecord',
        'Changes' => 'getChanges',
    );

    /**
     * Set of non-persistent Change objects attached to the event.
     *
     * @var array
     * @see HistoryLogEntry::_saveChanges()
     * @see HistoryLogEntry::_getChanges()
     */
    private $_changes = array();

    /**
     * @var array The changes related to the event before saving.
     */
    private $_changesToLog;

    /**
     * @var array List of old texts used to get changes for an update.
     * If null during an update, all element texts will be saved.
     */
    private $_oldElements;

    /**
     * Initialize the mixins for a record.
     */
    protected function _initializeMixins()
    {
        // TODO The acl resource interface is useless?
        $this->_mixins[] = new Mixin_Owner($this, 'user_id');
        $this->_mixins[] = new Mixin_Timestamp($this, 'added', null);
    }

    /**
     * Prepare the log of update on a record: store old metadata.
     *
     * This is the recommended method to log an update.
     *
     * @param Object $record
     * @return boolean False if an error occur, else true.
      */
    public function prepareEvent($record)
    {
        // getAllElementTextsByElement() is available only in Omeka 2.3.
        $elementTexts = $record->getAllElementTexts();
        if (is_null($elementTexts)) {
            return false;
        }

        $oldElements = array();
        foreach ($elementTexts as $elementText) {
            $oldElements[$elementText->element_id][] = $elementText->text;
        }
        $this->_oldElements = $oldElements;

        return true;
    }

    /**
     * Log an operation on a record and set associated values.
     *
     * This is the recommended method to log an event.
     *
     * @internal Checks  are done here and during validation.
     *
     * @param Object|array $record The Omeka record to log. It should exist at
     * the time of logging. If the operation is "update", it must be an object.
     * @param User|integer $user
     * @param string $operation The type of event to log (e.g. "create"...).
     * @param string|array $change An extra piece of type specific data for the
     * log. When the operation is "create", the change of elements is
     * automatically set. For "update", the change may be automatically set if
     * the method prepareEvent() has been called before. Else, this param should
     * be filled with the array of old texts, ordered by element id. For
     * "delete", there is no change. For "import" and "export", this is an
     * external content that can't be determined inside the history log entry.
     * @return boolean False if an error occur, else true.
      */
    public function logEvent($record, $user, $operation, $change = null)
    {
        $this->setOperation($operation);
        if (empty($this->operation)) {
            return false;
            // throw __('Operation "%s" is not allowed.', $operation);
        }

        // Special check for "create' and "'update": an object is required to
        // find elements (in fact, for "create", only for a manual insert).
        if (!is_object($record) && in_array($this->operation, array(
                HistoryLogEntry::OPERATION_CREATE,
                HistoryLogEntry::OPERATION_UPDATE,
            ))) {
            return false;
            // throw __('Operations "Create" and "Update" cannot be logged if the record is not an object.');
        }

        // Get the record object if it is an array.
        if (is_array($record)) {
            $record = $this->getRecord();
            if (empty($record)) {
                return false;
            }
        }

        if (!$this->_isLoggable(get_class($record), $record->id)) {
            return false;
        }

        $this->setRecordType(get_class($record));
        $this->setRecordId($record->id);

        $userId = is_object($user) ? $user->id : $user;
        $this->setUserId($userId);

        // Set the "part_of" if needed.
        switch ($this->record_type) {
            case 'Item':
                $this->setPartOf($record->collection_id);
                break;
            case 'File':
                $this->setPartOf($record->item_id);
                break;
            case 'Collection':
            default:
                $this->setPartOf(0);
        }

        // Set change according to the operation.
        switch ($this->operation) {
            case HistoryLogEntry::OPERATION_CREATE:
                $changes = $this->_findAlteredElementsForCreatedRecord($record);
                $this->_setChangesToLog($changes);
                break;
            case HistoryLogEntry::OPERATION_UPDATE:
                // This allows to save change without preparation.
                if (is_null($change)) {
                    $changes = $this->_findAlteredElementsForUpdatedRecord($record);
                    $this->_setChangesToLog($changes);
                }
                // No preparation and no check.
                else {
                    $this->_setChangesToLog($change);
                }
                break;
            case HistoryLogEntry::OPERATION_DELETE:
                // No change.
                break;
            case HistoryLogEntry::OPERATION_IMPORT:
            case HistoryLogEntry::OPERATION_EXPORT:
                $this->_setChangesToLog((string) $change);
                break;
        }

        return true;
    }

    /**
     * Helper to save the entry only if something is changed.
     *
     * This function can be used only during  creation.
     * This is the recommended way to save an event and to avoid empty changes.
     *
     * @return boolean|null Return null if the entry is already saved or when
     * there is no change.
     */
    public function saveIfChanged()
    {
        if ($this->_isChanged()) {
            return $this->save();
        }
        // Return null if no change.
    }

    /**
     * Helper to check if something has changed in the record.
     *
     * @return boolean
     */
    protected function _isChanged()
    {
        // Don't update a log event.
        if (!empty($this->id)) {
            return false;
        }
        // Update if there is data to log.
        if (!empty($this->_changesToLog)) {
            return true;
        }
        // There is no data to log. Nevertheless, log the operation, except for
        // update.
        if ($this->operation != HistoryLogEntry::OPERATION_UPDATE) {
            return true;
        }

        // This is an update without change, so this an internal update. For a
        // file, it may be new derivatives. For an item, it may be an update of
        // the status public or featured, a change of collection, files added,
        // reordered or removed, etc. Currently, only the change of a collection
        // of an item is logged. Data about files are saved separately.

        // Check if the collection of the item changed.
        if ($this->record_type == 'Item') {
            // Get the old record to check it.
            try {
                $oldRecord = get_record_by_id('Item', $this->record_id);
            } catch(Exception $e) {
                return true;
            }
            return $oldRecord->collection_id != $this->part_of;
        }

        // For all other cases, there is no change.
        return false;
    }

    /**
     * Sets the record type.
     *
     * @internal Check is done during validation.
     *
     * @param int $id The record type
     */
    public function setRecordType($type)
    {
        $this->record_type = $type;
    }

    /**
     * Sets the record id.
     *
     * @param int $id The record id
     */
    public function setRecordId($id)
    {
        $this->record_id = (integer) $id;
    }

    /**
     * Sets the part of id.
     *
     * @param int $part_of The part of.
     */
    public function setPartOf($partOf)
    {
        $this->part_of = (integer) $partOf;
    }

    /**
     * Sets the user id.
     *
     * @param int $id The user id.
     */
    public function setUserId($id)
    {
        $this->user_id = (integer) $id;
    }

    /**
     * Set the operation.
     *
     * @param string $operation
     */
    public function setOperation($operation)
    {
        if ($this->_isOperationValid($operation)) {
            $this->operation = $operation;
        }
    }

    /**
     * Get the record object. It may be deleted.
     *
     * @return Record|null Therecord, else null if deleted.
     */
    public function getRecord()
    {
        // Manage the case where record type has been removed.
        if (class_exists($this->record_type)) {
            return $this->getTable($this->record_type)->find($this->record_id);
        }
    }

    /**
     * Returns the list of Change objects related to this entry.
     *
     * @return HistoryLogChange Array of Change objects related to the entry.
     */
    public function getChanges()
    {
        if (empty($this->_changes)) {
            $this->_changes =$this->getTable('HistoryLogChange')
                ->findByEntry($this->id);
        }
        return $this->_changes;
    }

    /**
     * Returns the list of changed element ids related to this entry.
     *
     * @return array List of element ids. 0 is not returned, because it's not an
     * element id.
     */
    public function getElementIds()
    {
        return $this->getTable('HistoryLogChange')
            ->getElementIds($this);
    }

    /**
     * Returns the list of changed element ids related to the record.
     *
     * @return array List of element ids. 0 is not returned, because it's not an
     * element id.
     */
    public function getElementIdsByRecord()
    {
        $record = array(
            'record_type' => $this->record_type,
            'record_id' => $this->record_id,
        );
        return $this->getTable('HistoryLogEntry')
            ->getElementIdsForRecord($record);
    }

    /**
     * Executes after the record is saved.
     *
     * @internal See Mixin_ElementText::beforeSaveElements() for a fully secured
     * way to save changes. This is useless here, because changes are set here.
     *
     * @param array $args
     */
    protected function afterSave($args)
    {
        $this->_saveChanges();
    }

    /**
     * Add one or multiple changes.
     *
     * @param string|array $changes
     */
    protected function _setChangesToLog($changes)
    {
        $this->_changesToLog = $changes;
    }

    /**
     * Save changes.
     *
     * Entries are not designed to be updated, so the current changes are kept
     * and can't be removed by normal ways.
     */
    protected function _saveChanges()
    {
        $changes = $this->_changesToLog;
        if (empty($changes)) {
            return;
        }

        // Simplify the process for strings.
        if (!is_array($changes)) {
            $changes = array(
                // This is not an element id, so "0".
                0 => array(
                    // There is no process, only a text.
                    array(HistoryLogChange::TYPE_NONE => (string) $changes),
            ));
        }

        foreach ($changes as $elementId => $texts) {
            foreach ($texts as $process) {
                $change = new HistoryLogChange();
                $change->entry_id = $this->id;
                $change->element_id = $elementId;
                $change->type = key($process);
                $change->text = reset($process);
                $change->save();
            }
        }

        // Reset the changes in order to get old and new ones.
        // Normally, there is no old change.
        $this->_changes = null;
        $this->getChanges();
    }

    /**
     * Helper to find out altered elements of a created record.
     *
     * Notes:
     * - Each text of repetitive field is returned.
     * - Checks are done according to the natural order.
     *
     * @param Record $record Record must be an object.
     * @return array|null Associative array of element ids and array of texts of
     * created elements.
     */
    protected function _findAlteredElementsForCreatedRecord($record)
    {
        // Get the current list of elements.
        $newElements = array();

        // If there are elements, the record is created via post (manually).
        $viaPost = isset($record->Elements);
        // Manual insert.
        if ($viaPost) {
            foreach ($record->Elements as $elementId => $elementTexts) {
                foreach ($elementTexts as $elementText) {
                    // strlen() is used to allow values like "0".
                    // But Omeka uses a simple empty() check.
                    if (strlen($elementText['text']) > 0) {
                        $newElements[$elementId][] = array(
                            HistoryLogChange::TYPE_CREATE => $elementText['text'],
                        );
                    }
                }
            }
        }

        // Else this is an automatic creation, without post.
        else {
            $elementTexts = get_records(
                'ElementText',
                array(
                    'record_type' => get_class($record),
                    'record_id' => $record->id),
                0);

            if (is_null($elementTexts)) {
                // TODO Throw an error? Normally, never here.
                return;
            }

            foreach ($elementTexts as $elementText) {
                $newElements[$elementText->element_id][] = array(
                    HistoryLogChange::TYPE_CREATE => $elementText['text'],
                );
            }
        }

        return $newElements;
    }

    /**
     * Helper to find out altered elements of an updated  record.
     *
     * Notes:
     * - Each text of repetitive field is returned.
     * - Checks are done according to the natural order.
     *
     * @param Record $record Record must be an object.
     * @param string $operation Only "create" and "update" have elements.
     * @return array|null Associative array of element ids and array of texts of
     * altered elements.
     */
    protected function _findAlteredElementsForUpdatedRecord($record)
    {
        // The operation is an update. The old record and the new one are
        // compared to check if there are altered (added, updated, removed)
        // element texts.

        $oldElements = $this->_oldElements;
        // Normally, should not be there. So all elements will be created.
        if (is_null($oldElements)) {
            return null;
        }

        // Get the current list of elements.
        $newElements = array();

        // If there are elements, the record is created via post (manually).
        $viaPost = isset($record->Elements);
        // Manual update.
        if ($viaPost) {
            foreach ($record->Elements as $elementId => $elementTexts) {
                foreach ($elementTexts as $elementText) {
                    // strlen() is used to allow values like "0".
                    if (strlen($elementText['text']) > 0) {
                        $newElements[$elementId][] = $elementText['text'];
                    }
                }
            }
        }

        // Automatic update.
        else {
            $elementTexts = get_records(
                'ElementText',
                array(
                    'record_type' => get_class($record),
                    'record_id' => $record->id),
                0);

            if (is_null($elementTexts)) {
                // TODO Throw an error? Normally, never here.
                return;
            }

            foreach ($elementTexts as $elementText) {
                $newElements[$elementText->element_id][] = $elementText->text;
            }
        }

        // Updated elements are the ones that have been added, updated or
        // deleted.
        $updatedElements = array();
        foreach ($oldElements as $elementId => $oldTexts) {
            // Updated element.
            if (isset($newElements[$elementId])) {
                $newTexts = $newElements[$elementId];
                foreach ($oldTexts as $key => $oldText) {
                    if (isset($newTexts[$key])) {
                        if ($newTexts[$key] !== $oldText) {
                            $updatedElements[$elementId][] = array(
                                HistoryLogChange::TYPE_UPDATE => $newTexts[$key],
                            );
                        }
                        // Else no change.
                    }
                    // The value has been deleted. The text is kept ot distinct
                    // the full remove of the element.
                    else {
                        $updatedElements[$elementId][] = array(
                            HistoryLogChange::TYPE_DELETE => $oldText,
                        );
                    }
                }
                // Check if there are more keys in the new texts.
                if (count($newTexts) > count($oldTexts)) {
                    for ($i = count($oldTexts); $i < count($newTexts); $i++) {
                        $updatedElements[$elementId][] = array(
                            HistoryLogChange::TYPE_CREATE => $newTexts[$i],
                        );
                    }
                }
            }
            // Deleted all values (single or multiple) of one field: log it one
            // time only.
            else {
                $updatedElements[$elementId][] = array(
                    HistoryLogChange::TYPE_DELETE => '',
                );
            }
        }

        // Check new texts for elements that weren't in the old ones.
        $newElementsIds = array_diff(array_keys($newElements), array_keys($oldElements));
        foreach ($newElements as $elementId => $newTexts) {
            if (in_array($elementId, $newElementsIds)) {
                foreach ($newTexts as $newText) {
                    $updatedElements[$elementId][] = array(
                        HistoryLogChange::TYPE_CREATE => $newText,
                    );
                }
            }
        }

        return $updatedElements;
    }

    /**
     * Helper to get the list of referenced elements for the entry.
     *
     * @return array|null List of elements by element id. If an element has been
     * removed, its value is null.
     */
    protected function _getReferencedElements()
    {
        $elementIds = $this->getElementIds();
        if (empty($elementIds)) {
            return;
        }
        return $this->_getElementsFromIds($elementIds);
    }

    /**
     * Helper to get the list of referenced elements for a record.
     *
     * @return array|null List of elements by element id. If an element has been
     * removed, its value is null.
     */
    protected function _getReferencedElementsByRecord()
    {
        $elementIds = $this->getElementIdsByRecord();
        if (empty($elementIds)) {
            return;
        }
        return $this->_getElementsFromIds($elementIds);
    }

    /**
     * Helper to get the list of elements from ids, even if removed.
     *
     * @param array $elementIds
     * @return array|null List of elements by element id. If an element has been
     * removed, its value is null.
     */
    protected function _getElementsFromIds($elementIds)
    {
        // Initialize the list of all element ids.
        $referenceds = array_fill_keys($elementIds, null);

        // Get the list of elements that still exist.
        $table = $this->_db->getTable('Element');
        $alias = $table->getTableAlias();
        $result = $table->findBySql($alias . '.id IN (' . implode(',', $elementIds) . ')');
        foreach ($result as $element) {
            $referenceds[$element->id] = $element;
        }

        return $referenceds;
    }

    // TODO Move all displays in a specific view helper.

    /**
     * Retrieve username of an omeka user by user ID.
     *
     * @return string The username of the Omeka user
     */
    public function displayUser()
    {
        $user = $this->getOwner();
        if (empty($user)) {
            return __('No user / deleted user [%d]', $this->user_id);
        }
        return $user->name . ' (' . $user->username . ')';
    }

    /**
     * Retrieve displayable name of an operation.
     *
     * @return string User displayable operation name.
     */
    public function displayOperation()
    {
        switch ($this->operation) {
            case HistoryLogEntry::OPERATION_CREATE:
                return __('Create');
            case HistoryLogEntry::OPERATION_UPDATE:
                return __('Update');
            case HistoryLogEntry::OPERATION_DELETE:
                return __('Delete');
            case HistoryLogEntry::OPERATION_IMPORT:
                return __('Import');
            case HistoryLogEntry::OPERATION_EXPORT:
                return __('Export');
            // Manage extra type of operation.
            default:
                return ucfirst($this->operation);
        }
    }

    /**
     * Retrieve "change" parameter for the displayable form.
     *
     * @return string The change in a human readable form.
     */
    public function displayChanges()
    {
        // The encoding is different depending on the type of event, so we
        // define different decoding methods for each event type.
        $changes = $this->getChanges();
        switch ($this->operation) {
            // Array for created and updated records.
            case HistoryLogEntry::OPERATION_CREATE:
                return $changes
                    ? $this->_displayElements()
                    : __('Created manually by user');

            case HistoryLogEntry::OPERATION_UPDATE:
                return $changes
                    ? $this->_displayElements()
                    // Internal update: file upload, public/featured...
                    : __('Internal update');

            // Nothing for delete.
            case HistoryLogEntry::OPERATION_DELETE:
                return '';

            // String for import and export.
            case HistoryLogEntry::OPERATION_IMPORT:
                $change = reset($changes);
                return empty($change->text)
                    ? ''
                    : __('Imported from %s', $change->text);

            case HistoryLogEntry::OPERATION_EXPORT:
                $change = reset($changes);
                return empty($change)
                    ? ''
                    : __('Exported to: %s', $change->text);
        }
    }

    /**
     * Helper to display the list of altered elements.
     *
     * @return string
     */
    protected function _displayElements()
     {
         // TODO Only the element name is needed.
        $elements = $this->_getReferencedElements();
        if (empty($elements)) {
            return __('No element.');
        }
        $changes = $this->getChanges();
        $result = array(
            __('Created') => array(),
            __('Updated') => array(),
            __('Deleted') => array(),
            __('Unchanged') => array(),
            __('Altered') => array(),
        );
        foreach ($changes as $change) {
            switch ($change->type) {
                case HistoryLogChange::TYPE_CREATE:
                    $type = __('Created');
                    break;
                case HistoryLogChange::TYPE_UPDATE:
                    $type = __('Updated');
                    break;
                case HistoryLogChange::TYPE_DELETE:
                    $type = __('Deleted');
                    break;
                case HistoryLogChange::TYPE_NONE:
                    $type = __('Unchanged');
                    break;
                default:
                    $type = __('Altered');
                    break;
            }
            $result[$type][] = empty($elements[$change->element_id])
                ? __('Unrecognized element #%d', $change->element_id)
                : $elements[$change->element_id]->name;
        }
        $result = array_filter($result);
        foreach ($result as $type => &$r) {
            $r = __('%s: %s', $type, implode(', ', array_unique($r)));
        }
        $result = implode(";\n", $result);
        return $result;
     }

    /**
     * Helper to display the list of altered elements.
     *
     * @return string
     */
    protected function _displayAlteredElements()
     {
         // TODO Only the element name is needed.
        $elements = $this->_getReferencedElements();
        if (empty($elements)) {
            return __('No element.');
        }
        $result = array();
        foreach ($elements as $elementId => $element) {
            $result[] = $element
                ? $element->name
                : __('Unrecognized element #%d', $elementId);
        }
        return __('Altered: %s', implode(', ', $result));
     }

    /**
     * Format a date in standard form.
     *
     * @return string The formatted dateTime
     */
    public function displayAdded()
    {
        // TODO Clearly, not yet fully implemented.
        return $this->added;
    }

    /**
     * Retrieves the current title, that may be different from the stored title.
     *
     * @return string The current Dublin Core title of the record if any.
     */
    public function displayCurrentTitle()
    {
        if ($this->operation == HistoryLogEntry::OPERATION_DELETE) {
            return __('Deleted record');
        }

        $record = $this->getRecord();
        if (empty($record)) {
            return __('Deleted record');
        }

        $titles = $record->getElementTexts('Dublin Core', 'Title');
        return isset($titles[0]) ? $titles[0] : '';
    }

    /**
     * Simple validation.
     */
    protected function _validate()
    {
        if (empty($this->record_id)) {
            $this->addError('record_id', __('Record cannot be empty.'));
        }
        if (!$this->_isLoggable()) {
            $this->addError('record_type', __('Type of record "%s" is not correct.', $this->record_type));
        }
        if (!$this->_isOperationValid()) {
            $this->addError('operation', __('Operation "%s" is not correct.', $this->operation));
        }
    }

    /**
     * Check if the record is loggable (item, collection, file).
     *
     * @param string $recordType
     * @param integer $recordId
     * @return boolean
     */
    protected function _isLoggable($recordType = null, $recordId = null)
    {
        if (is_null($recordType)) {
            $recordType = $this->record_type;
        }
        if (is_null($recordId)) {
            $recordId = $this->record_id;
        }
        return !empty($recordId)
            && in_array($recordType, $this->_validRecordTypes);
    }

    /**
     * Check if the operation is valid.
     *
     * @param string $operation
     * @return boolean
     */
    protected function _isOperationValid($operation = null)
    {
        if (is_null($operation)) {
            $operation = $this->operation;
        }
        return in_array($operation, array(
            HistoryLogEntry::OPERATION_CREATE,
            HistoryLogEntry::OPERATION_UPDATE,
            HistoryLogEntry::OPERATION_DELETE,
            HistoryLogEntry::OPERATION_IMPORT,
            HistoryLogEntry::OPERATION_EXPORT,
        ));
    }

    /**
     * Get a property or special value of this record.
     *
     * @param string $property
     * @return mixed
     */
    public function getProperty($property)
    {
        switch($property) {
            case 'record':
                return $this->getRecord();
            default:
                return parent::getProperty($property);
        }
    }

    /**
     * Get the ACL resource ID for the record.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'HistoryLogEntries';
    }
}
