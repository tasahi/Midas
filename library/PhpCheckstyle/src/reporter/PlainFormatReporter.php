<?php
/*
 *  $Id: PlainFormatReporter.php 26734 2005-07-15 01:34:26Z hkodungallur $
 *
 *  Copyright(c) 2004-2005, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Source License version 2.1
 *  (See http://www.spikesource.com/license.html)
 */

require_once PHPCHECKSTYLE_HOME_DIR."/src/reporter/Reporter.php";

/**
 * Writes the errors into in plain text to the output file
 * Format:
 * ================================
 *  File1:
 *      Line X: Error Message
 *      Line Y: Error Message
 *
 *  File 2:
 *      Line X: Error Message
 *      Line Y: Error Message
 * ================================
 *
 * @author Hari Kodungallur <hkodungallur@spikesource.com>
 */
class PlainFormatReporter extends Reporter {

	/**
	 * Constructor; calls parent's constructor
	 *
	 * @param $ofile the file name
	 */
	public function PlainFormatReporter($ofile = false) {
		parent::__construct($ofile);
	}

	/**
	 * @see Reporter::start
	 * make sure that the file is opened
	 *
	 */
	public function start() {
		$this->_ensureFileOpen();
	}

	/**
	 * @see Reporter::stop
	 * make sure that the file is closed
	 */
	public function stop() {
		$this->_ensureFileClosed();
	}

	/**
	 * @see Reporter::stop
	 * Add a new line with the new file name
	 *
	 * @param $phpFile the file currently processed
	 */
	public function currentlyProcessing($phpFile) {
		parent::currentlyProcessing($phpFile);
		$this->_write("\nFile: ".$this->currentPhpFile.PHP_EOL);
	}

	/**
	 * @see Reporter::writeError
	 * Tab the line and write the error message
	 *
	 * @param Integer $line the line number
	 * @param String $check the name of the check
	 * @param String $message the text
	 * @param String $level the severity level
	 */
	public function writeError($line, $check, $message, $level = WARNING) {
		$msg = "\t".$level." Line:".$line.": ".$message.PHP_EOL;
		$this->_write($msg);
	}

	private function _write($message) {
		if ($this->_ensureFileOpen()) {
			fwrite($this->fileHandle, $message);
		}
	}

	private function _ensureFileOpen() {
		if ($this->fileHandle === false) {
			$this->fileHandle = fopen($this->outputFile, "w");
		}
		return $this->fileHandle;
	}

	private function _ensureFileClosed() {
		if ($this->fileHandle) {
			fclose($this->fileHandle);
			$this->outputFile = false;
		}
	}
}
