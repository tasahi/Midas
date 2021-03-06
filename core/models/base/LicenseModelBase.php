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

/** License model base. */
abstract class LicenseModelBase extends AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'license';
        $this->_key = 'license_id';

        $this->_mainData = array(
            'license_id' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'fulltext' => array('type' => MIDAS_DATA),
        );
        $this->initialize();
    }

    /**
     * Return all licenses.
     *
     * @return array list of license DAOs
     */
    abstract public function getAll();

    /**
     * Return a license given its name.
     *
     * @param string $name name of the license
     * @return array list of license DAOs
     */
    abstract public function getByName($name);
}
