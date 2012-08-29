<?php
/**
 * Simple class for CSV file manipulation.
 * You can read, write new value to the
 * CSV file and create new CSV file with this
 * class.
 * 
 * @author Taynov Dmitry
 * @email dtaynov@gmail.com
 * @copyright Taynov Studio
 */
 
class Csv
{
    /**
     * All parsed CSV rows
     * @var array
     */
    protected $rows;
    /**
     * All links to row data columns
     * @var array
     */
    protected $columns;
    /**
     * Path to CSV file
     * @var string
     */
    protected $file;
    /**
     * Element delimiter
     * @var string
     */
    protected $splitter     = ',';
    /**
     * Element wrapper
     * @var string
     */
    protected $wrapper      = '"';
    /**
     * If first row is the names of columns
     * then set it to true. Default false
     * @var bool
     */
    protected $columnNames  = false;
    /**
     * Total count of the rows
     * @var int
     */
    protected $count        = 0;
    /**
     * Associated names of the cells
     * @var array
     */
    protected $map          = null;
    private $readOnly       = false;

    /**
     * CSV file options
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (isset ($options['wrapper']))
                $this->wrapper($options['wrapper']);
            if (isset ($options['splitter']))
                $this->splitter($options['splitter']);
            if (isset ($options['columnNames']))
                $this->columnNames($options['columnNames']);
            if (isset ($options['map']))
                $this->map($options['map']);
            if (isset ($options['file']))
                $this->file($options['file']);
        } else if (is_string($options)) {
            $this->file($options);
        }
    }

    /**
     * Read CSV file by line
     * @param $file
     * @return int Count of loaded rows
     */
    public function load($file = null)
    {
        // If file set then load them else set in in object
        if ($file === null)
            $file = $this->file();
        else
            $this->file($file);
        
        if (!is_file($file))
            throw new \Exception('Wrong CSV file: ' . $file);

        // Read and parse data from file
        $h = fopen($file, 'rb');
        while (($rawRow = fgets($h)) !== false) {
            $this->rows[] = $this->parse(
                trim($rawRow)
            );
        }
        fclose($h);
        // Set default mapper, if need of course
        if ($this->columnNames) {
            $firstRow = array_shift ($this->rows);
            // And clear columns first element
            foreach ($this->columns as &$col)
                array_shift($col);
            if ($this->map === null)
                $this->map = $firstRow;
        }
        // And calculate the total count of the rows
        $this->count = count ($this->rows);
        return $this->count;
    }

    /**
     * Return parsed row
     * @param string $rawRow
     * @return array
     */
    private function parse ($rawRow)
    {
        $row = $this->explode($rawRow);
        // Clear wrapper char
        foreach ($row as $key => &$cell) {
            $cell = trim (
                trim($cell), $this->wrapper()
            );
            $this->columns[$key][] = $cell;
        }
        return $row;
    }

    /**
     * Split raw string from the CSV file
     * to array elements
     * @param $rawRow
     * @return array
     */
    private function explode ($rawRow)
    {
        if ($this->wrapper !== '') {
            preg_match_all (
                '`[\d+\.]+|\\'
                . $this->wrapper()
                . '[^\\' . $this->wrapper() . ']+\\'
                . $this->wrapper() . '`is', $rawRow, $rowMatches
            );
            $row = $rowMatches[0];
        } else
            $row = explode($this->splitter(), $rawRow);
        return $row;
    }

    /**
     * Accept the mapper rules
     * for any rows of CSV file.
     * @param $row
     * @return array
     */
    private function mapped ($row)
    {
        foreach ($row as $key => $cell) {
            $row[$this->map[$key]] = $cell;
        }
        return $row;
    }

    /**
     * Splitter getter/setter
     * @param null $splitter
     * @return Nanocoding_Csv|string
     */
    public function splitter($splitter = null)
    {
        if ($splitter === null) return $this->splitter;
        else {
            $this->splitter = $splitter;
            return $this;
        }
    }

    /**
     * Wrapper setter/getter
     * @param null $wrapper
     * @return Nanocoding_Csv|string
     */
    public function wrapper($wrapper = null)
    {
        if ($wrapper === null) return $this->wrapper;
        else {
            $this->wrapper = $wrapper;
            return $this;
        }
    }

    /**
     * Set is first line is name of columns
     * @param $isNameOfColumn
     * @return Nanocoding_Csv
     */
    public function columnNames($isNameOfColumn)
    {
        $this->columnNames = (bool) $isNameOfColumn;
        return $this;
    }

    /**
     * Return all parsed rows
     * @param array|null $rows
     * @return Nanocoding_Csv
     */
    public function rows(array $rows = null)
    {
        if ($rows === null) return $this->rows;
        else {
            $this->rows = $rows;
            return $this;
        }
    }

    /**
     * Set path to csv file or get it if $file param is null
     * @param null $file
     * @return Nanocoding_Csv
     */
    public function file ($file = null)
    {
        if ($file === null) return $this->file;
        else {
            $this->file = $file;
            return $this;
        }
    }

    /**
     * Get row data by id
     * @param $id
     * @return
     */
    public function row($id)
    {
        return $this->rows[$id];
    }

    /**
     * Get cell data by ids
     * @param $idRow
     * @param $idColumn
     * @return
     */
    public function cell($idRow, $idColumn)
    {
        return $this->rows[$idRow][$this->columnId($idColumn)];
    }

    /**
     * Get column id from map
     * if exists of course
     * 
     * @throws Exception
     * @param $idColumn
     * @return mixed
     */
    private function columnId($idColumn)
    {
        if (is_string($idColumn))
            $idColumn = array_search($idColumn, $this->map);
        if ($idColumn === false)
            throw new \Exception('Cell ID is empty');
        return $idColumn;
    }

    /**
     * Count Rows
     * @return int
     */
    public function count ()
    {
        return $this->count;
    }

    /**
     * Mapper
     * @param array|null $map
     * @return Nanocoding_Csv
     */
    public function map (array $map = null)
    {
        if ($map === null) return $this->map;
        else {
            $this->map = $map;
            return $this;
        }
    }

    /**
     * Add new row to CSV
     * @param array $row
     * @return \Nanocoding_Csv
     */
    public function add(array $row)
    {
        $this->isWriteable();
        $this->rows[] = $row;
        foreach ($row as $key => &$cell)
            $this->columns[$key][] = $cell;
        $this->count++;
        return $this;
    }

    /**
     * Remove row
     * @param $id
     * @return \Nanocoding_Csv
     */
    public function remove($id)
    {
        $this->isWriteable();
        unset ($this->rows[$id]);
        foreach ($this->columns as $column)
            unset ($column[$id]);
        $this->count--;
        return $this;
    }

    /**
     * Save CSV file
     * @param null $file
     * @return void
     */
    public function save($file = null)
    {
        $this->isWriteable();
        if ($file === null)
            $file = $this->file();
        $h = fopen($file, 'w');
        if ($h === false)
            throw new \Exception('Can not create the file');
        if ($this->rows) {
            foreach ($this->rows as $row) {
                fputs($h, $this->rowToStr($row) . "\r\n");
            }
        }
        fclose($h);
    }

    /**
     * Convert array row to string
     * @param array $row
     * @return string
     */
    private function rowToStr(array $row)
    {
        $str = '';
        $row = $this->fillRow($row);
        foreach ($row as $cell) {
            $str .= $this->wrapper() . $cell . $this->wrapper() . $this->splitter();
        }
        return trim ($str, $this->splitter());
    }
    
    /**
     * If row not have all columns values fill empty,
     * if row have more columns value then Exception.
     * @throws Exception
     * @param array $row
     * @return array
     */
    private function fillRow(array $row)
    {
        $countColumns   = count ($this->columns);
        $countRows      = count ($row);
        $diff = $countColumns - $countRows;
        if ($diff <= 0)
            return $row;
        else
            return array_merge(
                $row,
                array_fill($countRows, $countColumns, "")
            );
    }

    /**
     * Mark object as read only
     * @param null $readOnly
     * @return bool|Nanocoding_Csv
     */
    public function readOnly($readOnly = null)
    {
        if ($readOnly === null)
            return $this->readOnly;
        else {
            $this->readOnly = $readOnly;
            return $this;
        }
    }

    /**
     * Exception generator for
     * not writable case
     * 
     * @throws Exception
     * @return void
     */
    private function isWriteable()
    {
        if ($this->readOnly())
            throw new \Exception ('CSV Object only read');
    }

    /**
     * Return all columns
     * @return array
     */
    public function columns()
    {
        return $this->columns;
    }

    /**
     * Return only one column by ID
     * @param $id
     * @return
     */
    public function column ($id)
    {
        return $this->columns[$this->columnId($id)];
    }

    /**
     * Fast search element in table.
     * Only a strict match and only the first search result
     * @param $query
     * @param $columnId
     * @return bool|array
     */
    public function find ($query, $columnId = null)
    {
        $row = false;
        if ($columnId !== null) {
            $row = $this->findByColumn($query, $columnId);
        } else {
            foreach ($this->columns as $columnId => $column) {
                $row = $this->findByColumn($query, $columnId);
                if ($row !== false) break;
            }
        }
        return $row;
    }

    /**
     * Fast search element in table by Columns Id
     * Only a strict match and only the first search result
     * 
     * @param $query
     * @param $columnId
     * @return bool|array
     */
    public function findByColumn ($query, $columnId)
    {
        $column = $this->column($columnId);
        if ($column !== null) {
            if (($id = array_search($query, $this->column($columnId))) or $id === 0)
                return $this->row($id);
        }
        return false;
    }
}
