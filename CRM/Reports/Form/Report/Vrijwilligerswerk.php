<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Reports_Form_Report_Vrijwilligerswerk extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_relField = FALSE;
  protected $_exposeContactID = FALSE;

  protected $_customGroupExtends = array('Case');

  protected $activityTypes = array();

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->activityTypes = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, $condition);

    $this->case_types = CRM_Case_PseudoConstant::caseType();
    $this->case_statuses = CRM_Core_OptionGroup::values('case_status');
    $rels = CRM_Core_PseudoConstant::relationshipType();
    foreach ($rels as $relid => $v) {
      $this->rel_types[$relid] = $v['label_b_a'];
    }

    $this->deleted_labels = array(
      '' => ts('- select -'),
      0 => ts('No'),
      1 => ts('Yes'),
    );

    $this->_columns = array(
      'civicrm_c2' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'client_name' => array(
            'name' => 'sort_name',
            'title' => ts('Client'),
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_case' => array(
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => array(
          'id' => array(
            'title' => ts('Case ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'subject' => array(
            'title' => ts('Case Subject'),
            'default' => FALSE,
          ),
          'status_id' => array(
            'title' => ts('Status'),
            'default' => FALSE,
          ),
          'case_type_id' => array(
            'title' => ts('Case Type'),
            'default' => FALSE,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'duration' => array(
            'title' => ts('Duration (Days)'),
            'default' => FALSE,
          ),
          'is_deleted' => array(
            'title' => ts('Deleted?'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'filters' => array(
          'start_date' => array(
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'case_type_id' => array(
            'title' => ts('Case Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('case_type_id', 'search'),
          ),
          'status_id' => array(
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('status_id', 'search'),
          ),
          'is_deleted' => array(
            'title' => ts('Deleted?'),
            'type' => CRM_Report_Form::OP_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->deleted_labels,
            'default' => 0,
          ),
          'activity_types' => array(
            'pseudofield' => true,
            'title' => ts('Activity type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,

          ),
        ),
      ),
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Staff Member'),
            'default' => FALSE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Staff Member'),
          ),
        ),
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'filters' => array(
          'relationship_type_id' => array(
            'title' => ts('Staff Relationship'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->rel_types,
          ),
        ),
      ),
      'civicrm_relationship_type' => array(
        'dao' => 'CRM_Contact_DAO_RelationshipType',
        'fields' => array(
          'label_b_a' => array(
            'title' => ts('Relationship'),
            'default' => FALSE,
          ),
        ),
      ),
      'civicrm_case_contact' => array(
        'dao' => 'CRM_Case_DAO_CaseContact',
      ),
    );

    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            if ($tableName == 'civicrm_relationship_type') {
              $this->_relField = TRUE;
            }

            if ($fieldName == 'duration') {
              $select[] = "IF({$table['fields']['end_date']['dbAlias']} Is Null, '', DATEDIFF({$table['fields']['end_date']['dbAlias']}, {$table['fields']['start_date']['dbAlias']})) as {$tableName}_{$fieldName}";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    if (empty($fields['relationship_type_id_value']) &&
      (array_key_exists('sort_name', $fields['fields']) ||
        array_key_exists('label_b_a', $fields['fields']))
    ) {
      $errors['fields'] = ts('Either filter on at least one relationship type, or de-select Staff Member and Relationship from the list of fields.');
    }
    if ((!empty($fields['relationship_type_id_value']) ||
        !empty($fields['sort_name_value'])) &&
      (!array_key_exists('sort_name', $fields['fields']) ||
        !array_key_exists('label_b_a', $fields['fields']))
    ) {
      $errors['fields'] = ts('To filter on Staff Member or Relationship, please also select Staff Member and Relationship from the list of fields.');
    }
    return $errors;
  }

  public function from() {

    $cc = $this->_aliases['civicrm_case'];
    $c = $this->_aliases['civicrm_contact'];
    $c2 = $this->_aliases['civicrm_c2'];
    $cr = $this->_aliases['civicrm_relationship'];
    $crt = $this->_aliases['civicrm_relationship_type'];
    $ccc = $this->_aliases['civicrm_case_contact'];

    if ($this->_relField) {
      $this->_from = "
            FROM civicrm_contact $c
inner join civicrm_relationship $cr on {$c}.id = ${cr}.contact_id_b
inner join civicrm_case $cc on ${cc}.id = ${cr}.case_id
inner join civicrm_relationship_type $crt on ${crt}.id=${cr}.relationship_type_id
inner join civicrm_case_contact $ccc on ${ccc}.case_id = ${cc}.id
inner join civicrm_contact $c2 on ${c2}.id=${ccc}.contact_id
";
    }
    else {
      $this->_from = "
            FROM civicrm_case $cc
inner join civicrm_case_contact $ccc on ${ccc}.case_id = ${cc}.id
inner join civicrm_contact $c2 on ${c2}.id=${ccc}.contact_id
";
    }
  }

  public function where() {
    $clauses = array();
    $this->_having = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          // respect pseudofield to filter spec so fields can be marked as
          // not to be handled here
          if (!empty($field['pseudofield'])) {
            continue;
          }

          $clause = NULL;
          if (CRM_Utils_Array::value("operatorType", $field) & CRM_Report_Form::OP_DATE
          ) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to,
              CRM_Utils_Array::value('type', $field)
            );
          }
          else {

            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($fieldName == 'case_type_id') {
              $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
              if (!empty($value)) {
                $operator = '';
                if ($op == 'notin') {
                  $operator = 'NOT';
                }

                $regexp = "[[:cntrl:]]*" . implode('[[:>:]]*|[[:<:]]*', $value) . "[[:cntrl:]]*";
                $clause = "{$field['dbAlias']} {$operator} REGEXP '{$regexp}'";
              }
              $op = NULL;
            }

            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
  }

  public function groupBy() {
    $this->_groupBy = "";
  }

  public function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
		if ($this->_outputMode == 'csv') {
			// Add activities to the rows.
			$newRows = array();
			unset($this->_columnHeaders['manage']);
			$columnHeaders = array_keys($this->_columnHeaders);
			$newRowHeader = array();
			foreach($this->_columnHeaders as $key => $header) {
				$newRowHeader[$key] = $header['title'];
			}
			foreach($rows as $row) {
				$newRow = $row;
				unset($newRow['activities']);
				$newRows[] = $newRowHeader;
				$newRows[] = $newRow;
				$columnCount = count($newRow);
				$new_activity_row[$columnHeaders[0]] = '';
				$currentColumn = 5;
				$new_activity_row[$columnHeaders[1]] = 'Datum';
				$new_activity_row[$columnHeaders[2]] = 'Activiteitstype';
				$new_activity_row[$columnHeaders[3]] = 'Onderwerp';
				$new_activity_row[$columnHeaders[4]] = 'Details';
				$activityRowColumnCount = count($new_activity_row);
				$columnsToAdd = $columnCount - $activityRowColumnCount;
				for($i=0; $i < $columnsToAdd; $i++) {
					$new_activity_row[$columnHeaders[$currentColumn]] = '';
					$currentColumn++;
				}
				$newRows[] = $new_activity_row;
				foreach($row['activities'] as $activity_row) {
					$new_activity_row = array();
					$new_activity_row[$columnHeaders[0]] = '';
					$columnCount = 1;
					foreach($activity_row as $column) {
						$new_activity_row[$columnHeaders[$columnCount]] = $column;
						$columnCount++;
					}
					$activityRowColumnCount = count($new_activity_row);
					$columnsToAdd = $columnCount - $activityRowColumnCount;
					for($i=0; $i < $columnsToAdd; $i++) {
						$new_activity_row[$columnHeaders[$columnCount]] = '';
						$columnCount++;
					}
					$newRows[] = $new_activity_row;
				}
			}
			$rows = $newRows;
		}
    $this->endPostProcess($rows);
  }

  /**
   * Modify column headers.
   */
  public function modifyColumnHeaders() {
    $this->_columnHeaders['manage'] = array('title' => ts('Manage case'));
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('civicrm_case_status_id', $row)) {
        if ($value = $row['civicrm_case_status_id']) {
          $rows[$rowNum]['civicrm_case_status_id'] = $this->case_statuses[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_case_case_type_id', $row) &&
        !empty($rows[$rowNum]['civicrm_case_case_type_id'])
      ) {
        $value = $row['civicrm_case_case_type_id'];
        $typeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
        $value = array();
        foreach ($typeIds as $typeId) {
          if ($typeId) {
            $value[$typeId] = $this->case_types[$typeId];
          }
        }
        $rows[$rowNum]['civicrm_case_case_type_id'] = implode(', ', $value);
        $entryFound = TRUE;
      }

      // convert Case ID and Subject to links to Manage Case
      if (array_key_exists('civicrm_case_id', $row) &&
        !empty($rows[$rowNum]['civicrm_c2_id'])
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/case",
          'reset=1&action=view&cid=' . $row['civicrm_c2_id'] . '&id=' .
          $row['civicrm_case_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['manage'] = ts('Manage Case');
        $rows[$rowNum]['manage_link'] = $url;
        $rows[$rowNum]['manage_hover'] = ts("Manage Case");

        $rows[$rowNum]['activities'] = $this->getActivities($row['civicrm_case_id']);

        try {
          $imageUrl = civicrm_api3('Contact', 'getvalue', array(
            'return' => "image_URL",
            'id' => $row['civicrm_c2_id'],
          ));
          $rows[$rowNum]['civicrm_c2_client_name_imageurl'] = $imageUrl;
          list($imageWidth, $imageHeight) = getimagesize(CRM_Utils_String::unstupifyUrl($imageUrl));
          list($imageThumbWidth, $imageThumbHeight) = CRM_Contact_BAO_Contact::getThumbSize($imageWidth, $imageHeight);
          $rows[$rowNum]['civicrm_c2_client_name_imageurl_width'] = $imageThumbWidth;
          $rows[$rowNum]['civicrm_c2_client_name_imageurl_height'] = $imageThumbHeight;
        } catch (Exception $e) {
          // Do nothing.
        }

        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_case_subject', $row) &&
        !empty($rows[$rowNum]['civicrm_c2_id'])
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/case",
          'reset=1&action=view&cid=' . $row['civicrm_c2_id'] . '&id=' .
          $row['civicrm_case_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_case_subject_link'] = $url;
        $rows[$rowNum]['civicrm_case_subject_hover'] = ts("Manage Case");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_case_is_deleted', $row)) {
        $value = $row['civicrm_case_is_deleted'];
        $rows[$rowNum]['civicrm_case_is_deleted'] = $this->deleted_labels[$value];
        $entryFound = TRUE;
      }



      if (!$entryFound) {
        break;
      }
    }
  }

  protected function getActivities($case_id)
  {
    $activityOp = 'IN';
    if ($this->_params['activity_types_op'] == 'notin') {
      $activityOp = 'NOT IN';
    }
    $sql = "
      SELECT civicrm_activity.id, subject, details, activity_date_time, activity_type_id
      FROM civicrm_activity 
      INNER JOIN civicrm_case_activity ON civicrm_activity.id = civicrm_case_activity.activity_id
      WHERE civicrm_case_activity.case_id = %1 AND civicrm_activity.is_current_revision = '1' AND civicrm_activity.is_deleted = '0'
      AND civicrm_activity.activity_type_id {$activityOp} (".implode(", ", $this->_params['activity_types_value']).")
      ORDER BY civicrm_activity.activity_date_time DESC";
    $sqlParams[1] = array($case_id, 'Integer');
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    $activities = array();
    while($dao->fetch()) {
      $activity = array();
      $activity['date'] = $dao->activity_date_time;
      $activity['type'] = $this->activityTypes[$dao->activity_type_id];
      $activity['subject'] = $dao->subject;
      $activity['details'] = $dao->details;

      $contactSql = "
        SELECT display_name 
        FROM civicrm_contact 
        INNER JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = civicrm_contact.id
        WHERE civicrm_activity_contact.activity_id = %1 ";
      $contactSqlParams[1] = array($dao->id, 'Integer');
      $contacts = array();
      $contactDao = CRM_Core_DAO::executeQuery($contactSql, $contactSqlParams);
      while($contactDao->fetch()) {
        $contacts[] = $contactDao->display_name;
      }
      $activity['contacts'] = implode(", ", $contacts);

      $activities[] = $activity;
    }
    return $activities;
  }

}
