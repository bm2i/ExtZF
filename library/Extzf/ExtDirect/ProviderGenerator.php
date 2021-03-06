<?php

/**
 * Generates the provider javascript code which declares the
 * Zend Framework MVC prototyped RPC method mapping in
 * the browser.
 */
class Extzf_ExtDirect_ProviderGenerator
{

    /**
     * Default module name
     * @var string
     */
    public static $defaultModule = "core";


    /**
     * Returns the modules collected from the configuration
     * given and the given MVC structure.
     *
     * @return array
     */
    public static function getModules()
    {
        // Append all library/Direct classes to the
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/direct.ini', APPLICATION_ENV);

        $controllers = $config->direct->controllers;

        // Determine modules & controllers
        $modules = array();
        foreach ($controllers as $controllerBaseName) {

            $controllerSplit = explode('_', $controllerBaseName);

            // Cleanup naming
            $controllerSplit[0] = strtolower($controllerSplit[0]);
            $controllerSplit[1] = ucfirst($controllerSplit[1]);

            if (!isset($modules[$controllerSplit[0]])) {
                $modules[$controllerSplit[0]] = array();
            }
            $modules[$controllerSplit[0]][] = $controllerSplit[1];
        }
        return $modules;

    }


    /**
     * Generates an MVC mapping and returns it's
     * logical structure as array.
     *
     * @return array
     */
    public static function generateMVCProviders()
    {
        // Append all library/Direct classes to the
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/direct.ini', APPLICATION_ENV);

        // Get the modules
        $modules = Extzf_ExtDirect_ProviderGenerator::getModules();

        // Walk modules, build API
        $apis = array();
        foreach ($modules as $moduleName => $controllerNames) {

            // Generate module provider API
            $apis[] = Extzf_ExtDirect_ProviderGenerator::generateModuleProvider($config->direct->namespace, $moduleName, $controllerNames);

            // Dynamically add the provider
            $addProviderStmt = "Ext.Direct.addProvider(". $config->direct->namespace .".". $moduleName .".REMOTING_API);";
            $apis[] = $addProviderStmt;
        }
        return $apis;
    }


    /**
     * Generates the provider code for one MVC module
     *
     * @param string $baseNamespace Namespace name (default: "Direct")
     * @param string $moduleName Name of the module to generate API for
     * @param array $controllerNames Names of the controllers of the module (without module prefix)
     * @return array
     */
    public static function generateModuleProvider($baseNamespace, $moduleName, $controllerNames)
    {
        $api = Extzf_ExtDirect_ProviderGenerator::_generateModuleAPI($baseNamespace, $moduleName, $controllerNames);

        // Send API descriptor to browser
        $rawApi = $api->getApi();
        return $api->sprint($rawApi);
    }


    /**
     * Generates the provider API and returns it
     *
     * @param string $baseNamespace Namespace name (default: "Direct")
     * @param string $moduleName Name of the module to generate API for
     * @param array $controllerNames Names of the controllers of the module (without module prefix)
     * @return Extzf_ExtDirect_API
     */
    public static function _generateModuleAPI($baseNamespace, $moduleName, $controllerNames)
    {
        $cache = new ExtDirect_CacheProvider(BASE_PATH . '/data/direct_api_cache_'. $moduleName .'.txt');
        $api = new Extzf_ExtDirect_API();

        $api->setRouterUrl('/index.php?direct');
        $api->setCacheProvider($cache);

        $api->setNameSpace($baseNamespace .'.'. $moduleName);
        $api->setDescriptor($baseNamespace .'.'. $moduleName .'.REMOTING_API');

        $api->setDefaults(
            array(
                'autoInclude' => false
            )
        );

        $controllers = array();
        foreach ($controllerNames as $controllerName) {

            require_once APPLICATION_PATH . "/modules/" . strtolower($moduleName) . "/controllers/" . ucfirst($controllerName) . ".php";

            if ($moduleName == Extzf_ExtDirect_ProviderGenerator::$defaultModule) {

                // Class and prefix
                $controllers[$controllerName] = array("prefix" => '', "basePath" => $moduleName);

            } else {

                // Class and prefix
                $controllers[$controllerName] = array("prefix" => $moduleName . '_', "basePath" => $moduleName);
            }

        }
        $api->add($controllers);

        return $api;
    }


    /**
     * Generates the provider API and returns it
     *
     * @param string $baseNamespace Namespace name (default: "Direct")
     * @param string $moduleName Name of the module to generate API for
     * @param array $controllerNames Names of the controllers of the module (without module prefix)
     * @return Extzf_ExtDirect_API
     */
    public static function generateModuleAPI($baseNamespace, $moduleName, $controllerNames)
    {
        return Extzf_ExtDirect_ProviderGenerator::_generateModuleAPI($baseNamespace, $moduleName, $controllerNames);
    }


    /**
     * Collects the JSON provider definitions and returns their
     * combined full-text code string.
     *
     * @param array $providers Providers json chunks
     * @return string Provider code
     */
    public static function providersToJson($providers)
    {
        $providerCode = "";
        foreach ($providers as $provider) {
            $providerCode .= $provider;
        }
        return $providerCode;
    }
}