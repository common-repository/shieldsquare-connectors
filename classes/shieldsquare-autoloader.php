<?php
/**
  * The autoloader conforming to PSR/0-4
  *
  */

/**
  * The autoloader conforming to PSR/0-4
  *
  */
if(!class_exists('ShieldSquare_Autoloader', false)){
    class ShieldSquare_Autoloader{
        /**
          * The constructor
          *
          */
        public function __construct()
        {
            spl_autoload_register(array($this, 'loader'));
        }

        /**
          * The method that actually loads the class (but only if it contains the name ShieldSquare)
          *
          * @param string $className class to load
          *
          * @return void
          */
        static function loader($className)
        {
            $className = ltrim($className, '\\');
            $fileName  = '';
            $namespace = '';
            if ($lastNsPos = strrpos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            if (strpos($fileName, "ShieldSquare") !== FALSE)
                require $fileName;
        }
    }
}
