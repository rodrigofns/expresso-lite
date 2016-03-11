<?php
/**
 * Expresso Lite
 * This class handles the values loaded in the .ini test data files
 *
 * @package ExpressoLiteTest\Functional\Generic
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Generic;

class TestData
{
    /**
     * @var string TEST_DATA_FOLDER Folder containing .ini files with test data
     */
    const TEST_DATA_FOLDER = './data/';

    /**
     * @var string $iniFile The name of the .ini file that contains the test data
     */
    private $iniFile;

    /**
     * @var array Array of arrays will all the values loaded from the .ini test data file
     */
    private $iniFileValues;

    /**
     * @var ExpressoLiteTest The test case to which this test data belongs to.
     */
    private $testCase;

    /**
     * Loads the content of the .ini file containing the test data for a test case.
     * The name of the .ini file must match the name of the test case class
     *
     * @param string $testCase The test case to which the .ini file corresponds
     */
    public function __construct(ExpressoLiteTest $testCase)
    {
        $this->testCase = $testCase;

        $testCaseClass = new \ReflectionClass($testCase);
        $testCaseName = $testCaseClass->getShortName();
        $this->iniFile = TEST_ROOT_PATH . self::TEST_DATA_FOLDER . $testCaseName . '.ini';
        $this->iniFileValues = file_exists($this->iniFile) ?
            parse_ini_file($this->iniFile, true) :
            null;
    }

    /**
     * Returns the value of an entry from the test data file
     *
     * @param string $sectionName The section within the ini files that
     * contains the data for a specific test
     * @param string $key The key for the specific test value
     */
    public function getTestValue($sectionName, $key)
    {
        if ($this->iniFileValues === null) {
            throw new \Exception('Could not find a test data file named ' . $this->iniFile);
        } else if (!isset($this->iniFileValues[$sectionName])) {
            throw new \Exception('Could not find a section named [' . $sectionName . '] in file ' . $this->iniFile);
        } else if (!isset($this->iniFileValues[$sectionName][$key])) {
            throw new \Exception('Could not find a value with key ' . $key . ' in section [' . $sectionName . '] in file ' . $this->iniFile);
        } else {
            return str_replace(
                    '$testId',
                    $this->testCase->getUniqueId(),
                    $this->iniFileValues[$sectionName][$key]);
        }
    }
}
