<?php
/**
 * @package      User Activity
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.filesystem.path');


/**
 * User Activity Component Helper Class
 * Takes care of loading plugins and translating activity records
 *
 */
class UserActivityHelper
{
    public static $extension = 'com_useractivity';


    /**
     * Method to translate an activity record
     *
     * @param     mixed    $data    The record data to translate
     *
     * @return    mixed             The translated data
     */
    public static function translate($data)
    {
        if (is_object($data)) {
            // Default text
            $data->text = '';

            if (!isset($data->plugin) || empty($data->plugin)) {
                return $item;
            }

            list($type, $name) = explode('.', $data->plugin, 2);

            // Get the plugin
            $plugin = self::getPluginHelper($name, $type);

            if (!$plugin) return $item;

            // Return translated item
            return $plugin->translateItem($data);
        }
        elseif (is_array($data)) {
            $key = key($data);

            if (!isset($data[$key]->plugin) || empty($data[$key]->plugin)) {
                $data[$key]->text = '';
                return $data[$key];
            }

            list($type, $name) = explode('.', $data[$key]->plugin, 2);

            // Get the plugin
            $plugin = self::getPluginHelper($name, $type);

            if (!$plugin) {
                $data[$key]->text = '';
                return $data[$key];
            }

            return $plugin->translateGroup($data);
        }
        else {
            return $data;
        }
    }


    /**
     * Method to load a user activity plugin translation helper
     *
     * @param     string    $name      The name of the plugin
     * @param     string    $type      The plugin type (Optional)
     *
     * @return    mixed     $plugin    The plugin helper instance on success, Null if not found
     */
    public static function getPluginHelper($name, $type = 'useractivity')
    {
        static $plugins = array();

        // Check the cache
        $cache = $type . '.' . $name;
        if (isset($plugins[$cache])) return $plugins[$cache];

        // Include the helper file
        $helper_file = JPath::check(JPATH_PLUGINS . '/' . $type . '/' . $name . '/helpers/' . $name . '.php');

        if (!file_exists($helper_file)) {
            $plugins[$cache] = null;
            return null;
        }

        require_once $helper_file;

        // Create class instance
        $class_name = 'plg' . $type . $name . 'Helper';

        $plugins[$cache] = new $class_name();

        return $plugins[$cache];
    }


    /**
     * Method to load the activity plugin language files
     *
     * @param     string     $base_path    The basepath to use (Optional)
     *
     * @return    boolean                  True on success, False on error
     */
    public static function loadPluginLanguages($base_path = JPATH_ADMINISTRATOR)
    {
        static $cached = null;

        // Check cache
        if (!is_null($cached)) return $cached;

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('plugin')
              ->from('#__user_activity_item_types')
              ->group('plugin');

        $db->setQuery($query);
        $plugins = $db->loadColumn();

        if (empty($plugins)) {
            $cached = false;
            return false;
        }

        $lang = JFactory::getLanguage();
        $default_lang = $lang->getDefault();

        foreach ($plugins AS $plugin)
        {
            list($type, $name) = explode('.', $plugin, 2);

            $extension = strtolower('plg_' . $type . '_' . $name);

            $lang->load($extension, $base_path, null, false, false);
            $lang->load($extension, JPATH_PLUGINS . '/' . $type . '/' . $name, null, false, false);
            $lang->load($extension, $base_path, $default_lang, false, false);
            $lang->load($extension, JPATH_PLUGINS . '/' . $type . '/' . $name, $default_lang, false, false);
        }

        $cached = true;

        return true;
    }
}