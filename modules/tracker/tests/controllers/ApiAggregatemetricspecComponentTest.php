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

require_once BASE_PATH.'/core/tests/controllers/api/RestCallMethodsTestCase.php';

/** API test for tracker module ApiaggregatemetricspecComponent. */
class Tracker_ApiAggregatemetricspecComponentTest extends RestCallMethodsTestCase
{
    public $moduleName = 'tracker';

    /** Setup. */
    public function setUp()
    {
        $this->enabledModules = array('api', 'scheduler', $this->moduleName);
        $this->_models = array('Assetstore', 'Community', 'Setting', 'User');
        $this->setupDatabase(array('default'));
        $this->setupDatabase(array('aggregateMetric'), 'tracker');

        ControllerTestCase::setUp();
    }

    /**
     * Test getting an existing existing aggregate metric spec with a set of params, via GET.
     *
     * @throws Zend_Exception
     */
    public function testGET()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        $restParams = array(
            'token' => $token,
        );
        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('GET', '/tracker/aggregatemetricspec/1');

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var Tracker_AggregateMetricSpecDao $specDao */
        $specDao = $aggregateMetricSpecModel->initDao('AggregateMetricSpec', json_decode(json_encode($resp['body']), true), $this->moduleName);

        $this->assertEquals($specDao->getProducerId(), '100');
        $this->assertEquals($specDao->getName(), '95th Percentile Greedy error');
        $this->assertEquals($specDao->getDescription(), '95th Percentile Greedy error');
        $this->assertEquals($specDao->getSpec(), "percentile('Greedy error', 95)");
    }

    /**
     * Test creating an existing aggregate metric spec with a set of params, via POST.
     *
     * @throws Zend_Exception
     */
    public function testPOST()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        $restParams = array(
            'token' => $token,
            'producer_id' => 100,
            'name' => 'POST 23 percentile',
            'description' => 'opaque',
            'spec' => "percentile('POST', 23)",
        );

        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('POST', '/tracker/aggregatemetricspec/');

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var Tracker_AggregateMetricSpecDao $specDao */
        $specDao = $aggregateMetricSpecModel->initDao('AggregateMetricSpec', json_decode(json_encode($resp['body']), true), $this->moduleName);

        // Test the result of the API call.
        $this->assertEquals($specDao->getProducerId(), $restParams['producer_id']);
        $this->assertEquals($specDao->getName(), $restParams['name']);
        $this->assertEquals($specDao->getDescription(), $restParams['description']);
        $this->assertEquals($specDao->getSpec(), $restParams['spec']);

        // Load from the DB and test again.
        $specDao = $aggregateMetricSpecModel->load($specDao->getAggregateMetricSpecId());

        $this->assertEquals($specDao->getProducerId(), $restParams['producer_id']);
        $this->assertEquals($specDao->getName(), $restParams['name']);
        $this->assertEquals($specDao->getDescription(), $restParams['description']);
        $this->assertEquals($specDao->getSpec(), $restParams['spec']);
    }

    /**
     * Test updating an existing aggregate metric spec with a set of params, via PUT.
     *
     * @throws Zend_Exception
     */
    public function testPUT()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        $restParams = array(
            'token' => $token,
            'producer_id' => 200,
            'name' => 'NewAlgo 23 percentile',
            'description' => 'vivid',
            'spec' => "percentile('NewAlgo', 23)",
        );

        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('PUT', '/tracker/aggregatemetricspec/1');

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var Tracker_AggregateMetricSpecDao $specDao */
        $specDao = $aggregateMetricSpecModel->initDao('AggregateMetricSpec', json_decode(json_encode($resp['body']), true), $this->moduleName);

        // Test the result of the API call.
        $this->assertEquals($specDao->getProducerId(), $restParams['producer_id']);
        $this->assertEquals($specDao->getName(), $restParams['name']);
        $this->assertEquals($specDao->getDescription(), $restParams['description']);
        $this->assertEquals($specDao->getSpec(), $restParams['spec']);

        // Load from the DB and test again.
        $specDao = $aggregateMetricSpecModel->load(1);

        $this->assertEquals($specDao->getProducerId(), $restParams['producer_id']);
        $this->assertEquals($specDao->getName(), $restParams['name']);
        $this->assertEquals($specDao->getDescription(), $restParams['description']);
        $this->assertEquals($specDao->getSpec(), $restParams['spec']);
    }

    /**
     * Test deleting an existing aggregate metric spec, via DELETE.
     *
     * @throws Zend_Exception
     */
    public function testDELETE()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        $restParams = array(
            'token' => $token,
        );

        /** @var Tracker_AggregateMetricSpecModel $aggregateMetricSpecModel */
        $aggregateMetricSpecModel = MidasLoader::loadModel('AggregateMetricSpec', 'tracker');
        /** @var Tracker_AggregateMetricSpecDao $specDao */
        $specDao = $aggregateMetricSpecModel->load(1);
        $this->assertNotEquals($specDao, false);

        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('DELETE', '/tracker/aggregatemetricspec/1');

        $specDao = $aggregateMetricSpecModel->load(1);
        $this->assertEquals($specDao, false);
    }
}
