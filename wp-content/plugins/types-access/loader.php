<?php
// make sure it is unique
if (!class_exists('TAccess_Loader', false))
{ 
/**
 *  TAccess_Loader
 * 
 *  This class is responsible for loading/including all files and getting instances of all objects
 *  in an efficient and abstract manner, abstracts all hardcoded paths and dependencies and manages singleton instances
 */
 
// define interfaces used to implement design patterns
if (!interface_exists('TAccess_Singleton', false))
{
    /**
    * Singleton interface used as "tag" to mark singleton classes.
    */
    interface TAccess_Singleton
    {
    }
}

final class TAccess_Loader
{
    // pool of singleton instances, implement singleton factory, tag with singleton interface
    private static $__singleton_instances__ = array();
    
    // some dependencies here
    private static $__dependencies__=array();
    private static $__loaded_dependencies__=array();
    private static $__assets__=array();
    
    public static function init()
    {
        self::_init_();
        // late init, to cope for any additional dependencies
        add_action('plugins_loaded', array(__CLASS__, '_init_'), 7);
    }
    
    public static function _init_()
    {
        // init assets
        if (empty(self::$__assets__))
        {
            self::$__assets__=array(
                'SCRIPT'=>array(
                    'wpcf-access-utils-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery', 'wp-pointer'),
                        'path'=>TACCESS_ASSETS_URL.'/js/utils.js'
                    ),
                    'types-suggest-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery'),
                        'path'=>TACCESS_ASSETS_URL.'/js/suggest.js'
                    ),
                    'wpcf-access-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery', 'wp-pointer'),
                        'path'=>TACCESS_ASSETS_URL.'/js/basic.js'
                    ),
                    'toolset-colorbox'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery'),
                        'path'=>TACCESS_ASSETS_URL.'/js/popup/jquery.colorbox-min.js'
                    ),
                    'views-utils-script'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('jquery', 'wp-pointer'),
                        'path'=>TACCESS_ASSETS_URL.'/js/popup/utils.js'
                    )
                ),
                'STYLE'=>array(
                    'font-awesome'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/font-awesome.min.css'
                    ),
                    'types-debug'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/pre.css'
                    ),
                    'types-suggest-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/suggest.css'
                    ),
                    'wpcf-access-dev'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>array('font-awesome', 'wp-pointer'),
                        'path'=>TACCESS_ASSETS_URL.'/css/basic.css'
                    ),
                    'toolset-colorbox'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/colorbox.css'
                    ),
                    'notifications'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/notifications.css'
                    ),
                    'views-dialogs-css'=>array(
                        'version'=>WPCF_ACCESS_VERSION,
                        'dependencies'=>null,
                        'path'=>TACCESS_ASSETS_URL.'/css/dialogs.css'
                    )
                )
            );
        }
        
        // init dependencies paths, if any
        if (empty(self::$__dependencies__))
        {
            self::$__dependencies__=array(
                'MODEL'=>array(
                    'Access' => array(
                        array(
                            'class' => 'Access_Model',
                            'path' => TACCESS_INCLUDES_PATH.'/Model.php'
                        )
                    ),
                ),
                'CLASS'=>array(
                    'XML_Processor' => array(
                        array(
                            'class' => 'Access_XML_Processor',
                            'path' => TACCESS_INCLUDES_PATH.'/XML_Processor.php'
                        )
                    ),
                    'Updater' => array(
                        array(
                            'class' => 'Access_Updater',
                            'path' => TACCESS_INCLUDES_PATH.'/Updater.php'
                        )
                    ),
                    'Helper' => array(
                        array(
                            'class' => 'Access_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Helper.php'
                        )
                    ),
                    /*'Plus' => array(
                        array(
                            'path' => TACCESS_PLUGIN_PATH.'/plus.php'
                        )
                    ),
                    'Embedded' => array(
                        array(
                            'path' => TACCESS_PLUGIN_PATH.'/embedded.php'
                        )
                    ),*/
                    'Admin' => array(
                        array(
                            'class' => 'Access_Admin',
                            'path' => TACCESS_INCLUDES_PATH.'/Admin.php'
                        )
                    ),
                    'Admin_Edit' => array(
                        array(
                            'class' => 'Access_Admin_Edit',
                            'path' => TACCESS_INCLUDES_PATH.'/Admin_Edit.php'
                        )
                    ),
                    'Ajax' => array(
                        array(
                            'class' => 'Access_Ajax_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Ajax_Helper.php'
                        )
                    ),
                    /*'Collect' => array(
                        array(
                            'path' => TACCESS_INCLUDES_PATH.'/collect.php'
                        )
                    ),
                    'Check' => array(
                        array(
                            'path' => TACCESS_INCLUDES_PATH.'/check.php'
                        )
                    ),
                    'Exceptions' => array(
                        array(
                            'path' => TACCESS_INCLUDES_PATH.'/exceptions.php'
                        )
                    ),
                    'Hooks' => array(
                        array(
                            'path' => TACCESS_INCLUDES_PATH.'/hooks.php'
                        )
                    ),
                    'Dependencies' => array(
                        array(
                            'path' => TACCESS_INCLUDES_PATH.'/dependencies.php'
                        )
                    ),*/
                    'Upload' => array(
                        array(
                            'class' => 'Access_Upload_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Upload_Helper.php'
                        )
                    ),
                    'Debug' => array(
                        array(
                            'class' => 'Access_Debug',
                            'path' => TACCESS_INCLUDES_PATH.'/Debug.php'
                        )
                    ),
                    /*'Menu' => array(
                        array(
                            'path' => TACCESS_INCLUDES_PATH.'/menu.php'
                        )
                    ),*/
                    'Post' => array(
                        array(
                            'class' => 'Access_Post_Helper',
                            'path' => TACCESS_INCLUDES_PATH.'/Post_Helper.php'
                        )
                    )
                )
            );
        }
        
        // add some Types assets/classes also, when available, NOT NEEDED anymore
        /*if (defined('WPCF_VERSION'))
        {
            if (defined('WPCF_EMBEDDED_RES_RELPATH'))
            {
                self::$__assets__['STYLE']['wpcf-access-wpcf-dev']=array(
                    'version'=>WPCF_VERSION,
                    'dependencies'=>null,
                    'path'=>WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css'
                );
                self::$__assets__['STYLE']['wpcf-access-wpcf']=array(
                    'version'=>WPCF_VERSION,
                    'dependencies'=>null,
                    'path'=>WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css'
                );
            }
            else
            {
                self::$__assets__['STYLE']['wpcf-access-wpcf-dev']=array(
                    'version'=>WPCF_VERSION,
                    'dependencies'=>null,
                    'path'=>WPCF_RELPATH . '/embedded/resources/css/basic.css'
                );
                self::$__assets__['STYLE']['wpcf-access-wpcf']=array(
                    'version'=>WPCF_VERSION,
                    'dependencies'=>null,
                    'path'=>WPCF_RELPATH . '/embedded/resources/css/basic.css'
                );
            }
            
            self::$__dependencies__['CLASS']['Fields'] = array(
                array(
                    'path' => WPCF_INC_ABSPATH.'/fields.php'
                )
            );
            
            self::$__dependencies__['CLASS']['Types'] = array(
                array(
                    'path' => WPCF_INC_ABSPATH.'/custom-types.php'
                )
            );
            
            self::$__dependencies__['CLASS']['Taxonomies'] = array(
                array(
                    'path' => WPCF_INC_ABSPATH.'/custom-taxonomies.php'
                )
            );
        }*/
    }
    
    public static function loadLocale($id, $locale_file)
    {
        load_textdomain($id, TACCESS_LOCALE_PATH . '/' . $locale_file);
    }
    
    // load an asset with dependencies
    public static function loadAsset($qclass, $registerAs=false, $enqueueIt=true)
    {
        list($type, $class)=explode('/', $qclass, 2);
        
        if ( 
            isset(self::$__assets__[$type]) && 
            isset(self::$__assets__[$type][$class]) 
        )
        {
            $_type=&self::$__assets__[$type];
            $_class=&$_type[$class];
            if (is_array($_class['dependencies']) && !empty($_class['dependencies']))
            {
                foreach ($_class['dependencies'] as $_dep)
                {
                    if (isset(self::$__assets__[$type][$_dep]))
                    {
                        // recursively register dependencies
                        self::loadAsset("$type/$_dep", false, false);
                    }
                }
            }
            $registerAs=($registerAs)?$registerAs:$class;
			
			/*if ( !wp_script_is('toolset-colorbox', 'registered') ){print "aaa";
				wp_deregister_script( 'toolset-colorbox' );
			}
			if ( wp_script_is('views-utils-script', 'registered') ){
				//wp_deregister_script( 'views-utils-script' );
			}*/
            if ('SCRIPT'==$type && isset($_class['path']))
            {
                $isFooter=isset($_class['footer'])?$_class['footer']:false;
                if ( !wp_script_is($registerAs, 'registered') ){
                	wp_register_script($registerAs, $_class['path'], $_class['dependencies'], $_class['version'], $isFooter);
				}
                if ($enqueueIt)
                    wp_enqueue_script($registerAs);
            }
            elseif ('STYLE'==$type && isset($_class['path']))
            {
                wp_register_style($registerAs, $_class['path'], $_class['dependencies'], $_class['version']);
                if ($enqueueIt)
                    wp_enqueue_style($registerAs);
            }
        }
    }
    
    // include a php file
    private static function getFile($path, $_in='require_once')
    {
        if( !is_file($path) )
        {
            printf(__('File "%s" doesn\'t exist!', 'wpcf_access'), $path);
            return false;
        }
        
        switch ($_in)
        {
            case 'include':
                include $path;
                break;
            
            case 'include_once':
                include_once $path;
                break;
                
            case 'require':
                require $path;
                break;
            
            case 'require_once':
                require_once $path;
                break;
        }
        
        return true;
    }

    // import a php class
    private static function getClass($class, $path, $_in='require_once')
    {
        if ( !class_exists( $class, false ) )
            self::getFile( $path, $_in );    
    }
    
    public static function loadScoped($qclass, $__glo__=true, $_in='require_once')
    {
        list($type, $class)=explode('/', $qclass, 2);
        
        if ( 
            isset(self::$__dependencies__[$type]) && 
            isset(self::$__dependencies__[$type][$class]) 
        )
        {
            
            if ($__glo__)
            {
                // auto-register globals in scope
                foreach (array_diff(array_keys($GLOBALS), array('GLOBALS', '_POST', '_GET', '_COOKIES', '_SERVER', '_FILES') ) as $__v)
                    global $$__v;
            }
            
            $_type=&self::$__dependencies__[$type];
            $_class=&$_type[$class];
            if ( isset($_type['%%PARENT%%']) && is_array($_type['%%PARENT%%']) )
            {
                $_parent=&$_type['%%PARENT%%'];
                foreach ($_parent as $_dep)
                {
                    if (isset($_dep['class']))
                        self::getClass($_dep['class'], $_dep['path']);
                    else
                        self::getFile($_dep['path']);
                }
            }
            foreach ($_class as $_dep)
            {
                if (isset($_dep['class']))
                    self::getClass($_dep['class'], $_dep['path']);
                else
                {
                    switch ($_in)
                    {
                        case 'include':
                            include $_dep['path'];
                            break;
                        
                        case 'include_once':
                            include_once $_dep['path'];
                            break;
                            
                        case 'require':
                            require $_dep['path'];
                            break;
                        
                        case 'require_once':
                            require_once $_dep['path'];
                            break;
                    }
                }
            }
        }
    }
    
    // load a class with dependencies if needed
    public static function load($qclass)
    {
        list($type, $class)=explode('/', $qclass, 2);
        
        // try to optimize a little bit
        if ( isset(self::$__loaded_dependencies__[$qclass]) )
        {
            $is_loaded=true;
        }
        else 
        {
            $is_loaded=false;
            self::$__loaded_dependencies__[$qclass]=1;
        }
            
        if ( 
            isset(self::$__dependencies__[$type]) && 
            isset(self::$__dependencies__[$type][$class]) 
        )
        {
            $_type=&self::$__dependencies__[$type];
            $_class=&$_type[$class];
            if ( !$is_loaded )
            {
                if ( isset($_type['%%PARENT%%']) && is_array($_type['%%PARENT%%']) )
                {
                    $_parent=&$_type['%%PARENT%%'];
                    foreach ($_parent as $_dep)
                    {
                        if (isset($_dep['class']))
                            self::getClass($_dep['class'], $_dep['path']);
                        else
                            self::getFile($_dep['path']);
                    }
                }
                foreach ($_class as $_dep)
                {
                    if (isset($_dep['class']))
                        self::getClass($_dep['class'], $_dep['path']);
                    else
                        self::getFile($_dep['path']);
                }
            }
            $class=end($_class);
            if (isset($class['class']))
                $class=$class['class'];
        }
        elseif ( !$is_loaded )
        {
            self::getFile($qclass);
            $class=$qclass;
        }
        return array(false, $class);
   }
    
    // singleton factory pattern, to enable singleton in php 5.2, etc..
    // http://stackoverflow.com/questions/7902586/extend-a-singleton-with-php-5-2-x
    // http://stackoverflow.com/questions/7987313/how-to-subclass-a-singleton-in-php
    // use tags (interfaces) to denote singletons, and then use "singleton factory"
    // http://phpgoodness.wordpress.com/2010/07/21/singleton-and-multiton-with-a-different-approach/
    // ability to pass parameters, and to avoid the singleton flag
    public static function get($qclass)
    {
        // if instance is in pool, return it immediately, only singletons are in the pool
        if ( isset(self::$__singleton_instances__[$qclass]) )
            return self::$__singleton_instances__[$qclass];
        
        $instance = null;
        
        // load it if needed
        list($type, $class) = self::load($qclass);
        // make sure it is loaded (exists) and is not interface
        // make sure it is not an interface (PHP 5)
        //if ( !$reflection->isInterface() )
        if ( class_exists( $class, false ) && !interface_exists( $class, false ))
        {
            // Parameters to call constructor (in case) and multiton's getFace (in case) with.
            $args = func_get_args();
            array_shift( $args );

            if ( empty($args) )  // (PHP 5)
            {
                // If the object doesn't have arguments to be passed, we just instantiate it.
                // It might be quicker to use - new $class_name -.
                $instance = new $class(); //$reflection->newInstance(); // PHP 5
            }
            else
            {
                // delay using reflection unless absolutely needed (eg there are args to be passed)
                // Here's the point where we have to instantiate the class anyway. (supports PHP 5)
                $reflection = new ReflectionClass( $class );
                if ( null !== $reflection->getConstructor() )   // (PHP 5)
                    // If the object does have constructor, we pass the additional parameters we got in this method.
                    $instance = $reflection->newInstanceArgs( $args ); // PHP > 5.1.3
                else
                    // If the object doesn't have constructor, we just instantiate it.
                    // It might be quicker to use - new $class_name -.
                    $instance = new $class(); //$reflection->newInstance(); // PHP 5
            }

            if ($instance)
            {
                // If it's a singleton, we have to keep track of it. (PHP 5)
                // If might be quicker to use - $new_instance instanceof Singleton -.
                if ( $instance instanceof TAccess_Singleton /*$reflection->isSubclassOf( 'TAccess_Singleton' )*/ )
                {
                    self::$__singleton_instances__[$qclass] = $instance;
                }
                return $instance;
            }
        }
        // sth failed, no instance, return null here finally
        return null;
    }
    
    // USE WP Object_Cache API to cache templates (so 3rd-party cache plugins can be used also)
    public static function tpl($template, array $args=array(), $cache=false)
    {
        $template_path = TACCESS_TEMPLATES_PATH . DIRECTORY_SEPARATOR . $template . '.tpl.php';
        
        // NEW use caching of templates
        $output=false;
        if ($cache)
        {
            $group='_TAccess_';
            $key=md5(serialize(array($template_path, $args)));
            $output=wp_cache_get( $key, $group );
        }
        
        if (false === $output)
        {
            if (!is_file($template_path))
            {
                printf(__('File "%s" doesn\'t exist!', 'wpcf_access'), $template_path);
                return '';
            }
            $output = self::getTpl($template_path, $args);
            if ($cache) wp_cache_set( $key, $output, $group/*, $expire*/ );
        }
        return $output;
    }
    
    private static function getTpl($______templatepath________, array $______args______=array())
    {
        ob_start();
            if (!empty($______args______)) extract($______args______);
            include($______templatepath________);
        return ob_get_clean();
    }
	
}

// init on load
TAccess_Loader::init();

}
