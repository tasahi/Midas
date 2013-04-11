<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

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

/** 
 * This component is used to create and manages
 * paraview (pvpython) instances.
 */
class Pvw_ParaviewComponent extends AppComponent
{
  /**
   * Creates a new pvpython instance and a corresponding database record for it.
   * @param item The item dao to visualize
   * @return The pvw_instance dao
   */
  public function createAndStartInstance($item, $appname, $progressDao = null)
    {
    $progressModel = MidasLoader::loadModel('Progress');
    if($progressDao)
      {
      $step = 1;
      $progressDao->setMaximum(5);
      $progressModel->save($progressDao);
      $progressModel->updateProgress($progressDao, $step, 'Checking available ports...');
      }
    $settingModel = MidasLoader::loadModel('Setting');
    $pvpython = $settingModel->getValueByName('pvpython', 'pvw');
    $staticContent = $settingModel->getValueByName('staticcontent', 'pvw');
    $application = BASE_PATH.'/modules/pvw/apps/'.$appname.'.py';
    if(!is_file($application))
      {
      throw new Zend_Exception('No such application: '.$appname, 400);
      }
    if($progressDao)
      {
      $step++;
      $progressModel->updateProgress($progressDao, $step, 'Checking available ports...');
      }

    // TODO critical section of code between getting the open port and listening on it
    // practical solution: add db setting for 'nextPortToTry'.
    $port = $this->_getNextOpenPort();
    if($port === false)
      {
      throw new Zend_Exception('Maximum number of running instances exceeded, try again soon', 503);
      }
    if($progressDao)
      {
      $step++;
      $progressModel->updateProgress($progressDao, $step, 'Starting ParaView instance...');
      }

    $instance = MidasLoader::newDao('InstanceDao', 'pvw');
    $instance->setItemId($item->getKey());
    $instance->setPort($port);
    $instance->setSid(''); // todo?
    $instance->setCreationDate(date('c'));

    $instanceModel = MidasLoader::loadModel('Instance', 'pvw');
    $instanceModel->save($instance);

    $dataPath = $this->_createDataDir($item, $instance);

    $cmdArray = array($pvpython, $application, '--port', $port, '--data', $dataPath);

    // Set static content root if necessary
    if($staticContent && is_dir($staticContent))
      {
      $cmdArray[] = '--content'; // If we want pvw to serve its own static content, pass this arg
      $cmdArray[] = $staticContent;
      }

    // Now start the instance
    $cmd = join(' ', $cmdArray);
    exec(sprintf("%s > %s 2>&1 & echo $!", $cmd, $dataPath.'/pvw.log'), $output);
    $pid = trim(join('', $output));
    if(!is_numeric($pid))
      {
      throw new Zend_Exception('Expected pid output, got: '.$pid, 500);
      }
    if($progressDao)
      {
      $step++;
      $progressModel->updateProgress($progressDao, $step, 'Waiting for port binding...');
      }
    // After we start the process, wait some number of seconds for the port to open up.
    // If it doesn't, something went wrong.
    $portOpen = false;
    for($i = 0; $i < 2 * MIDAS_PVW_STARTUP_TIMEOUT; $i++)
      {
      usleep(500000); // sleep for half a second
      if(UtilityComponent::isPortListening($port))
        {
        $portOpen = true;
        break;
        }
      }
    if(!$portOpen)
      {
      throw new Zend_Exception('Instance did not bind to port within '.MIDAS_PVW_STARTUP_TIMEOUT.' seconds', 500);
      }

    $instance->setPid($pid);
    $instanceModel->save($instance);
    return $instance;
    }

  /**
   * Kills the pvpython process and deletes the instance record from the database
   * @param instance The instance dao to kill
   */
  public function killInstance($instance)
    {
    exec('kill -9 '.$instance->getPid());

    UtilityComponent::rrmdir(BASE_PATH.'/tmp/pvw-data/'.$instance->getKey());

    $instanceModel = MidasLoader::loadModel('Instance', 'pvw');
    $instanceModel->delete($instance);
    }

  /**
   * Return whether or not the given instance is still running
   * @param instance The pvw_instance dao
   */
  public function isRunning($instance)
    {
    exec('ps '.$instance->getPid(), $output);
    return count($output) >= 2;
    }

  /**
   * Uses the admin-configured port settings and allocates the next
   * available port that isn't currently in use. If none are available, returns false.
   */
  private function _getNextOpenPort()
    {
    $settingModel = MidasLoader::loadModel('Setting');
    $ports = $settingModel->getValueByName('ports', 'pvw');
    if(!$ports)
      {
      $ports = '9000,9001'; // some reasonable default
      }
    $ports = explode(',', $ports);
    foreach($ports as $portEntry)
      {
      // TODO accomodate port ranges, e.g. 9000-9005
      $portEntry = trim($portEntry);
      if(!UtilityComponent::isPortListening($portEntry))
        {
        return $portEntry;
        }
      }
    return false;
    }

  /**
   * Symlink the item into a directory
   */
  private function _createDataDir($itemDao, $instanceDao)
    {
    if(!is_dir(BASE_PATH.'/tmp/pvw-data'))
      {
      mkdir(BASE_PATH.'/tmp/pvw-data');
      }
    $path = BASE_PATH.'/tmp/pvw-data/'.$instanceDao->getKey();
    mkdir($path);
    mkdir($path.'/main');

    $itemModel = MidasLoader::loadModel('Item');
    $rev = $itemModel->getLastRevision($itemDao);
    $bitstreams = $rev->getBitstreams();
    $src = $bitstreams[0]->getFullpath();

    symlink($src, $path.'/main/'.$bitstreams[0]->getName());
    return $path;
    }
} // end class
