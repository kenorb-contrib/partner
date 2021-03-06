<?php

require_once 'CRM/Core/Form.php';

/**
 * Class for civimember task actions 
 * 
 */
class CRM_Attendance_Form_Task extends CRM_Core_Form
{
    /**
     * the task being performed
     *
     * @var int
     */
    protected $_task;

    /**
     * The additional clause that we restrict the search with
     *
     * @var string
     */
    protected $_componentClause = null;

    /**
     * The array that holds all the component ids
     *
     * @var array
     */
    protected $_componentIds;

    /**
     * The array that holds all the contact ids
     *
     * @var array
     */
    public $_contactIds;

    /**
     * The array that holds all the member ids
     *
     * @var array
     */
    protected $_memberIds;

    /**
     * build all the data structures needed to build the form
     *
     * @param
     * @return void
     * @access public
     */
    function preProcess( ) 
    {
        $this->_memberIds = array();

        $values = $this->controller->exportValues( 'Search' );
        
        $this->_task = $values['task'];
        $memberTasks = CRM_Attendance_Task::tasks();
        $this->assign( 'taskName', $memberTasks[$this->_task] );

        $ids = array();
        if ( $values['radio_ts'] == 'ts_sel' ) {
            foreach ( $values as $name => $value ) {
                if ( substr( $name, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX ) {
                    $ids[] = substr( $name, CRM_Core_Form::CB_PREFIX_LEN );
                }
            }
        } else {
            $queryParams =  $this->get( 'queryParams' );
            $query       =& new CRM_Contact_BAO_Query( $queryParams, null, null, false, false, 
                                                       CRM_Contact_BAO_Query::MODE_MEMBER);
            $result = $query->searchQuery(0, 0, null);

            while ($result->fetch()) {
                $ids[] = $result->membership_id;
            }
        }
        
        if ( ! empty( $ids ) ) {
            $this->_componentClause =
                ' civicrm_membership.id IN ( ' .
                implode( ',', $ids ) . ' ) ';
            $this->assign( 'totalSelectedMembers', count( $ids ) );
        }
        
        $this->_memberIds = $this->_componentIds = $ids;

        //set the context for redirection for any task actions
        $session =& CRM_Core_Session::singleton( );
        $session->replaceUserContext( CRM_Utils_System::url( 'civicrm/attendance/dashboard', 'force=1' ) );
    }

    /**
     * Given the membership id, compute the contact id
     * since its used for things like send email
     */
    public function setContactIDs( ) 
    {
        $this->_contactIds =& CRM_Core_DAO::getContactIDsFromComponent( $this->_memberIds,
                                                                        'civicrm_membership' );
    }

    /**
     * simple shell that derived classes can call to add buttons to
     * the form with a customized title for the main Submit
     *
     * @param string $title title of the main button
     * @param string $type  button type for the form after processing
     * @return void
     * @access public
     */
    function addDefaultButtons( $title, $nextType = 'next', $backType = 'back' ) 
    {
        $this->addButtons( array(
                                 array ( 'type'      => $nextType,
                                         'name'      => $title,
                                         'isDefault' => true   ),
                                 array ( 'type'      => $backType,
                                         'name'      => ts('Cancel') ),
                                 )
                           );
    }

}


