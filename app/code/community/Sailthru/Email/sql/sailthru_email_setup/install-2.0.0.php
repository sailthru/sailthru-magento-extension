<?php

/* @var $this Mage_Eav_Model_Entity_Setup */
$this->addAttribute('customer', 'sailthru_id', array(
    'type'      => 'varchar',
    'label'     => 'Sailthru ID',
    'input'     => 'text',
    'position'  => 120,
    'required'  => false,
    'is_system' => 0,
));
$attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'sailthru_id');
$attribute->setData('used_in_forms', array('adminhtml_customer'));
$attribute->setData('is_user_defined', 0);
$attribute->save();