<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Sailthru_Horizon_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
