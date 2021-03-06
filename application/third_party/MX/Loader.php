<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library extends the CodeIgniter CI_Loader class
 * and adds features allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Loader.php
 *
 * @copyright	Copyright (c) 2011 Wiredesignz
 * @version 	5.4
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class MX_Loader extends CI_Loader
{
	private $_module;
	
	public $_ci_plugins;
    
    /**
     * Keep track of which sparks are loaded. This will come in handy for being
     *  speedy about loading files later.
     *
     * @var array
     */
    var $_ci_loaded_sparks = array();

    /**
     * Is this version less than CI 2.1.0? If so, accomodate
     * @bubbafoley's world-destroying change at: http://bit.ly/sIqR7H
     * @var bool
     */
    var $_is_lt_210 = false;
    
	
	public function __construct() {
	    if(!defined('SPARKPATH'))
        {
            define('SPARKPATH', 'sparks/');
        }

        $this->_is_lt_210 = (is_callable(array('CI_Loader', 'ci_autoloader')) || is_callable(array('CI_Loader', '_ci_autoloader')));
                               
		parent::__construct();
		
		/* set the module name */
		$this->_module = CI::$APP->router->fetch_module();
		
		/* add this module path to the loader variables */
		$this->_add_module_paths($this->_module);
	}
	
	/** Initialize the module **/
	public function _init($controller) {
		
		/* references to ci loader variables */
		foreach (get_class_vars('CI_Loader') as $var => $val) {
			if ($var != '_ci_ob_level') $this->$var =& CI::$APP->load->$var;
		}
		
		/* set a reference to the module controller */
 		$this->controller = $controller;
 		$this->__construct();
	}

	/** Add a module path loader variables **/
	public function _add_module_paths($module = '') {
		
		if (empty($module)) return;
		
		foreach (Modules::$locations as $location => $offset) {
			
			/* only add a module path if it exists */
			if (is_dir($module_path = $location.$module.'/')) {
				array_unshift($this->_ci_model_paths, $module_path);
			}
		}
	}	
	
	/** Load a module config file **/
	public function config($file = '', $use_sections = FALSE, $fail_gracefully = FALSE) {
		return CI::$APP->config->load($file, $use_sections, $fail_gracefully, $this->_module);
	}

	/** Load the database drivers **/
	public function database($params = '', $return = FALSE, $active_record = NULL) {
		
		if (class_exists('CI_DB', FALSE) AND $return == FALSE AND $active_record == NULL AND isset(CI::$APP->db) AND is_object(CI::$APP->db)) 
			return;

		require_once BASEPATH.'database/DB'.EXT;

		if ($return === TRUE) return DB($params, $active_record);
			
		CI::$APP->db = DB($params, $active_record);
		
		return CI::$APP->db;
	}

	/** Load a module helper **/
	public function helper($helper) {
		
		if (is_array($helper)) return $this->helpers($helper);
		
		if (isset($this->_ci_helpers[$helper]))	return;

		list($path, $_helper) = Modules::find($helper.'_helper', $this->_module, 'helpers/');

		if ($path === FALSE) return parent::helper($helper);

		Modules::load_file($_helper, $path);
		$this->_ci_helpers[$_helper] = TRUE;
	}

	/** Load an array of helpers **/
	public function helpers($helpers) {
		foreach ($helpers as $_helper) $this->helper($_helper);	
	}

	/** Load a module language file **/
	public function language($langfile, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '') {
		return CI::$APP->lang->load($langfile, $idiom, $return, $add_suffix, $alt_path, $this->_module);
	}
	
	public function languages($languages) {
		foreach($languages as $_language) $this->language($language);
	}
	
	/** Load a module library **/
	// public function library($library, $params = NULL, $object_name = NULL) {
		// if (is_array($library)) return $this->libraries($library);		
// 		
		// $class = strtolower(end(explode('/', $library)));
// 		
		// if (isset($this->_ci_classes[$class]) AND $_alias = $this->_ci_classes[$class])
			// return CI::$APP->$_alias;
// 			
		// ($_alias = strtolower($object_name)) OR $_alias = $class;
// 		
		// list($path, $_library) = Modules::find($library, $this->_module, 'libraries/');
// 		
		// /* load library config file as params */
		// if ($params == NULL) {
			// list($path2, $file) = Modules::find($_alias, $this->_module, 'config/');	
			// ($path2) AND $params = Modules::load_file($file, $path2, 'config');
		// }	
// 			
		// if ($path === FALSE) {
			// $this->_ci_load_class($library, $params, $object_name);
			// $_alias = $this->_ci_classes[$class];
// 			
		// } else {		
// 			
			// Modules::load_file($_library, $path);
// 			
			// $library = ucfirst($_library);
			// CI::$APP->$_alias = new $library($params);
			// $this->_ci_classes[$class] = $_alias;
		// }
// 		
		// return CI::$APP->$_alias;
    // }

	/** Load an array of libraries **/
	public function libraries($libraries) {
		foreach ($libraries as $_library) $this->library($_library);	
	}

	/** Load a module model **/
	public function model($model, $object_name = NULL, $connect = FALSE) {
		
		if (is_array($model)) return $this->models($model);

		($_alias = $object_name) OR $_alias = end(explode('/', $model));

		if (in_array($_alias, $this->_ci_models, TRUE)) 
			return CI::$APP->$_alias;
			
		/* check module */
		list($path, $_model) = Modules::find(strtolower($model), $this->_module, 'models/');
		
		if ($path == FALSE) {
			
			/* check application & packages */
			parent::model($model, $object_name);
			
		} else {
			
			class_exists('CI_Model', FALSE) OR load_class('Model', 'core');
			
			if ($connect !== FALSE AND ! class_exists('CI_DB', FALSE)) {
				if ($connect === TRUE) $connect = '';
				$this->database($connect, FALSE, TRUE);
			}
			
			Modules::load_file($_model, $path);
			
			$model = ucfirst($_model);
			CI::$APP->$_alias = new $model();
			
			$this->_ci_models[] = $_alias;
		}
		
		return CI::$APP->$_alias;
	}

	/** Load an array of models **/
	function models($models) {
		foreach ($models as $_model) $this->model($_model);	
	}

	/** Load a module controller **/
	public function module($module, $params = NULL)	{
		
		if (is_array($module)) return $this->modules($module);

		$_alias = strtolower(end(explode('/', $module)));
		CI::$APP->$_alias = Modules::load(array($module => $params));
		return CI::$APP->$_alias;
	}

	/** Load an array of controllers **/
	public function modules($modules) {
		foreach ($modules as $_module) $this->module($_module);	
	}

	/** Load a module plugin **/
	public function plugin($plugin)	{
		
		if (is_array($plugin)) return $this->plugins($plugin);		
		
		if (isset($this->_ci_plugins[$plugin]))	
			return;

		list($path, $_plugin) = Modules::find($plugin.'_pi', $this->_module, 'plugins/');	
		
		if ($path === FALSE) return;

		Modules::load_file($_plugin, $path);
		$this->_ci_plugins[$plugin] = TRUE;
	}

	/** Load an array of plugins **/
	public function plugins($plugins) {
		foreach ($plugins as $_plugin) $this->plugin($_plugin);	
	}

	/** Load a module view **/
	public function view($view, $vars = array(), $return = FALSE) {
		list($path, $view) = Modules::find($view, $this->_module, 'views/');
		$this->_ci_view_path = $path;
		return $this->_ci_load(array('_ci_view' => $view, '_ci_vars' => $this->_ci_object_to_array($vars), '_ci_return' => $return));
	}

	public function _ci_is_instance() {}

	public function _ci_get_component($component) {
		return CI::$APP->$component;
	} 

	public function __get($class) {
		return (isset($this->controller)) ? $this->controller->$class : CI::$APP->$class;
	}

	function _ci_load($_ci_data) {
		
		foreach (array('_ci_view', '_ci_vars', '_ci_path', '_ci_return') as $_ci_val) {
			$$_ci_val = ( ! isset($_ci_data[$_ci_val])) ? FALSE : $_ci_data[$_ci_val];
		}

		if ($_ci_path == '') {
			$_ci_file = strpos($_ci_view, '.') ? $_ci_view : $_ci_view.EXT;
			$_ci_path = $this->_ci_view_path.$_ci_file;
		} else {
			$_ci_file = end(explode('/', $_ci_path));
		}

		if ( ! file_exists($_ci_path)) 
			show_error('Unable to load the requested file: '.$_ci_file);

		if (is_array($_ci_vars)) 
			$this->_ci_cached_vars = array_merge($this->_ci_cached_vars, $_ci_vars);
		
		extract($this->_ci_cached_vars);

		ob_start();

		if ((bool) @ini_get('short_open_tag') === FALSE AND CI::$APP->config->item('rewrite_short_tags') == TRUE) {
			echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($_ci_path))));
		} else {
			include($_ci_path); 
		}

		log_message('debug', 'File loaded: '.$_ci_path);

		if ($_ci_return == TRUE) return ob_get_clean();

		if (ob_get_level() > $this->_ci_ob_level + 1) {
			ob_end_flush();
		} else {
			CI::$APP->output->append_output(ob_get_clean());
		}
	}	
	
	/** Autoload module items **/
	public function _autoloader($autoload) {
		
		$path = FALSE;
		
		if ($this->_module)
			list($path, $file) = Modules::find('autoload', $this->_module, 'config/');

		/* module autoload file */
		if ($path != FALSE)
			$autoload = array_merge(Modules::load_file($file, $path, 'autoload'), $autoload);
	
		/* nothing to do */
		if (count($autoload) == 0) return;
		
		/* autoload package paths */
		if (isset($autoload['packages'])){
			foreach ($autoload['packages'] as $package_path){
				$this->add_package_path($package_path);
			}
		}
				
		/* autoload config */
		if (isset($autoload['config'])){
			foreach ($autoload['config'] as $key => $val){
				$this->config($val);
			}
		}

		/* autoload helpers, plugins, languages */
		foreach (array('helper', 'plugin', 'language') as $type){
			if (isset($autoload[$type])){
				foreach ($autoload[$type] as $k => $item){
					$this->$type($item);
				}
			}
		}	
			
		/* autoload database & libraries */
		if (isset($autoload['libraries'])){
			if (in_array('database', $autoload['libraries'])){
				/* autoload database */
				if ( ! $db = CI::$APP->config->item('database')){
					$db['params'] = 'default';
					$db['active_record'] = TRUE;
				}
				$this->database($db['params'], FALSE, $db['active_record']);
				$autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
			}

			/* autoload libraries */
			foreach ($autoload['libraries'] as $library){
				$this->library($library);
			}
		}
		
		/* autoload models */
		if (isset($autoload['model'])){
			foreach ($autoload['model'] as $model => $alias){
				(is_numeric($model)) ? $this->model($alias) : $this->model($model, $alias);
			}
		}
		
		/* autoload module controllers */
		if (isset($autoload['modules'])){
			foreach ($autoload['modules'] as $controller) {
				($controller != $this->_module) AND $this->module($controller);
			}
		}
	}

    /**
     * To accomodate CI 2.1.0, we override the initialize() method instead of
     *  the ci_autoloader() method. Once sparks is integrated into CI, we
     *  can avoid the awkward version-specific logic.
     * @return Loader
     */
    function initialize()
    {
        parent::initialize();

        if(!$this->_is_lt_210)
        {
            $this->ci_autoloader();
        }

        return $this;
    }
    
    /**
     * Load a spark by it's path within the sparks directory defined by
     *  SPARKPATH, such as 'markdown/1.0'
     * @param string $spark The spark path withint he sparks directory
     * @param <type> $autoload An optional array of items to autoload
     *  in the format of:
     *   array (
     *     'helper' => array('somehelper')
     *   )
     * @return <type>
     */
    function spark($spark, $autoload = array())
    {
        if(is_array($spark))
        {
            foreach($spark as $s)
            {
                $this->spark($s);
            }
        }

        $spark = ltrim($spark, '/');
        $spark = rtrim($spark, '/');

        $spark_path = SPARKPATH . $spark . '/';
        $parts      = explode('/', $spark);
        $spark_slug = strtolower($parts[0]);

        # If we've already loaded this spark, bail
        if(array_key_exists($spark_slug, $this->_ci_loaded_sparks))
        {
            return true;
        }

        # Check that it exists. CI Doesn't check package existence by itself
        if(!file_exists($spark_path))
        {
            show_error("Cannot find spark path at $spark_path");
        }

        if(count($parts) == 2)
        {
            $this->_ci_loaded_sparks[$spark_slug] = $spark;
        }

        $this->add_package_path($spark_path);

        foreach($autoload as $type => $read)
        {
            if($type == 'library')
                $this->library($read);
            elseif($type == 'model')
                $this->model($read);
            elseif($type == 'config')
                $this->config($read);
            elseif($type == 'helper')
                $this->helper($read);
            elseif($type == 'view')
                $this->view($read);
            else
                show_error ("Could not autoload object of type '$type' ($read) for spark $spark");
        }

        // Looks for a spark's specific autoloader
        $this->ci_autoloader($spark_path);

        return true;
    }

    /**
     * Pre-CI 2.0.3 method for backward compatility.
     *
     * @param null $basepath
     * @return void
     */
    function _ci_autoloader($basepath = NULL)
    {
        $this->ci_autoloader($basepath);
    }

    /**
     * Specific Autoloader (99% ripped from the parent)
     *
     * The config/autoload.php file contains an array that permits sub-systems,
     * libraries, and helpers to be loaded automatically.
     *
     * @param array|null $basepath
     * @return void
     */
    function ci_autoloader($basepath = NULL)
    {
        if($basepath !== NULL)
        {
            $autoload_path = $basepath.'config/autoload'.EXT;
        }
        else
        {
            $autoload_path = APPPATH.'config/autoload'.EXT;
        }

        if(! file_exists($autoload_path))
        {
            return FALSE;
        }

        include($autoload_path);

        if ( ! isset($autoload))
        {
            return FALSE;
        }

        if($this->_is_lt_210 || $basepath !== NULL)
        {
            // Autoload packages
            if (isset($autoload['packages']))
            {
                foreach ($autoload['packages'] as $package_path)
                {
                    $this->add_package_path($package_path);
                }
            }
        }

        // Autoload sparks
        if (isset($autoload['sparks']))
        {
            foreach ($autoload['sparks'] as $spark)
            {
                $this->spark($spark);
            }
        }

        if($this->_is_lt_210 || $basepath !== NULL)
        {
            if (isset($autoload['config']))
            {
                // Load any custom config file
                if (count($autoload['config']) > 0)
                {
                    $CI =& get_instance();
                    foreach ($autoload['config'] as $key => $val)
                    {
                        $CI->config->load($val);
                    }
                }
            }

            // Autoload helpers and languages
            foreach (array('helper', 'language') as $type)
            {
                if (isset($autoload[$type]) AND count($autoload[$type]) > 0)
                {
                    $this->$type($autoload[$type]);
                }
            }

            // A little tweak to remain backward compatible
            // The $autoload['core'] item was deprecated
            if ( ! isset($autoload['libraries']) AND isset($autoload['core']))
            {
                $autoload['libraries'] = $autoload['core'];
            }

            // Load libraries
            if (isset($autoload['libraries']) AND count($autoload['libraries']) > 0)
            {
                // Load the database driver.
                if (in_array('database', $autoload['libraries']))
                {
                    $this->database();
                    $autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
                }

                // Load all other libraries
                foreach ($autoload['libraries'] as $item)
                {
                    $this->library($item);
                }
            }

            // Autoload models
            if (isset($autoload['model']))
            {
                $this->model($autoload['model']);
            }
        }
    }
}

/** load the CI class for Modular Separation **/
(class_exists('CI', FALSE)) OR require dirname(__FILE__).'/Ci.php';