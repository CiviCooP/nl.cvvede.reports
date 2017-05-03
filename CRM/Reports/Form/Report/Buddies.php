<?php

class CRM_Reports_Form_Report_Buddies extends CRM_Report_Form_Contact_Summary {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->activityTypes = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE);
    asort($this->activityTypes);
    $this->activityTypes = array('' => ts(' - Select - ')) + $this->activityTypes;

      parent::__construct();
    $this->_columns['civicrm_contact']['fields']['created_date'] = array(
      'title' => ts('Created Date'),
      'default' => FALSE,
    );
    $this->_columns['civicrm_contact']['fields']['relationships'] = array(
      'title' => ts('Relationships'),
      'pseudofield' => TRUE,
    );
    $this->_columns['civicrm_contact']['order_bys']['created_date'] = array(
      'name' => 'created_date',
      'title' => ts('Created Date'),
    );
    $this->_columns['civicrm_activity'] = array(
      'grouping' => 'activity',
      'group_title' => ts('Activity'),
      'fields' => array(
        'activity_date_time' => array(
          'title' => ts('Activity Date'),
        ),
      ),
      'filters' => array(
        'activity_type_id' => array(
          'title' => ts('Activity Type'),
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => $this->activityTypes,
          'pseudofield' => TRUE,
        ),
      ),
      'order_bys' => array(
        'activity_date_time' => array(
          'title' => ts('Activity Date'),
        ),
      ),
    );
  }

  /**
   * Adds group filters to _columns (called from _Construct).
   */
  public function buildGroupFilter() {
    parent::buildGroupFilter();
  }

  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['pseudofield'])) {
            continue;
          }
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            elseif ($tableName == 'civicrm_country') {
              $this->_countryField = TRUE;
            }

            $alias = "{$tableName}_{$fieldName}";
            $select[] = "{$field['dbAlias']} as {$alias}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_selectAliases[] = $alias;
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ) ";

    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
            LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']}
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                      {$this->_aliases['civicrm_email']}.is_primary = 1) ";
    }

    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }

    if ($this->isTableSelected('civicrm_country')) {
      $this->_from .= "
            LEFT JOIN civicrm_country {$this->_aliases['civicrm_country']}
                   ON {$this->_aliases['civicrm_address']}.country_id = {$this->_aliases['civicrm_country']}.id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ";
    }


    if ($this->isTableSelected('civicrm_activity')) {
      $activity_type_clause = "1";
      $activity_type_op = CRM_Utils_Array::value("activity_type_id_op", $this->_params);
      if ($activity_type_op) {
        $field = $this->_columns['civicrm_activity']['filters']['activity_type_id'];
        $field['dbAlias'] = 'civicrm_activity.activity_type_id';
        $activity_type_clause = $this->whereClause($field,
          $activity_type_op,
          CRM_Utils_Array::value("activity_type_id_value", $this->_params),
          CRM_Utils_Array::value("activity_type_id_min", $this->_params),
          CRM_Utils_Array::value("activity_type_id_max", $this->_params)
        );
      }

      $this->_from .= "
            LEFT JOIN (
                      SELECT min(civicrm_activity.activity_date_time) as activity_date_time, civicrm_activity_contact.contact_id 
                      FROM civicrm_activity 
                      INNER JOIN civicrm_activity_contact ON civicrm_activity.id = civicrm_activity_contact.activity_id
                      WHERE civicrm_activity.is_deleted = '0'
                      AND civicrm_activity.is_current_revision = '1'
                      AND {$activity_type_clause}
                      GROUP BY civicrm_activity_contact.contact_id
                      ) {$this->_aliases['civicrm_activity']} ON {$this->_aliases['civicrm_activity']}.contact_id = {$this->_aliases['civicrm_contact']}.id 
            ";
    }
  }

  /**
   * Build where clause.
   */
  public function where() {
    $this->storeWhereHavingClauseArray();

    $deleteClause = "`{$this->_aliases['civicrm_contact']}`.`is_deleted` = '0'";

    if (empty($this->_whereClauses)) {
      $this->_where = "WHERE ( {$deleteClause} ) ";
      $this->_having = "";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $this->_whereClauses) . " AND {$deleteClause}";
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($this->_havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $this->_havingClauses);
    }
  }

  public function modifyColumnHeaders() {
    if (!empty($this->_params['fields']['relationships'])) {
      $this->_columnHeaders['relationships'] = array(
        'title' => ts('Relationships'),
      );
    }
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
      // make count columns point to detail report
      // convert sort name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {

        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact');
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // Handle ID to label conversion for contact fields
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contact/summary', 'View Contact Summary') ? TRUE : $entryFound;

      // display birthday in the configured custom format
      if (array_key_exists('civicrm_contact_birth_date', $row)) {
        $birthDate = $row['civicrm_contact_birth_date'];
        if ($birthDate) {
          $rows[$rowNum]['civicrm_contact_birth_date'] = CRM_Utils_Date::customFormat($birthDate, '%Y%m%d');
        }
        $entryFound = TRUE;
      }

      if (!empty($this->_params['fields']['relationships']) && array_key_exists('civicrm_contact_id', $row)) {
        $relationships = civicrm_api3('Relationship', 'get', array('contact_id' => $row['civicrm_contact_id'], 'status_id' => 3));
        $relationshipsText = '';
        foreach($relationships['values'] as $relationship) {
          $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $relationship['cid'], $this->_absoluteUrl);
          if (strlen($relationshipsText)) {
            $relationshipsText .= '<br>';
          }

          $age = '';
          $contact = civicrm_api('Contact', 'getsingle', array('id' => $relationship['cid'], 'version' => '3'));
          if (!empty($contact['birth_date'])) {
            $birthDate = new DateTime ($contact['birth_date']);
            $age = $birthDate->diff(new DateTime('now'))->y;
            $age = ' ('.$age.')';
          }

          $relationshipsText .= $relationship['relation'] . '&nbsp;'.'<a href="'.$url.'">'.$relationship['display_name'].$age.'</a>';
          $rows[$rowNum]['relationships'] = $relationshipsText;
        }
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

}
