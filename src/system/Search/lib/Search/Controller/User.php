<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Search
 * @author Patrick Kellum
 * @author Stefano Garuti
 */

class Search_Controller_User extends Zikula_Controller
{

    /**
     * Main user function
     *
     * This function is the default function. Call the function to show the search form.
     *
     * @author Stefano Garuti
     * @return string HTML string templated
     */
    public function main()
    {
        // Security check will be done in form()
        return $this->form();
    }

    /**
     * Generate complete search form
     *
     * Generate the whole search form, including the various plugins options.
     * It uses the Search API's getallplugins() function to find plugins.
     *
     * @author Patrick Kellum
     * @author Stefano Garuti
     *
     * @return string HTML string templated
     */
    public function form($vars = array())
    {
        // Security check
        if (!SecurityUtil::checkPermission('Search::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }

        // get parameter from input
        $vars['q'] = strip_tags(FormUtil::getPassedValue('q', '', 'REQUEST'));
        $vars['searchtype'] = FormUtil::getPassedValue('searchtype', SessionUtil::getVar('searchtype'), 'REQUEST');
        $vars['searchorder'] = FormUtil::getPassedValue('searchorder', SessionUtil::getVar('searchorder'), 'REQUEST');
        $vars['numlimit'] = $this->getVar('itemsperpage', 25);
        $vars['active'] = FormUtil::getPassedValue('active', SessionUtil::getVar('searchactive'), 'REQUEST');
        $vars['modvar'] = FormUtil::getPassedValue('modvar', SessionUtil::getVar('searchmodvar'), 'REQUEST');


        // this var allows the headers to not be displayed
        if (!isset($vars['titles']))
            $vars['titles'] = true;

        // set some defaults
        if (!isset($vars['searchtype']) || empty($vars['searchtype'])) {
            $vars['searchtype'] = 'AND';
        }
        if (!isset($vars['searchorder']) || empty($vars['searchorder'])) {
            $vars['searchorder'] = 'newest';
        }

        // reset the session vars for a new search
        SessionUtil::delVar('searchtype');
        SessionUtil::delVar('searchorder');
        SessionUtil::delVar('searchactive');
        SessionUtil::delVar('searchmodvar');

        //889$domain = $renderer->renderDomain;

        // get all the search plugins
        $search_modules = ModUtil::apiFunc('Search', 'user', 'getallplugins');

        if (count($search_modules) > 0) {
            $plugin_options = array();
            foreach($search_modules as $mods) {
                // as every search plugins return a formatted html string
                // we assign it to a generic holder named 'plugin_options'
                // maybe in future this will change
                // we should retrieve from the plugins an array of values
                // and formatting it here according with the module's template
                // we have also to provide some trick to assure the 'backward compatibility'

                if (isset($mods['title'])) {
                    $plugin_options[$mods['title']] = ModUtil::apiFunc($mods['title'], 'search', 'options', $vars);
                }
            }

            //889$renderer->renderDomain = $domain;

            // Create output object
            // add content to template
            $this->view->assign($vars)
                       ->assign('plugin_options', $plugin_options);

            // Return the output that has been generated by this function
            return $this->view->fetch('search_user_form.tpl');
        } else {
            // Create output object
            // Return the output that has been generated by this function
            return $this->view->fetch('search_user_noplugins.tpl');
        }
    }

    /**
     * Perform the search then show the results
     *
     * This function includes all the search plugins, then call every one passing
     * an array that contains the string to search for, the boolean operators.
     *
     * @author Patrick Kellum
     * @author Stefano Garuti
     * @author Mark West
     * @author Jorn Wildt
     *
     * @return string HTML string templated
     */
    public function search()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Search::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }

        // get parameter from HTTP input
        $vars = array();
        $vars['q'] = strip_tags(FormUtil::getPassedValue('q', '', 'REQUEST'));
        $vars['searchtype'] = FormUtil::getPassedValue('searchtype', SessionUtil::getVar('searchtype'), 'REQUEST');
        $vars['searchorder'] = FormUtil::getPassedValue('searchorder', SessionUtil::getVar('searchorder'), 'REQUEST');
        $vars['numlimit'] = $this->getVar('itemsperpage', 25);
        $vars['page'] = (int)FormUtil::getPassedValue('page', 1, 'REQUEST');

        // $firstpage is used to identify the very first result page
        // - and to disable calls to plugins on the following pages
        $vars['firstPage'] = !isset($_REQUEST['page']);

        // The modulename exists in this array as key, if the checkbox was filled
        $vars['active'] = FormUtil::getPassedValue('active', SessionUtil::getVar('searchactive'), 'REQUEST');

        // All formular data from the modules search plugins is contained in:
        $vars['modvar'] = FormUtil::getPassedValue('modvar', SessionUtil::getVar('searchmodvar'), 'REQUEST');

        if (empty($vars['q'])) {
            LogUtil::registerError ($this->__('Error! You did not enter any keywords to search for.'));
            return System::redirect(ModUtil::url('Search', 'user', 'main'));
        }

        // set some defaults
        if (!isset($vars['searchtype']) || empty($vars['searchtype'])) {
            $vars['searchtype'] = 'AND';
        } else {
            SessionUtil::setVar('searchtype', $vars['searchtype']);
        }
        if (!isset($vars['searchorder']) || empty($vars['searchorder'])) {
            $vars['searchorder'] = 'newest';
        } else {
            SessionUtil::setVar('searchorder', $vars['searchorder']);
        }
        if (!isset($vars['active']) || !is_array($vars['active']) || empty($vars['active'])) {
            $vars['active'] = array();
        } else {
            SessionUtil::setVar('searchactive', $vars['active']);
        }
        if (!isset($vars['modvar']) || !is_array($vars['modvar']) || empty($vars['modvar'])) {
            $vars['modvar'] = array();
        } else {
            SessionUtil::setVar('searchmodvar', $vars['modvar']);
        }

        // Create output object and check caching
        $this->view->cache_id = md5($vars['q'] . $vars['searchtype'] . $vars['searchorder'] . UserUtil::getVar('uid')) . $vars['page'];

        // check if the contents are cached.
        if ($this->view->is_cached('search_user_results.tpl')) {
            return $this->view->fetch('search_user_results.tpl');
        }

        $result = ModUtil::apiFunc('Search', 'user', 'search', $vars);

        // Get number of chars to display in search summaries
        $limitsummary = $this->getVar('limitsummary');
        if (empty($limitsummary)) {
            $limitsummary = 200;
        }

        $this->view->assign('resultcount', $result['resultCount'])
                   ->assign('results', $result['sqlResult'])
                   ->assign($this->getVars())
                   ->assign($vars)
                   ->assign('limitsummary', $limitsummary);

        // log the search if on first page
        if ($vars['firstPage']) {
            ModUtil::apiFunc('Search', 'user', 'log', $vars);
        }

        // Return the output that has been generated by this function
        return $this->view->fetch('search_user_results.tpl');
    }

    /**
     * display a list of recent searches
     *
     * @author Jorg Napp
     */
    public function recent()
    {
        // security check
        if (!SecurityUtil::checkPermission('Search::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }

        // Get parameters from whatever input we need.
        $startnum = (int)FormUtil::getPassedValue('startnum', null, 'GET');

        // we need this value multiple times, so we keep it
        $itemsperpage = $this->getVar('itemsperpage');

        // get the
        $items = ModUtil::apiFunc('Search', 'user', 'getall', array('startnum' => $startnum, 'numitems' => $itemsperpage, 'sortorder' => 'date'));

        // assign the results to the template
        $this->view->assign('recentsearches', $items);

        // assign the values for the smarty plugin to produce a pager in case of there
        // being many items to display.
        $this->view->assign('pager', array('numitems'     => ModUtil::apiFunc('Search', 'user', 'countitems'),
                                           'itemsperpage' => $itemsperpage));

        // Return the output that has been generated by this function
        return $this->view->fetch('search_user_recent.tpl');
    }
}
