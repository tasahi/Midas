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

/** Upgrade the core to version 3.0.15. */
class Upgrade_3_0_15 extends MIDASUpgrade
{
    /** Post database upgrade. */
    public function postUpgrade()
    {
        $this->addTableField('user', 'city', 'varchar(100)', ' character varying(100)', null);
        $this->addTableField('user', 'country', 'varchar(100)', ' character varying(100)', null);
        $this->addTableField('user', 'website', 'varchar(255)', ' character varying(255)', null);
        $this->addTableField('user', 'biography', 'varchar(255)', ' character varying(255)', null);
    }
}
