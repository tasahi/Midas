<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

require_once dirname(__FILE__).'/TestsBootstrap.php';
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/**
 * @property ActivedownloadModel $Activedownload
 * @property AssetstoreModel $Assetstore
 * @property BitstreamModel $Bitstream
 * @property CommunityModel $Community
 * @property CommunityInvitationModel $CommunityInvitation
 * @property object $Component
 * @property FeedModel $Feed
 * @property FeedpolicygroupModel $Feedpolicygroup
 * @property FeedpolicyuserModel $Feedpolicyuser
 * @property FolderModel $Folder
 * @property FolderpolicygroupModel $Folderpolicygroup
 * @property FolderpolicyuserModel $Folderpolicyuser
 * @property object $Form
 * @property GroupModel $Group
 * @property ItemModel $Item
 * @property ItempolicygroupModel $Itempolicygroup
 * @property ItempolicyuserModel $Itempolicyuser
 * @property ItemRevisionModel $ItemRevision
 * @property LicenseModel $License
 * @property MetadataModel $Metadata
 * @property ModuleModel $Module
 * @property NewUserInvitationModel $NewUserInvitation
 * @property PendingUserModel $PendingUser
 * @property ProgressModel $Progress
 * @property SettingModel $Setting
 * @property TokenModel $Token
 * @property UserModel $User
 * @property UserapiModel $Userapi;
 */
abstract class DatabaseTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
{
    protected $application;

    /**
     * Fet the temporary directory.
     *
     * @return string
     */
    protected function getTempDirectory()
    {
        return UtilityComponent::getTempDirectory();
    }

    /** Setup. */
    public function setUp()
    {
        $this->bootstrap = array($this, 'appBootstrap');
        $this->loadElements();
        if (isset($this->enabledModules) && !empty($this->enabledModules)) {
            foreach ($this->enabledModules as $route) {
                if (file_exists(BASE_PATH.'/modules/'.$route.'/AppController.php')) {
                    require_once BASE_PATH.'/modules/'.$route.'/AppController.php';
                }
                if (file_exists(BASE_PATH.'/modules/'.$route.'/models/AppDao.php')) {
                    require_once BASE_PATH.'/modules/'.$route.'/models/AppDao.php';
                }
                if (file_exists(BASE_PATH.'/modules/'.$route.'/models/AppModel.php')) {
                    require_once BASE_PATH.'/modules/'.$route.'/models/AppModel.php';
                }
            }
        }

        parent::setUp();
    }

    /** Teardown. */
    public function tearDown()
    {
        parent::tearDown();
    }

    /** Bootstrap the application. */
    public function appBootstrap()
    {
        $this->application = new Zend_Application(APPLICATION_ENV, CORE_CONFIG);
        $this->_initModule();
        $this->application->bootstrap();
    }

    /** Initialize modules. */
    private function _initModule()
    {
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers');
        $router = $frontController->getRouter();
        $enabledModules = array();

        if (isset($this->enabledModules)) {
            $enabledModules = $this->enabledModules;
        }

        /** @var UtilityComponent $utilityComponent */
        $utilityComponent = MidasLoader::loadComponent('Utility');

        /** @var ModuleModel $moduleModel */
        $moduleModel = MidasLoader::loadModel('Module');

        foreach ($enabledModules as $enabledModule) {
            $frontController->addControllerDirectory(BASE_PATH.'/modules/'.$enabledModule.'/controllers', $enabledModule);

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/constant/module.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/constant/module.php';
            }

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/AppController.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/AppController.php';
            }

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/models/AppDao.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/models/AppDao.php';
            }

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/models/AppModel.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/models/AppModel.php';
            }

            $router->addRoute($enabledModule.'-1', new Zend_Controller_Router_Route($enabledModule.'/:controller/:action/*', array('module' => $enabledModule)));
            $router->addRoute($enabledModule.'-2', new Zend_Controller_Router_Route($enabledModule.'/:controller/', array('module' => $enabledModule, 'action' => 'index')));
            $router->addRoute($enabledModule.'-3', new Zend_Controller_Router_Route($enabledModule.'/', array('module' => $enabledModule, 'controller' => 'index', 'action' => 'index')));

            $utilityComponent->installModule($enabledModule);
            $moduleDao = $moduleModel->getByName($enabledModule);
            $moduleDao->setEnabled(1);
            $moduleModel->save($moduleDao);
        }

        Zend_Registry::set('modulesEnable', $enabledModules);
    }

    /**
     * Setup the database using XML files.
     *
     * @param string|array $files
     * @param null|string $module
     * @throws Zend_Exception
     */
    public function setupDatabase($files, $module = null)
    {
        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        $connection = new Zend_Test_PHPUnit_Db_Connection($db, $configDatabase->database->params->dbname);
        $databaseTester = new Zend_Test_PHPUnit_Db_SimpleTester($connection);
        if (is_array($files)) {
            foreach ($files as $f) {
                $path = BASE_PATH.'/core/tests/databaseDataset/'.$f.'.xml';
                if (isset($module)) {
                    $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$f.'.xml';
                }
                $xmlDataSet = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
                $replacementDataSet = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($xmlDataSet);
                $configCore = new Zend_Config_Ini(CORE_CONFIG, 'global', true);
                Zend_Registry::set('configCore', $configCore);
                $coreVersion = UtilityComponent::getLatestModuleVersion('core');
                $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $coreVersion, $matches);
                if ($result !== 1) {
                    throw new Zend_Exception('Invalid core version string.');
                }
                $replacementDataSet->addFullReplacement('##CORE_MAJOR_VERSION##', $matches[1]);
                $replacementDataSet->addFullReplacement('##CORE_MINOR_VERSION##', $matches[2]);
                $replacementDataSet->addFullReplacement('##CORE_PATCH_VERSION##', $matches[3]);
                $databaseTester->setupDatabase($replacementDataSet);
            }
        } else {
            $path = BASE_PATH.'/core/tests/databaseDataset/'.$files.'.xml';
            if (isset($module)) {
                $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$files.'.xml';
            }
            $xmlDataSet = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
            $replacementDataSet = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($xmlDataSet);
            $coreVersion = UtilityComponent::getLatestModuleVersion('core');
            $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $coreVersion, $matches);
            if ($result !== 1) {
                throw new Zend_Exception('Invalid core version string.');
            }
            $replacementDataSet->addFullReplacement('##CORE_MAJOR_VERSION##', $matches[1]);
            $replacementDataSet->addFullReplacement('##CORE_MINOR_VERSION##', $matches[2]);
            $replacementDataSet->addFullReplacement('##CORE_PATCH_VERSION##', $matches[3]);

            $databaseTester->setupDatabase($replacementDataSet);
        }

        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('activedownload_activedownload_id_seq', (SELECT MAX(activedownload_id) FROM activedownload)+1);");
            $db->query("SELECT setval('assetstore_assetstore_id_seq', (SELECT MAX(assetstore_id) FROM assetstore)+1);");
            $db->query("SELECT setval('bitstream_bitstream_id_seq', (SELECT MAX(bitstream_id) FROM bitstream)+1);");
            $db->query("SELECT setval('community_community_id_seq', (SELECT MAX(community_id) FROM community)+1);");
            $db->query("SELECT setval('communityinvitation_communityinvitation_id_seq', (SELECT MAX(communityinvitation_id) FROM communityinvitation)+1);");
            $db->query("SELECT setval('feed_feed_id_seq', (SELECT MAX(feed_id) FROM feed)+1);");
            $db->query("SELECT setval('folder_folder_id_seq', (SELECT MAX(folder_id) FROM folder)+1);");
            $db->query("SELECT setval('group_group_id_seq', (SELECT MAX(group_id) FROM \"group\")+1);");
            $db->query("SELECT setval('item_item_id_seq', (SELECT MAX(item_id) FROM item)+1);");
            $db->query("SELECT setval('itemrevision_itemrevision_id_seq', (SELECT MAX(itemrevision_id) FROM itemrevision)+1);");
            $db->query("SELECT setval('license_license_id_seq', (SELECT MAX(license_id) FROM license)+1);");
            $db->query("SELECT setval('metadata_metadata_id_seq', (SELECT MAX(metadata_id) FROM metadata)+1);");
            $db->query("SELECT setval('metadatavalue_metadatavalue_id_seq', (SELECT MAX(metadatavalue_id) FROM metadatavalue)+1);");
            $db->query("SELECT setval('module_module_id_seq', (SELECT MAX(module_id) FROM module)+1);");
            $db->query("SELECT setval('newuserinvitation_newuserinvitation_id_seq', (SELECT MAX(newuserinvitation_id) FROM newuserinvitation)+1);");
            $db->query("SELECT setval('progress_progress_id_seq', (SELECT MAX(progress_id) FROM progress)+1);");
            $db->query("SELECT setval('setting_setting_id_seq', (SELECT MAX(setting_id) FROM setting)+1);");
            $db->query("SELECT setval('token_token_id_seq', (SELECT MAX(token_id) FROM token)+1);");
            $db->query("SELECT setval('user_user_id_seq', (SELECT MAX(user_id) FROM \"user\")+1);");
            $db->query("SELECT setval('userapi_userapi_id_seq', (SELECT MAX(userapi_id) FROM userapi)+1);");
        }
    }

    /**
     * Get the mock database connection.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection|Zend_Test_PHPUnit_Db_Connection
     */
    protected function getConnection()
    {
        if (!isset($this->_connectionMock) || $this->_connectionMock == null) {
            $configDatabase = Zend_Registry::get('configDatabase');
            if (empty($configDatabase->database->params->driver_options)) {
                $driverOptions = array();
            } else {
                $driverOptions = $configDatabase->database->params->driver_options->toArray();
            }
            $params = array(
                'dbname' => $configDatabase->database->params->dbname,
                'username' => $configDatabase->database->params->username,
                'password' => $configDatabase->database->params->password,
                'driver_options' => $driverOptions,
            );
            if (empty($configDatabase->database->params->unix_socket)) {
                $params['host'] = $configDatabase->database->params->host;
                $params['port'] = $configDatabase->database->params->port;
            } else {
                $params['unix_socket'] = $configDatabase->database->params->unix_socket;
            }
            $db = Zend_Db::factory($configDatabase->database->adapter, $params);
            $this->_connectionMock = $this->createZendDbConnection($db, $configDatabase->database->params->dbname);
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
        }

        return $this->_connectionMock;
    }

    /**
     * Get the data set.
     *
     * @param string $name
     * @param null|string $module
     * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet
     * @throws Zend_Exception
     */
    protected function getDataSet($name = 'default', $module = null)
    {
        $path = BASE_PATH.'/core/tests/databaseDataset/'.$name.'.xml';
        if (isset($module) && !empty($module)) {
            $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$name.'.xml';
        }
        $xmlDataSet = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
        $replacementDataSet = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($xmlDataSet);
        $coreVersion = UtilityComponent::getLatestModuleVersion('core');
        $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $coreVersion, $matches);
        if ($result !== 1) {
            throw new Zend_Exception('Invalid core version string.');
        }
        $replacementDataSet->addFullReplacement('##CORE_MAJOR_VERSION##', $matches[1]);
        $replacementDataSet->addFullReplacement('##CORE_MINOR_VERSION##', $matches[2]);
        $replacementDataSet->addFullReplacement('##CORE_PATCH_VERSION##', $matches[3]);

        return $replacementDataSet;
    }

    /**
     * Load data.
     *
     * @param string $modelName name of the model to load
     * @param null|string $file file that the test data is defined in
     * @param string $modelModule module of the model, or '' if in core
     * @param string $fileModule module the test data file is in, or '' if in core
     * @return false|array|mixed
     */
    protected function loadData($modelName, $file = null, $modelModule = '', $fileModule = '')
    {
        $model = MidasLoader::loadModel($modelName, $modelModule);
        if ($file == null) {
            $file = strtolower($modelName);
        }
        $data = $this->getDataSet($file, $fileModule);
        $dataUsers = $data->getTable($model->getName());
        $key = array();
        for ($i = 0; $i < $dataUsers->getRowCount(); ++$i) {
            $key[] = $dataUsers->getValue($i, $model->getKey());
        }

        return $model->load($key);
    }

    /** Load model and components. */
    public function loadElements()
    {
        Zend_Registry::set('models', array());
        if (isset($this->_models)) {
            MidasLoader::loadModels($this->_models);
            $modelsArray = Zend_Registry::get('models');
            foreach ($modelsArray as $key => $tmp) {
                $this->$key = $tmp;
            }
        }
        if (isset($this->_daos)) {
            foreach ($this->_daos as $dao) {
                Zend_Loader::loadClass($dao.'Dao', BASE_PATH.'/core/models/dao');
            }
        }

        Zend_Registry::set('components', array());
        if (isset($this->_components)) {
            foreach ($this->_components as $component) {
                $nameComponent = $component.'Component';
                Zend_Loader::loadClass($nameComponent, BASE_PATH.'/core/controllers/components');
                @$this->Component->$component = new $nameComponent();
            }
        }

        Zend_Registry::set('forms', array());
        if (isset($this->_forms)) {
            foreach ($this->_forms as $forms) {
                $nameForm = $forms.'Form';

                Zend_Loader::loadClass($nameForm, BASE_PATH.'/core/controllers/forms');
                @$this->Form->$forms = new $nameForm();
            }
        }
    }
}
