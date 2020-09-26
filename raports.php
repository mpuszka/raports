<?php
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Raports module class
 */
class Raports extends Module
{   
    /**
     * Controller class name
     */
    const CONTROLLER_CLASS_NAME = 'AdminRaports';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name                     = 'raports';
        $this->tab                      = 'dashboard';
        $this->version                  = '1.0.0';
        $this->author                   = 'Marcin Puszka';
        $this->need_instance            = 0;
        $this->bootstrap                = true;
        $this->displayName              = $this->l('Raports module');
        $this->description              = $this->l('Simple module to generate raports');
        $this->confirmUninstall         = $this->l('Are you sure you want to uninstall?');
        $this->ps_versions_compliancy   = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];

        parent::__construct();
    }

    /**
     * Install method
     *
     * @return boolean
     */
    public function install(): boolean 
    {
        return parent::install() &&
            $this->createTab();
    }

    /**
     * Uninstall method
     *
     * @return boolean
     */
    public function uninstall(): boolean
    {
        return parent::uninstall() &&
            $this->uninstallTab();
    }

    /**
     * Create tab
     *
     * @return boolean
     */
    private function createTab(): boolean
    {
        $tab = new Tab;

        foreach(Language::getLanguages() as $lang)
        {
            $tab->name[$lang['id_lang']] = $this->l('Raports');
        }

        $tab->class_name    = self::CONTROLLER_CLASS_NAME;
        $tab->module        = $this->name;
        $tab->id_parent     = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->add();

        return true;
    }

    /**
     * Uninstall tab
     *
     * @return boolean
     */
    private function uninstallTab(): boolean
    {
        $id_tab = (int)Tab::getIdFromClassName(self::CONTROLLER_CLASS_NAME);

        if (!$id_tab) 
        {
            return false;
        } 

        $tab = new Tab($id_tab);
        $tab->delete();
            
        return true;
    }

}