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

require_once BASE_PATH.'/library/KWUtils.php';
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/** KWUtils tests */
class KWUtilsTest extends ControllerTestCase
{
    /** tests mkDir function */
    public function testMkDir()
    {
        $tmpDir = UtilityComponent::getTempDirectory();
        $testDir = $tmpDir.'/'.'KWUtilsTest';
        $this->assertTrue(KWUtils::mkDir($testDir));
        // now clean up
        KWUtils::recursiveRemoveDirectory($testDir);
    }

    /** tests createSubDirectories function */
    public function testCreateSubDirectories()
    {
        // test creating directories, do this in the tmp dir
        //
        // create a nested set of directories
        $tmpDir = UtilityComponent::getTempDirectory();
        $subDirs = array('KWUtilsTest', '1', '2', '3');
        $outDir = KWUtils::createSubDirectories($tmpDir.'/', $subDirs);

        // now check that all the subdirs have been created

        // according to what we wanted
        $this->assertFileExists($tmpDir);
        // and what we got back
        $this->assertFileExists($outDir);

        $currDir = $tmpDir;
        foreach ($subDirs as $subdir) {
            $currDir = $currDir.'/'.$subdir;
            $this->assertFileExists($currDir);
            $this->assertTrue(is_dir($currDir));
        }

        $topDir = UtilityComponent::getTempDirectory().'/KWUtilsTest';
        KWUtils::recursiveRemoveDirectory($topDir);
    }

    /** tests exec function */
    public function testExec()
    {
        // Do not run this on Windows until can figure out how to test it
        if (KWUtils::isWindows()) {
            return;
        }

        // not sure how to test this exactly, for now create a tmp dir, check
        // the value of pwd in it

        $initialCwd = getcwd();
        $output = null;
        $returnVal = null;

        // create a tmp dir for this test
        $execDir = UtilityComponent::getTempDirectory().'/KWUtilsTest';
        mkdir($execDir);
        $cmd = 'pwd';
        $chdir = $execDir;
        KWUtils::exec($cmd, $output, $chdir, $returnVal);
        // $output should have one value, the same as execDir

        $postCwd = getcwd();
        // check that we are back to the original directory after the exec
        // we are already checking with this test that we chdir correctly because
        // the test changes into a dir and then performs pwd there
        $this->assertEquals($initialCwd, $postCwd, "exec didn't correctly return to the origin directory");

        // yuck, need to do a bit of munging to get around tests/.. in BASE_PATH
        $execDir = str_replace('tests/../', '', $execDir);
        // and now replace any // with /,
        // the // doesn't affect functionality on the
        // filesystem, but will cause string inequality
        $execDir = str_replace('//', '/', $execDir);

        $this->assertEquals($execDir, $output[0]);
        // returnVal should be 0
        $this->assertEquals($returnVal, 0);
        // now clean up the tmp dir
        rmdir($execDir);

        // pass in a bad cwd, be sure that we get an exception
        $chdir = '/this/dir/probably/will/not/exist/anywhere';
        $output = null;
        $returnVal = null;
        try {
            KWUtils::exec($cmd, $output, $chdir, $returnVal);
            $this->fail();
        } catch (Zend_Exception $ze) {
            // this is the correct behavior
            $this->assertTrue(true);
        }
        $postCwd = getcwd();
        // ensure we are still in the same directory
        $this->assertEquals($initialCwd, $postCwd, "exec didn't correctly return to the origin directory");

        // now try to run pwd passing in a null chdir
        // should return an output the same as the initialCwd
        $chdir = null;
        $output = null;
        $returnVal = null;
        KWUtils::exec($cmd, $output, $chdir, $returnVal);
        // ensure that it ran in the initialCwd
        $this->assertEquals($initialCwd, $output[0]);

        // check that we are still in the output dir
        $postCwd = getcwd();
        $this->assertEquals($initialCwd, $postCwd);
    }

    /** tests appendStringIfNot function */
    public function testAppendStringIfNot()
    {
        // try one that doesn't have the suffix:
        $subject = 'blah';
        $ext = '.exe';
        $subject = KWUtils::appendStringIfNot($subject, $ext);
        $this->assertEquals($subject, 'blah.exe');
        // now try one that already has the suffix
        $subject = 'blah';
        $ext = '.exe';
        $subject = KWUtils::appendStringIfNot($subject, $ext);
        $this->assertEquals($subject, 'blah.exe');
    }

    /** tests findApp function */
    public function testFindApp()
    {
        // first try something that should be in the path, php, and check that it
        // is executable
        $app = 'php';
        if (KWUtils::isWindows()) {
            $app .= '.exe';
        }

        KWUtils::findApp($app, true);
        // now try something that is unlikely to be in the path
        try {
            KWUtils::findApp('php_exe_that_is_vanishingly_likeley_to_be_in_the_path', true);
            $this->fail('Should have caught exception but did not, testFindApp');
        } catch (Zend_Exception $ze) {
            // if we end up here, that is the correct behavior
            $this->assertTrue(true);
        }
    }

    /** tests isExecutable function */
    public function testIsExecutable()
    {
        // this is tricky to test, as it is hard to make assumptions that hold
        // up across platforms
        $app = 'php';
        if (KWUtils::isWindows()) {
            $app .= '.exe';
        }

        // for now assume that 'php' will not be found when not looking in the path
        $this->assertFalse(KWUtils::isExecutable($app, false));
        // but 'php' will be found in the path
        $this->assertTrue(KWUtils::isExecutable($app, true));
    }

    /** tests prepareExecCommand function */
    public function testPrepareExecCommand()
    {
        $app = 'php';
        if (KWUtils::isWindows()) {
            $app .= '.exe';
        }

        $returnVal = KWUtils::prepareExecCommand($app, array('blah1', 'blah2', 'blah3'));
        $appPath = KWUtils::findApp($app, true);
        $this->assertEquals($returnVal, "'".$appPath."' 'blah1' 'blah2' 'blah3'");
    }

    /** tests recursiveRemoveDirectory function */
    public function testRecursiveRemoveDirectory()
    {
        // test some basic exception handling
        $this->assertFalse(KWUtils::recursiveRemoveDirectory(''));
        $this->assertFalse(KWUtils::recursiveRemoveDirectory('thisstringisunlikelytobeadirectory'));

        // create a two-level directory
        $testParentDir = UtilityComponent::getTempDirectory().'/KWUtilsParentDir';
        mkdir($testParentDir);
        $testChildDir = UtilityComponent::getTempDirectory().'/KWUtilsParentDir/ChildDir';
        mkdir($testChildDir);
        copy(BASE_PATH.'/tests/testfiles/search.png', $testChildDir.'/testContent.png');
        $this->assertTrue(file_exists($testChildDir.'/testContent.png'));

        // recursively remove the directory
        KWUtils::recursiveRemoveDirectory($testParentDir);
        $this->assertFalse(file_exists($testParentDir));
    }
}
