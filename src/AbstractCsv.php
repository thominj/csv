<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/csv/
* @version 7.0.0
* @package League.csv
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace League\Csv;

use IteratorAggregate;
use JsonSerializable;
use League\Csv\Config;
use SplFileInfo;
use SplFileObject;

/**
 *  An abstract class to enable basic CSV manipulation
 *
 * @package League.csv
 * @since  4.0.0
 *
 */
abstract class AbstractCsv implements JsonSerializable, IteratorAggregate
{
    /**
     *  UTF-8 BOM sequence
     */
    const BOM_UTF8 = "\xEF\xBB\xBF";

    /**
     * UTF-16 BE BOM sequence
     */
    const BOM_UTF16_BE = "\xFE\xFF";

    /**
     * UTF-16 LE BOM sequence
     */
    const BOM_UTF16_LE = "\xFF\xFE";

    /**
     * UTF-16 BE BOM sequence
     */
    const BOM_UTF32_BE = "\x00\x00\xFE\xFF";

    /**
     * UTF-16 LE BOM sequence
     */
    const BOM_UTF32_LE = "\x00\x00\xFF\xFE";

    /**
     * The constructor path
     *
     * can be a SplFileInfo object or the string path to a file
     *
     * @var \SplFileObject|string
     */
    protected $path;

    /**
     * The file open mode flag
     *
     * @var string
     */
    protected $open_mode;

    /**
     *  Csv Controls Trait
     */
    use Config\Controls;

    /**
     *  Csv Factory Trait
     */
    use Config\Factory;

    /**
     * Csv Ouputting Trait
     */
    use Config\Output;

    /**
     *  Stream Filter API Trait
     */
    use Config\StreamFilter;

    /**
     * Create a new instance
     *
     * The path must be an SplFileInfo object
     * an object that implements the `__toString` method
     * a path to a file
     *
     * @param object|string $path      The file path
     * @param string        $open_mode the file open mode flag
     */
    public function __construct($path, $open_mode = 'r+')
    {
        ini_set('auto_detect_line_endings', '1');

        $this->path      = $this->normalizePath($path);
        $this->open_mode = strtolower($open_mode);
        $this->flags     = SplFileObject::READ_CSV|SplFileObject::DROP_NEW_LINE;
        $this->initStreamFilter($this->path);
    }

    /**
     * Return a normalize path which could be a SplFileObject
     * or a string path
     *
     * @param object|string $path the filepath
     *
     * @return \SplFileObject|string
     */
    protected function normalizePath($path)
    {
        if ($path instanceof SplFileObject) {
            return $path;
        } elseif ($path instanceof SplFileInfo) {
            return $path->getPath().'/'.$path->getBasename();
        }

        return trim($path);
    }

    /**
     * The destructor
     */
    public function __destruct()
    {
        $this->path = null;
    }

    /**
     * Return the CSV Iterator
     *
     * @return \SplFileObject
     */
    public function getIterator()
    {
        $iterator = $this->path;
        if (! $iterator instanceof SplFileObject) {
            $iterator = new SplFileObject($this->getStreamFilterPath(), $this->open_mode);
        }
        $iterator->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
        $iterator->setFlags($this->flags);

        return $iterator;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOutputIterator()
    {
        return $this->getIterator();
    }

    /**
     * Create a {@link AbstractCsv} instance from another {@link AbstractCsv} object
     *
     * @param string $class_name the class to be instantiated
     * @param string $open_mode  the file open mode flag
     *
     * @return static
     */
    protected function newInstance($class_name, $open_mode)
    {
        $csv = new $class_name($this->path, $open_mode);
        $csv->delimiter    = $this->delimiter;
        $csv->enclosure    = $this->enclosure;
        $csv->escape       = $this->escape;
        $csv->encodingFrom = $this->encodingFrom;
        $csv->flags        = $this->flags;
        $csv->input_bom    = $this->input_bom;
        $csv->output_bom   = $this->output_bom;
        $csv->newline      = $this->newline;

        return $csv;
    }

    /**
     * Create a {@link Writer} instance from a {@link AbstractCsv} object
     *
     * @param string $open_mode the file open mode flag
     *
     * @return \League\Csv\Writer
     */
    public function newWriter($open_mode = 'r+')
    {
        return $this->newInstance('\League\Csv\Writer', $open_mode);
    }

    /**
     * Create a {@link Reader} instance from a {@link AbstractCsv} object
     *
     * @param string $open_mode the file open mode flag
     *
     * @return \League\Csv\Reader
     */
    public function newReader($open_mode = 'r+')
    {
        return $this->newInstance('\League\Csv\Reader', $open_mode);
    }

    /**
    * Validate a variable to be stringable
    *
    * @param object|string $str
    *
    * @return bool
    */
    public static function isValidString($str)
    {
        return is_scalar($str) || (is_object($str) && method_exists($str, '__toString'));
    }
}
